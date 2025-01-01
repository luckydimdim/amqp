<?php

declare(strict_types=1);

namespace Typhoon\Amqp091;

use Typhoon\Amqp091\Exception\UriIsInvalid;
use Typhoon\Amqp091\Internal\Protocol\Auth\Mechanism;

/**
 * @api
 */
final class Config
{
    private const DEFAULT_HOST = 'localhost';
    private const DEFAULT_PORT = 5672;
    private const DEFAULT_USERNAME = 'guest';
    private const DEFAULT_PASSWORD = 'guest';
    private const DEFAULT_VHOST = '/';
    private const DEFAULT_CONNECTION_TIMEOUT = 1000;
    private const DEFAULT_HEARTBEAT_INTERVAL = 60;
    private const MAX_CHANNEL = 0xFFFF;
    private const MAX_FRAME = 0xFFFF;

    public static function default(): self
    {
        return new self();
    }

    /**
     * @see https://www.rabbitmq.com/docs/uri-spec
     *
     * @param non-empty-string $uri
     * @throws UriIsInvalid
     */
    public static function fromURI(string $uri): self
    {
        $components = parse_url($uri);

        if ($components === false) {
            throw new UriIsInvalid();
        }

        $query = [];
        if (isset($components['query']) && $components['query'] !== '') {
            $query = Internal\parseQuery($components['query']);
        }

        $certFile = null;
        if (isset($query['certfile'])) {
            $certFile = \is_array($query['certfile']) ? $query['certfile'][0] : $query['certfile'];
        }

        $keyFile = null;
        if (isset($query['keyfile'])) {
            $keyFile = \is_array($query['keyfile']) ? $query['keyfile'][0] : $query['keyfile'];
        }

        $cacertfile = null;
        if (isset($query['cacertfile'])) {
            $cacertfile = \is_array($query['cacertfile']) ? $query['cacertfile'][0] : $query['cacertfile'];
        }

        $serverName = null;
        if (isset($query['server_name_indication'])) {
            $serverName = \is_array($query['server_name_indication']) ? $query['server_name_indication'][0] : $query['server_name_indication'];
        }

        $authMechanisms = [];
        if (isset($query['auth_mechanism'])) {
            $authMechanisms = \is_string($query['auth_mechanism']) ? [$query['auth_mechanism']] : $query['auth_mechanism'];
        }

        $heartbeat = self::DEFAULT_HEARTBEAT_INTERVAL;
        if (isset($query['heartbeat']) && is_numeric($query['heartbeat']) && (int) $query['heartbeat'] >= 0) {
            /** @var non-negative-int $heartbeat */
            $heartbeat = (int) $query['heartbeat'];
        }

        $connectionTimeout = self::DEFAULT_CONNECTION_TIMEOUT;
        if (isset($query['connection_timeout']) && is_numeric($query['connection_timeout']) && (int) $query['connection_timeout'] > 0) {
            /** @var positive-int $connectionTimeout */
            $connectionTimeout = (int) $query['connection_timeout'];
        }

        $channelMax = self::MAX_CHANNEL;
        if (isset($query['channel_max']) && is_numeric($query['channel_max']) && (int) $query['channel_max'] > 0) {
            /** @var int<0, 65535> $channelMax */
            $channelMax = min($channelMax, (int) $query['channel_max']);
        }

        $frameMax = self::MAX_FRAME;
        if (isset($query['frame_max']) && is_numeric($query['frame_max']) && (int) $query['frame_max'] > 0) {
            /** @var int<0, 65535> $frameMax */
            $frameMax = min($frameMax, (int) $query['frame_max']);
        }

        $tcpNoDelay = false;
        if (isset($query['tcp_nodelay']) && ($nodelay = filter_var($query['tcp_nodelay'], FILTER_VALIDATE_BOOL))) {
            $tcpNoDelay = $nodelay;
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

        $username = self::DEFAULT_USERNAME;
        if (isset($components['user']) && $components['user'] !== '') {
            $username = $components['user'];
        }

        $password = self::DEFAULT_PASSWORD;
        if (isset($components['pass']) && $components['pass'] !== '') {
            $password = $components['pass'];
        }

        return new self(
            scheme: Scheme::tryFrom($components['scheme'] ?? Scheme::amqp->value) ?: throw UriIsInvalid::invalidScheme($components['scheme'] ?? ''),
            host: $host,
            port: $port,
            username: $username,
            password: $password,
            vhost: $vhost,
            certFile: $certFile,
            keyFile: $keyFile,
            cacertFile: $cacertfile,
            serverName: $serverName,
            authMechanisms: $authMechanisms,
            heartbeat: $heartbeat,
            connectionTimeout: $connectionTimeout,
            channelMax: $channelMax,
            frameMax: $frameMax,
            sasl: array_map(static fn(string $mechanism): Mechanism => Mechanism::create($mechanism, $username, $password), $authMechanisms),
            tcpNoDelay: $tcpNoDelay,
        );
    }

    /**
     * @return non-empty-string
     */
    public function connectionDsn(): string
    {
        return \sprintf('tcp://%s:%d', $this->host, $this->port);
    }

    /**
     * @param non-empty-string $host
     * @param positive-int $port
     * @param non-empty-string $vhost
     * @param list<non-empty-string> $authMechanisms
     * @param non-negative-int $heartbeat
     * @param positive-int $connectionTimeout
     * @param int<0, 65535> $channelMax
     * @param int<0, 65535> $frameMax
     * @param list<Mechanism> $sasl
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
        public readonly array $authMechanisms = [],
        public readonly int $heartbeat = self::DEFAULT_HEARTBEAT_INTERVAL,
        public readonly int $connectionTimeout = self::DEFAULT_CONNECTION_TIMEOUT,
        public readonly int $channelMax = self::MAX_CHANNEL,
        public readonly int $frameMax = self::MAX_FRAME,
        public readonly array $sasl = [],
        public readonly bool $tcpNoDelay = false,
    ) {}
}
