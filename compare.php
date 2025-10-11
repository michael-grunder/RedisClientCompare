#!/usr/bin/env php
<?php
// compare.php
// Usage: php compare.php file_a.jsonl file_b.jsonl
// Ignores meta records (type=meta). Compares command records by order.

if ($argc < 3) {
    fwrite(STDERR, "Usage: php compare.php a.jsonl b.jsonl\n");
    exit(2);
}

[$_, $fa, $fb] = $argv;
if (!is_readable($fa) || !is_readable($fb)) {
    fwrite(STDERR, "files must be readable\n");
    exit(2);
}

$ra = fopen($fa, 'r') or die("open $fa: $!\n");
$rb = fopen($fb, 'r') or die("open $fb: $!\n");

function next_cmd_record($fh) {
    while (true) {
        $l = fgets($fh);
        if ($l === false) return null;
        $j = json_decode(trim($l), true);
        if (!is_array($j)) continue;
        if (($j['type'] ?? null) === 'meta') continue;
        return $j;
    }
}

$line = 0;
while (true) {
    $ja = next_cmd_record($ra);
    $jb = next_cmd_record($rb);

    if ($ja === null && $jb === null) break;
    if ($ja === null || $jb === null) {
        echo "DIFFERENT LENGTH at cmd index $line\n";
        exit(1);
    }

    // Compare only {cmd,args,result,error} regardless of index.
    $ka = [
        'cmd'    => $ja['cmd'] ?? null,
        'args'   => $ja['args'] ?? null,
        'result' => $ja['result'] ?? null,
        'error'  => $ja['error'] ?? null,
    ];
    $kb = [
        'cmd'    => $jb['cmd'] ?? null,
        'args'   => $jb['args'] ?? null,
        'result' => $jb['result'] ?? null,
        'error'  => $jb['error'] ?? null,
    ];

    if ($ka != $kb) {
        echo "DIFFER at cmd index $line\n";
        echo "COMMAND: " . ($ka['cmd'] ?? 'unknown') . "\n";
        echo "--- run A ---\n";
        echo json_encode($ja, JSON_PRETTY_PRINT) . "\n";
        echo "--- run B ---\n";
        echo json_encode($jb, JSON_PRETTY_PRINT) . "\n";
        exit(1);
    }

    $line++;
}

fclose($ra);
fclose($rb);
echo "IDENTICAL\n";
exit(0);
