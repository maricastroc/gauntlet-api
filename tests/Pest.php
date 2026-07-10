<?php

declare(strict_types=1);
use Tests\TestCase;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| Feature tests boot the Laravel application via Tests\TestCase.
| Unit tests (incl. app/Domain) are plain PHP and don't need the framework.
|
*/

pest()->extend(TestCase::class)->in('Feature');
