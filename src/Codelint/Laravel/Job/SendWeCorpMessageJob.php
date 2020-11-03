<?php namespace Codelint\Laravel\Job;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Arr;

class SendWeCorpMessageJob implements ShouldQueue {
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $message;
    protected $detail;
    protected $meta;

    private $chat_id = 'logger';

    /**
     * Create a new job instance.
     *
     * @param $message
     * @param array $info
     */
    public function __construct($message, $info = [])
    {
        $this->message = $message;
        $this->detail = $info;
        $meta = array(
            'host' => gethostname(),
            'os' => php_uname(),
            'server_ip' => Arr::get($_SERVER, 'SERVER_ADDR', '-'),
            'http_referer' => Arr::get($_SERVER, 'HTTP_REFERER', '-'),
            'user_agent' => Arr::get($_SERVER, 'HTTP_USER_AGENT', '-'),
            'remote' => Arr::get($_SERVER, 'REMOTE_ADDR', '-'),
            'request_uri' => Arr::get($_SERVER, 'REQUEST_URI', '-'),
            'http_host' => Arr::get($_SERVER, 'HTTP_HOST', '-'),
            'http_accept_language' => Arr::get($_SERVER, 'HTTP_ACCEPT_LANGUAGE', '-'),
            'real_ip' => Arr::get($_SERVER, 'HTTP_X_FORWARDED_FOR', '-'),
            'real_proto' => Arr::get($_SERVER, 'HTTP_X_FORWARDED_PROTO', '-')
        );
        try
        {
            $meta['customer_id'] = auth('customer')->check() ? auth('customer')->user()->id : null;
        } catch (\Exception $e)
        {
        }
        $this->meta = $meta;

        $this->onConnection(env('LOG_QUEUE_CONNECTION', 'array'));
    }

    //{
    //    "name" : "NAME",
    //    "owner" : "userid1",
    //    "userlist" : ["userid1", "userid2", "userid3"],
    //    "chatid" : "CHATID"
    //}


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $chat_ids = cache()->get('base.Service.Logger.wx.chat_ids', []);
        if (!Arr::has($chat_ids, $this->chat_id))
        {
            if ($this->genChannel($this->chat_id, ['gzhang', 'dev']))
            {
                $chat_ids[] = $this->chat_id;
                cache()->put('base.Service.Logger.wx.chat_ids', $chat_ids);
            }
        }
        $this->wx($this->message, $this->detail);
    }

    public function setChatId($c_id)
    {
        $this->chat_id = $c_id;
        return $this;
    }

    public function genChannel($channel_name, $user_ids, $channel_id = null)
    {
        $channel_id = $channel_id ?: $channel_name;
        $owner_id = $user_ids[0];
        $token = $this->getToken();

        $res = $this->callOnce('https://qyapi.weixin.qq.com/cgi-bin/appchat/get?access_token=' . $token, array(
            'access_token' => $token,
            'chatid' => $channel_id
        ), 'get');

        if ($res && $res['errmsg'] == 'ok')
        {
            return true;
        }

        $res = $this->callOnce('https://qyapi.weixin.qq.com/cgi-bin/appchat/create?access_token=' . $token, array(
            'name' => $channel_name,
            'owner' => $owner_id,
            'userlist' => $user_ids,
            'chatid' => $channel_id,

        ), 'post');

//        $res = $this->callOnce('https://qyapi.weixin.qq.com/cgi-bin/appchat/get?access_token=' . $token, array(
//            'access_token' => $token,
//            'chat_id' => $channel_id
//        ), 'get');

        return $res && $res['errmsg'] == 'ok';
    }

    private function getToken()
    {
        $token_res = cache()->remember('base.Service.Logger.wx.token', 7200 - 500, function () {
            $corpId = env('LOG_WE_CORP_ID', '');
            $secret = env('LOG_WE_CORP_SECRET', '');
            return $this->callOnce('https://qyapi.weixin.qq.com/cgi-bin/gettoken', array(
                'corpid' => $corpId,
                'corpsecret' => $secret
            ), 'get');
        });

        return $token_res && isset($token_res['access_token']) ? $token_res['access_token'] : null;
    }

    public function wx($message, $info = [])
    {
        $token = $this->getToken();

        if ($token)
        {
            $url = 'https://qyapi.weixin.qq.com/cgi-bin/appchat/send?access_token=' . $token;
            $params = $this->getMsgData($message, $info);
            // print_r($params);
            $params['chatid'] = $this->chat_id;
            $params['safe'] = 0;

            $res = $this->callOnce($url, $params, 'post');
            // {"errcode":0,"errmsg":"ok"}
            return $res && $res['errmsg'] == 'ok';
        }

        return false;
    }

    private function getMsgData($message, $info = [])
    {
        $url = Arr::get($info, 'url', Arr::get($info, 'link', null));

        $image = Arr::get($info, 'image', null);

        if(!$url && count($info) > 1 && env('LOGGER_DEFAULT_URL'))
        {
            $url_data = array(
                'message' => $message,
                'detail' => $info,
                'meta' => $this->meta
            );
            $key = 'wx-msg-' . md5(json_encode($url_data));
            cache()->put($key , json_encode($url_data), 86400*21);
            $url = url('/base/message/view/' . $key);
            $url= env('LOGGER_DEFAULT_URL') . '?key=' . $key;
        }

        if ($url && $image)
        {
            $data = array(
                'msgtype' => 'news',
                'news' => array(
                    'articles' => [
                        array(
                            'title' => $message,
                            'description' => 'from ' . gethostname(),
                            'url' => $url,
                            'picurl' => $image,
                        )
                    ]
                ),
            );
        }
        elseif ($url)
        {
            $data = array(
                'msgtype' => 'textcard',
                'textcard' => array(
                    'title' => $message,
                    'description' => 'from ' . gethostname(),
                    'url' => $url,
                    'btntxt' => '更多',
                ),
            );
        }
        else
        {
            $data = array(
                'msgtype' => 'text',
                'text' => array('content' => $message),
            );
        }

        return $data;
    }

    private function callOnce($url, $args = null, $method = "post", $headers = array(), $withCookie = false, $timeout = 10)
    {
        $ch = curl_init();
        if ($method == "post")
        {
            curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($args) ? $args : json_encode($args));
            curl_setopt($ch, CURLOPT_POST, 1);
        }
        else
        {
            $data = $args ? http_build_query($args) : null;
            if ($data)
            {
                if (stripos($url, "?") > 0)
                {
                    $url .= "&$data";
                }
                else
                {
                    $url .= "?$data";
                }
            }
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if (!empty($headers))
        {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        if ($withCookie)
        {
            curl_setopt($ch, CURLOPT_COOKIEJAR, $_COOKIE);
        }
        $r = curl_exec($ch);
        curl_close($ch);
        return @json_decode($r, true);
    }
}
