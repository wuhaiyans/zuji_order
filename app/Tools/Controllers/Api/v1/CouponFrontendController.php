<?php
namespace App\Tools\Controllers\Api\v1;

use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\User\User;
use Illuminate\Http\Request;
use App\Tools\Modules\Service\Coupon\CouponSpu\{ CouponList , CouponListWhenOrder , CouponListWhenPay };
use App\Tools\Modules\Service\Coupon\CouponUser\{ 
    CouponUserList , CouponUserDetail , CouponUserExchange 
    , CouponUserReceive , CouponUserWriteOff , CouponUserCancel};
use App\Tools\Modules\Inc\CouponStatus;
use App\Tools\Modules\Service\GreyTest\GreyTestGetByMobile;
use App\Tools\Modules\Func\Func;
use App\Lib\Tool\Tool;
                                                                                    
    /**
     * 优惠券后台控制器
     * 各action方法中注入相关服务service
     * 后期优化路线:注册服务容器，绑定interface，功能替换时可直接切换实现接口的新service
     * @author gaobo
     */
    class CouponFrontendController
    {
        protected $couponModelStatus = CouponStatus::CouponTypeStatusIssue;
        public function __construct(GreyTestGetByMobile $GreyTestGetByMobile)
        {
            $channel = Tool::getChannel(['status'=>1]);
            print_r($channel);exit;
            //获取用户是否是灰度测试用户
            if($this->userInfo['mobile']){
                $testMobile = $GreyTestGetByMobile->execute($this->userInfo['mobile']);
                if($testMobile){
                    $this->couponModelStatus = CouponStatus::CouponTypeStatusTest;
                }
            }
        }
       
        /**
         * 我的优惠券
         * @param Request $request 
         * @param CouponUserList $CouponUserList
         * @return \Illuminate\Http\JsonResponse
         * @localtest OK
         * @devtest ?
         */
        public function couponUserList(Request $request , CouponUserList $CouponUserList)
        {
            $request = $request->all();
            $params  = $request['params'];
            $CouponUserList = $CouponUserList->execute($params , $this->couponModelStatus);
            return apiResponse($CouponUserList,get_code(),get_msg());
        }
        
        /**
         * 商品优惠券列表
         * @param Request $request
         * @param CouponList $CouponList
         * @return \Illuminate\Http\JsonResponse
         * @localtest OK
         * @devtest ?
         */
        public function spuCouponList(Request $request , CouponList $CouponList){
            $request = $request->all();
            $params  = $request['params'];
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
        public function couponListWhenOrder(Request $request , CouponListWhenOrder $CouponListWhenOrder)
        {
            $request = $request->all();
            $params  = $request['params'];
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
        public function couponListWhenPay(Request $request , CouponListWhenPay $CouponListWhenPay)
        {
            $request = $request->all();
            $params  = $request['params'];
            $CouponListWhenPay = $CouponListWhenPay->execute($params , $this->couponModelStatus);
            return apiResponse($CouponListWhenPay,get_code(),get_msg());
        }
        
        /**
         * 用户使用兑换码兑换优惠券
         * @param Request $request
         * @param CouponUserExchange $CouponUserExchange
         * @return \Illuminate\Http\JsonResponse
         * @localtest OK
         * @devtest ?
         */
        public function couponUserExchange(Request $request , CouponUserExchange $CouponUserExchange)
        {
            $request = $request->all();
            $params  = $request['params'];
            $CouponUserExchange = $CouponUserExchange->execute($this->userInfo['mobile'] , $params['coupon_no'] , $this->couponModelStatus);
            return apiResponse($CouponUserExchange,get_code(),get_msg());
        }
        
        /**
         * 用户领取优惠券
         * @param Request $request
         * @param CouponUserReceive $CouponUserReceive
         * @return \Illuminate\Http\JsonResponse
         * @localtest OK
         * @devtest ?
         */
        public function couponUserReceive(Request $request , CouponUserReceive $CouponUserReceive)
        {
            $request = $request->all();
            $params  = $request['params'];
            $CouponUserReceive = $CouponUserReceive->execute($params['model_no'] , $params['mobile'] , $this->couponModelStatus);
            return apiResponse($CouponUserReceive,get_code(),get_msg());
        }
        
        /**
         * 核销优惠券
         * @param Request $request
         * @param CouponUserWriteOff $CouponUserWriteOff
         * @return \Illuminate\Http\JsonResponse
         * @localtest OK
         * @devtest ?
         */
        public function couponUserWriteOff(Request $request , CouponUserWriteOff $CouponUserWriteOff)
        {
            $request = $request->all();
            $params  = $request['params'];
            $CouponUserWriteOff = $CouponUserWriteOff->execute($params['id'] , $params['mobile'] , $this->couponModelStatus);
            return apiResponse($CouponUserWriteOff,get_code(),get_msg());
        }
        
        /**
         * 撤销优惠券的使用
         * @param Request $request
         * @param CouponUserCancel $CouponUserCancel
         * @return \Illuminate\Http\JsonResponse
         * @localtest OK
         * @devtest ?
         */
        public function couponUserCancel(Request $request , CouponUserCancel $CouponUserCancel)
        {
            $request = $request->all();
            $params  = $request['params'];
            $CouponUserCancel = $CouponUserCancel->execute($params['id'] , $params['mobile'] , $this->couponModelStatus);
            return apiResponse($CouponUserCancel,get_code(),get_msg());
        }
        
        /**
         * 用户优惠券详情
         * @param Request $request
         * @param CouponUserDetail $CouponUserDetail
         * @return \Illuminate\Http\JsonResponse
         * @localtest OK
         * @devtest ?
         */
        public function couponUserDetail(Request $request , CouponUserDetail $CouponUserDetail)
        {
            $request = $request->all();
            $params  = $request['params'];
            $CouponUserDetail = $CouponUserDetail->execute($params['id'] , $this->couponModelStatus)->toArray();
            return apiResponse($CouponUserDetail,get_code(),get_msg());
        }
        
        public function test(Request $request)
        {
            $t1 = time();
            $num = 10000;//兼容补发
            $couponCodeArr = [];
            $whileNum = 0;
            while($whileNum < $num){
                $couponCodeArr[] = Func::md5_16();
                $couponCodeArr = array_keys(array_flip($couponCodeArr));
                $whileNum = count($couponCodeArr);
            }
            $t2 = time();
            print_r($couponCodeArr);
            print_r([$t2-$t1]);exit;
        }
    }
    