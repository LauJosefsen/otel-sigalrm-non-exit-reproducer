<?php
require __DIR__.'/vendor/autoload.php';

// wait for PDO to be available
while(true){
    try {
        $pdo = new \PDO('mysql:host=mariadb;dbname=mysql', 'root', 'example');
        $pdo->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true); // Avoid errors that we don't fetch rows.
        break;
    } catch (\PDOException $e) {
        echo "Waiting for PDO to be available...\n";
        echo $e->getMessage() . "\n";
        sleep(1);
    }
}

pcntl_async_signals(true);
pcntl_signal(SIGALRM, function () {
    echo "Timeout reached, exiting...\n";
    exit(1);
});

while (true) {
    pcntl_alarm(1);
    try{
        $pdo->exec('SELECT sleep(2)');
    } catch (\PDOException $e) {}
}
