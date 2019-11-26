<?php namespace Codelint\Laravel\Util;

use PHPUnit\Framework\TestCase;


/**
 * LunarTest:
 * @date 2019/11/26
 * @time 14:12
 * @author Ray.Zhang <codelint@foxmail.com>
 **/
class LunarTest extends TestCase {

    public function testLunarToSolar()
    {
        $lunar = new Lunar();

        $solar_ydm = $lunar->convertLunarToSolar('2019', '11', '26');
        $solar_date = implode('-', $solar_ydm);
        $this->assertTrue($solar_date == '2019-12-21');
    }

    public function testSolarToLunar()
    {
        $lunar = new Lunar();

        $solar_ydm = $lunar->convertSolarToLunar('2019', '12', '21');
        $solar_date = implode('-', $solar_ydm);

         // var_dump($solar_date);
        $this->assertTrue($solar_date == '2019-冬月-廿六-己亥-11-26-猪-0');
    }

    public function testFestival()
    {
        $lunar = new Lunar();

        $f_name = $lunar->getFestival('2019-12-21', false, ['lunar' => array(
            '*-26' => '佛诞'
        )]);

        $this->assertTrue($f_name == '佛诞');
    }
}