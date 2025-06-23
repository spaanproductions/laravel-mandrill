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

	public function test_logger_channel_is_used_if_provided()
	{
		$apiClient = $this->createMock(ApiClient::class);
		$config = ['logger' => 'single'];
		$transport = new MandrillTransport($apiClient, $config);
		$reflection = new \ReflectionClass($transport);
		$method = $reflection->getMethod('getLogger');
		$method->setAccessible(true);
		$logger = $method->invoke($transport);
		$this->assertNotNull($logger);
	}

	public function test_set_headers_with_empty_headers_config_does_not_add_headers()
	{
		$apiClient = $this->createMock(ApiClient::class);
		$config = ['headers' => []];
		$transport = new MandrillTransport($apiClient, $config);
		$email = (new Email)->from('sender@example.com')->to('test@example.com')->subject('Test')->text('Body');
		$envelope = new Envelope($email->getFrom()[0], $email->getTo());
		$sentMessage = new SentMessage($email, $envelope);
		$result = $this->invokeMethod($transport, 'setHeaders', [$sentMessage]);
		$this->assertNull($result->getOriginalMessage()->getHeaders()->get('X-Test-Header'));
	}

	public function test_set_headers_with_multiple_headers_adds_all()
	{
		$apiClient = $this->createMock(ApiClient::class);
		$config = ['headers' => [
			'X-Header-One' => 'One',
			'X-Header-Two' => 'Two',
		]];
		$transport = new MandrillTransport($apiClient, $config);
		$email = (new Email)->from('sender@example.com')->to('test@example.com')->subject('Test')->text('Body');
		$envelope = new Envelope($email->getFrom()[0], $email->getTo());
		$sentMessage = new SentMessage($email, $envelope);
		$result = $this->invokeMethod($transport, 'setHeaders', [$sentMessage]);
		$this->assertSame('One', $result->getOriginalMessage()->getHeaders()->get('X-Header-One')->getBodyAsString());
		$this->assertSame('Two', $result->getOriginalMessage()->getHeaders()->get('X-Header-Two')->getBodyAsString());
	}

	public function test_do_send_adds_return_path_domain_if_header_present()
	{
		$apiClient = $this->createMock(ApiClient::class);
		$messagesStub = new class {
			public $lastRequest;
			public function sendRaw($request)
			{
				$this->lastRequest = $request;

				return [];
			}
		};
		$apiClient->messages = $messagesStub;
		$transport = new MandrillTransport($apiClient, []);
		$email = (new Email)
			->from('sender@example.com')
			->to('test@example.com')
			->subject('Test')
			->text('Body');
		$email->getHeaders()->addTextHeader('X-MC-ReturnPathDomain', 'return.example.com');
		$envelope = new Envelope($email->getFrom()[0], $email->getTo());
		$sentMessage = new SentMessage($email, $envelope);
		$this->invokeProtectedMethod($transport, 'doSend', [$sentMessage]);
		$this->assertEquals('return.example.com', $apiClient->messages->lastRequest['return_path_domain']);
	}

	public function test_do_send_with_response_missing_message_id_does_not_throw()
	{
		$apiClient = $this->createMock(ApiClient::class);
		$messagesStub = new class {
			public function sendRaw($request)
			{
				return [];
			}
		};
		$apiClient->messages = $messagesStub;
		$transport = new MandrillTransport($apiClient, []);
		$email = (new Email)->from('sender@example.com')->to('test@example.com')->subject('Test')->text('Body');
		$envelope = new Envelope($email->getFrom()[0], $email->getTo());
		$sentMessage = new SentMessage($email, $envelope);
		// Should not throw
		$this->invokeProtectedMethod($transport, 'doSend', [$sentMessage]);
		$messageId = $sentMessage->getOriginalMessage()->getHeaders()->get('X-Message-ID')->getBodyAsString() ?? null;
		$this->assertTrue($messageId === null || $messageId === '', 'Message ID should be null or empty string');
	}

	public function test_do_send_with_null_response_does_not_throw()
	{
		$apiClient = $this->createMock(ApiClient::class);
		$messagesStub = new class {
			public function sendRaw($request)
			{
				return null;
			}
		};
		$apiClient->messages = $messagesStub;
		$transport = new MandrillTransport($apiClient, []);
		$email = (new Email)->from('sender@example.com')->to('test@example.com')->subject('Test')->text('Body');
		$envelope = new Envelope($email->getFrom()[0], $email->getTo());
		$sentMessage = new SentMessage($email, $envelope);
		// Should not throw
		$this->invokeProtectedMethod($transport, 'doSend', [$sentMessage]);
		$messageId = $sentMessage->getOriginalMessage()->getHeaders()->get('X-Message-ID')->getBodyAsString() ?? null;
		$this->assertTrue($messageId === null || $messageId === '', 'Message ID should be null or empty string');
	}

	public function test_get_header_returns_value_if_header_set()
	{
		$apiClient = $this->createMock(ApiClient::class);
		$transport = new MandrillTransport($apiClient, []);
		$email = (new Email)->from('sender@example.com')->to('test@example.com')->subject('Test')->text('Body');
		$email->getHeaders()->addTextHeader('X-Test-Header', 'HeaderValue');
		$envelope = new Envelope($email->getFrom()[0], $email->getTo());
		$sentMessage = new SentMessage($email, $envelope);
		$result = $this->invokeMethod($transport, 'getHeader', [$sentMessage, 'X-Test-Header']);
		$this->assertSame('HeaderValue', $result);
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
