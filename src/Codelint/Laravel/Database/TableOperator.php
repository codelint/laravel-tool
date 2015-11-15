<?php namespace Codelint\Laravel\Database;

/**
 * TableOperator:
 * @date 15/10/21
 * @time 23:21
 * @author Ray.Zhang <codelint@foxmail.com>
 **/
abstract class TableOperator implements ITableOperator, IMetaOperator {

    /**
     *  查询方法: findBy, findByOne, findById
     */

    /**
     * 根据主键获得记录，成功以array返回记录，失败返回null
     * @param int $id
     * @param array $fields
     * @return null|array
     */
    public function findById($id, $fields = [])
    {
        return $this->findByOne([$this->key_name() => $id], $fields);
    }

    /**
     * 查询一条记录
     * @param array $conds
     * @param array $fields
     * @return null|array
     */
    public function findByOne(array $conds = [], $fields = [])
    {
        $arr = $this->findBy($conds, $fields, 1);
        return empty($arr) ? null : $arr[0];
    }

    /**
     * 判断是否存在符合条件的记录
     * @param array $conds
     * @return bool
     */
    public function exist(array $conds)
    {
        $a = $this->findByOne($conds, [$this->table() . '.' . $this->key_name()]);
        return !empty($a);
    }

    /**
     * 插入更新操作 upsertBy, upsertById, updateBy, updateById
     */

    /**
     * 更新记录，若不存在则插入新记录
     * @param array $obj 条件只能包含等于的判断，不支持比较操作符号
     * @param array $cri 作为约束的字段, 如: ['order_id', 'created_at']
     * @param bool $insert 如果不存在是否创建新对象
     * @return array|bool 成功返回$obj，失败返回false
     */
    public function upsertBy(array $obj, array $cri, $insert = true)
    {
        $key_name = $this->key_name();
        $conds = array_only($obj, $cri);
        $old = $this->findByOne($conds, [$key_name]);
        if ($old)
        { //exist, so just update
            $obj[$key_name] = $old[$key_name];
            $obj = $this->updateById($obj);
        }
        else
        {
            if ($insert)
            {
                return $this->insert($obj);
            }
        }
        return $obj;
    }

    /**
     * 根据主键id更新
     * @param array $obj array('id'=>123, ...)
     * @param bool $insert
     * @return array|bool
     */
    public function upsertById(array $obj, $insert = true)
    {
        if (!(isset($obj[$this->key_name()])))
        {
            //obj haven't primary key
            return false;
        }
        return $this->upsertBy($obj, [$this->key_name()], $insert);
    }

    /**
     * @desc 取得某个列的记录
     * @param array $conds
     * @param string $field
     * @return  array
     */
    public function findByField($conds, $field)
    {
        return array_column($this->findBy($conds, [$field]), $field);

    }

    /**
     * 按约束添加记录，存在则根据约束返回，否则则插入一条记录返回
     * @param array $obj 数据
     * @param array $cri 唯一字段,为空则与insert相同
     * @return array|bool
     */
    function add($obj, $cri = [])
    {
        $old = empty($cri) ? false : $this->findByOne(array_only($obj, $cri));
        return $old ? : $this->insert($obj);
    }

    /**
     * 根据主键id更新
     * @param array $obj array('id'=>'123', 'f1'=>'v1', 'f2'=>'v2')
     * @return array|bool
     */
    public function updateById($obj)
    {
        return $this->updateBy($obj, [$this->key_name()]);
    }

    /**
     * 自增函数
     * @param array $cri 约束，必须未唯一约束，若查找不到，则会根据此约束内容创建记录
     * @param string $field 自增字段名
     * @param int $step 自增量，默认为1
     */
    public function increase($cri, $field, $step = 1)
    {
        if ($obj = $this->findByOne($cri, [$this->key_name(), $field]))
        {
            $obj[$field] += $step;
            $this->updateById($obj);
        }
        else
        {
            $this->insert(array_add($cri, $field, $step));
        }
    }

}