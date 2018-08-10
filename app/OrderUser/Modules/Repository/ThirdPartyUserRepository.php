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
    public static function lists($params, $where='1=1', $limit, $page=null)
    {
        $query = ThirdPartyUser::where($where);
        if(isset($params['order_start']) || $params['order_start']){
            $query->where('order_time','>=',strtotime($params['order_start']));
        }
        if(isset($params['order_end']) || $params['order_end']){
            $query->where('order_time','<=',strtotime($params['order_end']));
        }
        $all = $query->paginate($limit,['*'],'page', $page);
        if($all){

            $all = self::zhuanhuan($all);
            return $all;
        }else{
            return [];
        }

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
     * @return mixed
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
            'order_time'=>($params['order_time']?strtotime($params['order_time']):0),
            'order_model'=>($params['order_model']?$params['order_model']:0),
            'colour'=>($params['colour']?$params['colour']:0),
            'total_amount'=>($params['total_amount']?$params['total_amount']:0),
            'deposit'=>($params['deposit']?$params['deposit']:0),
            'pinpai'=>($params['pinpai']?$params['pinpai']:0),
            'yanse'=>($params['yanse']?$params['yanse']:0),
            'rongliang'=>($params['rongliang']?$params['rongliang']:0),
            'zujin'=>($params['zujin']?$params['zujin']:0),
            'total_zujin'=>($params['total_zujin']?$params['total_zujin']:0),
            'suipingbao_chengben'=>($params['suipingbao_chengben']?$params['suipingbao_chengben']:0),
            'suipingbao'=>($params['suipingbao']?$params['suipingbao']:0),
            'zuqi'=>($params['zuqi']?$params['zuqi']:0),
        ];
        $matching_row = self::matching_row($data);
        $t = ThirdPartyUser::create($data);
        if($t){
            if($matching_row){
                $data['id']=$t->id;
                $matching_row[]=$data;
                return $matching_row;
            }
            return [];
        }else{
            throw new \Exception('第三方用户添加失败:'.json_encode($data));
        }
    }

    /**
     * 删除一条记录
     * @param $id
     * @return mixed
     * @throws \Exception
     */
    public static function del($id){
        if(ThirdPartyUser::destroy($id)){
            return $id;
        }else{
            throw new \Exception('第三方用户删除失败:'.$id);
        }
    }

    /**
     * 审核通过
     * @param $id
     * @return bool
     * @throws \Exception
     */
    public static function audit($id){
        $obj = ThirdPartyUser::find($id);
        if($obj){
            $obj->status = ThirdPartyUser::STATUS_NONE;
            if($obj->update()){
                return true;
            }
            throw new \Exception('第三方用户审核失败:'.$id);
        }else{
            throw new \Exception('第三方用户审核失败:未找到 '.$id);
        }
    }

    /**
     * 查询相似订单
     * @param array $matching 二维数组
     * @return array 三维数组|空数组
     */
    public static function matching($matching){
        $data = [];
        $in = [
            ThirdPartyUser::STATUS_ZHIFU,
            ThirdPartyUser::STATUS_FAHUO,
            ThirdPartyUser::STATUS_QIANSHOU,
            ThirdPartyUser::STATUS_ZUYONG,
            ThirdPartyUser::STATUS_TUIHUO,
        ];

        foreach ($matching as $key=>$item){
            //判断手机号
            if($item['phone']){
                $query = ThirdPartyUser::whereIn('status',$in);
                $query->where(['phone'=>$item['phone']]);
                $all = $query->get();
                if($all){
                    $all = $all->toArray();
                    $all = self::zhuanhuan($all);
                    $data[] = $all;
                    continue;
                }
                unset($query);
            }

            //判断身份证
            if($item['identity']){
                $query = ThirdPartyUser::whereIn('status',$in);
                $query->where(['identity'=>$item['identity']]);
                $all = $query->get();
                if($all){
                    $all = $all->toArray();
                    $all = self::zhuanhuan($all);
                    $data[] = $all;
                    continue;
                }
                unset($query);
            }

            //判断收货人 收货地址
            if($item['consignee'] && $item['shipping_address']){
                $query = ThirdPartyUser::whereIn('status',$in);
                $query->where([
                    'consignee'=>$item['consignee'],
                    'province'=>$item['province'],
                    'city'=>$item['city'],
                    'county'=>$item['county']
                ]);
                $all = $query->get();
                if($all){
                    $all = $all->toArray();
                    $all = self::zhuanhuan($all);
                    $data[] = $all;
                    continue;
                }
                unset($query);
            }

        }
        return $data;

    }

    /**
     * 匹配一条数据
     * @param array $matching 一维数组
     * @return array 二维数组|空数组
     */
    public static function matching_row($matching){
        $data = [];
        $in = [
            ThirdPartyUser::STATUS_ZHIFU,
            ThirdPartyUser::STATUS_FAHUO,
            ThirdPartyUser::STATUS_QIANSHOU,
            ThirdPartyUser::STATUS_ZUYONG,
            ThirdPartyUser::STATUS_TUIHUO,
        ];

        //判断手机号
        if(isset($matching['phone']) && $matching['phone']){
            $query = ThirdPartyUser::whereIn('status',$in);
            $query->where(['phone'=>$matching['phone']]);
            $all = $query->get();
            if($all){
                $all = $all->toArray();
                $all = self::zhuanhuan($all);
                return $all;
            }
            unset($query);
        }

        //判断身份证
        if(isset($matching['identity']) && $matching['identity']){
            $query = ThirdPartyUser::whereIn('status',$in);
            $query->where(['identity'=>$matching['identity']]);
            $all = $query->get();
            if($all){
                $all = $all->toArray();
                $all = self::zhuanhuan($all);
                return $all;
            }
            unset($query);
        }

        //判断收货人 收货地址
        if(isset($matching['consignee']) && isset($matching['shipping_address']) && $matching['consignee'] && $matching['shipping_address']){
            $query = ThirdPartyUser::whereIn('status',$in);
            $query->where([
                'consignee'=>$matching['consignee'],
                'province'=>$matching['province'],
                'city'=>$matching['city'],
                'county'=>$matching['county']
            ]);
            $all = $query->get();
            if($all){
                $all = $all->toArray();
                $all = self::zhuanhuan($all);
                return $all;
            }
            unset($query);
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
            $all[$key]['types_name'] = ThirdPartyUser::types($item['types']);
            if($item['start_time']){
                $all[$key]['start_time'] = date('Y-m-d h:i:s',$item['start_time']);
            }
            if($item['end_time']){
                $all[$key]['end_time'] = date('Y-m-d h:i:s',$item['end_time']);
            }
            if($item['order_time']){
                $all[$key]['order_time'] = date('Y-m-d h:i:s',$item['order_time']);
            }
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
        $row['types_name'] = ThirdPartyUser::types($row['types']);
        if($row['start_time']){
            $row['start_time'] = date('Y-m-d h:i:s',$row['start_time']);
        }
        if($row['end_time']){
            $row['end_time'] = date('Y-m-d h:i:s',$row['end_time']);
        }
        if($row['order_time']){
            $row['order_time'] = date('Y-m-d h:i:s',$row['order_time']);
        }

        return $row;

    }

    /**
     * 开始时间
     * @return bool
     * @throws \Exception
     */
    public static function start(){
        $in = [ThirdPartyUser::STATUS_ZHIFU,ThirdPartyUser::STATUS_FAHUO,ThirdPartyUser::STATUS_QIANSHOU];
        $t = time();
        $query = ThirdPartyUser::whereIn('status',$in);
        $query->where('start_time','<=',$t);
        $query->where('end_time','>',$t);
        $r = $query->update(['status'=>ThirdPartyUser::STATUS_ZUYONG]);
        if($r){
            return true;
        }else{
            throw new \Exception('第三方用户 定时任务 开始时间 执行失败');
        }

    }

    /**
     * 结束时间
     * @return bool
     * @throws \Exception
     */
    public static function end(){
        $in = [ThirdPartyUser::STATUS_ZHIFU,ThirdPartyUser::STATUS_FAHUO,ThirdPartyUser::STATUS_QIANSHOU,ThirdPartyUser::STATUS_ZUYONG];
        $t = time();
        $query = ThirdPartyUser::whereIn('status',$in);
        $query->where('end_time','<',$t);
        $r = $query->update(['status'=>ThirdPartyUser::STATUS_WANCHENG]);
        if($r){
            return true;
        }else{
            throw new \Exception('第三方用户 定时任务 结束时间 执行失败');
        }
    }


}