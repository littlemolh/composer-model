<?php

namespace littlemo\model\traits;



trait Change
{
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
        return  $this->allowField($allowField ?: true)->create($params);
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
    public function edit(&$row, $params = [], $allowField = [])
    {
        //清除缓存
        $this->rmRowDataCache($row[$this->pk ?: $this->getPk()]);

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
        //清除缓存
        $this->rmRowDataCache($row[$this->pk ?: $this->getPk()]);

        return $row->delete();
    }
}
