<?php

namespace app\admin\model;

use plugin\admin\app\model\Admin;
use plugin\admin\app\model\Base;

/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property integer $admin_id 管理员
 * @property string $withdraw_amount 提现金额
 * @property string $fee 手续费
 * @property string $arrival_amount 到账金额
 * @property integer $status 状态:0=待审核,1=已到账,2=驳回
 * @property string $mark 备注
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property-read Admin|null $admin
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminWithdraw newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminWithdraw newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminWithdraw query()
 * @mixin \Eloquent
 */
class AdminWithdraw extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_admin_withdraw';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = [
        'admin_id',
        'withdraw_amount',
        'fee',
        'arrival_amount',
        'status',
        'mark',
    ];

    function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id', 'id');
    }


    
    
    
}
