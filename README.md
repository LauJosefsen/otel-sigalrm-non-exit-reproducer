# otel-sigalrm-non-exit-reproducer

Weird behavior when using SIGALRM with OpenTelemetry auto-instrumentation for PDO in PHP.

# Motivation
Avoid stuck long-running worker processes.

For example a worker that consumes from a database can reach a state where its query never finishes (dead TCP connection etc.) and thus we need to enforce a maximum execution time.

This is often done using `pcntl_alarm` and a signal handler for `SIGALRM`, by simply exiting the process when the alarm is reached, allowing it to be restarted by the orchestrator. Example: https://github.com/laravel/framework/blob/12.x/src/Illuminate/Queue/Worker.php#L213

# What it does:

```bash
docker compose up --build
```

Starts a mariadb and a php container that waits for MariaDB to be ready.

The PHP container has the OpenTelemetry auto-instrumentation for PDO enabled.

After connection is successful, it will register a SIGALRM handler that will kill the process if reached. It also registers handlers to run asynchronously.

It will then loop forever creating an alarm for 1 second and then querying a sleep for 2 seconds in mariadb, thus reaching the SIGALRM handler while the PDO query is still running.

# What is expected
The process should exit once the SIGALRM handler is reached.

# What is actually happening
The following error is printed to the console:

```
reproducer-1  | Warning: PDO::exec(): OpenTelemetry: post hook threw exception, class=PDO function=exec message=null in Unknown on line 0
```

it also seems the process reaches a bad state, where it no longers loops the while loop, and eventually gets an out of memory error.

```
reproducer-1  | Fatal error: Allowed memory size of 134217728 bytes exhausted (tried to allocate 4096 bytes) in /reproducer.php on line 26
mariadb-1     | 2025-07-03  7:41:32 3 [Warning] Aborted connection 3 to db: 'mysql' user: 'root' host: '172.20.0.3' (Got an error reading communication packets)
```

The problem is removed if auto instrumentation is disabled, or removing the PDO auto instrumentation.
