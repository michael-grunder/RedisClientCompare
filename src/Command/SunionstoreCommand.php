<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class SunionstoreCommand extends Command
{
    protected const NAME = 'SUNIONSTORE';
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

    protected function generateClusterArguments(): array
    {
        $count = random_int(1, 5);
        $tag = $this->randomClusterSlotTag();
        $destination = $this->randomClusterKey($tag);
        $keys = $this->randomClusterKeySet($count, $tag, null, false);

        return array_merge([$destination], $keys);
    }
}
