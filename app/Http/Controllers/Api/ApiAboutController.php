<?php

/**
 * GC-Stats — About Us internal API controller
 *
 * Exposes CRUD endpoints to manage the "About Us" page content
 * (sections, team members and projects) from trusted internal services.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AboutProject;
use App\Models\AboutSection;
use App\Models\AboutTeamMember;
use App\Services\LogoUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ApiAboutController extends Controller
{
    public function __construct(private readonly LogoUploadService $logoUploadService) {}

    public function index(): JsonResponse
    {
        return response()->json([
            'sections' => AboutSection::all(),
            'team' => AboutTeamMember::orderBy('order')->get(),
            'projects' => AboutProject::orderBy('order')->get(),
        ]);
    }

    public function updateSection(string $key, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => ['sometimes', 'array'],
            'content' => ['sometimes', 'array'],
        ]);

        $section = AboutSection::firstOrNew(['key' => $key]);
        $section->fill($validated);
        $section->save();

        return response()->json([
            'success' => true,
            'section' => $section->fresh(),
        ]);
    }

    public function uploadMemberPhoto(int $id, Request $request): JsonResponse
    {
        $member = AboutTeamMember::findOrFail($id);

        $member->update(['photo_url' => $this->uploadImage($request, 'about/team')]);

        return response()->json([
            'success' => true,
            'member' => $member->fresh(),
        ]);
    }

    public function uploadProjectLogo(int $id, Request $request): JsonResponse
    {
        $project = AboutProject::findOrFail($id);

        $project->update(['logo_url' => $this->uploadImage($request, 'about/projects')]);

        return response()->json([
            'success' => true,
            'project' => $project->fresh(),
        ]);
    }

    private function uploadImage(Request $request, string $directory): string
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'max:10240'],
        ]);

        $uuid = (string) Str::uuid();

        return $this->logoUploadService->storeImage($request->file('image'), "{$directory}/{$uuid}.webp", 1000, 1000, 90);
    }

    public function storeMember(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'role' => ['sometimes', 'array'],
            'bio' => ['sometimes', 'nullable', 'array'],
            'photo_url' => ['sometimes', 'nullable', 'string', 'max:255'],
            'socials' => ['sometimes', 'array'],
            'order' => ['sometimes', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $member = AboutTeamMember::create($validated);

        return response()->json([
            'success' => true,
            'member' => $member,
        ], 201);
    }

    public function updateMember(int $id, Request $request): JsonResponse
    {
        $member = AboutTeamMember::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'role' => ['sometimes', 'array'],
            'bio' => ['sometimes', 'nullable', 'array'],
            'photo_url' => ['sometimes', 'nullable', 'string', 'max:255'],
            'socials' => ['sometimes', 'array'],
            'order' => ['sometimes', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $member->update($validated);

        return response()->json([
            'success' => true,
            'member' => $member->fresh(),
        ]);
    }

    public function destroyMember(int $id): JsonResponse
    {
        AboutTeamMember::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }

    public function storeProject(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'description' => ['sometimes', 'array'],
            'url' => ['sometimes', 'nullable', 'string', 'max:255'],
            'logo_url' => ['sometimes', 'nullable', 'string', 'max:255'],
            'order' => ['sometimes', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $project = AboutProject::create($validated);

        return response()->json([
            'success' => true,
            'project' => $project,
        ], 201);
    }

    public function updateProject(int $id, Request $request): JsonResponse
    {
        $project = AboutProject::findOrFail($id);

        $validated = $request->validate([
            'name' => ['sometimes', 'string', 'max:100'],
            'type' => ['sometimes', 'nullable', 'string', 'max:50'],
            'description' => ['sometimes', 'array'],
            'url' => ['sometimes', 'nullable', 'string', 'max:255'],
            'logo_url' => ['sometimes', 'nullable', 'string', 'max:255'],
            'order' => ['sometimes', 'integer'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $project->update($validated);

        return response()->json([
            'success' => true,
            'project' => $project->fresh(),
        ]);
    }

    public function destroyProject(int $id): JsonResponse
    {
        AboutProject::findOrFail($id)->delete();

        return response()->json(['success' => true]);
    }
}
