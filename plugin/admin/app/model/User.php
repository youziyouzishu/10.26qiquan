<?php

namespace plugin\admin\app\model;

use app\admin\model\UsersScoreLog;
use plugin\admin\app\model\Base;
use support\Db;

/**
 * 
 *
 * @property integer $id 主键(主键)
 * @property string $username 用户名
 * @property string $nickname 昵称
 * @property string $password 密码
 * @property string $sex 性别
 * @property string $avatar 头像
 * @property string $email 邮箱
 * @property string $mobile 手机
 * @property integer $level 等级
 * @property string $birthday 生日
 * @property integer $money 余额
 * @property integer $score 积分
 * @property string $last_time 登录时间
 * @property string $last_ip 登录ip
 * @property string $join_time 注册时间
 * @property string $join_ip 注册ip
 * @property string $token token
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property integer $role 角色
 * @property integer $status 禁用
 * @property string $mini_openid 小程序OPENID
 * @property string $h5_openid 公众号OPENID
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|User query()
 * @property string $unionid 开放平台统一标识
 * @property int $admin_id 所属管理员ID
 * @property-read \plugin\admin\app\model\Admin|null $admin
 * @mixin \Eloquent
 */
class User extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_users';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    protected $fillable = [
        'username', 'nickname', 'password', 'sex', 'avatar', 'email', 'mobile', 'level', 'birthday', 'money', 'score', 'last_time', 'last_ip', 'join_time', 'join_ip', 'token', 'created_at', 'updated_at', 'role', 'status', 'mini_openid', 'h5_openid','unionid'
    ];


    /**
     * 变更会员积分
     * @param int $score 积分
     * @param int $user_id 会员ID
     * @param string $memo 备注
     * @param string $type
     * @throws \Throwable
     */
    public static function score($score, $user_id, $memo, $type)
    {
        Db::connection('plugin.admin.mysql')->beginTransaction();
        try {
            $user = self::lockForUpdate()->find($user_id);
            if ($user && $score != 0) {
                $before = $user->$type;
                $after = $user->$type + $score;
                //更新会员信息
                $user->$type = $after;
                $user->save();
                //写入日志
                UsersScoreLog::create(['user_id' => $user_id, 'score' => $score, 'before' => $before, 'after' => $after, 'memo' => $memo, 'type' => $type]);
            }
            Db::connection('plugin.admin.mysql')->commit();
        } catch (\Throwable $e) {
            Db::connection('plugin.admin.mysql')->rollback();
        }
    }


    function admin()
    {
        return $this->belongsTo(Admin::class, 'admin_id','id');
    }
    
    
    
    
}
