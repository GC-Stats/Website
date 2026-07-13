<?php

/**
 * GC-Stats — About Us seeder
 *
 * Seeds the default "About Us" page content (presentation and future
 * plans sections), used as a starting point before being edited via the
 * internal API.
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace Database\Seeders;

use App\Models\AboutSection;
use Illuminate\Database\Seeder;

class AboutSeeder extends Seeder
{
    public function run(): void
    {
        AboutSection::updateOrCreate(
            ['key' => 'presentation'],
            [
                'title' => [
                    'en' => 'Who We Are',
                    'fr' => 'Qui sommes-nous',
                ],
                'content' => [
                    'en' => 'GC-Stats is a community project providing statistics and coverage for official GC Valorant competitions.',
                    'fr' => 'GC-Stats est un projet communautaire qui propose des statistiques et un suivi des compétitions officielles GC Valorant.',
                ],
            ]
        );

        AboutSection::updateOrCreate(
            ['key' => 'future'],
            [
                'title' => [
                    'en' => "What's Next",
                    'fr' => 'Et ensuite ?',
                ],
                'content' => [
                    'en' => 'We are working on new features and integrations. Stay tuned!',
                    'fr' => 'Nous travaillons sur de nouvelles fonctionnalités et intégrations. Restez à l\'écoute !',
                ],
            ]
        );
    }
}
