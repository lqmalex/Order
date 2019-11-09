<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class ProductModel extends Model
{
    const STATUS_UP = 10;
    const STATUS_DOWN = 20;
    const STATUS_DEL = 90;

    protected $table = 'pre_product';
}
