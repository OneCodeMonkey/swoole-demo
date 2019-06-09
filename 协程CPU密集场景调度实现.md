#### 协程CPU密集场景调度实现

###### 抢占式 / 非抢占式

如果服务场景是 IO 密集型，那么非抢占式可行。如果服务中加入了CPU密集型操作，我们就不得不考虑重新安排协程的调度模式了。

在 Swoole 协程系列文章中我们曾经介绍过 IO 密集场景下协程基于非抢占式调度的优势和卓越的性能。但是在CPU密集的场景下抢占式调度是非常重要的。试想有以下场景，程序中有 A,B 两个协程，协程A一直在执行 CPU 密集型的计算，非抢占式的调度模型中，A不会主动让出控制权，从而导致B得不到时间片，协程得不到均衡调度。导致的问题是假如当前服务 A，B同时对外提供服务，B协程处理的请求就可能因为得不到时间片而导致请求超时，在企业级应用中，这种情况是有危害的。

###### php 对此的应对方式

由于php是单线程运行的，所以针对 php 的协程调度和 `go` 完全不同，我们选择使用 `declare(tick=N)` 语法功能实现协程调度。Tick（时钟周期）是一个在 declare 代码段中解释器每执行 N 条可计时的低级语句就会发生的事情。N 的值是在 declare 中的 directive 部分用 ticks = N 来指定的。不是所有语句都可计时。通常条件表达式和参数表达式都不可计时，以下类型是不可被 `tick` 计数的。

```c
static inline zend_bool zend_is_unticked_stmt(zend_ast *ast)
{
    return ast->kind == ZEND_AST_STMT_LIST || ast->kind == ZEND_AST_LABEL || ast->kind == ZEND_AST_PROP_DECL || ast->kind == ZEND_AST_CLASS_CONST_DECL || ast->kind == ZEND_AST_USE_TRAIT || ast->kind == ZEND_AST_METHOD;
}
```

协程调度的逻辑是每次触发 `tick handler`，我们判断当前协程相对最近一次调度时间是否大于协程最大执行时间。这样就可以让协程超出执行时间后被其他协程抢占（让出），这种调度表现为抢占式调度，且不是基于IO，首先来一段php最简单加法指定执行的压测。

```php
<?php
const N = 10000000;
$n = N;
$s = microtime(true);
$i = 0;
while($n--) {
    $i++;
}
$e = microtime(true);
echo "pho add " . N . " times,takes " . round(($e - $s) * 1000, 2) . "ms\n";
echo "one add time = " . round(($e - $s) / N * (1000 * 1000 * 1000), 2) . "ns\n";
```

这里测试机器主频为 `3.60Ghz` 输出结果为

```shell
php add 10000000 times, takes 310.37ms
one add time = 31.04ns
```

即一千万次 $i++ 操作，耗时300ms，每次耗时 30ns

Tick 的基本原理是，在脚本的最开始声明 `tick=number`, 表示每执行 `Number` 个指令会插入一个 ZEND_TICKS 指令，然后执行相应的 handler。我们有了以上操作的压测就可以做一个粗略的估算：

假设 php 一次 opcode 操作为 `opcode_time = 50ns` （具体因机器型号和指令集不同而有变化）。如果我们想实现一个协程最大执行时间为 10ms，由于调度的时间误差粒度为 `number * opcode_time`，比如：

1. tick = 100, handler 执行的周期为 `100 * opcode_time = 5000ns`, 10ms 误差为 0.005ms
2. tick = 1000，handler 执行的周期为 `1000 * opcode_time = 0.05ms`，10ms 误差为 0.05ms
3. tick = 10000，handler 执行的周期为 `10000 * opcode_time = 500000ns`，10ms 误差为 0.5ms

可以看出这样的误差颗粒是完全可以接受的，误差的范围和 tick 的参数有关系。tick 越大，对性能影响为每 `tick = number` 条指令执行一次 tick handler 约 50ns，性能的影响非常小。

###### sample

```php
<?php
declare(ticks = 1000);
$max_msec = 10;
Swoole\Coroutine::set([
    'max_exec_msec' => $max_msec,
]);
$s = microtime(true);
echo "start\n";
$flag = 1;
go(function() use (&$flag, $max_msec) {
    echo "coroutine 1 start to loop for $max_msec msec\n";
    $i = 0;
    while(1) $i++;
    echo "coroutine 1 can exit\n";
});
$t = microtime(true);
$u = round(($t - $s) * 1000, 5);
echo "schedule use time " . $u . "ms\n";

go(function () use (&$flag) {
    echo "coroutine 2 set flag = false\n";
    $flag = false;
});
echo "end\n";
```

输出结果为

```python
start
coroutine 1 start to loop for 10 msec
schedule use time 10.1835ms
coroutine 2 set flag = false
end
coroutine 1 can exit
```

其中 coroutine1 的 opcodes 为

```ini
{closure}: ; (lines=18, args=0, vars=3, tmps=5)
    ; (before optimizer)
    ; /path-to-tick/tick.php:12-19
L0 (12):    BIND_STATIC (ref) CV0($flag) string("flag")
L1 (12):    BIND_STATIC CV1($max_msec) string("max_msec")
L2 (13):    T4 = ROPE_INIT 3 string("coro 1 start to loop for ")
L3 (13):    T4 = ROPE_ADD 1 T4 CV1($max_msec)
L4 (13):    T3 = ROPE_END 2 T4 string(" msec
")
L5 (13):    ECHO T3
L6 (13):    TICKS 1000
L7 (14):    ASSIGN CV2($i) int(0)
L8 (14):    TICKS 1000
L9 (15):    JMP L13
L10 (16):   T7 = POST_INC CV2($i)
L11 (16):   FREE T7
L12 (16):   TICKS 1000
L13 (15):   JMPNZ CV0($flag) L10
L14 (15):   TICKS 1000
L15 (18):   ECHO string("coro 1 can exit
")
L16 (18):   TICKS 1000
L17 (19):   RETURN null
LIVE RANGES:
        4: L2 - L4 (rope)
```

现在基于 tick 的调度实现已单独放在 

[分支]: https://github.com/swoole/swoole-src/tree/schedule

，测试用例在

[这里]: https://github.com/swoole/swoole-src/tree/schedule/tests/swoole_coroutine/schedule

###### 小结

使用 tick 的方式实现有 一个较大的缺点就是需要用户在 php 层的脚本开始的地方声明 `declare(tick=N)` ，这样使得这个功能对于扩展层来说不够完备。但是它能够处理所有的php指令，同时我们在处理 tick handler 时，HOOK 了php 默认的方式，因为使用了默认的方式，php用户层可以注册 `tick` 函数造成干扰。我们发现，历史提交记录中有一种方式是基于 HOOK 循环指令的方式实现的。我们假设使得 CPU 密集的类型是大量的循环操作，我们检测循环的次数和当前协程运行的时间，即每次遇到循环指令的 handler，我们去检查当前循环的次数和协程执行的时间，进而可以发现执行时间较长的卸车嗯。

但是这种方式无法处理没有使用循环的情况，假如只有单纯的大量php指令密集运算是无法检测到的。权衡优缺点，swoole 最终使用 php `tick` 这种方式实现。

