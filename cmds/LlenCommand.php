<?php

require_once __DIR__ . '/KeyCommand.php';

class LlenCommand extends KeyCommand
{
    protected const NAME = 'LLEN';
    protected const ATTRIBUTES = [
        'data_type' => 'list',
        'readonly' => true,
    ];
}
