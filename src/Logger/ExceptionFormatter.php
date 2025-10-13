<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Logger;

use Throwable;

final class ExceptionFormatter
{
    public static function format(Throwable $throwable): string
    {
        $sections = [];
        $current = $throwable;
        $depth = 0;

        while ($current !== null) {
            $header = $depth === 0
                ? sprintf('%s: %s', $current::class, trim($current->getMessage()))
                : sprintf('Caused by %s: %s', $current::class, trim($current->getMessage()));

            $sections[] = $header;
            $sections[] = self::formatTrace($current);

            $current = $current->getPrevious();
            $depth++;
        }

        return implode(PHP_EOL, $sections);
    }

    private static function formatTrace(Throwable $throwable): string
    {
        $trace = $throwable->getTrace();
        $lines = [];
        $index = 0;

        $lines[] = sprintf(
            '#%d %s(%s)',
            $index,
            self::formatFile($throwable->getFile()),
            self::formatLine($throwable->getLine())
        );
        $index++;

        foreach ($trace as $frame) {
            $lines[] = sprintf(
                '#%d %s(%s): %s',
                $index,
                self::formatFile($frame['file'] ?? null),
                self::formatLine($frame['line'] ?? null),
                self::formatFrameCallable($frame)
            );

            $index++;
        }

        return implode(PHP_EOL, $lines);
    }

    private static function formatFile(?string $file): string
    {
        if ($file === null || $file === '') {
            return '[internal]';
        }

        return $file;
    }

    private static function formatLine(?int $line): string
    {
        if ($line === null || $line <= 0) {
            return '?';
        }

        return (string) $line;
    }

    /**
     * @param array<string, mixed> $frame
     */
    private static function formatFrameCallable(array $frame): string
    {
        $function = $frame['function'] ?? '{closure}';

        if (isset($frame['class'])) {
            $type = $frame['type'] ?? '::';

            return sprintf('%s%s%s', $frame['class'], $type, $function);
        }

        return $function;
    }
}

