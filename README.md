## RedisClientCompare

This project is probably not useful to anyone but me.

It is just a quick fuzzer to compare the return values from PhpRedis 6.3.0RC1 and 6.2.0.

I want to make sure we haven't introduced any breaking changes given that there have been several performance improvments especially to the hash commands (how we curry field names).

The project simply generates random Redis commands and then compares the results from different versions of PhpRedis.
