<?php

// +----------------------------------------------------------------------
// | Little Mo - Tool [ WE CAN DO IT JUST TIDY UP IT ]
// +----------------------------------------------------------------------
// | Copyright (c) 2021 http://ggui.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: littlemo <25362583@qq.com>
// +----------------------------------------------------------------------

namespace littlemo\model;

use think\Cache;

class Model extends \think\Model
{
    // 表名

    // 自动写入时间戳字段
    protected $autoWriteTimestamp = 'int';

    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $tablePrimary = 'id';
    protected $deleteTime = false;

    protected $primaryId = 0; //主键ID
    protected $aliasName = 'a'; //(主表)别名
    protected $page = 1;
    protected $pagesize = 10;

    // 追加属性
    protected $append = [];

    //数据缓存时间
    protected $cacheTime = 3600;

    protected $message = '';
    protected $code = null;


    /**
     * 获取列表数据
     *
     * @description
     * @example
     * @author LittleMo 25362583@qq.com
     * @since 2021-04-02
     * @version 2021-04-02
     * @param array $params 筛选条件
     * @return array
     */
    public function getListData($params = [], $with = [])
    {
        $data = [];

        $page =  ($params['page'] ?? $this->page) ?: $this->page;
        $pagesize = ($params['pagesize'] ?? $this->pagesize) ?: $this->pagesize;
        $orderby = $params['orderby'] ?? $this->tablePrimary;
        $orderway = $params['orderway'] ?? 'desc';

        $wsql = $this->commonWsql($params, $with);

        $rows = $this
            ->alias($this->aliasName)
            ->where($wsql)
            ->with($with)
            ->page($page, $pagesize)
            ->order($orderby, $orderway)
            ->select();

        $this->parseListData($rows);
        $total = $this->totalCount($params, null, null, $with) ?: 0;
        $lastpage = ceil($total / $pagesize);
        return compact('total', 'page', 'pagesize',  'lastpage', 'rows');
    }

    /**
     * 解析列表数据
     *
     * @description
     * @example
     * @author LittleMo 25362583@qq.com
     * @since 2021-08-11
     * @version 2021-08-11
     * @param array $data
     * @return array
     */
    public function parseListData(&$data = [])
    {
        foreach ($data as $key => &$val) {
            if (is_object($val)) {
                $val = $val->toArray();
            }
        }
    }

    /**
     * 统计数量
     *
     * @description
     * @example
     * @author LittleMo 25362583@qq.com
     * @since 2021-04-02
     * @version 2021-04-02
     * @param array $params 筛选条件
     * @return int
     */
    public function totalCount($params = [], $group = null, $field = null, $join = [])
    {
        $wsql = $this->commonWsql($params, $join);

        $fieldNew = $this->initField($field, $group);

        // 统计
        $this->alias($this->aliasName)
            ->field($fieldNew);
        foreach ($join as $val) {
            $this->join($val[0], $val[1], $val[2] ?? null);
        }
        return  $this->where($wsql)
            ->group($group)
            ->count();
    }

    /**
     * 统计某个字段数字总和
     *
     * @description
     * @example
     * @author LittleMo 25362583@qq.com
     * @since 2021-04-02
     * @version 2021-04-02
     * @param array $params 筛选条件
     * @param string $field 字段名
     * @return int|float
     */
    public function totalSum($params = [], $field = '', $join = [])
    {
        $wsql = $this->commonWsql($params, $join);
        $this->alias($this->aliasName)->where($wsql);
        foreach ($join as $val) {
            $this->join($val[0], $val[1], $val[2] ?? null);
        }
        return $this->sum($field);
    }


    /**
     * 整理where
     * @description
     * @example
     * @author LittleMo 25362583@qq.com
     * @since 2022-01-15
     * @version 2022-01-15
     * @param array $params
     * @param array $with
     * @return array
     */
    protected  function commonWsql($params = [], $with = [])
    {

        foreach ($params as $key => $val) {
            //过滤分页和排序
            if (in_array($key, ['pagesize', 'page', 'orderby', 'orderway'])) {
                unset($params[$key]);
                continue;
            }

            //过滤空字段
            if (!is_array($val) && strlen($val) <= 0) {
                unset($params[$key]);
                continue;
            } elseif (is_array($val)) {
                if (count($val) == 3) {
                    // array(array('gt',3),array('lt',10), 'or');
                    if (empty($val[0][1]) && empty($val[1][1])) {
                        unset($params[$key]);
                        continue;
                    } else {
                        if (empty($val[0][1])) {
                            $params[$key] = $val[1];
                        } else
                        if (empty($val[1][1])) {
                            $params[$key] = $val[0];
                        }
                    }
                } elseif ((!is_array($val[1]) && strlen($val[1]) <= 0) || (is_array($val[1]) && count($val[1]) <= 0)) {
                    unset($params[$key]);
                    continue;
                }
            }
        }

        //时间筛选
        if ($this->createTime || $this->updateTime) {
            $k = $this->createTime ?: $this->updateTime;

            if (isset($params['start_date'])) {
                $params['start_time'] = strtotime($params['start_date']);
                unset($params['start_date']);
            }

            if (isset($params['end_date'])) {
                $params['end_time'] = strtotime($params['end_date']);
                if ('00' == date("H", $params['end_time']) && '00' == date("i", $params['end_time']) && '00' == date("s", $params['end_time'])) {
                    $params['end_time'] = strtotime('+1 day', $params['end_time']) - 1;
                }
                unset($params['end_date']);
            }

            if (isset($params['start_time']) && isset($params['end_time'])) {
                $params[$k] = ['between', $params['start_time'] . ',' . $params['end_time']];
                unset($params['start_time'], $params['end_time']);
            } elseif (isset($params['start_time'])) {
                $params[$k] = ['>=', $params['start_time']];
                unset($params['start_time']);
            } elseif (isset($params['end_time'])) {
                $params[$k] = ['<=', $params['end_time']];
                unset($params['end_time']);
            }
        }

        if (!empty($with)) {
            foreach ($params as $key => $val) {
                if (strpos($key, '.') === false) {
                    $params[$this->aliasName . '.' . $key] = $val;
                    unset($params[$key]);
                }
            }
        }
        return $params;
    }
    /**
     * 分组统计
     *
     * @description
     * @example
     * @author LittleMo 25362583@qq.com
     * @since 2021-04-02
     * @version 2021-04-02
     * @param array $params 筛选条件
     * @param string $group 分组字段
     * @param array $field  查询字段
     * @return array
     */
    public function getGroupListData($params = [], $group, $field = '*', $join = [])
    {
        $wsql  = $this->commonWsql($params, $join);
        $fieldNew = $this->initField($field, $group ?: true);

        // 列表
        $this->alias($this->aliasName)
            ->field($fieldNew);
        foreach ($join as $val) {
            $this->join($val[0], $val[1], $val[2] ?? null);
        }

        $rows = $this->where($wsql)
            ->order('count desc')
            ->group($group)
            ->page($params['page'] ?? $this->page, $params['pagesize'] ?? $this->pagesize)
            ->select();

        // 统计
        $total = $this->totalCount($params, $group, $field, $join);

        return compact('rows', 'total');
    }

    /**
     * 整理查询字段
     * @description
     * @example
     * @author LittleMo 25362583@qq.com
     * @since 2022-01-27
     * @version 2022-01-27
     * @param string $field
     * @param string $group
     * @return void
     */
    public function initField($field = '*', $group = '')
    {
        if ($field != "*") {
            if (is_array($field)) {
                $field = implode(',', $field);
            }
            if ($group && $group != $field) {
                $field = $group . ',' . $field;
            }
        }
        if ($group) {
            $field = $field . ', count(*) as count ';
        }
        return $field;
    }
    /**
     * 获取一条缓存数据
     *
     * @description
     * @example
     * @author LittleMo 25362583@qq.com
     * @since 2021-07-03
     * @version 2021-07-03
     * @param int $id 主键ID
     * @return array
     */
    public function getRowDataCache($id)
    {
        $name = $this->getRowDataCacheName($id);
        if (Cache::has($name)) {
            return Cache::get($name);
        }
        $data = self::get($id);
        !empty($data) && $data = $data->toArray();
        Cache::set($name, $data, $this->cacheTime);
        return $data;
    }
    /**
     * 删除一条缓存数据
     *
     * @description
     * @example
     * @author LittleMo 25362583@qq.com
     * @since 2021-07-03
     * @version 2021-07-03
     * @param int $id 主键ID
     * @return boolean
     */
    public function rmRowDataCache($id)
    {
        $name = $this->getRowDataCacheName($id);
        if (Cache::has($name)) {
            return Cache::rm($name);
        }
    }

    /**
     * 生成单条信息缓存名称
     *
     * @description
     * @example
     * @author LittleMo 25362583@qq.com
     * @since 2021-07-07
     * @version 2021-07-07
     * @param int $id
     * @return string
     */
    private function getRowDataCacheName($id)
    {
        // \littlemo\utils\Tools::createSign($id);
        return str_replace('_', '-', $this->table) . ':row-data:' . $id;
    }
    /**
     * 生成列表信息缓存名称
     *
     * @description
     * @example
     * @author LittleMo 25362583@qq.com
     * @since 2021-07-07
     * @version 2021-07-07
     * @param int $id
     * @return string
     */
    private function getListDataCacheName($params = [], $diy_wsql = '')
    {
        sort($params);
        $params['_wsql'] = $diy_wsql;
        $paramText = '';
        foreach ($params as $k => $v) {
            $paramText .= $k . '=' . $k . '&';
        }
        $paramText = rtrim($paramText, '&');

        // \littlemo\utils\Tools::createSign($params);
        $name = str_replace('_', '-', $this->table) . ':list-data:';
        $name .= md5($paramText);
        return $name;
    }

    /**
     * 添加一条记录
     *
     * @description
     * @example
     * @author LittleMo 25362583@qq.com
     * @since 2021-07-01
     * @version 2021-07-01
     * @param array $params
     * @return boolean/int
     */
    public function add($params, $allowField = [])
    {
        if ($this->allowField($allowField ?: true)->save($params)) {
            $this->primaryId = $this[$this->tablePrimary];
            return $this->primaryId;
        } else {
            return false;
        }
    }

    /**
     * 编辑一条记录
     *
     * @description
     * @example
     * @author LittleMo 25362583@qq.com
     * @since 2021-07-01
     * @version 2021-07-01
     * @param int|object    $row        主键ID/当前要更新的数据对象
     * @param array         $params     编辑内容
     * @param array         $allowField 允许修改的字段，默认为true
     * @return int
     */
    public function edit($row, $params = [], $allowField = [])
    {
        if (!is_object($row)) {
            $row = $this->get($row);
        }
        //清除缓存
        $this->rmRowDataCache($row[$this->tablePrimary]);

        return $row->allowField($allowField ?: true)->save($params);
    }

    /**
     * 删除一条记录
     *
     * @description
     * @example
     * @author LittleMo 25362583@qq.com
     * @since 2021-07-01
     * @version 2021-07-01
     * @param int|object $row 主键ID/当前要删除的数据对象
     * @return int
     */
    public function del($row)
    {
        if (!is_object($row)) {
            $row = $this->get($row);
        }
        //清除缓存
        $this->rmRowDataCache($row[$this->tablePrimary]);

        return $row->delete();
    }

    /**
     * 设置错误信息
     *
     * @param $msg  错误/提示信息信息
     * @return Auth
     */
    public function setMessage($msg, $code = 0)
    {
        $this->message = $msg;
        $this->code = $code;
        return $this;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getMessage()
    {
        return (function_exists('__')) ? __($this->message) : $this->message;
    }

    /**
     * 获取错误信息
     * @return string
     */
    public function getCode()
    {
        return $this->code;
    }
}
