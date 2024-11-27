<?php

namespace app\admin\model;

use plugin\admin\app\model\Base;

/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property integer $structure_id 所属结构
 * @property integer $type 类型:0=1个月,1=2个月,2=3个月,3=六个月
 * @property integer $broker 券商:0=中信
 * @property string $value 平均看涨值
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockStructureTime newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockStructureTime newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockStructureTime query()
 * @property-read \app\admin\model\StockStructure|null $structure
 * @property-read mixed $broker_text
 * @property-read mixed $price
 * @property-read mixed $type_text
 * @property int $weigh 权重
 * @mixin \Eloquent
 */
class StockStructureTime extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_stock_structure_time';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = [
        'structure_id', 'type', 'broker', 'value'
    ];

    protected $appends = ['price', 'type_text', 'broker_text'];

    public function getPriceAttribute()
    {
        $value = is_numeric($this->value) ? $this->value : 0;
        $type = $this->structure->type;
        switch ($type) {
            case 0:
                return round($value * 1000000, 2);
            case 1:
                return round($value * 2000000, 2);
            default:
                return 0;
        }
    }


    function structure()
    {
        return $this->belongsTo(StockStructure::class, 'structure_id');
    }

    function getTypeTextAttribute($value)
    {
        $value = $value ?: ($this->type ?? '');
        $list = $this->getTypeList();
        return $list[$value] ?? '';
    }

    public function getTypeList()
    {
        return ['0' => '1个月', '1' => '2个月', '2' => '3个月', '3' => '6个月'];
    }

    function getBrokerTextAttribute($value)
    {
        $value = $value ?: ($this->broker ?? '');
        $list = $this->getBrokerList();
        return $list[$value] ?? '';
    }

    public function getBrokerList()
    {
        return ['0' => '中信'];
    }


}
