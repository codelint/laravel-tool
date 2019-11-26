<?php namespace Codelint\Laravel\Util;

use PHPUnit\Framework\TestCase;


/**
 * IdCardTest:
 * @date 2019/11/26
 * @time 14:10
 * @author Ray.Zhang <codelint@foxmail.com>
 **/
class IdCardTest extends TestCase{

    public function testBase()
    {
        $card = IdCard::one('440106198609084411');

        $this->assertTrue($card !== null);

        $this->assertTrue($card->birthday() == '1986-09-08');
        $this->assertTrue($card->sex() == '男');
    }
}