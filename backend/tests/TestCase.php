<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Los tests no dependen de que los assets (Vite/Tailwind) estén compilados
        $this->withoutVite();
    }
}
