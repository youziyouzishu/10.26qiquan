<?php

namespace app\admin\model;

use plugin\admin\app\model\Base;

/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property integer $stock_id 所属标的
 * @property integer $type 结构:0=100call
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \app\admin\model\StockStructureTime> $time
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockStructure newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockStructure newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|StockStructure query()
 * @property-read mixed $type_text
 * @property-read \app\admin\model\Stock|null $stock
 * @mixin \Eloquent
 */
class StockStructure extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_stock_structure';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = [
        'stock_id','type'
    ];

    protected $appends = ['type_text'];

    public function time(){
        return $this->hasMany(StockStructureTime::class,'structure_id','id');
    }

    function stock()
    {
        return $this->belongsTo(Stock::class,'stock_id');
    }

    function getTypeTextAttribute($value)
    {
        $value = $value ?: ($this->type ?? '');
        $list = $this->getTypeList();
        return $list[$value] ?? '';
    }
    public function getTypeList()
    {
        return ['0'=>'100call'];
    }
    
    
    
}
