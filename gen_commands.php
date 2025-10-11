#!/usr/bin/env php
<?php
// gen_commands.php
// Usage: php gen_commands.php <count> <out.jsonl>
// Generates random *typed* Redis command args (ints, floats, strings)
// to exercise PhpRedis' coercion paths. One JSON array per line.

if ($argc < 3) {
    fwrite(STDERR,
        "Usage: php gen_commands.php <count> <out.jsonl>\n");
    exit(2);
}

$count = (int)$argv[1];
$outfn = $argv[2];
if ($count <= 0) {
    fwrite(STDERR, "count must be > 0\n");
    exit(2);
}

$cmds = [
    "DECR",
    "DEL",
    "EXISTS",
    "EXPIRE",
    "GET",
    "HDEL",
    "HGET",
    "HGETALL",
    "HMGET",
    "HMSET",
    "HSET",
    "INCR",
    "LPOP",
    "LPUSH",
    "LRANGE",
    "MGET",
    "MSET",
    "PERSIST",
    "RPOP",
    "RPUSH",
    "SADD",
    "SET",
    "SMEMBERS",
    "SREM",
    "TTL",
    "ZADD",
    "ZRANGE",
    "ZREM",
];

function rnd_ascii($min, $max) {
    $len = random_int($min, $max);
    $chars = "abcdefghijklmnopqrstuvwxyz0123456789_-:.";
    $s = '';
    for ($i = 0; $i < $len; $i++) {
        $s .= $chars[random_int(0, strlen($chars)-1)];
    }
    return $s;
}

function rnd_unicode() {
    // A little unicode spice (emoji + accents).
    $bank = [
        "αβγ", "Ångström", "naïve", "élan", "ßharp",
        "日本語", "русский", "مرحبا", "😀", "🔥", "🛰️"
    ];
    return $bank[random_int(0, count($bank)-1)];
}

function rnd_num_string() {
    // Strings that *look* numeric (to tickle coercion),
    // including leading zeros, signs, sci-notation, suffixes.
    $kinds = random_int(0, 6);
    switch ($kinds) {
    case 0:  return (string)random_int(-100000, 100000);
    case 1:  return "0" . str_pad(
                 (string)random_int(0, 999999), 5, "0", STR_PAD_LEFT);
    case 2:  return "-" . random_int(1, 99999);
    case 3:  return (string)(random_int(1, 9999)) . "e" .
                    random_int(1, 6);
    case 4:  return (string)(random_int(1, 9999)) . "."
                    . random_int(0, 9999);
    case 5:  return (string)random_int(1, 9999) . "abcff";
    default: return "42abc" . random_int(0, 999);
    }
}

function rnd_string() {
    $pick = random_int(0, 6);
    if ($pick <= 2) return rnd_ascii(1, 30);
    if ($pick == 3) return rnd_unicode();
    if ($pick == 4) return rnd_num_string();
    if ($pick == 5) return " " . rnd_ascii(1, 10) . " ";
    // very rarely empty string (edge case)
    return random_int(0, 30) === 0 ? "" : rnd_ascii(1, 5);
}

function rnd_int() {
    return random_int(-1_000_000, 1_000_000);
}

function rnd_float() {
    // varied magnitude + precision
    $v = random_int(-1_000_000, 1_000_000) / random_int(1, 10_000);
    // occasionally scientific
    if (random_int(0, 5) === 0) {
        $v *= pow(10, random_int(-6, 6));
    }
    return (float)$v;
}

function rnd_scalar_key() {
    // Bias towards strings, but include ints/floats.
    $pick = random_int(0, 9);
    if ($pick <= 4) return rnd_string();
    if ($pick <= 7) return rnd_int();
    return rnd_float();
}

function rnd_scalar_field() {
    // Same distribution, separate generator to vary independently.
    return rnd_scalar_key();
}

function rnd_value() {
    // Values: strings mostly; include ints/floats too.
    $pick = random_int(0, 9);
    if ($pick <= 5) return rnd_string();
    if ($pick <= 7) return rnd_int();
    return rnd_float();
}

$out = fopen($outfn, 'w') or die("open $outfn: $!\n");

for ($i = 0; $i < $count; $i++) {
    $cmd = $cmds[random_int(0, count($cmds)-1)];
    $args = [];

    switch ($cmd) {
    case "SET":
        $args = [rnd_scalar_key(), rnd_value()];
        break;

    case "GET":
    case "DEL":
    case "EXISTS":
    case "INCR":
    case "DECR":
    case "HGET":
    case "HDEL":
    case "EXPIRE":
    case "TTL":
    case "PERSIST":
        $args = [rnd_scalar_key()];
        if ($cmd === "EXPIRE") $args[] = (string)random_int(1, 3600);
        break;

    case "LPUSH":
    case "RPUSH":
        $k = rnd_scalar_key();
        $vals = [];
        for ($j = 0; $j < random_int(1, 4); $j++) $vals[] = rnd_value();
        $args = array_merge([$k], $vals);
        break;

    case "LPOP":
    case "RPOP":
        $args = [rnd_scalar_key()];
        break;

    case "LRANGE":
        $args = [rnd_scalar_key(),
                 (string)random_int(0, 2),
                 (string)random_int(3, 12)];
        break;

    case "SADD":
    case "SREM":
        $k = rnd_scalar_key();
        $vals = [];
        for ($j = 0; $j < random_int(1, 5); $j++) $vals[] = rnd_value();
        $args = array_merge([$k], $vals);
        break;

    case "SMEMBERS":
        $args = [rnd_scalar_key()];
        break;

    case "HSET":
        // Single field form to keep JSON as a flat array.
        $args = [rnd_scalar_key(), rnd_scalar_field(), rnd_value()];
        break;

    case "HMGET":
        $n = random_int(1, 20);
        $fields = [];
        for ($j = 0; $j < $n; $j++)
            $fields[] = rnd_scalar_field();
        $args = array_merge([rnd_scalar_key()], $fields);
        break;

    case "HMSET":
        $n = random_int(1, 10);
        $pairs = [];
        for ($j = 0; $j < $n; $j++) {
            $pairs[] = rnd_scalar_field();
            $pairs[] = rnd_value();
        }
        $args = array_merge([rnd_scalar_key()], $pairs);
        break;

    case "HGETALL":
        $args = [rnd_scalar_key()];
        break;

    case "ZADD":
        // ZADD key score member; member can be any value string/int/float.
        $args = [rnd_scalar_key(),
                 (string)random_int(-1000, 1000),
                 rnd_value()];
        break;

    case "ZRANGE":
        $args = [rnd_scalar_key(), "0", "-1"];
        break;

    case "ZREM":
        $args = [rnd_scalar_key(), rnd_value()];
        break;

    case "MGET":
        $n = random_int(1, 5);
        $keys = [];
        for ($j = 0; $j < $n; $j++) $keys[] = rnd_scalar_key();
        $args = $keys;
        break;

    case "MSET":
        // Flat pair list (k1, v1, k2, v2, ...) with typed keys+values.
        $n = random_int(1, 4);
        $pairs = [];
        for ($j = 0; $j < $n; $j++) {
            $pairs[] = rnd_scalar_key();
            $pairs[] = rnd_value();
        }
        $args = $pairs;
        break;

    default:
        $args = [rnd_scalar_key()];
    }

    // NOTE: JSON must be UTF-8; we avoid raw binary. Mixed ints/floats
    // and strings are kept as native JSON types to preserve coercion.
    $line = json_encode(array_merge([$cmd], $args));
    fwrite($out, $line . "\n");
}

fclose($out);
exit(0);
