<?php
namespace DongPHP\System\Libraries;
require_once(PUBLIC_PATH . '/vendor_bak/jpush/jpush/src/JPush/JPush.php');
use \JPush as baseJpush;

class Jpush
{
    private $client;

    public function __construct()
    {
        $this->jpsuhBase = new baseJpush('e0c0010e6613e133e3e71245', '519c56d4cd4a1661b9f07b74');
        $this->client    = $this->jpsuhBase->push();
    }

    public function customSend($title = '', $content = '', $platform = 'all', $time = '', $channel = '', $version = '', $type = '', $url = '')
    {
        $tag = [];
        if (ENVIRONMENT == 'development') {
            $this->client->addTagAnd('test');
            $tag[] = 'test';
        }
        //基本参数设置
        //$this->client->setOptions(100000, 0, null, false, null);
        $this->client->setOptions(100000, 0, null, true, 10);

        //发送平台设置
        $tmp = ($platform == 'all') ? 'all' : [$platform];
        $this->client->setPlatform($tmp);

        //设置扩展字段
        $extend = [];
        if ($type && $url) {
            $extend = ['type' => $type, 'url' => $url];
        }

        if ($platform == 'ios' || $platform == 'all') {
            $this->client->addIosNotification($content, 'iOS sound', 1, true, 'iOS category', $extend);
        }
        if ($platform == 'android' || $platform == 'all') {
            $this->client->addAndroidNotification($content, $title, 2, $extend);
        }

        //设置tag
        if ($channel) {
            $channel = explode(',', $channel);
            $channel = array_map(function ($row) {
                return 'chl_' . $row;
            }, $channel);
            $tag     = array_merge($channel, $tag);
        }

        if ($version) {
            $version = str_replace('.', '', $version);
            $version = explode(',', $version);
            $version = array_map(function ($row) {
                return 'vs_' . $row;
            }, $version);
            $tag     = array_merge($version, $tag);
        }

        if ($tag) {
            $this->client->addTagAnd($tag);
        }

        if (empty($tag)) {
            $this->client->addAllAudience();
        }
        try {
            if ($time) {
                $payload        = $this->client->build();
                $timeresult     = $this->jpsuhBase->schedule()->createSingleSchedule("后台发送定时消息", $payload, array("time" => $time.':00'));
                $result['data'] = ['schedule_id' => $timeresult->data->schedule_id, 'name' => $timeresult->data->name];
            } else {
                $result = $this->client->send();
            }

        } catch (Exception $e) {
            $errorCode = $e->getCode();
            $errorMsg  = $e->getMessage();
            $result    = false;
        };
        $result=json_encode($result);
        $result=json_decode($result,true);
        return $data = [
            'title'    => $title,
            'content'  => $content,
            'platform' => $platform,
            'tag'      => json_encode($tag),
            'extend'   => json_encode($extend),
            'send'     => $this->client->toJSON(),
            'result'   => json_encode($result) ? json_encode($result) : $errorMsg . $errorCode,
            'msgid'    => isset($result['data']['msg_id']) ? $result['data']['msg_id'] : '',
            'time'     => strtotime($time),
            'addtime'  => time()
        ];
    }

    //定时任务列表
    public function getClockList()
    {
        $response = $this->jpsuhBase->schedule()->getSchedules();
        $result   = (array)$response->data;
        $result   = json_encode($result);
        $result   = json_decode($result, true);
        return $result['schedules'];
    }

    public function clockDel($schedule_id)
    {
        return $this->jpsuhBase->schedule()->deleteSchedule($schedule_id);
    }

    public function  sendProgress($gid, $progress, $title, $content)
    {
        $tag    = 'prd_' . $gid . '_' . $progress;
        $extend = ['type' => 101, 'url' => $gid];
        return $this->send($tag, $extend, $title, $content);

    }

    public function sendKj($period, $title, $content)
    {
        $tag    = 'kj_' . $period;
        $extend = ['type' => 401, 'url' => ''];
        return $this->send($tag, $extend, $title, $content);
    }

    private function send($tag, $extend, $title, $content)
    {
        try {
            if (ENVIRONMENT == 'development') {
                $this->client->addTagAnd('test');
            } else {
                $this->client->setOptions(100000, 0, null, true);
            }
            $currentHour = (float)date('H.i');

            if ($currentHour < 8 || $currentHour > 22) {
                $tag = [$tag];
            } else {
                $tag = [$tag, $tag . '_m'];
            }

            $obj = $this->client
                ->setPlatform('all')
                ->addTag($tag)
                ->addAndroidNotification($content, $title, 2, $extend)
                ->addIosNotification($content, 'iOS sound', 1, true, 'iOS category', $extend)
                ->send();
            return ['data' => $obj->data, 'limit' => $obj->limit];
        } catch (\Exception $e) {
            return ['code' => $e->getCode(), 'msg' => $e->getMessage(), 'json' => $this->client->toJSON()];
        }
    }
}