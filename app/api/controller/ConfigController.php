<?php

namespace app\api\controller;

use app\api\basic\Base;
use plugin\admin\app\model\Option;
use support\Request;

class ConfigController extends Base
{
    protected $noNeedLogin = ['*'];

    function getConfig()
    {
        $name = 'admin_config';
        $config = Option::where('name', $name)->value('value');
        $config = json_decode($config);
        return $this->success('成功', $config);
    }

}
