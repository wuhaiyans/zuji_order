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
        $all = $query->paginate($limit,['*'],'page', $page);
        $all = self::zhuanhuan($all);

        return $all;
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
        $row = self::zhuanhuan_row($row->toArray());

        return $row;
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
        $row->order_time=($params['order_time']?strtotime($params['order_time']):0);
        $row->order_model=$params['order_model'];
        $row->colour=$params['colour'];
        $row->total_amount=$params['total_amount'];
        $row->deposit=$params['deposit'];
        $row->pinpai=$params['pinpai'];
        $row->jixing=$params['jixing'];
        $row->yanse=$params['yanse'];
        $row->rongliang=$params['rongliang'];
        $row->zujin=$params['zujin'];
        $row->total_zujin=$params['total_zujin'];
        $row->suipingbao_chengben=$params['suipingbao_chengben'];
        $row->suipingbao=$params['suipingbao'];
        $row->zuqi=$params['zuqi'];
        if($row->update()){
            return true;
        }else{
            throw new \Exception('第三方用户ID:'.$params['id'].'修改失败');
        }

    }

    /**
     * 添加
     * @param $params
     * @return bool
     * @throws \Exception
     */
    public static function add($params){
        if(!$params){
            throw new \Exception('第三方用户添加失败 params 为空');
        }
        $data = [
            'phone'=>$params['phone'],
            'consignee'=>$params['consignee'],
            'province'=>$params['province'],
            'city'=>$params['city'],
            'county'=>$params['county'],
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
            'types'=>$params['types'],
            'order_time'=>($params['order_time']?$params['order_time']:0),
            'order_model'=>($params['order_model']?$params['order_model']:0),
            'colour'=>($params['colour']?$params['colour']:0),
            'total_amount'=>($params['total_amount']?$params['total_amount']:0),
            'deposit'=>($params['deposit']?$params['deposit']:0),
            'pinpai'=>($params['pinpai']?$params['pinpai']:0),
            'jixing'=>($params['jixing']?$params['jixing']:0),
            'yanse'=>($params['yanse']?$params['yanse']:0),
            'rongliang'=>($params['rongliang']?$params['rongliang']:0),
            'zujin'=>($params['zujin']?$params['zujin']:0),
            'total_zujin'=>($params['total_zujin']?$params['total_zujin']:0),
            'suipingbao_chengben'=>($params['suipingbao_chengben']?$params['suipingbao_chengben']:0),
            'suipingbao'=>($params['suipingbao']?$params['suipingbao']:0),
            'zuqi'=>($params['zuqi']?$params['zuqi']:0),
        ];
        $t = ThirdPartyUser::create($data);
        if($t){
            return $t->id;
        }else{
            throw new \Exception('第三方用户添加失败:'.json_encode($data));
        }
    }

    /**
     * 查询相似订单 三维数组
     * @param $matching
     * @return array
     */
    public static function matching($matching){
        $data = [];
        foreach ($matching as $key=>$item){
            //判断手机号
            if($item['phone']){
                $all = ThirdPartyUser::where(['phone'=>$item['phone']])->all();
                if($all){
                    $all = $all->toArray();
                    $all = self::zhuanhuan($all);
                    $data[] = $all;
                    continue;
                }
            }

            //判断身份证
            if($item['identity']){
                $all = ThirdPartyUser::where(['identity'=>$item['identity']])->all();
                if($all){
                    $all = $all->toArray();
                    $all = self::zhuanhuan($all);
                    $data[] = $all;
                    continue;
                }
            }

            //判断收货人 收货地址
            if($item['consignee'] && $item['shipping_address']){
                $all = ThirdPartyUser::where([
                    'consignee'=>$item['consignee'],
                    'province'=>$item['province'],
                    'city'=>$item['city'],
                    'county'=>$item['county']
                ])->all();
                if($all){
                    $all = $all->toArray();
                    $all = self::zhuanhuan($all);
                    $data[] = $all;
                    continue;
                }
            }

        }
        return $data;

    }

    /**
     * 状态转换
     *      二维数组
     * @param $all
     * @return mixed
     */
    public static function zhuanhuan($all){
        foreach ($all as $key=>$item){
            $all[$key]['status_name'] = ThirdPartyUser::sta($item['status']);
            $all[$key]['platform_name'] = ThirdPartyUser::platform($item['platform']);
            $all[$key]['pinpai_name'] = ThirdPartyUser::pinpai($item['pinpai']);
            $all[$key]['colour_name'] = ThirdPartyUser::chengse($item['colour']);
        }
        return $all;

    }

    /**
     * 状态转换
     *      一维数组
     * @param $all
     * @return mixed
     */
    public static function zhuanhuan_row($row){
        $row['status_name'] = ThirdPartyUser::sta($row['status']);
        $row['platform_name'] = ThirdPartyUser::platform($row['platform']);
        $row['pinpai_name'] = ThirdPartyUser::pinpai($row['pinpai']);
        $row['colour_name'] = ThirdPartyUser::chengse($row['colour']);

        return $row;

    }


}