## Hướng dẫn sử dụng Sudo ViettelPost ##

**Giới thiệu:** Đây là package dùng để quản lý đơn ViettelPost.

### Cài đặt để sử dụng ###

- Package cần phải có base `sudo/core`, `sudo/ecommerce` để có thể hoạt động không gây ra lỗi

### Cách sử dụng ###
- Thêm sudo/packages-viettelpost vào composer.json
- composer update để require
- Cấu hình SudoMenu
	`[
        'name' 		=> 'Kho viettelpost',
        'route' 	=> 'admin.viettelpost_stores.index',
        'role' 		=> 'viettelpost_stores_index',
    ],
    [
        'name' 		=> 'Tài khoản viettelpost',
        'route' 	=> 'admin.viettelpost_stores.setAccount',
        'role' 		=> 'viettelpost_stores_setAccount',
    ],`
- Cấu hình SudoModule
	`'viettelpost_stores' => [
		'name' 			=> 'Kho viettelpost',
		'permision' 	=> [
			[ 'type' => 'index', 'name' => 'Truy cập' ],
			[ 'type' => 'create', 'name' => 'Thêm' ],
			[ 'type' => 'edit', 'name' => 'Sửa' ],
			[ 'type' => 'active', 'name' => 'Cấu hình kho mặc định' ],
			[ 'type' => 'setAccount', 'name' => 'Cấu hình tài khoản' ],
			[ 'type' => 'restore', 'name' => 'Lấy lại' ],
			[ 'type' => 'delete', 'name' => 'Xóa' ],
		],
	],`
- Sau khi composer update để require thành công chạy các lệnh sau
- php artisan migrate để tạo table địa chỉ và thêm các trường để lưu dữ liệu vận chuyển cho đơn hàng
- php artisan sudo/vtpaddress:seeds provinces để lấy dữ liệu địa chỉ tỉnh thành
- php artisan sudo/vtpaddress:seeds districts để lấy dữ liệu địa chỉ quận huyện
- php artisan sudo/vtpaddress:seeds wards để lấy dữ liệu địa chỉ phường xã

#### Giao diện người dùng ####
- use Sudo\ViettelPost\Http\Controllers\ViettelPostController;
- $viettelPost = new ViettelPostController();
- ex: đăng đơn $data = $viettelPost->putOrderVTP($order_id);
- Chi tiết các hàm xử lý xem tại ViettelPostController

### Cấu hình webbhook nhận hành trình vận đơn ##
- Thêm đường dẫn  `/viettelpost/webhook` vào App\Middleware\VerifyCsrfToken

### Lưu ý ##
- Package hỗ trợ đăng đơn, hủy đơn và nhận hành trình vận đơn từ viettelpost
- Riêng phần tính phí vận chuyển package chỉ xử lý dữ liệu và trả về kết quả cho client xử lý để tùy biến theo từng website. xem hàm getPriceAll và getPrice