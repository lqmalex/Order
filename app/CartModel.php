<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class CartModel extends Model
{
    const STATUS_YES = 20;
    const STATUS_NO = 10;
    const STATUS_DEL = 90;

    protected $table = 'pre_cart';

    public function sku() {
        return $this->belongsTo('App\SkuModel');
    }
}
