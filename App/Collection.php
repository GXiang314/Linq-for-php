<?php

namespace App;

use stdClass;

class Collection
{
    private $data;

    public function __construct($data = [])
    {
        $this->data = $data;
    }

    /* #region Select 查詢 */
    public function select(...$keys)
    {
        $result = [];
        foreach ($this->data as $item) {
            $selected = new stdClass();
            foreach ($keys as $key) {
                if (strpos($key, strtolower(' as ')) !== false) {
                    [$originalKey, $newKey] = explode(' as ', $key);
                    $selected->{$newKey} = $item->{$originalKey};
                } else {
                    $selected->{$key} = $item->{$key};
                }
            }
            $result[] = $selected;
        }
        return new static($result);
    }
    /* #endregion */

    /* #region Join 合併查詢 */
    public function join($innerList, $outerKey, $innerKey, $resultCallback)
    {
        $result = [];
        foreach ($this->data as $outer) {
            foreach ($innerList as $inner) {
                $match = true;
                if (is_array($innerKey)) {
                    $match = array_reduce($innerKey, function ($carry, $key) use ($outer, $inner) {
                        return $carry && ($outer->{$key} == $inner->{$key});
                    }, true);
                } else {
                    $match = $outer->{$outerKey} == $inner->{$innerKey};
                }
                if ($match) {
                    $result[] = $resultCallback($outer, $inner);
                }
            }
        }
        return new static($result);
    }
    /* #endregion */

    /* #region Where 條件查詢 */
    public function where($key, $operator = "===", $value = null)
    {
        if (is_null($value)) {
            $value = $operator;
            $operator = '===';
        }
        $filtered = array_filter($this->data, function ($item) use ($key, $operator, $value) {
            $operator = strtoupper($operator);
            switch ($operator) {
                case '===':
                    return $item[$key] === $value;
                case '==':
                    return $item[$key] == $value;
                case '=':
                    return $item[$key] == $value;
                case '>':
                    return $item[$key] > $value;
                case '<':
                    return $item[$key] < $value;
                case '>=':
                    return $item[$key] >= $value;
                case '<=':
                    return $item[$key] <= $value;
                case '!=':
                    return $item[$key] !== $value;
                case 'IN':
                    return in_array($item[$key], $value);
                case 'NOT IN':
                    return !in_array($item[$key], $value);
                case 'LIKE':
                    return strpos($item[$key], $value) !== false;
                case 'NOT LIKE':
                    return strpos($item[$key], $value) === false;
                default:
                    return false;
            }
        });

        return new static(array_values($filtered));
    }
    /* #endregion */

    /* #region OrWhere 條件查詢 */
    public function orWhere($key, $value)
    {
        $filtered = array_filter($this->data, function ($item) use ($key, $value) {
            return $item->$key == $value;
        });
        $this->data = array_merge($this->data, $filtered);
        return $this;
    }
    /* #endregion */

    /* #region WhereIn 條件查詢 */
    public function whereIn($key, $values)
    {
        return new static(array_filter($this->data, function ($item) use ($key, $values) {
            return in_array($item[$key], $values);
        }));
    }
    /* #endregion */

    /* #region Map 組合查詢 */
    public function map($callback)
    {
        return new static(array_map($callback, $this->data));
    }
    /* #endregion */

    /* #region GroupBy 聚合查詢 */
    public function groupBy($keys, $datalistName = 'datalist')
    {
        $result = [];
        foreach ($this->data as $item) {
            $groupKey = new stdClass();
            foreach ($keys as $key) {
                $groupKey->{$key} = $item->{$key};
                unset($item->{$key});
            }
            if (!isset($result[serialize($groupKey)])) {
                $result[serialize($groupKey)] = (object)array_merge((array)$groupKey, [$datalistName => []]);
            }
            $result[serialize($groupKey)]->{$datalistName}[] = (array)$item;
        }
        return new static(array_values($result));
    }
    /* #endregion */

    /* #region Max 最大值 */
    public function max($key = null)
    {
        if ($key === null) {
            return max($this->data);
        } else {
            return max(array_column($this->data, $key));
        }
    }
    /* #endregion */

    /* #region Min 最小值 */
    public function min($key = null)
    {
        if ($key === null) {
            return min($this->data);
        } else {
            return min(array_column($this->data, $key));
        }
    }
    /* #endregion */

    /* #region Avg 平均 */
    public function avg($key = null)
    {
        if ($key === null) {
            return array_sum($this->data) / count($this->data);
        } else {
            return array_sum(array_column($this->data, $key)) / count($this->data);
        }
    }
    /* #endregion */

    /* #region Sum 彙總 */
    public function sum($key = null)
    {
        if ($key === null) {
            return array_sum($this->data);
        } else {
            return array_sum(array_column($this->data, $key));
        }
    }
    /* #endregion */

    /* #region First 第一筆元素 */
    public function first($default = null, $predicate = null)
    {
        if ($predicate === null) {
            return count($this->data) > 0 ? $this->data[0] : $default;
        }
        foreach ($this->data as $item) {
            if ($predicate($item)) {
                return $item;
            }
        }
        return $default;
    }
    /* #endregion */

    /* #region FirstOrDefault 第一筆元素或預設值 */
    public function firstOrDefault($default = null, $predicate = null)
    {
        return $this->first($default, $predicate);
    }
    /* #endregion */

    /* #region Last 最後一筆元素 */
    public function last($default = null, $predicate = null)
    {
        if ($predicate === null) {
            return count($this->data) > 0 ? end($this->data) : $default;
        }
        $reversed = array_reverse($this->data);
        foreach ($reversed as $item) {
            if ($predicate($item)) {
                return $item;
            }
        }
        return $default;
    }
    /* #endregion */

    /* #region LastOrDefault 最後一筆元素或預設值 */
    public function lastOrDefault($default = null, $predicate = null)
    {
        return $this->last($default, $predicate);
    }
    /* #endregion */

    /* #region Count 目前元素個數 */
    public function count()
    {
        return count($this->data);
    }
    /* #endregion */

    /* #region Get 取得目前資料物件 */
    public function get()
    {
        return new static($this->data);
    }
    /* #endregion */

    /* #region ToList 取得目前資料 */
    public function toList()
    {
        return $this->data;
    }
    /* #endregion */

    /* #region ToArray 轉換陣列 */
    public function toArray()
    {
        $array = [];

        foreach ($this->data as $key => $item) {
            if (is_array($item)) {
                $array[$key] = (new static($item))->toArray();
            } elseif (is_object($item)) {
                $array[$key] = (new static(get_object_vars($item)))->toArray();
            } else {
                $array[$key] = $item;
            }
        }

        return $array;
    }
    /* #endregion */

    /* #region ToPage 分頁 */
    public function toPage($page, $limit)
    {
        $offset = ($page - 1) * $limit;
        $paged = array_slice($this->data, $offset, $limit);

        return new static($paged);
    }
    /* #endregion */

    /* #region Skip 略過幾筆元素 */
    public function skip($count)
    {
        return new static(array_slice($this->data, $count));
    }
    /* #endregion */

    /* #region Take 取得幾筆元素 */
    public function take($count)
    {
        return new static(array_slice($this->data, 0, $count));
    }
    /* #endregion */

    /* #region OrderBy 欄位排序 */
    public function orderBy($key, $direction = 'asc')
    {
        $direction = strtolower($direction);
        usort($this->data, function ($a, $b) use ($key, $direction) {
            if ($a[$key] == $b[$key]) {
                return 0;
            }
            if ($direction == 'asc') {
                return $a[$key] < $b[$key] ? -1 : 1;
            } else {
                return $a[$key] > $b[$key] ? -1 : 1;
            }
        });
        return $this;
    }
    /* #endregion */

    /* #region OrderByDescending 遞減欄位排序 */
    public function orderByDescending($key)
    {
        return $this->orderBy($key, 'desc');
    }
    /* #endregion */

    /* #region Sort 資料集排序 */
    public function sort($direction = 'asc')
    {
        $direction = strtolower($direction);
        $sorted = $this->data;
        if ($direction == 'asc') {
            sort($sorted);
        } else {
            rsort($sorted);
        }
        return new static($sorted);
    }
    /* #endregion */

    /* #region FindIndex 查詢指定元素 */
    public function findIndex($predicate)
    {
        $index = array_search(true, array_map($predicate, $this->data));
        return $index !== false ? $index : -1;
    }
    /* #endregion */

    /* #region FindAt 查詢索引 */
    public function findAt($index)
    {
        if ($index < 0 || $index >= count($this->data)) {
            return null;
        }

        return $this->data[$index];
    }
    /* #endregion */

    /* #region Union 聯集 */
    public function union($collection)
    {
        $merged = array_merge($this->data, $collection->toArray());
        $unique = array_unique($merged, SORT_REGULAR);
        return new static($unique);
    }
    /* #endregion */

    /* #region Intersect 交集 */
    public function intersect($collection)
    {
        $intersected = array_intersect($this->data, $collection->toArray());
        return new static($intersected);
    }
    /* #endregion */

    /* #region Except 差集 */
    public function except($collection)
    {
        $result = [];
        foreach ($this->data as $item) {
            if (!in_array($item, $collection->data)) {
                $result[] = $item;
            }
        }
        return new static($result);
    }
    /* #endregion */

    /* #region Add 加入元素 */
    public function add($item)
    {
        if (array_diff_key($item, $this->data[0])) {
            throw new \InvalidArgumentException('Keys in $item parameter do not match collection keys.');
        }
        $data = $this->data;
        $data[] = $item;
        return new static($data);
    }
    /* #endregion */

    /* #region AddRange 加入範圍 */
    public function addRange($items)
    {
        foreach ($items as $item) {
            $this->add($item);
        }
    }
    /* #endregion */

    /* #region Clear 清空目前資料集 */
    public function clear()
    {
        $this->data = null;
    }
    /* #endregion */

    /* #region Any 條件判斷 */
    public function any($callback = null)
    {
        if (is_null($callback)) {
            return empty($this->data);
        }

        foreach ($this->data as $item) {
            if ($callback($item)) {
                return true;
            }
        }
        return false;
    }
    /* #endregion */

    /* #region All 條件判斷 */
    public function all($predicate = null)
    {
        if (empty($this->data)) {
            return false;
        }

        if (is_null($predicate)) {
            foreach ($this->data as $item) {
                if (empty($item)) {
                    return false;
                }
            }
        } else {
            foreach ($this->data as $item) {
                if (!$predicate($item)) {
                    return false;
                }
            }
        }

        return true;
    }
    /* #endregion */
}
