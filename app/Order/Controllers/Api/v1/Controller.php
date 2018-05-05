<?php
namespace App\Order\Controllers\Api\v1;

use Illuminate\Http\Request;
use Dingo\Api\Routing\Helpers;
use App\Http\Controllers\Controller as BaseController;

class Controller extends BaseController
{
    use Helpers;
    protected function apiResponse($code = 0, $message = '', $data = [])
    {
        return response()->json(array_merge([
            'code'    => $code,
            'status'  => $code == 0 ? 'success' : 'error',
            'message' => $message,
        ], $data));
    }
}