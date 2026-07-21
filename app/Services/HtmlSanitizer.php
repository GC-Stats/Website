<?php

/**
 * GC-Stats — HTML sanitizer
 *
 * Strips a rich-text HTML fragment down to an allow-listed set of tags and
 * attributes before it's stored. News articles are rendered unescaped on
 * the public site (`{!! $content !!}` in resources/views/news/show.blade.php,
 * matching the `prose` typography classes there) and, since publisher
 * accounts (external content partners, not vetted site staff) can now write
 * this field themselves, an unsanitized value would be stored XSS reaching
 * every site visitor. This runs over a real DOM (not regex) so it can't be
 * tricked by malformed/nested markup, and rejects `javascript:`/`data:`
 * URLs in href/src rather than trying to blocklist specific tags.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;

class HtmlSanitizer
{
    /**
     * Matches the tags actually styled by the `prose` classes on the public
     * article page — nothing else is meaningful there anyway.
     *
     * @var array<string, list<string>> allowed attributes per tag
     */
    private const ALLOWED_TAGS = [
        'p' => [], 'br' => [], 'hr' => [],
        'h1' => [], 'h2' => [], 'h3' => [], 'h4' => [], 'h5' => [], 'h6' => [],
        'strong' => [], 'b' => [], 'em' => [], 'i' => [], 'u' => [], 's' => [],
        'ul' => [], 'ol' => [], 'li' => [],
        'blockquote' => [],
        'code' => [], 'pre' => [],
        'a' => ['href', 'title', 'target', 'rel'],
        'img' => ['src', 'alt', 'title', 'width', 'height'],
        'table' => [], 'thead' => [], 'tbody' => [], 'tr' => [], 'th' => [], 'td' => [],
        'figure' => [], 'figcaption' => [],
        'span' => ['style'],
    ];

    private const ALLOWED_URL_SCHEMES = ['http', 'https', 'mailto'];

    /**
     * The only `style` declarations kept by cleanAttributes() — matches what
     * the admin rich text editor's forecolor/backcolor toolbar buttons emit
     * (span style="color:..."/"background-color:..."). Everything else
     * (url(), expression(), font/position tricks, ...) is dropped.
     */
    private const ALLOWED_STYLE_PROPERTIES = ['color', 'background-color'];

    private const SAFE_STYLE_VALUE_PATTERN = '/^(#[0-9a-f]{3}|#[0-9a-f]{4}|#[0-9a-f]{6}|#[0-9a-f]{8}|rgba?\(\s*[\d.]+%?\s*,\s*[\d.]+%?\s*,\s*[\d.]+%?\s*(,\s*[\d.]+\s*)?\)|[a-z]+)$/i';

    /**
     * Tags whose *content* is dangerous, not just the wrapper — a plain
     * "unwrap and keep the children" pass (as done for e.g. a stray <div>)
     * would leave `<script>alert(1)</script>`'s text node behind as inert
     * but still-injected text. These are removed wholesale, content included.
     */
    private const STRIP_ENTIRELY = [
        'script', 'style', 'iframe', 'object', 'embed', 'noscript', 'template',
        'form', 'input', 'button', 'select', 'textarea', 'svg', 'math',
    ];

    public function sanitize(string $html): string
    {
        if (trim($html) === '') {
            return '';
        }

        $dom = new DOMDocument;

        // Wrap in a container so we can pull the sanitized body back out;
        // LIBXML_NOERROR/NOWARNING silence libxml's complaints about
        // being fed an HTML fragment rather than a full document.
        @$dom->loadHTML(
            '<?xml encoding="utf-8"?><div id="__root__">'.$html.'</div>',
            LIBXML_NOERROR | LIBXML_NOWARNING
        );

        $root = $dom->getElementById('__root__');

        if (! $root) {
            return '';
        }

        $this->cleanNode($dom, $root);

        $output = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $output .= $dom->saveHTML($child);
        }

        return trim($output);
    }

    private function cleanNode(DOMDocument $dom, DOMNode $node): void
    {
        foreach (iterator_to_array($node->childNodes) as $child) {
            if ($child instanceof DOMText) {
                continue;
            }

            if (! $child instanceof DOMElement) {
                $node->removeChild($child);

                continue;
            }

            $tag = strtolower($child->tagName);

            if (in_array($tag, self::STRIP_ENTIRELY, true)) {
                $node->removeChild($child);

                continue;
            }

            if (! array_key_exists($tag, self::ALLOWED_TAGS)) {
                // Unwrap rather than delete outright — keeps the text
                // content of e.g. a stripped <div> instead of losing it.
                $this->cleanNode($dom, $child);

                while ($child->firstChild) {
                    $node->insertBefore($child->firstChild, $child);
                }

                $node->removeChild($child);

                continue;
            }

            $this->cleanAttributes($child, self::ALLOWED_TAGS[$tag]);
            $this->cleanNode($dom, $child);
        }
    }

    /**
     * @param  list<string>  $allowedAttributes
     */
    private function cleanAttributes(DOMElement $element, array $allowedAttributes): void
    {
        foreach (iterator_to_array($element->attributes ?? []) as $attribute) {
            $name = strtolower($attribute->name);

            if (! in_array($name, $allowedAttributes, true)) {
                $element->removeAttribute($attribute->name);

                continue;
            }

            if (in_array($name, ['href', 'src'], true) && ! self::isSafeUrl($attribute->value)) {
                $element->removeAttribute($attribute->name);

                continue;
            }

            if ($name === 'style') {
                $safeStyle = self::sanitizeStyleValue($attribute->value);

                if ($safeStyle === '') {
                    $element->removeAttribute($attribute->name);
                } else {
                    $element->setAttribute('style', $safeStyle);
                }
            }
        }

        if ($element->tagName === 'a' && $element->hasAttribute('target')) {
            // Prevent target="_blank" reverse-tabnabbing regardless of what
            // rel was (or wasn't) supplied.
            $element->setAttribute('rel', 'noopener noreferrer');
        }
    }

    /**
     * Keeps only allow-listed `property: value` declarations with a value
     * matching SAFE_STYLE_VALUE_PATTERN, dropping everything else
     * (url(), expression(), calc(), custom properties, ...) rather than
     * trying to blocklist specific dangerous constructs.
     */
    private static function sanitizeStyleValue(string $style): string
    {
        $safeDeclarations = [];

        foreach (explode(';', $style) as $declaration) {
            if (! str_contains($declaration, ':')) {
                continue;
            }

            [$property, $value] = explode(':', $declaration, 2);
            $property = strtolower(trim($property));
            $value = trim($value);

            if (! in_array($property, self::ALLOWED_STYLE_PROPERTIES, true)) {
                continue;
            }

            if (preg_match(self::SAFE_STYLE_VALUE_PATTERN, $value) !== 1) {
                continue;
            }

            $safeDeclarations[] = "{$property}: {$value}";
        }

        return implode('; ', $safeDeclarations);
    }

    /**
     * Public so callers validating standalone URL fields (e.g. profile
     * "socials" links, which never pass through sanitize()'s DOM walk) can
     * apply the same javascript:/data: rejection instead of duplicating it.
     */
    public static function isSafeUrl(string $url): bool
    {
        $url = trim($url);

        if ($url === '' || str_starts_with($url, '/') || str_starts_with($url, '#')) {
            return true;
        }

        $scheme = parse_url($url, PHP_URL_SCHEME);

        return $scheme !== null && in_array(strtolower($scheme), self::ALLOWED_URL_SCHEMES, true);
    }
}
