<?php

namespace App\Model;

use Illuminate\Database\Eloquent\Model;

class Classi extends Model
{

    /**
     * 与模型关联的表名
     *
     * @var string
     */
    protected $table = 'class';

    /**
     * 指示是否自动维护时间戳
     *
     * @var bool
     */
    public $timestamps = false;
    protected $guarded = ['id', 'add_time', 'edit_time', 'data_state'];
}
