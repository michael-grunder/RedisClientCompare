## RedisClientCompare

This project is probably not useful to anyone but me.

It is just a quick fuzzer to compare the return values from PhpRedis 6.3.0RC1 and 6.2.0.

I want to make sure we haven't introduced any breaking changes given that there have been several performance improvments especially to the hash commands (how we curry field names).

The project simply generates random Redis commands and then compares the results from different versions of PhpRedis.

### Usage

Install dependencies and dump the autoloader:

```bash
composer install
```

All tooling is exposed through a Symfony Console application. The primary entry point is `bin/redis-client-compare`, but the historical wrapper scripts remain for convenience and now delegate to the same commands.

Generate a JSONL file of Redis commands (expiration-related commands are excluded by default to avoid timing-dependent noise; pass `--include-expiration` to include them):

```bash
php bin/redis-client-compare commands:generate 200 /tmp/commands.jsonl
# legacy wrapper
php bin/gen-commands 200 /tmp/commands.jsonl
```

Execute the commands against a Redis instance (requires the phpredis extension):

```bash
php bin/redis-client-compare commands:run /tmp/commands.jsonl /tmp/results.jsonl 127.0.0.1 6379
# legacy wrapper
php bin/run-commands /tmp/commands.jsonl /tmp/results.jsonl 127.0.0.1 6379
```

Compare two result sets:

```bash
php bin/redis-client-compare outputs:compare /tmp/results-old.jsonl /tmp/results-new.jsonl
# legacy wrapper
php bin/compare-outputs /tmp/results-old.jsonl /tmp/results-new.jsonl
```

Or run the end-to-end loop comparing two PHP binaries:

```bash
php bin/redis-client-compare compare:loop --old=/path/to/php-old --new=/path/to/php-new
# legacy wrapper
php bin/compare-loop --old=/path/to/php-old --new=/path/to/php-new
```

List Redis commands that are missing implementations in `src/Command`:

```bash
php bin/redis-client-compare commands:missing 127.0.0.1 6379
# legacy wrapper
php bin/needed 127.0.0.1 6379
```
