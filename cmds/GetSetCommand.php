<?php

require_once __DIR__ . '/KeyValueCommand.php';

class GetSetCommand extends KeyValueCommand
{
    protected const NAME = 'GETSET';
    protected const ATTRIBUTES = [
        'data_type' => 'string',
        'readonly' => false,
    ];
}
