<?php

namespace Sudo\ViettelPost\Models;

use Sudo\Base\Models\BaseModel;

class ViettelPostStore extends BaseModel
{
	protected $table = 'viettelpost_stores';
	protected $fillable = [
        'name', 'phone', 'email', 'address', 'group_address_id', 'cus_id', 'province_id', 'district_id', 'ward_id', 'active', 'status',
    ];

    public function province() {
    	return $this->belongsTo('Sudo\Theme\Models\Province', 'province_id', 'id');
    }
    public function district() {
    	return $this->belongsTo('Sudo\Theme\Models\District', 'district_id', 'id');
    }
    public function ward() {
    	return $this->belongsTo('Sudo\Theme\Models\Ward', 'ward_id', 'id');
    }

    public function getAddress() {
        $address = $this->address . ' - ' . getWard($this->ward_id ?? 0). ' - ' . getDistrict($this->district_id ?? 0) . ' - ' . getProvince($this->province_id ?? 0);
         return $address;
    }
}
