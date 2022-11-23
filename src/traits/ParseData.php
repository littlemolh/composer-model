<?php

namespace littlemo\model\traits;



trait ParseData
{
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
    public function parseListData(&$rows = [])
    {
        foreach ($rows as &$row) {

            $this->parseCommonData($row);
        }
    }

    /**
     * 解析单条数据
     * @description
     * @example
     * @author LittleMo 25362583@qq.com
     * @since 2022-04-28
     * @version 2022-04-28
     * @param array $data
     * @return void
     */
    public function parseRowData(&$row)
    {
        return $this->parseCommonData($row);
    }

    /**
     * 解析通用数据
     * @description
     * @example
     * @author LittleMo 25362583@qq.com
     * @since 2022-04-28
     * @version 2022-04-28
     * @param array $data
     * @return void
     */
    public function parseCommonData(&$row, $toarray = false)
    {
        if ($toarray && is_object($row)) {
            $row = $row->toArray();
        }
        return $row;
    }
}
