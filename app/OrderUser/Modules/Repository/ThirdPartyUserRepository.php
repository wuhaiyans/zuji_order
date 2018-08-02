<?php
/**
 * User: jinlin wang
 * Date: 2018/8/1
 * Time: 17:13
 */
namespace App\OrderUser\Modules\Repository;

use App\OrderUser\Models\ThirdPartyUser;
use PHPUnit\Framework\MockObject\Stub\Exception;

class ThirdPartyUserRepository
{

    /**
     * 导入imei数据
     * $data 二维数组
     * $data = [
     *  ['imei'=> 'abcdeedsafsdeds89a8df7sa0dsd7f0', 'price'=> '20.00'],
     * ['imei'=> 'abcdeedsafsdeds89a8df7sa0dsd7f1', 'price'=> '29.00'],
     * ];
     */
    public static function import($data)
    {

        try {
            //DB::beginTransaction();
            //DB::setDefaultConnection('warehouse');

            $time = time();

//            foreach ($data as &$d) {
//                $d['create_time'] = $d['update_time'] = $time;
//                $d['status'] = 1;
//            }unset($d);
            foreach ($data as $k=>$item) {
                $data[$k]['create_time'] = $time;
                $data[$k]['update_time'] = $time;
                $data[$k]['status'] = 1;
                Imei::insert($data[$k]);
            }

            //DB::table('zuji_imei')->insert($data);
            //DB::commit();
        } catch (\Exception $e) {
            //DB::rollBack();

            throw new \Exception($e->getMessage());
        }

        return true;
    }


    /**
     * @param $where
     * @param $limit
     * @param null $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     *
     * 列表
     */
    public static function lists($where='1=1', $limit, $page=null)
    {
        $query = ThirdPartyUser::where($where);

        return $query->paginate($limit,
            [
                '*'
            ],
            'page', $page);
    }


    /**
     * @param $imei
     *
     * 模糊查询
     */
    public static function search($imei, $limit)
    {
        $list = \App\Warehouse\Models\Imei::where('imei','like','%'.$imei.'%')
            ->where(['status' => \App\Warehouse\Models\Imei::STATUS_IN])
            ->limit($limit)
            ->get()->toArray();

        return $list;
    }

    /**
     * 根据第三方用户ID返回一条记录
     *
     * @param string $id
     * @return array 一维数组
     */
    public static function getRow($id)
    {
        $row = ThirdPartyUser::find($id);

        if(!$row){
            throw new \Exception('第三方用户ID:'.$id.'未找到');
        }

        return $row->toArray();
    }
    /**
     * 根据第三方用户ID修改一条记录
     *
     * @param array $imei
     * @return boolean
     */
    public static function setRow($params)
    {
        $row = ThirdPartyUser::find($params['id']);

        if(!$row){
            throw new \Exception('第三方用户ID:'.$params['id'].'未找到');
        }
        $row->status=$params['status'];
        $row->start_time=($params['start_time']?strtotime($params['start_time']):0);
        $row->end_time=($params['end_time']?strtotime($params['end_time']):0);
        $row->user_name=$params['user_name'];
        $row->identity=$params['identity'];
        $row->order_no=$params['order_no'];
        $row->imei=$params['imei'];
        $row->remarks=$params['remarks'];
        if($row->update()){
            return true;
        }else{
            throw new \Exception('第三方用户ID:'.$params['id'].'修改失败');
        }

    }

    public static function add($params){
        if(!$params){
            throw new \Exception('第三方用户添加失败 params 为空');
        }
        $data = [
            'phone'=>$params['phone'],
            'consignee'=>$params['consignee'],
            'shipping_address'=>$params['shipping_address'],
            'status'=>$params['status'],
            'platform'=>$params['platform'],
            'start_time'=>($params['start_time'] ? strtotime($params['start_time']) : 0),
            'end_time'=>($params['end_time'] ? strtotime($params['end_time']) : 0),
            'user_name'=>($params['user_name']?$params['user_name']:0),
            'identity'=>($params['identity']?$params['identity']:0),
            'order_no'=>($params['order_no']?$params['order_no']:0),
            'imei'=>($params['imei']?$params['imei']:0),
            'remarks'=>($params['remarks']?$params['remarks']:0),
        ];
        if(ThirdPartyUser::create($data)){
            return true;
        }else{
            throw new \Exception('第三方用户添加失败:'.json_encode($data));
        }
    }


}