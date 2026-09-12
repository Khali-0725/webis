<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // WEBIS authenticates the SPA with Sanctum's stateful cookie mode.
        // Sanctum only starts a session when the request looks like it came
        // from a first-party front end, which it decides from the Origin or
        // Referer header. Test requests carry neither, so we add one - without
        // it every test would run through the stateless token path and
        // `$request->session()` would not exist.
        $this->withHeader('Origin', config('app.url'));
    }
}
