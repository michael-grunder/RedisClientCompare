<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

abstract class KeyFieldCommand extends KeyCommand
{
    protected function generateArguments(): array
    {
        return [$this->randomKey(), $this->randomField()];
    }
}
