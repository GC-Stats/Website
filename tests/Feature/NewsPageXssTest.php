<?php

use App\Models\News;
use App\Models\NewsAuthor;
use App\Services\HtmlSanitizer;

test('a news article title containing a script-breakout payload cannot escape the JSON-LD block', function () {
    $payload = '</script><script>alert(document.cookie)</script>';

    $author = NewsAuthor::create(['name' => 'Test Author', 'slug' => 'test-author']);

    $article = News::create([
        'author_id' => $author->id,
        'lang' => 'en',
        'title' => $payload,
        'slug' => 'xss-test-article',
        'content' => '<p>hello</p>',
        'status' => 'published',
        'published_at' => now(),
    ]);

    $response = $this->get(route('news.show', $article->slug));

    $expectedEscaped = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

    $response->assertOk();
    // The raw breakout sequence must never appear unescaped in the HTML.
    $response->assertDontSee('</script><script>alert', false);
    // json_encode(..., JSON_HEX_TAG) is what actually neutralizes the payload
    // in the JSON-LD block — assert the escaped form is present instead.
    $response->assertSee(trim($expectedEscaped, '"'), false);
});

test('a news article content field is sanitized to strip script tags and event handlers before storage', function () {
    $author = NewsAuthor::create(['name' => 'Test Author 2', 'slug' => 'test-author-2']);

    $article = News::create([
        'author_id' => $author->id,
        'lang' => 'en',
        'title' => 'Safe title',
        'slug' => 'sanitized-content-article',
        'content' => app(HtmlSanitizer::class)->sanitize(
            '<p>hello</p><script>alert(document.cookie)</script><img src=x onerror=alert(1)>'
        ),
        'status' => 'published',
        'published_at' => now(),
    ]);

    $response = $this->get(route('news.show', $article->slug));

    $response->assertOk();
    $response->assertDontSee('alert(document.cookie)', false);
    $response->assertDontSee('onerror', false);
    $response->assertSee('<p>hello</p>', false);
});
