<?php

namespace Sudo\ViettelPost\Http\Controllers;
use Sudo\Base\Http\Controllers\AdminController;

use Illuminate\Http\Request;
use ListData;
use Form;
use ListCategory;
use DB;
use Auth;
use Sudo\ViettelPost\Models\Province;
use Sudo\ViettelPost\Models\District;
use Sudo\ViettelPost\Models\Ward;
use Sudo\ViettelPost\Http\Controllers\ViettelPostController;
use Sudo\ViettelPost\Models\ViettelPostStore;

class ViettelPostStroreController extends AdminController
{
    function __construct() {
        $this->models = new \Sudo\ViettelPost\Models\ViettelPostStore;
        $this->table_name = $this->models->getTable();
        $this->module_name = 'Kho vận';
        $this->has_seo = false;
        $this->has_locale = false;
        $this->provinces = Province::all();

        parent::__construct();
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $requests) {
        $listdata = new ListData($requests, $this->models, 'ViettelPost::table', $this->has_locale);

        // Build Form tìm kiếm
        $listdata->search('name', 'Tên', 'string');
        $listdata->search('phone', 'Điện thoại', 'string');
        $listdata->search('address', 'Địa chỉ', 'string');
        $listdata->search('status', 'Trạng thái', 'array', config('app.status'));
        // Build các hành động
        $listdata->action('status');
        $listdata->no_paginate();
        $listdata->no_trash();
        
        // Build bảng
        $listdata->add('name', 'Tên', 0);
        $listdata->add('phone', 'Điện thoại', 0);
        $listdata->add('address', 'Địa chỉ', 0);
        $listdata->add('', 'Mặc định', 0);
        $listdata->add('', 'Thời gian', 0, 'time');
        $listdata->add('status', 'Trạng thái', 0, 'status');
        $listdata->add('', 'Language', 0, 'lang');
        // Trả về views
        return $listdata->render();
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {   
        // Khởi tạo form
        $form = new Form;

        $form->lang($this->table_name, true);
        $form->note("Lưu ý: khi tạo kho vận ở đây sẽ tạo luôn 1 kho vận mới với thông tin vừa nhập, khi người dùng đặt hàng, phí vận chuyển cũng sẽ được tính theo địa chỉ kho vừa nhập và khi tạo vận đơn ở Viettel post cũng vậy. Sẽ có lỗi xảy ra nếu như khi người dùng đặt hàng phí vận chuyển được tính ở kho A, nhưng khi tạo vận đơn kho lại được lấy ở kho B.");
        $form->text('name', '', 1, 'Tên', '', true);
        $form->text('phone', '', 1, 'Điện thoại', '', true);
        $form->text('email', '', 1, 'Email', '', true);
        $form->text('address', '', 1, 'Địa chỉ', '', true);
        $form->custom('ViettelPost::select', ['provinces'=>$this->provinces, 'districts' => [], 'wards' => []]);
        $form->checkbox('active', 1, 1, 'Kho mặc định');
        $form->checkbox('status', 1, 1, 'Trạng thái');
        $form->action('add');
        // Hiển thị form tại view

        return $form->render('create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $requests)
    {
        // Xử lý validate
        validateForm($requests, 'name', 'Tên không được để trống.');
        validateForm($requests, 'phone', 'Điện thoại không được để trống.');
        validateForm($requests, 'email', 'Email không được để trống.');
        validateForm($requests, 'address', 'Địa chỉ không được để trống.');
        validateForm($requests, 'province_id', 'Tỉnh thành không được để trống.');
        validateForm($requests, 'district_id', 'Quận huyện không được để trống.');
        validateForm($requests, 'ward_id', 'Phường xã không được để trống.');
        // Các giá trị mặc định
        $status = 0;
        // Đưa mảng về các biến có tên là các key của mảng
        extract($requests->all(), EXTR_OVERWRITE);
        $data = [
        	'PHONE' => $phone,
        	'NAME' => $name,
        	'ADDRESS' => $address,
        	'WARDS_ID' => $ward_id,
        ];
        $vtp = new ViettelPostController();
        $result = $vtp->createInventory($data);
        if($result['status']) {
        	\Log::info('');
        	$inventory = $result['data'][0];
        	$group_address_id = $inventory->groupaddressId ?? 0;
        	$cus_id = $inventory->cusId ?? 0;
        } else {
        	return back()->withErrors('Có lỗi xảy ra khi tạo kho trên ViettelPost');
        }
        // Chuẩn hóa lại dữ liệu
        $created_at = $created_at ?? date('Y-m-d H:i:s');
        $updated_at = $updated_at ?? date('Y-m-d H:i:s');
        
        // Nếu click lưu nháp
        if($redirect == 'save'){
            $status = 0;
            $redirect = 'index';
        }
        $redirect = 'index';
        $active = $active ?? 0;
        // Thêm vào DB
        $compact = compact('name','phone','email', 'address', 'group_address_id', 'cus_id', 'province_id', 'district_id', 'ward_id', 'active','status','created_at','updated_at');
        $id = $this->models->createRecord($requests, $compact, $this->has_seo, $this->has_locale);
        if(isset($active) && $active == 1) {
        	ViettelPostStore::where('id', '<>', $id)->update(['active' => 0]);
        }
        // Điều hướng
        return redirect(route('admin.'.$this->table_name.'.index'))->with([
            'type' => 'success',
            'message' => __('Translate::admin.create_success')
        ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id) {
        return redirect()->route('admin.viettelpost_stores.index')->withErrors('Chức năng khóa!');
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $requests
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $requests, $id) {
        return redirect()->route('admin.viettelpost_stores.index')->withErrors('Chức năng khóa!');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function setAccount(Request $requests) {
    	$setting_name = 'vtp_account';
        $module_name = "Cấu hình tài khoản viettelpost";
        $note = "Translate::form.require_text";

        // Thêm hoặc cập nhật dữ liệu
        $setting = new \Sudo\Theme\Models\Setting();
        if (isset($requests->redirect)) {
            $setting->postData($requests, $setting_name);
        }
        // Lấy dữ liệu ra
        $data = $setting->getData($setting_name);
        // Khởi tạo form
        $form = new Form;
        $form->card('col-lg-12');
            $form->title('Cấu hình tài khoản');
            $form->text('username', $data['username'] ?? '', 1, 'Tên đăng nhập', '', true);
            $form->password('password', $data['password'] ?? '', 1, 'Mật khẩu', '', '', true);
        $form->endCard();
        $form->action('editconfig');
        // Hiển thị form tại view
        return $form->render('custom', compact(
            'module_name', 'note'
        ), 'Default::admin.settings.form');
    }

    // setDefault
    public function setDefault(Request $request) {
    	$id = $request->id ?? 0;
    	if($id) {
    		ViettelPostStore::where('id', '<>', $id)->update(['active' => 0]);
    		ViettelPostStore::where('id', $id)->update(['active' => 1]);
    		return ['status'=> 1];
    	}
    	return ['status' => 0];
    }

    // load for all
    public function loadAdress(Request $requests) {
        $id = $requests->id ?? 0;
        $type = $requests->type ?? 0;
        $html = '';
        switch ($type) {
            case 'province':
                $data = District::where('province_id',$id)->get();
                $html = '<option value="" >'.__('Quận/Huyện').'</option>';
            break;
            case 'district':
                $data = Ward::where('district_id',$id)->get();
                $html = '<option value="" >'.__('Phường/Xã').'</option>';
            break;
            default:
                $data = [];
            break;
        }
        if(count($data) > 0) {
            foreach ($data as $key => $value) { 
                $html .= '<option value='.$value->id.'>'.$value->name.'</option>';
            }
        }
        return $html;
    }
}
