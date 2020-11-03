<?php namespace Codelint\Laravel\Provider;

use Codelint\Laravel\Util\Logger;
use Illuminate\Support\ServiceProvider;

/**
 * LoggerProvider:
 * @date 2020/11/3
 * @time 16:11
 * @author Ray.Zhang <codelint@foxmail.com>
 **/
class LoggerProvider extends ServiceProvider {

    public function register()
    {
        $this->app->singleton('codelint.logger', function () {
            return new Logger();
        });
    }

}