#### 详解Swoole协程的实现

###### 写在最前

Swoole 协程的诞生经历了几个大的阶段，我们要在前进的道路上时常总结和回顾自己的发展历程。

###### 什么是协程？

协程的概念早就出现了，摘自 `wiki`：

> According to Donald Knuth, the term coroutine was coined by Melvin Conway in 1958, after he applied it to construction of an assembly program.  The first published explanation of the coroutine appeared later, in 1963.

协程要比 C 语言历史更长，究其概念，协程是一种子程序，可以通过 `yield` 的方式转移程序控制权，协程之间不是调用者与被调用者的关系，而是彼此对称，平等的。协程完全由用户态程序控制，所以也被称为用户态的线程。协程由用户以非抢占式的方式调度，而不是操作系统。正因为如此，没有系统调度和上下文切换的开销，协程实现了**轻量，高效，快速**等特点。（大部分为非抢占式，但是比如 `go` 在 1.4 版本也加入了抢占式调度，其中一个协程发生死循环，不至于其他协程被“饿死”。需要在必要的时刻让出CPU，Swoole 后续也会增加这个特性）。

协程开始流行很大一部分原因归功于 `go` 语言的流行，很多人开始使用它。目前支持协程的语言由很多，`go`，`lua`，`python`，`C#`，`Javascript`。我们可以用很短的时间用 C/C++ 描述出协程的模型，当然 php 也有自己的协程实现，也就是生成器，在此不探讨这个点。

###### Swoole 1.x 版本

Swoole 最终设计目的是要做**高性能网络通讯引擎**， Swoole 1.x 的编码主要是异步回调的方式，虽然性能很高效，但很多开发者会发现，随着项目工程的复杂度上升，用异步回调方式写业务逻辑是和我们人的正常思维方式不那么符合的。尤其是回调中嵌套了多层子回调时，不仅维护成本指数级上升，而且犯错的几率也在加速上升。

更符合人类思维习惯的方式是：同步的代码，运行出异步非阻塞的效果。所以 Swoole 很早就开始研究如何达到这个目的。

最初的协程版本是基于PHP生成器Generators\Yield的方式实现的，可以参考PHP大神Nikita的早期博客的关于[协程](https://nikic.github.io/2012/12/22/Cooperative-multitasking-using-coroutines-in-PHP.html)介绍。PHP和Swoole的事件驱动的结合可以参考腾讯出团队开源的[TSF](https://github.com/Tencent/tsf)框架，我们也在很多生产项目中使用了该框架，确实让大家感受到了，以同步编程的方式写异步代码的快感，然而，现实总是很残酷，这种方式有几个致命的缺点：

- 所有主动让出的逻辑都需要yield关键字。这会给程序员带来极大的概率犯错，导致大家对协程的理解转移到了对Generators语法的原理的理解。
- 由于语法无法兼容老的项目，改造老的项目工程复杂度巨大，成本太高。

这样使得无论新老项目，使用都无法得心应手。

### Swoole2.x

   2.x之后的协程都是基于内核原生的协程，无需yield关键字。2.0的版本是一个非常重要的里程碑，实现了php的栈管理，深入zend内核在协程创建，切换以及结束的时候操作PHP栈。在Swoole的文档中也介绍了很多关于每个版本实现的细节，我们这篇文章只对每个版本的协程驱动技术做简单介绍。**原生协程都有对php栈的管理，后续我们会单独拿一片文章来深入分析PHP栈的管理和切换。**

   2.x主要使用了setjmp/longjmp的方式实现协程，很多C项目主要采用这种方式实现try-catch-finally，大家也可以参考Zend内核的用法。setjmp的首次调用返回值是0，longjmp跳转时，setjmp的返回值是传给longjmp的value。 setjmp/longjmp由于只有控制流跳转的能力。虽然可以还原PC和栈指针，但是无法还原栈帧，因此会出现很多问题。比如longjmp的时候，setjmp的作用域已经退出，当时的栈帧已经销毁。这时就会出现未定义行为。假设有这样一个调用链：

> func0() -> func1() -> ... -> funcN()

只有在func{i}()中setjmp，在func{i+k}()中longjmp的情况下，程序的行为才是可预期的。

### Swoole3.x

3.x是生命周期很短的一个版本，主要借鉴了[fiber-ext](https://github.com/fiberphp/fiber-ext)项目，使用了PHP7的VM interrupts机制，该机制可以在vm中设置标记位，在执行一些指令的时候（例如：跳转和函数调用等）检查标记位，如果命中就可以执行相应的hook函数来切换vm的栈，进而实现协程。

\####Swoole4.x 4.x协程我们放在最后。

协程之旅前篇结束，下一篇文章我们将深入Zend分析Swoole原生协程PHP部分的实现。