<?php

namespace app\api\controller;

use app\admin\model\StockStructureTime;
use app\admin\model\Subscribe;
use app\api\common\Tool;
use Carbon\Carbon;
use plugin\admin\app\controller\Base;
use plugin\admin\app\model\User;
use support\Request;

class SubscribeController extends Base
{
    #认购
    function doSubscribe(Request $request)
    {
        $time_id = $request->post('time_id');
        $type = $request->post('type');#0=市价认购 1=限价认购
        $price = $request->post('price');#委托价
        $time = StockStructureTime::find($time_id);
        if (!$time) {
            return $this->fail('报价不存在');
        }
        if ($time->price == 0) {
            return $this->fail('该报价已过期');
        }
        $user = User::find($request->user_id);
        if (!$user) {
            return $this->fail('用户不存在');
        }
        if ($user->money < $time->price) {
            return $this->fail('余额不足');
        }
        User::score(-$time->price, $user->id, '认购' . $time->structure->stock->name . '/' . $time->structure->stock->code . '/' . $time->structure->type_text, 'money');

        $scale_amount = 0;
        if ($time->structure->type == 0) {
            $scale_amount = 1000000;
        }
        Subscribe::create([
            'user_id' => $user->id,
            'type' => $type,
            'stock_id' => $time->structure->stock_id,
            'structure' => $time->structure->type,
            'price' => !empty($price)?$price:0,
            'time' => $time->type,
            'value' => $time->value,
            'pay_amount' => $time->price,
            'scale_amount' => $scale_amount
        ]);
        return $this->success('成功');
    }

    function getList(Request $request)
    {
        #浮动收益
        $total_yield_amount = 0;
        #持有列表
        $holdlist = Subscribe::with(['stock'])->where(['user_id' => $request->user_id])->whereIn('status', [0, 1, 3, 5, 6, 7, 8])->get()->each(function ($item) use (&$total_yield_amount) {

            $stockinfo = Tool::getStockPrice($item->stock->code);
            $item->market_price = $stockinfo->p;
            $difference_sell_price = 0;
            if ($item->status == 6) {
                $difference_sell_price = $stockinfo->p - $item->sell_price;
            }
            if ($item->status == 1) {
                $increase = ($stockinfo->p - $item->price) / $item->price;#涨幅
                $yield_amount = $increase * $item->scale_amount - $item->pay_amount;#盈亏
                if ($yield_amount < ($item->pay_amount * -1)) {
                    $yield_amount = $item->pay_amount * -1;
                }
                $total_yield_amount += $yield_amount;
            }
            $item->difference_sell_price = $difference_sell_price;#距离行权价
        });
        #完结列表
        $overlist = Subscribe::with(['stock'])->where(['user_id' => $request->user_id])->whereIn('status', [2, 4])->get();


        $data['total_scale_amount'] = $holdlist->sum('scale_amount');#存续规模
        $data['total_yield_amount'] = $total_yield_amount;#浮动收益
        $data['history_yield_amount'] = $overlist->sum('yield_amount');#历史净收益
        $data['holdlist'] = $holdlist;
        $data['overlist'] = $overlist;

        return $this->success('成功', $data);
    }

    function detail(Request $request)
    {
        $subscribe_id = $request->post('subscribe_id');
        $row = Subscribe::with(['stock'])->find($subscribe_id);
        if (!$row) {
            return $this->fail('数据不存在');
        }
        $stockinfo = Tool::getStockPrice($row->stock->code);
        $total_yield_amount = 0;  #浮动收益
        $total_yield_rate = 0;#浮动净收益率
        if ($row->status == 1) {
            $increase = ($stockinfo->p - $row->price) / $row->price;#涨幅
            $yield_amount = $increase * $row->scale_amount - $row->pay_amount;#盈亏
            if ($yield_amount < ($row->pay_amount * -1)) {
                $yield_amount = $row->pay_amount * -1;
            }
            $total_yield_amount = $yield_amount;
            $total_yield_rate = ($total_yield_amount - $row->pay_amount) / $row->pay_amount;
            $start_time = Carbon::create($row->start_time);
            $end_time = Carbon::create($row->end_time);
            $diff_time = $start_time->diffInDays($end_time);
            $end_time_text = '剩余' . $diff_time . '个自然日';
            $row->setAttribute('end_time_text', $end_time_text);
        }
        $row->setAttribute('total_yield_amount', $total_yield_amount);
        $row->setAttribute('total_yield_rate', $total_yield_rate);

        return $this->success('成功', $row);
    }

    #撤销挂单
    function cancel(Request $request)
    {
        $subscribe_id = $request->post('subscribe_id');
        $row = Subscribe::find($subscribe_id);
        if (!$row) {
            return $this->fail('数据不存在');
        }

        if ($row->status != 0) {
            return $this->fail('已交易，无法撤销');
        }
        $row->status = 5;
        $row->cancel_time = date('Y-m-d H:i:s');
        $row->save();
        return $this->success('成功');
    }

    #行权
    function sell(Request $request)
    {
        $subscribe_id = $request->post('subscribe_id');
        $sell_price = $request->post('sell_price',0);
        $sell_type = $request->post('sell_type');
        $row = Subscribe::find($subscribe_id);
        if (!$row) {
            return $this->fail('数据不存在');
        }
        if ($row->status != 1) {
            return $this->fail('当前状态无法行权');
        }
        $row->sell_type = $sell_type;
        $row->sell_price = !empty($sell_price)?$sell_price:0;
        $row->sell_num += 1;
        $row->status = 3;
        $row->sell_time = date('Y-m-d H:i:s');
        $row->save();
        return $this->success('成功');
    }

    #撤销行权
    function cancelSell(Request $request)
    {
        $subscribe_id = $request->post('subscribe_id');
        $row = Subscribe::find($subscribe_id);
        if (!$row) {
            return $this->fail('数据不存在');
        }
        if ($row->status != 3) {
            return $this->fail('当前状态无法撤销行权');
        }
        $row->status = 8;
        $row->save();
        return $this->success('成功');
    }


}
