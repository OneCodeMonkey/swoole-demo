<?php

$serv = new swoole_server("127.0.0.1", 9601);

// watching connect
$serv->on('connect', function ($serv, $fd) {
    echo "Client: Connect success! \n";
});

// watching receive
$serv->on('receive', function ($serv, $fd, $form_id, $data) {
    $serv->send($fd, "Server: " . $data);
});

// watching close
$serv->on('close', function ($serv, $fd) {
    echo "Client: Closed! \n";
});

// start server
$serv->start();