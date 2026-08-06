<?php

/**
 * GC-Stats — Admin: "About Us" page content
 *
 * Manages the remaining two content blocks of the public About page
 * (App\Models\AboutSection, AboutProject): translatable text sections and
 * showcased projects. The team section is no longer admin-curated — it's
 * driven by real User accounts holding a global role, see
 * Public\AboutController. Image uploads go through
 * App\Services\LogoUploadService::storeImage() (webp, single file, no
 * history — same helper already used by Api\ApiAboutController).
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Public\Controller;
use App\Models\AboutProject;
use App\Models\AboutSection;
use App\Services\LogoUploadService;
use App\Support\Activity\ActivityChangeSet;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AboutController extends Controller
{
    public const LOCALES = ['en', 'fr'];

    public const PROJECT_TYPES = ['Website', 'API', 'DiscordBot'];

    public function __construct(private readonly LogoUploadService $logoUploadService) {}

    public function index(): View
    {
        return view('admin.about.index', [
            'sections' => AboutSection::orderBy('order')->get()->keyBy('key'),
            'projects' => AboutProject::orderBy('order')->get(),
            'locales' => self::LOCALES,
            'projectTypes' => self::PROJECT_TYPES,
        ]);
    }

    public function saveSection(string $key, Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'order' => ['sometimes', 'integer'],
            'title' => ['array'],
            'title.*' => ['nullable', 'string', 'max:255'],
            'content' => ['array'],
            'content.*' => ['nullable', 'string', 'max:5000'],
        ]);

        $section = AboutSection::firstOrNew(['key' => $key]);
        $section->fill($validated);
        $section->save();

        activity('administration')->causedBy($request->user())
            ->performedOn($section)
            ->withProperties(ActivityChangeSet::fromModel($section, array_keys($validated))->toArray())
            ->log('about_section.updated');

        return back()->with('status', 'about-section-updated');
    }

    public function storeProject(Request $request): RedirectResponse
    {
        $payload = $this->buildProjectPayload($request);
        $project = AboutProject::create($payload);

        activity('administration')->causedBy($request->user())
            ->performedOn($project)
            ->withProperties(ActivityChangeSet::fromCreated($project, array_keys($payload))->toArray())
            ->log('about_project.created');

        return back()->with('status', 'about-project-created');
    }

    public function updateProject(Request $request, AboutProject $project): RedirectResponse
    {
        $payload = $this->buildProjectPayload($request);
        $project->update($payload);

        activity('administration')->causedBy($request->user())
            ->performedOn($project)
            ->withProperties(ActivityChangeSet::fromModel($project, array_keys($payload))->toArray())
            ->log('about_project.updated');

        return back()->with('status', 'about-project-updated');
    }

    public function destroyProject(Request $request, AboutProject $project): RedirectResponse
    {
        $name = $project->name;
        $project->delete();

        activity('administration')->causedBy($request->user())
            ->withProperties(['name' => $name])
            ->log('about_project.deleted');

        return back()->with('status', 'about-project-deleted');
    }

    public function uploadProjectLogo(Request $request, AboutProject $project): RedirectResponse
    {
        $project->update(['logo_url' => $this->uploadImage($request, 'about/projects')]);

        activity('administration')->causedBy($request->user())
            ->performedOn($project)
            ->withProperties(ActivityChangeSet::fromModel($project, ['logo_url'])->toArray())
            ->log('about_project.logo_updated');

        return back()->with('status', 'about-project-updated');
    }

    private function buildProjectPayload(Request $request): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['sometimes', 'nullable', Rule::in(self::PROJECT_TYPES)],
            'description' => ['sometimes', 'array'],
            'description.*' => ['nullable', 'string', 'max:2000'],
            'url' => ['sometimes', 'nullable', 'url', 'max:255'],
            'order' => ['sometimes', 'integer'],
        ]);

        $data['is_active'] = $request->boolean('is_active');

        return $data;
    }

    private function uploadImage(Request $request, string $directory): string
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'max:10240'],
        ]);

        $uuid = (string) Str::uuid();

        return $this->logoUploadService->storeImage($request->file('image'), "{$directory}/{$uuid}.webp", 1000, 1000, 90);
    }
}
