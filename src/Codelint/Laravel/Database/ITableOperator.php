<?php namespace Codelint\Laravel\Database;

/**
 * IDataOperator:
 * @date 15/10/21
 * @time 23:12
 * @author Ray.Zhang <codelint@foxmail.com>
 **/
interface ITableOperator {

    /**
     * return table's field
     * @return array
     */
    public function fields();

    /**
     * 获得表名
     * @return string
     */
    public function table();

    /**
     * 获得主键名
     * @return string
     */
    public function key_name();

    /**
     * 获得meta键名
     * @return string
     */
    public function meta_key_name();

    /**
     * 重置操作器
     * @return $this
     */
    public function reset();

    /**
     *  查询方法: findBy, findByOne, findById
     */

    /**
     * 按条件查询，条件规则位 [$field1 => $value, $field2 => [$operator, $value]]
     * @param array $conds
     * @param array $fields 要查询的字段
     * @param int $max 查询记录数, 0为不限制
     * @return mixed 结果记录
     */
    public function findBy(array $conds, $fields = [], $max = 0);

    /**
     * @param $table
     * @param $key $table.key
     * @param bool $fkey $this->table.key
     * @return $this
     */
    public function leftJoinOn($table, $key, $fkey = false);

    /**
     * @param string $table table name
     * @param string $key $table.key
     * @param string|bool $fkey $this->table.key
     * @return $this
     */
    public function innerJoinOn($table, $key, $fkey = false);

    /**
     * 插入记录
     * @param array $obj
     * @return bool|array 成功返回带id的对象，否则返回false
     */
    function insert($obj);

    /**
     * 删除记录
     * @param mixed $id
     * @return mixed
     */
    function delete($id = null);

    /**
     * 更新记录，参数解释见upsert方法
     * @param array $obj array('f1'=>'v1','f2'=>'v2','f3'=>'v3',...)
     * @param array $cri array('f1','f2','f3',...)
     * @return array|bool 成功返回$obj，失败返回false
     */
    public function updateBy($obj, $cri);

    /**
     * 查询结果集数目
     * @param array $conds 查询条件
     * @return mixed
     */
    public function rows($conds);

    /**
     * 查询总和
     * @param $conds
     * @param $field
     * @return int
     */
    public function sum($conds, $field);

    public function meta_operator();

    /**
     * 活动对应得meta值，例如order，则会从表order_meta中读取或者设置meta值
     * @param string $iid
     * @param bool|string $key
     * @param mixed $val
     * @return mixed
     */
    public function meta($iid, $key, $val = null);

    /**
     * Get/update all the meta data of the object
     * @param int|string $iid object id
     * @param bool|array $data
     * @return array
     */
    public function metadata($iid, $data = false);

    /**
     * 设置分页查询
     * @param int $pnum 页号
     * @param int $psize 页大小
     * @return $this
     */
    public function page($pnum = 1, $psize = 10);

    /**
     * 写入判断条件
     * @param array $conds 选择条件 array('f1'=>['op1','v1'], 'f2'=>'v2')
     * @return $this
     */
    public function wheres($conds);

    /**
     * @param array $orders
     * @param string $v 兼容原来的方法
     * @return $this
     */
    public function orderBy($orders = [], $v = 'desc');

    public function groupBy($field);

}