<?php
namespace App\Tools\Controllers\Api\v1;

use Illuminate\Http\Request;
use App\Tools\Modules\Service\Coupon\CouponModel\{
    CouponModelCreate , CouponModelDetail , CouponModelTestStart 
    , CouponModelTestStop , CouponModelList , CouponModelRemove
    , CouponModelPublish , CouponModelUnPublish };
use App\Tools\Modules\Service\Coupon\CouponUser\{CouponUserGetCode , CouponUserImport};
use App\Tools\Modules\Service\Coupon\CouponModel\CouponModelRePublish;
use App\Tools\Modules\Service\Coupon\CouponModel\CouponModelSearchCondition;

/**
 * 优惠券后台控制器
 * 各action方法中注入相关服务service
 * 后期优化路线:注册服务容器，绑定interface，功能替换时,可直接切换实现接口的新service
 * @author gaobo
 */
class CouponBackendController
{
    protected $request = [];
    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->request = $request->all();
    }

    /**
     * 创建优惠券模板
     * @param CouponModelCreate $CouponModelCreate
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest OK
     */
    public function create(CouponModelCreate $CouponModelCreate)
    {
        $params  = $this->request['params'];
        $model = $CouponModelCreate->execute($params);
        return apiResponse($model,get_code(),get_msg());
    }
    
    /**
     * 获取优惠券模板详情
      $params['model_no']
     * @param CouponModelDetail $CouponModelDetail
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest ?
     */
    public function detail(CouponModelDetail $CouponModelDetail){
        $params  = $this->request['params'];
        $CouponModelDetail = $CouponModelDetail->execute($params['model_no']);
        return apiResponse($CouponModelDetail,get_code(),get_msg());
    }
    
    /**
     * 优惠券模型发布
     * @param CouponModelPublish $CouponModelPublish
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest OK
     */
    public function publish(CouponModelPublish $CouponModelPublish)
    {
        $params  = $this->request['params'];
        $CouponModelPublish = $CouponModelPublish->execute($params['model_no']);
        return apiResponse($CouponModelPublish,get_code(),get_msg());
    }
    
    /**
     * 优惠券模型补发
     * @param CouponModelRePublish $CouponModelRePublish
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest OK
     */
    public function rePublish(CouponModelRePublish $CouponModelRePublish)
    {
        $params  = $this->request['params'];
        $CouponModelRePublish = $CouponModelRePublish->execute($params['model_no'] , $params['num']);
        return apiResponse($CouponModelRePublish,get_code(),get_msg());
    }
    
    /**
     * 优惠券模型停止发布
     * @param CouponModelUnPublish $CouponModelUnPublish
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest OK
     */
    public function unPublish(CouponModelUnPublish $CouponModelUnPublish)
    {
        $params  = $this->request['params'];
        $model = $CouponModelUnPublish->execute($params['model_no']);
        return apiResponse($model,get_code(),get_msg());
    }
    
    /**
     * 删除优惠券模型草稿
     * @param CouponModelRemove $CouponModelRemove
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest OK
     */
    public function remove(CouponModelRemove $CouponModelRemove)
    {
        $params  = $this->request['params'];
        $model = $CouponModelRemove->execute($params['model_no']);
        return apiResponse($model,get_code(),get_msg());
    }
    
    /**
     * 优惠券灰度发布
     * @param CouponModelTestStart $CouponModelTestStart
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest OK
     */
    public function greyTest(CouponModelTestStart $CouponModelTestStart)
    {
        $params  = $this->request['params'];
        $model = $CouponModelTestStart->execute($params['model_no'] , $params['mobile']);
        return apiResponse($model,get_code(),get_msg());
    }
    
    /**
     * 优惠券取消灰度发布
     * @param CouponModelTestStop $CouponModelTestStop
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest OK
     */
    public function cancelGreyTest(CouponModelTestStop $CouponModelTestStop)
    {
        $params  = $this->request['params'];
        $model = $CouponModelTestStop->execute($params['model_no'] , $params['mobile']);
        return apiResponse($model,get_code(),get_msg());
    }
    
    /**
     * 优惠券模型列表
     * @param CouponModelList $CouponModelList
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest OK
     */
    public function modelList(CouponModelList $CouponModelList)
    {
        $params = $this->request['params'];
        $model = $CouponModelList->execute($params);
        return apiResponse($model,get_code(),get_msg());
    }
    
    /**
     * 获取优惠券兑换码
     * @param CouponUserGetCode $CouponUserGetCode
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest OK
     */
    public function getCode(CouponUserGetCode $CouponUserGetCode)
    {
        $params  = $this->request['params'];
        $CouponUserGetCode->execute($params['model_no'] , $params['num']);
        return apiResponse([],get_code(),get_msg());
    }
    
    /**
     * 用户导入优惠券
     * @param CouponUserImport $CouponUserImport
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest OK
     */
    public function importUser(CouponUserImport $CouponUserImport)
    {
        $tempFile = $_FILES['file_data']['tmp_name'];
        $tempFile = $this->request['params']['tmp_name'];
        $params  = $this->request['params'];
        $model = $CouponUserImport->execute($params['model_no'] , $tempFile);
        return apiResponse($model,get_code(),get_msg());
    }
    
    /**
     * 卡券模型列表搜索条件
     * @param CouponModelSearchCondition $CouponModelSearchCondition
     * @return \Illuminate\Http\JsonResponse
     * @localtest OK
     * @devtest OK
     */
    public function searchCondition(CouponModelSearchCondition $CouponModelSearchCondition)
    {
        $channels = $CouponModelSearchCondition->execute();
        return apiResponse($channels,get_code(),get_msg());
    }

}
