<?php

namespace App\ClientApi\Controllers;

use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Routing\Controller as BaseController;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;
	
	public function __construct() {
		$params = request()->all();
		if( isset($params) ){
			\App\Lib\Common\LogApi::setSource($params['method']);
		}
	}
	
}
