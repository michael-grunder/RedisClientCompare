<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class EchoCommand extends Command
{
    protected const NAME = 'ECHO';
    protected const ATTRIBUTES = [
        'data_type' => 'generic',
        'readonly' => true,
    ];

    protected function generateArguments(): array
    {
        return [$this->randomString()];
    }

    protected function generateClusterArguments(): array
    {
        return [$this->randomAscii(1, 30), $this->randomString()];
    }
}
