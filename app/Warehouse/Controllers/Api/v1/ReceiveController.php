<?php

namespace App\Warehouse\Controllers\Api\v1;
use App\Lib\ApiStatus;
use App\Order\Modules\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ReceiveController extends Controller
{
    protected $OrderCreate;

    public function __construct(Service\OrderCreater $OrderCreate)
    {
        $this->OrderCreate = $OrderCreate;
    }


    public function receiveList(){
//        DB::connection('foo');
        echo "收货表列表接口";
    }

}




<?php

/**
 *
 * 商品品接口文件
 *
 * @author subin
 */
namespace Api\Shop\v2;

class SkuApi extends \Api\Shop\v2\Base {

    /**
     * 批量增加商品销量&产品销量
     *
     * @param array $arr
     *            array(0=>array('goodsId'=>'', 'prodSn'=>'', 'quantity'=>''))
     * @return array
     * @uses 订单支付成功后
     * @abstract 此函数更新更新缓存；使用事务
     */
    public static function addSalesNum(array $arr = array()) {
        // 整合数据
        $goodsIds = null;
        $prodSns = null;
        foreach ($arr as $v) {
            // 商品数据
            $_id = $v['goodsId'];
            if (isset($goodsIds[$_id])) {
                $goodsIds[$_id] += $v['quantity'];
            } else {
                $goodsIds[$_id] = $v['quantity'];
            }

            // 产品数据
            $_sn = $v['prodSn'];
            if (isset($prodSns[$_id])) {
                $prodSns[$_sn] += $v['quantity'];
            } else {
                $prodSns[$_sn] = $v['quantity'];
            }
        }

        // 统一时间
        $time = time();

        // 开启事务
        $dbShop = \Phalcon\DI::getDefault()->getDbShop();
        $dbShop->begin();

        // 商品列表
        $skuCache = null;
        $list = \Goods::query()->inWhere("id", array_keys($goodsIds))->execute();
        foreach ($list as $goodsObj) {
            // 增加的数量
            $_quantity = $goodsIds[$goodsObj->id];
            // 修改前的商品销量
            $beSalesNum = isset($goodsObj->salesNum) ? $goodsObj->salesNum : 0;
            // 更新
            $goodsObj->salesNum = $beSalesNum + $_quantity;
            $goodsObj->utime = $time;
            if (!$goodsObj->save()) {
                $dbShop->rollback();
                return parent::error('gid保存失败');
            }
            $skuCache[$goodsObj->id] = $goodsObj->salesNum;
        }

        // 统计产品销量
        $instr = \Api\Shop\v2\SpuSkuCommon::getMysqlInStr(array_keys($prodSns));
        $sql = "SELECT prodSn,SUM(salesNum) AS saleTotal FROM goods WHERE prodSn IN ({$instr}) GROUP BY prodSn";
        $goodsList = \Goods::querySql($sql);
        if (empty($goodsList)) {
            $dbShop->rollback();
            return parent::error('产品数据错误');
        }

        // 产品列表
        $spuCache = null;
        foreach ($goodsList as $sku) {
            // 修改product.saleTotal
            $updateSql = "UPDATE product SET saleTotal={$sku->saleTotal},utime={$time} WHERE prodSn = '{$sku->prodSn}';";
            $updated = \Goods::querySql($updateSql, true);
            if (!$updated) {
                $dbShop->rollback();
                return parent::error('产品保存失败');
            }
            $spuCache[$sku->prodSn] = $sku->saleTotal;
        }

        // 提交事务
        $dbShop->commit();

        // 商品缓存
        foreach ($skuCache as $gid => $salesNum) {
            $newData = null;
            $newData['salesNum'] = $salesNum;
            $newData['utime'] = $time;
            \Api\Shop\v2\MongoSku::saveById($gid, $newData);
        }

        // 产品缓存
        foreach ($spuCache as $prodSn => $saleTotal) {
            $newData = null;
            $newData['saleTotal'] = $saleTotal;
            $newData['utime'] = $time;
            \Api\Shop\v2\MongoSpu::saveBySn($prodSn, $newData);
        }

        return parent::success();
    }

    /**
     * 批量修改商品占有库存
     *
     * @param array $arr
     *            array(0=>array('goodsId'=>'','quantity'=>''))
     * @param string $type
     *            默认add(add:增加, reduce:减少, replace:替换)
     * @return array
     * @uses 下单成功 | 取消订单 | 确认发货
     * @abstract 此函数更新更新缓存 | 使用事务
     */
    public static function updateNum(array $arr = array(), $type = 'add') {
        $typeArr = array(
            'reduce',
            'add',
            'replace',
        );
        if (!in_array($type, $typeArr)) {
            return parent::error("type({$type}) 不存在");
        }

        // 整合数据
        $goodsIds = null;
        foreach ($arr as $v) {
            // 商品数据
            $_id = $v['goodsId'];
            if (isset($goodsIds[$_id])) {
                $goodsIds[$_id] += $v['quantity'];
            } else {
                $goodsIds[$_id] = $v['quantity'];
            }
        }

        // 统一时间
        $time = time();

        // 开启事务
        $dbShop = \Phalcon\DI::getDefault()->getDbShop();
        $dbShop->begin();

        // 商品列表
        $skuCache = array();
        $list = \Goods::query()->inWhere("id", array_keys($goodsIds))->execute();
        foreach ($list as $goodsObj) {
            // 数量
            $_quantity = $goodsIds[$goodsObj->id];
            // 修改前的占有库存
            $beNum = empty($goodsObj->num) ? 0 : $goodsObj->num;

            // 根据类型计算新的占有库存
            switch ($type) {
                case 'reduce':
                    $goodsObj->num = $beNum - $_quantity;
                    break;
                case 'add':
                    $goodsObj->num = $beNum + $_quantity;
                    break;
                case 'replace':
                    $goodsObj->num = $_quantity;
                    break;
            }
            $goodsObj->num = ($goodsObj->num > 0) ? $goodsObj->num : 0;
            // 如果与修改前相同
            if ($goodsObj->num == $beNum) {
                continue;
            }
            $goodsObj->utime = time();
            if (!$goodsObj->save()) {
                $dbShop->rollback();
                return parent::error('保存失败');
            }

            $skuCache[$goodsObj->id] = $goodsObj->num;
        }

        // 提交事务
        $dbShop->commit();

        // 商品缓存
        foreach ($skuCache as $gid => $num) {
            $newData = null;
            $newData['num'] = $num;
            $newData['utime'] = $time;
            \Api\Shop\v2\MongoSku::saveById($gid, $newData);
        }

        return parent::success();
    }

    /**
     * 批量修改商品库存（客访使用）
     *
     * @param array $arr
     *            array(0=>array('goodsId'=>'', 'prodSn'=>'','stock'=>''))
     * @param string $type
     *            默认add(add代表增加，reduce代表减少)
     * @return array
     * @uses 下单成功 | 取消订单 | 确认发货
     * @abstract 此函数更新更新缓存 | 使用事务
     */
    public static function updateStockForWeb(array $arr = array(), $type = 'add') {
        // 整合数据
        $goodsIds = null;
        $prodSns = null;
        foreach ($arr as $v) {
            // 商品数据
            $_id = $v['goodsId'];
            if (isset($goodsIds[$_id])) {
                $goodsIds[$_id] += $v['stock'];
            } else {
                $goodsIds[$_id] = $v['stock'];
            }

            // 产品数据
            $_sn = $v['prodSn'];
            if (isset($prodSns[$_id])) {
                $prodSns[$_sn] += $v['stock'];
            } else {
                $prodSns[$_sn] = $v['stock'];
            }
        }

        // 统一时间
        $time = time();

        // 开启事务
        $dbShop = \Phalcon\DI::getDefault()->getDbShop();
        $dbShop->begin();

        // 商品列表
        $skuCache = null;
        $list = \Goods::query()->inWhere("id", array_keys($goodsIds))->execute();
        foreach ($list as $goodsObj) {
            // 增加的数量
            $_stock = $goodsIds[$goodsObj->id];
            // 修改前的商品库存
            $beStock = isset($goodsObj->stock) ? $goodsObj->stock : 0;
            // 更新
            $goodsObj->stock = $beStock + $_stock;
            $goodsObj->utime = $time;
            if (!$goodsObj->save()) {
                $dbShop->rollback();
                return parent::error('商品保存失败');
            }

            $skuCache[$goodsObj->id] = $goodsObj->stock;

            // 修改商品库存日志
            $rlt = \Api\Shop\GoodsStockLog::addStockLog($goodsObj->goodsSn, $goodsObj->stock, 'update_kjt');
            if (!$rlt) {
                $dbShop->rollback();
                return parent::error('日志保存失败');
            }
        }

        // 统计产品库存
        $instr = \Api\Shop\v2\SpuSkuCommon::getMysqlInStr(array_keys($prodSns));
        $sql = "SELECT prodSn,SUM(stock) AS stock FROM goods WHERE prodSn IN ({$instr}) GROUP BY prodSn";
        $goodsList = \Goods::querySql($sql);
        if (empty($goodsList)) {
            $dbShop->rollback();
            return parent::error('产品数据错误');
        }

        // 产品列表
        $spuCache = null;
        foreach ($goodsList as $sku) {
            // 修改product
            $updateSql = "UPDATE product SET stock={$sku->stock},utime={$time} WHERE prodSn = '{$sku->prodSn}';";
            $updated = \Goods::querySql($updateSql, true);
            if (!$updated) {
                $dbShop->rollback();
                return parent::error('产品保存失败');
            }
            $spuCache[$sku->prodSn] = $sku->stock;
        }

        // 提交事务
        $dbShop->commit();

        // 商品缓存
        foreach ($skuCache as $gid => $stock) {
            $newData = null;
            $newData['stock'] = $stock;
            $newData['utime'] = $time;
            \Api\Shop\v2\MongoSku::saveById($gid, $newData);
        }

        // 产品缓存
        foreach ($spuCache as $prodSn => $stock) {
            $newData = null;
            $newData['stock'] = $stock;
            $newData['utime'] = $time;
            \Api\Shop\v2\MongoSpu::saveBySn($prodSn, $newData);
        }

        return parent::success();
    }

    /**
     * 批量修改商品库存（代理商&运营使用）
     *
     * @param array $arr
     *            array(0=>array('goodsSn'=>'','stock'=>''))
     * @param string $type
     *            操作动作
     *            update_base：从efast更新商品基本信息时候更新库存
     *            update_stock：直接从efast更新库存
     *            update_platform：大平台批量修改
     * @return array
     * @abstract 此函数更新更新缓存 | 使用事务
     */
    public static function updateStockForOther(array $arr = array(), $type = 'update_stock') {
        // 整合数据
        $goodsSns = null;
        $prodSns = null;
        foreach ($arr as $v) {
            // 商品数据
            $_sn = $v['goodsSn'];
            if (isset($goodsSns[$_sn])) {
                $goodsSns[$_sn] += $v['stock'];
            } else {
                $goodsSns[$_sn] = $v['stock'];
            }
        }

        // 统一时间
        $time = time();

        // 开启事务
        $dbShop = \Phalcon\DI::getDefault()->getDbShop();
        $dbShop->begin();

        //库存变化
        $stocks = array(); //'old'=>0,'new'=>0

        // 商品列表
        $skuCache = null;
        $list = \Goods::query()->inWhere("goodsSn", array_keys($goodsSns))->execute();
        foreach ($list as $goodsObj) {
            // 修改前的商品库存
            $beStock = isset($goodsObj->stock) ? $goodsObj->stock : 0;
            // 新商品库存
            $afStock = $goodsSns[$goodsObj->goodsSn];
            //记录更新前后库存数量
            $stocks[$goodsObj->goodsSn] = array();
            $stocks[$goodsObj->goodsSn] = array('goodsId' => $goodsObj->id, 'prodSn' => $goodsObj->prodSn, 'name' => $goodsObj->name, 'old' => $beStock, 'new' => $afStock);
            // 修改前和修改后的数差
            $diff = $afStock - $beStock;
            // 更新
            $goodsObj->stock = $afStock;
            $goodsObj->utime = $time;
            if (!$goodsObj->save()) {
                $dbShop->rollback();
                return parent::error('商品保存失败');
            }

            // 修改商品库存日志
            $rlt = \Api\Shop\GoodsStockLog::addStockLog($goodsObj->goodsSn, $goodsObj->stock, $type);
            if (!$rlt) {
                $dbShop->rollback();
                return parent::error('日志保存失败');
            }

            $skuCache[$goodsObj->id] = $goodsObj->stock;
            // 产品数据 - 计算每个产品的数差
            if (isset($prodSns[$goodsObj->prodSn])) {
                $prodSns[$goodsObj->prodSn] += $diff;
            } else {
                $prodSns[$goodsObj->prodSn] = $diff;
            }
        }

        // 统计产品库存
        $instr = \Api\Shop\v2\SpuSkuCommon::getMysqlInStr(array_keys($prodSns));
        $sql = "SELECT prodSn,SUM(stock) AS stock FROM goods WHERE prodSn IN ({$instr}) GROUP BY prodSn";
        $goodsList = \Goods::querySql($sql);
        if (empty($goodsList)) {
            $dbShop->rollback();
            return parent::error('产品数据错误');
        }

        // 产品列表
        $spuCache = null;
        foreach ($goodsList as $sku) {
            // 修改product
            $updateSql = "UPDATE product SET stock={$sku->stock},utime={$time} WHERE prodSn = '{$sku->prodSn}';";
            $updated = \Goods::querySql($updateSql, true);
            if (!$updated) {
                $dbShop->rollback();
                return parent::error('产品保存失败');
            }
            $spuCache[$sku->prodSn] = $sku->stock;
        }

        // 提交事务
        $dbShop->commit();

        // 商品缓存
        foreach ($skuCache as $gid => $stock) {
            $newData = null;
            $newData['stock'] = $stock;
            $newData['utime'] = $time;
            \Api\Shop\v2\MongoSku::saveById($gid, $newData);
        }

        // 产品缓存
        foreach ($spuCache as $prodSn => $stock) {
            $newData = null;
            $newData['stock'] = $stock;
            $newData['utime'] = $time;
            \Api\Shop\v2\MongoSpu::saveBySn($prodSn, $newData);
        }

        $msg = '修改库存成功';
        $notices = array();
        //库存变化通知(0=>x,x=>0)
        foreach ($stocks as $goodsSn => $stockInfo) {

            $skuSn = str_replace('_', '', $goodsSn);

            $info = array();

            $goodsName = htmlspecialchars_decode($stockInfo['name'], ENT_QUOTES);

            if (0 == $stockInfo['old'] && 0 != $stockInfo['new']) {
                $code = array('name' => $goodsName, 'skuSn' => $goodsSn);
                $info['title'] = '您所分销的商品有新到货';
                $info['goodsId'] = $stockInfo['goodsId'];
                $info['prodSn'] = $stockInfo['prodSn'];
                $info['code'] = $code;
                $info['type'] = 2;
            }
            if (0 != $stockInfo['old'] && 0 == $stockInfo['new']) {
                $code = array('name' => $goodsName, 'skuSn' => $goodsSn);
                $info['title'] = '您所分销的商品已售罄';
                $info['goodsId'] = $stockInfo['goodsId'];
                $info['prodSn'] = $stockInfo['prodSn'];
                $info['code'] = $code;
                $info['type'] = 1;
            }
            if ($info) {
                $notices[$goodsSn] = $info;
            }
        }

        if ($notices) {
            $ret = \ToQueue\NoticeCache::noticeGoodsNotice($notices);
            if (!$ret) {
                $msg .= '(发送商品动态失败)';
            } else {
                $msg .= '(发送商品动态成功)';
            }
        }
        return parent::success($notices, $msg);
    }

    /**
     * 中准价格调整，并记录日志
     *
     * @param string $goodsSn
     * @param float $bprice
     * @return boolean
     * @author zhangxianwen
     */
    public static function changeBasePrice($goodsSn, $bprice, $status = 0) {

        // 开启事务
        $dbShop = \Phalcon\DI::getDefault()->getDbShop();
        $dbShop->begin();

        // 下架操作
        $now = time();
        $para['conditions'] = "goodsSn=:goodsSn: ";
        $para['bind']['goodsSn'] = $goodsSn;
        $Goods = \Goods::findFirst($para);
        $Goods->basePrice = $bprice;
        $Goods->utime = $now;
        if (!$Goods->save()) {
            $dbShop->rollback();
            return parent::error("中准价更新失败！");
        }

        // 下架所有商品
        $Sql = "UPDATE `goods` SET `closeTime`=IF(`isShelved`=1, {$now},`closeTime`),`isShelved`=IF(`isShelved`=1,'-1',`isShelved`), `utime`='{$now}' WHERE prodSn = '{$Goods->prodSn}' ";

        $sku = \Goods::querySql($Sql, true);
        if (!$sku) {
            $dbShop->rollback();
            return parent::errorCode("中准价更新商品下架失败！");
        }

        // 下架产品
        $sql = "UPDATE `product` SET `closeTime`=IF(`isShelved`=1, {$now},`closeTime`),`isShelved`=IF(`isShelved`=1,'-1',`isShelved`), `utime`='{$now}' WHERE prodSn = '{$Goods->prodSn}' ";

        $spu = \Product::querySql($sql, true);
        if (!$spu) {
            $dbShop->rollback();
            return parent::errorCode("中准价更新产品下架失败！");
        }

        $dbShop->commit();

        $gcon['conditions'] = "prodSn=:prodSn: ";
        $gcon['bind']['prodSn'] = $Goods->prodSn;
        $GoodsList = \Goods::find($gcon);
        if ($GoodsList->valid()) {
            foreach ($GoodsList as $val) {
                // 更新所有商品缓存
                \Api\Shop\v2\MongoSku::saveById($val->id, array(
                    'basePrice' => $val->basePrice,
                    `isShelved`=> $val->isShelved,
                    `closeTime`=> $val->closeTime,
                    'utime' => $val->utime,
                ));
            }
        }

        // 更新产品缓存
        $pcon['conditions'] = "prodSn=:prodSn: ";
        $pcon['bind']['prodSn'] = $Goods->prodSn;
        $product = \Product::findFirst($pcon);
        \Api\Shop\v2\MongoSpu::saveBySn($product->prodSn, array(
            `isShelved`=> $product->isShelved,
            `closeTime`=> $product->closeTime,
            'utime' => $product->utime,
        ));

        // 获取反确认状态
        $blockSt = \Api\Shop\ProductBlock::checkBasePrice($Goods->prodSn);
        if ($blockSt) {
            // 解除中准价冻结
            \Api\Shop\ProductBlock::unBlockBasePrice($Goods->prodSn);
        }

        //得到店铺信息
        if ($Goods->shopId) {
            $shopUser = \Api\Shop\Shop::getInfoById($Goods->shopId);
        }
        $agentId = empty($shopUser) ? 0 : $shopUser['agentId'];

        $log = new \GoodsBasePriceLog();
        $item['agentId'] = $agentId;
        $item['prodSn'] = $Goods->prodSn;
        $item['goodsSn'] = $Goods->goodsSn;
        $item['price'] = $bprice;

        // 中准价变化 处理情况
        if ($blockSt == true && $status == -1) {
            // 因为中准价冻结的通知状态
            $item['sendStatus'] = 0;
        } else if ($status == 1) {
            // 在售的通知状态
            $item['sendStatus'] = 1;
        } else {
            // 已下架的通知状态
            $item['sendStatus'] = -1;
        }
        $item['ctime'] = $now;
        \Api\Shop\GoodsBasePriceLog::addLog($item);

        //取消分销
        \ToQueue\NoticeCache::noticeDealerCancel(array('spuSn' => $Goods->prodSn), $runTime = 0);

        //通知客服@email
        if ($status == 1) {
            $emailInfo = array();
            $emailInfo['type'] = 'soldOut';
            $emailInfo['pkId'] = 'spuSn';
            $emailInfo['pkVal'] = array($Goods->prodSn);
            \ToQueue\NoticeCache::noticeDealerEmail($emailInfo, $runTime = 0);
        }

        // 设置队列任务
        $api_url = SHOP_DOMAIN . '/Innerservice/basePriceChangeNotice/';
        $args = array(
            'aid' => $agentId,
        );
        // 延迟发通知 等多个商品积累到一起发，放到任务里不合适，以后在压力大的情况下要拆分任务，就不兼容了
        $delayTime = 25;
        \ToQueue\Common::toCommon($api_url, $args, 'get', true, $now + $delayTime);

        return parent::success();
    }

    /**
     * 删除商品和产品
     *
     * @param array $idArr
     *            商品编号集合
     * @return array
     * @uses 运营平台
     * @abstract 此函数更新更新缓存；使用事务
     */
    public static function deleteSkuSpu(array $idArr = array()) {
        // 对应的产品列表
        $skuStates = null;
        $skuStates['delState'] = 0;
        $skuFields = null;
        $skuFields = array(
            'id',
            'prodSn',
        );
        $idArr = array_unique($idArr);
        $ret = \Api\Shop\v2\Sku::getListByIds($idArr, $skuStates, $skuFields, 0);
        if ($ret['code'] != 0 || empty($ret['data'])) {
            return parent::errorCode(-24810);
        }
        $skuList = $ret['data'];
        if (count($skuList) != count($idArr)) {
            return parent::errorCode(-24811);
        }

        // 全部产品编号数组
        $spuArr = array();
        foreach ($skuList as $k => $sku) {
            $spuArr[$sku['prodSn']][] = $sku['id'];
        }
        $spuSns = \Api\Shop\v2\SpuSkuCommon::getMysqlInStr(array_keys($spuArr));
        $skuIds = \Api\Shop\v2\SpuSkuCommon::getMysqlInStr($idArr);
        // 部分删除
        $spuPartArr = null;
        // 全部删除
        $spuAllArr = null;

        // 统一时间
        $time = time();

        // 开启事务
        $dbShop = \Phalcon\DI::getDefault()->getDbShop();
        $dbShop->begin();

        // 商品sql
        $skuSql = "UPDATE `goods` SET  `delState`='1', `delTime`='{$time}', `utime`='{$time}'";
        $skuSql .= "WHERE id IN ({$skuIds}) AND delState=0";
        $spu = \Goods::querySql($skuSql, true);
        if (!$spu) {
            $dbShop->rollback();
            return parent::errorCode(-24814, "商品编号:({$skuIds})");
        }

        // 全部产品对应的未删除的商品
        $sql = "SELECT id, prodSn, delState, attrImg, price FROM goods WHERE prodSn IN ({$spuSns}) AND delState=0 GROUP BY prodSn";
        $goods = \Goods::querySql($sql);
        if (!empty($goods)) {
            foreach ($goods as $k => $gObj) {
                // 在部分删除中不存在
                if (!isset($spuPartArr[$gObj->prodSn])) {
                    // 加入部分删除
                    $spuPartArr[$gObj->prodSn] = $gObj->toArray();
                    // 去掉全部产品中部分删除的
                    unset($spuArr[$gObj->prodSn]);
                }
            }
        }
        // 全部删除等于全部产品中剩余
        $spuAllArr = empty($spuArr) ? array() : array_keys($spuArr);

        $spuPartCache = null;
        // 产品部分删除
        if (!empty($spuPartArr)) {
            foreach ($spuPartArr as $prodSn => $minSku) {
                $para = null;
                $para['conditions'] = "prodSn = :prodSn:";
                $para['bind']['prodSn'] = $prodSn;
                $pObj = \Product::findFirst($para);
                if (empty($pObj)) {
                    $dbShop->rollback();
                    return parent::errorCode(-24817, "产品货号:({$prodSn})");
                }

                // 设置新数据
                $pObj->delState = 1;
                $pObj->delTime = $time;
                $pObj->utime = $time;
                $pObj->dealer = 0;
                $pObj->dealerTime = 0;
                // 默认商品在本次删除
                if (in_array($pObj->defaultImg, $idArr)) {
                    $pObj->defaultImg = $minSku['id'];
                    $pObj->defaultPrice = $minSku['price'];
                    $pObj->coverImg = $minSku['attrImg'];
                }
                if (!$pObj->save()) {
                    $dbShop->rollback();
                    return parent::errorCode(-24815, "产品货号:({$spu['prodSn']})");
                }

                $spuPartCache[$prodSn] = $pObj->toArray();
            }
        }

        // 产品全部删除
        if (!empty($spuAllArr)) {
            $inStr = \Api\Shop\v2\SpuSkuCommon::getMysqlInStr($spuAllArr);
            $spuSql = "UPDATE product SET delState='2', delTime='{$time}', dealer='0', dealerTime='0',utime='{$time}' ";
            $spuSql .= "WHERE prodSn IN ({$inStr})";
            $spu = \Product::querySql($spuSql, true);
            if (!$spu) {
                $dbShop->rollback();
                return parent::errorCode(-24816, "产品货号:({$inStr})");
            }
        }

        //删除商品缺货登记
        $ret = \Api\Shop\GoodsLack::updateDelstateByGid($idArr, 1);
        if (!$ret) {
            $dbShop->rollback();
            return parent::errorCode(-24818);
        }

        // 提交事务
        $dbShop->commit();

        // 产品部分删除
        if (!empty($spuPartCache)) {
            foreach ($spuPartCache as $prodSn => $newData) {
                \Api\Shop\v2\MongoSpu::saveBySn($prodSn, $newData);
            }
        }

        // 产品全部删除
        if (!empty($spuAllArr)) {

            // 产品 缓存数据
            $newData = null;
            $newData['delState'] = '2';
            $newData['delTime'] = "{$time}";
            $newData['dealer'] = '0';
            $newData['dealerTime'] = "{$time}";
            $newData['utime'] = "{$time}";
            \Api\Shop\v2\MongoSpu::saveBySns($spuAllArr, $newData);
        }

        // 商品 缓存数据
        $newData = null;
        $newData['delState'] = '1';
        $newData['delTime'] = "{$time}";
        $newData['utime'] = "{$time}";
        \Api\Shop\v2\MongoSku::saveByIds($idArr, $newData);

        //取消分销
        if (!empty($spuAllArr) || !empty($spuPartArr)) {
            $all = empty($spuAllArr) ? array() : $spuAllArr;
            $part = empty($spuPartArr) ? array() : array_keys($spuPartArr);
            $sns = array_merge($all, $part);
            \ToQueue\NoticeCache::noticeDealerCancel(array('spuSn' => $sns));
        }

        return parent::success();
    }

    /**
     * 恢复删除的商品和产品
     *
     * @param array $idArr
     *            商品编号集合
     * @return array
     * @uses 运营平台
     * @abstract 此函数更新更新缓存；使用事务
     */
    public static function restoreSkuSpu(array $idArr = array()) {
        // 对应的产品列表
        $skuStates = array(
            'delState' => 1,
        );
        $skuFields = array(
            'id',
            'prodSn',
            'isShelved',
            'isNew',
            'attrImg',
            'price',
        );
        $idArr = array_unique($idArr);
        $ret = \Api\Shop\v2\Sku::getListByIds($idArr, $skuStates, $skuFields, 0);
        if ($ret['code'] != 0 || empty($ret['data'])) {
            return parent::errorCode(-24820);
        }
        $skuList = $ret['data'];
        if (count($skuList) != count($idArr)) {
            return parent::errorCode(-24821);
        }

        // 全部产品编号数组
        $spuArr = array();
        foreach ($skuList as $k => $sku) {
            $spuArr[$sku['prodSn']][$sku['id']] = $sku;
        }

        // 缓存
        $spuCache = null;
        $skuCache = null;
        // 统一时间
        $time = time();

        // 开启事务
        $dbShop = \Phalcon\DI::getDefault()->getDbShop();
        $dbShop->begin();

        // 全部产品
        $criteria = \Product::query();
        $criteria->inWhere("prodSn", array_keys($spuArr));
        $productList = $criteria->execute();
        foreach ($productList as $pObj) {
            // 当前产品下的商品集合按照商品id排序
            $tmpSkus = $spuArr[$pObj->prodSn];
            ksort($tmpSkus);
            // 每个产品还原的最小商品信息
            $minSku = array();

            // 遍历
            foreach ($tmpSkus as $gid => $skuInfo) {
                if (empty($minSku)) {
                    $minSku = $skuInfo;
                }

                // 商品缓存
                $skuCache[$gid]['delState'] = 0;
                $skuCache[$gid]['delTime'] = 0;
                $skuCache[$gid]['utime'] = $time;

                // 商品sql
                $skuSql = "UPDATE `goods` SET `delState`='0', `delTime`='0', `utime`='{$time}'";
                if ($pObj->isShelved) {
                    // 产品如果上架
                    $skuSql .= ", `isShelved`=0, `isNew`=1";
                    $skuCache[$gid]['isShelved'] = 0;
                    $skuCache[$gid]['isNew'] = 1;
                } elseif ($skuInfo['isShelved'] == 1) {
                    // 商品如果上架
                    $skuSql .= ", `isShelved`=0";
                    $skuCache[$gid]['isShelved'] = 0;
                }
                $skuSql .= " WHERE id ='{$gid}'";
                $ret = \Goods::querySql($skuSql, true);
                if (!$ret) {
                    $dbShop->rollback();
                    return parent::errorCode(-24822, "商品编号:({$gid})");
                }
            }

            // 产品是否有未删除的商品
            $para = null;
            $para['conditions'] = "prodSn = :prodSn: AND delState=1";
            $para['bind']['prodSn'] = $pObj->prodSn;
            $count = \Goods::count($para);
            if ($count > 0) {
                // 产品原来的删除状态为全部删除
                if ($pObj->delState == 2) {
                    $pObj->isPublished = 0;
                    $pObj->isShelved = 0;

                    // 产品默认商品在本次还原中不存在
                    if (!isset($spuArr[$pObj->prodSn][$pObj->defaultImg])) {
                        $para = null;
                        $para['conditions'] = "prodSn = :prodSn: AND delState=0";
                        $para['bind']['prodSn'] = $pObj->prodSn;
                        $minSku = \Goods::findFirst($para)->toArray();
                    }
                    $pObj->defaultImg = $minSku['id'];
                    $pObj->defaultPrice = $minSku['price'];
                    $pObj->coverImg = $minSku['attrImg'];
                } // 产品原来的删除状态为部分删除
                elseif ($pObj->delState == 1) {
                    // 如果已经上架
                    if ($pObj->isShelved == 1) {
                        $pObj->hasNew = 1;
                    }
                }

                // 设置新数据
                $pObj->utime = $time;
                $pObj->delState = 1;
                $pObj->dealer = 0;
                if (!$pObj->save()) {
                    $dbShop->rollback();
                    return parent::errorCode(-24823, "产品货号:({$pObj->prodSn})");
                }
            } else {
                // 如果已经上架
                if ($pObj->isShelved == 1) {
                    $pObj->hasNew = 1;
                    // 原来是全部删除
                    if ($pObj->delState == 2) {
                        $pObj->isPublished = 0;
                        $pObj->isShelved = 0;
                    }
                }
                // 设置新数据
                $pObj->delState = 0;
                $pObj->delTime = 0;
                $pObj->dealer = 0;
                $pObj->utime = $time;
                if (!$pObj->save()) {
                    $dbShop->rollback();
                    return parent::errorCode(-24824, "产品货号:({$pObj->prodSn}})");
                }
            }

            // 产品缓存
            $spuCache[$pObj->prodSn] = $pObj->toArray();
        }

        //还原商品缺货登记
        $ret = \Api\Shop\GoodsLack::updateDelstateByGid($idArr, 0);
        if (!$ret) {
            $dbShop->rollback();
            return parent::errorCode(-24827);
        }

        // 提交事务
        $dbShop->commit();

        // 产品缓存
        if (!empty($spuCache)) {
            foreach ($spuCache as $prodSn => $newData) {
                \Api\Shop\v2\MongoSpu::saveBySn($prodSn, $newData);
            }
        }

        // 商品缓存
        if (!empty($skuCache)) {
            foreach ($skuCache as $gid => $newData) {
                \Api\Shop\v2\MongoSku::saveById($gid, $newData);
            }
        }

        // 取消分销
        if (!empty($spuArr)) {
            \ToQueue\NoticeCache::noticeDealerCancel(array('spuSn' => array_keys($spuArr)));
        }

        return parent::success();
    }

    /**
     * 修改商品快照版本号
     *
     * @param number $id
     * @param number $version
     * @return boolean
     */
    public static function updateGoodsVersion($id = 0, $version = 0) {
        if (empty($id) || !is_numeric($id) || empty($version) || !is_numeric($version)) {
            return parent::error('参数错误');
        }
        $now = time();

        $Goods = \Goods::findFirstById($id);
        if (empty($Goods)) {
            return parent::error('商品不存在');
        }

        $Goods->version = $version;
        $Goods->utime = $now;
        if (!$Goods->save()) {
            return parent::error('保存失败');
        }

        // 通知更新缓存
        $data = array(
            'utime' => $now,
            'version' => $version,
        );
        \Api\Shop\v2\MongoSku::saveById($id, $data);
        return parent::success();
    }
}

