<?php

namespace SpaanProductions\LaravelMandrill\Tests\Exceptions;

use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestCase;
use GuzzleHttp\Exception\RequestException;
use SpaanProductions\LaravelMandrill\Exceptions\MandrillTransportException;

class MandrillTransportExceptionTest extends TestCase
{
	public function test_exception_message_and_code()
	{
		$request = new Request('POST', '/');
		$previous = new RequestException('Test error', $request, null, null, ['code' => 123]);
		$exception = new MandrillTransportException($previous);
		$this->assertStringContainsString('Test error', $exception->getMessage());
		$this->assertSame($previous->getCode(), $exception->getCode());
		$this->assertSame($previous, $exception->getPrevious());
	}

	public function test_exception_falls_back_to_zero_code_if_non_integer()
	{
		$request = new Request('POST', '/');
		$previous = new RequestException('Test error', $request, null, null, ['code' => 'not-an-int']);
		$exception = new MandrillTransportException($previous);
		$this->assertSame(0, $exception->getCode());
	}
}
