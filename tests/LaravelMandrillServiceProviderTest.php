<?php

namespace SpaanProductions\LaravelMandrill\Tests;

use SpaanProductions\LaravelMandrill\MandrillTransport;
use SpaanProductions\LaravelMandrill\LaravelMandrillServiceProvider;

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

	public function test_mandrill_transport_is_registered()
	{
		$transport = app('mail.manager')->mailer('mandrill')->getSymfonyTransport();
		$this->assertInstanceOf(MandrillTransport::class, $transport);
	}

	public function test_mandrill_transport_fails_gracefully_if_api_token_missing()
	{
		$this->app['config']->set('mail.mailers.mandrill', [
			'transport' => 'mandrill',
			// 'api-token' => missing on purpose
			'headers' => [],
		]);
		$this->expectException(\Exception::class);
		app('mail.manager')->mailer('mandrill')->getSymfonyTransport();
	}
}
