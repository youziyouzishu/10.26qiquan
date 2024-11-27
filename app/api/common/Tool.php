<?php

namespace app\api\common;

use GuzzleHttp\Client;
use support\Cache;

class Tool
{
    public static function getStockPrice($code)
    {
        $key = "stock-$code";
        $ret = Cache::get($key);
        if (!$ret){
            $client = new Client;
            $ret = $client->get("http://api.biyingapi.com/zs/sssj/{$code}/71936A19-1FC5-4CB5-9F0A-D705E383E319");
            $ret = $ret->getBody()->getContents();
            Cache::set($key,$ret,5);
        }
        return json_decode($ret);
    }
}