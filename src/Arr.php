<?php
/**
 * Name: 数组管理.
 * User: 董坤鸿
 * Date: 2019/06/14
 * Time: 13:59
 */

namespace buqiu\Arr;

class Arr
{
    /**
     * 递归合并数组
     *
     * @param $dest //原数组
     * @param $result //覆盖的数组
     *
     * @return array
     */
    public function merge($dest, $result)
    {
        $result = is_array($result) ? $result : [];
        foreach ($dest as $key => $value) {
            $result[$key] = isset($result[$key]) ? $result[$key] : $value;
            $result[$key] = is_array($result[$key]) ? $this->merge($value, $result[$key]) : $result[$key];
        }

        return $result;
    }

    /**
     * 返回多层栏目
     *
     * @param $data //操作的数组
     * @param int $pid 一级PID的值
     * @param string $html 栏目名称前缀
     * @param string $fieldPri 唯一键名，如果是表则是表的主键
     * @param string $fieldPid 父ID键名
     * @param int $level 不需要传参数（执行时调用）
     *
     * @return array
     */
    public function channelLevel($data, int $pid = 0, string $html = "&nbsp;", string $fieldPri = 'id', string $fieldPid = 'pid', int $level = 1)
    {
        if (empty($data)) {
            return [];
        }
        $arrays = [];
        foreach ($data as $value) {
            if ($value[$fieldPid] == $pid) {
                $arrays[$value[$fieldPri]] = $value;
                $arrays[$value[$fieldPri]]['_level'] = $level;
                $arrays[$value[$fieldPri]]['_html'] = str_repeat($html, $level - 1);
                $arrays[$value[$fieldPri]]["_data"] = $this->channelLevel($data, $value[$fieldPri], $html, $fieldPri, $fieldPid, $level + 1);
            }
        }

        return $arrays;
    }

    /**
     * 获得栏目列表
     *
     * @param $arrays //栏目数据
     * @param int $pid 操作的栏目
     * @param string $html 栏目名前字符
     * @param string $fieldPri 表主键
     * @param string $fieldPid 父id
     * @param int $level 等级
     *
     * @return array
     */
    public function channelList($arrays, int $pid = 0, string $html = "&nbsp;", string $fieldPri = 'id', string $fieldPid = 'pid', int $level = 1)
    {
        $pid = is_array($pid) ? $pid : [$pid];
        $data = [];
        foreach ($pid as $id) {
            $res = $this->_channelList($arrays, $id, $html, $fieldPri, $fieldPid, $level);
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
     * @param $data //栏目数据
     * @param int $pid 操作的栏目
     * @param string $html 栏目名前字符
     * @param string $fieldPri 表主键
     * @param string $fieldPid 父id
     * @param int $level 等级
     *
     * @return array
     */
    private function _channelList($data, int $pid = 0, string $html = "&nbsp;", string $fieldPri = 'id', string $fieldPid = 'pid', int $level = 1)
    {
        if (empty($data)) {
            return [];
        }
        $arrays = [];
        foreach ($data as $value) {
            $id = $value[$fieldPri];
            if ($value[$fieldPid] == $pid) {
                $value['_level'] = $level;
                $value['_html'] = str_repeat($html, $level - 1);
                array_push($arrays, $value);
                $tmp = $this->_channelList($data, $id, $html, $fieldPri, $fieldPid, $level + 1);
                $arrays = array_merge($arrays, $tmp);
            }
        }

        return $arrays;
    }

    /**
     * 解析多级栏目
     *
     * @param $categories //栏目数据
     * @param int $pid 操作的栏目
     * @param string $title 标题
     * @param string $id 表主键
     * @param string $parent_id 父id
     * @param int $level
     *
     * @return mixed
     */
    public function categories($categories, int $pid = 0, string $title = 'title', string $id = 'id', string $parent_id = 'parent_id', int $level = 1)
    {
        $collection = collect([]);
        foreach ($categories as $category) {
            if ($category[$parent_id] == $pid) {
                $category['level'] = $level;
                $category['_' . $title] = ($level == 1 ? '' : '|' . str_repeat('-', $level)) . $category[$title];
                $collection->push($category);
                $collection = $collection->merge($this->categories($categories, $category[$id], $title, $id, $parent_id,
                    $level + 1));
            }
        }
        return $collection;
    }

    /**
     * 获得树状数据
     *
     * @param $data //数据
     * @param string $title 字段名
     * @param string $fieldPri 主键id
     * @param string $fieldPid 父id
     *
     * @return array
     */
    public function tree($data, string $title, string $fieldPri = 'id', $fieldPid = 'pid')
    {
        if (!is_array($data) || empty($data)) {
            return [];
        }
        $arrays = $this->channelList($data, 0, '', $fieldPri, $fieldPid);
        foreach ($arrays as $key => $value) {
            $str = "";
            if ($value['_level'] > 2) {
                for ($i = 1; $i < $value['_level'] - 1; $i++) {
                    $str .= "│&nbsp;&nbsp;&nbsp;&nbsp;";
                }
            }
            if ($value['_level'] != 1) {
                $t = $title ? $value[$title] : '';
                if (isset($arrays[$key + 1])
                    && $arrays[$key + 1]['_level'] >= $arrays[$key]['_level']
                ) {
                    $arrays[$key]['_' . $title] = $str . "├─ " . $value['_html'] . $t;
                } else {
                    $arrays[$key]['_' . $title] = $str . "└─ " . $value['_html'] . $t;
                }
            } else {
                $arrays[$key]['_' . $title] = $value[$title];
            }
        }
        //设置主键为$fieldPri
        $data = [];
        foreach ($arrays as $array) {
            //$data[$array[$fieldPri]] = $array;
            $data[] = $array;
        }

        return $data;
    }

    /**
     * 获得所有父级栏目
     *
     * @param $data //栏目数据
     * @param int $sid 子栏目
     * @param string $fieldPri 唯一键名，如果是表则是表的主键
     * @param string $fieldPid 父ID键名
     *
     * @return array
     */
    public function parentChannel($data, int $sid, string $fieldPri = 'id', string $fieldPid = 'pid')
    {
        if (empty($data)) {
            return $data;
        } else {
            $arrays = [];
            foreach ($data as $value) {
                if ($value[$fieldPri] == $sid) {
                    $arrays[] = $value;
                    $_n = $this->parentChannel(
                        $data,
                        $value[$fieldPid],
                        $fieldPri,
                        $fieldPid
                    );
                    if (!empty($_n)) {
                        $arrays = array_merge($arrays, $_n);
                    }
                }
            }

            return $arrays;
        }
    }

    /**
     * 判断$s_cid是否是$d_cid的子栏目
     *
     * @param $data //栏目数据
     * @param int $sid 子栏目id
     * @param int $pid 父栏目id
     * @param string $fieldPri 主键
     * @param string $fieldPid 父id字段
     *
     * @return bool
     */
    public function isChild($data, int $sid, int $pid, string $fieldPri = 'id', string $fieldPid = 'pid')
    {
        $_data = $this->channelList($data, $pid, '', $fieldPri, $fieldPid);
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
     * @param $data //栏目数据
     * @param int $id 要判断的栏目id
     * @param string $fieldPid 父id表字段名
     *
     * @return bool
     */
    public function hasChild($data, int $id, string $fieldPid = 'pid')
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
     * @param $arrays //操作的数组
     * @param array $tmp
     *
     * @return array
     */
    public function descArte($arrays, $tmp = [])
    {
        $new_arrays = [];
        foreach (array_shift($arrays) as $value) {
            $tmp[] = $value;
            if ($arrays) {
                $this->descArte($arrays, $tmp);
            } else {
                $new_arrays[] = $tmp;
            }
            array_pop($tmp);
        }

        return $new_arrays;
    }

    /**
     * 从数组中移除给定的值
     *
     * @param array $data 原数组数据
     * @param array $values 要移除的值
     *
     * @return array
     */
    public function del(array $data, array $values)
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
     * @param $data
     * @param string $key 名称
     * @param mixed $value 默认值
     *
     * @return array|mixed|null
     */
    public function get($data, $key = null, $value = null)
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
    public function getExtName(array $data, array $extName)
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
    public function set(array $data, $key, $value)
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
    public function keyCase($arr, $type = 0)
    {
        $func = $type ? 'strtoupper' : 'strtolower';
        $data = []; //格式化后的数组
        foreach ($arr as $key => $value) {
            $key = $func($key);
            $data[$key] = is_array($value) ? $this->keyCase($value, $type) : $value;
        }

        return $data;
    }

    /**
     * 不区分大小写检测数据键名是否存在
     *
     * @param $key
     * @param $arrays
     *
     * @return bool
     */
    public function keyExists($key, $arrays)
    {
        return array_key_exists(strtolower($key), $this->keyExists($arrays));
    }

    /**
     * 将数组中的值全部转为大写或小写
     *
     * @param array $arr
     * @param int $type 类型 1值大写 0值小写
     *
     * @return array
     */
    public function valueCase($arr, $type = 0)
    {
        $func = $type ? 'strtoupper' : 'strtolower';
        $data = []; //格式化后的数组
        foreach ($arr as $k => $v) {
            $data[$k] = is_array($v) ? $this->valueCase($v, $type) : $func($v);
        }

        return $data;
    }

    /**
     * 数组进行整数映射转换
     *
     * @param $arrays //数据
     * @param array $map
     *
     * @return mixed
     */
    public function intToString($arrays, array $map = ['status' => ['0' => '禁止', '1' => '启用']])
    {
        foreach ($map as $name => $m) {
            if (isset($arrays[$name]) && array_key_exists($arrays[$name], $m)) {
                $arrays['_' . $name] = $m[$arrays[$name]];
            }
        }

        return $arrays;
    }

    /**
     * 数组中的字符串数字转为INT类型
     *
     * @param $data
     *
     * @return mixed
     */
    public function stringToInt($data)
    {
        $tmp = $data;
        foreach ((array)$tmp as $k => $v) {
            $tmp[$k] = is_array($v) ? $this->stringToInt($v)
                : (is_numeric($v) ? intval($v) : $v);
        }

        return $tmp;
    }

    /**
     * 根据下标过滤数据元素
     *
     * @param array $data 原数组数据
     * @param string $keys 参数的下标
     * @param int $type 1 存在在$keys时过滤  0 不在时过滤
     *
     * @return array
     */
    public function filterKeys(array $data, $keys, $type = 1)
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