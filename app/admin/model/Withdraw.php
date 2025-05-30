<?php

namespace app\admin\model;

use plugin\admin\app\model\Base;
use plugin\admin\app\model\User;

/**
 * 
 *
 * @property int $id 主键
 * @property int $user_id 所属用户
 * @property string $amount 金额
 * @property string $image 转账凭证
 * @property int $status 状态:0=待审核,1=审核通过,2=审核不通过
 * @property string $reason 原因
 * @property string|null $day 入金日期
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Withdraw newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Withdraw newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Withdraw query()
 * @property int $type 类型:0=入金,1=出金
 * @property int $admin_id 所属管理员
 * @property string $memo 备注
 * @property string|null $check_time 审核时间
 * @property-read mixed $status_text
 * @property-read mixed $type_text
 * @property-read User|null $user
 * @mixin \Eloquent
 */
class Withdraw extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_withdraw';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $appends = [
        'status_text'
    ];

    protected $fillable = [
        'user_id','amount','image','status','reason','day','type','admin_id','memo'
    ];

    function user()
    {
        return $this->belongsTo(User::class,'user_id','id');
    }

    function getStatusTextAttribute($value)
    {
        $value = $value ?: ($this->status ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }


    #状态:0=认购中,1=认购成功,2=认购失败,3=行权中,4=完结
    public function getStatusList()
    {
        return [
            '0'=>'待审核',
            '1'=>'审核通过',
            '2'=>'审核不通过',
            '3'=>'期权收益',
        ];
    }

       function getTypeTextAttribute($value)
       {
           $value = $value ?: ($this->type ?? '');
           $list = $this->getTypeList();
           return $list[$value] ?? '';
       }


    #状态:0=认购中,1=认购成功,2=认购失败,3=行权中,4=完结
    public function getTypeList()
    {
        return [
            '0'=>'入金',
            '1'=>'出金',
        ];
    }




}
