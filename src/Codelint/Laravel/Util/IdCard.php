<?php namespace Codelint\Laravel\Util;


/**
 * IdCard:
 * @date 2019/11/26
 * @time 02:39
 * @author Ray.Zhang <codelint@foxmail.com>
 **/
class IdCard {

    /**
     * @var string
     */
    private $no;

    /**
     * IdCard constructor.
     * @param $no string
     */
    public function __construct($no)
    {
        $this->no = $no;
    }

    /**
     * @return null|string
     */
    public function sex()
    {
        $idcard = $this->no;
        if (empty($idcard)) return null;
        $sexint = (int)substr($idcard, 16, 1);
        return $sexint % 2 === 0 ? '女' : '男';
    }

    public function birthday()
    {
        $idcard = $this->no;
        if (empty($idcard)) return null;
        $bir = substr($idcard, 6, 8);
        $year = substr($bir, 0, 4);
        $month = substr($bir, 4, 2);
        $day = substr($bir, 6, 2);
        return $year . "-" . $month . "-" . $day;
    }

    public function age()
    {
        $idcard = $this->no;
        if (empty($idcard)) return null;
        #  获得出生年月日的时间戳
        $date = strtotime(substr($idcard, 6, 8));
        #  获得今日的时间戳
        $today = strtotime('today');
        #  得到两个日期相差的大体年数
        $diff = floor(($today - $date) / 86400 / 365);
        #  strtotime加上这个年数后得到那日的时间戳后与今日的时间戳相比
        $age = strtotime(substr($idcard, 6, 8) . ' +' . $diff . 'years') > $today ? ($diff + 1) : $diff;
        return intval($age);
    }

    public function city()
    {
        $stack = $this->area_stack(substr($this->no, 0, 6));

        $province = array_pop($stack);

        $city = array_pop($stack);

        return $city ? $city->name : ($province ? $province->name : '');
    }

    public function province()
    {
        $stack = $this->area_stack(substr($this->no, 0, 6));

        $district = array_pop($stack);

        return $district ? $district['name'] : '';
    }

    public function area()
    {
        $stack = $this->area_stack(substr($this->no, 0, 6));

        $province = array_pop($stack);

        $city = array_pop($stack);

        $area = array_pop($stack);

        return $area ? $area->name : ($city ? $city->name : ($province ? $province->name : ''));
    }

    /**
     * @param $code
     * @return array
     */
    protected function area_stack($code)
    {
        return [];
    }

    public function __toString()
    {
        return $this->no;
    }

    public function toArray()
    {
        return [
            'sex' => $this->sex(),
            'birthday' => $this->birthday(),
            'area' => $this->area(),
            'city' => $this->city(),
            'province' => $this->province(),
            'age' => $this->age()
        ];
    }

    static public function one($id_card)
    {
        if (self::check($id_card))
        {
            return new self($id_card);
        }
        else
        {
            return null;
        }
    }

    static function check($idcard)
    {
        // 只能是18位
        if (strlen($idcard) != 18)
        {
            return false;
        }

        // 取出本体码
        $idcard_base = substr($idcard, 0, 17);
        // 取出校验码
        $verify_code = substr($idcard, 17, 1);
        // 加权因子
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);
        // 校验码对应值
        $verify_code_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
        // 根据前17位计算校验码
        $total = 0;
        for ($i = 0; $i < 17; $i++)
        {
            $total += substr($idcard_base, $i, 1) * $factor[$i];
        }
        // 取模
        $mod = $total % 11;

        // 比较校验码
        if ($verify_code == $verify_code_list[$mod])
        {
            return true;
        }
        else
        {
            return false;
        }
    }


}