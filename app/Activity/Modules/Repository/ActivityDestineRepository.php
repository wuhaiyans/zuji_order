<?php
namespace App\Activity\Modules\Repository;

use App\Activity\Models\ActivityDestine;
use App\Activity\Modules\Inc\DestineStatus;
use App\Lib\Common\LogApi;
use Illuminate\Support\Facades\DB;

class ActivityDestineRepository
{

    protected $activityDestine;


    public function __construct()
    {
        $this->activityDestine = new ActivityDestine();
    }

    /**
     * 创建活动预定
     * @param $data
     *  [
     *      'destine_no'    => ' ', //【必须】 string 预定编号
     *      'activity_id'   => ' ', //【必须】 int   活动ID
     *      'user_id'       => ' ', //【必须】 int   用户ID
     *      'mobile'        => ' ', //【必须】 string 用户手机号
     *      'destine_amount'=> ' ', //【必须】 float  预定金额
     *      'pay_type'      => ' ', //【必须】 int  支付类型
     *      'app_id'        => ' ', //【必须】 int app_id
     *      'channel_id'    => ' ', //【必须】 int 渠道Id
     *      'activity_name' => ' ', //【必须】 string 活动名称
    ]
     * @return bool
     */

    public function add($data){
        $data = filter_array($data, [
            'destine_no'    => 'required',
            'activity_id'   => 'required',
            'user_id'       => 'required',
            'mobile'        => 'required',
            'destine_amount'=> 'required',
            'pay_type'      => 'required',
            'app_id'        => 'required',
            'channel_id'    => 'required',
            'activity_name' => 'required',
        ]);
        if(count($data)<9){
            return false;
        }
        $this->activityDestine->destine_no = $data['destine_no'];
        $this->activityDestine->activity_id = $data['activity_id'];
        $this->activityDestine->user_id = $data['user_id'];
        $this->activityDestine->mobile = $data['mobile'];
        $this->activityDestine->destine_amount = $data['destine_amount'];
        $this->activityDestine->pay_type = $data['pay_type'];
        $this->activityDestine->app_id = $data['app_id'];
        $this->activityDestine->channel_id = $data['channel_id'];
        $this->activityDestine->activity_name = $data['activity_name'];
        $this->activityDestine->destine_status = DestineStatus::DestineCreated;
        $this->activityDestine->create_time = time();
        $this->activityDestine->update_time = time();

        return $this->activityDestine->save();
    }

    /**
     * 查询当前用户是否已经预约活动
     * @param $user_id   用户ID
     * @param $activity_id 活动ID
     * @return array
     */

    public static function unActivityDestineByUser($userId,$activityId){
        if (empty($userId)) return false;
        if (empty($activityId)) return false;
        $info = ActivityDestine::query()->where([
            ['user_id', '=', $userId],
            ['activity_id', '=', $activityId],
        ])->first();
        return $info;
    }

    /**
     * 获取预定信息列表
     * @param $params
     * [
     *   'user_id'     => '',  //用户id   【可选】
     *   'destine_no'  =>'',   //预定编号  【可选】
     *   'mobile'      =>'',   //手机号    【可选】
     *   'activity_name'=>'',   //预定名称   【可选】
     *   'destine_status' =>'' //定金状态   【可选】
     *   'app_id '      =>'' //应用渠道     【可选】
     *    'channel_id ' =>'' //渠道id       【可选】
     *   'pay_type '    =>'' //支付方式     【可选】
     *   'pay_time '    =>''  //支付时间     【可选】
     *   'account_number' =>'' // 支付宝账号  【可选】
     * ]
     *

     */
    public static function getDestineList($param=array()){
        $page       = isset($param['page']) ? $param['page'] : 1;
        $pageSize   = 500;
        $offset     = ($page - 1) * $pageSize;
        $whereArray= self::get_where($param);  //获取搜索的条件
        LogApi::debug("新机预约导出的条件",$whereArray);
        $destineArrays = array();

        $destineList =  DB::table('order_activity_destine')
            ->select('order_activity_destine.*')
            ->where($whereArray)
            ->offset($offset)
            ->limit($pageSize)
            ->orderBy('create_time', 'DESC')
            ->get();
        LogApi::debug("新机预约查询结果",$destineList);
        $destineArrays = array_column(objectToArray($destineList),NULL,'destine_no');
        LogApi::debug("新机预约查询最终结果",$destineArrays);
        return $destineArrays;
    }

    /**
     * 获取预定信息分页列表
     * @param $params
     * [
     *   'user_id'     => '',  //用户id   【可选】
     *   'destine_no'  =>'',   //预定编号  【可选】
     *   'mobile'      =>'',   //手机号    【可选】
     *   'activity_name'=>'',   //预定名称   【可选】
     *   'destine_status' =>'' //定金状态   【可选】
     *   'app_id '      =>'' //应用渠道     【可选】
     *    'channel_id ' =>'' //渠道id       【可选】
     *   'pay_type '    =>'' //支付方式     【可选】
     *   'pay_time '    =>''  //支付时间     【可选】
     *   'account_number' =>'' // 支付宝账号  【可选】
     * ]
     *

     */
    public static  function getDestinePageList($param=array()){
        $page = empty($param['page']) ? 1 : $param['page'];
        $size = !empty($param['size']) ? $param['size'] : config('web.pre_page_size');
        $whereArray= self::get_where($param);  //获取搜索的条件
        $destineList =  DB::table('order_activity_destine')
            ->select('order_activity_destine.*')
            ->where($whereArray)
            ->orderBy('create_time', 'DESC')
            ->paginate($size,$columns = ['*'], $pageName = 'page', $page);
        if($destineList){
            return $destineList->toArray();
        }
        return [];
    }

    /**
     * 列表条件
     */
    public static function get_where($param=array()){
        $whereArray=[];
        //根据预订单编号
        if (isset($param['destine_no']) && !empty($param['destine_no'])) {

            $whereArray[] = ['destine_no', '=', $param['destine_no']];
        }
        //根据用户手机号
        if (isset($param['mobile']) && !empty($param['mobile'])) {

            $whereArray[] = ['mobile', '=', $param['mobile']];
        }

        //根据定金状态
        if (isset($param['destine_status']) && !empty($param['destine_status'])) {

            $whereArray[] = ['destine_status', '=', $param['destine_status']];
        }
        //根据支付方式
        if (isset($param['pay_type']) && !empty($param['pay_type'])) {

            $whereArray[] = ['pay_type', '=', $param['pay_type']];
        }
        //根据应用渠道
        if (isset($param['app_id']) && !empty($param['app_id'])) {

            $whereArray[] = ['channel_id', '=', $param['app_id']];
        }


        //根据定金支付时间
        if (isset($param['begin_time']) && !empty($param['begin_time']) ) {
            $whereArray[] = ['create_time', '>=', strtotime($param['begin_time'])];
        }

        //根据定金支付时间
        if ( isset($param['end_time']) && !empty($param['end_time'])) {
            $whereArray[] = ['create_time', '<', (strtotime($param['end_time'])+3600*24)];
        }
        return $whereArray;
    }

}