<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Logger;

use DateTimeInterface;
use Monolog\Formatter\LineFormatter;
use Monolog\LogRecord;

/**
 * Formats log lines to match the project's compact key=value style.
 */
final class ContextLineFormatter extends LineFormatter
{
    public function __construct()
    {
        parent::__construct();
        $this->dateFormat = 'Y-m-d H:i:s';
    }

    public function format(LogRecord $record): string
    {
        $timestamp = $this->formatDate($record->datetime);
        $levelName = $record->level->getName();
        $message = $record->message;
        $context = $this->formatContext($record->context);

        $line = sprintf('[%s] %-5s %s', $timestamp, $levelName, $message);

        if ($context !== '') {
            $line .= ' ' . $context;
        }

        return $line;
    }

    protected function formatDate(DateTimeInterface $date): string
    {
        $format = $this->dateFormat ?? 'Y-m-d H:i:s';

        return $date->format($format);
    }

    /**
     * @param array<string,mixed> $context
     */
    private function formatContext(array $context): string
    {
        if ($context === []) {
            return '';
        }

        ksort($context);

        $parts = [];
        foreach ($context as $key => $value) {
            $parts[] = sprintf('%s=%s', $key, $this->formatValue($value));
        }

        return implode(' ', $parts);
    }

    private function formatValue(mixed $value): string
    {
        if (is_float($value)) {
            return rtrim(rtrim(sprintf('%.4f', $value), '0'), '.');
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if ($value === null) {
            return 'null';
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return $this->stringify($value);
    }
}
