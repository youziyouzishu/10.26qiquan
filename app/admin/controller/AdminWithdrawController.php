<?php

namespace app\admin\controller;

use plugin\admin\app\model\Admin;
use plugin\admin\app\model\AdminRole;
use support\Request;
use support\Response;
use app\admin\model\AdminWithdraw;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * 后台提现记录 
 */
class AdminWithdrawController extends Crud
{
    
    /**
     * @var AdminWithdraw
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new AdminWithdraw;
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
        $query = $this->doSelect($where, $field, $order)->with(['admin']);
        if (in_array(3,admin('roles'))){
            //管理员能看到所有经销商
            $admin_ids = AdminRole::where('role_id',5)->pluck('admin_id');
            $query->whereIn('admin_id',$admin_ids);
        }
        if (in_array(4,admin('roles'))){
            //业务员只能看到自己的
            $query->where('admin_id',admin_id());
        }
        if (in_array(5,admin('roles'))){
            //经销商只能看到自己的管理员和自己
            $admin_ids = Admin::where('pid',admin_id())->pluck('id');
            $admin_ids->push(admin_id());
            $query->whereIn('admin_id',$admin_ids);
        }
        return $this->doFormat($query, $format, $limit);
    }
    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        if (in_array(3,admin('roles'))){
            $guanliyuan = 1;
        }else{
            $guanliyuan = 0;
        }
        return view('admin-withdraw/index',['admin_id'=>admin_id(),'guanliyuan'=>$guanliyuan]);
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
            if (!in_array(4,admin('roles'))&&!in_array(5,admin('roles'))){
                return $this->fail('只有经销商和业务员可以提现');
            }
            $admin = admin();
            if (empty($admin['alipay'])||empty($admin['wechatpay'])){
                return $this->fail('请在通用设置->个人资料设置支付宝和微信收款码');
            }
            $request->setParams('post',[
                'admin_id'=>admin_id(),
                'alipay'=>$admin['alipay'],
                'wechatpay'=>$admin['wechatpay'],
            ]);

            if ($admin['money']< $request->post('withdraw_amount')){
                return $this->fail('余额不足');
            }
            Admin::score(-$request->post('withdraw_amount'),admin_id(),'提现','money');
            return parent::insert($request);
        }
        return view('admin-withdraw/insert');
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
            $row = $this->model->find($request->post('id'));
            $role_ids = AdminRole::where(['admin_id'=>$row->admin_id])->pluck('role_id')->toArray();
            $status = $request->post('status');
            if (in_array(3,admin('roles'))&&!in_array(5,$role_ids)){
                //如果当前用户是管理员  审核的不是经销商
                return $this->fail('无权限');
            }

            if (in_array(5,admin('roles'))&&!in_array(4,$role_ids)){
                //如果当前用户是经销商  审核的不是管理员
                return $this->fail('无权限');
            }
            if ($status == 1 && $row->status == 0){
                //审核通过

            }
            if ($status == 2 && $row->status == 0){
                //审核拒绝通过
                Admin::score($row->withdraw_amount,$row->admin_id,'驳回提现','money');
            }

            return parent::update($request);
        }
        return view('admin-withdraw/update');
    }

}
