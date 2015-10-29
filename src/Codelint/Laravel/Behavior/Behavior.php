<?php namespace Codelint\Laravel\Behavior;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;

/**
 * Behavior:
 * @author Ray.Zhang <codelint@foxmail.com>
 **/
abstract class Behavior {

    private $_tag = 'behavior';

    private static $_config = array();

    protected function tag_name($name = false)
    {
        $this->_tag = $name ? : $this->_tag;
        return $this->_tag;
    }

    public abstract function run(&$params);

    private static function getBehaviorsByTag($tag_name)
    {
        return Config::get('behavior.tags.' . $tag_name, array_get(static::$_config, 'behavior.tags.' . $tag_name, []));
    }

    static function setConfig($config)
    {
        static::$_config = $config;
    }

    /**
     * 在要执行行为的地方，进行标签
     * @param $tag_name
     * @param $params
     */
    static function tag($tag_name, &$params)
    {
        $behaviors = static::getBehaviorsByTag($tag_name);
        $behaviors = array_get($behaviors, '_', $behaviors, []);
        Log::info('run ' . count($behaviors) . ' behaviors for tag[' . $tag_name . ']');

        $logs = Cache::get('mall.log.tags', []);
        $logs[$tag_name] = [
            'tag_name' => $tag_name,
            'last_call' => date('Y-m-d H:i:s'),
            'params' => array_keys($params),
            'behaviors' => $behaviors
        ];
        Cache::put('mall.log.tags', $logs, 86400);

        foreach ($behaviors as $behavior)
        {
            if(starts_with($behavior, '@'))
            {
                static::safe_exec(substr($behavior,1), $params, $tag_name);
            }else
            {
                static::exec($behavior, $params, $tag_name);
            }
        }
    }

    static function safe_tag($tag_name, &$params)
    {
        $behaviors = Config::get('behavior.tags.' . $tag_name, []);
        $behaviors = array_get($behaviors, '_', $behaviors);
        Log::info('run ' . count($behaviors) . ' behaviors for tag[' . $tag_name . ']');

        $logs = Cache::get('mall.log.tags', []);
        $logs[$tag_name] = [
            'tag_name' => $tag_name,
            'last_call' => date('Y-m-d H:i:s'),
            'params' => array_keys($params),
            'behaviors' => $behaviors
        ];
        Cache::put('mall.log.tags', json_decode(json_encode($logs), true), 86400);

        $res = [];
        foreach ($behaviors as $behavior)
        {
            $res[] = static::safe_exec($behavior, $params, $tag_name);
        }
        return $res;
    }

    /**
     * 执行行为
     * @param $name
     * @param $params
     * @param string $tag_name
     * @return mixed
     */
    static function exec($name, &$params, $tag_name = 'behavior')
    {
        $behavior = App::make($name);
        $behavior->tag_name($tag_name);
        return $behavior->run($params);
    }

    /**
     * 安全执行行为，catch所有Exception, 失败返回Exception, 成功返回true
     * @param string $name 行为类名
     * @param array $params 行为执行所需要参数
     * @param string $tag_name 标签名称
     * @return bool|\Exception 行为执行异常,返回Exception, 成功返回true
     */
    static function safe_exec($name, &$params, $tag_name = 'behavior')
    {
        $behavior = App::make($name);
        $behavior->tag_name($tag_name);
        try
        {
            $behavior->run($params);
            return true;
        } catch (\Exception $e)
        {
            return $e;
        }
    }

} 