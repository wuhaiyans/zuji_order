<?php
namespace App\Tools\Controllers\Api\v1;

use Illuminate\Http\Request;
use App\Tools\Modules\Service\Coupon\CouponSpu\{ CouponList , CouponListWhenOrder , CouponListWhenPay };
use App\Tools\Modules\Service\Coupon\CouponUser\{ 
    CouponUserList , CouponUserDetail , CouponUserExchange 
    , CouponUserReceive , CouponUserWriteOff , CouponUserCancel};
use App\Tools\Modules\Inc\CouponStatus;
use App\Tools\Modules\Service\GreyTest\GreyTestGetByMobile;
                            
/**
 * 优惠券后台控制器
 * 各action方法中注入相关服务service
 * 后期优化路线:注册服务容器，绑定interface，功能替换时可直接切换实现接口的新service
 * @author gaobo
 */
class CouponFrontendController
{
    protected $couponModelStatus = CouponStatus::CouponTypeStatusIssue;
    protected $request = [];
    protected $mobile = null;
    //用户类型-针对营销工具系统
    protected $userType = CouponStatus::RangeUserScope;//全体
    public function __construct(Request $request , GreyTestGetByMobile $GreyTestGetByMobile)
    {
        //获取用户是否是灰度测试用户
        $this->request = $request->all();
        if(isset($this->request['userinfo']['username'])){
            $this->mobile = $this->request['userinfo']['username'];
            $testMobile = $GreyTestGetByMobile->execute($this->request['userinfo']['username']);
            if($testMobile){
                $this->couponModelStatus = CouponStatus::CouponTypeStatusTest;
            }
        }else{
            $this->userType = CouponStatus::RangeUserVisitor; //游客
        }
        //定义当前用户的类型
        if(($this->request['userinfo']['register_time'])){
            //超过24小时为老用户,否则为新人
            $this->userType = ((time() - $this->request['userinfo']['register_time']) > 86400) ? CouponStatus::RangeUserOld : CouponStatus::RangeUserNew;
        }
    }
   
    /**
     * 我的优惠券
     * @param Request $request 
     * @param CouponUserList $CouponUserList
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest OK
     */
    public function couponUserList(CouponUserList $CouponUserList)
    {
        $params  = $this->request['params'];
        $params['mobile']  = $this->mobile;
        $CouponUserList = $CouponUserList->execute($params , $this->couponModelStatus);
        return apiResponse($CouponUserList,get_code(),get_msg());
    }
    
    /**
     * 商品优惠券列表
     * @param Request $request
     * @param CouponList $CouponList
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest OK
     */
    public function spuCouponList(CouponList $CouponList)
    {
        $params  = $this->request['params'];
        $params['range_user'] = $this->userType;
        $params['mobile']  = $this->mobile;
        $CouponList = $CouponList->execute($params , $this->couponModelStatus);
        return apiResponse($CouponList,get_code(),get_msg());
    }
    
    /**
     * 确认订单时的优惠券列表
     * @param Request $request
     * @param CouponListWhenOrder $CouponListWhenOrder
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest ?
     */
    public function couponListWhenOrder(CouponListWhenOrder $CouponListWhenOrder)
    {
        $params  = $this->request['params'];
        $params['mobile']  = $this->mobile;
        $CouponListWhenOrder = $CouponListWhenOrder->execute($params , $this->couponModelStatus);
        return apiResponse($CouponListWhenOrder,get_code(),get_msg());
    }
    
    /**
     * 支付时的优惠券列表
     * @param Request $request
     * @param CouponListWhenPay $CouponListWhenPay
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest ?
     */
    public function couponListWhenPay(CouponListWhenPay $CouponListWhenPay)
    {
        $params  = $this->request['params'];
        $params['mobile']  = $this->mobile;
        $CouponListWhenPay = $CouponListWhenPay->execute($params , $this->couponModelStatus);
        return apiResponse($CouponListWhenPay,get_code(),get_msg());
    }
    
    /**
     * 用户使用兑换码兑换优惠券
     * @param Request $request
     * @param CouponUserExchange $CouponUserExchange
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest OK
     */
    public function couponUserExchange(CouponUserExchange $CouponUserExchange)
    {
        $params  = $this->request['params'];
        $CouponUserExchange = $CouponUserExchange->execute($this->mobile , $params['coupon_no'] , $this->couponModelStatus);
        return apiResponse($CouponUserExchange,get_code(),get_msg());
    }
    
    /**
     * 用户领取优惠券
     * @param Request $request
     * @param CouponUserReceive $CouponUserReceive
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest OK
     */
    public function couponUserReceive(CouponUserReceive $CouponUserReceive)
    {
        $params  = $this->request['params'];
        $CouponUserReceive = $CouponUserReceive->execute($params['model_no'] , $this->mobile , $this->couponModelStatus);
        return apiResponse($CouponUserReceive,get_code(),get_msg());
    }
    
    /**
     * 核销优惠券
     * @param Request $request
     * @param CouponUserWriteOff $CouponUserWriteOff
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest OK
     */
    public function couponUserWriteOff(CouponUserWriteOff $CouponUserWriteOff)
    {
        $params  = $this->request['params'];
        $CouponUserWriteOff = $CouponUserWriteOff->execute($params['id'] , $this->mobile , $this->couponModelStatus);
        return apiResponse($CouponUserWriteOff,get_code(),get_msg());
    }
    
    /**
     * 撤销优惠券的使用
     * @param Request $request
     * @param CouponUserCancel $CouponUserCancel
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest OK
     */
    public function couponUserCancel(CouponUserCancel $CouponUserCancel)
    {
        $params  = $this->request['params'];
        $CouponUserCancel = $CouponUserCancel->execute($params['id'] , $this->mobile , $this->couponModelStatus);
        return apiResponse($CouponUserCancel,get_code(),get_msg());
    }
    
    /**
     * 用户优惠券详情
     * @param Request $request
     * @param CouponUserDetail $CouponUserDetail
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest OK
     */
    public function couponUserDetail(CouponUserDetail $CouponUserDetail)
    {
        $params  = $this->request['params'];
        $CouponUserDetail = $CouponUserDetail->execute($params['id'] , $this->couponModelStatus)->toArray();
        return apiResponse($CouponUserDetail,get_code(),get_msg());
    }
    
}