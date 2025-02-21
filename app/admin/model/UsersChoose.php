<?php

namespace app\admin\model;

use plugin\admin\app\model\Base;

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
