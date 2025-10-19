<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\Command;

class XreadgroupCommand extends StreamCommand
{
    protected const NAME = 'XREADGROUP';
    protected const ATTRIBUTES = [
        'data_type' => 'stream',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        $streamCount = 1;
        $keys = [];
        $ids = [];

        for ($i = 0; $i < $streamCount; $i++) {
            $key = $this->randomStreamKey();
            $keys[] = $key;
            $ids[] = $this->randomGroupReadId();
        }

        $group = $this->randomStreamGroup($keys[0]);
        $consumer = $this->randomStreamConsumer($group);

        $arguments = ['GROUP', $group, $consumer];

        if (random_int(0, 1) === 1) {
            $arguments[] = 'COUNT';
            $arguments[] = random_int(1, 50);
        }

        if (random_int(0, 1) === 1) {
            $arguments[] = 'NOACK';
        }

        $arguments[] = 'STREAMS';

        foreach ($keys as $key) {
            $arguments[] = $key;
        }

        foreach ($ids as $id) {
            $arguments[] = $id;
        }

        return $arguments;
    }

    private function randomGroupReadId(): string
    {
        return match (random_int(0, 2)) {
            0 => '>',
            1 => '0-0',
            default => $this->randomStreamId(),
        };
    }
}
