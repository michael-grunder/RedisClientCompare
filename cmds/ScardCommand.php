<?php

require_once __DIR__ . '/KeyCommand.php';

class ScardCommand extends KeyCommand
{
    protected const NAME = 'SCARD';
    protected const ATTRIBUTES = [
        'data_type' => 'set',
        'readonly' => true,
    ];
}
