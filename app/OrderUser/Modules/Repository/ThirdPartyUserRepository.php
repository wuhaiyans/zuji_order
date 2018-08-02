<?php
/**
 * User: jinlin wang
 * Date: 2018/8/1
 * Time: 17:13
 */
namespace App\OrderUser\Modules\Repository;

use App\OrderUser\Models\ThirdPartyUser;
use App\Warehouse\Models\Receive;
use Illuminate\Support\Facades\DB;
use App\Warehouse\Models\Imei;
class ThirdPartyUserRepository
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
    public static function setRow($params)
    {
        $row = Imei::where(['imei'=>$params['imei']])->first();

        if(!$row){
            throw new \Exception('设备表IMEI号:'.$params['imei'].'未找到');
        }
        $row->brand=$params['brand'];
        $row->name=$params['name'];
        $row->price=$params['price'];
        $row->apple_serial=$params['apple_serial'];
        $row->quality=$params['quality'];
        $row->color=$params['color'];
        $row->business=$params['business'];
        $row->storage=$params['storage'];
        $row->status=$params['status'];
        $row->update_time=time();
        if($row->update()){
            return true;
        }else{
            throw new \Exception('设备表IMEI号:'.$params['imei'].'修改失败');
        }

    }

    /**
     * 修改IMEI状态仓库中(确认收货入库)
     */
    public static function updateStatus($receive_no){
        $model = Receive::find($receive_no);
        //目前是一个收货单对应一个商品一个IMEI
        $imei = $model->imeis;

        if(!$imei) {
            return false;
        }
        foreach($imei as $imeModel) {

            $imeModel->status = Imei::STATUS_IN;
            if (!$imeModel->update()){

                return false;
            }

        }

        return true;

    }

}