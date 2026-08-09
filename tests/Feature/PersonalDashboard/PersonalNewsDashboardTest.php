<?php

use App\Models\News;
use App\Models\NewsAuthor;
use App\Models\Organization;
use App\Models\User;
use App\Services\OrganizationRoleService;
use App\Support\OrganizationPermissions;
use App\Support\PermissionTeam;
use Database\Seeders\RoleSeeder;

/**
 * Covers dashboard/me (routes/personal-dashboard.php): the org-less
 * equivalent of the organization dashboard, reusing Admin\NewsController/
 * NewsAuthorController/NewsMediaController for a lone author holding only
 * the site-wide 'news.author' permission — see that route file's docblock.
 */
beforeEach(function () {
    $this->seed(RoleSeeder::class);
});

function makePersonalAuthor(): User
{
    $user = User::factory()->create();
    PermissionTeam::global();
    $user->givePermissionTo(['news.author']);
    NewsAuthor::create(['user_id' => $user->id, 'name' => $user->name, 'slug' => 'author-'.$user->id]);

    return $user;
}

test('a user without news.author cannot access the personal dashboard', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('personal-dashboard.index'))
        ->assertForbidden();
});

test('a lone author can view their personal dashboard overview', function () {
    $user = makePersonalAuthor();

    $this->actingAs($user)
        ->get(route('personal-dashboard.index'))
        ->assertOk();
});

test('a lone author can view their personal news list without belonging to an organization', function () {
    $user = makePersonalAuthor();

    $this->actingAs($user)
        ->get(route('personal-dashboard.news.index'))
        ->assertOk();
});

test('a lone author can create an org-less article from the personal dashboard', function () {
    $user = makePersonalAuthor();

    $response = $this->actingAs($user)
        ->post(route('personal-dashboard.news.store'), [
            'lang' => 'en',
            'title' => 'Personal article',
            'content' => '<p>Body</p>',
        ]);

    $article = News::where('title', 'Personal article')->firstOrFail();

    $response->assertRedirect(route('personal-dashboard.news.edit', $article));
    expect($article->organization_id)->toBeNull();
    expect($article->author_id)->toBe($user->newsAuthor->id);
});

test('a lone author can view, edit and publish their own article from the personal dashboard', function () {
    $user = makePersonalAuthor();

    $article = News::create([
        'author_id' => $user->newsAuthor->id,
        'lang' => 'en',
        'title' => 'Own personal article',
        'slug' => 'own-personal-article',
        'content' => '<p>Body</p>',
        'status' => 'draft',
        'is_featured' => false,
        'show_on_home' => false,
    ]);

    $this->actingAs($user)
        ->get(route('personal-dashboard.news.edit', $article))
        ->assertOk();

    $this->actingAs($user)
        ->put(route('personal-dashboard.news.update', $article), [
            'lang' => 'en',
            'title' => 'Own personal article, updated',
            'content' => '<p>New body</p>',
        ])
        ->assertRedirect();

    expect($article->refresh()->title)->toBe('Own personal article, updated');

    $this->actingAs($user)
        ->post(route('personal-dashboard.news.publish', $article))
        ->assertRedirect();

    expect($article->refresh()->status)->toBe('published');
});

test('a lone author cannot see another authors article in their personal news list', function () {
    $user = makePersonalAuthor();
    $otherUser = makePersonalAuthor();

    $otherArticle = News::create([
        'author_id' => $otherUser->newsAuthor->id,
        'lang' => 'en',
        'title' => 'Someone elses article',
        'slug' => 'someone-elses-article',
        'content' => '<p>Body</p>',
        'status' => 'draft',
        'is_featured' => false,
        'show_on_home' => false,
    ]);

    $this->actingAs($user)
        ->get(route('personal-dashboard.news.index'))
        ->assertOk()
        ->assertDontSee('Someone elses article');

    $this->actingAs($user)
        ->get(route('personal-dashboard.news.edit', $otherArticle))
        ->assertForbidden();
});

test('a lone author can view their personal media library', function () {
    $user = makePersonalAuthor();

    $this->actingAs($user)
        ->get(route('personal-dashboard.news.media.index'))
        ->assertOk();
});

test('the organization dashboard still works unaffected by the shared layout accepting a null organization', function () {
    $organization = Organization::create([
        'name' => 'personal-dashboard-regression-org',
        'slug' => 'personal-dashboard-regression-org',
        'types' => ['media'],
        'socials' => [],
        'max_permissions' => OrganizationPermissions::all(),
    ]);

    $user = User::factory()->create();
    app(OrganizationRoleService::class)->assign($user, $organization, OrganizationRoleService::ROLE_OWNER);
    PermissionTeam::global();

    $this->actingAs($user)
        ->get(route('organization-dashboard.index', $organization))
        ->assertOk()
        ->assertSee($organization->name);
});
