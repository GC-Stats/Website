<?php

/**
 * GC-Stats — Migrate local public storage to Bunny
 *
 * One-off migration command that copies every file currently sitting in
 * the local "public" disk (storage/app/public — team/player logos, emotes,
 * news images) to the dedicated Bunny uploads storage zone. Always reads
 * from the local disk and writes to Bunny regardless of the current
 * FILESYSTEM_DISK_PUBLIC setting, so it can be run before flipping that
 * flag over to bunnycdn.
 * Usage: php artisan app:migrate-local-storage-to-bunny [--dry-run] [--force]
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('app:migrate-local-storage-to-bunny
    {--dry-run : List what would be transferred without writing anything}
    {--force : Re-upload files that already exist on Bunny}')]
#[Description('Copy local public storage files (logos, emotes, news images) to the Bunny uploads zone')]
class MigrateLocalStorageToBunny extends Command
{
    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        $config = config('filesystems.disks.public');

        if (empty($config['storage_zone']) || empty($config['api_key'])) {
            $this->error('BUNNY_UPLOADS_STORAGE_ZONE / BUNNY_UPLOADS_API_KEY are not configured.');

            return self::FAILURE;
        }

        $source = Storage::build([
            'driver' => 'local',
            'root' => public_path('storage'),
        ]);

        $destination = Storage::build([...$config, 'driver' => 'bunnycdn']);

        $files = $source->allFiles();

        if (empty($files)) {
            $this->info('No local files found to migrate.');

            return self::SUCCESS;
        }

        $this->info(sprintf('Found %d file(s) in local public storage.', count($files)));

        $copied = 0;
        $skipped = 0;
        $failed = 0;

        $bar = $this->output->createProgressBar(count($files));
        $bar->start();

        foreach ($files as $path) {
            if (! $force && $destination->exists($path)) {
                $skipped++;
                $bar->advance();

                continue;
            }

            if ($dryRun) {
                $copied++;
                $bar->advance();

                continue;
            }

            try {
                $destination->put($path, $source->get($path));
                $copied++;
            } catch (\Throwable $e) {
                $failed++;
                $this->newLine();
                $this->error("Failed to upload {$path}: {$e->getMessage()}");
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $verb = $dryRun ? 'Would transfer' : 'Transferred';
        $this->info("{$verb}: {$copied}. Skipped (already on Bunny): {$skipped}. Failed: {$failed}.");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
