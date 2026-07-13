<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\DuskTestCase;
use Tests\TestCase;

uses(
    DuskTestCase::class,
)->in('Browser');

uses(
    TestCase::class,
    RefreshDatabase::class,
)->in('Feature');
