<?php

require_once __DIR__ . '/KeyCommand.php';

abstract class KeyFieldCommand extends KeyCommand
{
    protected function generateArguments(): array
    {
        return [$this->randomKey(), $this->randomField()];
    }
}
