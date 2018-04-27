<?php
/**
 * Created by PhpStorm.
 * User: wuhaiyan
 * Date: 2018/4/27
 * Time: 18:40
 */

$GLOBALS['__ApiResponse__'] = new ApiResponse();
function api_resopnse($data, $code=ApiStatus::CODE_0,$msg='',$subcCode='',$subMsg=''){
    $GLOBALS['__ApiResponse__']->setData($data)->setCode($code)->setMsg($msg)->setSubCode($subcCode)->setSubMsg($subMsg);
    return $GLOBALS['__ApiResponse__'];
}
