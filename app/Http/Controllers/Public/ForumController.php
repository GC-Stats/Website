<?php

/**
 * GC-Stats — Forum controller
 *
 * Public forum pages: category overview, the "general" thread list/creation
 * (the only category where threads are user-created — tournament/match/news
 * threads are found-or-created lazily by the embedded forum-thread Livewire
 * component, see resources/views/livewire/forum-thread.blade.php), and a
 * single thread's full view.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Public;

use App\Models\ForumThread;
use App\Services\ForumService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;

class ForumController extends Controller
{
    public function index()
    {
        $generalThreads = ForumThread::where('category', ForumThread::CATEGORY_GENERAL)
            ->latest('last_message_at')
            ->limit(10)
            ->withCount(['messages' => fn ($query) => $query->visible()])
            ->get();

        return view('public.forum.index', ['generalThreads' => $generalThreads]);
    }

    public function generalIndex()
    {
        $threads = ForumThread::where('category', ForumThread::CATEGORY_GENERAL)
            ->latest('last_message_at')
            ->withCount(['messages' => fn ($query) => $query->visible()])
            ->paginate(20);

        return view('public.forum.general.index', ['threads' => $threads]);
    }

    public function generalCreate()
    {
        return view('public.forum.general.create');
    }

    public function generalStore(Request $request): RedirectResponse
    {
        abort_if(Auth::user()->activeGlobalBlockingSanction(), 403);
        abort_if(Auth::user()->activeGlobalMuteSanction(), 403);

        $limiterKey = 'forum-thread-create:'.Auth::id();

        if (RateLimiter::tooManyAttempts($limiterKey, 3)) {
            return back()->withErrors(['title' => __('forum.errors.too_many_threads')])->withInput();
        }

        $data = $request->validate([
            'title' => ['required', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:5000'],
        ]);

        RateLimiter::hit($limiterKey, 600);

        $thread = app(ForumService::class)->createGeneralThread(Auth::user(), $data['title'], $data['body']);

        return redirect()->route('forum.threads.show', $thread);
    }

    public function show(ForumThread $thread)
    {
        return view('public.forum.threads.show', ['thread' => $thread]);
    }
}
