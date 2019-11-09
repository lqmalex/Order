<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderItemModel extends Model
{
    protected $table = 'pre_order_item';

    protected $fillable = [
        'order_id', 'product_id', 'product_full_name', 'sku_id', 'quantity', 'price'
    ];
}
