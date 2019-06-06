#### 队列(Queue)

异步并发的服务器里经常使用队列实现 `生产者-消费者` 模型，解决并发排队问题。php 的 spl标准库中提供了 `SplQueue` 扩展内置的队列数据结构。另外 php 的数组也提供了 `array_pop` 和 `array_shift` 可以使用数组来模拟队列数据结构。

###### SplQueue

```php
$queue = new SplQueue;
// 入队
$queue->push($data);
// 出对
$data = $queue->shift();
// 查询队列中的排队数量
$n = count($queue);
```

###### Array模拟队列

```php
$queue = array();
// 入队
$queue[] = $data;
// 出队
$data = array_shift($queue);
// 查询队列中的排队数量
$n = count($queue);
```

###### 性能对比

虽然使用 Array 实现队列，但实际上性能会非常差。在一个大并发的服务器程序上，建议使用 `SplQueue` 作为队列数据结构。

100万条数据随机入队，出对，使用 `SplQueue` 仅用 `2312.345ms` 即可完成，而使用 Array 模拟的队列的程序根本无法完成测试，CPU使用率持续高达 100%

降低数据量到 1 万条以后，也需要 `260ms` 才能完成测试。

```php
// SplQueue

$splq = new SplQueue;
for($i = 0; $i < 1000000; $i++)
{
    $data = "hello $i\n";
    $splq->push($data);
    if($i % 100 == 99 and count($splq) > 100) {
        $popN = rand(10, 99);
        for($j = 0; $j < $popN; $j++) {
            $splq->shift();
        }
    }
}
$popN = count($splq);
for($j = 0; $j < $popN; $j++) {
    $splq->pop();
}
```

```php
// Array

$arrq = array();
for($i = 0; $i < 1000000; $i++) {
    $data = "hello $i\n";
    $arrq[] = $data;
    if($i % 100 == 99 and count($arrq) > 100) {
        $popN = rand(10, 99);
        for($j = 0; $j < $popN; $j++) {
            array_shift($arrq);
        }
    }
}
$popN = count($arrq);
for($j = 0; $j < $popN; $j++) {
    array_shift($arrq);
}
```

