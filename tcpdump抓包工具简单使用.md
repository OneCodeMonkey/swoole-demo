#### tcpdump 抓包工具的简单使用

在调试网络通信程序时，tcpdump 是必备的工具。tcpdump 工具很强大，可以看到网络通信的每个细节，如TCP，可以看到3次握手，PUSH/ACK 数据推送，close 4次挥手，全部细节。包括每一次网络收包的字节数，时间等。

###### sample

```shell
sudo tcpdump -i any tcp port 9501
```

- -i 指定网卡，any表示所有网卡
- tcp 指定仅监听 tcp 协议的包
- port 指定监听的端口

> tcpdump 需要root权限
>
> 需要看通信的数据内容，可以加 -Xnlps0 参数，其他更多参数可以搜
>
> 运行结果
>
> ```shell
> 13:29:07.788802 IP localhost.42333 > localhost.9501: Flags [S], seq 828582357, win 43690, options [mss 65495,sackOK,TS val 2207513 ecr 0,nop,wscale 7], length 0
> 13:29:07.788815 IP localhost.9501 > localhost.42333: Flags [S.], seq 1242884615, ack 828582358, win 43690, options [mss 65495,sackOK,TS val 2207513 ecr 2207513,nop,wscale 7], length 0
> 13:29:07.788830 IP localhost.42333 > localhost.9501: Flags [.], ack 1, win 342, options [nop,nop,TS val 2207513 ecr 2207513], length 0
> 13:29:10.298686 IP localhost.42333 > localhost.9501: Flags [P.], seq 1:5, ack 1, win 342, options [nop,nop,TS val 2208141 ecr 2207513], length 4
> 13:29:10.298708 IP localhost.9501 > localhost.42333: Flags [.], ack 5, win 342, options [nop,nop,TS val 2208141 ecr 2208141], length 0
> 13:29:10.298795 IP localhost.9501 > localhost.42333: Flags [P.], seq 1:13, ack 5, win 342, options [nop,nop,TS val 2208141 ecr 2208141], length 12
> 13:29:10.298803 IP localhost.42333 > localhost.9501: Flags [.], ack 13, win 342, options [nop,nop,TS val 2208141 ecr 2208141], length 0
> 13:29:11.563361 IP localhost.42333 > localhost.9501: Flags [F.], seq 5, ack 13, win 342, options [nop,nop,TS val 2208457 ecr 2208141], length 0
> 13:29:11.563450 IP localhost.9501 > localhost.42333: Flags [F.], seq 13, ack 6, win 342, options [nop,nop,TS val 2208457 ecr 2208457], length 0
> 13:29:11.563473 IP localhost.42333 > localhost.9501: Flags [.], ack 14, win 342, options [nop,nop,TS val 2208457 ecr 2208457], length 0
> ```
>
> - 13:29:11.563473 时间带有精确到微秒
> - localhost.42333 > localhost.9501 表示通信的流向，42333是客户端，9501是服务器端
> - [S] 表示这是一个SYN请求
> - [.] 表示这是一个ACK确认包，(client)SYN->(server)SYN->(client)ACK 就是3次握手过程
> - [P] 表示这个是一个数据推送，可以是从服务器端向客户端推送，也可以从客户端向服务器端推
> - [F] 表示这是一个FIN包，是关闭连接操作，client/server都有可能发起
> - [R] 表示这是一个RST包，与F包作用相同，但RST表示连接关闭时，仍然有数据未被处理。可以理解为是强制切断连接
> - win 342是指滑动窗口大小
> - length 12指数据包的大小

