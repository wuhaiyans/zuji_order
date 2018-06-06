<?php
/**
 * SKU 组件
 * @access public
 * @author 
 * @copyright (c) 2018, Huishoubao
 */

namespace App\Order\Modules\Repository\Order\Creater\Component;

use App\Order\Modules\Repository\Order\Creater\OrderCreaterComponent;

class SkuComponent extends OrderCreaterComponent
{

    /**
	 * sku信息列表
	 * @var array	二维数组
	 * [
	 *		[
	 *			'sku_id'	=> '',	//【必须】int SKU ID
	 *			'num'		=> '',	//【必须】int 数量
	 *		]
	 * ]
	 */
	private $sku_info_list = [];
	


    /**
     * 过滤
	 * 计算 sku 基本数据
     * @return bool true：成功；false：失败
     */
    public function filter( array $params ): bool
    {
        if( !parent::filter( $params ) ){
            return false;
        }
        //

        return true;
    }

    /**
     * 用户基本信息
     * @return array
	 * [
	 * 
	 * ]
     */
    public function getDataSchema(): array
    {
        return [
            'sku' => $this->sku_info_list,
        ];

    }

    /**
     * 保存商品清单
     * @return bool
     */
    public function create(): bool
    {
		
        return true;
    }

}