<?php namespace Codelint\Laravel\Database;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use PDO;


/**
 * DBOperator:
 * @date 15/10/21
 * @time 23:12
 * @author Ray.Zhang <codelint@foxmail.com>
 **/
class DBOperator extends TableOperator {

    static $TABLE_PKEY = [];

    /**
     * @var string 操作表名
     */
    private $_table;
    /**
     * @var \Illuminate\Database\Query\Builder
     */
    private $_operator;

    /**
     * @var string 主键名
     */
    private $_pkey = 'id';

    /**
     * meta表的对象id键名
     * @var string
     */
    private $_mkey = 'iid';

    private $meta_sets = array();

    /**
     * @var int default is 10
     */
    private $pageSize = 10;

    /**
     * @var int start with 1
     */
    private $pageNo = 0;

    private $_fields = [];

    /**
     * @param $table
     * @param $primaryKey
     * @param string $meta_key
     * @param array $fields
     */
    function __construct($table, $primaryKey, $meta_key = 'iid', $fields = [])
    {
        if (Config::get('database.fetch') == PDO::FETCH_ASSOC)
        {
            $this->_table = $table;
            $this->_operator = DB::table($table);
            $this->_pkey = $primaryKey;
        }else{
            throw new \Exception('just support: config[database.fetch] == PDO::FETCH_ASSOC');
        }

    }

    /**
     * 构建一个DBOperator对象
     * @param string $table
     * @param string $key
     * @param string $meta_key default is 'iid'
     * @return DBOperator
     */
    static function apply($table, $key = '', $meta_key = 'iid')
    {
        if (empty($key))
        {
            $key = isset(self::$TABLE_PKEY[$table]) ? self::$TABLE_PKEY[$table] : 'id';
        }

        $fields = \Cache::remember('db.cache.' . $table . '.fields', 1, function () use ($table)
        {
            return (new DBOperator($table, 'id'))->fields();
        });

        return new DBOperator($table, $key, $meta_key, $fields);
    }

    /**
     * 获得表名
     * @return string
     */
    public function table()
    {
        return $this->_table;
    }

    /**
     * 获得主键名
     * @return string
     */
    public function key_name()
    {
        return $this->_pkey;
    }

    /**
     * 获得meta键名
     * @return string
     */
    public function meta_key_name()
    {
        return $this->_mkey;
    }

    /**
     * @param array $fields
     * @return array
     */
    public function fields($fields = [])
    {
        if (empty($fields))
        {
            $conn = $this->_operator->getConnection();
            if ($conn->getName() == 'mysql')
            {
                $results = $conn->select((new \Illuminate\Database\Schema\Grammars\MySqlGrammar())->compileColumnExists(), [$conn->getDatabaseName(), $conn->getTablePrefix() . $this->_table]);
                $this->_fields = array_map(function ($v)
                {
                    return $v['column_name'];
                }, $results);
            }
        }
        return $this->_fields;
    }

    /**
     * 设置主键
     * @param string $keyName 主键名
     * @return $this
     */
    public function primaryKey($keyName)
    {
        $this->_pkey = $keyName;
        return $this;
    }

    /**
     * 设置meta键
     * @param $keyName
     * @return $this
     */
    public function metaKey($keyName)
    {
        $this->_mkey = $keyName;
        return $this;
    }

    /**
     * 获得内部的Query建造类
     * @return \Illuminate\Database\Query\Builder
     */
    public function getOperator()
    {
        return $this->_operator;
    }

    /**
     * 重置内部operator
     */
    public function reset()
    {
        $this->_operator = DB::table($this->_table);
        return $this;
    }

    /**
     * 按条件查询，条件规则位 [$field1 => $value, $field2 => [$operator, $value]]
     * @param array $conds
     * @param array $fields 要查询的字段
     * @param int $max 查询记录数, 0为不限制
     * @return mixed 结果记录
     */
    public function findBy(array $conds, $fields = [], $max = 0)
    {
        $this->_wheres($conds);
        if ($max)
        {
            $this->limit($max);
        }
        $my_fields = $this->fields();
        $fields = empty($fields) ? array_diff($my_fields, ['deleted_at']) : $fields;
        foreach ($fields as &$field)
        {
            $field = str_contains($field, '.') ? $field : $this->_table . '.' . $field;
        }
        $this->select(empty($fields) ? [$this->_table . '.*'] : $fields);
        return $this->get();
    }

    /**
     * @param $table
     * @param $key $table.key
     * @param bool $fkey $this->table.key
     * @return $this
     */
    public function leftJoinOn($table, $key, $fkey = false)
    {
        $fkey = $fkey ? : $key;
        $key = starts_with($key, $table . '.') ? $key : $table . '.' . $key;
        $fkey = starts_with($key, $this->_table . '.') ? $fkey : $this->_table . '.' . $fkey;
        $this->_operator = $this->_operator->leftJoin($table, $key, '=', $fkey);
        return $this;
    }

    /**
     * @param string $table table name
     * @param string $key $table.key
     * @param string|bool $fkey $this->table.key
     * @return $this
     */
    public function innerJoinOn($table, $key, $fkey = false)
    {
        $fkey = $fkey ? : $key;
        $key = starts_with($key, $table . '.') ? $key : $table . '.' . $key;
        $fkey = starts_with($key, $this->_table . '.') ? $fkey : $this->_table . '.' . $fkey;
        $this->_operator = $this->_operator->join($table, $key, '=', $fkey);
        return $this;
    }

    /**
     * 插入记录
     * @param array $obj
     * @return bool|array 成功返回带id的对象，否则返回false
     */
    function insert($obj)
    {
        $retId = $this->_operator->insertGetId($obj);
        if ($retId)
        {
            $obj[$this->_pkey] = $retId;
        }
        return $obj;
    }

    function delete($id = null)
    {
        return $this->_operator->delete($id);
    }

    public function updateBy($obj, $cri)
    {
        if (empty($cri))
        {
            return $obj;
        }
        $conds = array_only($obj, $cri);
        if (count($conds) == count($cri))
        {
            $sets = array_except($obj, $cri);
            $this->_wheres($conds)->update($sets);
            return $obj;
        }
        else
        {
            return $obj;
        }

    }

    /**
     * 查询结果集数目
     * @param array $conds 查询条件
     * @return mixed
     */
    public function rows($conds)
    {
        return $this->_wheres($conds)->select('count(' . $this->_table . '.' . $this->_pkey . ')')->count();
    }

    /**
     * 查询总和
     * @param $conds
     * @param $field
     * @return int
     */
    public function sum($conds, $field)
    {
        return $this->_wheres($conds)->_operator->sum($field) ? : 0;
    }

    public function meta_operator()
    {
        return new DBOperator($this->_table . '_meta', 'id');
    }

    protected function build_meta_table()
    {
        $table = $this->table();
        $sql = sprintf('CREATE TABLE `m2_%s_meta` (
              `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
              `iid` bigint(20) unsigned NOT NULL DEFAULT "0",
              `key` char(64) NOT NULL DEFAULT "",
              `value` varchar(192) NOT NULL DEFAULT "",
              PRIMARY KEY (`id`),
              KEY `index_%s_meta_iid_key_value` (`iid`,`key`,`value`)
            ) ENGINE=InnoDB AUTO_INCREMENT=10000 DEFAULT CHARSET=utf8 COMMENT="%s meta data"',
            $table, $table, $table);
        DB::statement($sql);
    }

    /**
     * 活动对应得meta值，例如order，则会从表order_meta中读取或者设置meta值
     * @param string $iid
     * @param bool|string $key
     * @param mixed $val
     * @return mixed
     */
    public function meta($iid, $key, $val = null)
    {
        $iid = is_array($iid) ? $iid[$this->_pkey] : $iid;
        $meta_op = $this->meta_operator();
        $obj = [$this->_mkey => $iid];
        if (!$key)
        {
            return $this->metadata($iid, $val);
        }
        else
        {
            $obj = array_add($obj, 'key', $key);
        }

        if ($val === null)
        {
            $one = $meta_op->findByOne($obj, ['value']);
        }
        else
        {
            $val = json_encode($val);
            $one = $meta_op->upsertBy(array_add($obj, 'value', $val), ['iid', 'key']);
        }

        return $one ? json_decode($one['value'], true) : false;
    }

    /**
     * Get/update all the meta data of the object
     * @param int|string $iid object id
     * @param bool|array $data
     * @return array
     */
    public function metadata($iid, $data = false)
    {
        $meta_op = $this->meta_operator();
        if (!empty($data))
        {
            $data = empty($this->meta_sets) ? $data : array_only($data, $this->meta_sets);
            foreach ($data as $k => $v)
            {
                $this->meta($iid, $k, $v);
            }
        }
        $data = $meta_op->findBy(['iid' => $iid]);
        $meta = [];
        foreach ($data as $kv)
        {
            $kv['value'] = json_decode($kv['value'], true);
            if (isset($meta[$kv['key']]))
            {
                $meta[$kv['key']] = (array)$meta[$meta[$kv['key']]];
                array_push($meta[$kv['key']], $kv['value']);
            }
            else
            {
                $meta[$kv['key']] = $kv['value'];
            }
        }
        return $meta;
    }

    /**
     * 设置分页查询
     * @param int $pnum 页号
     * @param int $psize 页大小
     * @return $this
     */
    public function page($pnum = 1, $psize = 10)
    {
        $this->pageNo = $pnum;
        $this->pageSize = $psize;
        return $this->offset(($pnum - 1) * $psize)->limit($this->pageSize);
    }

    /**
     * 写入判断条件
     * @param array $conds 选择条件 array('f1'=>['op1','v1'], 'f2'=>'v2')
     * @return $this
     */
    public function wheres($conds)
    {
        return $this->_wheres($conds);
    }

    /**
     * @param array $orders
     * @param string $v 兼容原来的方法
     * @return $this
     */
    public function orderBy($orders = [], $v = 'desc')
    {
        if (empty($orders))
        {
            return $this;
        }

        if (is_array($orders))
        {
            foreach ($orders as $k => $v)
            {
                $this->_operator->orderBy($k, $v);
            }
        }
        else
        {
            $this->_operator->orderBy($orders, $v);
        }
        return $this;
    }

    public function groupBy($field)
    {
        $this->_operator->groupBy($field);
        return $this;
    }

//    public function union($conds)
//    {
//        $query = new self($this->_table, $this->_pkey, $this->_mkey, $this->_fields);
//        $this->_operator->union($query->_wheres($conds)->getOperator());
//        return $this;
//    }

    /**
     * like wheres function ,call by internal
     * @param array $conds
     * @return $this
     */
    protected function _wheres($conds)
    {
        foreach ($conds as $field => $opAndVal)
        {
            if (is_null($opAndVal))
            {
                $opAndVal = [null];
            }
            $opAndVal = (array)$opAndVal;
            $op = strtolower(count($opAndVal) == 1 ? '=' : $opAndVal[0]);
            $val = last($opAndVal);
            $field = str_contains($field, '.') ? $field : $this->_table . '.' . $field;
            switch ($op)
            {
                case 'in':
                {
                    if (count($val) == 1)
                    {
                        $this->_operator->where($field, '=', $val[0]);
                    }
                    else
                    {
                        $this->_operator->whereIn($field, $val);
                    }
                    break;
                }
                case 'notin':
                {
                    if (count($val) == 1)
                    {
                        $this->_operator->where($field, '<>', $val[0]);
                    }
                    else
                    {
                        $this->_operator->whereNotIn($field, $val);
                    }
                    break;
                }
                case 'between':
                {
                    $this->_operator->whereBetween($field, $val);
                    break;
                }
                case 'notbetween':
                {
                    $this->_operator->whereNotBetween($field, $val);
                    break;
                }
                case 'null':
                {
                    if ($val)
                    {
                        $this->_operator->whereNull($field);
                    }
                    else
                    {
                        $this->_operator->whereNotNull($field);
                    }
                    break;
                }
                case 'raw':
                {
                    $this->_operator->whereRaw($val);
                    break;
                }
                default:
                    $this->_operator->where($field, $op, $val);

            }

        }
        return $this;
    }

    /**
     * 代理$this->operator的所有方法
     * @param string $method
     * @param array $parameters
     * @return $this|mixed
     */
    public function __call($method, $parameters)
    {
        $res = call_user_func_array(array($this->_operator, $method), $parameters);
        $this->_operator = ($res instanceof Builder) ? $res : $this->_operator;
        if ($res instanceof Builder)
        {
            $this->_operator = $res;
            return $this;
        }
        else
        {
            return $res;
        }
    }

}
