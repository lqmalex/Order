<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class OrderModel extends Model
{
    //订单状态
    const STATUS_CONDUCT = 10; //进行中
    const STATUS_SUCCESS = 20; //交易成功
    const STATUS_NO = 90; //交易关闭(无效订单)
    //物流状态
    const DELIVERY_STATUS_NO = 30; //未发货
    const DELIVERY_STATUS_YES = 20; //已发货
    const DELIVERY_STATUS_SUCCESS = 10; //已收货
    //支付状态
    const PAYMENT_STATUS_YES = 10; //已付款
    const PAYMENT_STATUS_NO = 20; //未付款

    protected $table = 'pre_order';

    protected $fillable = [
        'number', 'user_id', 'total_fee', 'status', 'product_fee', 'express_fee', 'delivery_status', 'payment_status', 'receiver_name', 'receiver_province', 'receiver_city', 'receiver_district', 'receiver_detail', 'receiver_mobile'
    ];
}
