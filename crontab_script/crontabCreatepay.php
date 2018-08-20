<?php
//此脚本用于在Docker内通过Screen启动多个php
define("GET_TOTALNUM_API", "https://dev-api.nqyong.com/api/order/api/crontabCreatepayNum");

function mylog($title, $msg) {
	$data  =  "\n".date("Y-m-d H:i:s")."\n";
	$data .= $title;
	$data .= $msg;
	file_put_contents("/var/tmp/logs/crontabCreatepay.txt", $data, FILE_APPEND);
}

function get_api() {
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, GET_TOTALNUM_API);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_HEADER, 0);
	$output = curl_exec($ch);
	if($output === FALSE ){
		mylog("get_api(): curl error:", curl_error($ch));
		return 0;
	}
	curl_close($ch);
	//$output= "[]";
	$arr = json_decode($output);
	return $arr;
}

function run(){
	//如果screen已经存在则不能支持
	$ret = system("screen -ls|grep 'crontabCreatepay_' -c");
	if ($ret != 0) {
		mylog("run():", "screen can not start,current screen in docker is $ret");
		return;
	}

	$job_args= get_api();
	if (!is_array($job_args) || empty($job_args)) {
		mylog("run():", "arr is null or not array");
		return;
	}

	foreach ($job_args as $k => $arg) {
		$screen_name = 'crontabCreatepay_'.$k;
		$arg = explode("-", $arg);
		if (count($arg) != 2) {
			continue;
		}
		$min_id =$arg[0];
		$max_id =$arg[1];
		system("screen -dmS $screen_name /bin/sh -c  'cd /var/www/OrderServe/crontab_script && php test.php $min_id $max_id;'");
		//system("screen -dmS $screen_name /bin/sh -c  'cd /var/www/OrderServer && php artisan command:crontabCreatepay --minId=$min_id --maxId=$max_id;'");
	}
}

run();
