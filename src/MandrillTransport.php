<?php

namespace SpaanProductions\LaravelMandrill;

use Illuminate\Support\Facades\Log;
use MailchimpTransactional\ApiClient;
use Symfony\Component\Mailer\SentMessage;
use GuzzleHttp\Exception\RequestException;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use SpaanProductions\LaravelMandrill\Exceptions\MandrillTransportException;

class MandrillTransport extends AbstractTransport
{
    /**
     * Create a new Mandrill transport instance.
     *
     * @param ApiClient $mailchimp
     * @param array $config
     */
    public function __construct(protected ApiClient $mailchimp, protected array $config)
    {
        $logger = data_get($this->config, 'logger') ? Log::channel(data_get($this->config, 'logger')) : null;
        parent::__construct( logger: $logger);
    }

    /**
     * {@inheritDoc}
     * @throws MandrillTransportException
     * @docs https://mailchimp.com/developer/transactional/api/messages/send-mime-document/
     */
    protected function doSend(SentMessage $message): void
    {
        $this->getLogger()->debug(sprintf('Email transport "%s" starting', __CLASS__));

        $message = $this->setHeaders($message);

        $response = $this->mailchimp->messages->sendRaw([
            'raw_message' => $message->toString(),
            'async' => true,
        ]);

        if ($response instanceof RequestException) {
            throw new MandrillTransportException($response);
        }

        $messageId = data_get($response, '0._id');
        $message->getOriginalMessage()->getHeaders()->addHeader('X-Message-ID', ($messageId ?? ''));

        $this->getLogger()->debug('Response: ' . json_encode($response));
        $this->getLogger()->debug(sprintf('Email transport "%s" finished', __CLASS__));
    }

    /**
     * Set headers of email.
     *
     * @param SentMessage $message
     *
     * @return SentMessage
     */
    protected function setHeaders(SentMessage $message): SentMessage
    {
        $messageHeaders = $message->getOriginalMessage()->getHeaders();

        foreach (data_get($this->config, 'headers', []) as $name => $value) {
            $messageHeaders->addTextHeader($name, $value);
        }

        return $message;
    }

    /**
     * Get the string representation of the transport.
     *
     * @return string
     */
    public function __toString(): string
    {
        return 'mandrill';
    }
}
