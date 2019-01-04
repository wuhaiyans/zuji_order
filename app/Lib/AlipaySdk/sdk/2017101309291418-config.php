<?php
/**
 * 测试使用 生活号配置文件
 */
return array(
    //应用ID,您的APPID。
    'app_id' => "2017101309291418",
    //商户私钥，您的原始格式私钥,一行字符串
    'merchant_private_key' => "MIICdwIBADANBgkqhkiG9w0BAQEFAASCAmEwggJdAgEAAoGBAMcMZH43GUYNs1zl8F5kZOHYvPG5P6jSFYKZRbmX3C9ZHbZmrOMGhxDn2JZWu7lfvyjx4Md0ek14GpWPKELjf+m1iEninUh6kUgxMYwyJ2OYfFNZ5W3lbBpZB41AdORjAJrRr1sLbwx1G5Pcdkpu1kQWGti7VT7i7JIeNp7CS3HFAgMBAAECgYEAhBNHAzDQRlmE8Flqu1dmUS2dgc9n3D86IqRNTa7kXU6GlqdehG2qZZ9RacA3Y/OSRjro6a/yD0FocmDBWFDYaDGHkvQjG7n9lnO1nV+R+dMb2s8eCsRL378j9oc+MEeie2N2YCn54GGI4X5jV5oR3zNZLfZcm/IN5ZWS9P1Bh8ECQQDio05lrYHZH5ajSswHwHkWJEy70UwenGuK65yEGelZ8z+cM7XYD/JgPmhUled/KjDu5kIKakahXA0uyiZgQ/FNAkEA4NYM2zK5HpfNl5RBnNwnAkTq00qrWkmT61hvx+bAXfYJtdTq0VR3yyDaJ2Jq4xbGNBh6AzbNvJRG++ymQLfGWQJAUH7qQGjg3qo+iZ7uWq59E2UvL+JFo/WwqLXIcI73d7BS3nrrUmNPlel0it53S45DtQZpXGOk1HjqYb0A5l4bXQJBALhPQCLApfhqQOMtacwIvQGjNU0YPPe6sUOQL7ITe0aLVtJ0RDptn/YobC01BKI8HSa/meXgmy8n7ji+els7S6ECQGO70AuqSFPTO+Tl6iHoguzMBg9SypgvRWb57rMgCXHOCS+nHvEBqu33syB1qEReJB5+75Z7etKGiHssl5dMo68=",
    //商户应用公钥,一行字符串
    'merchant_public_key' => "MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQDHDGR+NxlGDbNc5fBeZGTh2LzxuT+o0hWCmUW5l9wvWR22ZqzjBocQ59iWVru5X78o8eDHdHpNeBqVjyhC43/ptYhJ4p1IepFIMTGMMidjmHxTWeVt5WwaWQeNQHTkYwCa0a9bC28MdRuT3HZKbtZEFhrYu1U+4uySHjaewktxxQIDAQAB",
    //支付宝公钥,查看地址：https://openhome.alipay.com/platform/keyManage.htm 对应APPID下的支付宝公钥。
    'alipay_public_key' => "MIIBIjANBgkqhkiG9w0BAQEFAAOCAQ8AMIIBCgKCAQEAi4JFIx2VUn/y1iA5Jx6G7JMLmxzEDzOqygJwaAjetfGkmFNAegdVmQEgQPsM8LUaAtkUjFYbxynuvAqbl8+6a87m5mg1MQX4ZNCIewS6YrRv5DGou+wbQwWhpmQpq14r+tDHb5eL2ypCxhbRdyNlEYNM5JAdtoU4wNHHRKjyd0GLDRXoEpfVMsQFLvHcHlVbcUBubbfmHqhZNHrYuzRxdOwJo+gUfVlgMimg8gkipvG55SNgcYAXmqE/jepj47yPQSMPokU9o2lVbwSrMfPtsl/Ssvj18dNFjX8YPFWl8Pn2hOe8y/19gKIGxaln4lfCZeJRzVgnwhRyhuG9Gn/aOQIDAQAB",
    //编码格式只支持GBK。
    //'charset' => "GBK",
    //（手机网站支付）编码格式
    'charset' => "UTF-8",
    //支付宝网关
    'gatewayUrl' => "https://openapi.alipay.com/gateway.do",
    //签名方式
    'sign_type' => "RSA2",
    //异步通知地址
    'notify_url' => Config::get('constants.LOCALHOST_URL').'alipay/Payment/payNotify',
    
    // 允许的支付方式：(balance：余额；moneyFund：余额宝；pcredit：花呗；pcreditpayInstallment：花呗分期；creditCard：信用卡；debitCardExpress：借记卡快捷)
    'enable_pay_channels' => 'balance,moneyFund,pcredit,pcreditpayInstallment,creditCard,debitCardExpress',
	
	// debug输出开启
	'debug_info' => true,
);
