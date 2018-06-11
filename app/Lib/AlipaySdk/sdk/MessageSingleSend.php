<?php
namespace App\Lib\AlipaySdk\sdk;

//支付宝单发消息接口

require_once __DIR__ . '/aop/request/AlipayOpenPublicMessageSingleSendRequest.php';


class MessageSingleSend extends BaseApi {


    public function __construct($appid) {
        parent::__construct($appid);
    }

    public function getError( ) {
        return $this->error;
    }

    /**
     * 支付宝单发消息接口
     * @param array $params
     * [
     *	    'to_user_id' => '', //接受消息的用户id
     *	    'template_id' => '', //消息模板id
     *	    'head_color' => '',  //顶部色条的色值
     *	    'url' => '', //点击消息后承接页的地址
     *	    'keyword' => '', //keyword数组
     *	    'action_name' => '',
     *	    'first' => '',
     *      'remark' =>'',
     * ]
     * @return string	url
     */
    public function MessageSingleSend( $params ){
        $biz_content['to_user_id'] = $params["to_user_id"];
        // 模板id
        $biz_content['template'] = [
            'template_id'=>$params["template_id"],
            'context'=>[
                'head_color'=>$params["head_color"],
                'url'=>config('ZUJI_H5_PERSONAL'),
                'action_name'=>$params["action_name"],
                'first'=>$params["first"],
                'remark'=>$params["remark"],
            ]
        ];
        foreach($params["keyword"] as $key => $val){
            $biz_content['template']['context'][$key] = $val;
        }
        $request = new \AlipayOpenPublicMessageSingleSendRequest();
        // json 格式字符串
        $request->setBizContent ( json_encode($biz_content) );
        $result = $this->execute($request);
        $result = json_encode($result);
        $result = json_decode( $result ,true );

        if( $result['alipay_open_public_message_single_send_response']['code'] == 10000 ){
            return true;
        }
        $this->error = $result;
        return false;
    }

}

?>