<?php

/*
 * 蚂蚁金服 金融科技 可信存证 数据处理 封装
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 * @copyright (c) 2018, Huishoubao
 */

namespace App\Lib\Alipay\Notary;

/**
 * NotaryDataHandler 可信存证 数据处理接口声明
 * @access public
 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
 */
interface NotaryDataHandler {
	
	
	/**
	 * 创建 存证事务
	 * @param \App\Lib\Alipay\Notary\NotaryTransaction $transation
	 * @return bool	true：成功；false：失败
	 */
	public function createTransaction(NotaryTransaction $transation):bool;
	
	/**
	 * 查询 存证事务
	 * @param int $business_type	业务类型
	 * @param string $business_no	业务数据标识
	 * @param \App\Lib\Alipay\Notary\NotaryTransaction $transation		查询结果写入该引用
	 * @return bool	true：查询成功；false：未找到
	 */
	public function queryTransaction(int $business_type, string $business_no, NotaryTransaction &$transation=null): bool;
	
	
	/**
	 * 创建 存证
	 * @param \App\Lib\Alipay\Notary\Notary $notary
	 * @return bool	true：成功；false：失败
	 */
	public function createNotary(Notary $notary):bool;
	
	/**
	 * 查询 存证
	 * @param array $where
	 * [
	 *		'id' => '',
	 *		'txHash' => '',
	 * ]
	 * @param \App\Lib\Alipay\Notary\Notary $notary
	 * @return bool	true：查询成功；false：未找到
	 */
	public function queryNotary(array$where, Notary &$notary=null):bool;
	
	
	
}
