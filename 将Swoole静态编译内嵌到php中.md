#### 使用systemd管理swoole服务器

`swoole-1.9.15`+ 支持了静态编译，可以将 `Swoole` 内嵌到 php 中。

###### 准备

1. 需要将 `swoole-src` 和 `php-src` 两份源码
2. 将`swoole` 源码放置到 `php-src/ext` 目录中
3. 清理 `swoole` 源码目录，使用 `phpize --clean` 和 `./clear.sh`

###### 配置

- 目前 `swoole` 只支持 `cli` 静态内联，必须关闭其他 `SAPI` 包括 `php-fpm` ，`CGI`, `phpdbg` 等
- 需要增加 `--enable-swoole-static` 和 `--with-swoole` 两项编译配置参数

###### 构建

```shell
cd php-src/
./buildconf --force
/configure --disable-all --enable-swoole-static --with-zlib --with-swoole --enable0-cli --disable-cgi --disable-phpdbg
make -j
```

###### 使用

编译完成，在 `sapi/cli` 目录中可以得到 `php` 可执行文件。使用 `./php --ri swoole` 查看信息