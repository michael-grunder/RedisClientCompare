<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class HincrbyfloatCommand extends KeyFieldCommand
{
    protected const NAME = 'HINCRBYFLOAT';
    protected const ATTRIBUTES = [
        'data_type' => 'hash',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        $delta = $this->randomFloat();
        if ($delta == 0.0) {
            $delta = 0.5;
        }

        return [$this->randomKey(), $this->randomField(), $delta];
    }
}
