<?php
/**
 * Created by PhpStorm.
 * User: FF
 * Date: 2018/4/28
 * Time: 15:12
 */
/**
 * api返回消息
 * @param $data
 * @param int $errno
 * @param string $errmsg
 * @return \Illuminate\Http\JsonResponse
 *
 */
function apiResponse($data=[], $errno=0, $errmsg='')
{
    return response()->json(['data'=>$data, 'code'=>$errno, 'msg'=>$errmsg]);
}