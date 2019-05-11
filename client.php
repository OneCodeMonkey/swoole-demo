<?php

$client = new swoole_client(SWOOLE_SOCK_TCP);

// connect to server-side
if (!$client->connect("127.0.0.1", 9601, 0.5)) {
    die("connecting failed.\n");
}



// send data to server-side
if (!$client->send('12312323')) {
    die("sending data failed.\n");
}

// receive data from server-side
$recv_data = $client->recv();
if (!$recv_data) {
    die("receiving failed.\n");
}
echo $recv_data . "\n";

// close connect
$client->close();