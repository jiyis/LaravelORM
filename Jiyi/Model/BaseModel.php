<?php
/**
 * Created by Gary.F.Dong.
 * Date: 2016/3/6
 * Time: 10:30
 * Desc： 所有的Model方法都要继承此基类,采用laravel的Eloquent ORM来对数据库操作
 */

namespace Jiyi\Model;

use Illuminate\Database\Capsule\Manager as Capsule;
use ProductLine\ProductInterface\BaseModelInterface;

class BaseModel implements BaseModelInterface
{
    protected static $capsule;
    private static $_country;
    private static $_dbconfig;
    private static $_ccode;

    private function __construct()
    {
    }

    public function __clone()
    {
        return false;
    }

    public static function init()
    {
        if(empty(self::$capsule)) {
            // 载入数据库配置文件
            include APPPATH . 'config/database.php';
            self::$_country  = "default";
            self::$_dbconfig = $db;
            self::$_ccode    = '';
            // Eloquent ORM
            self::$capsule = new Capsule;
            //针对于多国家
            foreach (self::$_dbconfig as $ccode => $country) {
                self::$capsule->addConnection([
                    'driver'    => 'mysql',
                    'host'      => self::$_dbconfig[$ccode]['hostname'],
                    'database'  => self::$_dbconfig[$ccode]['database'],
                    'username'  => self::$_dbconfig[$ccode]['username'],
                    'password'  => self::$_dbconfig[$ccode]['password'],
                    'charset'   => self::$_dbconfig[$ccode]['char_set'],
                    'collation' => self::$_dbconfig[$ccode]['dbcollat'],
                    'prefix'    => self::$_dbconfig[$ccode]['dbprefix'],
                ], $ccode);
            }
            //设定默认的连接db，当前的国家
            self::$capsule->addConnection([
                'driver'    => 'mysql',
                'host'      => self::$_dbconfig[self::$_country]['hostname'],
                'database'  => self::$_dbconfig[self::$_country]['database'],
                'username'  => self::$_dbconfig[self::$_country]['username'],
                'password'  => self::$_dbconfig[self::$_country]['password'],
                'charset'   => self::$_dbconfig[self::$_country]['char_set'],
                'collation' => self::$_dbconfig[self::$_country]['dbcollat'],
                'prefix'    => self::$_dbconfig[self::$_country]['dbprefix'],
            ]);
            self::$capsule->setAsGlobal();
            self::$capsule->bootEloquent();
            self::$capsule->getConnection(self::$_ccode)->enableQueryLog();
        }

    }
    public static function setDatabase($ccode)
    {
        self::$_ccode = $ccode;
        self::$capsule->getConnection(self::$_ccode)->enableQueryLog();
    }
    /**
     * 检查数据库是否存在
     * @param $database
     * @return bool
     */
    public static function hasDatabase($database)
    {
        if(empty($database))  return false;
        return self::$capsule->getConnection(self::$_ccode)->selectOne("SELECT SCHEMA_NAME FROM INFORMATION_SCHEMA.SCHEMATA WHERE SCHEMA_NAME = '".$database."'");
    }
    /**
     * 检查数据表是否存在
     * @param $tables
     * @return bool
     */
    public static function hasTable($tables)
    {
        if(empty($tables) || !is_array($tables))  return false;
        foreach ($tables as $table) {
            if(!self::$capsule->getConnection(self::$_ccode)->getSchemaBuilder()->hasTable($table)){
                return false;
                exit;
            }
        }
        return true;
    }
    /**
     * 检查表中的字段是否存在，多个字段时候，有一个不存在，则返回false
     * @param string $table
     * @param $column
     * @return bool
     */
    public static function hasColumns($table, $column)
    {
        if(empty($table) || empty($column))  return false;
        if(!is_array($column)) $column = array($column);
        return self::$capsule->getConnection(self::$_ccode)->getSchemaBuilder()->hasColumns($table,$column);
    }
    /**
     * 新增表中的某些字段
     * @param string $table
     * @param $column
     * @return bool
     */
    public static function addColumns($table, $column)
    {
        if(empty($table) || empty($column))  return false;
        if(!is_array($column)) $column = array($column);
        if(!self::$hasTable($table)) return false;
        $column = implode(',',$column);
        return self::$capsule->getConnection(self::$_ccode)->statement("ALTER TABLE `".$table."` ADD COLUMN ".$column." VARCHAR(100) NULL");
    }

    /**
     * 返回执行过的所有sql
     * @return mixed
     */
    public static function getQueryLog()
    {
        return self::$capsule->getConnection(self::$_ccode)->getQueryLog();
    }
    /**
     * 批量插入数据
     * @param string $table
     * @param $datas
     * @return bool
     */
    public static function  batchInsert($table, $datas)
    {
        if(empty($table) || empty($datas)) return false;
        if(!self::hasTable([$table])) return false;
        self::$capsule->getConnection(self::$_ccode)->table($table)->insert($datas);
    }
    /**
     * 这边只是做spec的新建table，所以所需要字段固定
     * @param string $table
     * @param string $column
     */
    public static function createTable($tablename)
    {
        if(self::hasTable($tablename)) return false;
        return self::$capsule->getConnection(self::$_ccode)->getSchemaBuilder()->create($tablename, function($table)
        {
            $table->integer('prod_id')->default(0)->unique();
        });
    }
    /**
     * 重命名数据表名字
     * @param $tablename
     * @param $newtablename
     * @return mixed
     */
    public static function renameTable($tablename,$newtablename)
    {
        if(empty($tablename) || empty($newtablename)) return false;
        if(!self::hasTable([$tablename])) return false;
        if(self::hasTable([$newtablename])) return false;
        return self::$capsule->getConnection(self::$_ccode)->getSchemaBuilder()->rename($tablename,$newtablename);
    }
    /**
     * 删除整个数据表
     * @param $tablename
     * @return bool
     */
    public static function deleteTable($tablename)
    {
        if(empty($tablename)) return false;
        if(!self::hasTable($tablename)) return true;
        return self::$capsule->getConnection(self::$_ccode)->getSchemaBuilder()->drop($tablename);
    }

    /**
     * 删除表中的数据，根据where条件来删除
     * @param $tablename
     * @param $key
     * @param $value
     * @return bool
     */
    public static function deleteRows($tablename,$array)
    {
        if(empty($tablename) || empty($array)) return false;
        if(!is_array($array)) return false;
        if(!self::hasColumns($tablename,array_keys($array))) return false;
        return self::$capsule->getConnection(self::$_ccode)->table($tablename)->where($array)->delete();
    }

    /**
     * 根据wherein 来删除
     * @param $tablename
     * @param $key
     * @param $array
     * @return bool
     */
    public static function deleteRowsByIn($tablename,$key,$array)
    {
        if(empty($tablename) || empty($key) || empty($array)) return false;
        if(is_array($key) || !is_array($array)) return false;
        self::$capsule->getConnection(self::$_ccode)->table($tablename)->whereIn($key, $array)->delete();
    }
    /**
     * 根据where获取一行数据
     * @param $tablename
     * @param $array
     * @return bool
     */
    public static function selectRow($tablename,$array)
    {
        if(empty($tablename) || empty($array)) return false;
        if(!is_array($array)) return false;
        return self::$capsule->getConnection(self::$_ccode)->table($tablename)->where($array)->first();
    }
    /**
     * 返回laravel的Schema 门面类，可以使用Schema方法
     * @return mixed
     */
    public static function Schema()
    {
        return self::$capsule->getConnection(self::$_ccode)->getSchemaBuilder();
    }
    /**
     * 返回laravel的DB 门面类，可以使用DB方法
     * @return mixed
     */
    public static function DB()
    {
        return self::$capsule->getConnection(self::$_ccode);
    }

}