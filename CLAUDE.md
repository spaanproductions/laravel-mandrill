# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## What this package is

A Laravel mail driver that plugs the Mandrill (Mailchimp Transactional) API into Laravel's mailer as a `mandrill` transport. The whole package is two source files plus a custom exception — keep changes minimal and focused; this is not a general-purpose abstraction layer.

Notable constraint: **BCC is not supported** by this driver (the API path used is `messages.sendRaw` with a raw MIME document, and the project has intentionally not implemented BCC handling — see `contributing.md` todo).

## Commands

```bash
# Install
composer install

# Run the full test suite
./vendor/bin/phpunit

# Run a single test (PHPUnit uses snake_case method names — see .php-cs-fixer.php)
./vendor/bin/phpunit --filter test_do_send_throws_exception_on_request_exception

# Run one test file
./vendor/bin/phpunit tests/MandrillTransportTest.php

# Lint / auto-format (PHP-CS-Fixer is a dev dep transitively; not in composer.json)
vendor/bin/php-cs-fixer fix
```

Note: the README mentions `composer test`, but no `scripts` section is defined in `composer.json` — invoke PHPUnit directly.

CI matrix (`.github/workflows/main.yml`) tests PHP 8.3 / 8.4 / 8.5 against Laravel 10–13 with both `prefer-lowest` and `prefer-stable`. Keep changes compatible across that matrix; supported PHP and Laravel ranges are pinned in `composer.json`.

## Architecture

The transport is wired in via Laravel's `Mail::extend` mechanism, not by replacing the mailer:

1. `LaravelMandrillServiceProvider::boot()` calls `Mail::extend('mandrill', ...)`. The closure receives the `config/mail.php` mailer config array (the `mandrill` block), instantiates a `MailchimpTransactional\ApiClient` with `api-token`, and returns a `MandrillTransport`.
2. The service provider is auto-registered via `composer.json` → `extra.laravel.providers`.
3. `MandrillTransport` extends `Symfony\Component\Mailer\Transport\AbstractTransport`. `doSend()`:
   - Merges configured `headers` from the mailer config onto the outgoing message (`setHeaders`).
   - Serializes the full MIME message via `$message->toString()` and POSTs it as `raw_message` to `messages.sendRaw` with `async: true`.
   - Special-cases `X-MC-ReturnPathDomain`: if present on the message headers, it is lifted into the API request as `return_path_domain` (Mandrill's MIME endpoint doesn't honor it as a header).
   - Wraps any `GuzzleHttp\Exception\RequestException` returned by the SDK (the SDK returns rather than throws on failure) into `MandrillTransportException`.
   - Writes the returned Mandrill message ID back onto the original message as the `X-Message-ID` header.
4. Optional `logger` config key selects a Laravel log channel via `Log::channel($name)` and passes it to `AbstractTransport`'s parent constructor; otherwise logging is silent.

When changing the transport, the key invariant is that the SDK's `sendRaw` does **not** throw on HTTP errors — it returns a `RequestException` instance — so the `instanceof RequestException` check on the response is load-bearing. Don't replace it with try/catch.

## Style

- **Indentation is tabs**, not spaces (`->setIndent("\t")` in `.php-cs-fixer.php`). All existing files use tabs; match them.
- PHP-CS-Fixer config layers `@PSR12` + `@PHP83Migration` with project-specific overrides. Of note: `ordered_imports` sorts by **length**, not alphabetically; `php_unit_method_casing` is `snake_case` (so test methods are `test_foo_bar`, not `testFooBar`).
- PHPUnit config has `failOnRisky=true` and `failOnWarning=true` — tests that emit deprecation warnings or are marked risky will fail CI.

## Testing

Tests extend `SpaanProductions\LaravelMandrill\Tests\TestCase`, which extends `Orchestra\Testbench\TestCase` so a real Laravel container is bootstrapped. The transport itself is exercised by:

- Constructing a real `MandrillTransport` with a mocked `ApiClient` (use `$apiClient->messages = new class { ... }` to stub the nested `messages` property — `createMock` alone won't reach it).
- Building `Symfony\Component\Mime\Email` + `Envelope` + `SentMessage` directly rather than going through the mailer.
- Invoking protected methods via `ReflectionClass` (`invokeMethod` / `invokeProtectedMethod` helpers in `MandrillTransportTest`).
