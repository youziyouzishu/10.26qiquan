<?php

namespace process;


use app\admin\model\StockStructureTime;
use Workerman\Crontab\Crontab;


class Task
{
    public function onWorkerStart()
    {
        // 每天的7点50执行，注意这里省略了秒位
        new Crontab('50 7 * * *', function(){
            #todo
//            StockStructureTime::query()->delete();
        });

    }
}