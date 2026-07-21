<?php

use App\Models\News;
use App\Models\NewsAuthor;
use App\Models\NewsImage;
use App\Models\NewsPublisher;
use App\Models\User;
use App\Services\PublisherRoleService;
use App\Support\PermissionTeam;
use App\Support\PublisherPermissions;
use Database\Seeders\RoleSeeder;

beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function newsAdmin(array $permissions): User
{
    $user = User::factory()->create();
    PermissionTeam::global();
    $user->givePermissionTo($permissions);

    return $user;
}

function makePublisher(string $slug): NewsPublisher
{
    return NewsPublisher::create([
        'name' => $slug,
        'slug' => $slug,
        'socials' => [],
        'max_permissions' => PublisherPermissions::all(),
    ]);
}

function makeArticle(NewsPublisher $publisher, array $overrides = []): News
{
    return News::create(array_merge([
        'publisher_id' => $publisher->id,
        'lang' => 'en',
        'title' => 'Article for '.$publisher->slug,
        'slug' => 'article-'.$publisher->id.'-'.uniqid(),
        'excerpt' => 'Excerpt',
        'content' => '<p>Content</p>',
        'status' => 'draft',
        'is_featured' => false,
        'show_on_home' => false,
    ], $overrides));
}

test('a javascript: url in a news author\'s socials is rejected', function () {
    $user = newsAdmin(['news.authors.edit']);
    $author = NewsAuthor::create(['name' => 'Some Author', 'slug' => 'some-author']);

    $this->actingAs($user)
        ->put(route('admin.news.authors.update', $author), [
            'name' => 'Some Author',
            'socials' => ['website' => 'javascript:alert(document.cookie)'],
        ])
        ->assertSessionHasErrors('socials.website');

    expect($author->refresh()->socials)->toBe([]);
});

test('a plain https url in a news author\'s socials is accepted', function () {
    $user = newsAdmin(['news.authors.edit']);
    $author = NewsAuthor::create(['name' => 'Some Author', 'slug' => 'some-author']);

    $this->actingAs($user)
        ->put(route('admin.news.authors.update', $author), [
            'name' => 'Some Author',
            'socials' => ['website' => 'https://example.com/author'],
        ])
        ->assertSessionDoesntHaveErrors('socials.website');

    expect($author->refresh()->socials)->toBe(['website' => 'https://example.com/author']);
});

test('re-linking media already attached to another publisher\'s article requires managing the original article too', function () {
    $publisherA = makePublisher('publisher-a');
    $publisherB = makePublisher('publisher-b');

    $articleA = makeArticle($publisherA);
    $articleB = makeArticle($publisherB);

    $image = NewsImage::create(['news_id' => $articleA->id]);

    // Site-wide news.media.upload is intentionally NOT granted — only
    // scoped publisher membership, so this exercises the publisher-scoped
    // branch of ensureCanManageArticle().
    $user = User::factory()->create();
    app(PublisherRoleService::class)->assign(
        $user,
        $publisherB,
        PublisherRoleService::ROLE_OWNER
    );
    PermissionTeam::global();

    $this->actingAs($user)
        ->put(route('admin.news.media.link', $image), [
            'news_id' => $articleB->id,
        ])
        ->assertForbidden();

    expect($image->refresh()->news_id)->toBe($articleA->id);
});

test('a publisher member can still link their own unattached upload to their own article', function () {
    $publisher = makePublisher('publisher-c');
    $article = makeArticle($publisher);
    $image = NewsImage::create(['news_id' => null]);

    $user = User::factory()->create();
    app(PublisherRoleService::class)->assign(
        $user,
        $publisher,
        PublisherRoleService::ROLE_OWNER
    );
    PermissionTeam::global();

    $this->actingAs($user)
        ->put(route('admin.news.media.link', $image), [
            'news_id' => $article->id,
        ])
        ->assertRedirect();

    expect($image->refresh()->news_id)->toBe($article->id);
});
