<?php

use App\Models\Team;

test('a team name containing a script-breakout payload cannot escape the JSON-LD block', function () {
    $payload = '</script><script>alert(document.cookie)</script>';

    $team = Team::factory()->create(['name' => $payload]);

    $response = $this->get(route('teams.show', [$team->id, str($team->name)->slug()]));

    $expectedEscaped = json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

    $response->assertOk();
    // The raw breakout sequence must never appear unescaped in the HTML.
    $response->assertDontSee('</script><script>alert', false);
    // json_encode(..., JSON_HEX_TAG) is what actually neutralizes the payload
    // in the JSON-LD block — assert the escaped form is present instead.
    $response->assertSee(trim($expectedEscaped, '"'), false);
});
