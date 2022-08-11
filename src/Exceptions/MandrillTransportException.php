<?php

namespace SpaanProductions\LaravelMandrill\Exceptions;

use Exception;
use GuzzleHttp\Exception\RequestException;

class MandrillTransportException extends Exception
{
    public function __construct(RequestException $exception)
    {
        parent::__construct(
            sprintf('Request to Mandrill API failed. Reason: %s.', $exception->getMessage()),
            is_int($exception->getCode()) ? $exception->getCode() : 0,
            $exception
        );
    }
}
