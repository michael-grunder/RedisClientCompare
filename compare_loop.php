#!/usr/bin/env php
<?php
// compare_loop.php
// Usage:
//   php compare_loop.php --old=/path/php-old --new=/path/php-new
//     [--iters=0] [--sleep=0] [--count=200] [--host=127.0.0.1]
//     [--port=6379]
// iters=0 => infinite

ini_set('display_errors', 'stderr');

$opts = getopt('', [
    'old:', 'new:', 'iters::', 'sleep::', 'count::', 'host::', 'port::'
]);

function usage(): void {
    fwrite(STDERR,
"Usage: php compare_loop.php --old=/path/php-old --new=/path/php-new
  [--iters=0] [--sleep=0] [--count=200] [--host=127.0.0.1] [--port=6379]
");
}

if (!isset($opts['old'], $opts['new'])) {
    usage();
    exit(2);
}

$PHP_OLD = $opts['old'];
$PHP_NEW = $opts['new'];
$ITERS   = (int)($opts['iters'] ?? 0);
$SLEEP   = (int)($opts['sleep'] ?? 0);
$COUNT   = (int)($opts['count'] ?? 200);
$HOST    = $opts['host'] ?? '127.0.0.1';
$PORT    = (int)($opts['port'] ?? 6379);

$GEN = __DIR__ . '/gen_commands.php';
$RUN = __DIR__ . '/runner.php';
$CMP = __DIR__ . '/compare.php';

foreach ([$GEN, $RUN, $CMP] as $p) {
    if (!is_file($p)) {
        fwrite(STDERR, "Missing file: $p\n");
        exit(2);
    }
}

$work = rtrim(sys_get_temp_dir(), DIRECTORY_SEPARATOR) .
        DIRECTORY_SEPARATOR . 'phpredis_diff_' . getmypid();
if (!mkdir($work, 0700) && !is_dir($work)) {
    fwrite(STDERR, "Failed to mkdir $work\n");
    exit(2);
}
register_shutdown_function(function() use ($work) {
    $it = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($work,
            FilesystemIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($it as $f) {
        $fn = $f->getPathname();
        $f->isDir() ? @rmdir($fn) : @unlink($fn);
    }
    @rmdir($work);
});

function run_cmd(string $cmd): int {
    passthru($cmd, $code);
    return (int)$code;
}

function sh($s): string { return escapeshellarg($s); }

$i = 0;
while (true) {
    if ($ITERS > 0 && $i >= $ITERS) {
        echo "completed $i iterations; no diffs found\n";
        exit(0);
    }

    $cmdFile = "$work/cmds.$i.jsonl";
    $outA    = "$work/out_a.$i.jsonl";
    $outB    = "$work/out_b.$i.jsonl";

    // Generate
    $cmd = sh(PHP_BINARY) . ' ' . sh($GEN) . ' ' .
           (int)$COUNT . ' ' . sh($cmdFile);
    if (run_cmd($cmd) !== 0) {
        fwrite(STDERR, "gen_commands failed\n");
        exit(3);
    }

    // Run A
    $cmd = sh($PHP_OLD) . ' ' . sh($RUN) . ' ' .
           sh($cmdFile) . ' ' . sh($outA) . ' ' .
           sh($HOST) . ' ' . (int)$PORT;
    if (run_cmd($cmd) !== 0) {
        fwrite(STDERR, "runner failed for php-old\n");
        exit(4);
    }

    // Run B
    $cmd = sh($PHP_NEW) . ' ' . sh($RUN) . ' ' .
           sh($cmdFile) . ' ' . sh($outB) . ' ' .
           sh($HOST) . ' ' . (int)$PORT;
    if (run_cmd($cmd) !== 0) {
        fwrite(STDERR, "runner failed for php-new\n");
        exit(5);
    }

    // Compare
    $cmd = sh(PHP_BINARY) . ' ' . sh($CMP) . ' ' .
           sh($outA) . ' ' . sh($outB);
    $code = run_cmd($cmd);

    if ($code !== 0) {
        echo "DIFFERENCE FOUND on iteration $i\n";
        echo "commands: $cmdFile\n";
        echo "out-old:  $outA\n";
        echo "out-new:  $outB\n";
        exit(1);
    }

    echo "iter $i: OK\n";
    $i++;
    if ($SLEEP > 0) sleep($SLEEP);
}

