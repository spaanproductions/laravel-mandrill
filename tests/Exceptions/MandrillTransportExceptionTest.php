<?php

namespace SpaanProductions\LaravelMandrill\Tests\Exceptions;

use GuzzleHttp\Exception\RequestException;
use PHPUnit\Framework\TestCase;
use SpaanProductions\LaravelMandrill\Exceptions\MandrillTransportException;
use GuzzleHttp\Psr7\Request;

class MandrillTransportExceptionTest extends TestCase
{
    public function testExceptionMessageAndCode()
    {
        $request = new Request('POST', '/');
        $previous = new RequestException('Test error', $request, null, null, ['code' => 123]);
        $exception = new MandrillTransportException($previous);
        $this->assertStringContainsString('Test error', $exception->getMessage());
        $this->assertSame($previous->getCode(), $exception->getCode());
        $this->assertSame($previous, $exception->getPrevious());
    }
} 