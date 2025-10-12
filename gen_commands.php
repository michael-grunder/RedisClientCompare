#!/usr/bin/env php
<?php
// gen_commands.php
// Usage: php gen_commands.php <count> <out.jsonl>
// Generates random Redis command argument vectors using command classes.

if ($argc < 3) {
    fwrite(STDERR, "Usage: php gen_commands.php <count> <out.jsonl>\n");
    exit(2);
}

$count = (int) $argv[1];
$outfn = $argv[2];
if ($count <= 0) {
    fwrite(STDERR, "count must be > 0\n");
    exit(2);
}

$commandsDir = __DIR__ . '/cmds';
require_once $commandsDir . '/Command.php';

$declaredBefore = get_declared_classes();
$files = glob($commandsDir . '/*.php') ?: [];
sort($files);

foreach ($files as $file) {
    if ($file === $commandsDir . '/Command.php') {
        continue;
    }
    require_once $file;
}

$declaredAfter = get_declared_classes();
$commandClasses = [];
foreach (array_diff($declaredAfter, $declaredBefore) as $class) {
    if (!is_subclass_of($class, Command::class)) {
        continue;
    }

    $ref = new ReflectionClass($class);
    if ($ref->isAbstract()) {
        continue;
    }

    $commandClasses[] = $class;
}

if (empty($commandClasses)) {
    fwrite(STDERR, "No command classes found under {$commandsDir}\n");
    exit(1);
}

$commandInstances = array_map(
    function ($class) {
        /** @var Command $instance */
        $instance = new $class();
        return $instance;
    },
    $commandClasses
);

$out = @fopen($outfn, 'w');
if ($out === false) {
    $err = error_get_last();
    $msg = $err['message'] ?? 'unknown error';
    fwrite(STDERR, "open {$outfn}: {$msg}\n");
    exit(2);
}

$maxIndex = count($commandInstances) - 1;
for ($i = 0; $i < $count; $i++) {
    /** @var Command $command */
    $command = $commandInstances[random_int(0, $maxIndex)];
    $line = json_encode($command->buildCommand());
    fwrite($out, $line . "\n");
}

fclose($out);
exit(0);
