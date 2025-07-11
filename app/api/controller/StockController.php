<?php

namespace app\api\controller;

use app\admin\model\Stock;
use app\admin\model\StockStructure;
use app\admin\model\StockStructureTime;
use app\api\basic\Base;
use EasyWeChat\MiniApp\Application;
use Endroid\QrCode\Color\Color;
use Endroid\QrCode\Encoding\Encoding;
use Endroid\QrCode\ErrorCorrectionLevel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\RoundBlockSizeMode;
use Endroid\QrCode\Writer\PngWriter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use plugin\admin\app\model\Option;
use plugin\admin\app\model\User;
use support\Log;
use support\Request;

class StockController extends Base
{

    protected $noNeedLogin = ['share','getList'];

    function getList(Request $request)
    {
        $rows = StockStructureTime::with(['structure.stock'])->where(['type' => 1])->orderByDesc('weigh')->paginate()->items();
        return $this->success('成功', $rows);
    }

    function getStockByKeyWord(Request $request)
    {
        $keyword = $request->post('keyword');
        $rows = Stock::where(function ($query) use ($keyword) {
            $query->where('name', 'like', "%{$keyword}%")->orWhere('code', 'like', "%{$keyword}%");
        })->get();
        return $this->success('成功', $rows);
    }

    function inquiryPprice(Request $request)
    {
        $stock_id = $request->post('stock_id');
        $time = $request->post('time', ''); #期限:0=1个月,1=2个月,2=3个月,3=六个月
        $structure = $request->post('structure');#结构:0=100call
        $broker = $request->post('broker');#报价方 0=中信
        $time = explode(',', $time);
        $structure = explode(',', $structure);
        $broker = explode(',', $broker);


        $stock = Stock::with(['structure' => function (HasMany $many) use ($structure, $time, $broker) {
            $many->with(['time' => function (HasMany $many) use ($time, $broker) {
                $many->when(!empty($time), function ($query) use ($time) {
                    $query->whereIn('type', $time);
                })->when(!empty($broker) || $broker == 0, function (Builder $query) use ($broker) {
                    $query->whereIn('broker', $broker);
                });
            }])->whereIn('type', $structure);
        }])->find($stock_id);
        if (!$stock) {
            return $this->fail('标的不存在');
        }
        return $this->success('成功', $stock);
    }

    function share(Request $request)
    {
        $type = $request->post('type');#0=分享指令 1=分享报价
        $stock_id = $request->post('stock_id');
        $time = $request->post('time'); #期限:0=1个月,1=2个月,2=3个月,3=六个月
        $structure = $request->post('structure');#结构:0=100call
        $broker = $request->post('broker');#报价方 0=中信
        $h5 = $request->isTerminal('h5');
        if ($h5) {
            // 使用构建器创建 QR Code
            $writer = new PngWriter();
            $qrCode = new QrCode(
                data: 'https://shutz.top/web/index.html',
                encoding: new Encoding('UTF-8'),
                errorCorrectionLevel: ErrorCorrectionLevel::Low,
                size: 100,
                margin: 10,
                roundBlockSizeMode: RoundBlockSizeMode::Margin,
                foregroundColor: new Color(0, 0, 0),
                backgroundColor: new Color(255, 255, 255)
            );
            $base64 = $writer->write($qrCode)->getDataUri();
        } else {
            try {
                $app = new Application(config('wechatmini'));
                $data = [
                    'scene' => '1',
                    'page' => 'pages/home',
                    'width' => 280,
                    'check_path' => !config('app.debug'),
                ];
                $response = $app->getClient()->postJson('/wxa/getwxacodeunlimit', $data);
                $base64 = "data:image/png;base64," . base64_encode($response->getContent());
            } catch (\Throwable $e) {
                // 失败
                return $this->fail($e->getMessage());
            }
        }


        $getTypeByStockStructure = (new StockStructure)->getTypeList();
        $arr_structure = explode(',', $structure);
        $string_structure = [];
        foreach ($arr_structure as $v) {
            $string_structure[] = $getTypeByStockStructure[$v];
        }
        $getTypeByStockStructureTime = (new StockStructureTime)->getTypeList();

        $arr_time = empty($time) ? [] : explode(',', $time);

        $string_time = [];

        foreach ($arr_time as $v) {
            $string_time[] = $getTypeByStockStructureTime[$v];
        }

        $getBorkerByStockStructureTime = (new StockStructureTime)->getBrokerList();
        $arr_broker = explode(',', $broker);
        $string_broker = [];
        foreach ($arr_broker as $v) {
            $string_broker[] = $getBorkerByStockStructureTime[$v];
        }

        $stock = Stock::find($stock_id);
        if (!$stock) {
            return $this->fail('标的不存在');
        }
        $data = [
            'name' => $stock->name,
            'code' => $stock->code . '.' . $stock->bourse,
            'type' => '香草期权',
            'structure' => implode('|', $string_structure),
            'time' => empty($string_time) ? '无' : implode('|', $string_time),
            'broker' => implode('|', $string_broker),
            'uri' => $base64
        ];

        if ($type == 1) {
            //分享报价
            $structure = StockStructure::where('stock_id', $stock->id)->where('type', $structure)->first();
            $time = StockStructureTime::where('structure_id', $structure->id)->where('type', $time)->first();
            $data['price'] = $time->price;
        }
        return $this->success('成功', $data);
    }


}
