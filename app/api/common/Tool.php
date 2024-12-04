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
            $ret = $client->get("http://api.biyingapi.com/zs/sssj/{$code}/62C9F6C1-5B30-4428-A7DD-BE72AC65ADE5");
            $ret = $ret->getBody()->getContents();
            Cache::set($key,$ret,5);
        }
        return json_decode($ret);
    }
}