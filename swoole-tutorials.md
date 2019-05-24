# Swoole-Tutorials

_____________________________

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

### 2.8 压力测试

## 3. Coroutine

### 3.1 Coroutine

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

#### 3.12.2 发送数据协程调度

#### 3.12.3 协程内存开销

#### 3.12.4 4.0协程实现原理

#### 3.12.5 协程客户端超时规则

#### 3.12.6 协程执行流程

### 3.13 注意点

#### 3.13.1 在多个协程间共用同一个协程客户端

#### 3.13.2 禁止使用协程API的场景（2.x版本）

#### 3.13.3 使用类静态变量/全局变量保存上下文

#### 3.13.4 退出协程

#### 3.13.5 异常处理

### 3.14 扩展组件

#### 3.14.1 MongoDB

### 3.15 编程调试

## 4. Runtime

### 4.1 文件操作

### 4.2 睡眠函数

### 4.3 开关选项

### 4.4 严格模式

## 5. Timer

### 5.1 swoole_timer_tick

### 5.2 swoole_timer_after

### 5.3 swoole_timer_clear

## 6. Memory

## 7. Http\Server

## 8. WebSocket\Server

## 9. Redis\Server

## 10. Process

## 11. Process\Pool

## 12. Client

## 13. Event

## 14. 高级特性

