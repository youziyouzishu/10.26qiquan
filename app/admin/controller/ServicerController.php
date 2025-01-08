<?php

namespace app\admin\controller;

use plugin\admin\app\common\Util;
use plugin\admin\app\model\Admin;
use plugin\admin\app\model\AdminRole;
use support\Request;
use support\Response;
use app\admin\model\Stock;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * 业务员列表
 */
class ServicerController extends Crud
{

    /**
     * @var Stock
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
        $query = $this->doSelect($where, $field, $order)->with(['parent']);
        if (in_array(5, admin('roles'))) {
            $query->where('pid',admin_id());
        }else{
            $servicer_ids = AdminRole::where('role_id', 4)->pluck('admin_id');
            $query->whereIn('id', $servicer_ids);
        }
        return $this->doFormat($query, $format, $limit);
    }


    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('servicer/index');
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
            $admin = $this->model->find(admin_id());
            if (!in_array(5, admin('roles'))) {
                return $this->fail('只有经销商可以添加业务员');
            }
            $request->setParams('post', [
                'invitecode' => Util::generateAdminInvitecode(),
                'pid' => admin_id()
            ]);
            if ($request->post('rate') > $admin->rate) {
                return $this->fail('业务员佣金不能大于经销商佣金');
            }
            $data = $this->insertInput($request);
            $admin_id = $this->doInsert($data);
            AdminRole::where('admin_id', $admin_id)->delete();
            $admin_role = new AdminRole;
            $admin_role->admin_id = $admin_id;
            $admin_role->role_id = 4;
            $admin_role->save();

            return $this->success('ok', ['id' => $admin_id]);
        }
        return view('servicer/insert');
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
            $admin = $this->model->find(admin_id());
            if (!in_array(5, admin('roles'))) {
                return $this->fail('只有经销商可以编辑业务员');
            }
            if ($request->post('rate') > $admin->rate) {
                return $this->fail('业务员佣金不能大于经销商佣金');
            }
            return parent::update($request);
        }
        return view('servicer/update');
    }


}
