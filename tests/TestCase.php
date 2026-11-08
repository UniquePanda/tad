<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Inertia looks for pages in `js/Pages` by default; the starter kit uses
        // a lowercase `js/pages` directory (matters on case-sensitive filesystems).
        config()->set('inertia.testing.page_paths', [resource_path('js/pages')]);
    }
}
