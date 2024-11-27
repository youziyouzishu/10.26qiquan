<?php

namespace app\admin\model;

use plugin\admin\app\model\Base;

/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $name 证券名称
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $code 证券代码
 * @property string $bourse 交易所
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Stock query()
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \app\admin\model\StockStructure> $structure
 * @property-read \Illuminate\Database\Eloquent\Collection<int, \app\admin\model\StockStructureTime> $time
 * @mixin \Eloquent
 */
class Stock extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_stock';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = [
        'name','code','bourse'
    ];

    function structure()
    {
        return $this->hasMany(StockStructure::class,'stock_id','id');
    }

    public function time()
    {
        return $this->hasManyThrough(StockStructureTime::class, StockStructure::class,'stock_id','structure_id','id');
    }
    
    
}
