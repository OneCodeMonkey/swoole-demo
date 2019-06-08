#### C 环境下使用swoole

swoole使用cmake来做编译配置，示例程序在swoole源码 example/server.c 中，您可以在此基础上进行代码开发。如果需要修改编译细节的选项，请直接修改CMakeLists.txt

###### 生成 config.h

swoole依赖 `phpize` 和 `configure` 检测系统环境，生成 `config.h`

```shell
cd swoole-src/
phpize
./configure
```

执行成功后 `swoole-src` 目录下会有 `config.h`

###### Build & Install

```shell
cmake . 
make
make install
```

- `cmake` 命令可以增加 `cmake . -DCMAKE_INSTALL_PREFIX=/opt/swoole` 参数指定安装的路径
- `make` 命令可以使用 `make DESTDIR=/opt/swoole install` 参数指定安装的路径

安装路径非系统默认的 lib 目录时，需要配置 `ld.so.conf` 将 `swoole` 动态连接库所在的目录添加到 link 路径中。

```shell
sudo echo "/opt/swoole/lib" >> /etc/ld.so.conf
sudo echo "/opt/swoole/lib" > /etc/ld.so.conf.d/swoole.conf
ldconfig
```

###### sample

```c
// examples/server.c
#include<swoole/Server.h>
#include<swoole/Client.h>

int main()
{
    swServer serv;
    swServer_create(&serv);
    serv.onStart = my_onStart;
    swServer_start(&serv);
}
```

###### 编译运行

```
gcc -o server server.c -lswoole
./server
```

___________________

#### C++ 中使用 swoole

php编写的 Server 程序在某些情况下会表现的比较差

- 内存占用高的场景，php底层使用内存结构 `zval` 来管理所有变量，会额外占用内存。比如一个 int32 的整数可能需要占用 16（php7）或者 24（php5）字节的内存，而 C/C++ 只需要 4个字节。如果系统要存储大量整数，那么占用内存会非常大。
- php是动态编译方式的脚本语言，计算性能较差。纯运算型的代码可能会比 C/C++ 程序差上几十倍甚至上百倍以上，此类场景下使用纯php语言不是个好选择

`cpp-swoole` 是对 `c-swoole` 的面向对象封装，支持了绝大部分 `swoole_server` 的特性包括 `task` 功能，另外还支持高精度定时器特性。

`cpp-swoole` 依赖于 `libswoole.so` ，需要先编译 `c-swoole` 生成 `libswoole.so`

###### 编译 libswoole.so

```shell
git clone https://github.com/swoole/cpp-swoole.git
cmake .
make
sudo make install
```

###### 编写程序

头文件：

```cpp
#include<swoole/Server.php>
#include<swoole/Timer.php>
```

服务器程序只需要继承 `swoole::Server`, 并实现响应的回调函数即可。

```c++
#include <swoole/Server.hpp>
#include <swoole/Timer.hpp>
#include <iostream>

using namespace std;
using namespace swoole;

class MyServer : public Server
{
public:
    MyServer(string _host, int _port, int _mode = SW_MODE_PROCESS, int _type = SW_SOCK_TCP) :
            Server(_host, _port, _mode, _type)
    {
        serv.worker_num = 4;
        SwooleG.task_worker_num = 2;
    }

    virtual void onStart();
    virtual void onShutdown() {};
    virtual void onWorkerStart(int worker_id) {}
    virtual void onWorkerStop(int worker_id) {}
    virtual void onPipeMessage(int src_worker_id, const DataBuffer &) {}
    virtual void onReceive(int fd, const DataBuffer &data);
    virtual void onConnect(int fd);
    virtual void onClose(int fd);
    virtual void onPacket(const DataBuffer &data, ClientInfo &clientInfo) {};

    virtual void onTask(int task_id, int src_worker_id, const DataBuffer &data);
    virtual void onFinish(int task_id, const DataBuffer &data);
};

void MyServer::onReceive(int fd, const DataBuffer &data)
{
    swConnection *conn = swWorker_get_connection(&this->serv, fd);
    printf("onReceive: fd=%d, ip=%s|port=%d Data=%s|Len=%ld\n", fd, swConnection_get_ip(conn),
           swConnection_get_port(conn), (char *) data.buffer, data.length);

    int ret;
    char resp_data[SW_BUFFER_SIZE];
    int n = snprintf(resp_data, SW_BUFFER_SIZE, (char *) "Server: %*s\n", (int) data.length, (char *) data.buffer);
    ret = this->send(fd, resp_data, (uint32_t) n);
    if (ret < 0)
    {
        printf("send to client fail. errno=%d\n", errno);
    }
    else
    {
        printf("send %d bytes to client success. data=%s\n", n, resp_data);
    }
    DataBuffer task_data("hello world\n");
    this->task(task_data);
//    this->close(fd);
}

void MyServer::onConnect(int fd)
{
    printf("PID=%d\tConnect fd=%d\n", getpid(), fd);
}

void MyServer::onClose(int fd)
{
    printf("PID=%d\tClose fd=%d\n", getpid(), fd);
}

void MyServer::onTask(int task_id, int src_worker_id, const DataBuffer &data)
{
    printf("PID=%d\tTaskID=%d\n", getpid(), task_id);
}

void MyServer::onFinish(int task_id, const DataBuffer &data)
{
    printf("PID=%d\tClose fd=%d\n", getpid(), task_id);
}

void MyServer::onStart()
{
    printf("server start\n");
}

class MyTimer : Timer
{
public:
    MyTimer(long ms, bool interval) :
            Timer(ms, interval)
    {

    }

    MyTimer(long ms) :
            Timer(ms)
    {

    }

protected:
    virtual void callback(void);
    int count = 0;
};

void MyTimer::callback()
{
    printf("#%d\thello world\n", count);
    if (count > 9)
    {
        this->clear();
    }
    count++;
}

int main(int argc, char **argv)
{
	MyServer server("127.0.0.1", 9501, SW_MODE_SINGLE);
	server.listen("127.0.0.1", 9502, SW_SOCK_UDP);
	server.listen("::1", 9503, SW_SOCK_TCP6);
	server.listen("::1", 9504, SW_SOCK_UDP6);
	server.setEvents(EVENT_onStart | EVENT_onReceive | EVENT_onClose | EVENT_onTask | EVENT_onFinish);
	server.start();
}
```

