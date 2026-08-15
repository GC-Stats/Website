<?php

use App\Models\ForumThread;
use App\Models\User;
use Laravel\Dusk\Browser;

/**
 * Regression coverage for the composer's client-side Alpine behavior
 * (resources/views/livewire/forum-thread.blade.php) — untestable from a
 * Pest HTTP test, hence Dusk:
 *  - emote/embed/gif insertion landing at the caret position instead of
 *    always at the end (insertNode());
 *  - Enter left to the browser's native contenteditable handling (plain
 *    newline) except Ctrl/Cmd+Enter, which submits — both a stray custom
 *    Enter handler AND a broken x-data attribute (a literal `"` inside a
 *    JS comment terminating the double-quoted HTML attribute early —
 *    silently killing every Alpine binding on the composer) each shipped
 *    and broke this at different points, so this file pins the actual
 *    end-to-end behavior rather than just the insertNode() unit.
 *
 * Exercises insertGif()/insertEmbed() by dispatching the same window
 * CustomEvent the pickers dispatch (see barId-scoped `-selected-` events)
 * rather than driving the picker UI itself — the pickers' own search/list
 * behavior is already covered elsewhere; what's under test here is what
 * happens to the composer once that event lands.
 */
function threadUrlFor(ForumThread $thread): string
{
    return '/forum/threads/'.$thread->id;
}

test('inserting a gif lands at the caret position, not always at the end', function () {
    $user = User::factory()->create();
    $thread = ForumThread::create(['category' => ForumThread::CATEGORY_GENERAL, 'title' => 'Composer test', 'created_by' => $user->id]);
    $barId = 'forum-thread-'.$thread->id;

    $this->browse(function (Browser $browser) use ($user, $thread, $barId) {
        $browser->loginAs($user)
            ->visit(threadUrlFor($thread))
            ->waitFor('.forum-composer')
            ->click('.forum-composer')
            ->type('.forum-composer', 'AZ')
            // Caret is now after "AZ" — move it back between "A" and "Z".
            ->keys('.forum-composer', ['{left}'])
            ->script("window.dispatchEvent(new CustomEvent('gif-selected-{$barId}', { detail: { url: 'https://media3.giphy.com/media/test/giphy.gif' } }))");

        $browser->pause(300);

        // Not `[x-ref="hidden"]` — resources/views/components/styled-select.blade.php
        // reuses that same ref name for the nav's language switcher, which
        // sits earlier in the DOM and would win a plain attribute query.
        $body = $browser->script("return document.querySelector('.forum-composer').previousElementSibling.value")[0];

        expect($body)->toStartWith('A')
            ->and($body)->toEndWith('Z')
            ->and($body)->toContain('{{gif:https://media3.giphy.com/media/test/giphy.gif}}');
    });
});

test('pressing Enter right after an inserted embed chip adds a new line instead of doing nothing', function () {
    $user = User::factory()->create();
    $thread = ForumThread::create(['category' => ForumThread::CATEGORY_GENERAL, 'title' => 'Composer test', 'created_by' => $user->id]);
    $barId = 'forum-thread-'.$thread->id;

    $this->browse(function (Browser $browser) use ($user, $thread, $barId) {
        $browser->loginAs($user)
            ->visit(threadUrlFor($thread))
            ->waitFor('.forum-composer')
            ->click('.forum-composer')
            ->script("window.dispatchEvent(new CustomEvent('embed-selected-{$barId}', { detail: { type: 'team', id: 1, label: 'Test Team', variant: null, query: null } }))");

        $browser->pause(200)
            ->click('.forum-composer')
            // keys()'s args are variadic (one key/string per argument, not
            // an array) and, unlike type(), doesn't clear existing content
            // first — type() would wipe the chip just inserted above.
            ->keys('.forum-composer', '{end}', '{enter}')
            ->keys('.forum-composer', 'second line');

        $browser->pause(300);

        // Not `[x-ref="hidden"]` — resources/views/components/styled-select.blade.php
        // reuses that same ref name for the nav's language switcher, which
        // sits earlier in the DOM and would win a plain attribute query.
        $body = $browser->script("return document.querySelector('.forum-composer').previousElementSibling.value")[0];

        expect($body)->toContain('{{team:1}}')
            ->and($body)->toContain("\nsecond line");
    });
});

test('plain Enter after ordinary text inserts a new line and keeps typing on it, not submit or reset the caret', function () {
    $user = User::factory()->create();
    $thread = ForumThread::create(['category' => ForumThread::CATEGORY_GENERAL, 'title' => 'Composer test', 'created_by' => $user->id]);

    $this->browse(function (Browser $browser) use ($user, $thread) {
        $browser->loginAs($user)
            ->visit(threadUrlFor($thread))
            ->waitFor('.forum-composer')
            ->click('.forum-composer')
            ->keys('.forum-composer', 'hello')
            ->keys('.forum-composer', '{enter}')
            ->keys('.forum-composer', 'world');

        $browser->pause(300);

        $body = $browser->script("return document.querySelector('.forum-composer').previousElementSibling.value")[0];

        expect($body)->toBe("hello\nworld");
    });
});

test('Ctrl+Enter submits the message', function () {
    $user = User::factory()->create();
    $thread = ForumThread::create(['category' => ForumThread::CATEGORY_GENERAL, 'title' => 'Composer test', 'created_by' => $user->id]);

    $this->browse(function (Browser $browser) use ($user, $thread) {
        $browser->loginAs($user)
            ->visit(threadUrlFor($thread))
            ->waitFor('.forum-composer')
            ->click('.forum-composer')
            ->keys('.forum-composer', 'ctrl enter submits this')
            ->keys('.forum-composer', ['{control}', '{enter}']);

        $browser->pause(1000);

        $browser->assertSee('ctrl enter submits this');
    });
});
