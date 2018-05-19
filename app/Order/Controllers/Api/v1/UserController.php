<?php
/**
 *  用户认证处理接口
 * Author: wutiantang
 * Email :wutiantang@huishoubao.com.cn
 * Date: 2018/5/2 0018
 * Time: 下午 2:18
 */

namespace App\Order\Controllers\Api\v1;
use App\User;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Exceptions\JWTException;
use Auth;
use App\Order\Transformers\UserTransformer;
class UserController extends Controller
{
    protected $guard = 'api';


    /**
     * 获取token
     * Author: heaven
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function token(Request $request)
    {


        $credentials=[
            'email' => $request->email,
            'password'  => $request->password,
        ];

        try {
            if (! $token = Auth::guard($this->guard)->attempt($credentials)) {
                return response()->json(['error' => 'invalid_credentials'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['error' => 'could_not_create_token'], 500);
        }
        return response()->json(compact('token'));
    }


    /**
     * 刷新token
     * Author: heaven
     * @return mixed
     */
    public function refershToken()
    {
        $token = Auth::guard($this->guard)->refresh();
        return $this->response->array(compact('token'));
    }

    /**
     * 获取个人信息
     * Author: heaven
     * @return mixed
     */
    public function me()
    {
        return $this->response->item($this->user(), new UserTransformer());
//        return response()->json($this->guard()->user());
        //return Auth::guard('api')->user();
    }


    /**
     * 退出
     * Author: heaven
     * @return \Illuminate\Http\JsonResponse
     */
    public function logout()
    {
        Auth::guard($this->guard)->logout();
        return response()->json(['status' => 'ok']);
    }
}
