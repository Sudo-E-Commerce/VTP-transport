@include('Table::components.text',['text' => $value->name])
@include('Table::components.text',['text' => $value->phone])
@include('Table::components.text',['text' => $value->getAddress()])
<td style="text-align: center;">
	@if($value->active == 1)
		<span class="btn btn-primary">Mặc định</span>
	@else
		<span class="change btn btn-danger" data-id="{{ $value->id }}">Đặt làm mặc định</span>
	@endif
</td>
@if($value->id == 1)
	<script>
		$.ajaxSetup({
		    headers: {
		        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
		    }
		});
		$('body').on('click', 'span.change.btn', function(e){
			if(confirm('Bạn có chắc chắn muốn nhận địa chỉ này làm địa chỉ kho mặc định?')) {
				var id = parseInt($(this).data('id'));
				var url = '{{ route('admin.vtpstores.default') }}';
				loadingBox('open');
				$.ajax({
		            type: 'POST',
		            cache: false,
		            url: url,
		            data: {
		                'id': id
		            },
		            success: function(data){
		                loadingBox('close');
		                if(data.status == 1) {
		                	window.location.href = '{{ route('admin.viettelpost_stores.index') }}';
		                } else {
		                	alertText('Có lỗi xảy ra, vui lòng tải lại trang và thử lại!', 'error');
		                }
		            },
		            error: function(data) {
		                loadingBox('close');
		                alertText('error', 'Có lỗi xảy ra vui lòng thử lại');
		            }
		        });
			}
		});
	</script>
@endif