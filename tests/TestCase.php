<?php

namespace StatamicCap\Tests;

use Statamic\Testing\AddonTestCase;

abstract class TestCase extends AddonTestCase
{
    protected string $addonServiceProvider = \StatamicCap\ServiceProvider::class;

    protected function getEnvironmentSetUp($app): void
    {
        parent::getEnvironmentSetUp($app);

        $app['config']->set('app.key', 'base64:' . base64_encode(random_bytes(32)));
    }
}
