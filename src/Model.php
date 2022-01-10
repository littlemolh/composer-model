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
        $data['rows'] = $rows;
        $data['total'] = $this->totalCount($params, $with)['count'] ?? 0;
        $data['page'] = $page;
        $data['lastpage'] = ceil($data['total'] / $pagesize);
        $data['pagesize'] = $pagesize;
        return $data;
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
     * @return array
     */
    public function totalCount($params = [], $with = [])
    {
        $data = [];

        $wsql = $this->commonWsql($params);
        $wsql_time = [];
        //处理时间-若不限定时间则查询数据的开始和结束时间
        $start_time = !empty($params['start_date']) ? strtotime($params['start_date']) : 0;
        $end_time = !empty($params['end_date']) ? strtotime($params['end_date']) : 0;

        if ($this->createTime) {
            if (empty($start_time)) {
                $start_time = $this
                    ->alias($this->aliasName)
                    ->with($with)
                    ->where($wsql)
                    ->min($this->aliasName . '.' . $this->createTime);
            } else {
                !empty($start_time) && $wsql_time[$this->aliasName . '.' . $this->createTime] = ['>', $start_time];
            }
            if (empty($end_time)) {
                $end_time = $this
                    ->alias($this->aliasName)
                    ->with($with)->where($wsql)
                    ->max($this->aliasName . '.' . $this->createTime);
            } else {
                !empty($end_time) && $wsql_time[$this->aliasName . '.' . $this->createTime] = ['<=', $end_time];
            }
        }

        $data['count'] = $this
            ->alias($this->aliasName)
            ->with($with)
            ->where($wsql)
            ->where($wsql_time)
            ->count();

        $data['start_time'] = $start_time;
        $data['start_date'] = date('Y-m-d H:i:s', $start_time);
        $data['end_time'] = $end_time;
        $data['end_date'] = date('Y-m-d H:i:s', $end_time);
        return $data;
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
     * @return array
     */
    public function totalSum($params = [], $field = '')
    {
        $data = [];

        $wsql = $this->commonWsql($params);
        $wsql_time = '';
        //处理时间-若不限定时间则查询数据的开始和结束时间
        $start_time = !empty($params['start_date']) ? strtotime($params['start_date']) : 0;
        $end_time = !empty($params['end_date']) ? strtotime($params['end_date']) : 0;

        if ($this->createTime) {
            if (empty($start_time)) {
                $start_time = $this->where($wsql)->min($this->createTime);
            } else {
                $wsql_time .= !empty($start_time) ? ' AND ' . $this->createTime . ' >' . $start_time  : null;
            }
            if (empty($end_time)) {
                $end_time = $this->where($wsql)->max($this->createTime);
            } else {
                $wsql_time .= !empty($end_time) ? ' AND ' . $this->createTime . ' <' . $end_time  : null;
            }
        }

        $data[$field . '_sum'] = $this->where($wsql)->where($wsql_time)->sum($field);

        $data['start_time'] = $start_time;
        $data['start_date'] = date('Y-m-d H:i:s', $start_time);
        $data['end_time'] = $end_time;
        $data['end_date'] = date('Y-m-d H:i:s', $end_time);
        return $data;
    }


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
                    //区间查询
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

            if (!empty($with) && strpos($key, '.') === false) {
                $params[$this->aliasName . '.' . $key] = $val;
                unset($params[$key]);
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
    public function getGroupListData($params = [], $group = '', $field = '*', $with = [])
    {
        $data = [];

        $wsql  = $this->commonWsql($params);

        //处理时间-若不限定时间则查询数据的开始和结束时间
        $start_time = $params['start_time'] ?: (!empty($params['start_date']) ? strtotime($params['start_date']) : 0);
        $end_time = $params['end_time'] ?: (!empty($params['end_date']) ? strtotime($params['end_date']) : 0);

        if (empty($start_time)) {
            $start_time = $this->where($wsql)->min('createtime');
        } else {
            $wsql .= !empty($start_time) ? ' AND ' . $this->aliasName . '.createtime >' . $start_time  : null;
        }
        if (empty($end_time)) {
            $end_time = $this->where($wsql)->max('createtime');
        } else {
            $wsql .= !empty($end_time) ? ' AND ' . $this->aliasName . '.createtime <' . $end_time  : null;
        }

        //整理字段
        $fields = null;

        if ($field == '*') {
            $fields = '*';
        } else if (is_array($field)) {
            $fields[] = $group;
            foreach ($field as $val) {
                $fields[] = $val;
            }
        } elseif (is_string($field)) {
            $fields[] = $group;
        }
        if (is_string($fields)) {
            $fields .= ',count(*) as count ';
        } else {
            $fields[] = ' count(*) as count ';
        }
        $data['list'] = $this
            ->field($fields)
            ->with($with)
            ->where($wsql)
            ->order('count desc')
            ->group($group)
            ->page($params['page'] ?? $this->page, $params['pagesize'] ?? $this->pagesize)
            ->select();

        $data['total'] = $this
            ->field($fields)
            ->with($with)
            ->where($wsql)
            ->order('count desc')
            ->group($group)
            ->count();

        $data['start_time'] = $start_time;
        $data['start_date'] = !empty($start_time) ? date('Y-m-d H:i:s', $start_time) : null;
        $data['end_time'] = $end_time;
        $data['end_date'] = !empty($end_time) ? date('Y-m-d H:i:s', $end_time) : null;
        return $data;
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
            $this->primaryId = $this->id;
            return $this->id;
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
     * @param int $id 主键ID/或查找的数据
     * @param array $params 编辑内容
     * @return int
     */
    public function edit($detail, $params = [])
    {
        //清除缓存
        $this->rmRowDataCache($detail);
        if (!is_object($detail)) {
            $detail = $this->get($detail);
        }

        return $detail->save($params);
    }

    /**
     * 删除一条记录
     *
     * @description
     * @example
     * @author LittleMo 25362583@qq.com
     * @since 2021-07-01
     * @version 2021-07-01
     * @param int $id 主键ID
     * @return int
     */
    public function del($detail)
    {
        //清除缓存

        if (!is_object($detail)) {
            $detail = $this->get($detail);
        }
        $this->rmRowDataCache($detail[$this->tablePrimary]);

        return $detail->delete();
    }

    /**
     * 设置错误信息
     *
     * @param $msg  错误/提示信息信息
     * @return Auth
     */
    public function setMessage($msg)
    {
        $this->message = $msg;
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
}
