<?php

namespace App\Console\Commands;

//use App\Lib\Common\LogApi;
use App\Order\Models\Order;
use App\Order\Models\OrderGoods;
use App\Order\Modules\Inc\OrderStatus;
use App\Order\Modules\Service\OrderCreater;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class ImportOrder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'command:ImportAlipayMember {--filename=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = '支付宝用户导入';


    private $conn;

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
        $this->conn =\DB::connection('mysql_01');

    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        
		$filename = $this->option('filename');
		
		// 读取 数据列表
		$data_file = __DIR__.'/import_alipay_member/'.$filename;
		if( !file_exists($data_file) || !is_readable($data_file) ){
			echo '数据文件不存在或不可读';
			exit;
		}
		$data = include $data_file;
		if( !isset($data['user_from']) || !isset($data['user_list']) ){
			echo '数据文件格式错误';
			exit;
		}
		
		
		// 根据手机号，获取或注册用户，获取用户ID
		
		// 标记当前第三方订单标记
		 
		// 根据支付宝user_id查询绑定信息，已经存在时 跳过
		
		// 未绑定时，绑定支付宝user_id
		
		// 标记 用户当前渠道订单标记
		
		
		
		
    }
	
	/**
	 * 查找用户
	 * @param string $mobile
	 * @param array $userinfo
	 * @return bool
	 */
	private function _find_user_info( string $mobile, &$userinfo=null ):bool{
		// 
		$user_info = \DB::connection('mysql_01')->table('zuji_member')
			->where(['username'=>$mobile])
			->select(['id'])
			->first();
		
		if( $user_info ){
			return true;
		}
		false;
	}
	
	/**
	 * 注册用户
	 * @param int $appid		渠道入口
	 * @param string $mobile	手机号
	 * @return int	 0：失败；>1：成功，返回用户ID
	 */
	private function _register_user( int $appid, string $mobile ):int{
		$id = \DB::connection('mysql_01')->table('zuji_member_alipay')->insertGetId([
			'username'	=> $mobile,
			'mobile'	=> $mobile,
			'appid'		=> $appid,
			'register_time' => time(),
		]);
		return $id;
	}
	
	/**
	 * 查找支付宝用户信息
	 * @param string $alipay_user_id
	 * @param array $info 
	 * @return bool  true：查找成功；false：不存在
	 */
	private function _find_alipay_user_info( string $alipay_user_id, &$info=null ):bool{
		// 
		$info = \DB::connection('mysql_01')->table('zuji_member_alipay')
			->where(['user_id'=>$alipay_user_id])
			->first();
		if( $info ){
			return true;
		}
		false;
	}
	
	/**
	 * 注册 支付宝用户 绑定信息
	 * @param int $member_id
	 * @param string $user_id
	 * @param string $province
	 * @param string $city
	 * @return int
	 */
	private function _register_alipay_user( int $member_id, string $user_id, string $province, string $city ):int{
		$id = \DB::connection('mysql_01')->table('zuji_member_alipay')->insertGetId([
			'member_id'	=> $member_id,
			'user_id'	=> $user_id,
			'province'	=> $province,
			'city'		=> $city,
		]);
		return $id;
	}

}
