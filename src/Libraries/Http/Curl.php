<?php
/**
 * this is part of xyfree
 *
 * @file Curl.php
 * @use
 * @author Dongjiwu(dongjw321@163.com)
 * @date 2015-11-02 17:08
 *
 */

namespace DongPHP\System\Libraries\Http;

use DongPHP\System\Logger;

class Curl
{
    private $methods = [];
    private $ch;
    private $body;
    private $info;
    private $defaultOptions = [
        'TIMEOUT'        => 20,
        'CONNECTTIMEOUT' => 5,
        'MAXRETRIES'     => 3,
        'AGENT'          => 'API PHP5 Client (curl) ',
    ];

    private $logger;

    public function __construct($url='', $option=[])
    {
        $this->ch      = curl_init();
        $this->methods = array_flip(array_map('strtoupper', get_class_methods(__CLASS__)));
        $this->setOption($url, $option);
        $this->logger  = Logger::get('curl');
    }

    /**
     * @param $url
     * @param array $option
     * @param bool|true $body
     * @return Curl|string
     * @throws CurlException
     */
    public static function get($url, $option=[], $body=true)
    {
        $client = new self($url, $option);
        $client->send();
        return $body ? $client->__toString() : $client;
    }

    /**
     * @param $url
     * @param null $fields
     * @param array $option
     * @param bool|true $body
     * @return Curl|string
     * @throws CurlException
     */
    public static function post($url, $fields=null, $option=[], $body=true)
    {
        $client = new self($url, $option);
        $client->addPostFields($fields);
        $client->send();
        return $body ? $client->__toString() : $client;
    }

    public static function getJson($url, $option=[])
    {
        return json_decode(self::get($url, $option), true);
    }

    public static function postJson($url, $fields=null, $option=[])
    {
        return json_decode(self::post($url, $fields, $option), true);
    }

    public function __toString()
    {
        return (string)$this->getBody();
    }

    protected function setOption($url, $option=[])
    {
        $option = array_merge($this->defaultOptions, array_map('strtoupper', $option));
        curl_setopt($this->ch, CURLOPT_URL, $url);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        foreach ($option as $key => $value) {
            $method = strtoupper('verify'.$key);
            if (isset($this->methods[$method])) {
                $this->{$method}($value);
            } elseif (defined('CURLOPT_'.$key)) {
                curl_setopt($this->ch, constant('CURLOPT_'.$key), $value);
            } elseif (defined($key)) {
                curl_setopt($this->ch, constant($key), $value);
            }
        }
    }

    public function getBody()
    {
        curl_close($this->ch);
        return $this->body;
    }


    public function getInfo()
    {
        return $this->info;
    }

    protected function send()
    {
        for ($i = 0; $i < $this->defaultOptions['MAXRETRIES']; $i++) {
            $this->body = curl_exec($this->ch);
            if ($this->body) {
                break;
            }
        }
        $this->info = curl_getinfo($this->ch);
        $this->logger->debug(json_encode($this->info));
        if (curl_errno($this->ch)) {
            $this->logger->error('code:'.curl_errno($this->ch).','.curl_error($this->ch).',info:'.json_encode($this->info));
        }
    }

    protected function addPostFields($post)
    {
        curl_setopt($this->ch, CURLOPT_POST, 1);
        curl_setopt($this->ch, CURLOPT_POSTFIELDS, $post);
    }

    protected function verifyHeader($header)
    {
        if(is_string($header)) {
            $header = [$header];
        }
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, $header);
    }

    protected function verifyTimeout($time)
    {
        if (defined('CURLOPT_TIMEOUT_MS')) {
            curl_setopt($this->ch, CURLOPT_NOSIGNAL, true);
            curl_setopt($this->ch, CURLOPT_TIMEOUT_MS, $time*1000);
        } else {
            curl_setopt($this->ch, CURLOPT_TIMEOUT, $time);
        }
    }

    protected function verifyConnectTimeout($time)
    {
        if (defined('CURLOPT_CONNECTTIMEOUT_MS')) {
            curl_setopt($this->ch, CURLOPT_NOSIGNAL, 1);
            curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT_MS, $time*1000);
        } else {
            curl_setopt($this->ch, CURLOPT_CONNECTTIMEOUT, $time);
        }
    }

    protected function verifyAgent($agent='')
    {
        curl_setopt($this->ch, CURLOPT_USERAGENT, $agent);
    }

    protected function verifyUser($val)
    {
        curl_setopt($this->ch, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
        curl_setopt($this->ch, CURLOPT_USERPWD, $val['user'].":".$val['password']);
    }
}


class CurlException extends \Exception
{
}
