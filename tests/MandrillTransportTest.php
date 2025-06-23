<?php

namespace SpaanProductions\LaravelMandrill\Tests;

use Symfony\Component\Mime\Email;
use MailchimpTransactional\ApiClient;
use Symfony\Component\Mailer\Envelope;
use Symfony\Component\Mailer\SentMessage;
use GuzzleHttp\Exception\RequestException;
use SpaanProductions\LaravelMandrill\MandrillTransport;
use SpaanProductions\LaravelMandrill\Exceptions\MandrillTransportException;

class MandrillTransportTest extends TestCase
{
	public function test_to_string_returns_mandrill()
	{
		$transport = new MandrillTransport($this->createMock(ApiClient::class), []);
		$this->assertSame('mandrill', (string)$transport);
	}

	public function test_set_headers_adds_configured_headers()
	{
		$apiClient = $this->createMock(ApiClient::class);
		$config = ['headers' => ['X-Test-Header' => 'Value']];
		$transport = new MandrillTransport($apiClient, $config);
		$email = (new Email)->from('sender@example.com')->to('test@example.com')->subject('Test')->text('Body');
		$envelope = new Envelope($email->getFrom()[0], $email->getTo());
		$sentMessage = new SentMessage($email, $envelope);
		$result = $this->invokeMethod($transport, 'setHeaders', [$sentMessage]);
		$this->assertSame('Value', $result->getOriginalMessage()->getHeaders()->get('X-Test-Header')->getBodyAsString());
	}

	public function test_get_header_returns_null_if_header_not_set()
	{
		$apiClient = $this->createMock(ApiClient::class);
		$transport = new MandrillTransport($apiClient, []);
		$email = (new Email)->from('sender@example.com')->to('test@example.com')->subject('Test')->text('Body');
		$envelope = new Envelope($email->getFrom()[0], $email->getTo());
		$sentMessage = new SentMessage($email, $envelope);
		$result = $this->invokeMethod($transport, 'getHeader', [$sentMessage, 'X-Not-Set']);
		$this->assertNull($result);
	}

	public function test_do_send_throws_exception_on_request_exception()
	{
		$this->expectException(MandrillTransportException::class);
		$apiClient = $this->createMock(ApiClient::class);
		$messagesStub = new class {
			public function sendRaw($request)
			{
				return new RequestException('Error', new \GuzzleHttp\Psr7\Request('POST', '/'));
			}
		};
		$apiClient->messages = $messagesStub;
		$transport = new MandrillTransport($apiClient, []);
		$email = (new Email)->from('sender@example.com')->to('test@example.com')->subject('Test')->text('Body');
		$envelope = new Envelope($email->getFrom()[0], $email->getTo());
		$sentMessage = new SentMessage($email, $envelope);
		$this->invokeProtectedMethod($transport, 'doSend', [$sentMessage]);
	}

	protected function invokeMethod($object, $method, array $args = [])
	{
		$reflection = new \ReflectionClass($object);
		$method = $reflection->getMethod($method);
		$method->setAccessible(true);

		return $method->invokeArgs($object, $args);
	}

	protected function invokeProtectedMethod($object, $method, array $args = [])
	{
		return $this->invokeMethod($object, $method, $args);
	}
}
