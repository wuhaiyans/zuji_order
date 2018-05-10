<?php
/**
 * User: wansq
 * Date: 2018/5/9
 * Time: 11:17
 */


namespace App\Warehouse\Modules\Repository;


use App\Warehouse\Models\Receive;
use Symfony\Component\Translation\Exception\NotFoundResourceException;


class ReceiveRepository
{
    public static function generateReceiveNo()
    {
        return date('YmdHis') . rand(1000, 9999);
    }
    /**
     * @param $params
     * @param $limit
     * @param null $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     *
     * 列表
     */

    /**
     * @param $params
     * @param $limit
     * @param null $page
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     *
     * 列表
     */
    public static function list($params, $logic_params, $limit, $page=null)
    {
        $query = Receive::where($params);

        if (is_array($logic_params)) {
            foreach ($logic_params as $logic) {
                $query->where($logic[0], $logic[1] ,$logic[2]);
            }
        }
        return $query->paginate($limit,
            [
                'receive_no','order_no', 'logistics_id','logistics_no',
                'status', 'create_time', 'receive_time','check_description',
                'status_time','check_time','check_result'
            ],
            'page', $page);
    }

    /**
     * 清单查询
     */
    public static function show($receive_no)
    {
        $model = Receive::find($receive_no);

        if (!$model) {
            throw new NotFoundResourceException('收货单' . $receive_no . '未找到');
        }

        $result = $model->toArray();

        if ($model->imeis) {
            $result['imeis'] = $model->imeis;
        }

        if ($model->goods) {
            $result['goods'] = $model->goods;
        }

        return $result;
    }

    /**
     * 创建
     */
    public static function create($data)
    {
        $receiveNo = self::generateReceiveNo();

        $model = new Receive();
    }

    /**
     * 取消收货单
     */
    public static function cancel($receive_no)
    {
        $model = Receive::find($receive_no);

        if (!$model) {
            throw new NotFoundResourceException('收货单' . $receive_no . '未找到');
        }

    }

    /**
     * 签收
     */
    public static function received()
    {

    }

    /**
     * 取消签收
     */
    public static function cancelReceive()
    {

    }


    /**
     * 验收 针对设备
     */
    public static function check()
    {

    }

    /**
     * 取消验收 针对设备
     */
    public static function cancelCheck()
    {

    }

    /**
     * 完成签收 针对收货单
     */
    public static function finishCheck()
    {

    }

    /**
     * 录入检测项
     */
    public static function note()
    {

    }
}