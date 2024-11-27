<?php

namespace app\queue\redis;

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
            //如果还是处于认购成功  到期自动平权
            if ($row->status == 1){
                $row->status = 3;
                $row->type = 0;
                $row->sell_num += 1;
                $row->save();
            }
        }
    }
            
}
