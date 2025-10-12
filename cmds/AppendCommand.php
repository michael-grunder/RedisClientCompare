<?php

require_once __DIR__ . '/KeyValueCommand.php';

class AppendCommand extends KeyValueCommand
{
    protected const NAME = 'APPEND';
    protected const ATTRIBUTES = [
        'data_type' => 'string',
        'readonly' => false,
    ];
}
