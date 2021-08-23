<?php

namespace Sudo\ViettelPost\Models;
use Sudo\Base\Models\BaseModel;

class District extends BaseModel
{

    public function province() {
    	return $this->belongsTo('Sudo\ViettelPost\Models\Province', 'province_id', 'id');
    }

    public function ward() {
    	return $this->hasMany('Sudo\ViettelPost\Models\Ward', 'district_id', 'id');
    }

}