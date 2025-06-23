<?php

namespace SpaanProductions\LaravelMandrill;

use Illuminate\Support\Facades\Mail;
use MailchimpTransactional\ApiClient;
use Illuminate\Support\ServiceProvider;

class LaravelMandrillServiceProvider extends ServiceProvider
{
	/**
	 * Perform post-registration booting of services.
	 *
	 * @return void
	 */
	public function boot(): void
	{
		Mail::extend('mandrill', function (array $config = []) {
			$client = new ApiClient;
			$client->setApiKey($config['api-token']);

			return new MandrillTransport($client, $config);
		});
	}

	/**
	 * Register any package services.
	 *
	 * @return void
	 */
	public function register(): void
	{
		//
	}
}
