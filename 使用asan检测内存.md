#### 使用 asan 检测内存

高版本 `gcc` 和 `clang` 支持 `asan` 内存检测，与 `valgrind` 相比 `asan` 消耗非常低，甚至可以直接在生产环境中启用 `asan` 排查跟踪内存问题。

使用 `asan` 特性必须将php也编译为 `asan`，否则运行时会报错。

###### 编译 php

执行 `./configure` 后，修改 `Makefile` 修改 `CFLAGS_CLEAN` 末尾追加 `-fsanitize=address -fno-omit-frame-pointer`，然后执行 `make clean && make install`

###### 编译 Swoole

```shell
phpize
./configure --enable-asan
make
make install
```

###### 关闭内存泄漏检测

php 中 `ZendVM` 有较多进程退出时内存释放的逻辑，可能会引起 `asan` 误报，可以设置 `export ASAN_OPTIONS=detect_leaks=0` 暂时关闭 `asan` 的内存泄漏检测。