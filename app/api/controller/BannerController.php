<?php

namespace app\api\controller;

use app\admin\model\Banner;
use app\api\basic\Base;
use Carbon\Carbon;
use EasyWeChat\MiniApp\Application;
use support\Request;

class BannerController extends Base
{
    protected $noNeedLogin = ['*'];
    function getList(Request $request)
    {
        $rows = Banner::orderByDesc('weigh')->get();
        return $this->success('成功',$rows);
    }

}
