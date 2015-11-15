<?php namespace Codelint\Laravel\Database;

/**
 * IMetaOperator:
 * @date 15/11/15
 * @time 01:03
 * @author Ray.Zhang <codelint@foxmail.com>
 **/
interface IMetaOperator {

    /**
     * 获得meta键名
     * @return string
     */
    public function meta_key_name();

    /**
     * @return \Illuminate\Database\Query\Builder
     */
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

} 