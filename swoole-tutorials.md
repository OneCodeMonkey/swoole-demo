# Swoole-Tutorials

_____________________________

## swoole

php的异步，并行，高性能网络通信引擎，使用纯C语言编写，提供了php语言的异步多线程服务器，异步TCP/UDP网络客户端，异步MySQL，异步Redis，数据库连接池，AsyncTask，消息队列，毫秒定时器，异步文件对俄，异步DNS查询。Swoole内置了 Http/WebSocket服务器端/客户端，Http2.0服务器端/客户端。

swoole底层内置了异步非阻塞，多线程的网络IO服务器。php服务器仅需处理事件回调即可而无需关心底层。与 `nginx/Tornado/node.js` 等全异步的框架不同，swoole既支持全异步，也支持同步。

除了异步IO的支持外，swoole为php多进程的模式设计了多个并发数据结构和 `IPC` 通信机制。可以大大简化多进程并发编程的工作。其中包括了 并发原子计数器，并发 HashTable，Channel，Lock，进程间通信 IPC等丰富的功能特性。

swoole从 2.0版本开始支持内置协程，可以使用完全同步的代码来实现异步的程序。php代码无需额外增加任何关键词，底层自动进行协程调度，实现异步。

## 1. 入门指引

`swoole` 虽然是标准PHP扩展，实际上与普通的扩展不同。普通的扩展一般只是提供一个库函数。而swoole扩展在运行后会接管PHP的控制权，进入PHP执行的事件循环。

当 IO 事件发生后，`swoole` 会自动回调指定的 `PHP` 函数。

##### Server

强大的 `TCP/UDP Server` 框架，支持多线程，`EventLoop` , 事件驱动，异步，`Worker` 进程组，`Task` 异步任务，毫秒定时器，`SSL/TLS` 隧道加密。

- `swoole_http_server` 是 `swoole_server` 的子类，内置了`http` 的支持
- `swoole_websocket_server` 是 `swoole_http_server` 的子类，内置了`webSocket` 的支持
- `swoole_redis_server` 是 `swoole_server` 的子类，内置了 `Redis` 服务器端协议的支持

> 子类可以调用父类的所有方法和属性

##### Client

一个`TCP/UDP/UnixSocket` 客户端，支持 `IPv4/IPv6` ，支持 `SSL/TLS` 隧道加密，支持 `SSL` 双向证书，支持同步并发调用，支持异步事件驱动编程。

##### Event

`EventLoop API` , 让用户可以直接操作底层的事件循环，将 `socket`, `stream`, 管道等 `Linux` 文件加入到事件循环种。

> `eventloop` 接口仅可用于 `socket` 类型的文件描述符，不能用于磁盘文件读写。

##### Coroutine

Swoole 在 2.0 开始内置协程（Coroutine）的能力，提供了具备协程编程能力 IO 接口（统一在命名空间 Swoole\Coroutine）。

协程可以理解为纯用户态的线程，其通过协作而不是抢占来进行切换。相对于进程和线程，协程所有的操作都可以在用户态完成，创建和切换的消耗更低。Swoole可以为每个请求创建对应的协程，根据 IO 的状态来合理地调度协程，这会带来以下几点优势：

开发者可以无感知地用同步的代码编写方式达到异步 IO 的效果和性能，避免了传统异步回调所带来的离散的代码逻辑和陷入多层回调中导致代码无法维护。

同时由于Swoole是在底层封装的协程，所以对比传统的 php 层协议框架，开发者不需要使用 yield 关键词来标识一个协程 IO 操作，所以不再需要对 yield 的语义进行深入理解以及对每一级的调用都修改为 yield，这极大地提高了开发效率。

##### Process

进程管理模块。可以方便地创建子进程，进行进程间通信，进程的管理。

##### Buffer

强大的内存区管理工具，像 `C` 语言一样进行指针计算，又无需关心内存的申请和释放，而且不用担心内存越界，底层已经都做好了。

##### Table

基于共享内存和自旋锁实现的超高性能内存表。彻底解决线程，进程间数据共享，加锁同步等问题。

> `swoole_table` 的性能可以达到单线程每秒读写 200 万次。

### 1.1 环境依赖

### 1.2 编译安装

#### 1.2.1 编译参数

#### 1.2.2 常见错误

#### 1.2.3 Cygwin

### 1.3 快速起步

#### 1.3.1 创建TCP服务器

###### sample code

```php
// 创建Server对象，监听9501端口
$serv = new swoole_server("127.0.0.1", 9501);
// 监听连接进入的事件
$serv->on('connect', function($serv, $fd) {
    echo "Client: Connect.\n";
});
// 监听数据接收事件
$serv->on('receive', function($serv, $fd, $from_id, $data) {
    $serv->send($fd, "Server: " . $data);
});
// 监听连接关闭事件
$serv->on('close', function($serv, $fd) {
    echo "Client: Close.\n";
});
// 启动服务器
$serv->start();
```

swoole_server 是一个异步服务器，通过监听事件的方式来实现功能。当对应的事件发生时，底层会主动回调指定的PHP函数。如果有新的 TCP 连接进入时会执行 onConnect 事件回调，当某个连接向服务器发送数据时，会回调 onReceive 函数。

- 服务器可以同时被成千上万个客户端连接，$fd 就是客户端连接的唯一标识符
- 调用`$server->send()` 方法向客户端连接发送数据，参数就是 $fd 客户端标识符
- 调用 `$server->close()` 方法可以强制关闭某个客户端连接
- 客户端可能会主动断开连接，此时触发 onClose 事件回调

###### 执行sample

```shell
php server.php
```

在命令行下运行 server.php 文件，启动成功后可以使用 `netstat` 工具可以看到，已经在监听 9501 端口。这时就可以使用 telnet/netcat 工具连接服务器。

```shell
telent 127.0.0.1 9501
hello
Server： hello
```



#### 1.3.2 创建UDP服务器

###### sample code

```php
// udp_server.php
//创建Server 对象，监听 127.0.0.1：9502 端口，类型为SWOOLE_SOCK_UDP
$serv = new swoole_server("127.0.0.1", 9502, SWOOLE_PROCESS, SWOOLE_SOCK_UDP);
//监听数据接收事件
$serv->on('Packet', function($serv, $data, $clientInfo) {
    $serv->sendto($clientInfo['address'], $clientInfo['port'], 'Server ' . $data);
    var_dump($clientInfo);
});
// 启动服务器
$serv->start();
```

UDP 是单向协议。启动Server后，客户端无需Connect, 直接可以向 Server监听的端口发送数据包，对应事件为 onPacket。

- clientInfo 是客户端相关信息数组，又客户端的IP和端口等
- 调用$server->sendto 方法向客户端发送数据

###### 启动服务

```shell
php udp_server.php
```

UDP服务器可以使用 `netcat -u` 来连接测试

```shell
netcat -u 127.0.0.1 9502
hello
Server: hello

```

#### 1.3.3 创建Web服务器

###### sample code

```php
// http_server.php
$http = new swoole_http_server('0.0.0.0', 9501);
$http->on('request', function($request, $response) {
    var_dump($request->get, $request->post);
    $response->header('Content-Type', 'text/html;charset=utf-8');
    $response->end("<h1>hello world. #" . rand(1000, 9999) . "</h1>");
});
$http->start();
```

Http服务器只需要关注请求响应即可，所以只需要监听 onRequest 事件。当有新的 Http 请求进入时就会触发此事件。事件的回调函数有两个参数，一个是 request 对象，包含了请求的相关信息，如 GET/POST 请求数据。

另外要给是 response对象，对 request 的响应可以通过操作 response 对象来完成。$response->end() 方法表示输出一段 HTML 内容，并结束此请求。

- 0.0.0.0 表示监听所有 IP 地址，一台服务器可能有多个 IP，如127.0.0.1 本地回环 IP，192.168.1.100 局域网IP，210.127.20.2 外网IP，当然我们也可以指定监听某个单独IP
- 9501 监听的端口，如果被占用程序会抛出致命错误，中断执行。

###### URL 路由

应用程序可以根据 $request->server['request_uri'] 实现路由，如 http://127.0.0.1:9501/test/index/?a=1, 代码中可以这样实现 URL 路由。

```php
$http->on('request', function($request, $response){
    list($controller, $action) = explode('/', trim($request->server['request_uri'], '/'));
    // 根据$controller, $action 映射到不同的控制器类和方法
    (new $controller)->$action($request, $response);
});
```



#### 1.3.4 创建WebSocket服务器

###### sample code

```php
// ws_server.php
// 创建websocket服务器对象，监听0.0.0.0：9502
$ws = new swoole_websocket_server('0.0.0.0', 9502);
// 监听WebSocket连接打开事件
$ws->on('open', function($ws, $request) {
    var_dump($request->fd, $request->get, $request->server);
    $ws->push($request->fd, "hello, welcome\n");
});
// 监听websocket消息事件
$ws->on('message', function($ws, $frame){
    echo "Message:{$frame->data}\n";
    $ws->push($frame->fd, "server:{$frame->data}");
});
// 监听websocket连接关闭事件
$ws->on('close', function($ws, $fd){
    echo "client-{$fd} is closed\n";
});
$ws->start();
```

websocket 服务器是建立在Http服务器之上的长连接服务器，客户端首先会发送一个Http请求与服务器握手。握手成功以后触发 onOpen事件表示连接就续，onOpen函数中包含$request对象，包含握手的详细信息，如 GET/POST参数，cookie，header等。

建立连接后客户端与服务器即可双向通信

- 客户端向服务器端发送信息时，服务器端触发 onMessage 事件回调
- 服务器端可以调用 $server->push() 向某个客户端（$fd 标识符）发送消息
- 服务端可设置 onHandShake 事件回调来手动处理 websocket 握手。
- swoole_http_server 是 swoole_server 的子类，内置了 Http 的支持
- swoole_websocket_server 是swoole_http_server 的子类，内置了 websocket 的支持运行程序

运行 `php ws_server.php` 

可以使用chrome浏览器测试，JS代码为：

```php
var wsServer = 'ws://127.0.0.1:9502';
var websocket = new WebSocket(wsServer);
websocket.onopen = function(e) {
    console.log('connected to websocket server!');
};
websocket.onclose = function(e) {
    console.log('disconnected.');
};
websocket.onmessage = function(e) {
    console.log('retrived data from server: ' + e.data);
};
websocket.onerror = function(e, t) {
    console.log('error occured: ' + e.data);
};
```

- 无法用 swoole_client 与 websocket 的服务器通信，因为swoole_client 是TCP型

- 必须实现 websocket协议才能和 websocket 服务器通信，可以使用 swoole/framework 提供的 

  [PHP WebSocket客户端]: https://github.com/swoole/framework/blob/master/libs/Swoole/Client/WebSocket.php

###### Comet

WebSocket服务器除了提供 websocket 功能以外，实际上也可以处理 Http 长连接，只需要增加 onRequest 事件监听即可实现 Comet 方案 Http 长轮询。

#### 1.3.5 设置定时器

swoole提供类似 JS 的 setInterval/setTimeout 异步定时器，粒度为毫秒级。

###### sample code

```php
// 每隔2000ms触发一次
swoole_timer_trick(2000, function($timer_id) {
    echo "tick-2000ms\n";
});
// 3000ms以后执行函数
swoole_timer_after(3000, function() {
    echo "after 3000ms\n";
});

```

- swoole_timer_tick = setInterval, 返回值 int，代表定时器ID
- swoole_timer_after = setTimeout  返回值 int，代表定时器ID
- swoole_timer_clear = clearInterval/clearTimeout, 参数为定时器ID



#### 1.3.6 执行异步任务

在server里如果需要执行耗时很长的动作，比如要给聊天服务器此时需要发送广播，web服务器中发送邮件等等。如果直接去执行这些操作就会阻塞当前进程，导致服务器响应严重被拖慢。

swoole可以执行异步任务处理，投递一个异步任务到 TaskWorker 进程池中去执行，同时不影响当前请求的执行。

###### sample code

```php
// 基于第一个TCP服务器，只需要增加 onTask 和 onFinish 两个事件回调即可。另外需要设置task进程数，可以根据任务的耗时和任务量配置适当的 task进程。
$serv = new swoole_server('127.0.0.1', 9501);
// 设置异步任务的工作进程数量
$serv->set(['task_worker_num' => 4]);
$serv->on('receive', function($serv, $fd, $from_id, $data) {
    // 投递异步任务
    $task_id = $serv->task($data);
    echo "Dispath AsyncTask: id=$task_id\n";
});
// 处理异步任务
$serv->on('task', function($serv, $task_id, $from_id, $data) {
    echo "New AsyncTask[id=$task_id]" . PHP_EOL;
    // 返回任务执行的结果
    $serv->finish("$data->OK");
});
// 处理异步任务的结果
$serv->on('finish', function($serv, $task_id, $data) {
    echo "AsyncTask[$task_id] Finish: $data" . PHP_EOL;
});
$serv->start();
```

调用 $serv->task() 后，程序立即返回，继续向下执行代码。onTask 回调函数 Task 进程池内被异步执行。执行完成之后调用 $serv->finish() 返回结果。

> finish 操作非必填

#### 1.3.7 创建同步TCP客户端

###### sample code

```php
// client.php
$client = new swoole_client(SWOOLE_SOCK_TCP);
// 连接到服务器
if(!$client->connect('127.0.0.1', 9501, 0.5))
    die("connect failed.");
// send
if(!$client->send('hello world'))
    die('send failed.');
// receive
$data = $client->recv();
if(!data)
    die("receive failed.");
echo $data;
// close connection
$client->close();
```

创建一个TCP的同步客户端，此客户端可以用于连接到我们第一个示例TCP服务器，向服务器端发送一个 hello world 字符串，服务端返回一个 Server: hello world 字符串

这个客户端是同步阻塞的，connect/send/recv 会等待 IO 完成后再返回。同步阻塞操作并不消耗 CPU 资源。IO 操作未完成当前进程的话会自动转入 sleep 模式，当IO完成后操作系统会唤醒当前进程，继续向下执行。

- tcp 建连接需要三次握手，所以connect至少要三次网络传输过程
- 在发送少量数据时 $client->send 可以立即返回，发送大量数据时，socket 缓存区可能会被塞满，send 操作会阻塞。
- recv 操作会阻塞等待服务器返回数据
- recv耗时 = 服务器处理时间 + 网络传输耗时

###### tcp通信图解

![tcp通信图解](https://camo.githubusercontent.com/f8315b68c96c2b19bf6cf89454555404cf4c9e22/68747470733a2f2f7777772e73776f6f6c652e636f6d2f7374617469632f696d6167652f7463705f73796e2e706e67)

测试一下

```shell
php client.php
Server: hello world
```



#### 1.3.8 创建异步TCP客户端

###### sample code

```php
// async_client.php
$client = new swoole_client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);

$client->on('connect', function($cli) {
    $cli->send("Hello world\n");
});

$client->on('receive', function($cli, $data) {
    echo "Received: " . $data . "\n";
});

$client->on('error', function($cli) {
    echo "connect failed.\n";
});

$client->on('close', function($cli) {
    echo "connection close.\n";
});

$client->connect('127.0.0.1', 9501, 0.5);
```

异步TCP客户端与同步TCP客户端不同，异步TCP客户端是非阻塞的，可以用来完成高并发的任务。swoole 提供的 redis-async, mysql-async 都是基于异步  swoole_client 实现。

异步客户端需要设置回调函数，有4个时间回调必须设置 onConnect, onError, onReceiver, onClose。分别在客户端连接成功，连接失败，收到数据，连接关闭时触发。

$client->connect() 发起连接的操作会立即返回，不存在等待时间。当对应的IO事件完成后，swoole 底层会自动调用设置好的回调

> 异步客户端只能用于命令行环境



#### 1.3.9 网络通信协议选择

###### 为什么需要用通信协议

TCP协议在底层机制上解决了UDP协议的顺序和丢包重传的问题，当相比UDP又带来了新的问题，TCP协议是流式的，数据包没有边界，应用程序使用TCP进行通信就面临这个问题——TCP传数据没有边界。

因为TCP通信是流式的，在接收一个大数据包时，会被拆分成多个数据包发送，多次Send底层也可能会合并成一次来发送。这里就需要两个操作来解决：

- 分包：Server 收到了多个数据包，需要拆分数据包

- 合并包：Server 收到的数据只是包的一部分，需要缓存数据，合并成完整的包

  所以TCP网络通信时需要设定通信协议，常见的TCP网络通信协议有`HTTP` , `HTTPS`, `FTP`, `SMTP`, `POP3`, `IMAP`, `SSH`, `Redis`, `Memcache`, `MySQL`

如果要设计一个通用的协议Server，那么就要按照通用协议的标准去处理网络数据，我们可以自己定义一个满足自己需要的协议。

Swoole 内置了两个自定义型协议

###### EOF结束符协议

EOF协议处理的原理是每个数据包结尾加一串特殊字符表示包已结束。如 `memcache`, `ftp`,`stmp` 使用 "\r\n" 当结束符。发送数据时在包末尾加上 "\r\n" 就行了。

使用EOF协议处理，一定要确保数据包中不会出现EOF，否则分包和预期会不一样

在swoole_server 和 swoole_client 的代码中只要设置2个参数就可以用EOF协议处理。

```php
$server->set([
    'open_eof_split' => true,
    'package_eof' => "\r\n",
]);
$client->set([
    'open_eof_split' => true,
    'package_eof' => "\r\n",
]);
```

###### 【固定包头 + 包体】协议

固定包头协议很常用，在BAT 的服务器程序中经常能看到。

这种协议的特点是一个数据包总由 `包头` + `包体` 2部分组成。包头中有一个字段指定了包体或整个包的长度，长度一般使用 2或4 byte 的整数来表示。服务器收到包头后，可根据长度值来精确地控制需要在接收多少数据才算完整的数据包。Swoole 的配置可以很好地支持这种协议，可灵活地设置 4 项参数应对所有情形。

swoole 的 server 和异步 client 都是在 onReceive 回调函数中处理数据包，当设置好协议部分后，只有收到一个完整的数据包时才会去触发 onReceive 事件。同步客户端在设置了协议处理后，调用 $client->recv() 不再需要传入长度，recv 函数在收到完整数据包或发生错误后返回。

```php
$server->set([
    'open_length_check' => true,
    'package_max_length' => 80 * 1024,
    'package_length_type' => 'n',  // see php pack()
    'package_length_offset' => 0,
    'package_body_offset' => 2,
]);
```



#### 1.3.10 使用异步客户端

php提供的 MySQL，CURL，Redis 等客户端是同步的，会导致服务器发生阻塞。

swoole 提供了常用的异步客户端组件来解决此问题。在编写纯异步服务器程序时，可以使用这些异步客户端。

异步客户端可以配合使用 SplQueue 实现连接池，以达到长连接复用的目的。在实际项目中可以使用 php 提供的 Yield/Generator 语法实现半协程的异步框架。也可以使用基于 Promises 来简化异步程序的编写。

###### MySQL

```php
$db = new Swoole\MySQL;
$server = [
    'host' => '127.0.0.1',
    'user' => 'test',
    'password' => 'test',
    'database' => 'test',
];
$db->connect($server, function($db, $result) {
    $db->query('show tables', function(Swoole\MySQL $db, $result) {
        var_dump($result);
        $db->close();
    });
});
```

与 mysqli 和 PDO 等客户端不同，Swoole\MySQL 是异步非阻塞的，连接服务器，执行SQL 时，需要传入一个回调函数。connect 的结果不在返回值中，而是在回调函数中。query 的结果也需要在回调函数中处理。

###### Redis

```php
$redis = new Swoole\Redis;
$redis->connect('127.0.0.1', 6379, function ($redis, $result) {
    $redis->set('test_key', 'value', function($redis, $result) {
        $redis->get('test_key', function($redis, $result) {
            var_dump($result);
        });
    });
});
```

Swoole\Redis 需要 swoole编译安装 hiredis，详细文档参见 

[异步Redis客户端]: https://wiki.swoole.com/wiki/page/p-redis.html

###### Http

```php
$cli = new Swoole\Http\Client('127.0.0.1', 80);
$cli->setHeaders(['User-Agent' => 'swoole-http-client']);
$cli->setCookies(['test' => 'value']);

$cli->post('/dump.php', ['test' => 'abc'], function($cli) {
    var_dump($cli->body);
    $cli->get('/index.php', function($cli) {
        var_dump($cli->cookies);
        var_dump($cli->headers);
    });
});
```

Swoole\Http\Client 的作用与CURL 完全一致，它完整地实现了 Http 客户端的相关功能。详细参见

[HttpClient文档]: https://wiki.swoole.com/wiki/page/p-http_client.html

###### 其他客户端

swoole 底层目前只提供了最常用的 MySQL, Redis，Http 异步客户端，如果我们应用程序中需要实现其他协议客户端，比如Kafka，AMQP 等协议，可以基于Swoole\Client 异步TCP 客户端，开发相关协议解析代码自行实现。

#### 1.3.11 多进程共享数据

由于php本身不支持多线程，因此swoole使用多进程模式，在多进程模式下存在进程内存隔离，在工作进程内修改 global 全局变量和超全局变量时，在其他进程是无效的。

> 设置 worker_num = 1 时，不存在进程隔离，可以使用全局变量保存数据

##### 进程隔离

```php
$fds = [];
$server->on('connect', function($server, $fd) {
    echo "connection open: {$fd}\n";
    global $fds;
    $fds[] = $fd;
    var_dump($fds);
});
```

`$fds` 虽然是全局变量，当只在当前进程有效，`swoole` 服务器底层会创建多个 `Worker` 进程，在`var_dump($fds)` 打印出来的值，只有部分连接的 `fd`。

对应的解决方案是使用外部存储服务：

- 数据库，如：`MySQL`， `MongoDB`
- 缓存服务器，如：`Redis`, `Memcache`
- 磁盘文件，多进程并发读写时需要加锁

普通的数据库和磁盘文件操作，存在较多的 `IO` 等待事件，因此推荐使用：

- `Redis` 内存 noSQL，读写速度极快
- `/dev/shm` 内存文件系统，读写操作全部在内存中完成，无IO损耗，性能极高

除了使用存储之外，还可以使用共享内存来保存数据

##### 共享内存

`PHP` 提供了多套共享内存的扩展，但实际上真正在实际项目中可用的并不多。

###### shm扩展

提供了 `shm_put_var` 和 `shm_get_var` 共享内存读写方法，但其底层实现使用链表结构，在保存大量数值时时间复杂度为 O(N)，性能非常差。并且读写数据没有加锁，存在数据同步问题，需要使用者自行加锁。

> 不推荐使用

###### shmop扩展

提供了 `shmop_read` 和 `shmop_write` 共享内存读写方法，仅提供了基础的共享内存操作指令，并未提供数据结构和封装，不适合普通开发者使用。

> 不推荐使用

###### apcu扩展

提供了 `apc_fetch`和`apc_store`，可以使用 key-value 方式访问，APC 扩展总体上是可以用于实际项目的，缺点是锁的粒度比较粗，在大量并发读写操作时锁的碰撞较为密集。

> `yac` 扩展，不适合用于保存数据，其设计原理导致存在一定的数据 miss 率，仅作为缓存，不可作为存储。

###### swoole\Table

`swoole` 官方提供的共享内存读写工具，提供了 key-value 访问方式，使用非常简单。底层使用自旋锁实现，在大量并发读写操作时性能依然非常强劲。推荐使用。 `Swoole\Table` 目前仍存在两个缺点，使用时需要根据情况来选择

- 需要预先申请内存，`Table` 在使用前就需要分配好内存，可能会占用较多的内存
- 无法动态扩容，`Table` 内存管理方式是静态的，不支持动态申请新内存，因此一个 `Table` 在设置好函数并创建之后，使用时不能超出限制。



#### 1.3.12 使用协程客户端

在swoole 的 4.x 版本中，协程取代了异步回调，作为我们推荐使用的编程方式。

协程解决了异步回调编程困难的情况。使用协程可以以传统同步编程的方式来些代码，而底层又能自动切换为异步IO。

协程往往用来提供系统设计的并发能力。

> 使用swoole 版本 4.2.5+

###### sample code

```php
$http = new swoole_http_server('0.0.0.0', 9501);
$http->on('request', function ($request, $response) {
    $db = new Swoole\Coroutine\MySQL();
    $db->connect([
        'host' => '127.0.0.1',
        'port' => 3306,
        'user' => 'user',
        'password' => 'pass',
        'database' => 'test',
    ]);
    $data = $db->query('select * from test_table');
    $response->end(json_encode($data));
});
$http->start();
```

上面的代码编写与同步阻塞模式程序完全一致，但是底层自动进行了协程切换处理，变为异步 IO, 因此服务器可以用来处理大量并发，每一个请求都会创建一个新的协程，执行对应的代码。

如果某个请求处理较慢，会引起这个请求被挂起，不影响其他请求的处理。

###### 其他协程组件

`swoole4` 扩展提供了丰富的协程组件，如 `Redis`,`TCP/UDP/Unix` 客户端，`Http/WebSocket/Http2` 客户端，使用这些组件可以方便地实现高性能的并发编程。

使用协程时参见

[协程编程须知]: https://wiki.swoole.com/wiki/page/851.html

###### 使用场景

适合用协程的场景有

- 高并发服务，如秒杀系统，高性能API接口，RPC 服务器。 使用协程可以让服务器容错率大大提高，某些接口出现故障时也不会导致整个服务瘫痪掉
- 爬虫。可以实现强大的并发能力，即使是慢速的网络环境，也可以高效利用带宽
- 即时通讯。如`IM` 聊天，游戏服务器，消息服务器等。可以确保消息通信完全无阻塞，每个消息包均可即使地被处理。

#### 1.3.13 协程：并发 shell_exec

在php程序中经常要用到 shell_exec 执行一些命令。而普通的 shell_exec 是阻塞的，如果命令执行的时间较长，那么很可能导致进程完全被卡住。在 swooel4 协程环境下可以用 `Co::exec` 并发地执行很多命令

#### 1.3.14 协程：Go + Chan + Defer

swoole4 为 php 提供了强大的 `CSP` 协程编程模式。底层提供了3个关键词，可以方便地实现各类功能。

- swoole4 提供的 `php协程` 语法借鉴自 `go`
- `php+swoole` 协程与 `go` 各有优势。`go` 是静态编译语言，性能好。php 动态脚本语言，开发速度快。

> 下面测试速度所用的环境是 php7.2 + swoole4.2

###### php关键词

- `go` ：创建一个协程
- `chan` ：创建一个通道
- `defer` ：延迟任务。在协程退出时执行，先进后出

这3个关键词底层表现方式均为***内存操作****，没有任何IO资源消耗，就像php 的数组一样是廉价的，只要有需要就可以直接使用。所以与 `socket`和`file` 操作不同，后者需要向操作系统申请接口和文件描述符，读写可能会产生阻塞的IO等待。

##### 协程并发

使用`go` 函数可以让一个函数并发地去执行，在编程过程中，如果某一段逻辑可以并发执行，就可以把它放到 `go` 协程中去执行。

###### 顺序执行

```php
function a() {
    sleep(1);
    echo 'b'l
}
function b() {
    sleep(2);
    echo 'b';
}
a();
b();
```

结果：

```shell
bchtf@LAPTOP-0K15EFQI:~$ time php co.php
bc
real    0m2.076s
user    0m0.000s
sys     0m0.078s
```

> 并发执行的任务，其总执行时间等于 max(t1, t2, t3, t4, ....)
>
> 顺序执行的任务，其总执行时间等于 t1 + t2 + t3 + t4

##### 协程通信

有了go 关键词之后，并发编程简单了很多。于此同时产生一个新问题。如果有2个协程并发执行，另外一个协程需要依赖这两个协程的执行结果，如何解决？

办法是使用通道（`channel`），在swoole4 协程中使用 `new chan`来创建一个通道。通道我们可以理解为自带协议调度功能的队列。通道有两个接口 `push` ,`pop`:

- `push` ：向通道中写入内容，如果已满，它会进入等待状态，有空间的时候才自动恢复
- `pop` ：从通道中读内容，如果为空则进入等待状态，有数据时自动恢复

使用通道可以方便地实现**并发管理**

```php
$chan = new chan(2);
// 协程1
go(function () use ($chan) {
    $result = [];
    for($i = 0; $i < 2; $i++) {
        $result += $chan->pop();
    }
    var_dump($result);
});
// 协程2
go(function () use ($chan) {
    $cli = new Swoole\Coroutine\Http\Client('www.qq.com', 80);
    $cli->set(['timeout' => 10]);
    $cli->setHeaders([
        'Host' => 'www.qq.com',
        'User-Agent' => 'Chrome/49.0.2587.3',
        'Accept' => 'text/html,application/xhtml+xml,application/xml',
        'Accept-Encoding' => 'gzip',
    ]);
    $ret = $cli->get('/');
    // $cli->body 响应内容过大，这里用Http状态码作为测试
    $chan->push(['www.qq.com' => $cli->statusCode]);
});
// 协程3
go(function () use ($chan) {
    $cli = new Swoole\Coroutine\Http\Client('www.163.com', 80);
    $cli->set(['timeout' => 10]);
    $cli->setHeaders([
        'Host' => 'www.163.com',
        'User-Agent' => 'Chrome/49.0.2587.3',
        'Accept' => 'text/html,application/xhtml+xml,application/xml',
        'Accept-Encoding' => 'gzip',
    ]);
    $ret = $cli->get('/');
    // $cli->body 响应内容过大，这里用Http状态码作为测试
    $chan->push(['www.163.com' => $cli->statusCode]);
});
```

执行结果：

```shell
htf@LAPTOP-0K15EFQI:~/swoole-src/examples/5.0$ time php co2.php
array(2) {
  ["www.qq.com"]=>
  int(302)
  ["www.163.com"]=>
  int(200)
}

real    0m0.268s
user    0m0.016s
sys     0m0.109s
htf@LAPTOP-0K15EFQI:~/swoole-src/examples/5.0$
```

- 协程1对管道进行两次 pop，刚开始时因为队列为空，所以进入等待状态
- 协程2和协程3执行完成后，会push数据，协程1拿到两个的结果，而这个等待时间仅是 二者取最大的执行时间而已。不用串行等待了。

##### 延迟任务

在协程编程中，可能需要在协程退出时自动执行一些任务做清理工作。类似于php 的`register_shutdown_function` ，在 swoole4 中可以使用 `defer` 实现

```php
Swoole\Runtime::enableCoroutine();
go(function () {
    echo 'a';
    defer(function () {
        echo '~a';
    });
    echo 'b';
    defer(function () {
        echo '~b';
    });
    sleep(1);
    echo 'c';
});
```

###### 执行结果：

```shell
htf@LAPTOP-0K15EFQI:~/swoole-src/examples/5.0$ time php defer.php
abc~b~a
real    0m1.068s
user    0m0.016s
sys     0m0.047s
htf@LAPTOP-0K15EFQI:~/swoole-src/examples/5.0$
```



#### 1.3.15 协程：实现 Go 语言风格的 defer

由于`go` 语言不提供析构方法，而php 对象是有析构函数的，我们使用 __destruct 就可以实现 `go` 风格的 `defer`.

###### sample code

```php
class DeferTask
{
    private $task;
    function add(callable $fn) {
        $this->tasks[] = $fn;
    }
    function __destruct() {
        // 反转
        $tasks = array_reverse($this->tasks);
        foreach($tasks as $fn) {
            $fn();
        }
    }
}
```

- 基于php对象的析构方法实现的`defer` 更为灵活，如果希望改变执行的实际，甚至可以将 `DeferTask` 对象赋值给其他生命周期较长的变量，`defer` 任务的执行可以延长生命周期。
- 默认情况下和 `go` 的 `defer` 一致，在函数退出时自动执行。

###### 使用 defer

```php
function testDefer() {
    $a = new DeferTask();

    $a->add(function () {
        // details
    });
    
    $a->add(function () {
        // details2
    });
    // 函数结束时，对象自动 destruct，defer 任务自动执行
    return $retval;
}
```



#### 1.3.16 协程： 实现 sync.WaitGroup 功能

在swoole4 中可以使用 `channel` 实现协程间通信，依赖管理，协程同步。基于 `channel` 可以轻松实现 `go` 的 `sync.WaitGroup`功能。

###### sample code

```php
class WaitGroup
{
    private $count = 0;
    private $chan;
    
    public function __construct()
    {
        $this->chan = new chan;
    }
    
    // 增加计数
    public function add()
    {
        $this->count++;
    }
    
    // 任务完成
    public function done()
    {
        $this->chan->push(true);
    }
    
    // 等待所有任务完成，恢复当前协程的执行
    public function wait()
    {
        while($this->count--) {
            $this->chan->pop();
        }
    }
}
```

- `WaitGroup` 对象可以复用，`add`, `done`, `wait` 之后可以再次使用

###### sample code

```php
go(function () {
    $wg = new WaitGroup();
    $result = [];
    $wg->add();
    // 启动第一个协程
    go(function () use ($wg, &$result) {
        // 启动一个协程客户端client来请求淘宝网首页
        $cli = new Client('www.taobao.com', 443, true);
        $cli->setHeaders([
            'Host' => 'www.taobao.com',
            'User-Agent' => 'Chrome/49.0.2587.3',
        	'Accept' => 'text/html,application/xhtml+xml,application/xml',
        	'Accept-Encoding' => 'gzip',
        ]);
        $cli->set(['timeout' => 1]);
        $cli->get('/index.php');
        $result['taobao'] = $cli->body;
        $cli->close();
        $wg->done();
    });
    $wg->add();
    // 启动第二个协程
    go(function () use ($wg, &$result) {
        // 启动一个协程客户端client来请求百度首页
        $cli = new Client('www.baidu.com', 443, true);
        $cli->setHeaders([
            'Host' => 'www.baidu.com',
            'User-Agent' => 'Chrome/49.0.2587.3',
        	'Accept' => 'text/html,application/xhtml+xml,application/xml',
        	'Accept-Encoding' => 'gzip',
        ]);
        $cli->set(['timeout' => 1]);
        $cli->get('/index.php');
        $result['taobao'] = $cli->body;
        $cli->close();
        $wg->done();
    });
    // 挂起当前协程，等待所有任务完成后恢复
    $wg->wait();
    // 此时$result 已经包含了2个任务执行结果
    var_dump($result);
})
```



### 1.4 注意点

#### 1.4.1 sleep/usleep 的影响

在异步IO 的程序中，**不允许使用sleep/usleep/time_sleep_until/time_nanosleep, 等睡眠函数**

原因如下：

- sleep等函数会使进程陷入睡眠阻塞
- 直到指定的时间后OS才会重新唤起当前睡眠了的进程
- sleep执行过程中，只有signal才能打断
- 由于swoole的signal 处理是基于signalfd 实现的，所以即使发送 signal 也无法中断swoole 的sleep

swoole提供的 `swoole_event_add`, `swoole_timer_tick`, `swoole_timer_after`, `swoole_process:signal`, 异步 swoole_client 在进程 sleep 后会停止工作，swoole_serer 也无法处理新的请求。

###### sample code

```php
$serv = new swoole_server('127.0.0.1', 9501);
$serv->set(['worker_num' => 1]);
$serv->on('receive', function($serv, $fd, $from_id, $data) {
    sleep(100);
    $serv->send($fd, 'Swoole: ' . $data);
});
$serv->start();
```

onReceive 事件中执行了 sleep函数，100秒内我们的 server无法处理任何进来的请求。

#### 1.4.2 exit/die 函数的影响

在swoole代码中尽量别使用 `exit` 和 `die`，如果PHP代码中有 `exit`和 `die` ，当前工作的 `worker` 进程，`task` 进程，`user`进程，以及 `swoole_process` 进程会立即退出。

使用 `exit` / `die` 之后，`worker` 进程会因为异常而退出，被 `master` 进程再次唤起，最终造成进程不断退出又不断启动和产生大量报警日志。

建议使用 `try` / `catch` 来替换掉 `exit` / `die` ，实现中断执行，以跳出当前的 php 函数调用栈。

```php
function swoole_exit(msg) {
    // php-fpm的环境
    if(ENV == 'PHP')
        exit;
    // swoole的环境
    else
        throw new Swoole\ExitException($msg);
}
```

> 上面的段代码还不能在项目中直接用，我们还要实现 ENV 变量和 Swoole\ExitException.

异常处理的方式比 `exit` / `die` 要更友好，因为异常是可控的，`exit` / `die` 不可控，在最外层执行 try catch 操作就能捕获异常，仅终止当前的任务。`worker` 进程可以继续处理新的请求，而 `exit` / `die` 会导致进程直接退出，当前进程保存的所有资源和变量值等都会被销毁。如果进程内还有其他任务要处理，此操作也会导致数据全部丢失 ：(



#### 1.4.3 while 循环的影响

异步程序如果碰到了死循环，事件将无法触发。异步IO程序使用 `Reactor` 模型，运行过程中必须在 `reactor->wait` 处轮询。如果遇到死循环，那么程序的控制权就在 while 中了，reactor 无法获取控制权，无法检测事件。所以 IO 事件回调函数也将无法触发。

> 密集运算的代码没有任何IO操作，所以不能称之为阻塞。

###### sample code

```php
$serv = new swoole_server('127.0.0.1', 9501);
$serv->set(['worker_num' => 1]);
$serv->on('receive', function ($serv, $fd, $reactorId, $data) {
    while(1) {
        $i++;
    }
    $serv->send($fd, 'Swoole: ' . $data);
});
$serv->start();
```

onReceive 事件中执行了死循环，结束不掉，所以 server 此时收不到任何客户端的请求。

#### 1.4.4 stat 缓存清理

php 底层对 `stat` 系统调用增加了 `cache` , 在使用 `stat`, `fstat`, `filemtime` 等函数时，底层可能会命中缓存，返回历史的数据。

我们可以主动用 `clearstatcache` 方法来清理 `stat` 文件缓存。 

#### 1.4.5 mt_rand 随机数

在 swoole 中如果我们于父进程中调用了 `mt_rand`，不同的子进程内再次调用 `mt_rand` , 返回的结果会一模一样。如果想要得到真正的随机，我们要在子进程中重新 "种时间种子"。

> 注：shuffer 和 array_rand 等依赖随机数的 php 函数同样会受到影响。

```php
mt_rand(0, 1);
// start
$worker_num = 16;
// fork
for($i = 0; $i < $worker_num; $i++) {
    $process = new swoole_process('child_async', false, 2);
    $pid = $process->start();
}
// async exec
function child_async(swoole_process $worker) {
    mt_srand();
    echo mt_rand(0, 100) . PHP_EOL;
    $worker->exit();
}
```



#### 1.4.6 进程隔离

进程隔离是很多新手会经常遇到的问题。修改了全局变量的值，为什么就是不生效？原因在于全局变量在不同的进程，不同的内存空间里是隔离的。所以我在一个进程里改的全局变量，在另一个进程里使用时不会生效。

- 不同进程的php变量是不共享的，即使是全局变量，在A进程内修改了它的值，在B进程里面也是无效的。
- 如果需要在不同的 worker 进程中共享数据，可以选择 `redis`, `mysql`, 文件，`Swoole\Table`, `APCu`, `shmget` 等工具来实现。
- 不同进程的文件句柄是隔离的，所以在A进程创建的 socket 连接或打开的文件，在B进程里是无效的，即使是将它的fd发送到B进程也是不可用的。

###### sample code

```php
$server = new Swoole\Http\Server('127.0.0.1', 9501);
$i = 1;
$server->on('Request', function($request, $response) {
    global $i;
    $response->end($i++);
});
$server->start();
```

在多进程的服务器中，$i 变量虽然是全局变量（global）,但由于进程隔离的原因，假设现在有 4 个进程在工作中，在进程1中进行 $i++, 实际上只有进程1中的 $i 变成 2，另外其他3个进程里的 $i 还是1

正确的做法是用 Swoole 提供的 Swoole\Table, 或 Swoole\Atomic 数据结构来保存数据，如上面代码可以这样实现：

```php
$server = new Swoole\Http\Server('127.0.0.1', 9501);
$atomic = new Swoole\Atomic(1);
$server->on('Request', function($request, $response) use ($atomic) {
    $response->end($atomic->add(1));
});
```

注：Swoole\Atomic 是建立在共享内存之上的，使用 add 方法加1时，在其他工作进程里也有效。

## 2. Server

### 2.1 函数列表

### 2.2 属性列表

### 2.3 配置选项

### 2.4 监听端口

### 2.5 预定义常量

### 2.6 事件回调函数

### 2.7 高级特性

#### 2.7.5 TCP-Keepalive 死连接检测

在tcp中有一个 `keep-Alive` 机制可以检测死连接，应用层如果对死连接周期不敏感或没有实现心跳机制，可以用操作系统提供的 `keepalive` 机制来踢掉死连接。在 `Server::set` 配置中增加 `open_tcp_keepalive >= 1` 表示启用 `tcp keepalive` 。另外有三个参数可以对 `keepAlive` 进行微调。

`keep-Alive` 机制不会强制切断连接，如果连接存在但是一直不发生数据交互，那么 `keep-Alive` 不会去切断连接。而应用层实现的心跳检测 `heartbeat_check` 即使连接存在，但在不产生数据交互的情况下仍会强制切断连接。

> 推荐使用 `heartbeat_check` 实现心跳检测

###### tcp_keepidle

连接在 n 秒内没有任何数据请求，则将开始对此连接进行探测。

###### tcp_keepcount

探测的次数，超过次数后将 `close` 此连接

###### tcp_keepinterval

探测的时间间隔，单位为秒

#### 2.7.6 TCP服务器心跳维持方案

正常情况下客户端中断 TCP 连接时，会发送一个 FIN 包，进行4次断开握手来通知服务器。但一些异常情况下，如果客户端突然断电断网或者网络异常，服务器可能无法得知客户端已断开了连接。

尤其是移动网络，TCP连接非常不稳定，因此需要一套机制来保证服务器和客户端之间连接的有效性。

swoole扩展本身内置了这种机制，开发者只需要配置一个参数即可启用。swoole在每次收到客户端数据时，会记录一个时间戳，当客户端在一定时间内未向服务器端发送数据，则服务器会自动切断连接。

###### 使用方法：

```php
$serv->set(array(
	'heartbeat_check_interval' => 5,
    'heartbeat_idle_time' => 10,
));
```

上面的设置就是每 5 秒检测一次心跳，一个TCP连接如果在 10 秒内未向服务器端发送数据，则将被切断。

###### 高级用法：

使用 swoole_server::heartbeat() 函数手工检测心跳是否到期。此函数会返回闲置时间超过 heartbeat_idle_time 的所有 TCP 连接。程序中可以将这些连接做一些操作，如发送数据或关闭连接。

### 2.8 压力测试

## 3. Coroutine

从swoole2.0开始，提供了协程（coroutine）特性，可使用协程 + 通道的全新编程模式来代替异步回调。应用层可以使用完全同步的编程方式，底层调度自动实现异步 IO

```php
go(function() {
    $redis = new Swoole\Coroutine\Redis();
    $redis->connect('127.0.0.1', 6379);
    $val = $redis->get('key');
});
```

> 4.0.0+ 仅支持 PHP7的版本
>
> 4.0.1 版本开始，去除了 --enable-coroutine 编译选项，改为
>
> [动态配置]: https://wiki.swoole.com/wiki/page/949.html
>
> 。

协程可以理解为纯用户态的线程，其通过**协作** 而不是抢占来进行切换。相对于进程或线程，协程所有的操作都可以在用户态完成，创建和切换的消耗更低。swoole 可以为每一个请求创建对应的协程。根据 IO 的状态来合理的调度协程，这会带来几点好处：

1. 开发者可以无感知地用同步的代码编写方式达到异步 IO 的效果和性能。避免了传统异步回调带来的离散代码逻辑和陷入多层回调中，导致代码无法维护。
2. 由于底层封装了协程，所以对比传统的php层协程框架，我们不需要使用 `yield` 关键词来表示一个协程 IO 操作，不需要再对 `yield` 语义进行深入理解以及对每一级的调用都修改为 `yield` ，提高编码效率

可满足大部分的场景需要，对于需要自定义网络协议的，开发者可以用协程 的 TCP 或 UDP 接口去封装自定义的协议。

###### 环境要去

- PHP version >=7.0
- 基于 `Server`, `Http\Server`, `WebSocket\Server` 进行开发，底层在 `onRequest`, `onReceive`, `onConnect` 等事件回调之前自动创建一个协程，在回调函数中使用协程 API
- 使用 coroutine::create 或 go 方法创建协程，在创建的协程中使用协程 API

###### 相关配置

在`server` 的 `set` 方法中增加了一个配置参数 `max_coroutine`， 用于配置一个 `worker` 进程最多同时处理的协程数。因为随着 `worker` 进程处理的协程数目越来越多，其占用的内存也会增加，为了避免超出 php 的 `memory_limit` 限制，需要根据业务的实际压力测试结果来调，默认为 3000

###### sample code

```php
$http = new swoole_http_server('127.0.0.1', 9501);
$http->on('request', function($request, $response) {
    $client = new Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
    $client->connect('127.0.0.1', 8888, 0.5);
    // 调用 connect 将触发协程切换
    $client->send('hello world from swoole');
    // recv() 也会触发协程切换
    $ret = $client->recv();
    $response->header('Content-Type', "text/plain");
    $response->end($ret);
    $client->close();
});
$http->start();
```

当代码执行到 connect() 和 recv() 时，底层会触发进行协程切换，此时可以去处理其他的事件或接收新的请求。当此客户端 connect 成功或后端服务 **回包**后，底层会恢复协程上下文，代码继续从切换点开始恢复执行。这整个过程底层自动完成，开发者不需要参与。

###### 全局变量

1. 全局变量：协程使得原有的异步逻辑同步化，但是在协程的切换是隐式发生的，所以在协程切换的前后不能保证全局变量以及 static 变量的一致性。
2. 请勿在 4.0 一下的版本的两种场景下触发协程切换：
   - 析构函数
   - 魔术方法 __call(), __ get(), __set() 等
3. 与 xdebug，xhprof，blackfire 等 zend 扩展不兼容。比如不能使用 xhprof 对协程 server 进行性能采样分析。

###### 协程组件

1. TCP/UDP Client:  Swoole\Coroutine\Client
2. HTTP/WebSocket Client: Swoole\Coroutine\HTTP\Client
3. HTTP2 Client: Swoole\Coroutine\HTTP2\Client
4. Redis Client: Swoole\Coroutine\Redis
5. MySQL Client: Swoole\Coroutine\MySQL
6. Postgresql Client: Swoole\Coroutine\PostgreSQL

- 在协程 Server 中使用协程版 Client, 可以实现全异步 Server
- 在其他程序中可以使用 go 关键词手动创建协程
- 同时 Swoole 提供了协程工具集：Swoole\Coroutine, 提供了获取当前协程 id，反射调用等能力。

### 3.1 Coroutine

###### 创建协程

```php
go(function() {
    co::sleep(0.5);
    echo "hello";
});
go('test');
go([$object, 'method']);
```

###### channel 操作

```php
$c = new chan(1);
$c->push($data);
$c->pop();
```

###### 协程客户端

```php
$redis = new Co\Redis;
$mysql = new Co\MySQL;
$http = new Co\Http\Client;
$tcp = new Co\Client;
$http2 = new Co\Http2\Client;
```

###### 其他API

```php
co::sleep(100);
co::fread($fp);
co::gethostbyname('www.baidu.com');
```

###### 延迟执行

```php
defer(function () use ($db) {
    $db->close();
});
```

#### 3.1.1 Coroutine::getCid

#### 3.1.2 Coroutine::create

#### 3.1.3 Coroutine::yield

#### 3.1.4 Coroutine::resume

#### 3.1.5 Coroutine::defer

#### 3.1.6 Coroutine::fread

#### 3.1.7 Coroutine::fgets

#### 3.1.8 Coroutine::write

#### 3.1.9 Coroutine::sleep

#### 3.1.10 Coroutine::gethostbyname

#### 3.1.11 Coroutine::getaddrinfo

#### 3.1.12 Coroutine::exec

#### 3.1.13 Coroutine::readFile

#### 3.1.14 Coroutine::writeFile

#### 3.1.15 Coroutine::stats

#### 3.1.16 Coroutine::statvfs

#### 3.1.17 Coroutine::getBackTrace

#### 3.1.18 Coroutine::listCoroutines

#### 3.1.19 Coroutine::set

### 3.2 Coroutine\Channel

#### 3.2.1 Coroutine\Channel->__construct

#### 3.2.2 Coroutine\Channel->push

#### 3.2.3 Coroutine\Channel->pop

#### 3.2.4 Coroutine\Channel->stats

#### 3.2.5 Coroutine\Channel->close

#### 3.2.6 Coroutine\Channel->length

#### 3.2.7 Coroutine\Channel->isEmpty

#### 3.2.8 Coroutine\Channel->isFull

#### 3.2.9 Coroutine\Channel->$capacity

#### 3.2.10 Coroutine\Channel->$errCode

### 3.3 Coroutine\Client

#### 3.3.1 Coroutine\Client->connect

#### 3.3.2 Coroutine\Client->send

#### 3.3.3 Coroutine\Client->recv

#### 3.3.4 Coroutine\Client->close

#### 3.3.5 Coroutine\Client->peek

### 3.4 Coroutine\Http\Client

#### 3.4.1 属性列表

#### 3.4.2 Coroutine\Http\Client->get

#### 3.4.3 Coroutine\Http\Client->post

#### 3.4.4 Coroutine\Http\Client->upgrade

#### 3.4.5 Coroutine\Http\Client->push

#### 3.4.6 Coroutine\Http\Client->recv

#### 3.4.7 Coroutine\Http\Client->addFile

#### 3.4.8 Coroutine\Http\Client->addData

#### 3.4.9 Coroutine\Http\Client->download

### 3.5 Coroutine\Http2\Client

#### 3.5.1 Coroutine\Http2\Client->__construct

#### 3.5.2 Coroutine\Http2\Client->set

#### 3.5.3 Coroutine\Http2\Client->connect

#### 3.5.4 Coroutine\Http2\Client->send

#### 3.5.5 Coroutine\Http2\Client->write

#### 3.5.6 Coroutine\Http2\Client->recv

#### 3.5.7 Coroutine\Http2\Client->close

### 3.6 Coroutine\Redis

#### 3.6.1 Coroutine\Redis::__construct

#### 3.6.2 Coroutine\Redis::setOptions

#### 3.6.3 属性列表

#### 3.6.4 事务模式

#### 3.6.5 订阅模式

### 3.7 Coroutine\Socket

#### 3.7.1 Coroutine\Socket::__construct

#### 3.7.2 Coroutine\Socket->bind

#### 3.7.3 Coroutine\Socket->listen

#### 3.7.4 Coroutine\Socket->accept

#### 3.7.5 Coroutine\Socket->connect

#### 3.7.6 Coroutine\Socket->send

#### 3.7.7 Coroutine\Socket->recv

#### 3.7.8 Coroutine\Socket->sendto

#### 3.7.9 Coroutine\Socket->recvfrom

#### 3.7.10 Coroutine\Socket->getsockname

#### 3.7.11 Coroutine\Socket->getpeername

#### 3.7.12 Coroutine\Socket->close

### 3.8 Coroutine\MySQL

#### 3.8.1 属性列表

#### 3.8.2 Coroutine\MySQL->connect

#### 3.8.3 Coroutine\MySQL->query

#### 3.8.4 Coroutine\MySQL->prepare

#### 3.8.5 Coroutine\MySQL->escape

#### 3.8.6 Coroutine\MySQL\Statement->execute

#### 3.8.7 Coroutine\MySQL\Statement->fetch

#### 3.8.8 Coroutine\MySQL\Statement->fetchAll

#### 3.8.9 Coroutine\MySQL\Statement->nextResult

### 3.9 Coroutine\PostgreSQL

#### 3.9.1 Coroutine\PostgreSQL->connect

#### 3.9.2 Coroutine\PostgreSQL->query

#### 3.9.3 Coroutine\PostgreSQL->fetchAll

#### 3.9.4 Coroutine\PostgreSQL->affectedRows

#### 3.9.5 Coroutine\PostgreSQL->numRows

#### 3.9.6 Coroutine\PostgreSQL->fetchObject

#### 3.9.7 Coroutine\PostgreSQL->fetchAssoc

#### 3.9.8 Coroutine\PostgreSQL->fetchArray

#### 3.9.9 Coroutine\PostgreSQL->fetchRow

#### 3.9.10 Coroutine\PostgreSQL->metaData

### 3.10 Server

### 3.11 并发调用

在协程版本的 client 中实现了多个客户端并发发包的功能（`setDefer` 功能）

通常如果一个业务请求中需要做一次 redis 请求和一次 mysql请求，那么网络 IO 会是这样：

`redis发包 -> redis收包 -> mysql发包 -> mysql收包`

以上流程网络IO的时间就等于 redis网络IO时间 + mysql网络IO时间

但对于协程版本的 client，网络IO可以是这样的：

`redis发包 -> mysql发包 -> redis收包 -> mysql收包`

以上流程网络IO的时间就接近于 max(redis网络IO时间，mysql网络IO时间)

目前支持并发请求的 client如下：

- Swoole\Coroutine\Client
- Swoole\Coroutine\Redis
- Swoole\Coroutine\MySQL
- Swoole\Coroutine\Http\Client

除了 Swoolen\Coroutine\Client, 其他client 都实现了 defer 特性，用于声明延迟收包。因为 Swoole\Coroutine\Client 的发包和收包方法是分开的，所以就不需要实现 defer 特性了，而其他 client 的发包和收包都在一个方法中，所以需要要给 setDefer 方法来声明延迟收包，然后通过 recv 方法收包。

###### setDefer 使用示例

```php
function onRequest($request, $response)
{
    // 并发请求n
    $n = 5;
    for($i = 0; $i < $n; $i++) {
        $cli = new Swoole\Coroutine\Http\Client('127.0.0.1', 80);
        $cli->setHeaders([
            'Host' => 'local.aa.com',
            'User-Agent' => 'Chrome/49.0.2587.3',
            'Accept' => 'text/html,application/xhtml+xml,application/xml',
            'Accept-Encoding' => 'gzip',
        ]);
        $cli->set(['timeout' => 2]);
        $cli->setDefer();
        $cli->get('/test.php');
        $clients[] = $cli;
    }
    for($i = 0; $i < $n; $i++) {
    	$r = $clients[$i]->recv();
        $result[] = $clients[$i]->body;
    }
    $response->end(json_encode($data));
}
```



#### 3.11.1 setDefer 机制

绝大部分协程组件，都支持了setDefer 特性，setDefer 特性可以将响应式的接口分拆为两个步骤，使用此机制可以实现并发请求。

以 `HttpClient` 为例，设置 setDefer(true) 以后，发起 $http->get() 请求，将不再等待服务器返回结果，而是在 send request 之后，立即返回 true。在此之后可以继续发起其他 `HttpClient` ,`MySQL`, `Redis` 等请求，最后再使用 $http->recv() 接收响应内容。

###### sample code

```php
<?php
$server = new Swoole\Http\Server('127.0.0.1', 9501, SWOOLE_BASE);
$server->set(['worker_num' => 1]);
$server->on('Request', function($request, $response) {
    $tcpClient = new Swoole\Coroutine\Client(SWOOLE_SOCK_TCP);
    $tcpClient->connect('127.0.0.1', 9501, 0.5);
    $tcpClient->send("hello world\n");
    
    $redis = new Swoole\Coroutine\Redis();
    $redis->connect('127.0.0.1', 6379);
    $redis->setDefer();
    $redis->get('key');
    
    $mysql = new Swoole\Coroutine\MySQL();
    $mysql->connect([
        'host' => '127.0.0.1',
        'user' => 'user',
        'password' => 'password',
        'database' => 'test',
    ]);
    $mysql->setDefer();
    $mysql->query('select sleep(1)');
    
    $httpClient = new Swoole\Coroutine\Http\Client('0.0.0.0', 9599);
    $httpClient->setHeaders(['Host' => 'api.mp.qq.com']);
    $httpClient->set(['timeout' => 1]);
    $httpClient->setDefer();
    $httpClient->get('/');
    
    $tcp_res = $tcpClient->recv();
    $redis_res = $redis->recv();
    $mysql_res = $mysql->recv();
    $http_res = $httpClient->recv();
    
    $response->end('Test End');
});
$server->start();
```



#### 3.11.2 子协程，通道

除了只用底层内置的 `setDefer` 机制实现并发请求之外，还可以用 `子协程` + `通道` 实现并发。

###### sample code

```php
$serv = new \swoole_http_server('127.0.0.1', 9501, SWOOLE_BASE);
$serv->on('request', function($request, $response) {
    $channel = new chan(2);
    go(function () use ($chan) {
        $cli = new Swoole\Coroutine\Http\Client('www.qq.com', 80);
        $cli->set(['timeout' => 10]);
        $cli->setHeaders([
            'Host' => 'www.qq.com',
            'User-Agent' => 'Chrome/49.0.2587.3',
            'Accept' => 'text/html,application/xhtml+xml,application/xml',
            'Accept-Encoding' => 'gzip',
        ]);
        $ret = $cli->get('/');
        $channel->push(['www.qq.com' => $cli->body]);
    });
    
    go(function () use ($chan) {
        $cli = new Swoole\Coroutine\Http\Client('www.163.com', 80);
        $cli->set(['timeout' => 10]);
        $cli->setHeaders([
            'Host' => 'www.163.com',
            'User-Agent' => 'Chrome/49.0.2587.3',
            'Accept' => 'text/html,application/xhtml+xml,application/xml',
            'Accept-Encoding' => 'gzip',
        ]);
        $ret = $cli->get('/');
        $channel->push(['www.163.com' => $cli->body]);
    });
    $result = [];
    for($i = 0; $i < 2; $i++)
        $result += $channel->pop();
    $response->end(json_encode($result));
});
$serv->start();
```

###### 实现原理

- 在`onRequest` 中需要并发两个http请求，可以使用 go 函数创建两个子协程，并发地请求多个 url
- 创建一个 channel，使用 use 闭包引用语法，传递给子协程
- 主协程循环调用 $channel->pop()，等待子协程完成任务，yield 进入挂起状态
- 并发的两个子协程其中某个完成请求时，调用 $channel->push() 将数据推送给主协程
- 子协程完成 url 请求后退出，主协程从挂起状态中恢复，继续向下执行调用 $response->end() 发送响应结果。

### 3.12 实现原理

`swoole-2.0` 基于 `setjmp`, `longjmp` 实现，在进行协程切换时会自动保存 `Zend VM` 的内存状态（主要是 `EG` 全局内存和 `vm stack` ）

- `setjmp` 和 `longjmp` 主要用于从 `ZendVm` 的 `C` 堆栈跳回 `Swoole` 的 `C` 回调函数
- 协程的创建，切换，挂起，销毁，全部都是内存操作。消耗是非常低的。

###### sample code

```php
$server = new Swoole\Http\Server('127.0.0.1', 9501, SWOOLE_BASE);
#1
$server->on('Request', function($request, $response) {
    $mysql = new Swoole\Coroutine\MySQL();
    #2
    $res = $mysql->connect([
        'host' => '127.0.0.1',
        'use' => 'root',
        'password' => '123456',
        'database' => 'test',
    ]);
    #3
    if ($res == false) {
        $response->end('MySQL connect fail!');
        return;
    }
    $ret = $mysql->query('show tables', 2);
    $response->end('Swoole response is ok, result=' . var_export($ret, true));
});
$server->start();
```

- 上面代码仅用了一个进程，就可以并发处理大量请求。
- 程序的性能基本上与异步回调方式相同

###### 运行过程

- 调用 `onRequest` 事件回调函数时，底层会调用 C 函数 coro_create 创建一个协程（#1），同时保存这个时间点的 CPU 寄存器状态和 ZendVM 的 栈信息。
- 调用 mysql->connect 时发生IO操作，底层会调用 C 函数 coro_save 保存当前协程的状态，包括 Zend VM 上下文以及协程描述信息，并调用 coro_yield 让出程序控制权，当前的请求会挂起（#2）
- 协程让出程序控制权以后，会继续进入 EventLoop 处理其他事件，这时 swoole 会继续去处理其他客户端发来的 request请求
- IO 事件完成后，MySQL 连接成功或失败，底层会调用 C 函数 coro_resume 恢复对应的协程，恢复 Zend VM 上下文，继续向下执行（#3）
- mysql->query() 的执行过程与 mysql->connect 一致，也会进行一次协程切换调度
- 所有操作完成后，调用 end 方法返回结果，并销毁此协程

###### 协程开销

相比普通的异步回调程序，协程会多占用额外的内存。

- swoole4 协程需要为每个并发保存 `zend stack` 栈内存并维护对应的 虚拟机状态。如果程序并发很大，可能会占用大量内存。取决于 C 函数，php 函数调用栈的深度

- 协程调度会增加额外的一些 cpu 开销，可使用官方提供的 

  [协程切换压测脚本]: https://github.com/swoole/swoole-src/blob/master/benchmark/co_switch.php

  测试性能

#### 3.12.1 协程与线程

swoole 的协程在底层实现上是单线程的。同一事件只有一个协程在工作，协程的执行是串行的。这与线程不同，多个线程会被操作系统调度到多个 CPU **并行** 执行。

当一个协程在运行时，其他协程会停止工作。当前协程执行阻塞 IO 的操作时会挂起，底层调度器会进入 EventLoop，当有 IO完成事件时，底层调度器恢复事件对应的协程的执行。

对于 CPI 多核的利用，仍然依赖于 swoole 引擎的多进程机制。

#### 3.12.2 发送数据协程调度

###### 现状

现在 Server/Client->send 在缓存区已满的情况下，会直接返回 false，需要借助 onBufferFull 和 onBufferEmpty 这样复杂的事件通知机制才能实现任务的暂停和恢复。

在实现需要大量发送的场景下，现有机制虽然可以实现，但非常复杂。

###### 思路

现在基于协程可以实现一种机制，直接在当前协程内 yield，等待数据发送完成，缓存区清空时，自动 resume 当前协程，继续 send 数据。

- Server/Client->send 返回 false，并且错误码 为 `SW_ERROR_OUTPUT_BUFFER_OVERFLOW` 时，不返回 false 到 php 层，而是 yield 挂起当前协程。
- Server/Client 监听 onBufferEmpty 事件，在该事件触发后，缓存区内的数据已被发送完毕，这时 resume 对应的协程
- 协程恢复后，继续调用 Server/Client->send 向缓存区内写入数据，这时因为缓存区已空，发送必然是成功的。

###### sample code

 改进前：

```php
for($i = 0; $i < 100; $i++) {
    // 在缓存区塞满时会直接 返回 false
    $server->send($fd, $data_2m);
}
```

改进后：

```php
for($i = 0; $i > 100; $i++) {
    // 在缓存区塞满时，会 yield 当前协程，发送完成后 resume 继续向下执行
    $server->send($fd, $data_2m);
}
```

可选项：

此项特性会改变底层的默认行为，因此需要额外的一个参数来开启。

```php
$serv->set([
    'send_yield' => true,
]);
```

###### 影响范围

- Swoole\Server::send
- Swoole\Http\Response::write
- Swoole\WebSocket\Server::push
- Swoole\Coroutine\Client::send
- Swoole\Coroutine\\Http\Client::push

#### 3.12.3 协程内存开销

swoole4.0 的版本实现了 C 栈 + PHP 栈 的协程实现方案。Server 程序每次请求的事件回调函数中会创建一个新的协程，处理完成后协程退出。

在协程创建时需要创建要给全新的内存段作为 C 和PHP 的栈，底层默认 分配 2M(C) 的虚拟内存 + 8K（PHP）内存（PHP-7.2+）。实际并不会分配这么多内存，系统会根据在内存实际读写时发生缺页中断，再分配实际内存。

> 由于 PHP 7.1/7.0 未提供设置栈内存尺寸的接口，这些版本的每个协程将申请 256K 的php内存。

相比于异步回调的程序，协程会增加一些内存管理的开销。、会产生一定的新跟那个损耗。经过压力测试 QPS 依然可以达到较高的水平。

```shell
ab -c 100 -n 500000 -k http://127.0.0.1:9501/
This is ApacheBench, Version 2.3 <$Revision: 1706008 $>
Copyright 1996 Adam Twiss, Zeus Technology Ltd, http://www.zeustech.net/
Licensed to The Apache Software Foundation, http://www.apache.org/

Benchmarking 127.0.0.1 (be patient)
Completed 50000 requests
Completed 100000 requests
Completed 150000 requests
Completed 200000 requests
Completed 250000 requests
Completed 300000 requests
Completed 350000 requests
Completed 400000 requests
Completed 450000 requests
Completed 500000 requests
Finished 500000 requests


Server Software:        swoole-http-server
Server Hostname:        127.0.0.1
Server Port:            9501

Document Path:          /
Document Length:        24 bytes

Concurrency Level:      100
Time taken for tests:   3.528 seconds
Complete requests:      500000
Failed requests:        0
Keep-Alive requests:    500000
Total transferred:      132500000 bytes
HTML transferred:       12000000 bytes
Requests per second:    141738.54 [#/sec] (mean)
Time per request:       0.706 [ms] (mean)
Time per request:       0.007 [ms] (mean, across all concurrent requests)
Transfer rate:          36680.38 [Kbytes/sec] received

Connection Times (ms)
              min  mean[+/-sd] median   max
Connect:        0    0   0.0      0       2
Processing:     0    1   0.9      0       7
Waiting:        0    1   0.9      0       7
Total:          0    1   0.9      0       7
WARNING: The median and mean for the processing time are not within a normal deviation
        These results are probably not that reliable.
WARNING: The median and mean for the waiting time are not within a normal deviation
        These results are probably not that reliable.
WARNING: The median and mean for the total time are not within a normal deviation
        These results are probably not that reliable.

Percentage of the requests served within a certain time (ms)
  50%      0
  66%      0
  75%      2
  80%      2
  90%      2
  95%      3
  98%      3
  99%      3
 100%      7 (longest request)
```



#### 3.12.4 4.0协程实现原理

###### 内存栈

swoole4 的版本实现了 PHP栈+C栈 的双栈模式，创建协程时会创建一个 C 栈，默认尺寸为 2m，创建一个php栈，默认尺寸为 8k，

C 栈主要用于保存底层函数调用的局部变量数据，用于解决 `call_user_func`, `array_map` 等 C 函数调用在协程切换时未能还原的问题。

swoole4 无论如何切换协程，底层总能正确地切换回原先的 C 函数栈帧，继续向下执行。

> C 栈分配的 2m 内存使用了虚拟内存，并不会分配实际内存。

swoole4 的底层还支持了 嵌套关系，在协程内创建子协程，子协程挂起时仍然可以恢复父进程的执行。

> 底层最大允许 128 层嵌套

```c
Context::Context(size_t stack_size, coroutine_func_t fn, void* private_data) : fn_(fn), stack_size(stack_size), private_data_(private_data) {
    protect_page_ = 0;
    end = false,
    swap_ctx_ = NULL;
    
    stack_ = (char*) sw_malloc(stack_size_);
    swDebug("alloc stack: size=%u, ptr=%p.", stack_size_, stack_);
}
```

php栈主要保存php函数调用的全局变量数据，主要是 `zval` 结构体，php 中的标量类型，如整型，浮点型，布尔型等直接保存在 `zval` 结构体内，而 `object`, `string`, `array` 是使用引用计数管理且在堆上存储的。

8K 的php栈足以保存整个函数调用的 全局变量。

```php
static inline void sw_vm_stack_init () {
    uint32_t size = COROG.stack_size;
    zend_vm_stack page = (zend_vm_stack) emalloc(size);
    
    page->top = ZEND_VM_STACK_ELEMENTS(page);
    page->end = (zval*) ((char*)page + size);
    page->prev = NULL;
    
    EG(vm_stack) = page;
    EG(vm_stack)->top++;
    EG(vm_stack_top) = EG(vm_stack)->top;
    EG(vm_stack_end) = EG(vm_stack)->end;
}
```

###### 进程切换

C 栈切换使用了 boost.context 1.60 汇编代码，用于保存寄存器，切换指令序列。只要是 `jump_fcontext` 这个 ASM 函数提供。php栈的切换是随 C栈的切换同步进行的。底层会切换 EG(vm_stack) 使 php 恢复到正确的 php 函数栈帧。swoole4.0.2 版本增加了 ob 输出缓存区的切换，ob_start 等操作也可以用于协程。

> boost.context 汇编切换协程栈的效率非常高，经过测试每秒可完成 2亿 次切换
>
> 某些平台下不支持 boost.context 汇编，底层将使用 ucontext

###### 性能对比

- boost.context: 8ns / 23 cycles
- ucontext: 547ns / 1433 cycles
- php context: 170ns

###### 调用栈切换

```c
int sw_coro_resume(php_context *sw_current_context, zval *retval, zval *coro_retval) {
    coro_task *task = SWCC(current_task);
    resume_php_stack(stack);
    if(EG(current_execute_data)->prev_execute_data->online->result_type != IS_UNUSED && retval) {
        ZVAL_COPY(SWCC(current_coro_return_value_ptr), retval);
    }
    if(OG(handlers).elements) {
        php_outputs_deactivate();
        if(!SWCC(current_coro_output_ptr)) {
            php_output_activate();
        }
    }
    if(SWCC(current_coro_output_ptr)) {
        memcpy(SWOG, SWCC(current_coro_output_ptr), sizeof(zend_output_globals));
        efree(SWCC(current_coro_output_ptr));
        SWCC(current_coro_output_ptr) = NULL;
    }
    swTraceLog(SW_TRACE_COROUTINE, "cid = %d", task->cid);
    coroutine_resume_naked(task->co);
    
    if(unlikely(EG(exception))) {
        if(retval) {
            zval_ptr_dtor(retval);
        }
        zend_exception_error(EG(exception), E_ERROR TSRMLS_CC);
    }
    return CORO_END;
}
```

###### 协程调度

swoole4 的协程实现中，主协程即为 `Reactor` 协程，负责整个 EventLoop 运行。主协程实现事件监听，在 IO 事件完成后唤醒其他工作协程。

**协程挂起：**

在工作协程中执行一些 IO 操作时，底层会将 IO 事件注册到 EventLoop，并让出执行权。

- 嵌套创建的非初代协程，会逐个让出到父协程，直到回到主协程。
- 在主协程上创建的初代协程，会立即回到主协程
- 主协程的 `Reactor` 会继续处理IO 事件，wait 监听新事件（`epoll_wait`）

> 初代协程是在 `EventLoop` 内直接创建的协程，例如 `onReceive` 回调中的内置协程就是初代协程
>
> 

**协程恢复：**

当主协程的 `Reactor` 接收到新的 IO 事件，底层会挂起主协程，并恢复 IO 事件对应的工作协程。该工作协程挂起或退出时，会再次回到主协程。

#### 3.12.5 协程客户端超时规则

> 在swoole版本 >= 4.2.10 下生效

为了统一各个客户端混乱的超时规则，避免开发者需要处处谨慎设置，从 4.2.10 版本开始，所有协程客户端统一超时规则如下：

###### 全局Socket超时配置项

一下配置项可通过 Co::set 方法配置，如

```php
Co::set([
    'socket_connect_timeout' => 1,
    'socket_timeout' => 5,
]);
```

- socket_connect_timeout, 建立 socket 连接超时时间默认为 1秒
- socket_timeout ，socket 读写操作超时时间默认为 `-1`,即永不超时

即：所有协程客户端默认连接超时时间为 1s，其它读写操作默认为永不超时

###### 超时时间设置规则

- -1 : 永不超时
- 0 : 不更改超时时间
- 其他正 int 值 : 设置相应秒数的超时定时器，最大进度为 1毫秒 

###### 生效范围

- Co::set => 全局
- 通过 set 等方法设置 => 所处的客户端
- 读写方法的函数传参 => 所在方法的读写操作内

###### php官方的网络库超时规则

在swoole 中很多 php 官方提供的 网络库 API 也可以协程化成 异步非阻塞 IO,他们的超时时间受 `default_socket_timeout` 配置影响，开发者可以通过 `ini_set('default_socket_timeout'， 60)` 来单独设置，它的默认值是 60.

#### 3.12.6 协程执行流程

协程执行的流程遵循一下规则：

- 协程没有 IO 等待，正常执行php 代码，不会产生执行流程切换
- 协程遇到 IO 等待会立即将控制权切换，待 IO 操作完成后，重新将执行流切回到原来协程所切出的点
- 协程并行协程依次执行，
- 协程嵌套执行流程是由外向内，逐层进入，直到发生 IO，然后再逐层由内向外切到外邻的协程，父协程不会等待子协程结束。

###### 无IO等待

> 正常执行 php 代码，不会产生执行流程切换。
>
> 无IO 操作的协程，相当于一次 php 函数调用

```php
echo "main start\n";
go(function() {
    echo "coro " . co::getcid() . " start\n";
});
echo "end\n";
/*
main start
coro 1 start
end
*/
```

###### IO 等待

> 立即将控制权切出，等 IO 完成后，重新将执行流切回到原来协程切出去的点。
>
> ```php
> echo "main start\n";
> go(function() {
>     echo "coro " . co::getcid() . " start\n";
>     co::sleep(.1);   // switch，切出控制权
>     echo "coro " . co::getcid() . " end\n";
> });
> echo " end\n";
> /*
> main start
> coro 1 start
> end
> coro 1 end
> */
> ```
>
> ###### 协程并行
>
> 多个协程其实是串行依次执行的。
>
> ```php
> echo "main start\n";
> go(function() {
>     echo "coro " . co::getcid() . " start\n";
>     co::sleep(.1);   // switch, 切除控制权
>     echo "coro " . co::getcid() . " end\n";
> });
> echo "main flag\n";
> go(function() {
>     echo "coro " . co::getcid() . " start\n";
>     co::sleep(.1);
>     echo "coro " . co::getcid() . " end\n";
> });
> echo " end \n";
> /*
> main start
> coro 1 start
> main flag
> coro 2 start
> end
> coro 1 end
> coro 2 end
> */
> ```
>
> ###### 协程的嵌套
>
> 协程的执行流程是由外向内逐层进入，直到发生IO操作，然后从内向外切回外邻协程，父协程不会等待子协程结束。
>
> ```php
> echo "main start\n";
> go(function() {
>     echo "coro " . co::getcid() . " start\n";
>     go(function() {
>         echo "coro " . co::getcid() . " start\n";
>         co::sleep(.1);
>         echo "coro " . co::getcid() . " end\n";
>     });
>     echo "coro " . co::getcid() . " dont wait child coroutine\n";
>     co::sleep(.2);
>     echo "coro " . co::getcid() . " end\n";
> });
> echo "end\n";
> /*
> main start
> coro 1 start
> coro 2 start
> coro 1 do not wait children coroutine
> end
> coro 2 end
> coro 1 end
> */
> ```
>
> ```php
> echo "main start\n";
> go(function() {
>     echo "coro " . co::getcid() . " start\n";
>     go(function() {
>         echo "coro " . co::getcid() . " start\n";
>         co::sleep(.2);
>         echo "coro " . co::getcid() . " end\n";
>     });
>     echo "coro " . co::getcid() . " dont wait child coroutine\n";
>     co::sleep(.1);
>     echo "coro " . co::getcid() . " end\n";
> });
> echo "end\n";
> /*
> main start
> coro 1 start
> coro 2 start
> coro 1 do not wait children coroutine
> end
> coro 1 end
> coro 2 end
> */
> ```
>
> 

### 3.13 注意点

###### 范式

- 协程内部禁止使用全局变量
- 协程使用 `use` 关键字引入外部变量到当前作用域时，禁止使用引用方式
- 协程之间进行通信必须使用通道 `channel`

换句话说，协程间通信不要使用全局变量或者引用外部变量到局部作用域，而要使用 channel

- 项目中如果有扩展 `hook` 了 `zend_execute_ex` 或者 `zend_execute_internal` ，特别需要注意 C 栈，可以用 `co::set` 重新设置 C 栈大小

`hook` 这两个入口函数之后，大部分情况下会把平坦的php指令调用变为 C 函数调用，增加 C 栈的消耗。

###### 与其他php扩展的冲突

因为某些跟踪调试的 php 扩展大量使用了 全局变量，可能会导致 swoole 协程发生崩溃。这些扩展有： xdebug, phptrace, aop, molten, xhprof, phalcon(swoole的协程无法运行在 phalcon 框架下)

###### 以下行为可能导致严重错误

- 在多个协程间共用一个连接
- 使用类静态变量 / 全局变量  来保存上下文

####  3.13.1 在多个协程间共用同一个协程客户端

与同步阻塞程序不同，协程是并发处理请求的，因此同一事件可能会有多个请求在并行处理，一旦共用客户端连接，就会导致不同协程之间发生数据错乱。

###### 错误的用法

```php
$server = new Swoole\Http\Server('127.0.0.1', 9501);
$server->on('Receive', function($serv, $fd, $rid, $data) {
    $redis = RedisFactory::getRedis();
    $result = $redis->hgetall('key');
    $resp->end(var_export($result, true));
});

$server->start();

class RedisFactory
{
    private static $_redis = null;
    public static function getRedis()
    {
        if(self::$_redis === null) {
            $redis = new \Swoole\Coroutine\Redis();
            $redis->connect('127.0.0.1', 6379);
            self::$_redis = $redis;
        }
        return self::$_redis;
    }
}
```

###### 避免严重错误，我们可以这样写

```php
// 基于 `SplQueue` 实现协程客户端的连接池，可以复用协程客户端，实现长连接。
$pool = new RedisPool();
$server = new Swoole\Http\Server('127.0.0.1', 9501);
$server->set([
    // 如果开启异步安全重启，需要在 workerExit 释放连接池资源
    'reload_async' => true
]);
$server->on('start', function(swoole_http_server $server) {
    var_dump($server->master_pid);
});
$server->on('workerExit', function(swoole_http_server $server) use ($pool) {
    $pool->destruct();
});
$server->on('request', function(swoole_http_request $request, swoole_http_response $response) use ($pool) {
    // 从连接池中获取一个 Redis 协程客户端
    $redis = $pool->get();
    // connect fail
    if($redis === false) {
        $response->end('error');
        return;
    }
    $result = $redis->hgetall('key');
    $response->end(var_export($result, true));
    // 释放客户端，其他协程可复用此对象
    $pool->put($redis);
});

$server->start();

class RedisPool
{
    protected $available = true;
    protected $pool;
    
    public function __construct()
    {
        $this->pool = new SplQueue;
    }
    
    public function put($redis)
    {
        $this->pool->push($redis);
    }
    
    /**
     * @return bool|mixed|\Swoole\Coroutine\Redis
     */
    public function get()
    {
        // 当有空闲连接且连接池处于可用状态
        if($this->available && count($this->pool) > 0) {
            return $this->pool->pop();
        }
        // 没有空闲连接时，创建新连接
        $redis = new Swoole\Coroutine\Redis();
        $res = $redis->connect('127.0.0.1', 6379);
        if($res == false)
            return false;
        else
            return $redis;
    }
    
    public function destruct()
    {
        // 连接池销毁，将其置为不可用状态，防止新的客户端进入常驻连接池，导致服务器无法平滑地退出
        $this->available = false;
        while(!$this->pool->isEmpty()) {
            $this->pool->pop();
        }
    }
}
```



#### 3.13.2 禁止使用协程API的场景（2.x版本）

在 `ZendVM` 中，魔术方法，反射函数，`call_user_func`, `call_user_func_array` 是由 C 函数实现，并未 `opcode` ，这些操作可能会与 `swoole` 底层的协程调度产生冲突。因此禁止在这些地方使用协程的 API，我们最好使用 php 提供的动态函数调用语法来实现上述之类的功能。

> 在swoole4+ 版本已解决此问题，可以在任意函数中使用协程，下列禁用场景仅适用于 swoole 2.x 版本

`__get()`, `__set()`, `__call()`, `__callStatic`, `__toString`, `__invoke`, `__destruct`, `call_user_func`, `call_user_func_array`, `ReflectionFunction::invoke`, `ReflectionFunction::invokeArgs`, `ReflectionMethod::invoke`, `ReflectionMethod::invokeArgs`, `array_walk / array_map`

###### 字符串函数

```php
// 错误写法
$func = 'test';
$retval = call_user_func($func, 'hello');
```

```php
// 正确写法
$func = 'test';
$retval = $func('hello');
```

###### 对象方法

```php
// 错误的写法
$retval = call_user_func(array($obj, 'test'), 'hello');
$retval = call_user_func_array(array($obj, 'test'), 'hello', array(1, 2, 3));
```

```php
// 正确写法
$method = 'test';
$args = array(1, 2, 3);
$retval = $obj->$method('hello');
$retval = $obj->$method('hello', ... $args);
```



#### 3.13.3 使用类静态变量/全局变量保存上下文

多个协程是并发执行的，因此不能使用类静态变量 / 全局变量 来保存协程上下文内容。使用局部变量是安全的，因为局部变量的值会自动保存在协程栈中，其他协程访问不到协程的局部变量。

###### sample code

```php
// 错误写法
$_array = [];
$serv->on('Request', function($request, $response) {
    global $_array;
    // 请求 /a (协程1)
    if($request->server['request_uri'] == '/a') {
        $_array['name'] = 'a';
        co::sleep(1.0);
        echo $_array['name'];
        $response->end($_array['name']);
    }
    // 请求 /b (协程2)
    else {
        $_array['name'] = 'b';
        $response->end();
    }
});
```

发起两个并发请求

```shell
curl http://127.0.0.1:9501/a
curl http://127.0.0.1:9501/b
```

协程1中设置了全局变量 `$_array['name']` 的值为 `a`， 协程1 调用co::sleep 挂起，然后协程2执行，将 `$_array['name']` 的值设为 b，协程2 结束。这是定时器返回，底层恢复协程1 的运行，而协程1的逻辑中有一个上下文的依赖关系。当再次打印  `$_array['name']` 的值时，程序预期是 a，但这个值已被协程2所修改，所以实际结果是 b，这样就造成逻辑错误.

同理, 使用类静态变量 `class::$array`, 全局对象属性 `$object->array`, 其他超全局变量 `$GLOBALS` 等，进行上下文保存在协程程序中是非常危险的，可能会出现不符合预期的行为。

###### 使用 Context 管理上下文

- 可以使用一个 `Context` 类来管理协程的上下文，在 `Context` 类中，使用 `Coroutine::getUid` 获取协程 `ID`, 然后隔离不同协程之间的全局变量
- 协程退出时，清理上下文数据

Context:

```php
use Swoole\Coroutine;
class Context
{
    protected static $pool = [];
    static function get($key)
    {
        $cid = Coroutine::getUid();
        if($cid < 0)
            return null;
        if(isset(self::$pool[$cid][$key])) {
            return self::$pool[$cid][$key];
        }
        return null;
    }
    
    static function put($key, $item)
    {
        $cid = Coroutine::getuid();
        if($cid > 0)
            self::$pool[$cid][$key] = $item;
    }
    
    static function delete($key = null)
    {
        $cid = Coroutine::getuid();
        if($cid > 0) {
            if($key)
                unset(self::$pool[$cid][$key]);
            else
                unset(self::$pool[$cid]);
        }
    }
}
```

```php
$serv->on('Request', function($request, $response) {
    if($request->server['request_uri'] == '/a') {
        Context::put('name', 'a');
        co::sleep(1.0);
        echo Context::get('name');
        $response->end(Context::get('name'));
        // 退出协程时清理
        Context::delete('name');
    } else {
        Context::put('name', 'b');
        $response->end();
        // 退出协程时清理
        Context::delete();
    }
});
```



#### 3.13.4 退出协程

#### 3.13.5 异常处理

在协程编程中，可以直接使用 `try/catch` 处理异常。但必须在协程内捕获，不能跨协程捕获异常

###### sample code

```php
// 在协程内捕获异常
function test()
{
    throw new \RuntimeException(__FILE__, __LINE__);
}
Swoole\Coroutine::create(function () {
    try{
        test();
    } catch (\Throwable $e) {
     	echo $e;   
    }
});
```

### 3.14 扩展组件

#### 3.14.1 MongoDB

### 3.15 编程调试

## 4. Runtime

swoole4.0 底层增加了一个新的特性，可以在运行时动态地将基于 `php_stream` 实现的扩展和 `php` 网络客户端代码一键协程化。底层替换了 `ZendVM` `Stream` 的函数指针，所有使用 `php_stream` 运行的 `socket` 的操作均变成协程调度的异步 IO

目前有php原生的 `Redis`, `PDO`, `MySQLi` 协程化的支持。

###### 函数原型

```php
function Runtime::enableCoroutine(bool $enable = true, int $flags = SWOOLE_HOOK_ALL);
```

- `$enable` : 打开或关闭协程
- `$flags` : 选择要 `Hook` 的类型，可以多选，默认为全选。仅在 `$enable = true` 时有效

###### 可用列表

- `redis` 扩展
- 使用 `mysqlnd` 模式的 `pdo`, `mysqli` 的扩展，如果未启用 `mysqlnd`, 将不支持协程化
- `soap` 扩展
- `file_get_contents`, `fopen`
- `stream_socket_client` (predis)
- `stream_socket_server`
- `fsockopen`

###### 不可用列表

- `mysql`: 底层使用 `libmysqlclient`
- `curl`: 底层使用 `libcurl` （即不能使用 `CURL` 驱动的 `Guzzle`）
- `mongo` : 底层使用 `mongo-c-client`
- `pdo-pgsql`
- `pdo-ori`
- `pdo-odbc`
- `pdo-firebird`

###### sample code

```php
Swoole\Runtime::enableCoroutine();
go(function() {
    $redis = new redis;
    $retval = $redis->connect('127.0.0.1', 6379);
    var_dump($retval, $redis->getLastError());
    var_dump($redis->get("key"));
    var_dump($redis->set("key", "value2"));
    var_dump($redis->get("key"));
    $redis->close();
});
```

###### 方法放置的位置

调用方法后，当前进程内全局生效，一般放在整个项目最开头，以期获得100% 覆盖的效果，协程内外会自动切换模式，不会影响php原生环境的使用。

注意：不建议在 onRequest 等回调中开启，会多次调用造成不必要的调用开销。

### 4.1 文件操作

swoole4 增加了对文件操作的 `Hook`, 在运行时开启协程后，可以将文件读写的 `IO` 操作转为协程模式。

底层使用了 `AIO` 线程池模拟实现，在 `IO` 完成时唤醒对应协程。

###### 可用列表

`foepn`, `fread`/`fgets`, `fwrite`/`fputs`, `file_get_contents`/`file_put_contents`, `unlink`, `mkdir`, `rmdir`

###### sample code

```php
Swoole\Routine::enableCoroutine(true);
go(function() {
    $fp = fopen('test.log', 'a+');
    fwrite($fp, str_repeat('A', 2048));
    fwrite($fp, str_repeat('B', 2048));
    fclose($fp);
});
```



### 4.2 睡眠函数

swoole4 增加了对 `sleep` 函数的 `Hook`, 底层替换了 `sleep`, `usleep`, `time_nanosleep`, `time_sleep_until` 四个函数。

当调用这些睡眠函数时，会自动切换为协程定时器调度，不会阻塞进程。

###### sample code

```php
Swoole\Runtime::enableCoroutine(true);
go(function() {
    sleep(1);
    echo "sleep 1s\n";
    usleep(1000);
    echo "sleep 1ms\n";
});
```

###### 例外情况

由于底层的定时器最小粒度是 `1ms`, 因此使用 `usleep` 等高精度睡眠函数时，如果设置为低于 `1ms` 时，将直接使用 `sleep` 系统调用。可能会引起非常短暂的睡眠阻塞。

### 4.3 开关选项

swoole 4 版本中，`Runtime::enableCoroutine` 增加了第二个参数，可以设置开关选项，选择要 `Hook` 哪些php函数

###### 支持的选项

- `SWOOLE_HOOK_SLEEP`: 睡眠函数
- `SWOOLE_HOOK_FILE`: 文件操作 `stream`
- `SWOOLE_HOOK_TCP`: `TCP Socket` 类型的 `stream`
- `SWOOLE_HOOK_UDP` : `UDP Socket` 类型的 `stream`
- `SWOOLE_HOOK_UNIX` : `Unix Stream Socket` 类型的 `stream`
- `SWOOLE_HOOK_UDG` : `Unix Dgram Socket` 类型的 `stream`
- `SWOOLE_HOOK_SSL` : `SSL Socket` 类型的 `stream`
- `SWOOLE_HOOK_TLS` : `TLS Socket` 类型的 `stream`
- `SWOOLE_HOOK_ALL` : 打开所有类型 

###### sample code

```php
Swoole\Runtime::enableCoroutine(true, SWOOLE_HOOK_SLEEP);
go(function() {
    sleep(1);
    // 注意仅 hook 了睡眠函数，下面的文件操作函数会导致阻塞
    $fp = fopen('test.log', 'a+');
    fwrite($fp, str_repeat('A', 2048));
    fwrite($fp, str_repeat('B', 2048));
    fclose();
});
```

###### 关闭协程

调用 `Runtime::enableCoroutine(false)` 关闭上一次设置的所有选项协程 `Hook` 设置。

注意关闭操作不接受第二个参数，底层会判断上一次打开时设置的选项列表，关闭对应的协程 `Hook` 设置

### 4.4 严格模式

> 注意：严格模式和 `enableCoroutine` 存在冲突，不要同时启用

在 swoole4 版本以后，开启严格模式以后，调用常用的阻塞 IO 的函数和方法会出现警告。

###### function prototype

```php
function Runtime::enableStrictMode();
```

###### sample code and warning

```php
Swoole\Runtime::enableStrictMode();
sleep(1);		// Warning: sleep() has been disabled for security reasons in strictmode.php on line 8
```



## 5. Timer

毫秒精度的定时器。底层基于 `epoll_wait` 和 `setitimer` 实现，数据结构使用 `最小堆`，可支持添加大量定时器。

- 在同步进程中使用 `setitimer` 和信号实现，如 `Manager` 和 `TaskWorker` 进程
- 在异步进程中使用 `epoll_wait`/`kevent`/`poll`/`select` 超时时间实现

###### 性能

底层使用最小堆数据结构实现定时器，定时器的添加和删除，全部为内存操作，因此性能是非常高的。官方的

[基准测试脚本]: https://github.com/swoole/swoole-src/blob/master/benchmark/timer.php

 中添加或删除 `10万` 个随机时间的定时器耗时为 `0.08s` 左右。

```php
~/workspace/swoole/benchmark$ php timer.php
add 100000 timer :0.091133117675781s
del 100000 timer :0.084658145904541s
```

>  定时器是内存损耗，而没有IO损耗

###### 差异

`Timer` 和 `PHP` 本身的 `pcntl_alarm` 是不同的。`pcntl_alarm` 是基于时钟信号 + `tick` 函数实现，存在一些如下几点缺点：

- 最大进度是秒级，而 `Timer` 最大进度是毫秒
- 不支持同时设定多个定时器程序
- `pcntl_alarm` 依赖于 `declare(ticks = 1)`, 性能很差

###### 零毫秒定时器

底层不支持时间参数为 `0` 的定时器。这与 `Node.js` 等编程语言不同，在`Swoole` 里可以使用 `Swoole\Event::defer` 实现类似功能。

```php
Swoole\Event::defer(function () {
    echo "hello\n";
});
```

上述代码与 `JS` 中的 `setTimeout(0, func)` 效果是完全一致的。

### 5.1 swoole_timer_tick

设置一个间隔时钟定时器，与 `after` 定时器不同的是 `tick` 定时器会持续触发，直到调用 `swoole_timer_clear` 清楚这个 timer

```php
int swoole_timer_tick(int $mesc, callable $callback, [$mixed $param]);
```

- `$mesc` 指定时间，单位为毫秒。如 `1000` 表示 `1` 秒，最大不得超过 `86400000`
- `$callback_function` 时间到期后所执行的函数，必须是可以调用的
- 可以使用匿名函数的 `use` 语法传递参数到回调函数中
- 定时器仅在当前进程空间内有效
- 定时器是纯异步实现。不能与阻塞 IO 的函数一起使用，否则定时器的执行时间会发生错乱

定时器在执行的过程中可能存在一定误差

###### 回调函数

```php
function callbackFunction(int $timer_id, [$mixed $param]);
```

- `$timer_id` 定时器的 ID，可用于 `swoole_timer_clear` 清除此定时器
- `$params` 由 `swoole_timer_tick` 传入的第三个参数 $param, 此参数也为可选参数

###### 定时器校正

定时器回调函数的执行时间不影响下一次定时器执行的时间。

比如，在 0.002s 时设置了 10ms 的 tick 定时器，第一次会在 0.012s 执行回调函数，如果回调函数执行了 5ms，下一次定时器仍然会在 0.022s 时触发，而不是 0.027s 。

然而如果定时器回调函数的执行时间过于长，延申到了下一次定时器执行的时间。底层会进行时间校正，丢弃已过期的**欲定时执行**的行为，在下一时间间隔点进行回调。比如上面的例子中，0.012s 时的回调函数执行了 15ms， 本该在 0.022s 产生的回调，实际上直到 0.027s 时才返回。那么 0.022s 时本欲执行的行为，就被“丢弃”了。再到下一个间隔即 0.032s 继续执行回调。

###### 协程模式

在协程环境下，`swoole_timer_tick` 回调中会自动创建一个协程，可以直接使用协程相关 API，无需调用 `go` 创建协程。

> 可设置 `enable_coroutine` 关闭自动创建协程

###### sample code

```php
// 1
swoole_timer_tick(1000, function() {
    echo "test timer.\n";
});
// 2
swoole_timer_tick(3000, function() {
    echo "after 3000ms.\n";
    swoole_timer_after(14000, function() {
        echo "after 14000ms.\n";
    });
});
```



### 5.2 swoole_timer_after

在指定的时间后执行函数

```php
int swoole_timer_after(int $after_timer_ms, mixed $callback_function);
```

`swoole_timer_after` 函数是一个一次性定时器，执行完成后就会销毁。此函数与 php 标准库提供的 `sleep`  函数不同，`after` 是非阻塞的。而 `sleep` 调用后会导致当前进程进入阻塞，将无法处理新的请求。

执行成功返回定时器 ID，若取消定时器，可调用 `swoole_timer_clear`

- `$after_time_ms` 最大步地超过 86400000，即一天
- `$callback_function` 时间到期后所执行的函数，必须是可以调用的。
- 可以使用 匿名函数的 `use` 方法传参到回调函数中

###### 协程模式

在协程模式下，`swoole_timer_after` 回调中会自动创建一个协程，可直接使用协程相关 API，无需调用 `go` 来创建协程。

> 可设置 `enable_coroutine` 关闭自动创建协程

###### sample code

```php
swoole_timer_after(1000, function () use ($str) {
    echo "timeout, " . $str . "\n";
});
```



### 5.3 swoole_timer_clear

使用定时器ID来删除定时器

```php
bool swoole_timer_clear(int $timer_id);
```

- `$timer_id` : 定时器ID，调用 `swoole_timer_tick`, `swoole_timer_after` 后会返回一个int 的 timer_id
- `swoole_timer_clear` 不能用来清除其他进程的定时器，只能作用于当前进程

###### sample code

```php
$timer = swoole_timer_after(1000, function () {
    echo "timeout\n";
});
var_dump(swoole_timer_clear($timer));  // bool(true)
var_dump($timer);   // int(1)
```



## 6. Memory

### 6.1 Lock

### 6.2 Buffer

### 6.3 Table

Swoole\Table 是一个基于共享内存和锁实现的超高性能，并发数据结构。用于解决多进程/多线程数据共享和同步加锁问题。

最新版本已移除 `lock` 和 `unlock` 方法，改用 `Swoole\Lock` 来实现数据同步

请谨慎使用数组方式去读写 `swoole_table`, 建议使用文档中提供的 API 来进行操作，数组方式取出的 `swoole_table_row` 对象是一次性对象，请勿依赖其进行过多操作。

###### swoole_table 的优势

- 性能强，单线程每秒读写可达 `200万` 次
- 引用代码无需加锁，`swoole_table` 内置行锁自旋锁，所有操作均是多线程 / 多进程安全。用户层完全不需要考虑数据同步问题。
- 支持多进程，`swoole_table` 可用于多进程之间共享数据
- 使用行锁，而不是全局锁，仅当2个进程在同一个 CPU 时间下并发读取同一条数据时才会发生 “抢锁” 现象。

> `swoole_table` 不受 php 的memory_limit 控制
>
> `swoole_table` 在 1.7.5 以上可用

###### 遍历Table

swoole_table 类实现了迭代器和 Countable 接口，可以使用 foreach 遍历，使用count 计算当前行数。

> 遍历Table 依赖 pcre，如果发现无法遍历 swoole_table，先查下有没安装 pcre-devel 扩展

```php
foreach($table as $row)
{
    var_dump($row);
}
echo count($table);
```



### 6.4 Atomic

Swoole\Atomic 是 swoole 扩展提供的原子技术操作类，可以方便整数的无锁原子增减。

- Swoole\Atomic 使用共享内存，可以在不同的进程之间操作计数
- Swoole\Atomic 基于 gcc 提供的CPU 原子指令，无需加锁
- Swoole\Atomic 在服务器程序中必须在 `swoole_server->start` 前创建才能在 Worker 进程中使用
- Swoole\Atomic 默认使用32位无符号整型，如要使用64位无符号整型，可以改用 Swoolen\Atomic\Long

注意：请勿在 `onReceive` 等回调函数中创建原子数，否则底层的 `GlobalMemory` 内存会持续增长，造成内存泄漏

###### 64位长整型

swoole 1.9.20增加了对64位有符号长整型原子计数的支持。使用 `new Swoole\Atomic\Long` 来创建。

- `Swoole\Atomic\Long` 不支持 `wait` 和 `wakeup` 方法

###### sample code

```php
$atomic = new swoole_atomic(123);
echo $atomic->add(12) . "\n";
echo $atomic->sub(11) . "\n";
echo $atomic->cmpset(122, 999) . "\n";
echo $atomic->cmpset(124, 999) . "\n";
echo $atomic->get() . "\n";
```



### 6.5 mmap

### 6.6 Channel

### 6.7 Serialize

## 7. Http\Server

> `Http\Server` 对 `Http` 协议的支持不完整，建议仅作为应用服务器，并且在前端增加 `Nginx` 作为代理

swoole包含了内置 http 服务器，通过同步的代码即可造出异步非阻塞多进程的 http 服务器

```php
$http = new Swoole\Http\Server('127.0.0.1', 9501);
$http->on('request', function($request, $response) {
    $response->end("<h1>Hello Swoole. #" . rand(1000, 9999) . "<h1>");
});
$http->start();
```

###### 使用 http2 协议

- 需要依赖 `nghttp2` 库，下载 nghttp2 后编译安装
- 使用 SSL 下的 `Http2` 协议必须安装 `openssl` ，且需要高版本 `openssl` 必须支持 `TLS1.2`，`ALPN`, `NPN`
- 使用HTTP2不一定要开启SSL

```shell
./configure --enable-openssl --enable-http2
```

设置 http 服务器的 `open_http2_protocol` 为 `true`

```php
$serv = new Swoole\Http\Server("127.0.0.1", SWOOLE_PROCESS, SWOOLE_SOCK_TCP | SWOOLE_SSL);
$serv->set([
    'ssl_cert_file' => $ssl_dir . '/ssl.crt',
    'ssl_key_file' => $ssl_dir . '/ssl.key',
    'open_http2_protocol' => true,
]);
```

###### nginx+swoole配置

```ini
server {
    root /data/wwwroot/;
    server_name local.swoole.com;
    
    location / {
            proxy_http_version 1.1;
            proxy_set_header Connection "keep-alive";
            proxt_set_header X-Real-IP $remote_addr;
        if (!-e $request_filename) {
            proxy_pass http://127.0.0.1:9501；
        }
    }
}
```

> 通过读取 `$request->header['x-real-ip']` 来获取客户端的真实IP

## 8. WebSocket\Server

swoole内置了 `WebSocket` 服务器支持，通过几行 php 代码就可以写出一个异步非阻塞多进程的 `WebSocket` 服务器。

```php
$server = new Swoole\WebSocket\Server('0.0.0.0', 9501);

$server->on('open', function(Swoole\WebSocket\Server $server, $request) {
    echo "Server: handshare success with fd{$request->fd}\n";
});

$server->on('message', function(Swoole\WebSocket\Server $server, $frame) {
    echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
    $server->push($frame->fd, "this is server");
});

$server->on('close', function($ser, $fd) {
    echo "client {$fd} closed\n";
});

$server->start();
```

###### onRequest回调

`WebSocket\Server` 继承自 `Http\Server`

- 设置了 `onRequest` 回调，`WebSocket\Server` 也可以同时作为 `http` 服务器
- 未设置 `onRequest` 回调， `WebSocket\Server` 收到http请求后会返回 400 错误
- 如果想通过接收 http 触发所有 `websocket` 的推送，需要注意作用域的问题，面向过程请使用 `global` 对 `WebSocket\Server` 进行引用，面向对象可以把 `WebSocket\Server` 设置成一个成员属性

**1.面向过程的写法**

```php
$server = new Swoole\WebSocket\Server('0.0.0.0', 9501);
$server->on('open', function(Swoole\WebSocket\Server $server, $request) {
    echo "Server: handshake success with fd{$request->fd}\n";
});
$server->on('message', function(Swoole\WebSocket\Server $server, $frame) {
    echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
    $server->push($frame->fd, "this is server");
});
$server->on('close', function($ser, $fd) {
    echo "client {$fd} closed\n";
});
$server->on('request', function(Swoole\Http\Request $request, Swoole\Http\Response $response) {
    global $server; // 调用外部的 server
    // $server->connections 遍历所有的 websocket 连接用户的 fd，给所有用户推送
    foreach($server->connections as $fd) {
        $server->push($fd, $request->get['message']);
    }
});
$server->start();
```

**2.面向对象的写法**

```php
class WebSocketTest {
    public $server;
    public function __construct() {
        $this->server = new Swoole\WebSocke\Server('0.0.0.0', 9501);
        $this->server->on('open', function(swoole_websocket_server $server, $request) {
            echo "Server: handshake success with fd{$request->fd}\n";
        });
        $this->server->on('message', function(Swoole\WebSocket\Server $server, $frame) {
            echo "receive from {$frame->fd}:{$frame->data},opcode:{$frame->opcode},fin:{$frame->finish}\n";
            $server->push($frame->fd, "this is server");
        });
        $this->server->on('close', function($ser, $fd) {
            echo "client {$fd} closed\n";
        });
        $this->server->on('request', function($request, $response) {
            // 接收 http 请求从 get 获取message参数的值，给用户推送
            // $this->server->connections 遍历所有 websocket 连接用户的 fd, 给所有用户推送
            foreach($this->server->connections as $fd) {
                $this->server->push($fd, $request->get['message']);
            }
        });
        $this->server->start();
    }
}
new WebsocketTest();
```

###### 客户端

- `chrome`/`Firefox` / 高版本`ie`/`Safari` 等浏览器内置了 JS 的 websocket 客户端
- 微信小程序开发框架内置了 websocket 客户端
- 异步的 php 程序中可以使用 `Swoole\Http\Client` 作为 websocket 客户端
- apache/php-fpm 或其他同步阻塞的 php程序中可以使用  `swoole/framework` 提供的同步WebSocket 客户端
- 非 websocket 客户端不能与 `SebSocket` 服务器通信

## 9. Redis\Server

`swoole1.8.14`开始增加了一个兼容 `Redis` 服务端协议的 server 框架，可以基于此框架实现 `Redis` 协议的服务器程序。`Swoole\Redis\Server` 继承自 `Swoole\Server` ，可调用父类提供的所有方法。

`Redis\Server` 不需要设置 `onReceive` 回调。

[实例程序]: https://github.com/swoole/swoole-src/blob/master/examples/redis/server.php

###### 可用的客户端

- 任意编程语言的 redis 客户端，包括php的redis扩展和phpredis库
- swoole扩展提供的异步redis客户端
- redis提供的命令行工具，包括 `redis-cli`，`redis-benchmark`

###### 协程

在 swoole2.0 协程版本中，无法使用 `return` 返回值的方式发送响应结果。可以使用 `$server->send` 方式发送数据。

```php
use Swoole\Redis\Server;
use Swoole\Coroutine\Redis;

$serv = new Server('0.0.0.0', 10086, SWOOLE_PROCESS, SWOOLE_SOCK_TCP);
$serv->setHandler('set', function($fd. $data) use ($serv) {
    $cli = new Redis;
    $cli->connect('0.0.0.0', 6379);
    $cli->set($data[0], $data[1]);
    
    $serv->send($fd, Server::format(Server::INT, 1));
});

$serv->start();
```

###### Redis\Server::setHandler

`Swoole\Redis\Server` 继承自 `Swoole\Server`, 可以使用父类提供的所有方法。

`Redis\Server` 不需要设置 `onReceive` 回调。只需使用 `setHandler` 方法设置对应命令的处理函数，收到未支持的命令后自动向客户端发送 `ERROR` 响应，消息为 `ERR unknown command '$command'`

setHandler : 设置Redis 命令字的处理器。

```php
function Redis\Server->setHandler(string $command, callable $callback);
```

- `$command` 命令的名称
- `$callback` 命令的处理函数，回调函数返回字符串类型时会自动发送给客户端
- `$callback` 返回的数据必须为 `Redis` 格式，可使用 `format` 静态方法进行打包 

sample code：

```php
use Swoole\Redis\Server;

$server = new Server('127.0.0.1', 9501);

// 同步模式
$server->setHandler('Set', function($fd, $data) use ($server) {
    $server->array($data[0], $data[1]);
    return Server::format(Server::INT, 1);
});

// 异步模式
$server->setHandler('Get', function ($fd, $data) use ($server) {
    $db->query($sql, function ($db, $result) use ($fd) {
        $server->send($fd, Server::format(Server::LIST, $result));
    });
});

$server->start();
```

客户端实例：

```shell
redis-cli -h 127.0.0.1 -p 9501 set name rango
```

###### Redis\Server::format

格式化命令响应数据。

```php
function Redis\Server::format(int $type, mixed $value = null);
```

- `$type` 表示数据类型， `NIL` 类型不需要传入 `$value`, `ERROR` 和 `STATUS` 类型 `$value` 可选，`INT`, `STRING`, `SET`， `MAP` 必选

###### 用到的常量

格式化参数常量：

（主要用于 `format` 函数打包 redis 响应数据）

- Server::NIL 返回 `nil` 数据
- Server::ERROR 返回错误码
- Server::STATUS 返回状态
- Server::INT 返回整数， `format` 必须传入参数值，类型必须为整数
- Server::STRING 返回字符串， `format` 必须传入参数值，类型必须为整数
- Server::SET 返回列表，`format` 必须传入参数值，类型必须为数组
- Server::MAP 返回 Map, `format` 必须传入参数值，类型必须为关联索引数组

## 10. Process

swoole 中新增了一个进程管理模块，用来替代 php 的 `pcntl`

需要注意 Process 进程在系统里是非常昂贵的资源，创建进程消耗很大。另外创建的进程过多会导致进程切换开销大幅上升。

```shell
vmstat 1 1000
procs -----------memory---------- ---swap-- -----io---- -system-- ------cpu-----
 r  b   swpd   free   buff  cache   si   so    bi    bo   in   cs us sy id wa st
 0  0      0 8250028 509872 4061168    0    0    10    13   88   86  1  0 99  0  0
 0  0      0 8249532 509872 4061936    0    0     0     0  451 1108  0  0 100  0  0
 0  0      0 8249532 509872 4061884    0    0     0     0  684 1855  1  3 95  0  0
 0  0      0 8249532 509880 4061876    0    0     0    16  492 1332  0  0 99  0  0
 0  0      0 8249532 509880 4061844    0    0     0     0  379  893  0  0 100  0  0
 0  0      0 8249532 509880 4061844    0    0     0     0  440 1116  0  0 99  0  0
```

php 自带的 `pcntl` 存在以下缺点：

- `pcntl` 没有提供进程间通信
- `pcntl` 不支持重定向标准输入和输出
- `pcntl` 只提供了 `fork` 这种原始的接口，使用上容易出错
- swoole_process 提供了比 `pcntl` 跟强大的功能，更易用的API，是php在多进程编程上更轻松

swoole\process 提供了如下特性：

- 基于 `unix socket` 和 `sysvmsg` 消息队列的进程间通信，只需调用 `write/read` 或 `push/pop` 即可
- 支持重定向标准输出和输入，在子进程内 echo 不会打印屏幕，而是写入 channel，读键盘输入可以重定向为从 channel 读取数据
- 配合Event模块，创建的 php 子进程可以异步地驱动事件
- 提供了 `exec` 接口，创建的进程可以执行其他程序，与原php父进程之间可以方便地通信。

###### sample code 

- 子进程异常退出时自动重启
- 主进程异常退出时，子进程会继续执行，完成所有任务后退出

```php
(new class{
    public $mpid = 0;
    public $works = [];
    public $max_process = 1;
    public $new_index = 0;
    
    public function __construct()
    {
        try{
            swoole_set_process_name(sprintf('php-ps:%s', 'master'));
            $this->mpid = posix_getpid();
            $this->run();
            $this->processWait();
        }catch(\Exception $e) {
            die('All ERROR:' . $e->getMessage());
        }
    }
    
    public function run()
    {
        for($i = 0; $i < $this->max_process; $i++) {
            $this->CreateProcess();
        }
    }
    
    public function CreateProcess($index = null)
    {
        $process = new swoole_process(function (swoole_process $worker) use ($index) {
            if(is_null($index)) {
                $index = $this->new_index;
                $this->new_index++;
            }
            swoole_set_process_name(sprintf('php-ps:%s', $index));
            for($j = 0; $j < 16000; $j++) {
                $this->checkMpid($worker);
                echo "msg: {$j}\n";
                sleep(1);
            }
        }, false, false);
        $pid = $process->start();
        $this->works[$index] = $pid;
        return $pid;
    }
    
    public function checkMpid(&$worker)
    {
        if(!swoole_process::kill($this->mpid, 0)) {
            $worker->exit();
            // log
            echo "Master process exited, I [{$worker['pid']}] also quit\n";
        }
    }
    
    public function rebootProcess($ret)
    {
        $pid = $ret['pid'];
        $index = array_search($pid, $this->works);
        if($index !== false) {
            $index = intval($index);
            $new_pid = $this->CreateProcess($index);
            echo "rebootProcess: {$index}={$new_pid} Done\n";
            return;
        }
        throw new \Exception('reboot Process Error: no pid');
    }
    
    public function processWait() {
        while(1) {
            if(count($this->works)) {
                $ret = swoole_process::wait();
                if($ret) {
                    $this->rebootProcess($ret);
                }
            } else {
                break;
            }
        }
    }
});
```



## 11. Process\Pool

进程池，基于 `Server` 的`Manager`模块实现。可管理多个工作进程。该模块的核心功能为**进程管理**，相比 `Process` 实现多进程，`Process\Pool` 更加简单，封装层次更高，开发者无需编写过多代码即可实现进程管理功能。

> 此特性需要2.1.2+

###### 常量定义

- `SWOOLE_IPC_MSGQUEUE`：系统消息队列信息
- `SWOOLE_IPC_SOCKET`：socket 通信

###### 异步支持

- 可在 `onWorkerStart` 中使用swoole 提供的 异步或协程 api，工作进程即可实现异步
- 底层自带的消息队列和 socket 通信均为同步阻塞 IO
- 如果进程为异步模式，则不要使用任何自带的同步 IPC 进程通信功能(无法使用message回调)

> 4.0 版本以下需在 `onWorkerStart` 末尾添加 `swoole_event_wait` 进入事件循环

###### sample code

```php
$workerNum = 10;
$pool = new Swoole\Process\Pool($workerNum);

$pool->on('WorkerStart', function($pool, $workerId) {
    echo "Worker #{$workerId} is started. \n";
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379);
    $key = "key1";
    while(1) {
        $msgs = $redis->brpop($key, 2);
        if($msgs == null)
            continue;
        var_dump($msgs);
    }
});

$pool->on("WorkerStop", function($pool, $workerId) {
    echo "Worker #{$workerId} is stopped. \n";
});
$pool->start();
```

## 12. Client

`client` 提供了 `TCP/UDP` `socket` 的客户端的封装代码，使用时仅需 `new Swoole\Client` 即可。

###### 优势

- `stream` 函数存在超时设置的陷阱和 `Bug`, 一旦没处理好会导致 Server 端长时间阻塞。
- `stream` 函数的 fread 默认最大长度 8192 限制，无法支持 UDP 的大包
- `Client` 支持 `waitall`，在有确定包长度时可一次取完，不必循环去读
- `Client` 支持 `UDP connect`, 解决了 UDP 串包问题
- `Client` 是纯 C 编写。专门处理 `socket` ，`stream` 的复杂函数，性能更好。
- `Client` 支持长连接

除了普通的异步阻塞 + select 的使用方法外，`Client` 还支持异步非阻塞回调。

###### 同步阻塞客户端

```php
$client = new swoole_client(SWOOLE_SOCK_TCP);
if(!$client->connect('127.0.0.1', 9501, -1)) {
    exit("Connect failed. Error: {$client->errCode}\n");
}
$client->send("hello world\n");
echo $client->recv();
$client->close();
```

> `php-fpm/apache` 环境下只能使用同步客户端
>
> apache环境下仅支持 prefork 多进程模式。不支持 prework 多线程

###### 异步非阻塞客户端

```php
$client = new Swoole\Client(SWOOLE_SOCK_TCP, SWOOLE_SOCK_ASYNC);
$client->on('connect', function(swoole_client $cli) {
    $cli->send("GET /HTTP/1.1\r\n\r\n");
});
$client->on('receive', function(swoole_client $cli, $data) {
    echo "Receive: $data";
    $cli->send(str_repeat('A', 100) . "\n");
    sleep(1);
});
$client->on('error', function(swoole_client $cli) {
    echo "error\n";
});
$client->on('close', function(swoole_client $cli) {
    echo "connect close\n";
});
$client->connect('127.0.0.1', 9501);
```

> 异步客户端只能在 cli 下使用

### 12.1 方法列表

###### SSL/TLS

- 依赖 `openssl` 库，需要在编译 swoole 时增加 `enable-openssl` 或 `with-openssl-dir`
- 必须在定义 `Client` 时增加 `SWOOLE_SSL`

> 低于 1.9.5 的版本在设置 `ssl_key_file` 后会自动启用 SSL

```php
$client = new Swoole\Client(SWOOLE_TCP|SWOOLE_ASYNC|SWOOLE_SSL);
```

#### 12.1.1 construct

```php
swoole_client->__construct(int $sock_type, int $is_sync = SWOOLE_SOCK_SYNC, string $key);
```

可以使用 swoole 提供的宏来指定类型，参考本 markdown 文档附录的 `swoole常量定义`

- `$sock_type` 表明 `socket` 的类型，如 `TCP`/`UDP`
- 使用 `$sock_type`/`SWOOLE_SSL` 可启用SSL加密
- `$is_sync` 表示同步阻塞还是异步非阻塞，默认为同步阻塞
- `$key` 用于长连接的 `key`，默认使用 IP ：端口 作为 `key`，相同 `key` 的连接会被复用

###### 在php-fpm / apache 中创建长连接

```php
$cli = new swoole_client(SWOOLE_TCP|SWOOLE_KEEP);
```

加入 `SWOOLE_KEEP` 标志后，创建的 TCP 连接在PHP请求结束或调用 `$cli->close` 时不会关闭，下一次执行 connect 调用时会复用上一次创建的连接。长连接保存的方式默认是以 ServerHost : ServerPort 为 key 的，可以在第三个参数内指定 key。

- `SWOOLE_KEEP`只适用于同步客户端

> > swoole_client 在 unset 时会自动调用 close 方法关闭 socket
>
> 异步模式 unset 时会自动关闭 socket 并从 epoll 事件轮询中移除

###### 在 swoole_server 中使用 swoole_client 

- 必须在事件回调函数中使用 `swoole_client `，不能在 `swoole_server->start` 前就创建。
- `swoole_server` 可以用任何语言编写的 socket client 连接，同样 `swoole_client` 也可以去连接任何语言编写的 socket server

#### 12.1.2 set

#### 12.1.3 on

#### 12.1.4 connect

#### 12.1.5 isConnected

#### 12.1.6 getSocket

#### 12.1.7 getSockName

#### 12.1.8 getPeerName

#### 12.1.9 getPeerCert

#### 12.1.10 send

#### 12.1.11 sendto

#### 12.1.12 sendfile

#### 12.1.13 recv

#### 12.1.14 close

#### 12.1.15 sleep

#### 12.1.16 wakeup

#### 12.1.17 enableSSL

### 12.2 回调函数

#### 12.2.1 onConnect

#### 12.2.2 onError

#### 12.2.3 onReceive

#### 12.2.4 onClose

#### 12.2.5 onBufferFull

#### 12.2.6 onBufferEmpty

### 12.3 属性列表

#### 12.3.1 errCode

#### 12.3.2 sock

#### 12.3.3 reuse

### 12.4 并行

#### 12.4.1 swoole_client_select

#### 12.4.2 TCP客户端异步连接

#### 12.4.3 SWOOLE_KEEP参数创建 TCP 长连接

### 12.5 常量

### 12.6 配置选项

#### 12.6.1 ssl_verify_peer

#### 12.6.2 ssl_host_name

#### 12.6.3 ssl_cafile

#### 12.6.4 ssl_capath

#### 12.6.5 package_length_func

#### 12.6.6 http_proxy_host

### 12.7 常见问题

## 13. Event

 除了异步 `server` 和 `client` 库之外，`swoole` 扩展还提供了直接操作底层 `epoll`/`kqueue` 事件循环的接口。可将其他扩展创建的 `socket` ，php代码中 `stream` / `socket` 扩展创建的 `socket` 等加入到 `Swoole` 的 `EventLoop` 中。

###### 事件优先级

1. 通过 `Process::signal` 设置的信号处理回调函数
2. 通过 `Event::defer` 设置的延迟执行函数
3. 通过 `Timer::tick` 和 `Timer::after` 设置的定时器回调
4. 通过 `Event::cycle` 设置的周期回调函数



## 14. 高级特性

### 14.1 swoole的实现

swoole 使用 `C/C++11` 编写，不依赖其他第三方库。

- swoole 并没使用 libevent，所以不依赖于 libevent扩展
- swoole 并不依赖 php 的 stream / sockets / pcntl / posix / sysvmsg 等扩展

###### socket 部分

swoole 使用底层的 socket 系统调用。参加源码的 sys/socket.h

###### IO 事件循环

- 在linux 系统下使用 `epoll` , `MacOS / FreeBSD` 下使用 kqueue
- task 进程没有事件循环，进程会循环阻塞读取管道

> 很多人使用 `strace -p` 查看 swoole 主进程只能看到 poll 系统调用。正确的查看方法是 stace -f -p
>
> ###### 多进程 / 多线程
>
> - 多进程使用 `fork()` 系统调用
> - 多线程使用 `pthread` 线程库
>
> ###### EventFd
>
> swoole 使用了 `eventfd` 作为线程 / 进程间消息通知的机制。
>
> ###### Signalfd
>
> swoole 中使用了 `signalfd` 来实现对信号的屏蔽和处理。可以有效地避免线程 / 进程被信号打断，系统调用 `restart` 的问题。在主进程中 `Reactor/AIO` 线程不会接收任何信号。

### 14.2 Reactor线程

swoole 的主进程是一个多线程的程序。其中有一组很重要的线程，称之为 Reactor 线程。它是真正处理 TCP 连接，收发数据的线程。

swoole 的主进程在 Accept 新的连接后，会将这个连接分配给一个固定的 Reactor 线程，并由这个线程负责监听此 socket。在 socket 可读时读取数据，并进行协议解析，将请求投递到 worker 进程。在 socket 可写时将数据发送给 TCP 客户端。

> 分配计算的方式是 fd % serv-> reactor_num
>
> ###### TCP 和 UDP 的差异
>
> - TCP 客户端，worker 进程处理完请求后，调用 `$server->send` 会将数据发送给 `Reactor` 线程，由 `Reactor` 线程再发送给客户端
> - UDP 客户端，worker 进程处理完请求后，调用 `$server->sendto` 直接发送给客户端，无需经过 `Reactor` 线程

### 14.3 Manager进程

swoole 中 worker/task 进程都是由 Manager 进程 Fork 并管理的。

- 子进程结束运行时，manager 进程负责回收此子进程，避免成为僵尸进程。并创建新的子进程
- 服务器关闭时，manager 进程将发送信号给所有子进程，通知子进程关闭服务
- 服务器reload 时，manager进程会逐个关闭或重启子进程

为什么不是 master 进程呢，因为 master 进程是多线程的，不能安全地执行 fork 操作

### 14.4 Worker进程

Swoole提供了完善的进程管理机制，当 worker 进程异常退出时，如果发生 php 的致命错误，被其他程序误杀，或达到 max_request 次数之后正常退出。主进程会重新拉起新的 worker 进程。worker 进程内可以像普通的 apache+php 或 php-fpm  中那样写逻辑，而不需要像 nodejs 那样写异步回调的程序。

###### 主进程内的回调函数

`onStart`, `onShutdown`, `onTimer`

###### worker 进程内的回调函数

`onWorkerStart`, `onWorkerStop`, `onConnect`, `onClose`, `onReceive`, `onFinish`

###### task_worker 进程内的回调函数

`onTask`, `onWorkerStart`

###### 管理进程内的回调函数

`onManagerStart`, `onManagerStop`

### 14.5 Reactor，Worker，TaskWorker的关系

`Reactor`, `Worker`, `TaskWorker` 三者分别的职责是：

###### Reactor线程

- 负责维护客户端 tcp 连接，处理网络IO，处理协议，收发数据
- 完全是异步非阻塞的模式
- 全部为 C 代码，除 `start`/`shutdown` 事件回调以外，不执行任何 php 代码
- 将 tcp 客户端发来的数据缓冲，拼接，拆分成完整的一个请求数据包
- `Reactor` 以多线程的方式运行

###### Worker 进程

- 接收由 `Reactor` 线程投递的请求数据包，并执行 php 回调函数处理数据
- 生成响应数据并发送给 `Reactor` 线程，由 `Reactor` 线程发送给 TCP 客户端
- 可以是异步非阻塞模式，也可以是同步阻塞模式
- `Worker` 以多进程的方式运行

###### TaskWorker 进程

- 接收由 `Worker` 进程通过 `swoole_server->task/taskwait` 方法投递的任务
- 处理任务，并将结果数据返回给`Worker` 进程处理（`swoole_server->finish`）
- 完全是**同步阻塞**模式
- `TaskWorker` 以多进程的方式运行

###### 关系

可以理解为 `Reactor` 就是 `nginx` , `Worker` 是 `php-fpm`, `Reactor` 线程异步并行地处理网络请求，然后再转发给 `Worker` 进程中处理。`Reactor` 和 `Worker` 间通过 `Unix Socket` 通信。

在 `php-fpm` 的应用中，经常会将一个任务异步投递到 `Redis` 等队列中，并在后台启动一些 `php` 进程异步地处理这些任务。`Swoole` 提供的 `TaskWorker` 是一套完整的方案，将任务的投递，队列，php任务处理进程管理合为一体。通过底层提供的api 可以简便地实现异步任务的处理。另外 `TaskWorker` 还可以在任务执行完成后再返回一个结果到 `Worker`.

`Swoole` 的 `Reactor`, `Worker`, `TaskWorker` 之间可以紧密地结合起来，提供更高级的使用方式。

一个更通俗的比喻，假设 `Server` 是一个工厂，那么 `Reactor` 就是销售，接收客户订单。而 `Worker` 是工人，当销售接收到订单后，`Worker` 去生产出客户要的东西。而 `TaskWorker` 可以理解为行政人员，可以帮助 `Worker` 干些杂事，让 `Worker` 专心工作。

> 底层会为 `Worker` 进程，`TaskWorker` 进程分配一个唯一的 ID
>
> 不同的 `Worker` 和 `TaskWorker` 进程之间可以通过 `sendMessage` 接口来通信