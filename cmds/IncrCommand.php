<?php

require_once __DIR__ . '/KeyCommand.php';

class IncrCommand extends KeyCommand
{
    protected const NAME = 'INCR';
    protected const ATTRIBUTES = [
        'data_type' => 'string',
        'readonly' => false,
    ];
}
