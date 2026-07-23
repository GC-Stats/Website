<?php

/**
 * GC-Stats — Admin: emotes
 *
 * CRUD over the site's custom reaction emotes (images, not unicode emoji —
 * see App\Models\Emote). An emote's image either comes from a direct
 * upload, or is copied from a team's current logo at creation time (a
 * frozen snapshot — it does not track that team's logo afterward).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Emote;
use App\Models\Team;
use App\Services\LogoUploadService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmoteController extends Controller
{
    private const SORTABLE = ['name', 'status', 'created', 'source'];

    public function index(Request $request): View
    {
        $search = $request->get('q');
        $statusFilter = $request->get('status');
        $sourceFilter = $request->get('source');

        [$sort, $direction] = $this->resolveSort($request, self::SORTABLE, 'name', 'asc');

        $emotes = Emote::query()
            ->when($search, fn ($query) => $query->where('name', 'like', '%'.$this->escapeLike($search).'%'))
            ->when($statusFilter === 'active', fn ($query) => $query->where('is_active', true))
            ->when($statusFilter === 'inactive', fn ($query) => $query->where('is_active', false))
            ->when($sourceFilter, fn ($query) => $query->where('source', $sourceFilter))
            ->when($sort === 'status', fn ($query) => $query->orderBy('is_active', $direction))
            ->when($sort === 'created', fn ($query) => $query->orderBy('created_at', $direction))
            ->when($sort === 'source', fn ($query) => $query->orderBy('source', $direction))
            ->when($sort === 'name', fn ($query) => $query->orderBy('name', $direction))
            ->paginate(25)
            ->withQueryString();

        return view('admin.emotes.index', [
            'emotes' => $emotes,
            'search' => $search ?? '',
            'statusFilter' => $statusFilter ?? '',
            'sourceFilter' => $sourceFilter ?? '',
            'sources' => Emote::sources(),
            'sort' => $sort,
            'direction' => $direction,
        ]);
    }

    public function create(): View
    {
        return view('admin.emotes.create', [
            'teams' => Team::orderBy('name')->get(['id', 'name']),
            'sources' => Emote::sources(),
        ]);
    }

    public function store(Request $request, LogoUploadService $service): RedirectResponse
    {
        $validated = $this->validateEmote($request);
        ['path' => $path, 'source' => $source] = $this->resolveImage($request, $service, $validated);

        $emote = Emote::create([
            'name' => $validated['name'],
            'source' => $source,
            'is_active' => $request->boolean('is_active', true),
            'image_path' => $path,
        ]);

        $this->forgetEmoteCaches();

        activity('emote')->causedBy($request->user())->performedOn($emote)->log('emote.created');

        return redirect()->route('admin.emotes.index')->with('status', 'emote-created');
    }

    public function edit(Emote $emote): View
    {
        return view('admin.emotes.edit', [
            'emote' => $emote,
            'teams' => Team::orderBy('name')->get(['id', 'name']),
            'sources' => Emote::sources(),
        ]);
    }

    public function update(Request $request, Emote $emote, LogoUploadService $service): RedirectResponse
    {
        $validated = $this->validateEmote($request, $emote);

        $replacingImage = $request->hasFile('image') || $request->filled('team_id');
        ['path' => $imagePath, 'source' => $source] = $replacingImage
            ? $this->resolveImage($request, $service, $validated)
            : ['path' => $emote->image_path, 'source' => $emote->source];

        if ($imagePath !== $emote->image_path) {
            Storage::disk('public')->delete($emote->image_path);
        }

        $emote->update([
            'name' => $validated['name'],
            'source' => $source,
            'is_active' => $request->boolean('is_active'),
            'image_path' => $imagePath,
        ]);

        $this->forgetEmoteCaches();

        activity('emote')->causedBy($request->user())->performedOn($emote)->log('emote.updated');

        return redirect()->route('admin.emotes.index')->with('status', 'emote-updated');
    }

    public function destroy(Request $request, Emote $emote): RedirectResponse
    {
        Storage::disk('public')->delete($emote->image_path);
        $emote->delete();

        $this->forgetEmoteCaches();

        activity('emote')->causedBy($request->user())->log('emote.deleted');

        return redirect()->route('admin.emotes.index')->with('status', 'emote-deleted');
    }

    private function forgetEmoteCaches(): void
    {
        Emote::forgetActiveCache();
        Emote::forgetSourcesCache();
    }

    private function validateEmote(Request $request, ?Emote $emote = null): array
    {
        $validated = $request->validate([
            'name' => [
                'required', 'string', 'max:80', 'alpha_dash',
                Rule::unique('emotes', 'name')->ignore($emote?->id),
            ],
            // Laravel's `image` rule rejects svg — Twemoji/custom vector
            // uploads need it, so mimes is checked explicitly instead.
            'image' => ['nullable', 'file', 'mimes:svg,png,jpg,jpeg,webp', 'max:2048'],
            'team_id' => ['nullable', 'integer', 'exists:teams,id'],
            // Storage subfolder for a direct upload — ignored when copying
            // a team logo, which always lands under "teams" (see
            // resolveImage).
            'source' => ['nullable', 'string', 'max:40', 'alpha_dash'],
        ]);

        $hasImage = $request->hasFile('image');
        $hasTeam = $request->filled('team_id');

        if ($emote === null && ! $hasImage && ! $hasTeam) {
            throw ValidationException::withMessages([
                'image' => __('admin.emotes.errors.source_required'),
            ]);
        }

        if ($hasImage && $hasTeam) {
            throw ValidationException::withMessages([
                'image' => __('admin.emotes.errors.source_conflict'),
            ]);
        }

        if ($hasImage && ! $request->filled('source')) {
            throw ValidationException::withMessages([
                'source' => __('admin.emotes.errors.source_field_required'),
            ]);
        }

        return $validated;
    }

    /**
     * @param  array{source?: ?string}  $validated
     * @return array{path: string, source: string}
     */
    private function resolveImage(Request $request, LogoUploadService $service, array $validated): array
    {
        if ($request->hasFile('image')) {
            $file = $request->file('image');
            // Always present here: validateEmote() requires "source" to be
            // filled whenever an image is uploaded.
            $source = $validated['source'] ?? null;
            $folder = 'emotes/'.$source;

            // SVGs are vector — stored as-is (Intervention/GD can't decode
            // them as raster). PNG/JPG go through the normal resize+webp
            // pipeline like every other image upload on the site.
            if ($file->getClientOriginalExtension() === 'svg' || $file->getMimeType() === 'image/svg+xml') {
                $path = $folder.'/'.Str::uuid().'.svg';
                Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

                return ['path' => $path, 'source' => $source];
            }

            $path = $folder.'/'.Str::uuid().'.webp';
            $service->storeImage($file, $path, 200, 200, 90);

            return ['path' => $path, 'source' => $source];
        }

        $team = Team::with('currentLogo')->findOrFail($request->input('team_id'));
        abort_if($team->currentLogo === null, 422, __('admin.emotes.errors.team_no_logo'));

        $path = 'emotes/teams/'.Str::uuid().'.webp';
        Storage::disk('public')->copy($service->thumbnailPath('teams', $team->currentLogo->id), $path);

        return ['path' => $path, 'source' => 'teams'];
    }
}
