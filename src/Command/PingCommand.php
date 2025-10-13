<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class PingCommand extends Command
{
    protected const NAME = 'PING';
    protected const ATTRIBUTES = [
        'data_type' => 'generic',
        'readonly' => true,
    ];

    protected function generateArguments(): array
    {
        if (random_int(0, 1) === 0) {
            return [];
        }

        return [$this->randomString()];
    }

    protected function generateClusterArguments(): array
    {
        return [$this->randomAscii(1, 30)];
    }
}
