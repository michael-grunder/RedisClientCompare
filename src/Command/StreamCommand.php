<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

/**
 * Base helpers for stream-oriented commands.
 *
 * We keep keys and identifiers deterministic enough for repeat runs while still
 * introducing variability across generated commands.
 */
abstract class StreamCommand extends Command
{
    protected function randomStreamKey(): string
    {
        return $this->randomScalarKey();
    }

    protected function randomStreamId(): string
    {
        $ms = random_int(1_000_000_000, 9_000_000_000);
        $sequence = random_int(0, 1023);

        return sprintf('%d-%d', $ms, $sequence);
    }

    protected function randomStreamField(): string
    {
        return $this->randomAscii(3, 12);
    }

    protected function randomStreamValue(): string
    {
        return $this->randomString();
    }

    protected function randomStreamGroup(string $key): string
    {
        $suffix = substr(md5($key . random_int(0, PHP_INT_MAX)), 0, 8);

        return sprintf('grp:%s', $suffix);
    }

    protected function randomStreamConsumer(string $group): string
    {
        $suffix = substr(md5($group . random_int(0, PHP_INT_MAX)), 0, 6);

        return sprintf('c:%s', $suffix);
    }
}
