<?php

require_once __DIR__ . '/KeyCommand.php';

class GetCommand extends KeyCommand
{
    protected const NAME = 'GET';
    protected const ATTRIBUTES = [
        'data_type' => 'string',
        'readonly' => true,
    ];
}
