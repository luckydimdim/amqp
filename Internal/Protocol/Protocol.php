<?php

declare(strict_types=1);

namespace Typhoon\Amqp091\Internal\Protocol;

use Typhoon\Amqp091\Exception\UnsupportedClassMethod;
use Typhoon\Amqp091\Internal\Io;

/**
 * @internal
 * @psalm-internal Typhoon\Amqp091
 */
enum Protocol
{
    case amqp091;

    /** @var non-negative-int */
    public const FRAME_END = 206;

    /** @var array<ClassType::*, array<ClassMethod::*, class-string<Frame>>> */
    private const METHODS = [
        ClassType::CONNECTION => [
            ClassMethod::CONNECTION_START => Frame\ConnectionStart::class,
            ClassMethod::CONNECTION_TUNE => Frame\ConnectionTune::class,
            ClassMethod::CONNECTION_OPEN_OK => Frame\ConnectionOpenOk::class,
            ClassMethod::CONNECTION_CLOSE => Frame\ConnectionClose::class,
            ClassMethod::CONNECTION_CLOSE_OK => Frame\ConnectionCloseOk::class,
        ],
        ClassType::CHANNEL => [
            ClassMethod::CHANNEL_OPEN_OK => Frame\ChannelOpenOkFrame::class,
            ClassMethod::CHANNEL_CLOSE => Frame\ChannelClose::class,
            ClassMethod::CHANNEL_CLOSE_OK => Frame\ChannelCloseOk::class,
        ],
        ClassType::EXCHANGE => [
            ClassMethod::EXCHANGE_DECLARE_OK => Frame\ExchangeDeclareOk::class,
            ClassMethod::EXCHANGE_BIND_OK => Frame\ExchangeBindOk::class,
            ClassMethod::EXCHANGE_UNBIND_OK => Frame\ExchangeUnbindOk::class,
            ClassMethod::EXCHANGE_DELETE_OK => Frame\ExchangeDeleteOk::class,
        ],
        ClassType::QUEUE => [
            ClassMethod::QUEUE_DECLARE_OK => Frame\QueueDeclareOk::class,
            ClassMethod::QUEUE_BIND_OK => Frame\QueueBindOk::class,
            ClassMethod::QUEUE_UNBIND_OK => Frame\QueueUnbindOk::class,
            ClassMethod::QUEUE_PURGE_OK => Frame\QueuePurgeOk::class,
            ClassMethod::QUEUE_DELETE_OK => Frame\QueueDeleteOk::class,
        ],
        ClassType::TX => [
            ClassMethod::TX_SELECT_OK => Frame\TxSelectOk::class,
            ClassMethod::TX_COMMIT_OK => Frame\TxCommitOk::class,
            ClassMethod::TX_ROLLBACK_OK => Frame\TxRollbackOk::class,
        ],
        ClassType::CONFIRM => [
            ClassMethod::CONFIRM_SELECT_OK => Frame\ConfirmSelectOk::class,
        ],
    ];

    /**
     * @param non-negative-int $channelId
     */
    public function parseMethod(Io\ReadBytes $reader, int $channelId): Request
    {
        $classId = $reader->readUint16();
        $methodId = $reader->readUint16();

        return new Request(
            $channelId,
            (self::METHODS[$classId][$methodId] ?? throw UnsupportedClassMethod::forClassMethod($classId, $methodId))::read($reader),
        );
    }
}
