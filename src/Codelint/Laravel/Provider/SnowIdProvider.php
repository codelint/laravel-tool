<?php namespace Codelint\Laravel\Provider;

use Codelint\Laravel\Util\SnowflakeIdWorker;
use Illuminate\Support\ServiceProvider;

/**
 * SnowIdProvider:
 * @date 2019/11/24
 * @time 02:21
 * @author Ray.Zhang <codelint@foxmail.com>
 **/
class SnowIdProvider extends ServiceProvider {


    public function register()
    {
        $this->app->singleton('iid.generator', function(){
            
            $work = new SnowflakeIdWorker(env('SNOW_WORKER_ID', 1));
            
            return $work;
        });
    }


}