<?php

/**
 * GC-Stats — Shared logo/image upload service
 *
 * Centralises the boilerplate that was previously duplicated across the
 * various Api*LogoController classes (ApiTeamLogoController,
 * ApiPlayerLogoController, ApiTournamentLogoController,
 * ApiAuthorLogoController, ApiPublisherLogoController)
 * and ApiAboutController::uploadImage: decoding an uploaded image, resizing
 * it, encoding it to WebP, storing it on disk, and (for the "logo" family)
 * recording/removing rows in the `logos` table.
 *
 * Two upload "shapes" are supported, matching what the controllers already
 * did:
 *  - storeLogoPair(): stores a 200x200 thumbnail + a full-size WebP under
 *    "{folder}/{uuid}/200x200.webp" and "{folder}/{uuid}/full.webp". Used by
 *    every Api*LogoController.
 *  - storeImage(): stores a single scaled-down WebP at an explicit path and
 *    returns its public URL. Used by ApiAboutController::uploadImage.
 *
 * The `logos` DB bookkeeping itself comes in two flavours that were
 * duplicated identically (modulo the entity type string and model) across
 * controllers:
 *  - acceptWithHistory(): keeps a from/until history (Team, Player,
 *    Tournament logos) — closes out the currently-open logo and opens a new
 *    one, or inserts a dated historical entry when `until` is given.
 *  - acceptReplacing(): no history — deletes any previous logo(s) for the
 *    entity before creating the new one (NewsAuthor, NewsMedia,
 *    NewsPublisher logos).
 *
 * All disk/folder/dimension/quality values are passed in by the caller
 * rather than hard-coded here, since some entities may use different
 * storage disks or dimensions.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Services;

use App\Models\Logo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Intervention\Image\Encoders\WebpEncoder;
use Intervention\Image\ImageManager;

class LogoUploadService
{
    /**
     * Decode an uploaded image and store a thumbnail + full-size WebP pair
     * under "{folder}/{uuid}/200x200.webp" and "{folder}/{uuid}/full.webp".
     *
     * Returns the generated UUID (the "uuid" the controllers previously
     * generated with Str::uuid()).
     */
    public function storeLogoPair(
        UploadedFile $file,
        string $folder,
        string $disk = 'public',
        int $thumbWidth = 200,
        int $thumbHeight = 200,
        int $thumbQuality = 80,
        int $fullQuality = 90
    ): string {
        ini_set('memory_limit', '512M');

        $manager = ImageManager::usingDriver(GdDriver::class);

        $image = $manager->decode($file->getPathname());

        $thumbnail = (clone $image)->scaleDown(width: $thumbWidth, height: $thumbHeight)->encode(new WebpEncoder(quality: $thumbQuality));

        $full = $image->encode(new WebpEncoder(quality: $fullQuality));

        $uuid = (string) Str::uuid();

        Storage::disk($disk)->put("{$folder}/{$uuid}/full.webp", (string) $full);
        Storage::disk($disk)->put("{$folder}/{$uuid}/200x200.webp", (string) $thumbnail);

        return $uuid;
    }

    /**
     * Decode an uploaded image, scale it down and store a single WebP file
     * at the given path. Returns its public URL. Used where no thumbnail /
     * `logos` table bookkeeping is involved (ApiAboutController).
     */
    public function storeImage(
        UploadedFile $file,
        string $path,
        ?int $width = null,
        ?int $height = null,
        int $quality = 90,
        string $disk = 'public'
    ): string {
        ini_set('memory_limit', '512M');

        $manager = ImageManager::usingDriver(GdDriver::class);

        $image = $manager->decode($file->getPathname());
        $image = $image->scaleDown(width: $width, height: $height);

        $encoded = $image->encode(new WebpEncoder(quality: $quality));

        Storage::disk($disk)->put($path, (string) $encoded);

        return Storage::disk($disk)->url($path);
    }

    public function thumbnailExists(string $folder, string $uuid, string $disk = 'public'): bool
    {
        return Storage::disk($disk)->exists("{$folder}/{$uuid}/200x200.webp");
    }

    public function thumbnailUrl(string $folder, string $uuid, string $disk = 'public'): string
    {
        return Storage::disk($disk)->url("{$folder}/{$uuid}/200x200.webp");
    }

    public function deleteFiles(string $folder, string $uuid, string $disk = 'public'): void
    {
        Storage::disk($disk)->deleteDirectory("{$folder}/{$uuid}");
    }

    /**
     * Accept a pending logo upload for an entity that keeps a from/until
     * history (Team, Player, Tournament). Mirrors the previously duplicated
     * `acceptLogo()` private methods.
     */
    public function acceptWithHistory(
        Model $entity,
        string $entityType,
        string $uuid,
        ?string $from = null,
        ?string $until = null
    ): Logo {
        if ($until) {
            return Logo::create([
                'id' => $uuid,
                'entity_type' => $entityType,
                'entity_id' => $entity->id,
                'from' => $from ?? now(),
                'until' => $until,
            ]);
        }

        Logo::where('entity_type', $entityType)->where('entity_id', $entity->id)->whereNull('until')->update(['until' => now()]);

        $logo = Logo::create([
            'id' => $uuid,
            'entity_type' => $entityType,
            'entity_id' => $entity->id,
            'from' => $from ?? now(),
        ]);

        $entity->touch();

        return $logo;
    }

    /**
     * Accept a pending logo upload for an entity that keeps no history
     * (NewsAuthor, NewsMedia, NewsPublisher): deletes any previous logo(s)
     * from disk and the DB before creating the new one. Expects the entity
     * to expose a `logos` relation.
     */
    public function acceptReplacing(
        Model $entity,
        string $entityType,
        string $uuid,
        string $folder,
        string $disk = 'public'
    ): Logo {
        foreach ($entity->logos as $old) {
            $this->deleteFiles($folder, $old->id, $disk);
            $old->delete();
        }

        return Logo::create([
            'id' => $uuid,
            'entity_type' => $entityType,
            'entity_id' => $entity->id,
            'from' => now(),
        ]);
    }

    public function deleteLogo(string $entityType, string $folder, string $uuid, string $disk = 'public'): void
    {
        $logo = Logo::where('entity_type', $entityType)->findOrFail($uuid);

        $this->deleteFiles($folder, $logo->id, $disk);

        $logo->delete();
    }
}
