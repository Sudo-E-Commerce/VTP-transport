<div class="mb-3  row " style="position: relative;">
    <label for="address" class="col-md-2 col-form-label"> *  Tỉnh thành</label>
	<div class="col-md-10">
		<select  name="province_id" id="province_id" class="form-control"
	onchange="loadDestination('#province_id', '{{ route('admin.vtpstores.loadAdress') }}', '#district_id', 'Tỉnh thành là bắt buộc', 'province');">
			<option value="">{{ __('Tỉnh thành') }}</option>
			@foreach ($provinces as $pr)
				<option value="{{ $pr->id }}" {{ !empty($data_edit) && $data_edit->province_id == $pr->id ? 'selected="selected"' : '' }}>{{ $pr->name }}</option>
			@endforeach
		</select>
		<span class="err_show err null">{{ __('Tỉnh thành là bắt buộc!') }}!</span>
	</div>
</div>
<div class="mb-3  row " style="position: relative;">
    <label for="address" class="col-md-2 col-form-label"> * Quận huyện</label>
	<div class="col-md-10">
		<select name="district_id" id="district_id" class="form-control"
	onchange="loadDestination('#district_id', '{{ route('admin.vtpstores.loadAdress') }}', '#list_ward', 'Quận huyện là bắt buộc', 'district');">
			<option value="">{{ __('Quận/ Huyện') }}</option>
			@foreach ($districts as $dt)
				@if(!empty($data_edit) && $data_edit->district_id == $dt->id)
					<option value="{{ $dt->id }}" selected="selected" >{{ $dt->name }}</option>
				@else
					<option value="{{ $dt->id }}">{{ $dt->name }}</option>
				@endif
			@endforeach
		</select>
		<span class="err_show err null">{{ __('Quận huyện là bắt buộc!') }}!</span>
	</div>
</div>

<div class="mb-3  row " style="position: relative;">
    <label for="address" class="col-md-2 col-form-label"> *  Phường xã</label>
	<div class="col-md-10">
		<select name="ward_id" id="list_ward" class="form-control">
	    	<option value="">{{ __('Phường xã') }}</option>
			@foreach ($wards as $wd)
				<option value="{{ $wd->id }}" {{ !empty($data_edit) && $data_edit->ward_id == $wd->id ? 'selected="selected"' : '' }}>{{ $wd->name }}</option>
			@endforeach
	    </select>
	    <span class="err_show err null">{{ __('Phường xã là bắt buộc!') }}!</span>
	</div>
</div>
<style>
	.err_show {
		display: none;
	}
</style>
<script>
	$(document).ready(function(){
		validateSelect('#province_id', 'Tỉnh thành là bắt buộc!');
		validateSelect('#district_id', 'Quận huyện là bắt buộc!');
		validateSelect('#list_ward', 'Phường xã là bắt buộc!');
	});
	$.ajaxSetup({
	    headers: {
	        'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
	    }
	});
	function loadDestination(select, url, result, message, type) {
	    loadingBox('open');
	    var id = $(select).val();
	    if(id == '') {
	        loadingBox('close');
	        alertText('error',message);
	    } else {
	        $.ajax({
	            type: 'POST',
	            cache: false,
	            url: url,
	            data: {
	                'id': id,
	                'type' : type
	            },
	            success: function(data){
	                $(result).empty();
	                $(result).html(data);
	                loadingBox('close');
	            },
	            error: function(data) {
	                loadingBox('close');
	                alertText('error', $('#loading_box').data('error'));
	            }
	        });
	    }
	}
</script>