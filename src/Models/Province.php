<?php

namespace Sudo\ViettelPost\Models;
use Sudo\Base\Models\BaseModel;

class Province extends BaseModel
{

    public function district() {
    	return $this->hasMany('Sudo\ViettelPost\Models\District', 'province_id', 'id');
    }

}