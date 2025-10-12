<?php

require_once __DIR__ . '/KeyCommand.php';

class RpopCommand extends KeyCommand
{
    protected const NAME = 'RPOP';
    protected const ATTRIBUTES = [
        'data_type' => 'list',
        'readonly' => false,
    ];
}
