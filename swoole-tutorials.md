# Swoole-Tutorials

_____________________________

## 1. 入门指引

### 1.1 环境依赖

### 1.2 编译安装

#### 1.2.1 编译参数

#### 1.2.2 常见错误

#### 1.2.3 Cygwin

### 1.3 快速起步

#### 1.3.1 创建TCP服务器

#### 1.3.2 创建UDP服务器

#### 1.3.3 创建Web服务器

#### 1.3.4 创建WebSocket服务器

#### 1.3.5 设置定时器

#### 1.3.6 执行异步任务

#### 1.3.7 创建同步TCP服务器

#### 1.3.8 创建异步TCP服务器

#### 1.3.9 网络通信协议设计

#### 1.3.10 使用异步客户端

#### 1.3.11 多进程共享数据

#### 1.3.12 使用协程客户端

#### 1.3.13 协程：并发 shell_exec

#### 1.3.14 协程：Go + Chan + Defer

#### 1.3.15 协程：实现 Go 语言风格的 defer

#### 1.3.16 协程： 实现 sync.WaitGroup 功能

### 1.4 注意点

#### 1.4.1 sleep/usleep 的影响

#### 1.4.2 exit/die 函数的影响

#### 1.4.3 while 循环的影响

#### 1.4.4 stat 缓存清理

#### 1.4.5 mt_rand 随机数

#### 1.4.6 进程隔离

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

#### 3.11.1 setDefer 机制

#### 3.11.2 子协程，通道

### 3.12 实现原理

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

