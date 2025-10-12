<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Logger;

use Monolog\Logger;
use Monolog\Level;
use Psr\Log\LoggerInterface;

final class LoggerFactory
{
    public static function create(string $minimumLevel = 'info'): LoggerInterface
    {
        $minLevel = Logger::toMonologLevel($minimumLevel);
        $formatter = new ContextLineFormatter();

        $logger = new Logger('redis-client-compare');

        $stdoutMaxLevel = Level::Info;

        if ($minLevel->value <= $stdoutMaxLevel->value) {
            $stdoutHandler = new StreamLevelHandler(STDOUT, $minLevel, $stdoutMaxLevel);
            $stdoutHandler->setFormatter($formatter);
            $logger->pushHandler($stdoutHandler);
        }

        $stderrLevel = $minLevel->value > Level::Error->value ? $minLevel : Level::Error;
        $stderrHandler = new StreamLevelHandler(STDERR, $stderrLevel);
        $stderrHandler->setFormatter($formatter);

        $logger->pushHandler($stderrHandler);

        return $logger;
    }
}
