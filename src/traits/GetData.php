<?php

namespace littlemo\model\traits;



trait GetData
{
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

        $page =  $this->page;
        $pagesize = $this->pagesize;


        $wsql = $this->commonWsql($params, $with);
        $rows = $this
            ->alias($this->aliasName)
            ->where($wsql)
            ->with($with)
            ->page($page, $pagesize)
            ->order($this->orderby, $this->orderway)
            ->select();

        $this->parseListData($rows);
        $total = $this->totalCount($params, $with) ?: 0;
        $lastpage = ceil($total / $pagesize);

        return compact('total', 'page', 'pagesize',  'lastpage', 'rows');
    }

    public function getRowData($params)
    {
        $params = $this->commonWsql($params);
        $row = self::get($params);
        $this->parseRowData($row);
        return $row;
    }


    /**
     * 分组查询
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
    public function getGroupListData($group, $params = [],  $with = [])
    {
        $wsql  = $this->commonWsql($params, $with);

        $page =  $this->page;
        $pagesize = $this->pagesize;

        $rows = $this->alias($this->aliasName)
            ->where($wsql)
            ->with($with)
            ->group($group)
            ->order($this->orderby, $this->orderway)
            ->page($page, $pagesize)
            ->select();

        // 统计
        $total = $this->totalCount($params,  $with, $group);
        $lastpage = ceil($total / $pagesize);
        return compact('total', 'page', 'pagesize',  'lastpage', 'rows');
    }
}
