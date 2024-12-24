<?php

declare(strict_types=1);

namespace Typhoon\Amqp091;

use Typhoon\Amqp091\Exception\UriIsInvalid;

/**
 * @api
 * @see http://www.rabbitmq.com/uri-spec.html
 */
final class Uri
{
    private const DEFAULT_HOST = 'amqp';
    private const DEFAULT_PORT = 5672;
    private const DEFAULT_USERNAME = 'guest';
    private const DEFAULT_PASSWORD = 'guest';
    private const DEFAULT_VHOST = '/';

    public static function default(): self
    {
        return new self();
    }

    /**
     * @param non-empty-string $uri
     * @throws UriIsInvalid
     */
    public static function parse(string $uri): self
    {
        $components = parse_url($uri);

        if ($components === false) {
            throw new UriIsInvalid();
        }

        parse_str($components['query'] ?? '', $query);

        $certFile = null;
        if (isset($query['certfile']) && \is_string($query['certfile']) && $query['certfile'] !== '') {
            $certFile = $query['certfile'];
        }

        $keyFile = null;
        if (isset($query['keyfile']) && \is_string($query['keyfile']) && $query['keyfile'] !== '') {
            $keyFile = $query['keyfile'];
        }

        $cacertfile = null;
        if (isset($query['cacertfile']) && \is_string($query['cacertfile']) && $query['cacertfile'] !== '') {
            $cacertfile = $query['cacertfile'];
        }

        $serverName = null;
        if (isset($query['server_name_indication']) && \is_string($query['server_name_indication']) && $query['server_name_indication'] !== '') {
            $serverName = $query['server_name_indication'];
        }

        $authMechanism = null;
        if (isset($query['auth_mechanism']) && \is_string($query['auth_mechanism']) && $query['auth_mechanism'] !== '') {
            $authMechanism = $query['auth_mechanism'];
        }

        $heartbeat = null;
        if (isset($query['heartbeat']) && is_numeric($query['heartbeat'])) {
            $heartbeat = (int) $query['heartbeat'];
        }

        $connectionTimeout = null;
        if (isset($query['connection_timeout']) && is_numeric($query['connection_timeout'])) {
            $connectionTimeout = (int) $query['connection_timeout'];
        }

        $channelMax = null;
        if (isset($query['channel_max']) && is_numeric($query['channel_max'])) {
            $channelMax = (int) $query['channel_max'];
        }

        $host = self::DEFAULT_HOST;
        if (isset($components['host']) && $components['host'] !== '') {
            $host = $components['host'];
        }

        $port = self::DEFAULT_PORT;
        if (isset($components['port']) && $components['port'] > 0) {
            $port = $components['port'];
        }

        $vhost = self::DEFAULT_VHOST;
        if (isset($components['path']) && $components['path'] !== '') {
            $vhost = $components['path'];
        }

        return new self(
            scheme: Scheme::tryFrom($components['scheme'] ?? Scheme::amqp->value) ?: throw UriIsInvalid::invalidScheme($components['scheme'] ?? ''),
            host: $host,
            port: $port,
            username: $components['user'] ?? self::DEFAULT_USERNAME,
            password: $components['pass'] ?? self::DEFAULT_PASSWORD,
            vhost: $vhost,
            certFile: $certFile,
            keyFile: $keyFile,
            cacertFile: $cacertfile,
            serverName: $serverName,
            authMechanism: $authMechanism,
            heartbeat: $heartbeat,
            connectionTimeout: $connectionTimeout,
            channelMax: $channelMax,
        );
    }

    /**
     * @param non-empty-string $host
     * @param positive-int $port
     * @param non-empty-string $vhost
     */
    private function __construct(
        public readonly Scheme $scheme = Scheme::amqp,
        public readonly string $host = self::DEFAULT_HOST,
        public readonly int $port = self::DEFAULT_PORT,
        public readonly string $username = self::DEFAULT_USERNAME,
        public readonly string $password = self::DEFAULT_PASSWORD,
        public readonly string $vhost = self::DEFAULT_VHOST,
        public readonly ?string $certFile = null,
        public readonly ?string $keyFile = null,
        public readonly ?string $cacertFile = null,
        public readonly ?string $serverName = null,
        public readonly ?string $authMechanism = null,
        public readonly ?int $heartbeat = null,
        public readonly ?int $connectionTimeout = null,
        public readonly ?int $channelMax = null,
    ) {}
}
