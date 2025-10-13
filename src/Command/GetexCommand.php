<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class GetexCommand extends KeyCommand
{
    protected const NAME = 'GETEX';
    protected const ATTRIBUTES = [
        'data_type' => 'string',
        'readonly' => false,
        'interacts_with_expiration' => true,
    ];

    protected function generateArguments(): array
    {
        $arguments = [$this->randomKey()];

        switch (random_int(0, 5)) {
            case 0:
                break;
            case 1:
                $arguments[] = 'EX';
                $arguments[] = random_int(1, 86_400);
                break;
            case 2:
                $arguments[] = 'PX';
                $arguments[] = random_int(1, 86_400_000);
                break;
            case 3:
                $arguments[] = 'EXAT';
                $arguments[] = time() + random_int(1, 86_400);
                break;
            case 4:
                $arguments[] = 'PXAT';
                $arguments[] = (time() + random_int(1, 86_400)) * 1_000 + random_int(0, 999);
                break;
            default:
                $arguments[] = 'PERSIST';
                break;
        }

        return $arguments;
    }
}
