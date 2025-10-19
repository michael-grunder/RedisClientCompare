<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class XaddCommand extends StreamCommand
{
    protected const NAME = 'XADD';
    protected const ATTRIBUTES = [
        'data_type' => 'stream',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        $key = $this->randomStreamKey();
        $id = $this->randomStreamId();
        $fieldCount = random_int(1, 3);

        $arguments = [$key, $id];

        for ($i = 0; $i < $fieldCount; $i++) {
            $arguments[] = $this->randomStreamField();
            $arguments[] = $this->randomStreamValue();
        }

        return $arguments;
    }
}
