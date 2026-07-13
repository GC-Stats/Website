<?php

use App\Models\GameMap;
use App\Models\GameMapRound;
use App\Models\GameMapRoundPlayerStat;
use App\Models\GamePlayerStat;
use App\Models\Matchs;
use App\Models\Player;
use App\Models\Team;
use App\Models\Tournament;

test('homepage returns 200', function () {
    $this->get('/')->assertOk();
});

test('homepage html has lang attribute', function () {
    $this->get('/')->assertSee('<html lang=', false);
});

test('homepage has skip-to-content link targeting main', function () {
    $response = $this->get('/');
    $response->assertSee('href="#main-content"', false);
    $response->assertSee('id="main-content"', false);
});

test('homepage main navigation has aria-label', function () {
    $this->get('/')->assertSee('aria-label=', false);
});

test('homepage footer has contentinfo role', function () {
    $this->get('/')->assertSee('role="contentinfo"', false);
});

test('homepage language switcher button has aria attributes', function () {
    $response = $this->get('/');
    $response->assertSee('aria-haspopup="true"', false);
    $response->assertSee('aria-label=', false);
});

// ─── Accessibility: old "homepage is accessible" kept as alias ────────────────

test('homepage is accessible', function () {
    $response = $this->get('/');
    $response->assertOk();
});

// ─── Player pages ────────────────────────────────────────────────────────────

test('player page is accessible', function () {
    Storage::fake('s3');

    $player = Player::factory()->create();
    $response = $this->get(route('players.show', $player->id));
    $response->assertOk();
});

test('player page has nav with aria-label', function () {
    Storage::fake('s3');

    $player = Player::factory()->create();
    $this->get(route('players.show', $player->id))
        ->assertSee('nav', false)
        ->assertSee('aria-label=', false);
});

test('player page active nav tab has aria-current', function () {
    Storage::fake('s3');

    $player = Player::factory()->create();
    $this->get(route('players.show', $player->id))
        ->assertSee('aria-current="page"', false);
});

test('player history page is accessible', function () {
    Storage::fake('s3');

    $player = Player::factory()->create();
    $this->get(route('players.history', [$player->id, str($player->handle)->slug()]))->assertOk();
});

test('player matches page is accessible', function () {
    Storage::fake('s3');

    $player = Player::factory()->create();
    $this->get(route('players.matches', [$player->id, str($player->handle)->slug()]))->assertOk();
});

test('player stats page is accessible', function () {
    Storage::fake('s3');

    $player = Player::factory()->create();
    $this->get(route('players.stats', [$player->id, str($player->handle)->slug()]))->assertOk();
});

test('player stats page has labelled date inputs', function () {
    Storage::fake('s3');

    $player = Player::factory()->create();
    $response = $this->get(route('players.stats', [$player->id, str($player->handle)->slug()]));
    $response->assertSee('for="start_date"', false);
    $response->assertSee('for="end_date"', false);
});

// ─── Team pages ──────────────────────────────────────────────────────────────

test('team page is accessible', function () {
    Storage::fake('s3');

    $team = Team::factory()->create();
    $this->get(route('teams.show', $team->id))->assertOk();
});

test('team logo has alt text with team name', function () {
    Storage::fake('s3');

    $team = Team::factory()->create(['name' => 'UniqueTeamName']);
    $this->get(route('teams.show', $team->id))
        ->assertSee('alt="UniqueTeamName"', false);
});

test('team page active nav tab has aria-current', function () {
    Storage::fake('s3');

    $team = Team::factory()->create();
    $this->get(route('teams.show', $team->id))
        ->assertSee('aria-current="page"', false);
});

test('team history page is accessible', function () {
    Storage::fake('s3');

    $team = Team::factory()->create();
    $this->get(route('teams.history', [$team->id, str($team->name)->slug()]))->assertOk();
});

test('team matches page is accessible', function () {
    Storage::fake('s3');

    $team = Team::factory()->create();
    $this->get(route('teams.matches', [$team->id, str($team->name)->slug()]))->assertOk();
});

// ─── Match page ───────────────────────────────────────────────────────────────

test('match page has team logo alt text', function () {
    Storage::fake('s3');

    $teamA = Team::factory()->create(['name' => 'AlphaTeam']);
    $teamB = Team::factory()->create(['name' => 'BetaTeam']);
    $match = Matchs::factory()->create([
        'team_a_id' => $teamA->id,
        'team_b_id' => $teamB->id,
    ]);
    GameMap::factory()->create(['match_id' => $match->id]);

    $response = $this->get(route('match.show', $match->id));
    $response->assertSee('alt="AlphaTeam"', false);
    $response->assertSee('alt="BetaTeam"', false);
});

test('match page is accessible', function () {
    Storage::fake('s3');

    $teamA = Team::factory()->create();
    $teamB = Team::factory()->create();
    $match = Matchs::factory()->create([
        'team_a_id' => $teamA->id,
        'team_b_id' => $teamB->id,
    ]);

    $map = GameMap::factory()->create(['match_id' => $match->id]);

    $playersA = Player::factory()->count(5)->create();
    $playersB = Player::factory()->count(5)->create();

    $teamA->players()->attach($playersA, ['joined_at' => now()]);
    $teamB->players()->attach($playersB, ['joined_at' => now()]);

    foreach ($playersA as $p) {
        GamePlayerStat::factory()->create([
            'match_id' => $match->id,
            'game_map_id' => $map->id,
            'player_id' => $p->id,
            'team_id' => $teamA->id,
        ]);
    }
    foreach ($playersB as $p) {
        GamePlayerStat::factory()->create([
            'match_id' => $match->id,
            'game_map_id' => $map->id,
            'player_id' => $p->id,
            'team_id' => $teamB->id,
        ]);
    }

    $round = GameMapRound::factory()->create([
        'game_map_id' => $map->id,
        'winning_team' => $teamA->id,
    ]);
    foreach ($playersA->concat($playersB) as $p) {
        GameMapRoundPlayerStat::factory()->create([
            'game_map_round_id' => $round->id,
            'player_id' => $p->id,
        ]);
    }

    $response = $this->get(route('match.show', $match->id));
    $response->assertOk();
});

// ─── Tournament pages ─────────────────────────────────────────────────────────

test('tournaments index is accessible', function () {
    $this->get(route('tournaments.index'))->assertOk();
});

test('tournament page is accessible', function () {
    Storage::fake('s3');

    $tournament = Tournament::factory()->create();
    $this->get(route('tournaments.show', $tournament->id))->assertOk();
});

test('tournament logo has alt text with name', function () {
    Storage::fake('s3');

    $tournament = Tournament::factory()->create(['name' => 'UniqueTournamentX']);
    $this->get(route('tournaments.show', $tournament->id))
        ->assertSee('alt="UniqueTournamentX"', false);
});

test('tournament page active nav tab has aria-current', function () {
    Storage::fake('s3');

    $tournament = Tournament::factory()->create();
    $this->get(route('tournaments.show', $tournament->id))
        ->assertSee('aria-current="page"', false);
});

test('tournament matches page is accessible', function () {
    Storage::fake('s3');

    $tournament = Tournament::factory()->create();
    $this->get(route('tournaments.matches', [$tournament->id, str($tournament->name)->slug()]))->assertOk();
});

test('tournament stats page is accessible', function () {
    Storage::fake('s3');

    $tournament = Tournament::factory()->create();
    $this->get(route('tournaments.stats', [$tournament->id, str($tournament->name)->slug()]))->assertOk();
});

// ─── Language switching ───────────────────────────────────────────────────────

test('language can be switched to french', function () {
    $response = $this->get(route('lang.switch', 'fr'));
    $response->assertRedirect();
    $this->assertEquals('fr', session('locale'));
});

test('language can be switched back to english', function () {
    session()->put('locale', 'fr');
    $this->get(route('lang.switch', 'en'))->assertRedirect();
    $this->assertEquals('en', session('locale'));
});

test('unsupported locale is rejected silently', function () {
    $this->get(route('lang.switch', 'xx'))->assertRedirect();
    $this->assertNotEquals('xx', session('locale'));
});

// ─── Static / legal pages ─────────────────────────────────────────────────────

test('legal pages are accessible', function () {
    $this->get(route('legal'))->assertStatus(200);
    $this->get(route('privacy'))->assertStatus(200);
    $this->get(route('takedown'))->assertStatus(200);
    $this->get(route('help.edit_page'))->assertStatus(200);
    $this->get(route('help.add_tournament'))->assertStatus(200);
    $this->get(route('developers'))->assertStatus(200);
    $this->get(route('terms'))->assertStatus(200);
    $this->get(route('data'))->assertStatus(200);
});

test('health endpoint returns ok status', function () {
    $this->getJson('/health')
        ->assertOk()
        ->assertJson(['status' => 'ok']);
});

test('non-existent route returns 404', function () {
    $this->get('/this-page-does-not-exist-xyz')->assertStatus(404);
});

// ─── Pagination accessibility ─────────────────────────────────────────────────

test('pagination has aria-label on nav element', function () {
    Storage::fake('s3');

    Team::factory()->count(30)->create();
    $team = Team::factory()->create();

    Matchs::factory()->count(25)->create([
        'team_a_id' => $team->id,
        'team_b_id' => Team::factory()->create()->id,
    ]);

    $response = $this->get(route('teams.matches', [$team->id, str($team->name)->slug()]));
    $response->assertOk();

    if (str_contains($response->getContent(), '<nav role="navigation"')) {
        $response->assertSee('aria-label=', false);
    }
});
