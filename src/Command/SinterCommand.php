<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class SinterCommand extends Command
{
    protected const NAME = 'SINTER';
    protected const ATTRIBUTES = [
        'data_type' => 'set',
        'readonly' => true,
    ];

    protected function generateArguments(): array
    {
        $keys = [];
        $count = random_int(1, 5);

        for ($i = 0; $i < $count; $i++) {
            $keys[] = $this->randomScalarKey();
        }

        return $keys;
    }
}

