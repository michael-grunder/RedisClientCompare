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

Generate a JSONL file of Redis commands:

```bash
php bin/gen-commands 200 /tmp/commands.jsonl
```

Execute the commands against a Redis instance (requires the phpredis extension):

```bash
php bin/run-commands /tmp/commands.jsonl /tmp/results.jsonl 127.0.0.1 6379
```

Compare two result sets:

```bash
php bin/compare-outputs /tmp/results-old.jsonl /tmp/results-new.jsonl
```

Or run the end-to-end loop comparing two PHP binaries:

```bash
php bin/compare-loop --old=/path/to/php-old --new=/path/to/php-new
```
