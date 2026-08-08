<?php

use App\Models\News;
use App\Models\NewsAuthor;
use App\Models\NewsImage;
use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationRoleService;
use App\Support\OrganizationPermissions;
use App\Support\PermissionTeam;
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

function makeOrganization(string $slug): Organization
{
    return Organization::create([
        'name' => $slug,
        'slug' => $slug,
        'types' => ['media'],
        'socials' => [],
        'max_permissions' => OrganizationPermissions::all(),
    ]);
}

function makeArticle(Organization $organization, array $overrides = []): News
{
    return News::create(array_merge([
        'organization_id' => $organization->id,
        'lang' => 'en',
        'title' => 'Article for '.$organization->slug,
        'slug' => 'article-'.$organization->id.'-'.uniqid(),
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

test('re-linking media already attached to another organization\'s article requires managing the original article too', function () {
    $organizationA = makeOrganization('organization-a');
    $organizationB = makeOrganization('organization-b');

    $articleA = makeArticle($organizationA);
    $articleB = makeArticle($organizationB);

    $image = NewsImage::create(['news_id' => $articleA->id]);

    // Site-wide news.media.upload is intentionally NOT granted — only
    // scoped organization membership, so this exercises the
    // organization-scoped branch of ensureCanManageArticle().
    $user = User::factory()->create();
    app(OrganizationRoleService::class)->assign(
        $user,
        $organizationB,
        OrganizationRoleService::ROLE_OWNER
    );
    PermissionTeam::global();

    $this->actingAs($user)
        ->put(route('admin.news.media.link', $image), [
            'news_id' => $articleB->id,
        ])
        ->assertForbidden();

    expect($image->refresh()->news_id)->toBe($articleA->id);
});

test('an organization member can still link their own unattached upload to their own article', function () {
    $organization = makeOrganization('organization-c');
    $article = makeArticle($organization);
    $image = NewsImage::create(['news_id' => null]);

    $user = User::factory()->create();
    app(OrganizationRoleService::class)->assign(
        $user,
        $organization,
        OrganizationRoleService::ROLE_OWNER
    );
    PermissionTeam::global();

    $this->actingAs($user)
        ->put(route('admin.news.media.link', $image), [
            'news_id' => $article->id,
        ])
        ->assertRedirect();

    expect($image->refresh()->news_id)->toBe($article->id);
});
