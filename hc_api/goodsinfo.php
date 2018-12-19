<?php

header('Access-Control-Allow-Origin:*');
header('Content-type:application/json');

require_once('lib/config.php');
require_once('lib/mysql.php');
session_start();

class goodsinfo {
	private $p;
	private $mysql;
	private $act;
	private $cfg;

	public function __construct() {
		$this->ResponseHandler();
	}
	private function ResponseHandler() {
		$this->mysql = new MysqlDriver(Config::init_mysql_info());
		$this->cfg = Config::init_host_info();
	}

	public function main($data){
		$this->act = $data['act'];
		$postData = $this->prepareData($data);
		$actArr = ['goodsinfo'];

		if(!in_array($this->act,$actArr)){
			return json_encode(array('code'=>500,'errmsg'=>'非法请求'));
		}else{
			$return = $this->action($postData);
			$this->mysql->close();
			return json_encode($return);
		}
	}

	private function action($postData){
		$act = $this->act;
		$returnArr = $this->$act($postData);
		if(!$returnArr){
			$returnArr = array('code'=>555,'errmsg'=>'服务器错误');
		}
		return $returnArr;
	}

	private function prepareData($data){
		if(empty($data)){
			return array();
		}
		$ret = array();
		foreach($data as $key => $val){
			$ret[$key] = $val;
		}
		return $ret;
	}

	//公有方法
	private function goodsinfo($data){
		if(!$data['user_id']){
			return array('code'=>400,'errmsg'=>'参数user_id缺失');
		}

		$goods = $this->mysql->select('hc_goods_info','*',array('status'=>1),'order by goods_order_id DESC');

$goods = [
	[
		'name' => '套餐S',
		'title' => '充3000元',
		'money' => '送600元',
		'msg' => '额外赠送300元',
		'price' => 3000
	],
	[
		'name' => '套餐S',
		'title' => '充3000元',
		'money' => '送600元',
		'msg' => '额外赠送300元',
		'price' => 5000
	],
	[
		'name' => '套餐S',
		'title' => '充3000元',
		'money' => '送600元',
		'msg' => '额外赠送300元',
		'price' => 6000
	],
];

		//获取用户当前的余额
		$mymoney = $this->mysql->select('hc_user_info','*',array('user_id'=>$data['user_id']),'limit 1');
		if(!$mymoney){
			return array('code'=>300,'errmsg'=>'获取用户余额异常');
		}
		if(count($goods) == 0){
			return array('code'=>300,'errmsg'=>'商品不存在');
		}else{
			foreach ($goods as $key => $value) {
				// $goods[$key]['image_url'] = $this->cfg['image_url'].$value['image_url'];
			}
		}


		return array('code'=>200,'result'=>$goods,'mymoney'=>$mymoney[0]['user_money']);
	}
}

$goodsinfo = new goodsinfo();
$response = $goodsinfo->main($_POST);
echo $response;
