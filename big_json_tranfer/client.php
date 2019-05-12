<?php
# big json 发送

$client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);

$filePath = realpath( 'data-json/data_100m.json');
$fileContent = file_get_contents($filePath);
$fileLength = strlen($fileContent);
echo 'File size is : ' . $fileLength . " bytes. \n";

$client->set(array(
    'package_eof' => "\r\n\r\n",    // 文件分割符
    'socket_buffer_size' => 1 * 1024 * 1024,    // buffer 大小
    'package_max_length' => 300 * 1024 * 1024,  //协议最大长度
));

// 获取当前时间戳微秒数
// microtime() 这个函数返回形式为 unix_timestamp + 微妙数，中间用空格隔开
function microtime_float()
{
    list($usec, $sec) = explode(" ", microtime());
    return ((float)$usec + (float)$sec);
}

$start_time = 0;

$client->on('connect', function ($cli) {
    echo "connected! \n";
    global $start_time;
    $start_time = microtime_float();
    global $fileContent;
    $cli->send($fileContent."\r\n\r\n");
});
$client->on('receive', function ($cli, $data) {
    echo "received: {$data}\n";
});
$client->on('error', function ($cli) {
    echo "connection failed! \n";
});
$client->on('close', function ($cli) {
    global $start_time;
    $end_time = microtime_float();
    echo "connection closed! \n";
    echo "Run time : " . ($end_time - $start_time) . "s. \n";
});
$client->connect('127.0.0.1', 9601, 0.5);