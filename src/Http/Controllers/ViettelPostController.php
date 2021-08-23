<?php

namespace Sudo\ViettelPost\Http\Controllers;

use Illuminate\Http\Request;
use Sudo\ViettelPost\Models\Province;
use Sudo\ViettelPost\Models\District;
use Sudo\ViettelPost\Models\Ward;
use Sudo\ViettelPost\Models\ViettelPostStore;
use DB;
use Cache;
use Session;
use Illuminate\Support\Facades\Auth;
use Sudo\Theme\Models\User;
use GuzzleHttp\Client;
use Sudo\Ecommerce\Models\Order;
use \Sudo\Ecommerce\Models\OrderDetail;
use \Sudo\Ecommerce\Models\OrderHistory;

class ViettelPostController
{

    /*
        Get token
        return status, token
    */
    public function getToken(){
        try {
            $vtp_account = getOption('vtp_account');
            $token = null;
            $client = new Client();
            $response = $client->request('POST', 'https://partner.viettelpost.vn/v2/user/Login', [
                'headers' => ['Content-Type' => 'application/json', 'Accept' => 'application/json'],
                'body'    => json_encode([
                    'USERNAME' => $vtp_account['username'],
                    'PASSWORD' => $vtp_account['password']
                ])
            ]);
            if ($response->getStatusCode() == 200 && $response->getReasonPhrase() == 'OK') {
                $response = $response->getBody()->getContents();
                $response = json_decode($response);
                $data = $response->data;
                $token = $data->token;
            }
            return ['status' => 1, 'token' => $token];
        } catch (\Exception $e) {
            return ['status' => 0, 'message' => $e->getMessage()];
        }
    }
    /*
        Tạo kho vận
    */
    public function createInventory($data) {
        $get_token = $this->getToken();
        $token = $get_token['token'];
        $client = new Client();
        $response = $client->request('POST', 'https://partner.viettelpost.vn/v2/user/registerInventory', [
            'headers' => [
                'Authorization' => $token,
                'token' => $token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'body' => json_encode($data)
        ]);
        if ($response->getStatusCode() == 200 && $response->getReasonPhrase() == 'OK') {
            $result = $response->getBody()->getContents();
            \Log::info($result);
            $result = json_decode($result);
            if($result->status == 200){
                return ['status'=> true, 'data'=> $result->data];
            }
            return ['status'=> false];
        }else {
            return ['status'=> false];

        }
    }
    /*
        - Lấy tất cả loại hình vận chuyển + Phí cho đơn được xác định
        $data = array(
            "PRODUCT_WEIGHT" => $weight, // cân nặng
            "PRODUCT_PRICE" => $total_price, // tổng tiền
            "MONEY_COLLECTION" => $total_price, // tổng tiền
            "SENDER_PROVINCE" => $sender->province_id, // id tỉnh thành kho gửi
            "SENDER_DISTRICT" => $sender->district_id, // id quận huyện gửi
            "RECEIVER_PROVINCE" => (int)$provincial, // id tình thành người nhận
            "RECEIVER_DISTRICT" => (int)$district, // id quận huyện người gửi
            "PRODUCT_TYPE" => "HH", // loại gửi là hàng hóa
            "TYPE" => 1
        );
    */
    public function getPriceAll($order) {
        $get_token = $this->getToken();
        $token = $get_token['token'];
        $client = new Client();
        $response = $client->request('POST', 'https://partner.viettelpost.vn/v2/order/getPriceAll', [
            'headers' => [
                'Authorization' => $token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'body' => json_encode($order)
        ]);
        if ($response->getStatusCode() == 200 && $response->getReasonPhrase() == 'OK') {
            $data = $response->getBody()->getContents();
            $data = json_decode($data, true);
            if(!empty($data)) {
                return ['status'=> true, 'data' => $data];
            }else {
                return ['status'=> false];
            }
        }else {
            return ['status'=> false];

        }

    }
    /* $order : Thông tin đơn hàng
        Trả về phí theo loại hình vận chuyển
    */
    public function getPrice($order) {
        $get_token = $this->getToken();
        $token = $get_token['token'];
        $client = new Client();
        $response = $client->request('POST', 'https://partner.viettelpost.vn/v2/order/getPrice', [
            'headers' => [
                'Authorization' => $token,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ],
            'body'    => json_encode($order)
        ]);
        if ($response->getStatusCode() == 200 && $response->getReasonPhrase() == 'OK') {
            $data = $response->getBody()->getContents();
            $data = json_decode($data);
            if(!empty($data)) {
                return ['status'=> true, 'data'=> $data];
            }else {
                return ['status'=> false];
            }
        }else {
            return ['status'=> false];

        }
    }

    /*
        putOrderVTP : đăng đơn lên hệ thống VTP
        // $order_id: id đơn hàng
    
    */
    public function putOrderVTP($order_id) {
        if(!$order_id){
            return ['status'=>false, 'message'=> 'Đơn hàng không tồn tại!'];
        }
        DB::beginTransaction();
        try {
            $order = Order::with('OrderDetail', 'customer')->where('id', $order_id)->first();
            $sender = ViettelPostStore::where('active', 1)->first();

            $customer = $order->customer;
            $province = $customer->province_id ?? 0;
            $district = $customer->district_id  ?? 0;
            $ward     = $customer->ward_id ?? 0;

            $total_weight = 0;
            $products = [];
            $qty = 0;
            $product_name = '';
            foreach ( $order->OrderDetail as $item_id => $item_data ) {
                $name = $item_data->product->name;
                $variant_text = json_decode($item_data->variant_text, 1);
                if(isset($variant_text['attibute'])){
                    foreach($variant_text['attibute'] as $v){
                        $name.= ' - '.$v;
                    }
                }
                $weight = $item_data->weight;
                $total_weight += $weight*$item_data->quantity;
                $qty += $item_data->quantity;
                $product_name .= $name.';';
                $products[] = array(
                    'PRODUCT_NAME'     => $name,
                    'PRODUCT_WEIGHT'   => $weight,
                    'PRODUCT_QUANTITY' => $item_data->quantity,
                    'PRODUCT_PRICE' => $item_data->price,
                );
            }
            $pick_money = $order->shiping_fee + $order->total_price??0;
            $oder_payment = 3; // Thu hộ tiền hàng
            if($order->payment_status == 1) {
                $pick_money = 0; // TH đơn đã thanh toán
                $oder_payment = 1; // Không thu hộ
            }

            $data_order = [
                "ORDER_NUMBER"=> getOrderCode($order->id),
                "GROUPADDRESS_ID"=> $sender->group_address_id,
                "CUS_ID"=> $sender->cus_id,
                "DELIVERY_DATE"=> date('d/m/Y H:i:s', strtotime($order->created_at)),
                "SENDER_FULLNAME"=> $sender->name,
                "SENDER_ADDRESS"=> $sender->address,
                "SENDER_PHONE"=> $sender->phone,
                "SENDER_EMAIL"=> $sender->email ?? 'info@haledco.com',
                "SENDER_WARD"=> $sender->ward_id,
                "SENDER_DISTRICT"=> $sender->district_id,
                "SENDER_PROVINCE"=> $sender->province_id,
                "SENDER_LATITUDE"=> 0,
                "SENDER_LONGITUDE"=> 0,
                "RECEIVER_FULLNAME"=> $customer->name ?? '',
                "RECEIVER_ADDRESS"=> $customer->address ?? '',
                "RECEIVER_PHONE"=> $customer->phone ?? "(024) 3568 6969",
                "RECEIVER_EMAIL"=> $customer->email ?? 'info@haledco.com',
                "RECEIVER_WARD"=> $ward,
                "RECEIVER_DISTRICT"=> $district,
                "RECEIVER_PROVINCE"=> $province,
                "RECEIVER_LATITUDE"=> 0,
                "RECEIVER_LONGITUDE"=> 0,
                "PRODUCT_NAME"  => $product_name,
                "PRODUCT_QUANTITY"=> $qty,
                "PRODUCT_PRICE"=> $pick_money,
                "PRODUCT_WEIGHT"=> $total_weight,
                "PRODUCT_TYPE"=> "HH",
                "ORDER_PAYMENT"=> $oder_payment,
                "ORDER_SERVICE"=> $order->shipping_method ?? 'LCOD',
                "ORDER_SERVICE_ADD"=> "",
                "ORDER_VOUCHER"=> "",
                "ORDER_NOTE"=> $order->note,
                "MONEY_COLLECTION"=> $pick_money,
                "MONEY_TOTALFEE"=> 0,
                "MONEY_FEECOD"=> 0,
                "MONEY_FEEVAS"=> 0,
                "MONEY_FEEINSURRANCE"=> 0,
                "MONEY_FEE"=> 0,
                "MONEY_FEEOTHER"=> 0,
                "MONEY_TOTALVAT"=> 0,
                "MONEY_TOTAL"=> 0,
                "LIST_ITEM"=> $products
            ];
            $get_token = $this->getToken();
            $token = $get_token['token'];
            $client = new Client();
            $response = $client->request('POST', 'https://partner.viettelpost.vn/v2/order/createOrder', [
                'headers' => [
                    'token' => $token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'body'    => json_encode($data_order)
            ]);
            if ($response->getStatusCode() == 200 && $response->getReasonPhrase() == 'OK') {
                $data = $response->getBody()->getContents();
                $data = json_decode($data);

                if($data->status == 200 && !empty($data->data)) {
                    // ghi lịch sử đăng đơn
                    OrderHistory::add($order_id, 'put_vtp');
                    // mặc định khi chuyển đơn cho vtp thì đơn trên hệ thống vẫn là đã tiếp nhận, các trạng thái chi tiết đơn sẽ là trạng thái của VTP
                    $result = $data->data;
                    $order = $order->update([
                        'viettelpot_id' => $result->ORDER_NUMBER,
                    ]);
                    DB::commit();
                    return ['status'=> true, 'message' => 'Đăng đơn thành công!'];
                }else {
                    DB::rollback();
                    return ['status'=> false];
                }
            }else {
                DB::rollback();
                return ['status'=> false];
            }
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('put order VTP error '.$e->getMessage());
            return ['status'=> false];
        }
    }

    public function cancelOrder($vtp_id) {
        if(!$vtp_id){
            return ['status'=>false, 'message'=> 'Đơn hàng không tồn tại!'];
        }
        DB::beginTransaction();
        try {
            $data_order = array(
                "TYPE" => 4,
                "ORDER_NUMBER" => $vtp_id,
                "NOTE" => "Hủy đơn"
            );
            $get_token = $this->getToken();
            $token = $get_token['token'];
            $client = new Client();
            $response = $client->request('POST', 'https://partner.viettelpost.vn/v2/order/UpdateOrder', [
                'headers' => [
                    'token' => $token,
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json'
                ],
                'body'    => json_encode($data_order)
            ]);
            if ($response->getStatusCode() == 200 && $response->getReasonPhrase() == 'OK') {
                $data = $response->getBody()->getContents();
                $data = json_decode($data);
                if($data->status == 200 && $data->error == false) {
                    $order = Order::where('viettelpot_id', $vtp_id)->first();
                    $order_dt = $order;
                    $order_id = $order_dt->id;
                    $order = $order->update(['status' => 3]);
                    OrderHistory::add($order_dt->id, 'request_cancel', $data->message ?? 'Hủy đơn hàng thành công!');
                    DB::commit();
                    return [
                        'status' => true,
                        'message' => $data->message ?? 'Hủy đơn hàng thành công!'
                    ];
                }else {
                    DB::rollback();
                    return [
                        'status' => false,
                        'message' => $data->message ?? ''
                    ]; 
                }
            }else {
                DB::rollback();
                return [
                    'status' => false
                ]; 
            }
        } catch (\Exception $e) {
            DB::rollback();
            \Log::error('cancel order in VTP faild '.$e->getMessage());
            return [
                'status' => false
            ]; 
        }
    }

    public function webhook(Request $request) {
        try {
            $data = $request->all() ?? [];
            $data_order = $data['DATA'] ?? [];
            if(isset($data_order['ORDER_REFERENCE'])) {
                $order_id = getOrderDecode($data_order['ORDER_REFERENCE'] ?? 0);
                $order_list = Order::query()->pluck('id', 'id')->toArray();
                if($order_id && in_array($order_id, $order_list)) {
                    $order_stt = $data_order['ORDER_STATUS'] ?? 0;
                    if(in_array($order_stt, [107, 201])) {
                        Order::where('id', $order_id)->update(['status' => 3]);
                    }
                    if($order_stt == 501) {
                        Order::where('id', $order_id)->update(['status' => 4]);
                    }
                    OrderHistory::add($order_id, 'change_status', $data_order);
                }
            }
            return 1;
        } catch (\Exception $e) {
            \Log::error('webhook error '.$e->getMessage());
            return 0;
        }
    }
}