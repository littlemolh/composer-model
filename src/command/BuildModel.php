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

namespace littlemo\model\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\console\input\Option;
use think\console\input\Argument;
use think\Db;

class BuildModel extends Command
{
    protected function configure()
    {
        $this->setName('build-model')
            ->addArgument('app', Argument::OPTIONAL, "app module name,defaule:common") //应用目录，默认：common
            ->addOption('app', 'a', Option::VALUE_REQUIRED, 'app module name,defaule common') //应用目录，默认：common
            ->addOption('dirpath', 'd', Option::VALUE_REQUIRED, 'dir path')
            ->addOption('relative_dir', 'r', Option::VALUE_REQUIRED, 'relative to app model dir') //相对应用的路径
            ->addOption('prefix', 'p', Option::VALUE_REQUIRED, 'table name prefix') //表名前缀 必须指定
            ->addOption('table', 't', Option::VALUE_REQUIRED, 'table name prefix') //表名前缀
            ->addOption('ignorePrefix', 'i', Option::VALUE_REQUIRED, 'file name and class name ignore prefix') //忽略表名前缀,文件名和class名忽略表前缀的存在
            ->setDescription('Here is the remark ');
    }

    static $appname = null;
    static $dirpath = null;
    static $relative_dir = null;
    static $prefix = null;
    static $table = null;
    static $namespace = null;
    static $ignorePrefix = true;
    static $newModel = [];

    protected function execute(Input $input, Output $output)
    {

        //初始化指令参数
        $this->_init($input, $output);

        //获取新（待创建）模型列表
        $this->getNewModel($output);

        //创建模型
        $this->createModel($output);

        $output->info("Build Successed!");
    }

    /**
     * 初始化指令参数
     *
     * @description
     * @example
     * @author LittleMo 25362583@qq.com
     * @since 2021-08-12
     * @version 2021-08-12
     * @param Object $input
     * @param Object $output
     * @return void
     */
    private function _init($input, $output)
    {
        $app = 'common';
        $dir = APP_PATH;
        if ($input->hasArgument('app')) {
            self::$appname = trim($input->getArgument('app')) ?: $app;
        }

        if ($input->hasOption('app')) {
            self::$appname = $input->getOption('app') ?: $app;
        }
        $output->info('[app module] -> ' . $app);

        self::$dirpath = $dir  . $app . DS . 'model' . DS;
        self::$relative_dir = '';
        if ($input->hasOption('relative_dir')) {
            self::$relative_dir .= $input->getOption('relative_dir');
        }
        self::$dirpath .= self::$relative_dir;
        $output->info('[relative_dir]    -> ' .  self::$relative_dir);
        $output->info('[dirpath]    -> ' .  self::$dirpath);



        if ($input->hasOption('prefix')) {
            self::$prefix = $input->getOption('prefix');
        }
        $output->info('[prefix]     -> ' . self::$prefix);

        if ($input->hasOption('table')) {
            self::$table = $input->getOption('table');
        }
        $output->info('[table]     -> ' . self::$table);

        self::$namespace = 'app\\' . $app . '\\model';
        $output->info('[namespace]  -> ' . self::$namespace);

        if ($input->hasOption('prefix')) {
            $ignorePrefix = $input->getOption('ignorePrefix');
            self::$ignorePrefix = $ignorePrefix == 'false' ? false : true;
        }
        $output->info('[ignorePrefix]  -> ' . self::$ignorePrefix);
    }

    /**
     * 获取新（待创建）模型列表
     *
     * @description
     * @example
     * @author LittleMo 25362583@qq.com
     * @since 2021-08-12
     * @version 2021-08-12
     * @param Object $output
     * @return void
     */
    private function getNewModel($output)
    {
        $model = $newModel = $files = $tables = [];
        $database = config('database.database');
        $prefix = config('database.prefix');

        $dir =  self::$dirpath;

        $p = self::$prefix;
        $t = self::$table;

        $rd =  self::$relative_dir;

        $rd =  str_replace('/', "", $rd);
        $rd =  str_replace('\\', '', $rd);
        $prd = $p . ($rd ? $rd . '_' : '');

        //获取model列表
        if (is_dir($dir)) {
            $files = scandir($dir);
        }
        foreach ($files as $val) {
            if ($val != '.' && $val != '..' && is_file($dir . '/' . $val)) {
                $model[] = self::humpToLine(explode('.', $val)[0]);
            }
        }
        //获取数据库table_name列表
        foreach (Db::Query('select TABLE_NAME from information_schema.tables where table_schema=\'' . $database . '\'') as $val) {
            $tables[] = $val['TABLE_NAME'];
        }
        foreach ($tables as $val) {
            $isnew = true;

            //排除非指定前缀表名 
            if (!empty($prd) && substr($val, 0, strlen($prd)) != $prd) {
                continue;
            }

            if (!empty($t) && $val != $t) {
                continue;
            }


            //排除已有模型
            $tempVar = $val;
            if (self::$ignorePrefix) {
                $tempVar = substr($val, strlen($prd));
            }
            foreach ($model as $v) {

                if ($tempVar == $v || $tempVar == ($prefix . $v)) {
                    $isnew = false;
                    break;
                }
            }

            //暂存待创建模型
            if ($isnew) {
                $newModel[] = $val;
            }
        }
        self::$newModel = $newModel;
    }

    /**
     * 创建模型
     *
     * @description
     * @example
     * @author LittleMo 25362583@qq.com
     * @since 2021-08-12
     * @version 2021-08-12
     * @param Object $output
     * @return void
     */
    private function createModel($output)
    {
        //通用前缀
        $prefix =  config('database.prefix');
        $p = self::$prefix;
        $rd =  self::$relative_dir;

        if (!is_dir(self::$dirpath)) {
            mkdir(self::$dirpath, 0777, true);
        }


        foreach (self::$newModel as $val) {
            $createTime = 'false';
            $updateTime = 'false';
            $deleteTime = 'false';
            $autoWriteTimestamp = '';
            $createTimeType = '';
            $updateTimeType = '';
            $deleteTimeType = '';
            foreach (Db::Query('select COLUMN_NAME, column_comment, DATA_TYPE, EXTRA from INFORMATION_SCHEMA.Columns where table_name=\'' . $val . '\'') as $v) {
                if (($v['COLUMN_NAME'] == 'createtime' || $v['COLUMN_NAME'] == 'intime')) {
                    $createTime = '\'' . $v['COLUMN_NAME'] . '\'';
                    $createTimeType = $v['DATA_TYPE'];
                }
                if ($v['COLUMN_NAME'] == 'updatetime' || $v['COLUMN_NAME'] == 'uptime') {
                    $updateTime = '\'' . $v['COLUMN_NAME'] . '\'';
                    $updateTimeType = $v['DATA_TYPE'];
                }
                if ($v['COLUMN_NAME'] == 'deletetime' || $v['COLUMN_NAME'] == 'deltime') {
                    $deleteTime = '\'' . $v['COLUMN_NAME'] . '\'';
                    $deleteTimeType = $v['DATA_TYPE'];
                }
            };
            if ($createTimeType == $updateTimeType && $updateTimeType == $deleteTimeType && $deleteTimeType == 'int') {
                $autoWriteTimestamp = '\'int\'';
            }

            //去表前缀
            $table_name = substr($val, 0, strlen($prefix)) == $prefix ? substr($val, strlen($prefix)) : $val;

            //按照文件夹简化类名

            $className = ucwords($this->convertUnderline($table_name));

            $p = str_replace('/', '_', $p);
            if (substr($className, 0, strlen($p)) == ucwords($this->convertUnderline($p))) {
                $className = substr(ucwords($this->convertUnderline($table_name)), strlen(ucwords($this->convertUnderline($p))));
            }
            if (substr($className, 0, strlen($rd)) == ucwords($this->convertUnderline($rd))) {
                $className = substr(ucwords($this->convertUnderline($table_name)), strlen(ucwords($this->convertUnderline($rd))));
            }


            $contents = "<?php\n";
            $contents .= "\n";
            $contents .= "namespace " . self::$namespace . ($rd ? ('\\' . rtrim($rd, DS)) : '') . "; \n";
            $contents .= "\n";
            // $contents .= "use littlemo\\model\\BaseModel; \n";
            $contents .= "\n";
            $contents .= "class " . $className . " extends \\littlemo\\model\\Model \n{ \n";
            $contents .= "    // 表名 \n";
            $contents .= '    protected $' . (substr($val, 0, strlen($prefix)) == $prefix ? 'name' : 'table') . ' = \'' . $table_name . '\';' . " \n";
            if ($autoWriteTimestamp) {
                // 自动写入时间戳字段
                $contents .= "    // 自动写入时间戳字段 \n";
                $contents .= '    protected $autoWriteTimestamp = ' . $autoWriteTimestamp . ';' . " \n";
            }
            $contents .= "    // 定义时间戳字段名 \n";
            $contents .= '    protected $createTime = ' . $createTime . ';' . " \n";
            $contents .= '    protected $updateTime = ' . $updateTime . ';' . " \n";
            $contents .= '    protected $deleteTime = ' . $deleteTime . ';' . " \n";
            $contents .= "\n";
            $contents .= '    protected $aliasName = \'' . $this->humpToLine($className) . '\';' . " \n";
            $contents .= "    // 追加属性 \n";
            $contents .= '    protected $append = [];' . " \n";
            $contents .= "}";

            //要创建的两个文件
            $fileName = self::$dirpath . '/' . $className . '.php';
            //以读写方式打写指定文件，如果文件不存则创建
            if (($TxtRes = fopen($fileName, "w+")) === FALSE) {
                $output->info("创建模型 失败 " . $fileName);
                break;
            }
            if (!fwrite($TxtRes, $contents)) { //将信息写入文件
                $output->info("创建模型 失败 " . $fileName);
                fclose($TxtRes);
                break;
            }
            $output->info("创建模型 成功 " . $fileName);
            fclose($TxtRes); //关闭指针
        }
    }


    /**
     * 下划线转驼峰
     *
     * @description
     * @example
     * @author LittleMo 25362583@qq.com
     * @since 2021-08-12
     * @version 2021-08-12
     * @param string $str
     * @return string
     */
    static function convertUnderline($str)
    {
        if (self::$ignorePrefix) {
            $str = str_replace(self::$prefix, "", $str);
        }
        $str = preg_replace_callback('/([-_]+([a-z]{1}))/i', function ($matches) {
            return strtoupper($matches[2]);
        }, $str);
        return $str;
    }


    /**
     * 驼峰转下划线
     *
     * @description
     * @example
     * @author LittleMo 25362583@qq.com
     * @since 2021-08-12
     * @version 2021-08-12
     * @param string $str
     * @return string
     */
    static function humpToLine($str)
    {
        $str = str_replace("_", "", $str);
        $str = preg_replace_callback('/([A-Z]{1})/', function ($matches) {
            return '_' . strtolower($matches[0]);
        }, $str);
        return ltrim($str, "_");
    }

    static function baseModelTemplate()
    {
    }
}
