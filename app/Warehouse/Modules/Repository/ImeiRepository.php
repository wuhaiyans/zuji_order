<?php
/**
 * User: wansq
 * Date: 2018/5/7
 * Time: 20:36
 */

namespace App\Warehouse\Modules\Repository;

use App\Warehouse\Models\Receive;
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
            }

            //DB::table('zuji_imei')->insert($data);
            Imei::insert($data);

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

//        $result = [];
//        foreach ($list as $v) {
//            $result[$v['imei']] = $v;
//        }

        return $list;
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