<?php

namespace app\admin\model;

use plugin\admin\app\model\Base;


/**
 * 
 *
 * @property int $id 主键
 * @property int $user_id 用户
 * @property string $score 变更积分
 * @property string $before 变更前积分
 * @property string $after 变更后积分
 * @property string $memo 备注
 * @property string $type 类型:money=余额
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsersScoreLog newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsersScoreLog newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsersScoreLog query()
 * @mixin \Eloquent
 */
class UsersScoreLog extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_users_score_log';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = [
        'user_id','score','before','after','memo','type'
    ];



}
