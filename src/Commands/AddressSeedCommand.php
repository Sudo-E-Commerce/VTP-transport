<?php

namespace Sudo\ViettelPost\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;
use DB;
use GuzzleHttp\Client;

class AddressSeedCommand extends Command {

    protected $signature = 'sudo/vtpaddress:seeds {name}';

    protected $description = 'Lấy dữ liệu địa chỉ VTP';

    public function handle() {
        $name = $this->argument('name');
        if($name && $name == 'provinces') {
            DB::table('provinces')->truncate();
            $client = new Client();
            $response = $client->request('GET', 'https://partner.viettelpost.vn/v2/categories/listProvinceById?provinceId=-1');
            $response = $response->getBody()->getContents();
            $response = json_decode($response, true);
            $province = [];
            foreach($response['data'] as $res) {
                $province[] = [
                    'id' => $res['PROVINCE_ID'],
                    'code' => $res['PROVINCE_CODE'],
                    'name' => $res['PROVINCE_NAME'],
                ];
            }
            DB::table('provinces')->insert($province);
            $this->echoLog('Tinh thanh. So luong: '.count($province));
        }
        if($name && $name == 'districts') {
            DB::table('districts')->truncate();
            $client = new Client();
            $response = $client->request('GET', 'https://partner.viettelpost.vn/v2/categories/listDistrict?provinceId=-1');
            $response = $response->getBody()->getContents();
            $response = json_decode($response, true);
            $district = [];
            foreach($response['data'] as $res) {
                $district[] = [
                    'id' => $res['DISTRICT_ID'],
                    'code' => $res['DISTRICT_VALUE'],
                    'name' => $res['DISTRICT_NAME'],
                    'province_id' => $res['PROVINCE_ID'],
                ];
            }
            DB::table('districts')->insert($district);
            $this->echoLog('Tinh thanh. So luong: '.count($district));
        }
        if($name && $name == 'wards') {
            DB::table('wards')->truncate();
            $client = new Client();
            $response = $client->request('GET', 'https://partner.viettelpost.vn/v2/categories/listWards?districtId=-1');
            $response = $response->getBody()->getContents();
            $response = json_decode($response, true);
            $ward = [];
            foreach($response['data'] as $res) {
                $ward[] = [
                    'id' => $res['WARDS_ID'],
                    'name' => $res['WARDS_NAME'],
                    'district_id' => $res['DISTRICT_ID'],
                ];
            }
            DB::table('wards')->insert($ward);
            $this->echoLog('Tinh thanh. So luong: '.count($ward));
        }
    }

    public function echoLog($string) {
        $this->info($string);
        Log::info($string);
    }

}