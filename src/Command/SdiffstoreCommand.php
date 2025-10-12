<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class SdiffstoreCommand extends Command
{
    protected const NAME = 'SDIFFSTORE';
    protected const ATTRIBUTES = [
        'data_type' => 'set',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        $destination = $this->randomScalarKey();
        $keys = [];
        $count = random_int(1, 5);

        for ($i = 0; $i < $count; $i++) {
            $keys[] = $this->randomScalarKey();
        }

        return array_merge([$destination], $keys);
    }
}

