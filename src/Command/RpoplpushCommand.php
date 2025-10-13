<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class RpoplpushCommand extends KeyCommand
{
    protected const NAME = 'RPOPLPUSH';
    protected const ATTRIBUTES = [
        'data_type' => 'list',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        return [$this->randomKey(), $this->randomKey()];
    }

    protected function generateClusterArguments(): array
    {
        $tag = $this->randomClusterSlotTag();
        $source = $this->randomClusterKey($tag);
        $destination = $this->randomClusterKey($tag);

        if (random_int(0, 4) === 0) {
            $destination = $source;
        }

        return [$source, $destination];
    }
}
