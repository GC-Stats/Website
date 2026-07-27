<?php

/**
 * GC-Stats — Filesystem configuration
 *
 * Standard Laravel filesystem config defining the default disk and
 * available storage disks, used for storing player photos and team logos.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default filesystem disk that should be used
    | by the framework. The "local" disk, as well as a variety of cloud
    | based disks are available to your application for file storage.
    |
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | Below you may configure as many filesystem disks as necessary, and you
    | may even configure multiple disks for the same driver. Examples for
    | most supported storage drivers are configured here for reference.
    |
    | Supported drivers: "local", "ftp", "sftp", "s3"
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root' => storage_path('app/private'),
            'serve' => true,
            'throw' => false,
            'report' => false,
        ],

        'public' => [
            'driver' => env('FILESYSTEM_DISK_PUBLIC', 'local'),
            'root' => public_path('storage'),
            'url' => rtrim(env('APP_URL', 'http://localhost'), '/').'/storage',
            'visibility' => 'public',
            'throw' => false,
            'report' => false,
            // Used when FILESYSTEM_DISK_PUBLIC=bunnycdn; ignored by the local driver.
            // Dedicated storage zone, separate from the BUNNY_* zone used for the open dataset export.
            'storage_zone' => env('BUNNY_UPLOADS_STORAGE_ZONE'),
            'api_key' => env('BUNNY_UPLOADS_API_KEY'),
            'region' => env('BUNNY_UPLOADS_REGION', ''),
            'pull_zone' => env('BUNNY_UPLOADS_PULL_ZONE_URL'),
        ],

        's3' => [
            'driver' => env('FILESYSTEM_DISK_S3', 's3'),
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
            'region' => env('AWS_DEFAULT_REGION', 'gra'),
            'bucket' => env('AWS_BUCKET'),
            'url' => env('AWS_URL'),
            'endpoint' => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'visibility' => 'public',
        ],

        'bunny' => [
            'driver' => 'bunnycdn',
            'storage_zone' => env('BUNNY_STORAGE_ZONE'),
            'api_key' => env('BUNNY_STORAGE_API_KEY'),
            'region' => env('BUNNY_STORAGE_REGION', ''),
            'pull_zone' => env('BUNNY_PULL_ZONE_URL'),
            'throw' => true,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    |
    | Here you may configure the symbolic links that will be created when the
    | `storage:link` Artisan command is executed. The array keys should be
    | the locations of the links and the values should be their targets.
    |
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
