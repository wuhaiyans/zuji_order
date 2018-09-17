<?php
/**
 * User: wansq
 * Date: 2018/5/7
 * Time: 20:36
 */

namespace App\Warehouse\Modules\Repository;

use App\Warehouse\Models\ImeiLog;
use App\Warehouse\Models\ImeiUpdateLog;
use App\Warehouse\Models\Receive;
use App\Warehouse\Models\ReceiveGoods;
use App\Warehouse\Models\ReceiveGoodsImei;
use Illuminate\Support\Facades\DB;
use App\Warehouse\Models\Imei;
class ImeiRepository
{

    private $imei;


    public function __construct(Imei $imei)
    {
        $this->imei = $imei;
    }

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
     * @param $params
     * @param $limit
     * @param null $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     *
     * 列表
     */
    public static function list($params,$logic_params, $limit, $page=null)
    {

        $query = \App\Warehouse\Models\Imei::whereIn('status', [Imei::STATUS_OUT, Imei::STATUS_IN]);

        if (is_array($params)) {
            foreach ($params as $k => $param) {
                if (in_array($k, ['imei', 'brand', 'color', 'business'])) {
                    $query->where($k, 'like', '%'.$param.'%');
                } else {
                    $query->where([$k=>$param]);
                }
            }
        }

        if (is_array($logic_params) && count($logic_params)>0) {
            foreach ($logic_params as $logic) {
                $query->where($logic[0], $logic[1] ,$logic[2]);
            }
        }

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
     * 根据IMEI查询返回一条记录
     *
     * @param string $imei
     * @return array 一维数组
     */
    public static function getRow($imei)
    {
        $row = Imei::where(['imei'=>$imei])->first();

        if(!$row){
            throw new \Exception('设备表IMEI号:'.$imei.'未找到');
        }

        return $row->toArray();
    }
    /**
     * 根据IMEI修改一条记录
     *
     * @param array $imei
     * @return boolean
     */
    public static function setRow($id,$params)
    {
        $row = Imei::where(['id'=>$id])->first();
        if(!$row){
            throw new \Exception('设备表ID号:'.$id.'未找到');
        }
        $log = [];
        $t = time();
        if(isset($params['brand'])){
            $log=[
                'imei_id'=>$id,
                'table_name'=>'brand',
                'before_value'=>$row->brand,
                'after_value'=>$params['brand'],
                'update_time'=>$t
            ];
            if(!ImeiUpdateLog::create($log)){
                throw new \Exception('批量添加Imei修改日志表失败:'.json_encode($log));
            }
            $row->brand=$params['brand'];

        }
        if(isset($params['name'])){
            $log=[
                'imei_id'=>$id,
                'table_name'=>'name',
                'before_value'=>$row->name,
                'after_value'=>$params['name'],
                'update_time'=>$t
            ];
            if(!ImeiUpdateLog::create($log)){
                throw new \Exception('批量添加Imei修改日志表失败:'.json_encode($log));
            }
            $row->name=$params['name'];

        }
        if(isset($params['color'])){
            $log=[
                'imei_id'=>$id,
                'table_name'=>'color',
                'before_value'=>$row->color,
                'after_value'=>$params['color'],
                'update_time'=>$t
            ];
            if(!ImeiUpdateLog::create($log)){
                throw new \Exception('批量添加Imei修改日志表失败:'.json_encode($log));
            }
            $row->color=$params['color'];

        }
        if(isset($params['business'])){
            $log=[
                'imei_id'=>$id,
                'table_name'=>'business',
                'before_value'=>$row->business,
                'after_value'=>$params['business'],
                'update_time'=>$t
            ];
            if(!ImeiUpdateLog::create($log)){
                throw new \Exception('批量添加Imei修改日志表失败:'.json_encode($log));
            }
            $row->business=$params['business'];

        }
        if(isset($params['storage'])){
            $log=[
                'imei_id'=>$id,
                'table_name'=>'storage',
                'before_value'=>$row->storage,
                'after_value'=>$params['storage'],
                'update_time'=>$t
            ];
            if(!ImeiUpdateLog::create($log)){
                throw new \Exception('批量添加Imei修改日志表失败:'.json_encode($log));
            }
            $row->storage=$params['storage'];

        }
        if(isset($params['quality'])){
            $log=[
                'imei_id'=>$id,
                'table_name'=>'quality',
                'before_value'=>$row->quality,
                'after_value'=>$params['quality'],
                'update_time'=>$t
            ];
            if(!ImeiUpdateLog::create($log)){
                throw new \Exception('批量添加Imei修改日志表失败:'.json_encode($log));
            }
            $row->quality=$params['quality'];

        }
        if(isset($params['imei'])){
            $log=[
                'imei_id'=>$id,
                'table_name'=>'imei',
                'before_value'=>$row->imei,
                'after_value'=>$params['imei'],
                'update_time'=>$t
            ];
            if(!ImeiUpdateLog::create($log)){
                throw new \Exception('批量添加Imei修改日志表失败:'.json_encode($log));
            }
            $row->imei=$params['imei'];

        }
        if(empty($log)){
            throw new \Exception('设备表ID号:'.$id.'没有要修改的数据');
        }

        $row->update_time=$t;
        if($row->update()){
            return true;
        }else{
            throw new \Exception('设备表ID号:'.$id.'修改失败');
        }

    }

    /**
     * 根据IMEI修改一条记录
     *
     * @param array $imei
     * @return boolean
     */
    public static function getImeiLog($id){
        $data=[
            'imeilog'=>[],
            'imeiupdatelog'=>[],
        ];

        $imeilog_obj = ImeiLog::where(['imei_id'=>$id])->get();
        if($imeilog_obj){
            $data['imeilog'] = $imeilog_obj->toArray();
            foreach ($data['imeilog'] as $k=>$item){
                $data['imeilog'][$k]['zuqi_type'] = ImeiLog::zuqi_type($item['zuqi_type']);
            }
        }

        $imeiupdatelog_obj = ImeiUpdateLog::where(['imei_id'=>$id])->get();
        if($imeiupdatelog_obj){
            $data['imeiupdatelog'] = $imeiupdatelog_obj->toArray();
        }

        return $data;

    }

    /**
     * 修改IMEI状态仓库中(确认收货入库)
     */
    public static function updateStatus($receive_no){
        $model = Receive::find($receive_no);
        //目前是一个收货单对应一个商品一个IMEI
        $imei = $model->imeis;
        //$goods = $model->goods;

        if(!$imei) {
            return false;
        }
//        foreach ($goods as $item){
//            $imei = ReceiveGoodsImei::where(['receive_no'=>$receive_no,'goods_no'=>$item->goods_no])->first();
//            if(!$imei){
//                return false;
//            }
//            if(!ImeiLog::in($imei->imei,$model->order_no,$item->zuqi,$item->zuqi_type)){
//                return false;
//            }
//            if(!Imei::where(['imei'=>$imei->imei])->update(['status'=>Imei::STATUS_IN])){
//                return false;
//            }
//            $imei->status = ReceiveGoodsImei::STATUS_CHECK_OVER;
//            if(!$imei->update()){
//                return false;
//            }
//        }
        foreach($imei as $k=>$imeModel) {
            if(!ImeiLog::in($imeModel->imei,$model->order_no)){
                return false;
            }
            if(!Imei::where(['imei'=>$imeModel->imei])->update(['status'=>Imei::STATUS_IN])){
                return false;
            }

        }

        return true;

    }

}