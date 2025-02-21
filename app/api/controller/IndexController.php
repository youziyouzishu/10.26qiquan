<?php

namespace app\api\controller;

use GuzzleHttp\Client;
use plugin\admin\app\controller\Base;
use support\Request;

class IndexController extends Base
{
    protected $noNeedLogin =['*'];
    function index()
    {
        $response = (new Client())->post('http://hq.sinajs.cn/list=sz300855',[
            'headers'=>[
                'Referer'=>'https://finance.sina.com.cn/',
            ]
        ])->getBody()->getContents();
//        $arr =  iconv('gb2312', 'utf-8//IGNORE',$response);

// 1. 去除字节字符串标记
        $data = substr($response, 2, -3);
        dump($data);
// 2. 解码字符串（假设原始编码是 GB2312）
        $data = iconv('gb2312', 'utf-8//IGNORE', $data);
        dump($data);
// 3. 提取数据部分（去掉 "var hq_str_sh000001="）
        $data = substr($data, strpos($data, '=') + 2);
        dump($data);
// 4. 分割数据
        $fields = explode(',', $data);
        dump($fields);
        // 5. 解析数据
        $stockData = [
            'name' => $fields[0], // 股票名称
            'open' => $fields[1], // 开盘价
            'close' => $fields[2], // 昨收价
            'current' => $fields[3], // 当前价
            'high' => $fields[4], // 最高价
            'low' => $fields[5], // 最低价
            'volume' => $fields[8], // 成交量
            'amount' => $fields[9], // 成交金额
            'date' => $fields[30], // 日期
            'time' => $fields[31], // 时间
        ];

// 打印解析结果

        return $this->success('成功',$stockData);
    }

}
