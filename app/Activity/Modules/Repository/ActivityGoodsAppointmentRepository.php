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
     * @param $where
     * [
     * 'id' => '' //活动id  int 【必传】
     * ]
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
     * 删除活动和商品的关系数据
     * @param int $id  活动id
     * @return bool|null
     * @throws \Exception
     */

    public static function delActivityGoods(int $id){
        if(empty($id)){
            return false;
        }
        $where[]=['appointment_id','=',$id];
        $res=ActivityGoodsAppointment::where($where)->delete();
        if( !$res ){
            return false;
        }
        return $res;
    }



}