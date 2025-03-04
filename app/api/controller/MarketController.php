<?php

namespace app\api\controller;

use app\admin\model\UsersChoose;
use app\api\basic\Base;
use GuzzleHttp\Client;
use support\Cache;
use support\Request;

class MarketController extends Base
{
    protected $noNeedLogin = ['grailindex','upStock','downStock','searchStock'];
    function grailindex()
    {
        $response = (new Client())->get('https://mogen.yingzaihushen.com/api/index/grailindex')->getBody()->getContents();
        $response = json_decode($response);
        $response = $response->list->showapi_res_body->indexList;
        return $this->success('成功',$response);
    }

    function upStock()
    {
        $response = (new Client())->get('https://mogen.yingzaihushen.com/api/mobile/up_stock')->getBody()->getContents();
        $response = json_decode($response);
        $response = $response->list;
        return $this->success('成功',$response);
    }

    function downStock()
    {
        $response = (new Client())->get('https://mogen.yingzaihushen.com/api/mobile/down_stock')->getBody()->getContents();
        $response = json_decode($response);
        $response = $response->list;
        return $this->success('成功',$response);
    }

    function stockDetail(Request $request)
    {
        $stock_code = $request->post('stock_code');
        $response = (new Client())->get("https://mogen.yingzaihushen.com/api/Mobile/stockDetail_tobuy?stock_code=$stock_code")->getBody()->getContents();
        $response = json_decode($response);
        $response = $response->list;
        $response->choose_status = UsersChoose::where(['user_id'=>$request->user_id,'stock_code'=>$stock_code])->exists();

        return $this->success('成功',$response);
    }

    function addChooseStock(Request $request)
    {
        $stock_code = $request->post('stock_code','bj838924');
        $user_id = $request->user_id;
        $row = UsersChoose::where(['user_id'=>$user_id,'stock_code'=>$stock_code])->first();
        if ($row) {
            $row->delete();
            $result = false;
        }else{
            UsersChoose::create([
                'user_id'=>$user_id,
                'stock_code'=>$stock_code,
            ]);
            $result = true;
        }
        return $this->success('成功',$result);
    }

    function getChooseList(Request $request)
    {
        $rows = UsersChoose::where(['user_id'=>$request->user_id])->paginate()->items();
        foreach($rows as $row){
            $response = (new Client())->get("https://mogen.yingzaihushen.com/api/Mobile/stockDetail_tobuy?stock_code=$row->stock_code")->getBody()->getContents();
            $response = json_decode($response);
            $response = $response->list;
            $row->setAttribute('increPer',$response->increPer);
            $row->setAttribute('nowPri',$response->nowPri);
            $row->setAttribute('stock_name',$response->name);
        }
        return $this->success('成功',$rows);
    }

    function searchStock(Request $request)
    {
        $keyword = $request->post('keyword');
        $response = (new Client())->post("https://mogen.yingzaihushen.com/api/mobile/search_stock_api",[
            'json'=>[
                'content'=>$keyword
            ]
        ])->getBody()->getContents();
        $response = json_decode($response);
        $response = $response->list;
        return $this->success('成功',$response);
    }

}
