<?php

namespace app\admin\model;

use plugin\admin\app\model\Admin;
use plugin\admin\app\model\Base;

/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property integer $admin_id 管理员
 * @property string $score 变更积分
 * @property string $before 变更前积分
 * @property string $after 变更后积分
 * @property string $memo 备注
 * @property string $type 类型:money=余额
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property-read Admin|null $admin
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminScoreLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminScoreLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|AdminScoreLog query()
 * @mixin \Eloquent
 */
class AdminScoreLog extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_admin_score_log';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = ['admin_id','score','before','after','memo','type'];

    function admin()
    {
        return $this->belongsTo(Admin::class,'admin_id','id');
    }
    
    
}
