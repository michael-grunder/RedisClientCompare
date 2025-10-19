<?php

declare(strict_types=1);

namespace Michaelgrunder\RedisClientCompare\PhpRedis;

use Redis;

final class Capabilities
{
    /**
     * @return list<int>
     */
    public static function availableCompressors(): array
    {
        $result = [];

        if (\defined('Redis::COMPRESSION_NONE')) {
            $result[] = (int) Redis::COMPRESSION_NONE;
        }

        if (\defined('Redis::COMPRESSION_LZF')) {
            $result[] = (int) Redis::COMPRESSION_LZF;
        }

        if (\defined('Redis::COMPRESSION_ZSTD')) {
            $result[] = (int) Redis::COMPRESSION_ZSTD;
        }

        if (\defined('Redis::COMPRESSION_LZ4')) {
            $result[] = (int) Redis::COMPRESSION_LZ4;
        }

        sort($result);

        return array_values(array_unique($result));
    }

    /**
     * @return list<int>
     */
    public static function availableSerializers(): array
    {
        $result = [];

        if (\defined('Redis::SERIALIZER_NONE')) {
            $result[] = (int) Redis::SERIALIZER_NONE;
        }

        if (\defined('Redis::SERIALIZER_PHP')) {
            $result[] = (int) Redis::SERIALIZER_PHP;
        }

        if (\defined('Redis::SERIALIZER_IGBINARY')) {
            $result[] = (int) Redis::SERIALIZER_IGBINARY;
        }

        if (\defined('Redis::SERIALIZER_MSGPACK')) {
            $result[] = (int) Redis::SERIALIZER_MSGPACK;
        }

        if (\defined('Redis::SERIALIZER_JSON')) {
            $result[] = (int) Redis::SERIALIZER_JSON;
        }

        sort($result);

        return array_values(array_unique($result));
    }
}
