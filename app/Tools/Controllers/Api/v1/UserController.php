<?php
/**
 *  用户认证处理接口
 * Author: wutiantang
 * Email :wutiantang@huishoubao.com.cn
 * Date: 2018/5/2 0018
 * Time: 下午 2:18
 */

namespace App\Order\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Order\Models\Order;
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

    /*
    * 查询用户手机号信息
    * @param array $params 【必选】
    * [
    *      "mobile"=>"",用户手机号
    * ]
    * @return json
    */
    public function getUserInfo(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        //过滤参数
        $rule= [
            'mobile'=>'required',
        ];
        $validator = app('validator')->make($params, $rule);
        if ($validator->fails()) {
            return apiResponse([],ApiStatus::CODE_20001,$validator->errors()->first());
        }
        $userInfo = \App\Lib\User\User::getUserInfo(['mobile'=>$params['mobile']]);
        if(!$userInfo){
            return apiResponse([],ApiStatus::CODE_50001,"未找到该用户");
        }
        $orderInfo = Order::query()->where(['user_id'=>$userInfo['id'],'order_status'=>6])->first();
        if($orderInfo){
            $userInfo['orderInfo'] = $orderInfo->toArray();
        }
        $userInfo['orderInfo'] = [];
        return apiResponse($userInfo,ApiStatus::CODE_0);
    }

    /*
    * 修改用户手机号
    * @param array $params 【必选】
    * [
    *      "user_id"=>"",用户id
    *      "username"=>"",用户名（当前手机号）
    *      "mobile"=>"",新手机号
    * ]
    * @return json
    */
    public function setMobile(Request $request){
        $orders =$request->all();
        $params = $orders['params'];
        //过滤参数
        $rule= [
            'user_id'=>'required',
            'username'=>'required',
            'mobile'=>'required',
        ];
        $validator = app('validator')->make($params, $rule);
        if ($validator->fails()) {
            return apiResponse([],ApiStatus::CODE_20001,$validator->errors()->first());
        }
        $userInfo = \App\Lib\User\User::getUserInfo(['user_id'=>$params['user_id'],'mobile'=>$params['username']]);
        if(!$userInfo){
            return apiResponse([],ApiStatus::CODE_50001,"未找到该用户");
        }
        $ret = \App\Lib\User\User::setUserName(['user_id'=>$userInfo['id'],'mobile'=>$params['mobile']]);
        if(!$ret){
            return apiResponse([],ApiStatus::CODE_50000,"更换手机号失败");
        }
        $data = [
            'mobile'=>$params['mobile']
        ];
        Order::where(['user_id'=>$userInfo['id']])->update($data);
        return apiResponse($userInfo,ApiStatus::CODE_0);
    }
}
