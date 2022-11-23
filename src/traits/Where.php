<?php

namespace littlemo\model\traits;

trait Where
{
    /**
     * 整理where
     * @description
     * @example
     * @author LittleMo 25362583@qq.com
     * @since 2022-01-15
     * @version 2022-01-15
     * @param array|string $params
     * @param array $with
     * @return array
     */
    protected  function commonWsql($params = [], $with = [])
    {

        $this->page =  (int)($params['page'] ?? $this->page);
        $this->pagesize = (int)($params['pagesize'] ?? $this->pagesize);
        $this->orderby = $params['orderby'] ??  $this->orderby ?: $this->pk;
        $this->orderway = $params['orderway'] ?? $this->orderway;

        if (!is_array($params)) {
            return $params;
        }

        foreach ($params as $key => $val) {
            //过滤分页和排序
            if (in_array($key, ['pagesize', 'page', 'orderby', 'orderway'])) {
                unset($params[$key]);
                continue;
            }

            //过滤空字段,不含：0,null
            if (!is_array($val) && $val !== null && strlen($val) <= 0) {
                unset($params[$key]);
                continue;
            } elseif (is_array($val)) {
                if (count($val) == 3) {
                    // array(array('gt',3),array('lt',10), 'or');
                    if (empty($val[0][1]) && empty($val[1][1])) {
                        unset($params[$key]);
                        continue;
                    } else {
                        if (empty($val[0][1]) && $val[0][1] !== 0) {
                            $params[$key] = $val[1];
                        } else
                        if (empty($val[1][1]) && $val[1][1] !== 0) {
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
            if ($this->autoWriteTimestamp != 'int') {
                if (isset($params['start_time']) && is_numeric($params['start_time'])) {
                    $params['start_time'] = date("Y-m-d H:i:s", $params['start_time']);
                }
                if (isset($params['end_time']) && is_numeric($params['end_time'])) {
                    $params['end_time'] = date("Y-m-d H:i:s", $params['end_time']);
                }
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
}
