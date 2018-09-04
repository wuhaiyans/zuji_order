<?php
namespace App\Activity\Modules\Repository;
use App\Activity\Models\ActivityGoodsAppointment;
use Illuminate\Support\Facades\DB;

class ActivityGoodsAppointmentRepository
{

    protected $activityGoodsAppointment;


    public function __construct()
    {
        $this->activityGoodsAppointment = new ActivityGoodsAppointment();
    }
    /**
     * 创建活动与商品的关联关系
     * @param $data
     * [
     *    'appointment_id'  =>'',  //活动id   int  【必传】
     *    'spu_id'          =>'',  //商品id   int  【必传】
     *    'create_time'    =>'',  //创建时间  int  【必传】
     * ]
     * @return bool
     */
    public static function add(array $data){
        $res = ActivityGoodsAppointment::insert($data);
        return $res;
    }
    /***
     * 通过活动id获取活动信息
     * @param $id  //活动id  int 【必传】

     * @return array
     */
    public static function getByIdInfo(int $id){
        if(empty($id)){
            return false;
        }
        $where[]=['appointment_id','=',$id];
        $activityInfo=ActivityGoodsAppointment::where($where)->get()->toArray();
        if( !$activityInfo ){
            return false;
        }
        return $activityInfo;

    }
    /***
     * 禁用/启用活动的商品
     * @param $id      //活动id  int 【必传】
     * @param $spu_id  //商品id  int 【必传】
     * $data
     * [
     *   'goods_status'  =>'', //商品状态    int 【必传】
     *   'update_time'   =>''  //修改过时间  int 【必传】
     * ]
     * @return array
     */
    public static function closeActivityGoods(int $id,int $spu_id ,array $data){
        if(empty($id)){
            return false;
        }
        $where[]=['appointment_id','=',$id];
        $where[]=['spu_id','=',$spu_id];
        $activityInfo=ActivityGoodsAppointment::where($where)->update($data);
        if( !$activityInfo ){
            return false;
        }
        return $activityInfo;

    }


}