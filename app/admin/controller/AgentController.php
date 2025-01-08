<?php

namespace app\admin\controller;

use plugin\admin\app\model\Admin;
use plugin\admin\app\model\AdminRole;
use plugin\admin\app\model\Role;
use support\Request;
use support\Response;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * 经销商管理 
 */
class AgentController extends Crud
{
    
    /**
     * @var Admin
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new Admin;
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
        $agent_ids = AdminRole::where('role_id',5)->pluck('admin_id');
        $query = $this->doSelect($where, $field, $order)->whereIn('id',$agent_ids);
        return $this->doFormat($query, $format, $limit);
    }
    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('agent/index');
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
            $rate = $request->post('rate');
            if (!in_array(3, admin('roles'))) {
                return $this->fail('只有管理员可以添加经销商');
            }
            if ($rate > 100){
                return $this->fail('佣金比例不能超过100%');
            }
            return parent::insert($request);
        }
        return view('agent/insert');
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
            $rate = $request->post('rate');
            if (!in_array(3, admin('roles'))) {
                return $this->fail('只有管理员可以编辑经销商');
            }
            if ($rate > 100){
                return $this->fail('佣金比例不能超过100%');
            }
            return parent::update($request);
        }
        return view('agent/update');
    }

}
