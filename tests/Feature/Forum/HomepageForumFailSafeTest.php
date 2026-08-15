<?php

use Illuminate\Support\Facades\Schema;

test('the homepage stays up and hides the forum section if the forum tables are unavailable', function () {
    // Simulates a forum-side outage (broken migration, table locked, etc.)
    // without reaching into HomeController's internals — the homepage must
    // degrade to "no forum section", not 500 the whole page.
    Schema::rename('forum_threads', 'forum_threads_moved');

    try {
        $response = $this->get(route('home'));

        $response->assertOk();
        $response->assertDontSee(__('forum.title.index'));
    } finally {
        Schema::rename('forum_threads_moved', 'forum_threads');
    }
});
