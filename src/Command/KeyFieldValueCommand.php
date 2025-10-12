<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

abstract class KeyFieldValueCommand extends KeyFieldCommand
{
    protected function generateArguments(): array
    {
        return [$this->randomKey(), $this->randomField(), $this->randomValue()];
    }
}
