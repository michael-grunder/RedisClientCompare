<?php

require_once __DIR__ . '/KeyValueCommand.php';

class SetCommand extends KeyValueCommand
{
    protected const NAME = 'SET';
    protected const ATTRIBUTES = [
        'data_type' => 'string',
        'readonly' => false,
    ];
}
