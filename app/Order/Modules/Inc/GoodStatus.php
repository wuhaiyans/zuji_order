<?php

namespace App\Order\Modules\Inc;

class GoodStatus {

   
    /**
     * @var 商品状态
     */
    const GoodInvalid = 0;

    /**
     * @var int 已创建申请（生效状态）
     */
    const GoodInvalid = 1;
    /**
     * @var int 审核通过
     */
    const GoodInvalid = 2;
    
    /**
     * @var int 审核拒绝
     */
    const GoodDenied = 3;
    
   

    
    public static function getStatusList(){
        return [
            self::GoodInvalid => '无效状态',
            self::GoodInvalid => '提交申请',
            self::GoodInvalid => '审核通过',
            self::GoodDenied => '审核拒绝',
            
        ];
    }


}
