<?php
namespace App\Tools\Controllers\Api\v1;

use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\User\User;
use Illuminate\Http\Request;
use App\Tools\Modules\Service\Coupon\CouponModel\{
    CouponModelCreate , CouponModelDetail , CouponModelTestStart 
    , CouponModelTestStop , CouponModelList , CouponModelRemove
    , CouponModelPublish , CouponModelUnPublish };
use App\Tools\Modules\Service\Coupon\CouponUser\{CouponUserGetCode , CouponUserImport};
use App\Lib\Tool\Tool;

/**
 * 优惠券后台控制器
 * 各action方法中注入相关服务service
 * 后期优化路线:注册服务容器，绑定interface，功能替换时,可直接切换实现接口的新service
 * @author gaobo
 */
class CouponBackendController
{
    public function __construct(){
    }

    /**
     * 创建优惠券模板
     * @param Request $request
     * @param CouponModelCreate $CouponModelCreate
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest ?
     */
    public function create(Request $request , CouponModelCreate $CouponModelCreate)
    {
        $request = $request->all();
        $params  = $request['params'];
        $model = $CouponModelCreate->execute($params);
        return apiResponse($model,get_code(),get_msg());
    }
    
    /**
     * 获取优惠券模板详情
     * @param Request $request $params['model_no']
     * @param CouponModelDetail $CouponModelDetail
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest ?
     */
    public function detail(Request $request , CouponModelDetail $CouponModelDetail){
        $request = $request->all();
        $params  = $request['params'];
        $CouponModelDetail = $CouponModelDetail->execute($params['model_no']);
        return apiResponse($CouponModelDetail,get_code(),get_msg());
    }
    
    /**
     * 优惠券模型发布
     * @param Request $request
     * @param CouponModelPublish $CouponModelPublish
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest ?
     */
    public function publish(Request $request , CouponModelPublish $CouponModelPublish)
    {
        $request = $request->all();
        $params  = $request['params'];
        $CouponModelPublish = $CouponModelPublish->execute($params['model_no']);
        return apiResponse($CouponModelPublish,get_code(),get_msg());
    }
    
    /**
     * 优惠券模型停止发布
     * @param Request $request
     * @param CouponModelUnPublish $CouponModelUnPublish
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest ?
     */
    public function unPublish(Request $request , CouponModelUnPublish $CouponModelUnPublish)
    {
        $request = $request->all();
        $params  = $request['params'];
        $model = $CouponModelUnPublish->execute($params['model_no']);
        return apiResponse($model,get_code(),get_msg());
    }
    
    /**
     * 删除优惠券模型草稿
     * @param Request $request
     * @param CouponModelRemove $CouponModelRemove
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest ?
     */
    public function remove(Request $request , CouponModelRemove $CouponModelRemove)
    {
        $request = $request->all();
        $params  = $request['params'];
        $model = $CouponModelRemove->execute($params['model_no']);
        return apiResponse($model,get_code(),get_msg());
    }
    
    /**
     * 优惠券灰度发布
     * @param Request $request
     * @param CouponModelTestStart $CouponModelTestStart
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest ?
     */
    public function greyTest(Request $request , CouponModelTestStart $CouponModelTestStart)
    {
        $request = $request->all();
        $params  = $request['params'];
        $model = $CouponModelTestStart->execute($params['model_no'] , $params['mobile']);
        return apiResponse($model,get_code(),get_msg());
    }
    
    /**
     * 优惠券取消灰度发布
     * @param Request $request
     * @param CouponModelTestStop $CouponModelTestStop
     * @return \Illuminate\Http\JsonResponse
     * @localtest ?
     * @devtest ?
     */
    public function cancelGreyTest(Request $request , CouponModelTestStop $CouponModelTestStop)
    {
        $request = $request->all();
        $params  = $request['params'];
        $model = $CouponModelTestStop->execute($params['model_no'] , $params['mobile']);
        return apiResponse($model,get_code(),get_msg());
    }
    
    /**
     * 优惠券模型列表
     * @param Request $request
     * @param CouponModelList $CouponModelList
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest ?
     */
    public function list(Request $request , CouponModelList $CouponModelList)
    {
        $request = $request->all();
        $model = $CouponModelList->execute($request['params']);
        return apiResponse($model,get_code(),get_msg());
    }
    
    /**
     * 获取优惠券兑换码
     * @param Request $request
     * @param CouponUserGetCode $CouponUserGetCode
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest ?
     */
    public function getCode(Request $request , CouponUserGetCode $CouponUserGetCode)
    {
        $request = $request->all();
        $params  = $request['params'];
        $CouponUserGetCode->execute($params['model_no'] , $params['num']);
        return apiResponse([],get_code(),get_msg());
    }
    
    /**
     * 用户导入优惠券
     * @param Request $request
     * @param CouponUserImport $CouponUserImport
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest ?
     */
    public function importUser(Request $request , CouponUserImport $CouponUserImport)
    {
        $request = $request->all();
        $tempFile = $_FILES['file_data']['tmp_name'];
        $tempFile = $request['params']['tmp_name'];
        $params  = $request['params'];
        $model = $CouponUserImport->execute($params['model_no'] , $tempFile);
        return apiResponse($model,get_code(),get_msg());
    }

}
