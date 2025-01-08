<?php

namespace app\admin\model;

use plugin\admin\app\model\Base;
use plugin\admin\app\model\User;


/**
 * 
 *
 * @property int $id 主键
 * @property int $user_id 用户
 * @property int $type 类型:0=市价认购 1=限价认购
 * @property int $stock_id 标的
 * @property int $structure 结构:0=100call
 * @property string $price 买入价格
 * @property int $time 期限:0=1个月,1=2个月,2=3个月,3=六个月
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscribe newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscribe newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Subscribe query()
 * @property string $value 平均看涨值
 * @property string $pay_amount 支付金额
 * @property int $status 状态:0=认购中,1=认购成功,2=认购失败,3=行权中,4=完结,5=撤销认购中,6=行权成功,7=撤销认购,8=撤销行权中
 * @property string $reason 拒绝原因
 * @property string $yield_amount 收益
 * @property string $yield_rate 收益率
 * @property string $scale_amount 认购规模
 * @property-read \app\admin\model\Stock|null $stock
 * @property-read User|null $user
 * @property string $sell_price 行权价
 * @property string|null $start_time 起始日
 * @property string|null $end_time 到期日
 * @property string|null $sell_time 行权时间
 * @property-read mixed $status_text
 * @property string|null $cancel_time 撤销时间
 * @property int $sell_num 行权次数
 * @property int $sell_type 行权类型:0=无,1=市价行权,2=限价行权
 * @property-read mixed $sell_type_text
 * @property-read mixed $structure_text
 * @property-read mixed $time_text
 * @property-read mixed $type_text
 * @property-read mixed $status_by
 * @property string $actual_cost 实际成本
 * @property string $access_fee 通道费用
 * @mixin \Eloquent
 */
class Subscribe extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_subscribe';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id','type','stock_id','structure','price','time','value','pay_amount','status','reason','yield_amount',
        'yield_rate','scale_amount',
        'sell_price','sell_time'
    ];

    protected $appends = [
        'status_text',
        'type_text',
        'time_text',
        'structure_text',
        'status_by'
    ];

    function stock()
    {
        return $this->belongsTo(Stock::class,'stock_id');
    }

    function user()
    {
        return $this->belongsTo(User::class,'user_id');
    }


    function getStatusTextAttribute($value)
    {
        $value = $value ?: ($this->status ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }

    public function getSellTypeTextAttribute($value)
    {
        $value = $value ?: ($this->sell_type ?? '');
        $list = $this->getSellTypeList();
        return $list[$value] ?? '';
    }

    public function getTypeTextAttribute($value)
    {
        $value = $value ?: ($this->type ?? '');
        $list = $this->getTypeList();
        return $list[$value] ?? '';
    }

    public function getTimeTextAttribute($value)
    {
        $value = $value ?: ($this->time ?? '');
        $list = $this->getTimeList();
        return $list[$value] ?? '';
    }

    public function getStructureTextAttribute($value)
    {
        $value = $value ?: ($this->structure ?? '');
        $list = $this->getStructureList();
        return $list[$value] ?? '';
    }


    public function getStatusByAttribute($value)
    {
        $value = $value ?: ($this->status ?? '');
        if (in_array($value,[0, 1, 3, 5, 6, 7, 8])){
            return '目前在持';
        }else{
            return '历史交割';
        }
    }




    #状态:0=认购中,1=认购成功,2=认购失败,3=行权中,4=完结
    public function getStatusList()
    {
        return [
            '0'=>'认购中',
            '1'=>'认购成功',
            '2'=>'认购失败',
            '3'=>'行权中',
            '4'=>'完结',
            '5'=>'撤销认购中',
            '6'=>'行权成功',
            '7'=>'撤销认购',
            '8'=>'撤销行权中',
        ];
    }

    public function getTypeList()
    {
        return ['0'=>'市价认购','1'=>'限价认购'];
    }

    public function getSellTypeList()
    {
        return ['0'=>'无','1'=>'市价行权','2'=>'限价行权'];
    }

    public function getTimeList()
    {
        return ['0'=>'1个月','1'=>'2个月','2'=>'3个月','3'=>'6个月'];
    }

    public function getStructureList()
    {
        return ['0'=>'100call'];
    }






}
