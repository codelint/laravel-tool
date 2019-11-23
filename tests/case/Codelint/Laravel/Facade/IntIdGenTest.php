<?php namespace Codelint\Laravel\Facade;

/**
 * IntIdGenTest:
 * @date 2019/11/24
 * @time 02:34
 * @author Ray.Zhang <codelint@foxmail.com>
 **/
class IntIdGenTest extends \TestCase{

    public function testNextId()
    {
        $one = IntIdGen::nextId();
        $two = IntIdGen::nextId();

        $this->assertTrue(is_int($one));
        $this->assertTrue($one != $two);
    }
}