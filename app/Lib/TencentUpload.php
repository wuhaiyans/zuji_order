<?php
/**
 * 上传图片 封装
 * @access public
 * @author wangjinlin
 */
namespace App\Lib;
use App\Lib\Common\LogApi;

/**
 * TencentUpload类
 *
 * @access public
 */
class TencentUpload {

//    private $api_upload_key = '8oxq1kma0eli9vlxnyj8v7qk335uvrf0';
//    private $api_upload_url = 'https://dev-api.nqyong.com/api/upload_hjx';

    private $header = [
        "version"   =>  "0.01",
        "msgtype"   =>  "request",
        "remark"    =>  "",
        "interface" =>  "upload",
    ];
    private $params = [];
    private $respall = [];
    private $respone = [
        'ret' => 1,
        'msg' => '上传失败',
        'img' => [],
    ];

    /**
     * 图片上传 单图
     **/
    public function file_upload_one(){

        $file = $_FILES;
        $key_all = array_keys($file);
        $key = current($key_all);

        if(!empty($file[$key]['tmp_name']))
        {
            $fileName = $file[$key]['name'];
            //后缀名
            $suffix   = strrchr($file[$key]['name'],".");

            $newName = time().mt_rand(10000,99999);

            // 上传大图
            $fileSrc  = base64_encode(file_get_contents($file[$key]['tmp_name']));
            $result = $this->tencentUpload($newName.$suffix,$fileSrc);
            if($result&&$result['ret']==0&&$result['data']['url']!=""){
                // 解决无缩略图时，thumb_url 为空字符串的问题
                $img['url'] = empty($result['data']['url'])?0:$result['data']['url'];
                $img['originalName'] = $fileName;
                $img['name'] = $newName;

                $this->respone['img'] = $img;
                $this->respone['ret'] = 0;
                $this->respone['msg'] = "上传成功";
            }
        }
        return $this->respone;
    }

    /**
     * 图片上传 批量(无缩略图)
     **/
    public function file_upload_all(){
        LogApi::info("[checkItemsFinish_3]图片上传");
        $file = $_FILES['params'];
        LogApi::info("[checkItemsFinish_4]接收文件参数",['params'=>$file]);
        $tmp_name = $file['tmp_name']['imgs'];
        foreach ($tmp_name as $key=>$value){
            if(!empty($value))
            {
                $fileName = $file['name']['imgs'][$key];
                //后缀名
                $suffix   = strrchr($file['name']['imgs'][$key],".");

                $newName = time().mt_rand(10000,99999);

                // 上传大图
                $fileSrc  = base64_encode(file_get_contents($value));
                $result = $this->tencentUpload($newName.$suffix,$fileSrc);
                if($result&&$result['ret']==0&&$result['data']['url']!=""){
                    // 解决无缩略图时，thumb_url 为空字符串的问题
                    $img['url'] = empty($result['data']['url'])?0:$result['data']['url'];
                    $img['originalName'] = $fileName;
                    $img['name'] = $newName;

                    $this->respall[$key]['img'] = $img;
                    $this->respall[$key]['ret'] = 0;
                    $this->respall[$key]['msg'] = "上传成功";
                }else{
                    $this->respall[$key]['img'] = [];
                    $this->respall[$key]['ret'] = 1;
                    $this->respall[$key]['msg'] = "图片".$key."上传失败";
                }
            }

        }

        return $this->respall;
    }

    /**
     * 图片上传 批量(无缩略图)
     **/
    public function file_upload_all_test(){

        $file = $_FILES['params'];
        LogApi::info("[file_upload_all_test]图片上传接受参数",['params'=>$file]);
        $tmp_name = $file['tmp_name']['imgs'];
        foreach ($tmp_name as $key=>$value){
            if(!empty($value))
            {
                $fileName = $file['name']['imgs'][$key];
                //后缀名
                $suffix   = strrchr($file['name']['imgs'][$key],".");

                $newName = time().mt_rand(10000,99999);

                // 上传大图
                $fileSrc  = base64_encode(file_get_contents($value));
                $result = $this->tencentUpload($newName.$suffix,$fileSrc);
                if($result&&$result['ret']==0&&$result['data']['url']!=""){
                    // 解决无缩略图时，thumb_url 为空字符串的问题
                    $img['url'] = empty($result['data']['url'])?0:$result['data']['url'];
                    $img['originalName'] = $fileName;
                    $img['name'] = $newName;

                    $this->respall[$key]['img'] = $img;
                    $this->respall[$key]['ret'] = 0;
                    $this->respall[$key]['msg'] = "上传成功";
                }else{
                    $this->respall[$key]['img'] = [];
                    $this->respall[$key]['ret'] = 1;
                    $this->respall[$key]['msg'] = "图片".$key."上传失败";
                }
            }

        }

        return $this->respall;
    }


    //文件上传核心方法
    private function tencentUpload($name,$src){

        $this->params = [
            "time"       => strval(time()),
            "system"     => "0",  //不确定
            "fileName"   => $name,
            "fileSrc"    => $src,
            "path"       => "/zuji/images/",
            "customPath" => "/content/",
            "uploadWay"  => "tencentUpload"
        ];

        //把head和params合并并且加密
        $inner = array_merge($this->header,$this->params);
        $sign = $this->sign($inner);
        $this->params["sign"] = $sign;
        $data['head'] = $this->header;
        $data['params'] = $this->params;
        $json = json_encode($data);

        $response = Curl::post(env('API_UPLOAD'),$json);
        $result = json_decode($response,true);
        if($result){
            return $result['body'];
        }
        return false;
    }

    //API请求参数加密
    private function sign( $param ){
        $sign = "";
        ksort($param);
        foreach( $param as $k=>$v ){
            if(!is_array($v) and $param[$k]){
                $sign .= $k.'='.$v.'&';
            }
        }
        $sign = strtolower( md5($sign.'key='. env('API_UPLOAD_KEY')) );
        return $sign;
    }
}
