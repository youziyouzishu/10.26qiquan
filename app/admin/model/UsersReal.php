<?php

namespace app\admin\model;

use plugin\admin\app\model\Base;
use plugin\admin\app\model\User;


/**
 * 
 *
 * @property int $id 主键
 * @property int $user_id 用户
 * @property string $name 姓名
 * @property string $mobile 手机号
 * @property string $id_num 身份证号
 * @property string $bankcard 银行卡号
 * @property string $bankname 银行名称
 * @property string $open_bank 开户行
 * @property string $id_front 身份证正面
 * @property string $id_back 身份证反面
 * @property int $status 状态:0=待审核,1=通过,2=驳回
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsersReal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsersReal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsersReal query()
 * @property-read mixed $status_text
 * @property-read User|null $user
 * @mixin \Eloquent
 */
class UsersReal extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_users_real';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $appends = ['status_text'];

    protected $fillable = [
        'user_id',
        'name',
        'mobile',
        'id_num',
        'bankcard',
        'bankname',
        'open_bank',
        'id_front',
        'id_back',
    ];

    public function getStatusTextAttribute($value)
    {
        $value = $value ?: ($this->type ?? '');
        $list = $this->getStatusList();
        return $list[$value] ?? '';
    }

    public function getStatusList()
    {
        return ['0'=>'待审核','1'=>'通过','2'=>'驳回'];
    }

    function user()
    {
        return $this->belongsTo(User::class,'user_id','id');
    }






}
