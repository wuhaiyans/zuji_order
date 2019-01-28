<?php
namespace App\Tool\Modules\Service\Coupon;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\Common\SmsApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Tool\Modules\Repository\Coupon\Coupon;
use App\Tool\Modules\Service\Coupon\CouponServiceInterface;
use App\Tool\Models\CouponModel;
use App\Tool\Modules\Repository\Coupon\CouponUser;
use App\Tool\Modules\Repository\GreyTest\GreyTest;
use App\Tool\Modules\Func\Func;
use App\Tool\Modules\Inc\CouponStatus;
use App\Tool\Modules\Repository\Coupon\CouponSpu;
use App\Lib\Tool\Tool;

class CouponService
{
    protected $coupon = [];

    public function __construct(coupon $couponModel)
    {
        $this->coupon = $couponModel;
    }
    
    /**
     * 获取couponModel元数据
     * @return array
     */
    public function getData() : array
    {
        return $this->coupon->toArray();
    }
    
    /**
     * 根据model_no获取一条数据
     * @param string $modelNo
     * @return self
     */
    public static function getCouponModelDetailByNo(string $modelNo) : CouponService
    {
        //数据正常后打开注释
        //if( strlen($modelNo) == 16 ){
            $coupon = Coupon::getDetailByNo($modelNo);
            return new self($coupon);
        //}
    }
    
    /**
     * 发布
     * @return bool
     */
    public function publishCouponModel() : bool
    {
        //事务处理FIXME
        $status = $this->coupon->getAttribute('status');
        if($status == 0 || $status == 4){
            $model_no = $this->coupon->getAttribute('model_no');
            //停止灰度发布
            $GreyTest = GreyTest::getOne(['model_no'=>$model_no , 'status'=>1]);
            if($GreyTest != null){
                $GreyTest->stop();
            }
            //$GreyTest = GreyTest::newModel();
            //$GreyTest->setAttribute('model_no',$model_no);
            //$GreyTest->getOneByMobile()->stop();
            //生成code
            $this->createCode($this->coupon->getAttribute('issue_num'), $model_no);
            //设置发布
            $this->coupon->setAttribute('status',1);
            return $this->coupon->saveCoupon();
        }else{
            return false;
        }
    }
    
    /**
     * 停止发布
     * @return bool
     */
    public function stopCouponModel() : bool
    {
        $status = $this->coupon->getAttribute('status');
        if($status == 1){
            $this->coupon->setAttribute('status',2);
            return $this->coupon->saveCoupon();
        }else{
            return false;
        }
    }
    
    /**
     * 删除
     * @return bool
     */
    public function deleteCouponModel() : bool
    {
        $status = $this->coupon->getAttribute('status');
        if($status == 0){
            $this->coupon->setAttribute('status',3);
            return $this->coupon->saveCoupon();
        }else{
            return false;
        }
    }
    
    /**
     * 灰度发布
     * @param string $mobile
     * @return bool
     */
    public function greyTestCouponModel(string $mobile = '') : bool
    {
        //事务处理FIXME
        $status = $this->coupon->getAttribute('status');
        if($status == 0 && $mobile){
            $model_no = $this->coupon->getAttribute('model_no');
            //生成code
            $this->createCode($this->coupon->getAttribute('issue_num'), $this->coupon->getAttribute('model_no'));
            //插入灰度
            GreyTest::create($mobile, $model_no);
            //设置灰度状态
            $this->coupon->setAttribute('status',4);
            return $this->coupon->saveCoupon();
        }else{
            return false;
        }
    }
    
    /**
     * 取消灰度发布至草稿状态
     * @return bool
     */
    public function cancelGreyTest() : bool
    {
        //事务处理FIXME
        $status = $this->coupon->getAttribute('status');
        if($status == 4){
            $model_no = $this->coupon->getAttribute('model_no');
            //取消灰度
            GreyTest::getOne(['model_no'=>$model_no , 'status'=>1])->stop();
            //设置灰度状态
            $this->coupon->setAttribute('status',0);
            return $this->coupon->saveCoupon();
        }else{
            return false;
        }
    }
    
    /**
     * 更新草稿状态下的优惠券模型
     * @param array $params ['start_time'=>1111111111,'end_time'=>222222222]
     * @return boolean
     */
    public function updateCouponModel(array $params) : bool
    {
        if($this->coupon->status == 0){
            $setter = $this->setter($params);
            if($setter){
                return $this->coupon->saveCoupon();
            }
        }
        return false;
    }
    
    /**
     * 创建优惠券模型
     * @param array $params
     */
    public function addCouponModel(array $params) : array
    {
        //事务处理FIXME
        //1.参数校验
        $checked = self::checkAddParams($params);
        if($checked){
            //2.设置参数
            $params = self::setAddParams($params);
            //3.组织模型数据
            $modelNo = func::md5_16();
            $modelData = [
                'model_no'=>func::md5_16(),
                'coupon_name'=>$params['coupon_name'],
                'coupon_type'=>$params['coupon_type'],
                'range_user'=>$params['range_user'],
                'site'=>$params['site'],
                'describe'=>$params['describe'],
                'only_id'=>"1",//coupon\CouponFunction::get_uuid(),
                'status'=>$params['status'],
                'scope'=>$params['scope'],
                'create_time'=>time(),
                'coupon_value' => $params['coupon_value'],
                'use_restrictions' => $params['use_restrictions'],
                'start_time'=>$params['start_time'],
                'end_time'=>$params['end_time'],
                'user_start_time'=>$params['user_start_time'],
                'user_end_time'=>$params['user_end_time'],
                'user_day'=>$params['user_day'],
                'issue_num'=>$params['issue_num'],
            ];
            //4.组织关联数据
            if(isset($params['channel_ids'])){//如果指定渠道所有商品，支持多个
                foreach($params['channel_ids'] as $key => $channel_id){
                    $spus = [['id'=>1],['id'=>2],['id'=>3]];//调用商品接口获取SPUS where channel_id=$val,status=1; selectField=id FIXME
                    if($spus){
                        foreach($spus as $spu_id){
                            $couponSpuData[] = [
                                'model_no'=>$modelNo,
                                'spu_id'=>$spu_id,
                                'channel_id'=>$channel_id,
                            ];
                        }
                    }
                }
            }elseif(isset($params['range_spu'])){//如果指定商品
                foreach($params['range_spu'] as $spu_id=>$channel_id){//range_spu 数据格式[[$spu_id=>$channel_id],[$spu_id=>$channel_id]]
                    $couponSpuData[] = [
                        'model_no'=>$modelNo,
                        'spu_id'=>$spu_id,
                        'channel_id'=>$channel_id,
                    ];
                }
            }
            //5.存储 事务保证
            if($modelData && $couponSpuData){
                //先存模型再存关联数据
                $resultModel = Coupon::addOne($modelData);
                $resultSpu = CouponSpu::add($couponSpuData);
                $result = $resultModel && $resultSpu;
            }
            if($result){
                //提交事务
                set_apistatus(\App\Lib\ApiStatus::CODE_0);
                return [];
            }else{
                //回滚事务
                set_apistatus(\App\Lib\ApiStatus::CODE_50000, '添加优惠券类型失败');
                return [];
            }
        }
        set_apistatus(\App\Lib\ApiStatus::CODE_20001, '参数错误');
        return [];
    }
    
    /*
     * 导入用户
     */
    public function importUserCoupon()
    {
        $model_no = $this->coupon->getAttribute('model_no'); //$model_no instead of $coupon_id
        $tmp_file = $_FILES['file_data']['tmp_name']; //临时文件
        $code = 0;
        $msg = '';
        
        if(!$tmp_file){
            set_apistatus(\App\Lib\ApiStatus::CODE_50000, '文件不存在');
            return [];
        }
        
        $time = time();
        $user_day = $this->coupon->getAttribute('user_day');
        if($user_day){//天换秒
            $start_time = $time;
            $end_time = $time + $user_day * 86400;
        }else{
            $start_time = $this->coupon->getAttribute('user_start_time');
            $end_time = $this->coupon->getAttribute('user_end_time');
        }
        
        $path = $tmp_file;
        //$path = "C:\Users\Administrator\Desktop\aaa.csv";
        $file = fopen($path, 'r');
        $data = [];
        while ($one = fgetcsv($file)) {
            $data = array_merge($data,$one);
        };
        fclose($file);
        
        $num = count($data);
        if($num <= 0){
            set_apistatus(\App\Lib\ApiStatus::CODE_50000, '文件内容错误');
            return [];
        }
        
        $where = ['model_no'=>$model_no,'status'=>0,'mobile'=>'0','is_lock'=>CouponStatus::CouponLockWei];
        $coupon_code_data = CouponUser::getCoupon($where,$num)->toArray();
        
        //优惠券个数是否等于用户个数
        if(count($coupon_code_data) < $num){
            set_apistatus(\App\Lib\ApiStatus::CODE_50000, '优惠券数量不足');
            return [];
        }
        
        //开启事务
        DB::beginTransaction();
        
        $retData['new_user'] = [];
        $retData['mobile_error'] = [];
        foreach($coupon_code_data as $key => $coupon_code){
            //手机号格式错误
            if(!self::checkMobileValidity($data[$key])){
                $retData['mobile_error'][] = strval($data[$key]);
                continue;
            }
            // 查询用户信息
            $userInfo  = Tool::getIsMember($data[$key]);//调用商品系统接口查询此手机是否注册,返回用户信息 
            if(!$userInfo){
                $retData['new_user'][] = strval($data[$key]);
                continue;
            }
            //查询该用户是否已拥有此优惠券
            $userCoupon = CouponUser::getCount(['mobile'=>$userInfo['mobile'] , 'model_no'=>$model_no]);
            if($userCoupon){
                continue;
            }
            
            //锁码
            $couponUser = CouponUser::getOne($coupon_code['id']);
            $couponUser->setAttribute('is_lock', 1);
            $couponUser->setAttribute('lock_time', time());
            $status = $couponUser->setLock();
            if(!$status){
                DB::rollBack();
                set_apistatus(\App\Lib\ApiStatus::CODE_50000, '优惠券兑换码锁定失败');
                return [];
            }
            
            //更新user_coupon
            $couponUser->mobile = $userInfo['mobile'];
            $couponUser->user_id = $userInfo['id'];
            $couponUser->create_time = time();
            $couponUser->import = 1;
            $status = $couponUser->setUserCoupon(); 
            if(!$status){
                DB::rollBack();
                set_apistatus(\App\Lib\ApiStatus::CODE_50000, '优惠券导入失败');
                return [];
            }
        }
        
        //提交事物
        DB::commit();
        set_apistatus(\App\Lib\ApiStatus::CODE_0);
        return [];
    }
    
    /*
     * 获取兑换码
     */
    public function getCouponNo($modelNo , $modelName ,$num)
    {
        $time = time();
        // 不限制超时时间
        @set_time_limit(0);
        // 内存2M
        @ini_set('memory_limit', 20*1024*1024);
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment;filename='.'优惠券code表'.time().'-'.rand(1000, 9999).'.csv');
        header('Cache-Control: max-age=0');
        
        $message = '';
        if($num > 50){
            $num = 50;
            $message = '最多导出优惠券兑换码数量为50';
        }
        //查询剩余可获取的兑换码
        $where = ['model_no'=>$modelNo,'status'=>0,'mobile'=>'0','is_lock'=>CouponStatus::CouponLockWei];
        $coupons = CouponUser::getCoupon($where,$num);
        if(count($coupons) < $num){
            $message = '最多可在生成'.count($coupons).'张优惠券';
        }
        
        $handle = fopen('php://output', 'a');
        //存储时间数组(最后导出数组)
        $header_data = array(
            $modelName,
            $message,
        );
        //输出头部数据
        $this->export_csv_wirter_row($handle, $header_data);
        foreach($coupons as $k=>$v){
            $_row = [
                "\t" . $v['coupon_no'],
            ];
            $this->export_csv_wirter_row($handle, $_row);
            CouponUser::getOne($v['id'])->setLock();
        }
        ob_flush();
        flush();
        fclose($handle);
    }
    
    private static function checkAddParams(array $params)
    {
        if(count($params)<6){
            set_apistatus(\App\Lib\ApiStatus::CODE_20001, '参数错误');
            return [];
        }
        
        if(empty($params['start_time'])){
            set_apistatus(\App\Lib\ApiStatus::CODE_50000, '优惠券领取时间不能为空');
            return [];
        }
        if(empty($params['end_time'])){
            set_apistatus(\App\Lib\ApiStatus::CODE_50000, '优惠券领取时间不能为空');
            return [];
        }
        
        if(empty($params['user_day'])){
            if(empty($params['user_end_time'])){
                set_apistatus(\App\Lib\ApiStatus::CODE_50000, '优惠券核销时间不能为空');
                return [];
            }
            if(empty($params['user_start_time'])){
                set_apistatus(\App\Lib\ApiStatus::CODE_50000, '优惠券核销时间不能为空');
                return [];
            }
            
            if( $params['end_time'] > $params['user_end_time'] ){
                set_apistatus(\App\Lib\ApiStatus::CODE_50000, '优惠券领取时间大于核销时间');
                return [];
            }
            if($params['user_start_time'] >= $params['end_time']){
                set_apistatus(\App\Lib\ApiStatus::CODE_50000, '用户核销的开始时间不能大于等于优惠券领取的结束时间');
                return [];
            }
        }
        
        if(empty($params['issue_num'])){
            set_apistatus(\App\Lib\ApiStatus::CODE_50000, '优惠券发放数量不能为空');
            return [];
        }
        return true;
    }
    
    private static function setAddParams(array $params)
    {
        if($params['coupon_type']==CouponStatus::CouponTypePercentage || $params['coupon_type']==CouponStatus::CouponTypeFirstMonthRentFree){
            $data['coupon_value'] = $params['coupon_value'];
        }elseif ($params['coupon_type']==CouponStatus::CouponTypeFullReduction){
            $data['coupon_value'] = $params['coupon_value']*100;
            $data['use_restrictions'] = $params['use_restrictions']*100;
        }else{
            $data['coupon_value'] = $params['coupon_value']*100;
        }
        
        if($params['site']==CouponStatus::SiteActive){
            $params['start_time']=empty($params['start_time'])?0:strtotime($params['start_time']);
            $params['end_time']=empty($params['end_time'])?0:strtotime($params['end_time']);
            $params['user_start_time']=empty($params['user_start_time'])?0:strtotime($params['user_start_time']);
            $params['user_end_time']=empty($params['user_end_time'])?0:strtotime($params['user_end_time']);
            $params['user_day']=empty($params['user_day'])?0:$params['user_day'];
            $params['issue_num']=empty($params['issue_num'])?0:$params['issue_num'];
        }elseif ($params['site']==CouponStatus::SiteOut){
            $params['user_start_time']=empty($params['user_start_time'])?0:strtotime($params['user_start_time']);
            $params['user_end_time']=empty($params['user_end_time'])?0:strtotime($params['user_end_time']);
            $params['issue_num']=empty($params['issue_num'])?0:$params['issue_num'];
        }
        
        return $params;
    }
    
    /*
     * 生成16位coupon_no 并且插入
     * @param int $num
     * @param string $model_no
     * @return boolean
     */
    private function createCode(int $num , string $model_no)
    {
        if(!$num || !$model_no){
            return false;
        }
        $time = time();
        //count 已发行的兑换码数量
        $count_coupon_code_item = CouponUser::getCount(['model_no'=>$model_no]);
        if($count_coupon_code_item < $num){
            $num = $num - $count_coupon_code_item;//兼容补发
            $couponCodeArr = [];
            $whileNum = 0;
            while($whileNum < $num){
                $couponCodeArr[] = Func::md5_16();
                $couponCodeArr = array_unique($couponCodeArr);
                $whileNum = count($couponCodeArr);
            }
            $couponCodeArr = array_merge($couponCodeArr,[]);
            if($couponCodeArr){
                $user_day        = $this->coupon->getAttribute('user_day');
                if($user_day){//天换秒
                    $start_time = $time;
                    $end_time = $time + $user_day * 86400;
                }else{
                    $start_time = $this->coupon->getAttribute('user_start_time');
                    $end_time = $this->coupon->getAttribute('user_end_time');
                }
                $mod = 100;
                foreach($couponCodeArr as $key => $code){
                    //组合values一次性插入多条数据
                    $data[] = [
                        'model_no'   => $model_no,
                        'coupon_no'  => $code,
                        'start_time' => $start_time,
                        'end_time'   => $end_time,  
                    ];
                    //取模，避免一次性大量插入
                    if(($key-1) >= $mod){
                        if($key%$mod == 0){
                            CouponUser::addAll($data);
                            $data = [];
                        }
                    }
                }
                if($data){
                    CouponUser::addAll($data);
                }
            }
            return true;
        }
        return false;
    }
    
    /*
     * 数据模型setter
     * @param array $setFields ['start_time'=>1111111111,'end_time'=>222222222]
     * @return boolean
     */
    private function setter(array $setFields)
    {
        if( is_array($setFields) && !empty($setFields) ){
            foreach($setFields as $fields => $value){
                if( in_array($fields , $this->coupon->getColumnsNames()) ){
                    $this->coupon->setAttribute($fields, $value);
                }else{
                    return false;
                }
            }
        }
        return $this->coupon;
    }
    
    private function export_csv_wirter_row($handle , $row){
        foreach ($row as $key => $value) {
            //$row[$key] = iconv('utf-8', 'gbk', $value);
            $row[$key] = mb_convert_encoding($value,'GBK');
        }
        fputcsv($handle, $row);
    }
    
    public static function checkMobileValidity($mobilephone){
        $exp = "/^1[0-9]{1}[0-9]{1}[0-9]{8}$|15[012356789]{1}[0-9]{8}$|18[012356789]{1}[0-9]{8}$|14[57]{1}[0-9]$/";
        if(preg_match($exp,$mobilephone)){
            return true;
        }else{
            return false;
        }
    }
}