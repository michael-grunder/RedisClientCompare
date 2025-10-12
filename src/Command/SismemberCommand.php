<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class SismemberCommand extends KeyCommand
{
    protected const NAME = 'SISMEMBER';
    protected const ATTRIBUTES = [
        'data_type' => 'set',
        'readonly' => true,
    ];

    protected function generateArguments(): array
    {
        return [$this->randomKey(), $this->randomSetMember()];
    }
}
