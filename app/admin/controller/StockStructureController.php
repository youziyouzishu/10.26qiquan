<?php

namespace app\admin\controller;

use support\Request;
use support\Response;
use app\admin\model\StockStructure;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * 标的结构 
 */
class StockStructureController extends Crud
{
    
    /**
     * @var StockStructure
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new StockStructure;
    }
    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('stock-structure/index');
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
            return parent::insert($request);
        }
        return view('stock-structure/insert');
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
            return parent::update($request);
        }
        return view('stock-structure/update');
    }

}
