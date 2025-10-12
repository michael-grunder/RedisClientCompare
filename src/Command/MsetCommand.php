<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class MsetCommand extends Command
{
    protected const NAME = 'MSET';
    protected const ATTRIBUTES = [
        'data_type' => 'string',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        $pairs = [];
        $count = random_int(1, 4);
        for ($i = 0; $i < $count; $i++) {
            $pairs[] = $this->randomScalarKey();
            $pairs[] = $this->randomValue();
        }

        return $pairs;
    }
}
