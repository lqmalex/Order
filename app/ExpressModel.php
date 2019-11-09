<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ExpressModel extends Model
{
    const STATUS_YES = 10;
    const STATUS_NO = 90;

    protected $table = 'pre_express';
}
