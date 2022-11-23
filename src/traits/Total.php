<?php

namespace littlemo\model\traits;



trait Total
{
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
    public function totalCount($params = [],  $with = [], $group = null)
    {
        $wsql = $this->commonWsql($params, $with);

        // 统计
        return $this->alias($this->aliasName)
            ->where($wsql)
            ->with($with)
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
    public function totalSum($params = [], $field = '', $with = [])
    {
        $wsql = $this->commonWsql($params, $with);
        return  $this->alias($this->aliasName)->with($with)->where($wsql)->sum($field);
    }



    /**
     * 统计分组后数据列表
     *
     * @description
     * @example
     * @author LittleMo 25362583@qq.com
     * @since 2021-04-02
     * @version 2021-04-02
     * @param array $params 筛选条件
     * @param string $group 分组字段
     * @return array
     */
    public function totalGroupListData($group, $field = [], $params = [],  $with = [])
    {
        $wsql  = $this->commonWsql($params, $with);

        $page =  $this->page;
        $pagesize = $this->pagesize;


        foreach ($field as $key => &$val) {
            if ($with && !strpos($val, '.')) {
                $field['sum(' . $this->aliasName . '.' . $key . ')'] = $val;
                unset($params[$key]);
            }
        }
        $field['count(*)'] = 'count';

        // 列表
        $model = $this->alias($this->aliasName)
            ->field($field)
            ->where($wsql)
            ->with($with)
            ->group($group);

        $rows = $model->order($this->orderby, $this->orderway)->page($page, $pagesize)->select();
        // 统计
        $total = $model->count();
        $lastpage = ceil($total / $pagesize);
        return compact('total', 'page', 'pagesize',  'lastpage', 'rows');
    }
}
