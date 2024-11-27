<?php

namespace app\api\controller;

use app\admin\model\Banner;
use app\api\basic\Base;
use Carbon\Carbon;
use support\Request;

class BannerController extends Base
{
    function getList(Request $request)
    {
        $rows = Banner::orderByDesc('weigh')->get();
        return $this->success('成功',$rows);
    }

}
