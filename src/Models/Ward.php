<?php

namespace Sudo\ViettelPost\Models;
use Sudo\Base\Models\BaseModel;

class Ward extends BaseModel
{

    public function district() {
    	return $this->belongsTo('Sudo\ViettelPost\Models\District', 'district_id', 'id');
    }

}