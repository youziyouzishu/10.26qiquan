<?php

namespace app\admin\controller;

use app\admin\model\StockStructureTime;
use app\admin\model\Withdraw;
use Carbon\Carbon;
use plugin\admin\app\model\User;
use support\Request;
use support\Response;
use app\admin\model\Subscribe;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;
use Webman\RedisQueue\Client;

/**
 * 认购管理
 */
class SubscribeController extends Crud
{

    /**
     * @var Subscribe
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new Subscribe;
    }

    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('subscribe/index');
    }

    public function select(Request $request): Response
    {
        [$where, $format, $limit, $field, $order] = $this->selectInput($request);
        $query = $this->doSelect($where, $field, $order)->with(['stock', 'user']);
        return $this->doFormat($query, $format, $limit);
    }

    /**
     * 插入
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function insert(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $time_id = $request->post('time_id');
            $user_id = $request->post('user_id');

            $time = StockStructureTime::find($time_id);
            if (!$time) {
                return $this->fail('报价不存在');
            }
            if ($time->price == 0) {
                return $this->fail('该报价已过期');
            }
            $user = User::find($user_id);
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
            $request->setParams('post',[
                'stock_id'=>$time->structure->stock_id,
                'structure' => $time->structure->type,
                'time' => $time->type,
                'value' => $time->value,
                'pay_amount' => $time->price,
                'scale_amount' => $scale_amount
            ]);
            return parent::insert($request);
        }
        return view('subscribe/insert');
    }

    /**
     * 更新
     * @param Request $request
     * @return Response
     */
    public function update(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $id = $request->post('id');
            $row = $this->model->find($id);
            $status = $request->post('status');
            $yield_amount = $request->post('yield_amount');

            if ($row->status == 6 && $status == 4) {
                //行权成功->完结
                $yield_rate =  ($yield_amount - $row->pay_amount) / $row->pay_amount * 100;
                $request->setParams('post',[
                    'yield_rate'=>$yield_rate
                ]);

                Withdraw::create([
                    'user_id' => $row->user_id,
                    'amount' => $yield_amount,
                    'type' => 0,
                    'status'=>3,
                    'memo'=>'期权结算收益',
                ]);
                User::score($yield_amount,$row->user_id,'期权收益','money');
            }
            if ($row->status == 0 && $status == 1){
                //认购中->认购成功
                if ($row->time == 0){
                    $month = 1;
                }elseif ($row->time == 1){
                    $month = 2;
                }elseif ($row->time == 2){
                    $month = 3;
                }else{
                    $month = 6;
                }
                $start_time = Carbon::today();
                $end_time = Carbon::today();
                $end_time = $end_time->addMonths($month);
                //加入队列  #自动平权
                $queue = 'subscribe';
                $data = ['id' => $row->id, 'event' => 'sell'];
                // 获取一个月后的结束时间戳
                $month_later_timestamp = $end_time->endOfDay()->timestamp;
                // 获取当前日期和时间
                $now_timestam = Carbon::now()->timestamp;
                // 计算时间差
                $time_difference = $month_later_timestamp - $now_timestam;
                Client::send($queue, $data, $time_difference);
                $request->setParams('post',[
                    'start_time'=>$start_time->toDateString(),
                    'end_time'=>$end_time->toDateString()
                ]);
            }
            if ($row->status == 8&&$status == 1){
                #申请撤销行权->认购成功
                $request->setParams('post',[
                    'cancel_time'=>date('Y-m-d H:i:s'),
                    'sell_time'=>null,
                    'sell_type'=>0,
                    'sell_price'=>0
                ]);

            }
            return parent::update($request);
        }
        return view('subscribe/update');
    }

}
