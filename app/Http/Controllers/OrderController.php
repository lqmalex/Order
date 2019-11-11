<?php

namespace App\Http\Controllers;

use App\AddressModel;
use App\CartModel;
use App\ExpressModel;
use App\OrderItemModel;
use App\OrderModel;
use App\ProductModel;
use App\SkuModel;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    private $order = [];

    /**
     * 主订单函数
     * @param Request $request
     * @return array
     */
    public function create(Request $request)
    {
        $arr = json_decode($request->getContent(), true);

        $add_res = (new AddressModel())->where('id', '=', $arr['addressId'])
            ->where('user_id', '=', $arr['userId'])
            ->where('status', '=', AddressModel::STATUS_YES)
            ->first();
        if (empty($add_res)) {
            return [
                'status' => false,
                'msg' => '收货地址无效'
            ];
        }

        foreach ($arr['cartId'] as $k => $v) {
            $cart_res = (new CartModel())
                ->with('sku')
                ->where('id', '=', $v)
                ->where('user_id', '=', $arr['userId'])
                ->where('status', '=', CartModel::STATUS_NO)
                ->first();

            if (empty($cart_res)) {
                return [
                    'status' => false,
                    'msg' => '购物车不存在'
                ];
            }

            $info = $this->product($cart_res['sku']->id, $cart_res->quantity, $cart_res->id);

            if (!$info['status']) {
                return $info;
            }
        }

        foreach ($this->order as $k => &$v) {
            //生成唯一订单号
            $num = date('Ymd') . substr(implode(NULL, array_map('ord', str_split(substr(uniqid(), 7, 13), 1))), 0, 8);
            //整理数据
            $v['number'] = $num;
            $v['user_id'] = $arr['userId'];
            $v['total_fee'] = $v['product_fee'] + $v['express_fee'];
            $v['status'] = OrderModel::STATUS_CONDUCT;
            $v['delivery_status'] = OrderModel::DELIVERY_STATUS_NO;
            $v['payment_status'] = OrderModel::PAYMENT_STATUS_NO;
            $v['receiver_name'] = $add_res->name;
            $v['receiver_province'] = $add_res->province;
            $v['receiver_city'] = $add_res->city;
            $v['receiver_district'] = $add_res->district;
            $v['receiver_detail'] = $add_res->detail;
            $v['receiver_mobile'] = $add_res->mobile;
            $order_res = (new OrderModel())->create($v);
            if ($order_res) {
                $this->orderItemCreate($order_res->id, $v);
            } else {
                return [
                    'status' => false,
                    'msg' => '添加失败'
                ];
            }
        }
    }

    /**
     * 处理数据
     * @param $sku_id 订单id
     * @param $quantity 重量
     * @return array
     */
    private function product($sku_id, $quantity)
    {
        $sku_res = (new SkuModel())->with(['product' => function ($query) {
            $query->where('status', '=', ProductModel::STATUS_UP);
        }])
            ->where('id', '=', $sku_id)
            ->where('status', '=', SkuModel::STATUS_YES)
            ->first();

        if ($sku_res->quantity < $quantity) {
            return [
                'status' => false,
                'msg' => $sku_res['product']->name . $sku_res->version . '库存不足',
            ];
        }

        $express_res = (new ExpressModel())
            ->where('status', '=', ExpressModel::STATUS_YES)
            ->where('id', '=', $sku_res['product']->express_id)->first();

        if (count($this->order) != 0) {
            foreach ($this->order as $k => &$v) {
                $price = $v['product_fee'] + $sku_res->price * $quantity;
                $num = $this->weight($express_res->min_money, $express_res->weight, $express_res->fee, $sku_res->weight * $quantity, $price, $v['express_fee']);
                $v['sku_id'][] = $sku_id;
                $v['sku_price'][] = $sku_res->price;
                $v['product_name'][] = $sku_res['product']->name . $sku_res->version;
                $v['sku_quantity'][] = $quantity;
                $v['product_fee'] = $price;
                $v['express_fee'] = $num;
                return ['status' => true];
            }

            return ['status' => true];
        } else {
            $num = $this->weight($express_res->min_money, $express_res->weight, $express_res->fee, $sku_res->weight * $quantity, $sku_res->price * $quantity);

            $this->order[] = [
                'id' => $sku_res['product']->id,
                'product_name' => [$sku_res['product']->name . $sku_res->version],
                'sku_id' => [$sku_id],
                'sku_price' => [$sku_res->price],
                'sku_quantity' => [$quantity],
                'product_fee' => $sku_res->price * $quantity,
                'express_fee' => $num,
            ];

            return ['status' => true];
        }
    }

    /**
     * 计算运费
     * @param $min_money
     * @param $weight
     * @param $fee
     * @param $sku_weight
     * @param $price
     * @param null $sku_fee
     * @return float|int|null
     */
    private function weight($min_money, $weight, $fee, $sku_weight, $price, $sku_fee = null)
    {
        if ($sku_weight < $weight) {
            return $fee;
        } else {
            $num = $sku_fee === null ? ($sku_weight / $weight) * $fee : $sku_fee + (($sku_weight / $weight) * $fee);

            if ($price >= $min_money) {
                return 0;
            }

            return $num;
        }
    }

    /**
     * 添加订单详情 以及修改购物车状态
     * @param $order_id 订单id
     * @param $arr 数据
     */
    private function orderItemCreate($order_id, $arr)
    {
        foreach ($arr['sku_id'] as $k => $v) {
            $data = [];
            $data['order_id'] = $order_id;
            $data['product_id'] = $arr['id'];
            $data['product_full_name'] = $arr['product_name'][$k];
            $data['sku_id'] = $v;
            $data['quantity'] = $arr['sku_quantity'][$k];
            $data['price'] = $arr['sku_price'][$k];
            $item_res = (new OrderItemModel())->create($data);
            if ($item_res) {
                (new CartModel())->where('user_id', '=', $arr['user_id'])->where('sku_id', '=', $v)->update(['status' => CartModel::STATUS_YES]);
            } else {
                return [
                    'status' => false,
                    'msg' => '添加失败'
                ];
            }
        }
    }
}
