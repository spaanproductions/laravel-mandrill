# LaravelMandrill

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Total Downloads][ico-downloads]][link-downloads]

This is where your description should go. Take a look at [contributing.md](contributing.md) to see a todo list.

## Note

Please note that BCC is not supported with this driver.

## Installation

Via Composer

``` bash
$ composer require spaanproductions/laravel-mandrill
```

Add the Mandrill mailer to your `config\mail.php`:

```php
'mandrill' => [
    'transport' => 'mandrill',
    'api-token' => env('MANDRILL_API_TOKEN'),
    'headers' => [
        // 'X-MC-ReturnPathDomain' => 'your.returndomain.com',
        // 'X-MC-PreserveRecipients' => true, // https://mailchimp.com/developer/transactional/docs/smtp-integration/#x-mc-preserverecipients
    ],
    // 'logger' => 'daily',
],
```

## Usage

## Change log

Please see the [changelog](changelog.md) for more information on what has changed recently.

## Testing

``` bash
$ composer test
```

## Contributing

Please see [contributing.md](contributing.md) for details and a todolist.

## Security

If you discover any security related issues, please email info@spaanproductions.nl instead of using the issue tracker.

## License

MIT. Please see the [license file](license.md) for more information.

[ico-version]: https://img.shields.io/packagist/v/spaanproductions/laravel-mandrill.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/spaanproductions/laravel-mandrill.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/spaanproductions/laravel-mandrill/master.svg?style=flat-square
[ico-styleci]: https://styleci.io/repos/12345678/shield

[link-packagist]: https://packagist.org/packages/spaanproductions/laravel-mandrill
[link-downloads]: https://packagist.org/packages/spaanproductions/laravel-mandrill
[link-travis]: https://travis-ci.org/spaanproductions/laravel-mandrill
[link-styleci]: https://styleci.io/repos/12345678
[link-author]: https://github.com/spaanproductions
[link-contributors]: ../../contributors
