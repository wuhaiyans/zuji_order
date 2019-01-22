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
class NotaryDataHandler {
	
	
	/**
	 * 创建 存证事务
	 * 主键ID为0时才允许写入数据库
	 * @param \App\Lib\Alipay\Notary\NotaryTransaction $transaction
	 * @return bool	true：成功；false：失败
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 */
	public function createTransaction(NotaryTransaction $transaction):bool{
		if( $transaction->getId() ){
			return false;
		}
		$model = new DataModel\NotaryTransaction();
		$id = $model->insertGetId([
			'transaction_token' => $transaction->getToken(),
			'account_id' => $transaction->getAccountId(),
			'order_no' => $transaction->getOrderNo(),
			'goods_no' => $transaction->getGoodsNo(),
			'customer' => json_encode([
				'certNo' => $transaction->getCustomer()->getCertNo(),
				'certName' => $transaction->getCustomer()->getCertName(),
				'mobileNo' => $transaction->getCustomer()->getMobileNo(),
				'properties' => $transaction->getCustomer()->getProperties(),
			]),
			'create_time' => time(),
		]);
		$transaction->setId( $id );
		return $id>1 ? true : false;
	}
	
	/**
	 * 查询 存证事务
	 * @param int	主键ID
	 * @param \App\Lib\Alipay\Notary\NotaryTransaction $transaction		查询结果写入该引用
	 * @return bool	true：查询成功；false：未找到
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 */
	public function queryTransactionById(int $id, NotaryTransaction &$transaction=null): bool{
		$model = new DataModel\NotaryTransaction();
		$info = $model->where([
			'id'=> $id,
		])->first();
		if( !$info ){
			return false;
		}
		$transaction = $this->_transformToNotaryTransaction($info);
		return true;
	}
	
	/**
	 * 查询 存证事务
	 * @param string $transaction_token		存在事务ID
	 * @param \App\Lib\Alipay\Notary\NotaryTransaction $transaction		查询结果写入该引用
	 * @return bool	true：查询成功；false：未找到
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 */
	public function queryTransactionByToken(string $transaction_token, NotaryTransaction &$transaction=null): bool{
		$model = new DataModel\NotaryTransaction();
		$info = $model->where([
			'transaction_token'=> $transaction_token,
		])->first();
		if( !$info ){
			return false;
		}
		$transaction = $this->_transformToNotaryTransaction($info);
		return true;
	}
	
	/**
	 * 查询 存证事务
	 * @param string $order_no		订单编号
	 * @param string $goods_no		商品编号
	 * @param \App\Lib\Alipay\Notary\NotaryTransaction $transaction		查询结果写入该引用
	 * @return bool	true：查询成功；false：未找到
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 */
	public function queryTransactionByBusiness(string $order_no, string $goods_no, NotaryTransaction &$transaction=null): bool{
		$model = new DataModel\NotaryTransaction();
		$info = $model->where([
			'order_no'=> $order_no,
			'goods_no'=> $goods_no,
		])->first();
		if( !$info ){
			return false;
		}
		$transaction = $this->_transformToNotaryTransaction($info);
		return true;
	}
	
	
	
	/**
	 * 创建 存证
	 * @param \App\Lib\Alipay\Notary\Notary $notary
	 * @return bool	true：成功；false：失败
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 */
	public function createNotary(Notary $notary):bool{
		$model = new DataModel\NotaryPhase();
		$id = $model->insertGetId([
			'transaction_id'	=> $notary->getTransactionId(),
			'txhash'			=> $notary->getTxHash(),
			'transaction_token'	=> $notary->getTransactionToken(),
			'phase'				=> $notary->getPhase(),
			'type'				=> $notary->getType(),
			'content_hash'		=> $notary->getContentHash(),
			'content'			=> $notary->getContent(),
			'meta'				=> json_encode($notary->getMeta()->toArray()),
			'create_time'		=> time(),
			'upload_time'		=> 0,
		]);
		$notary->setId( $id );
		return $id>1 ? true : false;
	}
	
	/**
	 * 存在上链
	 * @param \App\Lib\Alipay\Notary\Notary $notary
	 * reutrn bool
	 */
	public function uploadNotary(Notary $notary):bool{
		$model = new DataModel\NotaryPhase();
		$n = $model->where( ['id' => $notary->getId()] )
				->limit(1)
				->update([
					'txhash'		=> $notary->getTxHash(),
					'meta'			=> json_encode($notary->getMeta()->toArray()),
					'upload_time'	=> time(),
				]);
		return $n!==false;
	}
	
	/**
	 * 查询 存证
	 * @param array $where
	 * [
	 *		'id' => '',
	 *		'txHash' => '',
	 * ]
	 * @param \App\Lib\Alipay\Notary\Notary $notary
	 * @return bool	true：查询成功；false：未找到
	 * @access public
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 */
	public function queryNotary(array $where, Notary &$notary=null):bool{
		$_where = [];
		if( isset($where['id']) ){
			$_where['id'] = $where['id'];
		}
		elseif( isset($where['txHash']) ){
			$_where['txhash'] = $where['txHash'];
		}
		if( !count($_where) ){
			return false;
		}
		$model = new DataModel\NotaryPhase();
		$info = $model->where($_where)->first();
		if( !$info ){
			return false;
		}
		$meta = json_decode($info->meta,true);
		
		$notary = new Notary($info->id,$info->transaction_id, $info->transaction_token, $info->phase, $info->txhash, $info->type, $info->content, $info->content_hash, NotaryMeta::fromArray($meta));
		return true;
	}

	
	/**
	 * 转换为事务对象
	 * @param type $info
	 * @return \App\Lib\Alipay\Notary\NotaryTransaction
	 * @access private
	 * @author liuhongxing <liuhongxing@huishoubao.com.cn>
	 */
	private function _transformToNotaryTransaction( $info ){
		$customer_info = json_decode($info->customer,true);
		
		$customer = new CustomerIdentity();
		$customer->setCertNo($customer_info['certNo']);
		$customer->setCertName($customer_info['certName']);
		$customer->setMobileNo($customer_info['mobileNo']);
		$customer->setProperties($customer_info['properties']);
		
		$transaction = new NotaryTransaction($info->id, $info->order_no, $info->goods_no, $info->account_id, $info->transaction_token, $customer);
		return $transaction;
	}
	
	
	
}
