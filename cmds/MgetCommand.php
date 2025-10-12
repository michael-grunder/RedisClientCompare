<?php

require_once __DIR__ . '/Command.php';

class MgetCommand extends Command
{
    protected const NAME = 'MGET';
    protected const ATTRIBUTES = [
        'data_type' => 'string',
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
