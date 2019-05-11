#!/usr/bin/env bash
# 如果我们修改了 server.php，则必须重启一次服务才能生效。
# 此脚本用来断开服务
ps -eaf | grep "server.php" | grep -v "grep" | awk '{print $2}' | xargs kill -9