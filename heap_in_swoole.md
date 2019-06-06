#### 堆(Heap)

在服务器程序开发中经常要用到排序功能，如会员积分榜。普通的 `array` 使用 `sort` 排序，即使用了最快的快排算法，实际上也会存在较大的时间开销。因此在内存中维护一个有序的内存结构，可以有效地避免 `sort` 排序的计算开销。

在php 中 `SplHeap` 就是一种有序的数据结构。数据总是按照最小或最大排在靠前的位置。新插入的数据会自动进行排序。

###### 定义

`SplHeap` 数据结构需要指定一个 `compare` 方法来进行元素的对比，从而实现自动排序。`SplHeap` 本身是 `abstract` 的，不能直接 `new`。

需要编写一个子类，并实现 `compare` 方法

```php
// 最大堆
class MaxHeap extends SplHeap
{
    protected function compare($a, $b) {
        return $a - $b;
    }
}
// 最小堆
class MinHeap extends SplHeap
{
    protected function compare($a, $b) {
        return $b - $a;
    }
}
```

###### 使用

定义好子类后，可使用 `insert` 方法插入元素。插入的元素会使用 `compare` 方法与已有元素进行对比，自动排序。

```php
$list = new MaxHeap;
$list->insert(56);
$list->insert(22);
$list->insert(35);
$list->insert(11);
$list->insert(88);
$list->insert(36);
$list->insert(89);
$list->insert(123);
```

`SplHeap` 底层使用跳表数据结构， `insert` 操作的时间复杂度为 O(Log(n))

注意这里只能插入数字，因为我们定义的 `compose` 不支持非数字对比。如果要支持插入数组或对象，可重新实现 `compare` 方法。

```php
class MyHeap extends SplHeap
{
    protected function compare($a, $b) {
        return $a->value - $b->value;
    }
}
class MyObject
{
    public $value;
    function __construct($value) {
        $this->value = $value;
    }
}
$list = new MyHeap;
$list->insert(new MyObject(56));
$list->insert(new MyObject(12));
```

使用 `foreach` 遍历堆，可以发现是有序输出。

```php
foreach($list as $li)
{
    echo $li . "\n";
}
```

