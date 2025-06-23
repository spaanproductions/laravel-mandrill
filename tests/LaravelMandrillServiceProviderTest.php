<?php

namespace SpaanProductions\LaravelMandrill\Tests;

use SpaanProductions\LaravelMandrill\LaravelMandrillServiceProvider;
use SpaanProductions\LaravelMandrill\MandrillTransport;

class LaravelMandrillServiceProviderTest extends TestCase
{
    protected function getPackageProviders($app)
    {
        return [LaravelMandrillServiceProvider::class];
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->app['config']->set('mail.mailers.mandrill', [
            'transport' => 'mandrill',
            'api-token' => 'test-token',
            'headers' => [],
        ]);
    }

    public function testMandrillTransportIsRegistered()
    {
        $transport = app('mail.manager')->mailer('mandrill')->getSymfonyTransport();
        $this->assertInstanceOf(MandrillTransport::class, $transport);
    }
} 