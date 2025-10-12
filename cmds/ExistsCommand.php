<?php

require_once __DIR__ . '/KeyCommand.php';

class ExistsCommand extends KeyCommand
{
    protected const NAME = 'EXISTS';
    protected const ATTRIBUTES = [
        'data_type' => 'generic',
        'readonly' => true,
    ];
}
