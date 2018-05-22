<?php
/**
 * Created by PhpStorm.
 * User: wangjinlin
 * Date: 2018/5/21
 * Time: 下午4:50
 */

namespace App\Order\Modules\Service;



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
        return $this->reletRepository->setStatus($params);
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

}