<?php
/**
 * Created by PhpStorm.
 * User: wangjinlin
 * Date: 2018/5/21
 * Time: 下午4:50
 */

namespace App\Order\Modules\Service;



use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Inc\ReletStatus;
use App\Order\Modules\Repository\OrderGoodsRepository;
use App\Order\Modules\Repository\ReletRepository;

class Relet
{
    protected $reletRepository;

    public function __construct(ReletRepository $reletRepository)
    {
        $this->reletRepository = $reletRepository;
    }

    /**
     * 获取续租列表
     *      带分页
     *
     * @param $params
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getList($params){
        return $this->reletRepository->getList($params);

    }

    /**
     * 通过ID获取一条记录
     *
     * @param $params
     * @return array
     */
    public function getRowId($params){
        return $this->reletRepository->getRowId($params);

    }

    /**
     * 设置status状态
     *
     * @param $params
     * @return bool
     */
    public function setStatus($params){
        $row = $this->reletRepository->getRowId($params['id']);
        if($row['status'] == 1){
            return $this->reletRepository->setStatus($params);
        }else{
            set_msg('只允许创建续租取消');
            return false;
        }
    }

    /**
     * 创建续租单
     *
     * @param $params
     * @return bool
     */
    public function createRelet($params){
        return $this->reletRepository->createRelet($params);
    }

    /**
     * @param $params
     * @return array|bool
     */
    public function getGoodsZuqi($params){
        $where = [
            ['id', '=', $params['goods_id']],
            ['user_id', '=', $params['user_id']],
            ['order_no', '=', $params['order_no']]
        ];
        $row = OrderGoodsRepository::getGoodsRow($where);
        if($row){
            if($row['zuqi_type']==OrderStatus::ZUQI_TYPE1){
                $list = ReletStatus::getDuanzuList();
                foreach ($list as $item){
                    $list[$item] = ['zuqi'=>$item,'zujin'=>$item*$row['zujin']];
                }
            }else{
                $list = ReletStatus::getCangzulist();
                foreach ($list as $item){
                    $list[$item] = ['zuqi'=>$item,'zujin'=>$item*$row['zujin']];
                }
            }
            $row['list'] = $list;
            return $row;
        }else{
            set_msg('数据未查到');
            return false;
        }
    }

}