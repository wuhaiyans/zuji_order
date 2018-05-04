<?php

namespace App\Lib\Common;

/**
 * EmailApi
 *
 * @author liuhongxing
 */
class EmailApi {

	/**
	 * 发送邮件
	 * @param array $tos		收件人地址列表
	 * @param string $title		标题
	 * @param string $content	正文
	 * @return bool
	 */
	public static function send(array $tos, string $title, string $content):bool {

		$mail = new email\PHPMailer();
		//是否启用smtp的debug进行调试 开发环境建议开启 生产环境注释掉即可 默认关闭debug调试模式
		//$mail->SMTPDebug = 3;
		//使用smtp鉴权方式发送邮件
		$mail->isSMTP();
		//smtp需要鉴权 这个必须是true
		$mail->SMTPAuth = true;
		//链接域名邮箱的服务器地址 
		$mail->Host = 'smtp.exmail.qq.com';
		//设置ssl连接smtp服务器的远程服务器端口号
		$mail->Port = '465';
		//设置使用ssl加密方式登录鉴权
		$mail->SMTPSecure = 'ssl';
		//设置发件人的主机域 可有可无 默认为localhost 内容任意，建议使用你的域名,这里为默认localhost 
		$mail->Hostname = 'zuji.huishoubao.com';
		$mail->CharSet = 'UTF-8';
		//设置发件人姓名（昵称）可为任意内容
		$mail->FromName = 'Xxx';
		//smtp登录的账号 
		$mail->Username = 'yaodongxu@huishoubao.com.cn';
		//smtp登录的密码
		$mail->Password = 'Ydxmy0919';
		//设置发件人邮箱地址
		$mail->From = 'yaodongxu@huishoubao.com.cn';
		//邮件正文是否以html方式发送
		$mail->isHTML(true);
		//设置收件人邮箱地址（可多次设置，设置多个）
		foreach ($tos as $it){
			$mail->addAddress( $it );
		}
		//添加该邮件的主题
		$mail->Subject = $title;
		//添加邮件正文
		$mail->Body = $content;
//		//为该邮件添加附件 该方法也有两个参数 第一个参数为附件存放的目录（相对目录、或绝对目录均可） ；第二参数为在邮件附件中该附件的名称  （可多次设置，设置多个）
//		//获取当前服务器根目录
//		foreach ($this->attachment as $value) {
//			$mail->addAttachment($value['path'], $value['name']);
//		}
		$status = $mail->send();
		return $status;
	}

}
