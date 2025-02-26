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

use Symfony\Component\Notifier\Exception\UnsupportedSchemeException;
use Symfony\Component\Notifier\Transport\AbstractTransportFactory;
use Symfony\Component\Notifier\Transport\Dsn;
use Symfony\Component\Notifier\Transport\TransportInterface;

/**
 * @author Dmitry Pigin <me.dotty@gmail.com>
 */
final class MicrosoftTeamsHookTransportFactory extends AbstractTransportFactory
{
    public function create(Dsn $dsn): TransportInterface
    {
        $scheme = $dsn->getScheme();
        $host = $dsn->getHost();
        $webhookPath = $dsn->getPath();

        if ('teams' === $scheme) {
            return (new MicrosoftTeamsHookTransport($webhookPath, $this->client, $this->dispatcher))->setHost($host);
        }

        throw new UnsupportedSchemeException($dsn, 'teams', $this->getSupportedSchemes());
    }

    protected function getSupportedSchemes(): array
    {
        return ['teams'];
    }
}
