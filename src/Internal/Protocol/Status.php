<?php

declare(strict_types=1);

namespace Thesis\Amqp\Internal\Protocol;

/**
 * @internal
 */
enum Status: int
{
    case REPLY_SUCCESS = 200;
    case CONTENT_TOO_LARGE = 311;
    case NO_ROUTE = 312;
    case NO_CONSUMERS = 313;
    case CONNECTION_FORCED = 320;
    case INVALID_PATH = 402;
    case ACCESS_REFUSED = 403;
    case NOT_FOUND = 404;
    case RESOURCE_LOCKED = 405;
    case PRECONDITION_FAILED = 406;
    case FRAME_ERROR = 501;
    case SYNTAX_ERROR = 502;
    case COMMAND_INVALID = 503;
    case CHANNEL_ERROR = 504;
    case UNEXPECTED_FRAME = 505;
    case RESOURCE_ERROR = 506;
    case NOT_ALLOWED = 530;
    case NOT_IMPLEMENTED = 540;
    case INTERNAL_ERROR = 541;
}
