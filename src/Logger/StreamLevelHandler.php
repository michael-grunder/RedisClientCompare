<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Logger;

use InvalidArgumentException;
use Monolog\Handler\AbstractProcessingHandler;
use Monolog\Level;
use Monolog\Logger;
use Monolog\LogRecord;

/**
 * Stream handler that limits records to a bounded level range.
 */
final class StreamLevelHandler extends AbstractProcessingHandler
{
    /**
     * @var resource
     */
    private $stream;

    private ?Level $maxLevel;

    /**
     * @param resource $stream
     */
    public function __construct($stream, int|string|Level $level = Logger::DEBUG, int|string|Level|null $maxLevel = null, bool $bubble = true)
    {
        if (!is_resource($stream)) {
            throw new InvalidArgumentException('Stream must be an open resource.');
        }

        parent::__construct($level, $bubble);

        $this->stream = $stream;
        $this->maxLevel = $maxLevel !== null ? Logger::toMonologLevel($maxLevel) : null;
    }

    public function isHandling(LogRecord $record): bool
    {
        if (!parent::isHandling($record)) {
            return false;
        }

        if ($this->maxLevel !== null && $record->level->value > $this->maxLevel->value) {
            return false;
        }

        return true;
    }

    protected function write(LogRecord $record): void
    {
        $line = (string) $record->formatted;

        if ($line === '') {
            return;
        }

        if (!self::endsWith($line, PHP_EOL)) {
            $line .= PHP_EOL;
        }

        fwrite($this->stream, $line);
    }

    private static function endsWith(string $haystack, string $needle): bool
    {
        if ($needle === '') {
            return true;
        }

        return substr($haystack, -strlen($needle)) === $needle;
    }
}
