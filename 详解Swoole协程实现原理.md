# 详解Swoole协程的实现

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

###### Swoole2.x

   2.x之后的协程都是基于内核原生的协程，无需yield关键字。2.0的版本是一个非常重要的里程碑，实现了php的栈管理，深入zend内核在协程创建，切换以及结束的时候操作PHP栈。在Swoole的文档中也介绍了很多关于每个版本实现的细节，我们这篇文章只对每个版本的协程驱动技术做简单介绍。**原生协程都有对php栈的管理，后续我们会单独拿一片文章来深入分析PHP栈的管理和切换。**

   2.x主要使用了setjmp/longjmp的方式实现协程，很多C项目主要采用这种方式实现try-catch-finally，大家也可以参考Zend内核的用法。setjmp的首次调用返回值是0，longjmp跳转时，setjmp的返回值是传给longjmp的value。 setjmp/longjmp由于只有控制流跳转的能力。虽然可以还原PC和栈指针，但是无法还原栈帧，因此会出现很多问题。比如longjmp的时候，setjmp的作用域已经退出，当时的栈帧已经销毁。这时就会出现未定义行为。假设有这样一个调用链：

> func0() -> func1() -> ... -> funcN()

只有在func{i}()中setjmp，在func{i+k}()中longjmp的情况下，程序的行为才是可预期的。

###### Swoole3.x

3.x是生命周期很短的一个版本，主要借鉴了[fiber-ext](https://github.com/fiberphp/fiber-ext)项目，使用了PHP7的VM interrupts机制，该机制可以在vm中设置标记位，在执行一些指令的时候（例如：跳转和函数调用等）检查标记位，如果命中就可以执行相应的hook函数来切换vm的栈，进而实现协程。

\####Swoole4.x 4.x协程我们放在最后。

协程之旅前篇结束，下一篇文章我们将深入Zend分析Swoole原生协程PHP部分的实现。

###### Swoole中php部分

本篇我们开始深入PHP来分析Swoole协程的PHP部分。

 先从一个协程最简单的例子入手:

```php
<?php
go(function(){
	echo "coro 1 start\n";
	co::sleep(1);
	echo "coro 1 exit";
});
echo "main flag\n";
go(function(){
	echo "coro 2 start\n";
	co::sleep(1);
	echo "coro 2 exit\n";
});
	echo "main end\n";
//输出内容为
coro 1 start
main flag
coro 2 start
main end
coro 1 exit
coro 2 exit
```

可以发现，原生协程是在函数内部发生了跳转，控制流从第4行跳转到第7行，接着执行从第8行开始执行go函数，到第10行跳转到了第13行，紧接着执行第9行，然后执行第15行的代码。为什么Swoole的协程可以这样执行呢？我们下面将一步一步进行分析。

  我们知道PHP作为一门解释型的语言，需要经过编译为中间字节码才可以执行，首先会经过词法和语法分析，将脚本编译为opcode数组，成为zend_op_array，然后经过vm引擎来执行。我们这里只关注vm执行部分。执行的部分需要关注几个重要的数据结构

- Opcodes

```c
struct _zend_op {
    const void *handler;//每个opcode对应的c处理函数
    znode_op op1;//操作数1
    znode_op op2;//操作数2
    znode_op result;//返回值
    uint32_t extended_value;
    uint32_t lineno;
    zend_uchar opcode;//opcode指令
    zend_uchar op1_type;//操作数1类型
    zend_uchar op2_type;//操作数2类型
    zend_uchar result_type;//返回值类型
};
```

从结构中很容易发现opcodes本质上是一个[三地址码](https://zh.wikipedia.org/wiki/%E4%B8%89%E4%BD%8D%E5%9D%80%E7%A2%BC)，这里opcode是指令的类型，有两个输入的操作数数和一个表示输出的操作数。每个指令可能全部或者部分使用这些操作数，比如加、减、乘、除等会用到全部三个； `！`操作只用到op1和result两个；函数调用会涉及到是否有返回值等。

- Op arrays

  `zend_op_array` PHP的主脚本会生成一个zend_op_array,每个function,eval,甚至是assert断言一个表达式等都会生成一个新得op_array。

```c
struct _zend_op_array {
    /* Common zend_function header here */
    /* ... */
    uint32_t last;//数组中opcode的数量
    zend_op *opcodes;//opcode指令数组
    int last_var;// CVs的数量
    uint32_t T;//IS_TMP_VAR、IS_VAR的数量
    zend_string **vars;//变量名数组
    /* ... */
    int last_literal;//字面量数量
    zval *literals;//字面量数组 访问时通过_zend_op_array->literals + 偏移量读取
    /* ... */
};
```

我们已经熟知php的函数内部有自己的单独的作用域，这归功于每个zend_op_array包含有当前作用域下所有的堆栈信息，函数之间的调用关系也是基于zend_op_array的切换来实现。

- PHP栈帧

PHP执行需要的所有状态都保存在一个个通过链表结构关联的VM栈里，每个栈默认会初始化为256K，Swoole可以单独定制这个栈的大小(协程默认为8k),当栈容量不足的时候，会自动扩容，仍然以链表的关系关联每个栈。在每次函数调用的时候，都会在VM Stack空间上申请一块新的栈帧来容纳当前作用域执行所需。栈帧结构的内存布局如下所示：

```shell
+----------------------------------------+
| zend_execute_data                      |
+----------------------------------------+
| VAR[0]                =         ARG[1] | arguments
| ...                                    |
| VAR[num_args-1]       =         ARG[N] |
| VAR[num_args]         =   CV[num_args] | remaining CVs
| ...                                    |
| VAR[last_var-1]       = CV[last_var-1] |
| VAR[last_var]         =         TMP[0] | TMP/VARs
| ...                                    |
| VAR[last_var+T-1]     =         TMP[T] |
| ARG[N+1] (extra_args)                  | extra arguments
| ...                                    |
+----------------------------------------+
```

zend_execute_data 最后要介绍的一个结构，也是最重要的一个。

```c
struct _zend_execute_data {
    const zend_op       *opline;//当前执行的opcode，初始化会zend_op_array起始
    zend_execute_data   *call;//
    zval                *return_value;//返回值
    zend_function       *func;//当前执行的函数（非函数调用时为空）
	zval                 This;/* this + call_info + num_args    */
    zend_class_entry    *called_scope;//当前call的类
    zend_execute_data   *prev_execute_data;
    zend_array          *symbol_table;//全局变量符号表
    void               **run_time_cache;   /* cache op_array->run_time_cache */
    zval                *literals;         /* cache op_array->literals       */
};
```

`prev_execute_data` 表示前一个栈帧结构，当前栈执行结束以后，会把当前执行指针(类比PC)指向这个栈帧。 PHP的执行流程正是将很多个zend_op_array依次装载在栈帧上执行。这个过程可以分解为以下几个步骤：

- **1：** 为当前需要执行的op_array从vm stack上申请当前栈帧，结构如上。初始化全局变量符号表，将全局指针EG(current_execute_data)指向新分配的zend_execute_data栈帧，EX(opline)指向op_array起始位置。

- 2:

   

  从

  ```
  EX(opline)
  ```

  开始调用各opcode的C处理handler(即_zend_op.handler)，每执行完一条opcode将

  ```
  EX(opline)++
  ```

  继续执行下一条，直到执行完全部opcode，遇到函数或者类成员方法调用：

  - 从`EG(function_table)`中根据function_name取出此function对应的zend_op_array，然后重复步骤1，将EG(current_execute_data)赋值给新结构的`prev_execute_data`，再将EG(current_execute_data)指向新的zend_execute_data栈帧，然后开始执行新栈帧，从位置`zend_execute_data.opline`开始执行，函数执行完将EG(current_execute_data)重新指向`EX(prev_execute_data)`，释放分配的运行栈帧，执行位置回到函数执行结束的下一条opline。

- **3:** 全部opcodes执行完成后将1分配的栈帧释放，执行阶段结束

------

有了以上php执行的细节，我们回到最初的例子，可以发现协程需要做的是，**改变原本php的运行方式，不是在函数运行结束切换栈帧，而是在函数执行当前op_array中间任意时候（swoole内部控制为遇到IO等待），可以灵活切换到其他栈帧。**接下来我们将Zend VM和Swoole结合分析，如何创建协程栈，遇到IO切换，IO完成后栈恢复，以及协程退出时栈帧的销毁等细节。 先介绍协程PHP部分的主要结构

- 协程 php_coro_task

```c
struct php_coro_task
{
    /* 只列出关键结构*/
    /*...*/
    zval *vm_stack_top;//栈顶
    zval *vm_stack_end;//栈底
    zend_vm_stack vm_stack;//当前协程栈指针
    /*...*/
    zend_execute_data *execute_data;//当前协程栈帧
    /*...*/
    php_coro_task *origin_task;//上一个协程栈帧，类比prev_execute_data的作用
};
```

协程切换主要是针对当前栈执行发生中断时对上下文保存，和恢复。结合上面VM的执行流程我们可以知道上面几个字段的作用。

- `execute_data` 栈帧指针需要保存和恢复是毋容置疑的
- `vm_stack*` 系列是什么作用呢？原因是PHP是动态语言，我们上面分析到，每次有新函数进入执行和退出的时候，都需要在全局stack上创建和释放栈帧，所以需要正确保存和恢复对应的全局栈指针，才能保障每个协程栈帧得到释放，不会导致内存泄漏的问题。（当以debug模式编译PHP后，每次释放都会检查当全局栈是否合法）
- `origin_task` 是当前协程执行结束后需要自动执行的前一个栈帧。

主要涉及到的操作有:

- 协程的创建 `create`，在全局stack上为协程申请栈帧。

  - 协程的创建是创建一个闭包函数，将函数(可以理解为需要执行的op_array)当作一个参数传入Swoole的内建函数go();

- 协程让出，`yield`，遇到IO，保存当前栈帧的上下文信息

- 协程的恢复，`resume`,IO完成，恢复需要执行的协程上下文信息到yield让出前的状态

- 协程的退出，`exit`,协程op_array全部执行完毕，释放栈帧和swoole协程的相关数据。

  经过上面的介绍大家应该对Swoole协程在运行过程中可以在函数内部实现跳转有一个大概了解，回到最初我们例子结合上面php执行细节，我们能够知道，该例子会生成3个op_array,分别为 主脚本，协程1，协程2。我们可以利用一些工具打印出opcodes来直观的观察一下。通常我们会使用下面两个工具

```
//Opcache, version >= PHP 7.1
php -d opcache.opt_debug_level=0x10000 test.php

//vld, 第三方扩展
php -d vld.active=1 test.php
```

我们用opcache来观察没有被优化前的opcodes,我们可以很清晰的看到这三组op_array的详细信息。

```ini
php -dopcache.enable_cli=1 -d opcache.opt_debug_level=0x10000 test.php
$_main: ; (lines=11, args=0, vars=0, tmps=4)
    ; (before optimizer)
    ; /path-to/test.php:2-6
L0 (2):     INIT_FCALL 1 96 string("go")
L1 (2):     T0 = DECLARE_LAMBDA_FUNCTION string("")
L2 (6):     SEND_VAL T0 1
L3 (6):     DO_ICALL
L4 (7):     ECHO string("main flag
")
L5 (8):     INIT_FCALL 1 96 string("go")
L6 (8):     T2 = DECLARE_LAMBDA_FUNCTION string("")
L7 (12):    SEND_VAL T2 1
L8 (12):    DO_ICALL
L9 (13):    ECHO string("main end
")
L10 (14):   RETURN int(1)

{closure}: ; (lines=6, args=0, vars=0, tmps=1)
    ; (before optimizer)
    ; /path-to/test.php:2-6
L0 (9):     ECHO string("coro 2 start
")
L1 (10):    INIT_STATIC_METHOD_CALL 1 string("co") string("sleep")
L2 (10):    SEND_VAL_EX int(1) 1
L3 (10):    DO_FCALL//yiled from 当前op_array [coro 1] ; resume
L4 (11):    ECHO string("coro 2 exit
")
L5 (12):    RETURN null

{closure}: ; (lines=6, args=0, vars=0, tmps=1)
    ; (before optimizer)
    ; /path-to/test.php:2-6
L0 (3):     ECHO string("coro 1 start
")
L1 (4):     INIT_STATIC_METHOD_CALL 1 string("co") string("sleep")
L2 (4):     SEND_VAL_EX int(1) 1
L3 (4):     DO_FCALL//yiled from 当前op_array [coro 2];resume
L4 (5):     ECHO string("coro 1 exit
")
L5 (6):     RETURN null
coro 1 start
main flag
coro 2 start
main end
coro 1 exit
coro 2 exit
```

Swoole在执行`co::sleep()`的时候让出当前控制权，跳转到下一个op_array,结合以上注释，也就是在`DO_FCALL`的时候分别让出和恢复协程执行栈，达到原生协程控制流跳转的目的。

我们分析下 `INIT_FCALL` `DO_FCALL`指令在内核中如何执行。以便于更好理解函数调用栈切换的关系。

> VM内部指令会根据当前的操作数返回值等特殊化为一个c函数，我们这个例子中 有以下对应关系

> `INIT_FCALL` => ZEND_INIT_FCALL_SPEC_CONST_HANDLER

> `DO_FCALL` => ZEND_DO_FCALL_SPEC_RETVAL_UNUSED_HANDLER

```c
ZEND_INIT_FCALL_SPEC_CONST_HANDLER(ZEND_OPCODE_HANDLER_ARGS)
{
    USE_OPLINE

    zval *fname = EX_CONSTANT(opline->op2);
    zval *func;
    zend_function *fbc;
    zend_execute_data *call;

    fbc = CACHED_PTR(Z_CACHE_SLOT_P(fname));
    if (UNEXPECTED(fbc == NULL)) {
        func = zend_hash_find(EG(function_table), Z_STR_P(fname));
        if (UNEXPECTED(func == NULL)) {
            SAVE_OPLINE();
            zend_throw_error(NULL, "Call to undefined function %s()", Z_STRVAL_P(fname));
            HANDLE_EXCEPTION();
        }
        fbc = Z_FUNC_P(func);
        CACHE_PTR(Z_CACHE_SLOT_P(fname), fbc);
        if (EXPECTED(fbc->type == ZEND_USER_FUNCTION) && UNEXPECTED(!fbc->op_array.run_time_cache)) {
            init_func_run_time_cache(&fbc->op_array);
        }
    }

    call = zend_vm_stack_push_call_frame_ex(
        opline->op1.num, ZEND_CALL_NESTED_FUNCTION,
        fbc, opline->extended_value, NULL, NULL); //从全局stack上申请当前函数的执行栈
    call->prev_execute_data = EX(call); //将正在执行的栈赋值给将要执行函数栈的prev_execute_data，函数执行结束后恢复到此处
    EX(call) = call; //将函数栈赋值到全局执行栈，即将要执行的函数栈
    ZEND_VM_NEXT_OPCODE();
}
ZEND_DO_FCALL_SPEC_RETVAL_UNUSED_HANDLER(ZEND_OPCODE_HANDLER_ARGS)
{
	USE_OPLINE
	zend_execute_data *call = EX(call);//获取到执行栈
	zend_function *fbc = call->func;//当前函数
	zend_object *object;
	zval *ret;

	SAVE_OPLINE();//有全局寄存器的时候 ((execute_data)->opline) = opline
	EX(call) = call->prev_execute_data;//当前执行栈execute_data->call = EX(call)->prev_execute_data 函数执行结束后恢复到被调函数
	/*...*/
	LOAD_OPLINE();

	if (EXPECTED(fbc->type == ZEND_USER_FUNCTION)) {
		ret = NULL;
		if (0) {
			ret = EX_VAR(opline->result.var);
			ZVAL_NULL(ret);
		}

		call->prev_execute_data = execute_data;
		i_init_func_execute_data(call, &fbc->op_array, ret);

		if (EXPECTED(zend_execute_ex == execute_ex)) {
			ZEND_VM_ENTER();
		} else {
			ZEND_ADD_CALL_FLAG(call, ZEND_CALL_TOP);
			zend_execute_ex(call);
		}
	} else if (EXPECTED(fbc->type < ZEND_USER_FUNCTION)) {
		zval retval;

		call->prev_execute_data = execute_data;
		EG(current_execute_data) = call;
		/*...*/
		ret = 0 ? EX_VAR(opline->result.var) : &retval;
		ZVAL_NULL(ret);

		if (!zend_execute_internal) {
			/* saves one function call if zend_execute_internal is not used */
			fbc->internal_function.handler(call, ret);
		} else {
			zend_execute_internal(call, ret);
		}

		EG(current_execute_data) = execute_data;
		zend_vm_stack_free_args(call);//释放局部变量

		if (!0) {
			zval_ptr_dtor(ret);
		}

	} else { /* ZEND_OVERLOADED_FUNCTION */
		/*...*/
	}

fcall_end:
		/*...*/
	}
	zend_vm_stack_free_call_frame(call);//释放栈
	if (UNEXPECTED(EG(exception) != NULL)) {
		zend_rethrow_exception(execute_data);
		HANDLE_EXCEPTION();
	}
	ZEND_VM_SET_OPCODE(opline + 1);
	ZEND_VM_CONTINUE();
}
```

Swoole在PHP层可以按照以上方式来进行切换，至于执行过程中有IO等待发生，需要额外的技术来驱动，我们后续的文章将会介绍每个版本的驱动技术结合Swoole原有的事件模型，讲述Swoole协程如何进化到现在。

###### 原理详解

本篇我们开始深入PHP来分析Swoole协程的驱动部分，也就是C栈部分。

 由于我们系统存在C栈和PHP栈两部分，约定名字：

- C协程 C栈管理部分，
- PHP协程 PHP栈管理部分。

 增加C栈是4.x协程最重要也是最关键的部分，之前的版本种种无法完美支持PHP语法也是由于没有保存C栈信息。接下来我们将展开分析，C栈切换的支持最初我们是使用腾讯出品[libco](https://github.com/Tencent/libco)来支持，但通过压测会有内存读写错误而且开源社区很不活跃，有问题无法得到及时的反馈处理，所以，我们剥离的c++ boost库的汇编部分，现在的协程C栈的驱动就是在这个基础上做的。

 先来一张简单的系统架构图。
[![Swoole4.x架构图](https://camo.githubusercontent.com/300db6deee465a98a7f4d7a617d0cfa0f32831d5/68747470733a2f2f77696b692e73776f6f6c652e636f6d2f7374617469632f75706c6f6164732f77696b692f3230313930312f32392f3432313433303930303735302e706e67)](https://camo.githubusercontent.com/300db6deee465a98a7f4d7a617d0cfa0f32831d5/68747470733a2f2f77696b692e73776f6f6c652e636f6d2f7374617469632f75706c6f6164732f77696b692f3230313930312f32392f3432313433303930303735302e706e67)可以发现，Swoole的角色是粘合在系统API和php ZendVM，给PHPer用户深度接口编写高性能的代码;不仅如此，也支持给C++/C用户开发使用，详细请参考文档[C++开发者如何使用Swoole](https://wiki.swoole.com/wiki/page/633.html)。 C部分的代码主要分为几个部分

1. 汇编ASM驱动
2. Conext 上下文封装
3. Socket协程套接字封装
4. PHP Stream系封装，可以无缝协程化PHP相关函数
5. ZendVM结合层

Swoole底层系统层次更加分明，Socket将作为整个网络驱动的基石，原来的版本中，每个客户端都要基于异步回调的方式维护上下文，所以4.x版本较之前版本比较，无论是从项目的复杂程度，还是系统的稳定性，可以说都有一个质的飞跃。 代码目录层级

```
$ tree swoole-src/src/coroutine/
swoole-src/src/coroutine/
├── base.cc //C协程API,可回调PHP协程API
├── channel.cc //channel
├── context.cc //协程实现 基于ASM make_fcontext jump_fcontext
├── hook.cc //hook
└── socket.cc //网络操作协程封装
swoole-src/swoole_coroutine.cc //ZendVM相关封装，PHP协程API
```

我们从用户层到系统至上而下有 PHP协程API, C协程API, ASM协程API。其中Socket层是兼容系统API的网络封装。我们至下而上进行分析。 ASM x86-64架构为例，共有16个64位通用寄存器，各寄存器及用途如下

- %rax 通常用于存储函数调用的返回结果，同时也用于乘法和除法指令中。在imul 指令中，两个64位的乘法最多会产生128位的结果，需要 %rax 与 %rdx 共同存储乘法结果，在div 指令中被除数是128 位的，同样需要%rax 与 %rdx 共同存储被除数。
- %rsp 是堆栈指针寄存器，通常会指向栈顶位置，堆栈的 pop 和push 操作就是通过改变 %rsp 的值即移动堆栈指针的位置来实现的。
- %rbp 是栈帧指针，用于标识当前栈帧的起始位置
- %rdi, %rsi, %rdx, %rcx,%r8, %r9 六个寄存器用于存储函数调用时的6个参数
- %rbx，%r12，%r13，%14，%15 用作数据存储，遵循被调用者使用规则
- %r10，%r11 用作数据存储，遵循调用者使用规则

也就是说在进入汇编函数后，第一个参数值已经放到了 %rdi 寄存器中，第二个参数值已经放到了 %rsi 寄存器中，并且栈指针 %rsp 指向的位置即栈顶中存储的是父函数的返回地址 x86-64使用swoole-src/thirdparty/boost/asm/make_x86_64_sysv_elf_gas.S

```
//在当前栈顶创建一个上下文，用来执行执行第三个参数函数fn，返回初始化完成后的执行环境上下文
fcontext_t make_fcontext(void *sp, size_t size, void (*fn)(intptr_t));
make_fcontext:
    /* first arg of make_fcontext() == top of context-stack */
    movq  %rdi, %rax

    /* shift address in RAX to lower 16 byte boundary */
    andq  $-16, %rax

    /* reserve space for context-data on context-stack */
    /* size for fc_mxcsr .. RIP + return-address for context-function */
    /* on context-function entry: (RSP -0x8) % 16 == 0 */
    leaq  -0x48(%rax), %rax

    /* third arg of make_fcontext() == address of context-function */
    movq  %rdx, 0x38(%rax)

    /* save MMX control- and status-word */
    stmxcsr  (%rax)
    /* save x87 control-word */
    fnstcw   0x4(%rax)

    /* compute abs address of label finish */
    leaq  finish(%rip), %rcx
    /* save address of finish as return-address for context-function */
    /* will be entered after context-function returns */
    movq  %rcx, 0x40(%rax)

    ret /* return pointer to context-data * 返回rax指向的栈底指针，作为context返回/
//将当前上下文(包括栈指针，PC程序计数器以及寄存器)保存至*ofc，从nfc恢复上下文并开始执行。
intptr_t jump_fcontext(fcontext_t *ofc, fcontext_t nfc, intptr_t vp, bool preserve_fpu = false);

jump_fcontext:
//保存当前寄存器，压栈
    pushq  %rbp  /* save RBP */
    pushq  %rbx  /* save RBX */
    pushq  %r15  /* save R15 */
    pushq  %r14  /* save R14 */
    pushq  %r13  /* save R13 */
    pushq  %r12  /* save R12 */

    /* prepare stack for FPU */
    leaq  -0x8(%rsp), %rsp

    /* test for flag preserve_fpu */
    cmp  $0, %rcx
    je  1f

    /* save MMX control- and status-word */
    stmxcsr  (%rsp)
    /* save x87 control-word */
    fnstcw   0x4(%rsp)

1:
    /* store RSP (pointing to context-data) in RDI  保存当前栈顶到rdi 即:将当前栈顶指针保存到第一个参数%rdi ofc中*/
    movq  %rsp, (%rdi)

    /* restore RSP (pointing to context-data) from RSI 修改栈顶地址，为新协程的地址 ，rsi为第二个参数地址 */
    movq  %rsi, %rsp

    /* test for flag preserve_fpu */
    cmp  $0, %rcx
    je  2f

    /* restore MMX control- and status-word */
    ldmxcsr  (%rsp)
    /* restore x87 control-word */
    fldcw  0x4(%rsp)

2:
    /* prepare stack for FPU */
    leaq  0x8(%rsp), %rsp
// 寄存器恢复
    popq  %r12  /* restrore R12 */
    popq  %r13  /* restrore R13 */
    popq  %r14  /* restrore R14 */
    popq  %r15  /* restrore R15 */
    popq  %rbx  /* restrore RBX */
    popq  %rbp  /* restrore RBP */

    /* restore return-address  将返回地址放到 r8 寄存器中 */
    popq  %r8

    /* use third arg as return-value after jump*/
    movq  %rdx, %rax
    /* use third arg as first arg in context function */
    movq  %rdx, %rdi

    /* indirect jump to context */
    jmp  *%r8
```

context管理位于context.cc，是对ASM的封装，提供两个API

```c
bool Context::SwapIn()
bool Context::SwapOut()
```

最终的协程API位于base.cc,最主要的API为

```c
//创建一个c栈协程，并提供一个执行入口函数，并进入函数开始执行上下文
//例如PHP栈的入口函数Coroutine::create(PHPCoroutine::create_func, (void*) &php_coro_args);
long Coroutine::create(coroutine_func_t fn, void* args = nullptr); 
//从当前上下文中切出，并且调用钩子函数 例如php栈切换函数 void PHPCoroutine::on_yield(void *arg)
void Coroutine::yield()
//从当前上下文中切入，并且调用钩子函数 例如php栈切换函数 void PHPCoroutine::on_resume(void *arg)
void Coroutine::resume()
//C协程执行结束，并且调用钩子函数 例如php栈清理 void PHPCoroutine::on_close(void *arg)
void Coroutine::close()
```

接下来是ZendVM的粘合层 位于swoole-src/swoole_coroutine.cc

```c
PHPCoroutine 供C协程或者底层接口调用
//PHP协程创建入口函数，参数为php函数
static long create(zend_fcall_info_cache *fci_cache, uint32_t argc, zval *argv);
//C协程创建API
static void create_func(void *arg);
//C协程钩子函数 上一部分base.cc的C协程会关联到以下三个钩子函数
static void on_yield(void *arg);
static void on_resume(void *arg);
static void on_close(void *arg);
//PHP栈管理
static inline void vm_stack_init(void);
static inline void vm_stack_destroy(void);
static inline void save_vm_stack(php_coro_task *task);
static inline void restore_vm_stack(php_coro_task *task);
//输出缓存管理相关
static inline void save_og(php_coro_task *task);
static inline void restore_og(php_coro_task *task);
	
```

有了以上基础部分的建设，结合我们上一篇文章中PHP内核执行栈管理，就可以从C协程驱动PHP协程，实现C栈+PHP栈的双栈的原生协程。

下一篇文章，我们将挑一个客户端实现分析socket层，把协程和Swoole事件驱动结合来分析C协程以及PHP协程在底层网络库的应用和实践。