<?php

namespace plugin\admin\app\model;

use app\admin\model\AdminScoreLog;
use plugin\admin\app\model\Base;
use support\Db;

/**
 * 
 *
 * @property integer $id ID(主键)
 * @property string $username 用户名
 * @property string $nickname 昵称
 * @property string $password 密码
 * @property string $avatar 头像
 * @property string $email 邮箱
 * @property string $mobile 手机
 * @property string $created_at 创建时间
 * @property string $updated_at 更新时间
 * @property string $login_at 登录时间
 * @property string $roles 角色
 * @property integer $status 状态 0正常 1禁用
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Admin query()
 * @property string $invitecode 邀请码
 * @property int $pid 上级
 * @property string $money 余额
 * @property string $rate 分佣百分比
 * @property-read Admin|null $parent
 * @mixin \Eloquent
 */
class Admin extends Base
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'wa_admins';

    /**
     * The primary key associated with the table.
     *
     * @var string
     */
    protected $primaryKey = 'id';

    function parent()
    {
        return $this->belongsTo(Admin::class, 'pid', 'id');
    }

    /**
     * 变更会员积分
     * @param int $score 积分
     * @param int $admin_id 后台ID
     * @param string $memo 备注
     * @param string $type
     * @throws \Throwable
     */
    public static function score($score, $admin_id, $memo, $type)
    {
        Db::connection('plugin.admin.mysql')->beginTransaction();
        try {
            $admin = self::lockForUpdate()->find($admin_id);
            if ($admin && $score != 0) {
                $before = $admin->$type;
                $after = $admin->$type + $score;
                //更新管理员信息
                $admin->$type = $after;
                $admin->save();
                //写入日志
                AdminScoreLog::create(['admin_id' => $admin_id, 'score' => $score, 'before' => $before, 'after' => $after, 'memo' => $memo, 'type' => $type]);
            }
            Db::connection('plugin.admin.mysql')->commit();
        } catch (\Throwable $e) {
            Db::connection('plugin.admin.mysql')->rollback();
        }
    }





}
