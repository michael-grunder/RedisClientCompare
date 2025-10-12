<?php

require_once __DIR__ . '/KeyCommand.php';

class DecrCommand extends KeyCommand
{
    protected const NAME = 'DECR';
    protected const ATTRIBUTES = [
        'data_type' => 'string',
        'readonly' => false,
    ];
}
