<?php namespace Codelint\Laravel\Facade;

/**
 * IntIdGen:
 * @date 2019/11/24
 * @time 02:23
 * @author Ray.Zhang <codelint@foxmail.com>
 *
 * @method static integer nextId()
 **/
class IntIdGen extends \Illuminate\Support\Facades\Facade {

    protected static function getFacadeAccessor()
    {
        return 'iid.generator';
    }


}