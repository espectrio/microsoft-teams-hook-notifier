<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Espectrio\MicrosoftTeamsHookNotifier;

use Symfony\Component\Notifier\Exception\LogicException;
use Symfony\Component\Notifier\Exception\TransportException;
use Symfony\Component\Notifier\Message\ChatMessage;
use Symfony\Component\Notifier\Message\MessageInterface;
use Symfony\Component\Notifier\Transport\AbstractTransport;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * @author Dmitry Pigin <me.dotty@gmail.com>
 *
 * @internal
 */
final class MicrosoftTeamsHookTransport extends AbstractTransport
{
    protected const HOST = 'outlook.office.com';

    public function __construct(string $webhookPath = null, HttpClientInterface $client = null, EventDispatcherInterface $dispatcher = null)
    {
        $this->webhookPath = $webhookPath;
        $this->client = $client;

        parent::__construct($client, $dispatcher);
    }

    public function __toString(): string
    {
        return sprintf('teams://%s/%s', $this->getEndpoint(), $this->webhookPath);
    }

    public function supports(MessageInterface $message): bool
    {
        return $message instanceof ChatMessage && (null === $message->getOptions() || $message->getOptions() instanceof MicrosoftTeamsHookOptions);
    }

    /**
     * @see https://api.slack.com/methods/chat.postMessage
     */
    protected function doSend(MessageInterface $message): void
    {
        if (!$message instanceof ChatMessage) {
            throw new LogicException(sprintf('The "%s" transport only supports instances of "%s" (instance of "%s" given).', __CLASS__, ChatMessage::class, \get_class($message)));
        }
        if ($message->getOptions() && !$message->getOptions() instanceof MicrosoftTeamsHookOptions) {
            throw new LogicException(sprintf('The "%s" transport only supports instances of "%s" for options.', __CLASS__, MicrosoftTeamsHookOptions::class));
        }

        if (!($opts = $message->getOptions()) && $notification = $message->getNotification()) {
            $opts = MicrosoftTeamsHookOptions::fromNotification($notification);
        }

        $options = $opts ? $opts->toArray() : [];
        $options['token'] = $this->accessToken;
        if (!isset($options['channel'])) {
            $options['channel'] = $message->getRecipientId() ?: $this->chatChannel;
        }
        $options['text'] = $message->getSubject();
        $response = $this->client->request('POST', 'https://'.$this->getEndpoint().$this->webhookPath, [
            'body' => array_filter($options),
        ]);

        if (200 !== $response->getStatusCode()) {
            throw new TransportException(sprintf('Unable to post the Teams message: %s.', $response->getContent(false)), $response);
        }

        $result = $response->toArray(false);
        if (!$result['ok']) {
            throw new TransportException(sprintf('Unable to post the Teams message: %s.', $result['error']), $response);
        }
    }
}
