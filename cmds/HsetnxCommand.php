<?php

require_once __DIR__ . '/KeyFieldValueCommand.php';

class HsetnxCommand extends KeyFieldValueCommand
{
    protected const NAME = 'HSETNX';
    protected const ATTRIBUTES = [
        'data_type' => 'hash',
        'readonly' => false,
    ];
}
