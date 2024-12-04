<?php

namespace app\queue\redis;

use DateTime;
use Webman\RedisQueue\Consumer;

class Subscribe implements Consumer
{
    // 要消费的队列名
    public $queue = 'subscribe';

    // 连接名，对应 plugin/webman/redis-queue/redis.php 里的连接`
    public $connection = 'default';

    // 消费
    public function consume($data)
    {
        $event = $data['event'];
        if ($event == 'sell'){

            $row = \app\admin\model\Subscribe::find($data['id']);
            // 创建一个 DateTime 对象表示当前日期
            $currentDate = new DateTime();
            $currentDate->setTime(0, 0, 0); // 设置时间为 00:00:00
            //创建一个 DateTime 对象表示 end_time
            $endTimeDate = DateTime::createFromFormat('Y-m-d', $row->end_time);
            //如果还是处于认购成功 并且结束时间是今天  到期自动平权
            if ($row->status == 1 && $currentDate->format('Y-m-d') === $endTimeDate->format('Y-m-d')){
                $row->status = 3;
                $row->type = 0;
                $row->sell_num += 1;
                $row->save();
            }
        }
    }
            
}
