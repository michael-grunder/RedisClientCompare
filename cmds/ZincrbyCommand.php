<?php

require_once __DIR__ . '/KeyCommand.php';

class ZincrbyCommand extends KeyCommand
{
    protected const NAME = 'ZINCRBY';
    protected const ATTRIBUTES = [
        'data_type' => 'zset',
        'readonly' => false,
    ];

    protected function generateArguments(): array
    {
        $delta = $this->randomFloat();
        if ($delta == 0.0) {
            $delta = 0.5;
        }

        return [$this->randomKey(), $delta, $this->randomValue()];
    }
}
