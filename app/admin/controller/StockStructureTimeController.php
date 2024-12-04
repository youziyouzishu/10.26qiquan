<?php

namespace app\admin\controller;

use app\admin\model\Stock;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Reader\Csv;
use PhpOffice\PhpSpreadsheet\Reader\Xls;
use PhpOffice\PhpSpreadsheet\Reader\Xlsx;
use support\Cache;
use support\Db;
use support\Request;
use support\Response;
use app\admin\model\StockStructureTime;
use plugin\admin\app\controller\Crud;
use support\exception\BusinessException;

/**
 * 标的期限 
 */
class StockStructureTimeController extends Crud
{
    
    /**
     * @var StockStructureTime
     */
    protected $model = null;

    /**
     * 构造函数
     * @return void
     */
    public function __construct()
    {
        $this->model = new StockStructureTime;
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
        $query = $this->doSelect($where, $field, $order)->with('structure.stock');
        return $this->doFormat($query, $format, $limit);
    }
    
    /**
     * 浏览
     * @return Response
     */
    public function index(): Response
    {
        return view('stock-structure-time/index');
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
        return view('stock-structure-time/insert');
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
        return view('stock-structure-time/update');
    }

    /**
     * 导入
     * @param Request $request
     * @return Response
     * @throws BusinessException
     */
    function imported(Request $request)
    {
//        $lock = Cache::get('imported');
//        if ($lock == '1'){
//            return $this->fail('上个文件正在导入中，请稍后再试');
//        }
//        Cache::set('imported', '1');
        $file = current($request->file());
        $ext = $file->getUploadExtension();
        $filePath = $file->getRealPath();
        //实例化reader
        if (!in_array($ext, ['xls', 'xlsx'])) {
            return $this->fail('文件格式错误');
        }
        if ($ext === 'csv') {
            $file = fopen($file->getRealPath(), 'r');
            $filePath = tempnam(sys_get_temp_dir(), 'import_csv');
            $fp = fopen($filePath, 'w');
            $n = 0;
            while ($line = fgets($file)) {
                $line = rtrim($line, "\n\r\0");
                $encoding = mb_detect_encoding($line, ['utf-8', 'gbk', 'latin1', 'big5']);
                if ($encoding !== 'utf-8') {
                    $line = mb_convert_encoding($line, 'utf-8', $encoding);
                }
                if ($n == 0 || preg_match('/^".*"$/', $line)) {
                    fwrite($fp, $line . "\n");
                } else {
                    fwrite($fp, '"' . str_replace(['"', ','], ['""', '","'], $line) . "\"\n");
                }
                $n++;
            }
            fclose($file) || fclose($fp);

            $reader = new Csv();
        } elseif ($ext === 'xls') {
            $reader = new Xls();
        } else {
            $reader = new Xlsx();
        }

        try {
            if (!$PHPExcel = $reader->load($filePath)) {
                return $this->fail('文件格式错误');
            }
            // 读取文件中的第一个工作表
            $currentSheet = $PHPExcel->getSheet(0);
            $allColumn = 'F'; // 取得最大的列号
            $allRow = $currentSheet->getHighestRow(); // 取得一共有多少行
            $maxColumnNumber = Coordinate::columnIndexFromString($allColumn);

            // 定义字段名
            $columns = ['code', 'name', 'm1', 'm2', 'm3', 'm4'];
            // 读取后续行的数据
            $insert = [];
            for ($currentRow = 3; $currentRow <= $allRow; $currentRow++) {
                $rowValues = [];
                for ($currentColumn = 1; $currentColumn <= $maxColumnNumber; $currentColumn++) {
                    $cellAddress = Coordinate::stringFromColumnIndex($currentColumn) . $currentRow;
                    $val = $currentSheet->getCell($cellAddress)->getValue();
                    $rowValues[$columns[$currentColumn - 1]] = $val;
                }
                $insert[] = $rowValues;
            }
            DB::connection('plugin.admin.mysql')->beginTransaction();
            try {
                // 使用 array_filter 过滤掉 a 为 null 的元素
                $insert = array_filter($insert, function($item) {
                    return $item['code'] !== null;
                });
                foreach ($insert as $item) {

                    list($code, $bourse) = explode('.', $item['code']);
                    $stock = Stock::firstOrCreate(['code' => $code], [
                        'name' => $item['name'],
                        'bourse' => $bourse,
                    ]);

                    $structure = $stock->structure()->firstOrCreate(['type' => 0]);
                    $structure->time()->where('broker', 0)->delete();
                    for ($i = 0; $i <= 3; $i++) {
                        $structure->time()->create([
                            'type' => $i,
                            'broker' => 0,
                            'value' => $item['m' . ($i + 1)]
                        ]);
                    }
                }
                DB::connection('plugin.admin.mysql')->commit();
            } catch (\Exception $e) {
                DB::connection('plugin.admin.mysql')->rollBack();
                throw $e;
            }
        } catch (\Throwable $exception) {
//            Cache::set('imported','0');
            return $this->fail($exception->getMessage());
        }
//        Cache::set('imported','0');
        return $this->success('导入成功');
    }

}
