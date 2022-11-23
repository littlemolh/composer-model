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

use think\Loader;


class Model extends \think\Model
{
    // 表名



    // 定义时间戳字段名
    protected $createTime = 'createtime';
    protected $updateTime = 'updatetime';
    protected $deleteTime = false;

    protected $primaryId = 0; //主键ID
    protected $aliasName = ''; //(主表)别名
    protected $page = 1;
    protected $pagesize = 10;

    // 追加属性
    protected $append = [];

    //数据缓存时间
    protected $cacheTime = 3600;

    protected $message = '';
    protected $code = null;

    protected $orderby = '';
    protected $orderway = 'desc';

    protected $validateName = '';


    use \littlemo\model\traits\Where;
    use \littlemo\model\traits\ParseData;
    use \littlemo\model\traits\Total;
    use \littlemo\model\traits\Cache;
    use \littlemo\model\traits\Change;
    use \littlemo\model\traits\GetData;

    /**
     * 构造方法
     * @access public
     * @param array|object $data 数据
     */
    public function __construct($data = [])
    {
        parent::__construct($data);

        !$this->pk && $this->getPk();
        $this->aliasName = $this->aliasName ?: Loader::parseName(basename(str_replace('\\', '/', get_class($this))));
        $this->validateName = str_replace("\\model\\", "\\validate\\", get_class($this));
    }
}
