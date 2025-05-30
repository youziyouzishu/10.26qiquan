<?php

namespace app\admin\controller;

use plugin\admin\app\model\User;
use support\Request;
use support\Response;
use app\admin\model\Withdraw;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * 出入金记录 
 */
class WithdrawController extends Crud
{
    
    /**
     * @var Withdraw
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new Withdraw;
    }

    /**
     * 查询
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    public function select(Request $request): Response
    {
        [$where, $format, $limit, $field, $order] = $this->selectInput($request);
        $query = $this->doSelect($where, $field, $order)->with(['user']);
        return $this->doFormat($query, $format, $limit);
    }
    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('withdraw/index');
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
            $amount = $request->post('amount');
            $user_id = $request->post('user_id');
            $type = $request->post('type');
            $admin = admin();
            $data['admin_id'] = $admin['id'];

            if (!in_array(3,$admin['roles'])){
                //管理员和财务 直接无需审核
                if ($type == 0){
                    //入金操作
                    $data['status'] = 1;
                    $data['day'] = date('Y-m-d');
                    User::score($amount,$user_id,'入金','money');
                }
            }
            $request->setParams('post',$data);
            return parent::insert($request);
        }
        return view('withdraw/insert');
    }

    /**
     * 更新
     * @param Request $request
     * @return Response
     * @throws BusinessException
    */
    public function update(Request $request): Response
    {
        if ($request->method() === 'POST') {
            $id = $request->post('id');
            $row = $this->model->find($id);
            $status = $request->post('status');
            $data = [];
            if ($row->status == 0&&$status != 0){
                $data['check_time'] = date('Y-m-d H:i:s');
                if ($row->type == 0&&$status == 1){
                    //入金 审核通过 增加余额
                    $data['day'] = date('Y-m-d');
                    User::score($row->amount,$row->user_id,'入金','money');
                }

                if ($row->type == 1&&$status == 2){
                    //出金 审核不通过 退还余额余额
                    User::score($row->amount,$row->user_id,'出金失败退还余额','money');
                }
            }
            $request->setParams('post',$data);
            return parent::update($request);
        }
        return view('withdraw/update');
    }

}
