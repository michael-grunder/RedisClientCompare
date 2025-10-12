<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class LtrimCommand extends KeyCommand
{
    protected const NAME = 'LTRIM';
    protected const ATTRIBUTES = [
        'data_type' => 'list',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        $start = random_int(0, 5);
        $stop = $start + random_int(0, 10);

        return [$this->randomKey(), $start, $stop];
    }
}
