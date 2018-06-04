<?php
/**
 * 用户组件
 * @access public
 * @author 
 * @copyright (c) 2018, Huishoubao
 */

namespace App\Order\Modules\Repository\Order\Creater;


class UserComponnet extends OrderCreaterComponnet
{

    /**
	 * 用户ID
	 * @var int
	 */
    private $user_id;


	/**
	 * 
	 * @param \App\Order\Modules\Repository\OrderCreater\OrderCreaterInterface $componnet
	 * @param int $user_id
	 * @throws \Exception
	 */
    public function __construct( OrderCreaterInterface $componnet, int $user_id ){
		parent::__construct($componnet);
		if( $user_id < 1 ){
			throw new \Exception('[用户ID]错误');
		}
		$this->user_id = $user_id;
	}

    /**
     * 过滤
	 * 判断用户是否满足下单条件
     * @return bool
     */
    public function filter(): bool
    {
        return true;
    }

    /**
     * 用户基本信息
     * @return array
     */
    public function getDataSchema(): array
    {
        return [
            'user' => [
                'user_id' => $this->userId,
                'user_mobile' => $this->mobile,
            ],
        ];

    }

    /**
     * 
     * @return bool
     */
    public function create(): bool
    {
        return true;
    }

}