<?php

require_once __DIR__ . '/KeyCommand.php';

class LpopCommand extends KeyCommand
{
    protected const NAME = 'LPOP';
    protected const ATTRIBUTES = [
        'data_type' => 'list',
        'readonly' => false,
    ];
}
