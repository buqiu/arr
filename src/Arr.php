<?php
/**
 * Name: 数组管理.
 * User: 董坤鸿
 * Date: 2022/05/02
 * Time: 18:50
 */

namespace Buqiu\Arr;

use Cassandra\Collection;

class Arr
{
    /**
     * 递归合并数组
     *
     * @param array $dest 原数组
     * @param array $result 覆盖的数组
     *
     * @return array
     */
    public static function merge(array $dest, array $result): array
    {
        $result = is_array($result) ? $result : [];
        foreach ($dest as $key => $value) {
            $result[$key] = $result[$key] ?? $value;
            $result[$key] = is_array($result[$key]) ? self::merge($value, $result[$key]) : $result[$key];
        }

        return $result;
    }

    /**
     * 返回多层栏目
     *
     * @param array $data 操作的数组
     * @param int|string|null $pid 一级PID的值
     * @param string $html 栏目名称前缀
     * @param string $fieldPri 唯一键名，如果是表则是表的主键
     * @param string $fieldPid 父ID键名
     * @param int $level 不需要传参数（执行时调用）
     * @param bool $related 是否为关联数组（默认true）
     *
     * @return array
     */
    public static function channelLevel(array $data, int|string $pid = null, string $html = "&nbsp;", string $fieldPri = 'id', string $fieldPid = 'pid', int $level = 1, bool $related = true): array
    {
        if (empty($data)) {
            return [];
        }
        $arr = [];
        foreach ($data as $value) {
            if ($value[$fieldPid] == $pid) {
                $arr[$value[$fieldPri]] = $value;
                $arr[$value[$fieldPri]]['_level'] = $level;
                $arr[$value[$fieldPri]]['_html'] = str_repeat($html, $level - 1);
                $arr[$value[$fieldPri]]["_data"] = self::channelLevel($data, $value[$fieldPri], $html, $fieldPri, $fieldPid, $level + 1, $related);
            }
        }

        return $related ? $arr : array_values($arr);
    }

    /**
     * 获得栏目列表
     *
     * @param array $arr 栏目数据
     * @param int|string|null $pid 操作的栏目
     * @param string $html 栏目名前字符
     * @param string $fieldPri 表主键
     * @param string $fieldPid 父id
     * @param int $level 等级
     *
     * @return array
     */
    public static function channelList(array $arr, int|string $pid = null, string $html = "&nbsp;", string $fieldPri = 'id', string $fieldPid = 'pid', int $level = 1): array
    {
        $pid = is_array($pid) ? $pid : [$pid];
        $data = [];
        foreach ($pid as $id) {
            $res = self::_channelList($arr, $id, $html, $fieldPri, $fieldPid, $level);
            foreach ($res as $k => $v) {
                $data[$k] = $v;
            }
        }
        if (empty($data)) {
            return $data;
        }
        foreach ($data as $n => $m) {
            if ($m['_level'] == 1) {
                continue;
            }
            $data[$n]['_first'] = false;
            $data[$n]['_end'] = false;
            if (!isset($data[$n - 1])
                || $data[$n - 1]['_level'] != $m['_level']
            ) {
                $data[$n]['_first'] = true;
            }
            if (isset($data[$n + 1])
                && $data[$n]['_level'] > $data[$n + 1]['_level']
            ) {
                $data[$n]['_end'] = true;
            }
        }
        //更新key为栏目主键
        $categories = [];
        foreach ($data as $d) {
            $categories[$d[$fieldPri]] = $d;
        }

        return $categories;
    }

    /**
     * 只供channelList方法使用
     *
     * @param array $data 栏目数据
     * @param int|string|null $pid 操作的栏目
     * @param string $html 栏目名前字符
     * @param string $fieldPri 表主键
     * @param string $fieldPid 父id
     * @param int $level 等级
     *
     * @return array
     */
    private static function _channelList(array $data, int|string $pid = null, string $html = "&nbsp;", string $fieldPri = 'id', string $fieldPid = 'pid', int $level = 1): array
    {
        if (empty($data)) {
            return [];
        }
        $arr = [];
        foreach ($data as $value) {
            $id = $value[$fieldPri];
            if ($value[$fieldPid] == $pid) {
                $value['_level'] = $level;
                $value['_html'] = str_repeat($html, $level - 1);
                array_push($arr, $value);
                $tmp = self::_channelList($data, $id, $html, $fieldPri, $fieldPid, $level + 1);
                $arr = array_merge($arr, $tmp);
            }
        }

        return $arr;
    }

    /**
     * 解析多级栏目
     *
     * @param Collection $categories 栏目数据
     * @param int|string|null $pid 操作的栏目
     * @param string $title 标题
     * @param string $id 表主键
     * @param string $parent_id 父id
     * @param int $level
     *
     * @return Collection
     */
    public static function categories(Collection $categories, int|string $pid = null, string $title = 'title', string $id = 'id', string $parent_id = 'parent_id', int $level = 1): Collection
    {
        $collection = collect([]);
        foreach ($categories as $category) {
            if ($category[$parent_id] == $pid) {
                $category['level'] = $level;
                $category['_' . $title] = ($level == 1 ? '' : '|' . str_repeat('-', $level)) . $category[$title];
                $collection->push($category);
                $collection = $collection->merge(
                    self::categories(
                        $categories,
                        $category[$id],
                        $title,
                        $id,
                        $parent_id,
                        $level + 1
                    )
                );
            }
        }

        return $collection;
    }

    /**
     * 获得树状数据
     *
     * @param array $data 数据
     * @param string $title 字段名
     * @param string $fieldPri 主键id
     * @param string $fieldPid 父id
     *
     * @return array
     */
    public static function tree(array $data, string $title, string $fieldPri = 'id', string $fieldPid = 'pid'): array
    {
        if (!is_array($data) || empty($data)) {
            return [];
        }
        $arr = self::channelList($data, 0, '', $fieldPri, $fieldPid);
        $arrKeys = array_keys($arr);
        foreach (array_keys($arrKeys) as $k) {
            $key = $arrKeys[$k];
            $value = $arr[$key];
            $str = "";
            if ($value['_level'] > 2) {
                for ($i = 1; $i < $value['_level'] - 1; $i++) {
                    $str .= "│&nbsp;&nbsp;&nbsp;&nbsp;";
                }
            }
            if ($value['_level'] != 1) {
                $t = $title ? $value[$title] : '';
                if (isset($arrKeys[$k + 1]) && isset($arr[$arrKeys[$k + 1]]) && $arr[$arrKeys[$k + 1]]['_level'] >= $value['_level']) {
                    $arr[$key]['_' . $title] = $str . "├─ " . $value['_html'] . $t;
                } else {
                    $arr[$key]['_' . $title] = $str . "└─ " . $value['_html'] . $t;
                }
            } else {
                $arr[$key]['_' . $title] = $value[$title];
            }
        }
        //设置主键为$fieldPri
        $data = [];
        foreach ($arr as $array) {
            //$data[$array[$fieldPri]] = $array;
            $data[] = $array;
        }

        return $data;
    }

    /**
     * 获得所有父级栏目
     *
     * @param array $data 栏目数据
     * @param int|string $sid 子栏目
     * @param string $fieldPri 唯一键名，如果是表则是表的主键
     * @param string $fieldPid 父ID键名
     *
     * @return array
     */
    public static function parentChannel(array $data, int|string $sid, string $fieldPri = 'id', string $fieldPid = 'pid'): array
    {
        if (empty($data)) {
            return $data;
        } else {
            $arr = [];
            foreach ($data as $value) {
                if ($value[$fieldPri] == $sid) {
                    $arr[] = $value;
                    $_n = self::parentChannel(
                        $data,
                        $value[$fieldPid],
                        $fieldPri,
                        $fieldPid
                    );
                    if (!empty($_n)) {
                        $arr = array_merge($arr, $_n);
                    }
                }
            }

            return $arr;
        }
    }

    /**
     * 判断$s_cid是否是$d_cid的子栏目
     *
     * @param array $data 栏目数据
     * @param int|string $sid 子栏目id
     * @param int|string $pid 父栏目id
     * @param string $fieldPri 主键
     * @param string $fieldPid 父id字段
     *
     * @return bool
     */
    public static function isChild(array $data, int|string $sid, int|string $pid, string $fieldPri = 'id', string $fieldPid = 'pid'): bool
    {
        $_data = self::channelList($data, $pid, '', $fieldPri, $fieldPid);
        foreach ($_data as $c) {
            //目标栏目为源栏目的子栏目
            if ($c[$fieldPri] == $sid) {
                return true;
            }
        }

        return false;
    }

    /**
     * 检测是不否有子栏目
     *
     * @param array $data 栏目数据
     * @param int|string $id 要判断的栏目id
     * @param string $fieldPid 父id表字段名
     *
     * @return bool
     */
    public static function hasChild(array $data, int|string $id, string $fieldPid = 'pid'): bool
    {
        foreach ($data as $value) {
            if ($value[$fieldPid] == $id) {
                return true;
            }
        }

        return false;
    }

    /**
     * 递归实现迪卡尔乘积
     *
     * @param array $arr 操作的数组
     * @param array $tmp
     *
     * @return array
     */
    public static function descartes(array $arr, array $tmp = []): array
    {
        $new_arr = [];
        foreach (array_shift($arr) as $value) {
            $tmp[] = $value;
            if ($arr) {
                self::descartes($arr, $tmp);
            } else {
                $new_arr[] = $tmp;
            }
            array_pop($tmp);
        }

        return $new_arr;
    }

    /**
     * 从数组中移除给定的值
     *
     * @param array $data 原数组数据
     * @param array $values 要移除的值
     *
     * @return array
     */
    public static function del(array $data, array $values): array
    {
        $news = [];
        foreach ($data as $key => $d) {
            if (!in_array($d, $values)) {
                $news[$key] = $d;
            }
        }

        return $news;
    }

    /**
     * 根据键名获取数据
     * 如果键名不存在时返回默认值
     *
     * @param array $data 数据
     * @param string|null $key 名称
     * @param mixed $value 默认值
     *
     * @return array|mixed|null
     */
    public static function get(array $data, string $key = null, mixed $value = null): mixed
    {
        $exp = explode('.', $key);
        foreach ((array)$exp as $d) {
            if (isset($data[$d])) {
                $data = $data[$d];
            } else {
                return $value;
            }
        }

        return $data;
    }

    /**
     * 排队字段获取数据
     *
     * @param array $data 数据
     * @param array $extName 排除的字段
     *
     * @return array
     */
    public static function getExtName(array $data, array $extName): array
    {
        $extData = [];
        foreach ((array)$data as $k => $v) {
            if (!in_array($k, $extName)) {
                $extData[$k] = $v;
            }
        }

        return $extData;
    }

    /**
     * 设置数组元素值支持点语法
     *
     * @param array $data
     * @param $key
     * @param $value
     *
     * @return array
     */
    public static function set(array $data, $key, $value): array
    {
        $tmp =& $data;
        foreach (explode('.', $key) as $v) {
            if (!isset($tmp[$v])) {
                $tmp[$v] = [];
            }
            $tmp = &$tmp[$v];
        }
        $tmp = $value;

        return $data;
    }

    /**
     * 将数组键名变成大写或小写
     *
     * @param array $arr 数组
     * @param int $type 转换方式 1大写   0小写
     *
     * @return array
     */
    public static function keyCase(array $arr, int $type = 0): array
    {
        $func = $type ? 'strtoupper' : 'strtolower';
        $data = []; //格式化后的数组
        foreach ($arr as $key => $value) {
            $key = $func($key);
            $data[$key] = is_array($value) ? self::keyCase($value, $type) : $value;
        }

        return $data;
    }

    /**
     * 不区分大小写检测数据键名是否存在
     *
     * @param string $key
     * @param array $arr
     *
     * @return bool
     */
    public static function keyExists(string $key, array $arr): bool
    {
        return array_key_exists(strtolower($key), $arr);
    }

    /**
     * 将数组中的值全部转为大写或小写
     *
     * @param array $arr
     * @param int $type 类型 1值大写 0值小写
     *
     * @return array
     */
    public static function valueCase(array $arr, int $type = 0): array
    {
        $func = $type ? 'strtoupper' : 'strtolower';
        $data = []; //格式化后的数组
        foreach ($arr as $k => $v) {
            $data[$k] = is_array($v) ? self::valueCase($v, $type) : $func($v);
        }

        return $data;
    }

    /**
     * 数组进行整数映射转换
     *
     * @param array $arr 数据
     * @param array $map
     *
     * @return array
     */
    public static function intToString(array $arr, array $map = ['status' => ['0' => '禁止', '1' => '启用']]): array
    {
        foreach ($map as $name => $m) {
            if (isset($arr[$name]) && array_key_exists($arr[$name], $m)) {
                $arr['_' . $name] = $m[$arr[$name]];
            }
        }

        return $arr;
    }

    /**
     * 数组中的字符串数字转为INT类型
     *
     * @param array $data
     *
     * @return array
     */
    public static function stringToInt(array $data): array
    {
        $tmp = $data;
        foreach ((array)$tmp as $k => $v) {
            $tmp[$k] = is_array($v) ? self::stringToInt($v)
                : (is_numeric($v) ? intval($v) : $v);
        }

        return $tmp;
    }

    /**
     * 根据下标过滤数据元素
     *
     * @param array $data 原数组数据
     * @param array $keys 参数的下标
     * @param int $type 1 存在在$keys时过滤  0 不在时过滤
     *
     * @return array
     */
    public static function filterKeys(array $data, array $keys, int $type = 1): array
    {
        $tmp = $data;
        foreach ($data as $k => $v) {
            if ($type == 1) {
                //存在时过滤
                if (in_array($k, $keys)) {
                    unset($tmp[$k]);
                }
            } else {
                //不在时过滤
                if (!in_array($k, $keys)) {
                    unset($tmp[$k]);
                }
            }
        }

        return $tmp;
    }
}
