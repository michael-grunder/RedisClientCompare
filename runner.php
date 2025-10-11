#!/usr/bin/env php
<?php
// runner.php
// Usage: php runner.php <commands.jsonl> <out.jsonl> [host] [port]
// Emits a single meta line, then one JSON record per command.
// Meta differences (php/ext versions, etc.) won't affect compare.

if ($argc < 3) {
    fwrite(STDERR,
        "Usage: php runner.php <commands.jsonl> <out.jsonl> [host] [port]\n");
    exit(2);
}

$cmdfn = $argv[1];
$outfn = $argv[2];
$host = $argv[3] ?? '127.0.0.1';
$port = isset($argv[4]) ? (int)$argv[4] : 6379;

if (!is_readable($cmdfn)) {
    fwrite(STDERR, "commands file not readable: $cmdfn\n");
    exit(2);
}
if (!extension_loaded('redis')) {
    fwrite(STDERR, "phpredis extension not loaded in this php.\n");
    exit(3);
}

$in = fopen($cmdfn, 'r') or die("open $cmdfn: $!\n");
$out = fopen($outfn, 'w') or die("open $outfn: $!\n");

$redis = new Redis();
try {
    $redis->connect($host, $port);
} catch (Throwable $e) {
    fwrite(STDERR, "connect failed: " . $e->getMessage() . "\n");
    exit(4);
}

try {
    $redis->flushAll();
} catch (Throwable $e) {
    // keep going; some servers restrict FLUSHALL
}

$meta = [
    'type' => 'meta',
    'time' => date('c'),
    'host' => $host,
    'port' => $port,
    'php_version' => phpversion(),
    'redis_ext_version' => phpversion('redis') ?: 'unknown',
    'sapi' => php_sapi_name(),
];
fwrite($out, json_encode($meta) . "\n");

function normalize($v) {
    if (is_bool($v)) return ['_type' => 'bool', 'v' => $v];
    if ($v === null) return null;
    if (is_int($v) || is_float($v) || is_string($v)) return $v;
    if (is_array($v)) {
        $o = [];
        foreach ($v as $k => $vv) $o[$k] = normalize($vv);
        return $o;
    }
    return ['_type' => 'other', 'repr' => (string)$v];
}

$idx = 0;
while (($line = fgets($in)) !== false) {
    $line = trim($line);
    if ($line === '') continue;
    $data = json_decode($line, true);
    if (!is_array($data) || !$data) continue;

    $cmd = array_shift($data);
    $call = array_merge([$cmd], $data);

    $rec = [
        'index' => $idx,
        'cmd' => $cmd,
        'args' => $data,
        'result' => null,
        'error' => null,
    ];

    try {
        $res = $redis->rawCommand(...$call);
        $rec['result'] = normalize($res);
    } catch (Throwable $e) {
        $rec['error'] = $e->getMessage();
    }

    fwrite($out, json_encode($rec) . "\n");
    $idx++;
}

fclose($in);
fclose($out);
exit(0);
