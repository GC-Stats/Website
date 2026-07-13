<?php

use App\Models\GameMap;
use App\Models\GameMapRound;
use App\Models\GameMapRoundPlayerStat;
use App\Models\GamePlayerStat;
use App\Models\Matchs;
use App\Models\Player;
use App\Models\Team;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;

uses(DatabaseTruncation::class);

/**
 * Match page accessibility tests.
 *
 * Covers team logo alt text, score landmark, map tab semantics,
 * stats table structure and round history labels.
 */

// ─── Helpers ─────────────────────────────────────────────────────────────────

function createFullMatch(string $teamAName, string $teamBName): array
{
    $teamA = Team::factory()->create(['name' => $teamAName]);
    $teamB = Team::factory()->create(['name' => $teamBName]);

    $match = Matchs::factory()->create([
        'team_a_id' => $teamA->id,
        'team_b_id' => $teamB->id,
        'status' => 'finished',
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

    return compact('match', 'teamA', 'teamB', 'map');
}

// ─── Team logos ───────────────────────────────────────────────────────────────

test('match page team logos have alt text', function () {
    Storage::fake('s3');
    ['match' => $match, 'teamA' => $teamA, 'teamB' => $teamB] = createFullMatch('MatchTeamA', 'MatchTeamB');

    $this->browse(function (Browser $browser) use ($match, $teamA, $teamB) {
        $browser->visit(route('match.show', $match->id));

        $altA = $browser->script("
            return !!document.querySelector('img[alt=\"{$teamA->name}\"]');
        ")[0];
        $altB = $browser->script("
            return !!document.querySelector('img[alt=\"{$teamB->name}\"]');
        ")[0];

        expect($altA)->toBeTrue('Team A logo must have alt with team name');
        expect($altB)->toBeTrue('Team B logo must have alt with team name');
    });
});

// ─── Score region ─────────────────────────────────────────────────────────────

test('match page has screen-reader score text', function () {
    Storage::fake('s3');
    ['match' => $match] = createFullMatch('SRTeamA', 'SRTeamB');

    $this->browse(function (Browser $browser) use ($match) {
        $browser->visit(route('match.show', $match->id));

        // sr-only text with score should be present
        $srText = $browser->script("
            return Array.from(document.querySelectorAll('.sr-only'))
                .map(el => el.textContent.trim())
                .filter(t => t.length > 0);
        ")[0];

        expect($srText)->not->toBeEmpty('Score must be communicated to screen readers via .sr-only');
    });
});

// ─── Map tab selector ─────────────────────────────────────────────────────────

test('map selector has tablist role', function () {
    Storage::fake('s3');
    ['match' => $match] = createFullMatch('TabTeamA', 'TabTeamB');

    $this->browse(function (Browser $browser) use ($match) {
        $browser->visit(route('match.show', $match->id));

        $tablistCount = $browser->script("
            return document.querySelectorAll('[role=\"tablist\"]').length;
        ")[0];

        expect($tablistCount)->toBeGreaterThan(0, 'Map selector must have role="tablist"');
    });
});

test('map selector buttons have tab role', function () {
    Storage::fake('s3');
    ['match' => $match] = createFullMatch('TabBtnTeamA', 'TabBtnTeamB');

    $this->browse(function (Browser $browser) use ($match) {
        $browser->visit(route('match.show', $match->id));

        $tabCount = $browser->script("
            return document.querySelectorAll('[role=\"tab\"]').length;
        ")[0];

        expect($tabCount)->toBeGreaterThan(0, 'Map selector buttons must have role="tab"');
    });
});

test('map tabs have aria-selected attribute', function () {
    Storage::fake('s3');
    ['match' => $match] = createFullMatch('AriaSTeamA', 'AriaSTeamB');

    $this->browse(function (Browser $browser) use ($match) {
        $browser->visit(route('match.show', $match->id));

        $tabsWithoutSelected = $browser->script("
            return Array.from(document.querySelectorAll('[role=\"tab\"]'))
                .filter(tab => !tab.hasAttribute('aria-selected'))
                .length;
        ")[0];

        expect($tabsWithoutSelected)->toBe(0, 'All role=tab buttons must have aria-selected');
    });
});

test('map tabs have aria-controls attribute', function () {
    Storage::fake('s3');
    ['match' => $match] = createFullMatch('CtrlTeamA', 'CtrlTeamB');

    $this->browse(function (Browser $browser) use ($match) {
        $browser->visit(route('match.show', $match->id));

        $tabsWithoutControls = $browser->script("
            return Array.from(document.querySelectorAll('[role=\"tab\"]'))
                .filter(tab => !tab.hasAttribute('aria-controls'))
                .length;
        ")[0];

        expect($tabsWithoutControls)->toBe(0, 'All role=tab buttons must have aria-controls');
    });
});

// ─── Stats tables ─────────────────────────────────────────────────────────────

test('stats table headers have scope attribute', function () {
    Storage::fake('s3');
    ['match' => $match] = createFullMatch('ScopeTeamA', 'ScopeTeamB');

    $this->browse(function (Browser $browser) use ($match) {
        $browser->visit(route('match.show', $match->id));

        $headersWithoutScope = $browser->script("
            return Array.from(document.querySelectorAll('table thead th'))
                .filter(th => !th.getAttribute('scope'))
                .length;
        ")[0];

        expect($headersWithoutScope)->toBe(0, 'All <th> must have scope attribute');
    });
});

test('stats tables have caption for screen readers', function () {
    Storage::fake('s3');
    ['match' => $match] = createFullMatch('CaptionTeamA', 'CaptionTeamB');

    $this->browse(function (Browser $browser) use ($match) {
        $browser->visit(route('match.show', $match->id));

        $tablesWithCaption = $browser->script("
            return Array.from(document.querySelectorAll('table'))
                .filter(t => t.querySelector('caption'))
                .length;
        ")[0];

        expect($tablesWithCaption)->toBeGreaterThan(0, 'At least one table must have a <caption>');
    });
});

test('agent images in stats table have alt text', function () {
    Storage::fake('s3');
    ['match' => $match] = createFullMatch('AgentTeamA', 'AgentTeamB');

    $this->browse(function (Browser $browser) use ($match) {
        $browser->visit(route('match.show', $match->id));

        $agentImgsWithoutAlt = $browser->script("
            return Array.from(document.querySelectorAll('table img'))
                .filter(img => !img.getAttribute('alt') || img.getAttribute('alt') === '')
                .map(img => img.src);
        ")[0];

        expect($agentImgsWithoutAlt)->toBeEmpty('Agent images in tables must have alt text');
    });
});

// ─── Round history ────────────────────────────────────────────────────────────

test('round history section has aria-label', function () {
    Storage::fake('s3');
    ['match' => $match] = createFullMatch('RndTeamA', 'RndTeamB');

    $this->browse(function (Browser $browser) use ($match) {
        $browser->visit(route('match.show', $match->id));

        $roundSections = $browser->script("
            return Array.from(document.querySelectorAll('section[aria-label]'))
                .map(s => s.getAttribute('aria-label'));
        ")[0];

        expect($roundSections)->not->toBeEmpty('Round history section must have aria-label');
    });
});
