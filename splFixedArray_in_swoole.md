#### 定长数组（SplFixedArray）

php官方的SPL库提供了一个定长数组的数据结构，类似于C语言中的数组。和普通的php数组不同，定长数组的读写性能更好。

###### 官方测试数据

测试使用php5.4，64位的linux系统

```ini
small data(1, 000):
	write: SplFixedArray is 15 % faster
	read: SplFixedArray is 5 % faster
larger data(512, 000):
	write: SplFixedArray is 33 % faster
	read: SplFixedArray is 10 % faster
```

###### 使用方法

SplFixedArray 使用方法与 Array 相同，但只支持数字索引的访问方式。

```php
$array = new SplFixedArray(5);
$array[1] = 2;
$array[4] = "foo";

var_dump($array[0]);	// NULL
var_dump($array[1]);	// int(2)
```

可以使用 `setSize()` 方法动态改变定长数组的尺寸。

