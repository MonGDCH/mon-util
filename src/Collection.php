<?php

declare(strict_types=1);

namespace mon\util;

use Countable;
use ArrayAccess;
use ArrayIterator;
use mon\util\Common;
use IteratorAggregate;

/**
 * 数组集合
 * 
 * @author Mon <985558837@qq.com>
 * @version 1.0.0
 */
class Collection implements ArrayAccess, Countable, IteratorAggregate
{
    /**
     * 集合数据
     *
     * @var array
     */
    protected $data = [];

    /**
     * 构造方法
     *
     * @param array $data
     */
    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    /**
     * 获取集合数据
     *
     * @return array
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * 获取json数据
     *
     * @return string
     */
    public function getJson(): string
    {
        return json_encode($this->data, JSON_UNESCAPED_UNICODE);
    }

    /**
     * 获取完整XML
     *
     * @param string $root  根节点
     * @param string $charset   字符集编码
     * @return string
     */
    public function getFullXML(string $root = 'mon', string $charset = 'utf-8'): string
    {
        $data  = "<?xml version=\"1.0\" encoding=\"{$charset}\"?>";
        $data .= "<{$root}>";
        $data .= $this->getXML();
        $data .= "</{$root}>";
        return $data;
    }

    /**
     * 获取XML
     *
     * @return string
     */
    public function getXML(): string
    {
        return Common::arrToXML($this->data);
    }

    /**
     * 获取笛卡尔积规格
     *
     * @param array $data   最终实现的笛卡尔积组合,可不传
     * @return Collection
     */
    public function getSpecCartesian(array $data = []): Collection
    {
        $result = Common::specCartesian($this->data, $data);
        return new self($result);
    }

    /**
     * 序列化
     *
     * @param string $ds    分隔符
     * @param bool $startDS 起始是否携带分隔符
     * @return string
     */
    public function serialize(string $ds = "&", bool $startDS = false): string
    {
        $data = Common::mapToStr($this->data, $ds);
        return $startDS ? $data : ltrim($data, $ds);
    }

    /**
     * 拼接字符串
     *
     * @param string $ds  拼接符
     * @return string
     */
    public function join(string $ds = ','): string
    {
        return implode($ds, $this->data);
    }

    /**
     * 是否为空
     *
     * @return boolean
     */
    public function isEmpty(): bool
    {
        return empty($this->data);
    }

    /**
     * 去重
     *
     * @param integer $flag
     * @return Collection
     */
    public function unique(int $flag = SORT_STRING): Collection
    {
        return new self(array_unique($this->data, $flag));
    }

    /**
     * 二维数组去重(键&值不能完全相同)
     *
     * @return Collection
     */
    public function unique2D(): Collection
    {
        $data = Common::uniqueArray2D($this->data);
        return new self($data);
    }

    /**
     * 二维数组去重(值不能相同)
     *
     * @return Collection
     */
    public function valueUnique2D(): Collection
    {
        $data = Common::uniqueArrayValue2D($this->data);
        return new self($data);
    }

    /**
     * 获取所有键
     *
     * @return Collection
     */
    public function keys(): Collection
    {
        return new self(array_keys($this->data));
    }

    /**
     * 获取所有值
     *
     * @return Collection
     */
    public function values(): Collection
    {
        return new self(array_values($this->data));
    }

    /**
     * 补长数组
     *
     * @param integer $size 数组长度
     * @param mixed $value  补充值
     * @return Collection
     */
    public function pad(int $size, $value): Collection
    {
        return new self(array_pad($this->data, $size, $value));
    }

    /**
     * 排序
     *
     * @param  $callback
     * @return Collection
     */
    public function sort(callable $callback = null): Collection
    {
        $items = $this->data;
        $callback && is_callable($callback) ? uasort($items, $callback) : asort($items, $callback ?? SORT_REGULAR);

        return new self($items);
    }

    /**
     * 二维数组排序
     *
     * @param string $keys  排序的键名
     * @param integer $sort 排序方式，默认值：SORT_DESC
     * @return Collection
     */
    public function sort2D(string $keys, $sort = SORT_DESC): Collection
    {
        $data = Common::sortArray2D($this->data, $keys, $sort);
        return new self($data);
    }

    /**
     * 获取二维数组指定列的值
     *
     * @param string $key   列名
     * @return Collection
     */
    public function value2D(string $key): Collection
    {
        $data = [];
        foreach ($this->data as $item) {
            if (array_key_exists($key, $item)) {
                $data[] = $item[$key];
            }
        }
        return new self($data);
    }

    /**
     * 查找获取元素 
     *
     * @param callable $callback    回调方法
     * @param boolean $index    是否获取的是索引
     * @return mixed
     */
    public function find(callable $callback, bool $index = false)
    {
        foreach ($this->data as $key => $item) {
            if ($callback($item, $key, $this)) {
                return $index ? $key : $item;
            }
        }

        return null;
    }

    /**
     * reduce 聚合结果
     *
     * @param callable $callback    回调方法
     * @param mixed $default    默认值
     * @return mixed
     */
    public function reduce(callable $callback, $default = null)
    {
        $result = $default;
        foreach ($this->data as $key => $value) {
            $result = $callback($result, $value, $key, $this);
        }

        return $result;
    }

    /**
     * 集合总和
     *
     * @param string $key  二维数组键名
     * @return integer|float
     */
    public function sum(string $key = '')
    {
        return $this->reduce(function ($result, $item) use ($key) {
            $value = empty($key) ? $item : (isset($item[$key]) ? $item[$key] : 0);
            return $result + $value;
        }, 0);
    }

    /**
     * 集合平均数
     *
     * @param string $key  二维数组键名
     * @return integer|float
     */
    public function svg(string $key = '')
    {
        return $this->sum($key) / $this->count();
    }

    /**
     * 集合最小值
     *
     * @param string $key  二维数组键名
     * @return integer|float
     */
    public function min(string $key = '')
    {
        return $this->reduce(function ($result, $item) use ($key) {
            $value = empty($key) ? $item : (isset($item[$key]) ? $item[$key] : null);
            return is_null($result) || $value < $result ? $value : $result;
        });
    }

    /**
     * 集合最大值
     *
     * @param string $key  二维数组键名
     * @return integer|float
     */
    public function max(string $key = '')
    {
        return $this->reduce(function ($result, $item) use ($key) {
            $value = empty($key) ? $item : (isset($item[$key]) ? $item[$key] : null);
            return is_null($result) || $value > $result ? $value : $result;
        });
    }

    /**
     * pop 弹出数据
     *
     * @param integer $count pop个数
     * @return mixed
     */
    public function pop(int $count = 1)
    {
        if ($this->isEmpty()) {
            return null;
        }
        if ($count === 1) {
            return array_pop($this->data);
        }
        $results = [];
        $collectionCount = $this->count();
        foreach (range(1, min($count, $collectionCount)) as $item) {
            array_push($results, array_pop($this->data));
        }

        return new self($results);
    }

    /**
     * push 写入数据
     *
     * @param mixed $values   插入数据
     * @return Collection
     */
    public function push(...$values): Collection
    {
        foreach ($values as $item) {
            $this->data[] = $item;
        }

        return $this;
    }

    /**
     * unshift 写入数据
     *
     * @param mixed ...$values  插入数据
     * @return Collection
     */
    public function unshift(...$values): Collection
    {
        array_unshift($this->data, ...$values);
        return $this;
    }

    /**
     * 遍历 foreach
     *
     * @param mixed $callback 回调方法
     * @return Collection 当前对象
     */
    public function each(callable $callback): Collection
    {
        foreach ($this->data as $key => $value) {
            $break = $callback($value, $key, $this);
            if ($break === false) {
                break;
            }
        }

        return $this;
    }

    /**
     * 遍历 map
     *
     * @param callable $callback    回调方法
     * @return Collection 新对象
     */
    public function map(callable $callback): Collection
    {
        $data = [];
        foreach ($this->data as $key => $value) {
            $data[] = $callback($value, $key, $this);
        }

        return new self($data);
    }

    /**
     * 过滤 filter
     *
     * @param callable $callback    回调方法
     * @return Collection   新对象
     */
    public function filter(callable $callback): Collection
    {
        return new self(array_filter($this->data, $callback, ARRAY_FILTER_USE_BOTH));
    }

    /**
     * 合并数组 merge
     *
     * @param array $data 合并数据
     * @return Collection
     */
    public function merge(array $data): Collection
    {
        return new self(array_merge($this->data, $data));
    }

    /**
     * 拼接数组
     *
     * @param array $data   拼接数据
     * @return Collection
     */
    public function concat(array $data): Collection
    {
        $result = new self($this->data);
        foreach ($data as $item) {
            $result->push($item);
        }
        return $result;
    }

    /**
     * 将集合分成给定大小的块。
     *
     * @param  integer  $size
     * @return Collection
     */
    public function chunk(int $size): Collection
    {
        if ($size <= 0) {
            return new self;
        }

        $chunks = [];
        foreach (array_chunk($this->data, $size, true) as $chunk) {
            $chunks[] = new self($chunk);
        }

        return new self($chunks);
    }

    /**
     * 打乱排序
     *
     * @return Collection
     */
    public function shuffle(): Collection
    {
        shuffle($this->data);
        return $this;
    }

    /**
     * 通道调用
     *
     * @param callable $callback
     * @return mixed
     */
    public function pipe(callable $callback)
    {
        return $callback($this);
    }

    /**
     * 输出字符串
     *
     * @return string
     */
    public function __toString(): string
    {
        return $this->getJson();
    }

    /**
     * ArrayAccess相关处理方法, 判断是否存在某个值
     *
     * @param string $offset
     * @return boolean
     */
    #[\ReturnTypeWillChange]
    public function offsetExists($offset): bool
    {
        return array_key_exists($offset, $this->data);
    }

    /**
     * ArrayAccess相关处理方法, 获取某个值
     *
     * @param string $offset
     * @return mixed
     */
    #[\ReturnTypeWillChange]
    public function offsetGet($offset)
    {
        return $this->data[$offset];
    }

    /**
     * ArrayAccess相关处理方法, 设置某个值
     *
     * @param mixed $offset
     * @param mixed $value
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetSet($offset, $value): void
    {
        if (is_null($offset)) {
            $this->data[] = $value;
        } else {
            $this->data[$offset] = $value;
        }
    }

    /**
     * ArrayAccess相关处理方法, 删除某个值
     *
     * @param mixed $offset
     * @return void
     */
    #[\ReturnTypeWillChange]
    public function offsetUnset($offset): void
    {
        unset($this->data[$offset]);
    }

    /**
     * Countable相关处理方法，获取计数长度
     *
     * @return integer
     */
    #[\ReturnTypeWillChange]
    public function count(): int
    {
        return count($this->data);
    }

    /**
     * IteratorAggregate相关处理方法, 迭代器
     *
     * @return ArrayIterator
     */
    #[\ReturnTypeWillChange]
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->data);
    }
}
