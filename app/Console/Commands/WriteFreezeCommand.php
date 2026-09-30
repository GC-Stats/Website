<?php

/**
 * GC-Stats — Toggle the public write freeze
 *
 * Blocks every public creation/edition of migrated data during the V2
 * migration, without taking the site down (see App\Support\WriteFreeze).
 * Usage: php artisan app:write-freeze on|off|status
 *
 * @copyright Copyright (c) 2026 Alice Alleman — GC-Stats-Website
 * @license   https://github.com/GC-Stats/Website/blob/main/LICENSE GC-Stats License v1.0
 *
 * @link      https://github.com/GC-Stats/Website
 */

namespace App\Console\Commands;

use App\Support\WriteFreeze;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('app:write-freeze {state=status : on, off or status}')]
#[Description('Freeze or unfreeze public writes (change requests, forum, reactions, reports)')]
class WriteFreezeCommand extends Command
{
    public function handle(): int
    {
        switch ($this->argument('state')) {
            case 'on':
                WriteFreeze::enable();
                $this->info('Public writes frozen.');
                break;
            case 'off':
                WriteFreeze::disable();
                $this->info('Public writes unfrozen.');
                break;
            case 'status':
                break;
            default:
                $this->error('State must be on, off or status.');

                return self::INVALID;
        }

        $this->line(WriteFreeze::active()
            ? 'Status: FROZEN since '.WriteFreeze::since()?->toDateTimeString()
            : 'Status: open');

        return self::SUCCESS;
    }
}
