<?php

abstract class Command
{
    protected const NAME = '';
    protected const ATTRIBUTES = [];

    /**
     * @return array{readonly?:bool,data_type?:string}
     */
    public function getAttributes(): array
    {
        return static::ATTRIBUTES;
    }

    public function getName(): string
    {
        return static::NAME;
    }

    abstract protected function generateArguments(): array;

    /**
     * @return array<int, mixed>
     */
    public function buildCommand(): array
    {
        return array_merge([$this->getName()], $this->generateArguments());
    }

    protected function randomAscii(int $min, int $max): string
    {
        $len = random_int($min, $max);
        $chars = 'abcdefghijklmnopqrstuvwxyz0123456789_-:.';
        $s = '';
        $charsLen = strlen($chars) - 1;
        for ($i = 0; $i < $len; $i++) {
            $s .= $chars[random_int(0, $charsLen)];
        }
        return $s;
    }

    protected function randomUnicode(): string
    {
        $bank = [
            'αβγ',
            'Ångström',
            'naïve',
            'élan',
            'ßharp',
            '日本語',
            'русский',
            'مرحبا',
            '😀',
            '🔥',
            '🛰️',
        ];
        return $bank[random_int(0, count($bank) - 1)];
    }

    protected function randomNumericString(): string
    {
        switch (random_int(0, 6)) {
            case 0:
                return (string) random_int(-100000, 100000);
            case 1:
                return '0' . str_pad((string) random_int(0, 999999), 5, '0', STR_PAD_LEFT);
            case 2:
                return '-' . random_int(1, 99999);
            case 3:
                return random_int(1, 9999) . 'e' . random_int(1, 6);
            case 4:
                return random_int(1, 9999) . '.' . random_int(0, 9999);
            case 5:
                return random_int(1, 9999) . 'abcff';
            default:
                return '42abc' . random_int(0, 999);
        }
    }

    protected function randomString(): string
    {
        $pick = random_int(0, 6);
        if ($pick <= 2) {
            return $this->randomAscii(1, 30);
        }
        if ($pick === 3) {
            return $this->randomUnicode();
        }
        if ($pick === 4) {
            return $this->randomNumericString();
        }
        if ($pick === 5) {
            return ' ' . $this->randomAscii(1, 10) . ' ';
        }
        return random_int(0, 30) === 0 ? '' : $this->randomAscii(1, 5);
    }

    /**
     * @return int|float|string
     */
    protected function randomScalarKey()
    {
        $pick = random_int(0, 9);
        if ($pick <= 4) {
            return $this->randomString();
        }
        if ($pick <= 7) {
            return $this->randomInt();
        }
        return $this->randomFloat();
    }

    protected function randomScalarField()
    {
        return $this->randomScalarKey();
    }

    /**
     * @return int|float|string
     */
    protected function randomValue()
    {
        $pick = random_int(0, 9);
        if ($pick <= 5) {
            return $this->randomString();
        }
        if ($pick <= 7) {
            return $this->randomInt();
        }
        return $this->randomFloat();
    }

    protected function randomInt(): int
    {
        return random_int(-1_000_000, 1_000_000);
    }

    protected function randomFloat(): float
    {
        $v = random_int(-1_000_000, 1_000_000) / random_int(1, 10_000);
        if (random_int(0, 5) === 0) {
            $v *= pow(10, random_int(-6, 6));
        }
        return (float) $v;
    }
}
