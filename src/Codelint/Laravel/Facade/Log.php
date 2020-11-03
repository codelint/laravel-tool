<?php namespace Codelint\Laravel\Facade;

use Illuminate\Support\Facades\Facade;

/**
 * Log:
 * @date 2020/5/25
 * @time 01:36
 * @author Ray.Zhang <codelint@foxmail.com>
 * @method static void info(string $message, array $info = [])
 * @method static void mail(string $message, array $info = [], array $mails = [])
 * @method static void ex_mail(\Exception|\Throwable $exception, array $mails = [])
 * @method static void notify(string $message, array $info = [], array $mails = [])
 * @method static void alert(string $message, array $info = [])
 * @method static void error(string $message, array $info = [])
 **/
class Log extends Facade {

    protected static function getFacadeAccessor()
    {
        return 'codelint.logger';
    }

}
