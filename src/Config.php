<?php

declare(strict_types=1);

namespace Thesis\Amqp;

use Thesis\Amqp\Exception\UriIsInvalid;
use Thesis\Amqp\Internal\Protocol\Auth\Mechanism;

/**
 * @api
 * @phpstan-import-type AmqpScheme from Scheme
 */
final readonly class Config
{
    private const string DEFAULT_URL = 'localhost:5672';
    private const string DEFAULT_HOST = 'localhost';
    private const int DEFAULT_PORT = 5672;
    private const string DEFAULT_USERNAME = 'guest';
    private const string DEFAULT_PASSWORD = 'guest';
    private const string DEFAULT_VHOST = '/';
    private const int DEFAULT_CONNECTION_TIMEOUT = 10;
    private const int DEFAULT_HEARTBEAT_INTERVAL = 60;
    private const int MAX_CHANNEL = 0xFFFF;
    private const int MAX_FRAME = 0xFFFF;

    /**
     * @internal
     * @var non-empty-list<Mechanism>
     */
    public array $sasl;

    /**
     * @param non-empty-list<non-empty-string> $urls
     * @param non-empty-string $vhost
     * @param list<non-empty-string> $authMechanisms
     * @param non-negative-int $heartbeat
     * @param float $connectionTimeout in seconds
     * @param int<0, 65535> $channelMax
     * @param int<0, 65535> $frameMax
     */
    public function __construct(
        public Scheme $scheme = Scheme::amqp,
        public array $urls = [self::DEFAULT_URL],
        #[\SensitiveParameter]
        public string $user = self::DEFAULT_USERNAME,
        #[\SensitiveParameter]
        public string $password = self::DEFAULT_PASSWORD,
        public string $vhost = self::DEFAULT_VHOST,
        public ?string $certFile = null,
        public ?string $keyFile = null,
        public ?string $cacertFile = null,
        public ?string $serverName = null,
        public array $authMechanisms = [],
        public int $heartbeat = self::DEFAULT_HEARTBEAT_INTERVAL,
        public float $connectionTimeout = self::DEFAULT_CONNECTION_TIMEOUT,
        public int $channelMax = self::MAX_CHANNEL,
        public int $frameMax = self::MAX_FRAME,
        public bool $tcpNoDelay = true,
        public bool $verifyPeer = true,
        public bool $verifyPeerName = true,
    ) {
        $authMechanisms = $this->authMechanisms;
        if (\count($authMechanisms) === 0) {
            $authMechanisms[] = Mechanism::PLAIN;
        }

        $this->sasl = array_map(fn(string $mechanism): Mechanism => Mechanism::create($mechanism, $this->user, $this->password), $authMechanisms);
    }

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
    public static function fromURI(#[\SensitiveParameter] string $uri): self
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

        $tcpNoDelay = true;
        if (isset($query['tcp_nodelay'])) {
            $tcpNoDelay = filter_var($query['tcp_nodelay'], FILTER_VALIDATE_BOOL);
        }

        $verifyPeer = true;
        if (isset($query['verify_peer'])) {
            $verifyPeer = filter_var($query['verify_peer'], FILTER_VALIDATE_BOOL);
        }

        $verifyPeerName = true;
        if (isset($query['verify_peer_name'])) {
            $verifyPeerName = filter_var($query['verify_peer_name'], FILTER_VALIDATE_BOOL);
        }

        $port = self::DEFAULT_PORT;
        if (isset($components['port']) && $components['port'] > 0) {
            $port = $components['port'];
        }

        $urls = [];
        foreach (explode(',', $components['host'] ?? '') as $host) {
            $hostport = explode(':', $host);
            $urls[] = \sprintf('%s:%d', $hostport[0] ?: self::DEFAULT_HOST, (int) ($hostport[1] ?? $port));
        }

        $vhost = self::DEFAULT_VHOST;
        if (isset($components['path'])) {
            $vhost = ltrim($components['path'], '/') ?: self::DEFAULT_VHOST;
        }

        $user = self::DEFAULT_USERNAME;
        if (isset($components['user']) && $components['user'] !== '') {
            $user = $components['user'];
        }

        $password = self::DEFAULT_PASSWORD;
        if (isset($components['pass']) && $components['pass'] !== '') {
            $password = $components['pass'];
        }

        return new self(
            scheme: Scheme::tryFrom($components['scheme'] ?? Scheme::amqp->value) ?: throw UriIsInvalid::invalidScheme($components['scheme'] ?? ''),
            urls: $urls,
            user: $user,
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
            tcpNoDelay: $tcpNoDelay,
            verifyPeer: $verifyPeer,
            verifyPeerName: $verifyPeerName,
        );
    }

    /**
     * @param array{
     *     scheme?: AmqpScheme,
     *     urls?: non-empty-list<non-empty-string>,
     *     user?: non-empty-string,
     *     password?: non-empty-string,
     *     vhost?: non-empty-string,
     *     certfile?: non-empty-string,
     *     keyfile?: non-empty-string,
     *     cacertfile?: non-empty-string,
     *     server_name?: ?non-empty-string,
     *     auth_mechanisms?: list<non-empty-string>,
     *     heartbeat?: non-negative-int,
     *     connection_timeout?: positive-int,
     *     channel_max?: int<0, 65535>,
     *     frame_max?: int<0, 65535>,
     *     tcp_nodelay?: bool,
     *     verify_peer?: bool,
     *     verify_peer_name?: bool,
     * } $options
     */
    public static function fromArray(#[\SensitiveParameter] array $options): self
    {
        return new self(
            scheme: isset($options['scheme']) ? Scheme::parse($options['scheme']) : Scheme::amqp,
            urls: $options['urls'] ?? [self::DEFAULT_URL],
            user: $options['user'] ?? self::DEFAULT_USERNAME,
            password: $options['password'] ?? self::DEFAULT_PASSWORD,
            vhost: $options['vhost'] ?? self::DEFAULT_VHOST,
            certFile: $options['certfile'] ?? null,
            keyFile: $options['keyfile'] ?? null,
            cacertFile: $options['cacertfile'] ?? null,
            serverName: $options['server_name'] ?? null,
            authMechanisms: $options['auth_mechanisms'] ?? [],
            heartbeat: $options['heartbeat'] ?? self::DEFAULT_HEARTBEAT_INTERVAL,
            connectionTimeout: $options['connection_timeout'] ?? self::DEFAULT_CONNECTION_TIMEOUT,
            channelMax: $options['channel_max'] ?? self::MAX_CHANNEL,
            frameMax: $options['frame_max'] ?? self::MAX_FRAME,
            tcpNoDelay: $options['tcp_nodelay'] ?? true,
            verifyPeer: $options['verify_peer'] ?? true,
            verifyPeerName: $options['verify_peer_name'] ?? true,
        );
    }

    /**
     * @internal
     * @return non-negative-int
     */
    public function heartbeat(int $suggestHeartbeat): int
    {
        $heartbeat = min($this->heartbeat, $suggestHeartbeat);
        \assert($heartbeat >= 0, 'heartbeat must not be negative.');

        return $heartbeat;
    }

    /**
     * @internal
     * @return non-negative-int
     */
    public function channelMax(int $suggestChannelMax): int
    {
        $channelMax = min($this->channelMax, $suggestChannelMax);
        \assert($channelMax >= 0, 'channel max must not be negative.');

        return $channelMax;
    }

    /**
     * @internal
     * @return positive-int
     */
    public function frameMax(int $suggestFrameMax): int
    {
        $frameMax = min($this->frameMax, $suggestFrameMax);
        \assert($frameMax > 0, 'frame max must be positive.');

        return $frameMax;
    }

    /**
     * @internal
     * @return iterable<non-empty-string>
     */
    public function connectionUrls(): iterable
    {
        foreach ($this->urls as $url) {
            if (!str_starts_with($url, 'tcp://')) {
                $url = "tcp://{$url}";
            }

            yield $url;
        }
    }

    /**
     * @deprecated since 1.0.2, use {@see Config::$sasl} property
     * @return non-empty-list<Mechanism>
     */
    public function sasl(): array
    {
        return $this->sasl;
    }
}
