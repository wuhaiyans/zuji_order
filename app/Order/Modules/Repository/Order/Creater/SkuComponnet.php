<?php
/**
 * SKU 组件
 * @access public
 * @author 
 * @copyright (c) 2018, Huishoubao
 */

namespace App\Order\Modules\Repository\Order\Creater;


class SkuComponnet extends OrderCreaterComponnet
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
	 * 构造函数
	 * @param \App\Order\Modules\Repository\OrderCreater\OrderCreaterInterface $componnet
	 * @param	array $sku_list	sku列表参数 
	 * [
	 *		[
	 *			'sku_id'	=> '',	//【必须】int SKU ID
	 *			'num'		=> '',	//【必须】int 数量
	 *		]
	 * ]
	 * @throws \Exception
	 */
    public function __construct( OrderCreaterInterface $componnet, array $sku_list ){
		parent::__construct($componnet);
		if( count($sku_list) < 1 ){
			throw new \Exception('[sku]错误');
		}
		
		foreach( $sku_list as $it ){
			$this->sku_info_list[] = [
				'sku_id'	=> $it['sku_id'],
				'num'		=> $it['num'],
			];
		}
	}

    /**
     * 过滤
	 * 计算 sku 基本数据
     * @return bool true：成功；false：失败
     */
    public function filter(): bool
    {
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