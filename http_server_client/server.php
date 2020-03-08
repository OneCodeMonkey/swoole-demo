<?php

$http = new Swoole\Http\Server("0.0.0.0", 9501);
$http->on('request', function ($request, $response) {
    $params = $request->get;
    if (!empty($params)) {
        $fin = fopen(__DIR__ . '/record.txt', "a");
        fwrite($fin, json_encode($params));
        fwrite($fin, "\r\n");
        fclose($fin);
    }
    $messageLists = '<h4>---------------------------------</h4>';
    $filePath = __DIR__ . '/record.txt';
    if (file_exists($filePath)) {
        $fout = fopen($filePath, "r");
        while (!feof($fout)) {
            $line = fgets($fout);
            if (!empty($line)) {
                $messageLists .= "<p style='color: darkblue';>>>>{username}:&nbsp;" . $line . "</p>";
            }
        }
    }

    $response->end("<h4>Message list:</h4><br/>" . $messageLists);
});
$http->start();
