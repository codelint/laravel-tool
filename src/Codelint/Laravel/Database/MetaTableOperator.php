<?php namespace Codelint\Laravel\Database;

use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * MetaTableOperator:
 * @date 15/11/15
 * @time 01:07
 * @author Ray.Zhang <codelint@foxmail.com>
 **/
class MetaTableOperator extends DBOperator {

    function __construct($table, $primaryKey, $meta_key = 'iid', $fields = [])
    {
        parent::__construct($table, $primaryKey, $meta_key, $fields);
        if (!Schema::hasTable($this->table()))
        {
            $this->build();
        }
    }

    public function build()
    {
        $table = $this->table();
        Schema::create($table, function($table)
        {
            $table->bigIncrements('id');
            $table->bigInteger('iid');
            $table->char('key', 64);
            $table->string('value', 192);
            $table->index(['iid', 'key', 'value']);
        });
//        $sql = sprintf('CREATE TABLE `%s%s` (
//                      `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
//                      `iid` bigint(20) unsigned NOT NULL DEFAULT "0",
//                      `key` char(64) NOT NULL DEFAULT "",
//                      `value` varchar(192) NOT NULL DEFAULT "",
//                      PRIMARY KEY (`id`),
//                      KEY `index_%s_iid_key_value` (`iid`,`key`,`value`)
//                    ) ENGINE=InnoDB AUTO_INCREMENT=10000 DEFAULT CHARSET=utf8 COMMENT="%s data"',
//            $prefix, $table, $table, $table);
//        DB::statement($sql);
    }

    /**
     * 按条件查询，条件规则位 [$field1 => $value, $field2 => [$operator, $value]]
     * @param array $conds
     * @param array $fields 要查询的字段
     * @param int $max 查询记录数, 0为不限制
     * @throws \Exception
     * @return mixed 结果记录
     */
    public function findBy(array $conds, $fields = [], $max = 0)
    {
        $list = parent::findBy($conds, $fields, $max);

        if (empty($fields) || in_array('value', $fields))
        {
            foreach ($list as &$item)
            {
                $item['value'] = json_decode($item['value'], true);
            }
        }

        return $list;
    }

    public function _wheres($conds)
    {
        if (isset($conds['value']))
        {
            if (is_array($conds['value']) && $conds['value'][0] != '=')
            {
                throw new \Exception('only support equal for meta.value findBy condition');
            }
            $conds['value'] = json_encode($conds['value']);
        }
        return parent::_wheres($conds);
    }

    /**
     * @param $table
     * @param $key $table.key
     * @param string|bool $fkey $this->table.key
     * @return $this
     */
    public function leftJoinOn($table, $key = 'id', $fkey = 'iid')
    {
        return parent::leftJoinOn($table, $key, $fkey);
    }

    /**
     * @param string $table table name
     * @param string $key $table.key
     * @param string|bool $fkey $this->table.key
     * @return $this
     */
    public function innerJoinOn($table, $key = 'id', $fkey = 'iid')
    {
        return parent::innerJoinOn($table, $key, $fkey);
    }

    /**
     * 插入记录
     * @param array $obj
     * @return bool|array 成功返回带id的对象，否则返回false
     */
    function insert($obj)
    {
        $orig = $obj;
        if (isset($obj['value']))
        {
            $obj['value'] = json_encode($obj['value']);
        }
        $obj = parent::insert($obj);
        return array_replace($obj, $orig);
    }

    public function updateBy($obj, $cri)
    {
        $orig = $obj;
        if (isset($obj['value']))
        {
            $obj['value'] = json_encode($obj['value']);
        }
        parent::updateBy($obj, $cri);

        return $orig;
    }

    public function meta_operator()
    {
        throw new \Exception('Do not support meta data for meta table');
    }

    /**
     * 活动对应得meta值，例如order，则会从表order_meta中读取或者设置meta值
     * @param string $iid
     * @param bool|string $key
     * @param mixed $val
     * @throws \Exception
     * @return mixed
     */
    public function meta($iid, $key, $val = null)
    {
        throw new \Exception('Do not support meta data for meta table');
    }

    /**
     * Get/update all the meta data of the object
     * @param int|string $iid object id
     * @param bool|array $data
     * @throws \Exception
     * @return array
     */
    public function metadata($iid, $data = false)
    {
        throw new \Exception('Do not support meta data for meta table');
    }


}