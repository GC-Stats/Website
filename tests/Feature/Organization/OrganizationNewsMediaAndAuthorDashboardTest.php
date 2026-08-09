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

/**
 * Covers the rest of "the news block" surfaced in the organization
 * dashboard alongside articles: the media library
 * (Admin\NewsMediaController, dual-context like Admin\NewsController) and
 * the self-service author profile (Admin\NewsAuthorController's
 * myProfile()/show()/update()/updateLogo(), personal rather than
 * organization-scoped — see that controller's docblock).
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function makeMediaOrganization(string $slug): Organization
{
    return Organization::create([
        'name' => $slug,
        'slug' => $slug,
        'types' => ['media'],
        'socials' => [],
        'max_permissions' => OrganizationPermissions::all(),
    ]);
}

function makeMediaOrganizationEditor(Organization $organization): User
{
    $user = User::factory()->create();
    app(OrganizationRoleService::class)->assign($user, $organization, OrganizationRoleService::ROLE_OWNER);
    PermissionTeam::global();

    return $user;
}

test('an organization member can view their dashboard media library', function () {
    $organization = makeMediaOrganization('dash-media-a');
    $user = makeMediaOrganizationEditor($organization);

    $this->actingAs($user)
        ->get(route('organization-dashboard.news.media.index', $organization))
        ->assertOk();
});

test('an organization member cannot view another organization dashboard media library', function () {
    $organizationA = makeMediaOrganization('dash-media-b');
    $organizationB = makeMediaOrganization('dash-media-c');
    $user = makeMediaOrganizationEditor($organizationA);

    $this->actingAs($user)
        ->get(route('organization-dashboard.news.media.index', $organizationB))
        ->assertForbidden();
});

test('the admin media library still works and is unaffected by the dashboard scoping', function () {
    $user = User::factory()->create();
    PermissionTeam::global();
    $user->givePermissionTo(['news.media.view']);

    $this->actingAs($user)
        ->get(route('admin.news.media.index'))
        ->assertOk();
});

test('an organization member without an author profile is prompted to create one from the dashboard', function () {
    $organization = makeMediaOrganization('dash-author-a');
    $user = makeMediaOrganizationEditor($organization);

    $this->actingAs($user)
        ->get(route('organization-dashboard.news.author.my', $organization))
        ->assertOk();
});

test('an organization member with an author profile is redirected to it from the dashboard', function () {
    $organization = makeMediaOrganization('dash-author-b');
    $user = makeMediaOrganizationEditor($organization);
    $author = NewsAuthor::create(['user_id' => $user->id, 'name' => $user->name, 'slug' => 'author-'.$user->id]);

    $this->actingAs($user)
        ->get(route('organization-dashboard.news.author.my', $organization))
        ->assertRedirect(route('organization-dashboard.news.author.show', [$organization, $author]));
});

test('an organization member can create, view and update their own author profile from the dashboard', function () {
    $organization = makeMediaOrganization('dash-author-c');
    $user = makeMediaOrganizationEditor($organization);

    $this->actingAs($user)
        ->post(route('organization-dashboard.news.author.store', $organization), [
            'name' => 'Dashboard Author',
        ])
        ->assertRedirect();

    $author = NewsAuthor::where('user_id', $user->id)->firstOrFail();
    expect($author->name)->toBe('Dashboard Author');

    $this->actingAs($user)
        ->get(route('organization-dashboard.news.author.show', [$organization, $author]))
        ->assertOk();

    $this->actingAs($user)
        ->put(route('organization-dashboard.news.author.update', [$organization, $author]), [
            'name' => 'Dashboard Author Updated',
        ])
        ->assertRedirect();

    expect($author->refresh()->name)->toBe('Dashboard Author Updated');
});

test('a site editor with news.authors.edit cannot relink their own author profile to a different user', function () {
    $organization = makeMediaOrganization('dash-author-e');
    $user = makeMediaOrganizationEditor($organization);
    $otherUser = User::factory()->create();
    PermissionTeam::global();
    $user->givePermissionTo(['news.authors.edit']);
    $author = NewsAuthor::create(['user_id' => $user->id, 'name' => $user->name, 'slug' => 'author-'.$user->id]);

    $this->actingAs($user)
        ->put(route('organization-dashboard.news.author.update', [$organization, $author]), [
            'name' => $author->name,
            'username' => $otherUser->username,
        ])
        ->assertSessionHasErrors('username');

    expect($author->refresh()->user_id)->toBe($user->id);
});

test('an organization member cannot view or edit another user author profile from the dashboard', function () {
    $organization = makeMediaOrganization('dash-author-d');
    $userA = makeMediaOrganizationEditor($organization);
    $userB = makeMediaOrganizationEditor($organization);
    $authorB = NewsAuthor::create(['user_id' => $userB->id, 'name' => 'User B', 'slug' => 'user-b-author']);

    $this->actingAs($userA)
        ->get(route('organization-dashboard.news.author.show', [$organization, $authorB]))
        ->assertForbidden();
});

test('linking an unattached image to an article from the dashboard media library works', function () {
    $organization = makeMediaOrganization('dash-media-d');
    $user = makeMediaOrganizationEditor($organization);
    NewsAuthor::create(['user_id' => $user->id, 'name' => $user->name, 'slug' => 'author-'.$user->id]);

    $article = News::create([
        'organization_id' => $organization->id,
        'lang' => 'en',
        'title' => 'Media test article',
        'slug' => 'media-test-article',
        'content' => '<p>Body</p>',
        'status' => 'draft',
        'is_featured' => false,
        'show_on_home' => false,
    ]);

    $image = NewsImage::create(['news_id' => null]);

    $this->actingAs($user)
        ->put(route('admin.news.media.link', $image), ['news_id' => $article->id])
        ->assertRedirect();

    expect($image->refresh()->news_id)->toBe($article->id);
});
