<?php
namespace App\Tools\Modules\Service\Coupon\CouponUser;
use App\Lib\ApiStatus;
use App\Lib\Common\LogApi;
use App\Lib\Common\SmsApi;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Tools\Models\CouponModel;
use App\Tools\Modules\Repository\GreyTest\GreyTest;
use App\Tools\Modules\Func\Func;
use App\Tools\Modules\Inc\CouponStatus;
use App\Tools\Modules\Repository\Coupon\CouponUserRepository;
use App\Lib\Tool\Tool;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use App\Tools\Modules\Service\Coupon\CouponModel\CouponModelDetail;

class CouponUserGetCode
{
    protected $CouponUserRepository = [];
    protected $CouponModelDetail = [];
    

    public function __construct(CouponUserRepository $CouponUserRepository , CouponModelDetail $CouponModelDetail)
    {
        $this->CouponUserRepository = $CouponUserRepository;
        $this->CouponModelDetail    = $CouponModelDetail;
    }
    
    public function execute(string $modelNo , int $num = 1)
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
        $couponModel = $this->CouponModelDetail->execute($modelNo);
        //查询剩余可获取的兑换码
        $where = ['model_no'=>$modelNo,'status'=>0,'mobile'=>'0','is_lock'=>CouponStatus::CouponLockWei];
        $coupons = $this->CouponUserRepository->getCoupon($where,$num);
        if(count($coupons) < $num){
            $message = '最多可在生成'.count($coupons).'张优惠券';
        }
        $handle = fopen('php://output', 'a');
        //存储时间数组(最后导出数组)
        $header_data = array(
            $couponModel->coupon_name,
            $message,
        );
        //输出头部数据
        $this->export_csv_wirter_row($handle, $header_data);
        foreach($coupons as $k=>$v){
            
            $couponUser = $this->CouponUserRepository->getOne($v['id']);
            if(!$couponUser->setLock()){
                unset($coupons[$k]);
                continue;
            }
            $_row = [
                "\t" . $v['coupon_no'],
            ];
            $this->export_csv_wirter_row($handle, $_row);
        }
        ob_flush();
        flush();
        fclose($handle);
    }
    
    private function export_csv_wirter_row( $handle, $row ){
        foreach ($row as $key => $value) {
            //$row[$key] = iconv('utf-8', 'gbk', $value);
            $row[$key] = mb_convert_encoding($value,'GBK');
        }
        fputcsv($handle, $row);
    }
}