<?php

use App\Models\News;
use App\Models\NewsAuthor;
use App\Models\NewsComment;
use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationRoleService;
use App\Support\OrganizationPermissions;
use App\Support\PermissionTeam;
use Database\Seeders\RoleSeeder;

/**
 * Covers the news integration into the organization dashboard: same
 * Admin\NewsController serving both admin.news.* and
 * organization-dashboard.news.*, scoped strictly to the {organization}
 * bound in the dashboard URL — see that controller's docblock.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function makeNewsOrganization(string $slug): Organization
{
    return Organization::create([
        'name' => $slug,
        'slug' => $slug,
        'types' => ['media'],
        'socials' => [],
        'max_permissions' => OrganizationPermissions::all(),
    ]);
}

function makeOrganizationEditor(Organization $organization): User
{
    $user = User::factory()->create();
    app(OrganizationRoleService::class)->assign($user, $organization, OrganizationRoleService::ROLE_OWNER);
    NewsAuthor::create(['user_id' => $user->id, 'name' => $user->name, 'slug' => 'author-'.$user->id]);
    PermissionTeam::global();

    return $user;
}

test('an organization member can view their own dashboard news list', function () {
    $organization = makeNewsOrganization('dash-news-a');
    $user = makeOrganizationEditor($organization);

    $this->actingAs($user)
        ->get(route('organization-dashboard.news.index', $organization))
        ->assertOk();
});

test('an organization member cannot view another organization dashboard news list', function () {
    $organizationA = makeNewsOrganization('dash-news-b');
    $organizationB = makeNewsOrganization('dash-news-c');
    $user = makeOrganizationEditor($organizationA);

    $this->actingAs($user)
        ->get(route('organization-dashboard.news.index', $organizationB))
        ->assertForbidden();
});

test('an organization member can create an article from the dashboard and it is attributed to that organization', function () {
    $organization = makeNewsOrganization('dash-news-d');
    $otherOrganization = makeNewsOrganization('dash-news-d-other');
    $user = makeOrganizationEditor($organization);

    $response = $this->actingAs($user)
        ->post(route('organization-dashboard.news.store', $organization), [
            'organization_id' => $otherOrganization->id, // must be ignored/overridden by the controller
            'lang' => 'en',
            'title' => 'Dashboard article',
            'content' => '<p>Body</p>',
        ]);

    $article = News::where('title', 'Dashboard article')->firstOrFail();

    $response->assertRedirect(route('organization-dashboard.news.edit', [$organization, $article]));
    expect($article->organization_id)->toBe($organization->id);
});

test('an organization member cannot edit an article belonging to another organization via the dashboard url', function () {
    $organizationA = makeNewsOrganization('dash-news-e');
    $organizationB = makeNewsOrganization('dash-news-f');
    $userA = makeOrganizationEditor($organizationA);
    makeOrganizationEditor($organizationB);

    $articleB = News::create([
        'organization_id' => $organizationB->id,
        'lang' => 'en',
        'title' => 'Org B article',
        'slug' => 'org-b-article',
        'content' => '<p>Body</p>',
        'status' => 'draft',
        'is_featured' => false,
        'show_on_home' => false,
    ]);

    $this->actingAs($userA)
        ->get(route('organization-dashboard.news.edit', [$organizationA, $articleB]))
        ->assertNotFound();
});

test('the admin flat news list still works and is unaffected by the dashboard scoping', function () {
    $user = User::factory()->create();
    PermissionTeam::global();
    $user->givePermissionTo(['news.view']);

    $this->actingAs($user)
        ->get(route('admin.news.index'))
        ->assertOk();
});

test('an organization member can view, edit, publish and comment on their own article from the dashboard', function () {
    $organization = makeNewsOrganization('dash-news-g');
    $user = makeOrganizationEditor($organization);

    $article = News::create([
        'organization_id' => $organization->id,
        'lang' => 'en',
        'title' => 'Own article',
        'slug' => 'own-article',
        'content' => '<p>Body</p>',
        'status' => 'draft',
        'is_featured' => false,
        'show_on_home' => false,
    ]);

    $this->actingAs($user)
        ->get(route('organization-dashboard.news.edit', [$organization, $article]))
        ->assertOk();

    $this->actingAs($user)
        ->put(route('organization-dashboard.news.update', [$organization, $article]), [
            'lang' => 'en',
            'title' => 'Own article, updated',
            'content' => '<p>New body</p>',
        ])
        ->assertRedirect();

    expect($article->refresh()->title)->toBe('Own article, updated');

    $this->actingAs($user)
        ->post(route('organization-dashboard.news.publish', [$organization, $article]))
        ->assertRedirect();

    expect($article->refresh()->status)->toBe('published');

    $this->actingAs($user)
        ->post(route('organization-dashboard.news.comments.store', [$organization, $article]), [
            'body' => 'Looks good',
        ])
        ->assertRedirect();

    $comment = $article->comments()->firstOrFail();
    expect($comment->body)->toBe('Looks good');

    $this->actingAs($user)
        ->put(route('organization-dashboard.news.comments.resolve', [$organization, $article, $comment]))
        ->assertRedirect();

    expect($comment->refresh()->resolved_at)->not->toBeNull();

    $this->actingAs($user)
        ->delete(route('organization-dashboard.news.comments.destroy', [$organization, $article, $comment]))
        ->assertRedirect();

    expect(NewsComment::find($comment->id))->toBeNull();
});

test('the admin edit page for a single article still works and is unaffected by the reordered parameters', function () {
    $organization = makeNewsOrganization('dash-news-h');

    $article = News::create([
        'organization_id' => $organization->id,
        'lang' => 'en',
        'title' => 'Admin-visible article',
        'slug' => 'admin-visible-article',
        'content' => '<p>Body</p>',
        'status' => 'draft',
        'is_featured' => false,
        'show_on_home' => false,
    ]);

    $user = User::factory()->create();
    PermissionTeam::global();
    $user->givePermissionTo(['news.view', 'news.edit']);

    $this->actingAs($user)
        ->get(route('admin.news.edit', $article))
        ->assertOk();
});
