<?php
/**
 * 用户组件
 * @access public
 * @author 
 * @copyright (c) 2018, Huishoubao
 */

namespace App\Order\Modules\Repository\Order\Creater\Component;

use App\Order\Modules\Repository\Order\Creater\OrderCreaterComponent;

class UserComponent extends OrderCreaterComponent
{

    /**
	 * 用户ID
	 * @var int
	 */
    private $user_id;



    /**
     * 过滤
	 * 判断用户是否满足下单条件
     * @return bool
     */
    public function filter( array $params ): bool
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