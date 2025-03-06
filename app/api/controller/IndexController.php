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
    }

}
