<?php

$server = new swoole_server('0.0.0.0', 9601);
$server->set(array(
    'open_length_check' => true,
    'open_eof_check' => true,
    'package_eof' => "\r\n\r\n",
    'open_eof_split' => true,
    'package_max_length' => 300 * 1024 * 1024,  //协议最大长度
));
$server->on('connect', function ($server, $fd){
    echo "connected! : {$fd}\n";
});
$server->on('receive', function ($server, $fd, $reactor_id, $data) {
    echo 'received : ' . $data;
    $server->close($fd);
    file_put_contents('./received.json', $data);
});
$server->on('close', function ($server, $fd) {
    echo "connection closed! : {$fd}\n";
});
$server->start();