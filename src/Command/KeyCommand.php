<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

abstract class KeyCommand extends Command
{
    protected function generateArguments(): array
    {
        return [$this->randomScalarKey()];
    }

    protected function randomKey()
    {
        return $this->randomScalarKey();
    }

    protected function randomField()
    {
        return $this->randomScalarField();
    }
}
