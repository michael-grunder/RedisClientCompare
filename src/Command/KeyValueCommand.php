<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

abstract class KeyValueCommand extends KeyCommand
{
    protected function generateArguments(): array
    {
        return [$this->randomKey(), $this->randomValue()];
    }
}
