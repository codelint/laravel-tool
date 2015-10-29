<?php namespace Codelint\Laravel\Behavior;

/**
 * BehaviorTest:
 * @date 15/10/29
 * @time 23:13
 * @author Ray.Zhang <codelint@foxmail.com>
 **/
class BehaviorTest extends \TestCase {

    public function testBehaviorRun()
    {
        $config = [
            'behavior' => [
                'tags' => [
                    'test' => [
                        'before' => ['\\Codelint\\Laravel\Behavior\\TestBehavior'],
                        'after' => ['\\Codelint\\Laravel\Behavior\\ExceptionBehavior'],
                        'no_exception' => [
                            '@\\Codelint\\Laravel\Behavior\\ExceptionBehavior',
                            '\\Codelint\\Laravel\Behavior\\TestBehavior'
                        ]
                    ]
                ]
            ]
        ];
        Behavior::setConfig($config);

        Behavior::tag('test.before', $config);
        $this->assertEquals([], $config['behavior']['tags']['test']['before']);
        try{
            Behavior::tag('test.after', $config);
            $this->assertFalse(true, 'there is except a exception occur, but not');
        }catch(BehaviorException $e)
        {
        }
        $config['exception.before'] = false;
        Behavior::tag('test.no_exception', $config);
        $this->assertTrue($config['exception.before']);

    }

}

class TestBehavior extends Behavior {
    public function run(&$params)
    {
        $params['behavior']['tags']['test']['before']=[];

    }
}

class ExceptionBehavior extends Behavior {
    public function run(&$params)
    {
        $params['exception.before'] = true;
        throw new BehaviorException('this is a exception in ExceptionBehavior for test!!!');
    }
}

class BehaviorException extends \Exception{}