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

