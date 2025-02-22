<?php

namespace app\admin\model;

use plugin\admin\app\model\Base;

/**
 * @property int $id 主键
 * @property int $user_id 用户
 * @property string $stock_code 代码
 * @property \Illuminate\Support\Carbon|null $created_at 创建时间
 * @property \Illuminate\Support\Carbon|null $updated_at 更新时间
 * @mixin \Eloquent
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsersChoose newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsersChoose newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|UsersChoose query()
 */
class UsersChoose extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_users_choose';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = ['user_id','stock_code'];



}
