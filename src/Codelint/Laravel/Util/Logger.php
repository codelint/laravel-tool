<?php namespace Codelint\Laravel\Util;

use Codelint\Laravel\Job\SendWeCorpMessageJob;
use Codelint\Laravel\Mailable\MessageMail;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;

/**
 * Logger:
 * @date 2020/5/25
 * @time 01:38
 * @author Ray.Zhang <codelint@foxmail.com>
 **/
class Logger {

    protected $alert_mails = [];

    public function __construct()
    {
        $this->alert_mails[] = env('LOG_ALERT_MAIL', '');
    }

    public function info($message, $info = [])
    {
        $message = is_string($message) ? $message : $this->json_encode($message);
        Log::info($message, $info);
    }

    public function notify($message, $info = [], $mails = [])
    {
        $message = is_string($message) ? $message : $this->json_encode($message);
        Log::info($message, $info);

        if (count($mails))
        {
            Mail::to($mails)->queue((new MessageMail($message, $info))->subject($message));
        }
    }

    /**
     * @param \Throwable|\Exception $exception
     * @param array $mails
     */
    public function ex_mail($exception, $mails = [])
    {
        $message_info = array(
            'clazz' => class_basename($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'code' => $exception->getCode(),
            'trace' => $exception->getTraceAsString(),
        );

        $this->mail($message_info['message'], $message_info, $mails);
    }

    public function mail($message, $info = [], $mails = [])
    {
        $message = is_string($message) ? $message : $this->json_encode($message);
        Mail::to(is_array($mails) && count($mails) ? $mails : $this->alert_mails)->queue((new MessageMail($message, $info))->subject($message));
    }

    public function alert($message, $info = [])
    {
        $message = is_string($message) ? $message : $this->json_encode($message);
        $this->notify($message, $info, $this->alert_mails);
        $this->weCorp($message, $info);
    }

    public function error($message, $info = [])
    {
        $message = is_string($message) ? $message : $this->json_encode($message);
        $this->notify($message, $info, $this->alert_mails);
        $this->weCorp($message, $info);
    }

    public function weCorp($message, $info = [])
    {
        if(env('LOG_WE_CORP_ID') && env('LOG_WE_CORP_SECRET'))
        {
            dispatch(new SendWeCorpMessageJob($message, $info));
        }
    }

    public function __call($name, $arguments)
    {
        try
        {
            if (Str::startsWith($name, 'we') && env('LOG_WE_CORP_ID') && env('LOG_WE_CORP_SECRET'))
            {
                $message = is_string($arguments[0]) ? $arguments[0] : $this->json_encode($arguments[0]);
                $job = new SendWeCorpMessageJob($message, isset($arguments[1]) ? $arguments[1] : []);

                $job->setChatId(substr($name, 2));
                dispatch($job);
                return;
            }

            if (Str::startsWith($name, 'notify'))
            {
                // notify notify@samulala.cn
                $mails = [env('LOG_NOTIFY_MAIL', 'notify@samulala.cn')];
                $mails = isset($arguments[2]) ? $arguments[2] : $mails;
                $mails = (array)$mails;
                $corps = array_filter($mails, function ($mail) {
                    return !Str::contains($mail, '@');
                });
                $mails = array_filter($mails, function ($mail) {
                    return Str::contains($mail, '@');
                });
                $this->notify($arguments[0], isset($arguments[1]) ? $arguments[1] : [], $mails);
                //notify we-corp
                if (!count($corps))
                {
                    array_push($corps, substr($name, 6));
                }
                $message = is_string($arguments[0]) ? $arguments[0] : $this->json_encode($arguments[0]);
                foreach ($corps as $corp)
                {
                    $job = new SendWeCorpMessageJob($message, isset($arguments[1]) ? $arguments[1] : []);
                    $job->setChatId($corp);
                    dispatch($job);
                }
                return;
            }

            if (count($arguments))
            {
                $this->notify($arguments[0], isset($arguments[1]) ? $arguments[1] : [], ["${name}@samulala.cn"]);
            }
        } catch (\Exception $e)
        {
            Log::error($e->getMessage());
            Log::error($e->getTraceAsString());
        }
    }

    private function json_encode($mixed)
    {
        return json_encode($mixed, JSON_UNESCAPED_UNICODE);
    }


}