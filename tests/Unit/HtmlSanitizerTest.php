<?php

use App\Services\HtmlSanitizer;

beforeEach(function () {
    $this->sanitizer = new HtmlSanitizer();
});

it('strips script tags entirely, including their content as executable code', function () {
    $result = $this->sanitizer->sanitize('<p>hello</p><script>alert(document.cookie)</script>');

    expect($result)->not->toContain('<script')
        ->and($result)->toContain('<p>hello</p>');
});

it('strips event handler attributes from otherwise-allowed tags', function () {
    $result = $this->sanitizer->sanitize('<img src="https://example.com/x.png" onerror="alert(1)">');

    expect($result)->not->toContain('onerror')
        ->and($result)->toContain('src="https://example.com/x.png"');
});

it('strips javascript: URLs from href', function () {
    $result = $this->sanitizer->sanitize('<a href="javascript:alert(1)">click</a>');

    expect($result)->not->toContain('javascript:')
        ->and($result)->not->toContain('href');
});

it('strips data: and vbscript: URLs from src/href', function () {
    $result = $this->sanitizer->sanitize('<img src="data:text/html,<script>alert(1)</script>">');

    expect($result)->not->toContain('data:')
        ->and($result)->not->toContain('<script');
});

it('keeps http/https/relative/mailto links and images intact', function () {
    $result = $this->sanitizer->sanitize('<a href="https://gc-stats.gg/foo">link</a><img src="/storage/x.webp" alt="x">');

    expect($result)->toContain('href="https://gc-stats.gg/foo"')
        ->and($result)->toContain('src="/storage/x.webp"');
});

it('removes disallowed tags (div, svg, iframe, style) while preserving their text content', function () {
    $result = $this->sanitizer->sanitize('<div onclick="alert(1)">kept text</div><svg onload="alert(1)"></svg><iframe src="evil.com"></iframe>');

    expect($result)->not->toContain('<div')
        ->and($result)->not->toContain('<svg')
        ->and($result)->not->toContain('<iframe')
        ->and($result)->toContain('kept text');
});

it('forces rel=noopener noreferrer on links with a target attribute', function () {
    $result = $this->sanitizer->sanitize('<a href="https://example.com" target="_blank">x</a>');

    expect($result)->toContain('rel="noopener noreferrer"');
});

it('preserves ordinary formatting tags used by the prose styling', function () {
    $result = $this->sanitizer->sanitize('<h2>Title</h2><p>Some <strong>bold</strong> and <em>italic</em> text.</p><ul><li>one</li></ul>');

    expect($result)
        ->toContain('<h2>Title</h2>')
        ->toContain('<strong>bold</strong>')
        ->toContain('<li>one</li>');
});
