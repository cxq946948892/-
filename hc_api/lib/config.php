<?php
ini_set('error_reporting', 'E_ALL ^ E_NOTICE');
date_default_timezone_set('Asia/Shanghai');
class Config {
	/* 数据库连接参数 */
	public static function init_mysql_info() {
		$host_name = $_SERVER['HTTP_HOST'];
		switch ($host_name) {
			case 'localhost':
				$host_url = array(
					'host' => '192.168.1.210',
					'port' => 3306,
					'username' => 'root',
					'password' => '123456',
					'dbname' => 'dc_huicard',
					'charset' => 'utf8',
				);
				break;
			case 'hmk.dcgames.cn':
				$host_url = array(
					'host' => '10.66.145.49',
					'port' => 3306,
					'username' => 'root',
					'password' => 'U^W#dyuwd237d68&',
					'dbname' => 'dc_huicard',
					'charset' => 'utf8',
				);
				break;
			default:
				$host_url = array(
					'host' => '192.168.1.210',
					'port' => 3306,
					'username' => 'root',
					'password' => '123456',
					'dbname' => 'dc_huicard',
					'charset' => 'utf8',
				);
				break;
		}
		if(is_null($host_url)) return;
		return $host_url;
	}

	/* 微信参数配置 */
	public static function init_wx_config() {
		$host_name = $_SERVER['HTTP_HOST'];
		switch ($host_name) {
			case 'localhost':
				$wx_config = array(
					'appid' => 'wx7bf0507c368c75c8',
					'secret' => '28e07da3100c465639284239e4fc66be',
				);
				break;
			case 'hmk.dcgames.cn':
				$wx_config = array(
					'appid' => 'wx825838e84705a950',
					'secret' => 'cf6492fa9bc5db60183b4ee530e24eb8',
				);
				break;
			default:
				$wx_config = array(
					'appid' => 'wx7bf0507c368c75c8',
					'secret' => '28e07da3100c465639284239e4fc66be',
				);
				break;
		}
		if(is_null($wx_config)) return;
		return $wx_config;
	}

	
	//引用的地址配置
	public static function init_host_info() {
		$host_name = $_SERVER['HTTP_HOST'];
		switch ($host_name) {
			case 'localhost':
				$host_url['url'] = "https://pay.swiftpass.cn/pay/gateway"; //接口请求地址，固定不变，无需修改
				$host_url['mchId'] = "101510138587"; //商户号
				$host_url['key'] = "f4390fd0cfbca4aaef94a7388bc387d8"; //密钥
				$host_url['notify_url'] = 'http://wapwx.dachuanyx.com/dcmjpay/yjsq_swiftpassPayTrade_vip_v2.php';//通知地址，必填项，接收平台通知的URL，需给绝对路径，255字符内格式如
				$host_url['image_url'] = 'http://localhost/hc_api//images/goods/';//图片地址
				$host_url['main_url'] = 'http://localhost/hc/';//前端地址
				$host_url['local_url'] = 'http://localhost/hc_api/';//当前地址
				break;
			case 'hmk.dcgames.cn':
				$host_url['url'] = "https://pay.swiftpass.cn/pay/gateway"; //接口请求地址，固定不变，无需修改
				$host_url['mchId'] = "101510138587"; //商户号
				$host_url['key'] = "f4390fd0cfbca4aaef94a7388bc387d8"; //密钥
				$host_url['notify_url'] = 'http://hmk.dcgames.cn/yjsq_swiftpassPayTrade_vip_v2.php';//通知地址，必填项，接收平台通知的URL，需给绝对路径，255字符内格式如
				$host_url['image_url'] = 'http://hmk.dcgames.cn/hc_api//images/goods/';//图片地址
				$host_url['main_url'] = 'http://localhost/hc/';//前端地址
				$host_url['local_url'] = 'http://hmk.dcgames.cn/hc_api/';//当前地址
				break;
			default:
				$host_url['url'] = "https://pay.swiftpass.cn/pay/gateway"; //接口请求地址，固定不变，无需修改
				$host_url['mchId'] = "101510138587"; //商户号
				$host_url['key'] = "f4390fd0cfbca4aaef94a7388bc387d8"; //密钥
				$host_url['notify_url'] = 'http://wapwx.dachuanyx.com/dcmjpay/yjsq_swiftpassPayTrade_vip_v2.php';//通知地址，必填项，接收平台通知的URL，需给绝对路径，255字符内格式如
				$host_url['image_url'] = 'http://localhost/hc_api//images/goods/';//图片地址
				$host_url['main_url'] = 'http://localhost/hc/';//前端地址
				$host_url['local_url'] = 'http://localhost/hc_api/';//当前地址
				break;
		}
		if(is_null($host_url)) return;
		return $host_url;
	}
    
}
?>