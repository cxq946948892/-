<?php

header('Access-Control-Allow-Origin:*');
header('Content-type:application/json');

require_once('lib/config.php');
require_once('lib/mysql.php');
require_once('ServerAPI.php');

class configData{
	private $mysql;
	private $act;
	private $p;

	public function __construct() {
		$this->ResponseHandler();
	}
	private function ResponseHandler() {
		$this->mysql = new MysqlDriver(Config::$mysql_config);
		$this->p     = new ServerAPI(Config::$AppKey,Config::$AppSecret,'curl');	
	}

	public function main($data){
		$this->act = $data['act'];
		$postData = $this->prepareData($data);

		$actArr = array(
			'banner',	//首页banner 
		);

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

	private function banner($data){
		if(!$data['place']){
			return array('code'=>500,'errmsg'=>'位置必填');
		}
		$config = $this->mysql->select('wc_config','*',array('name'=>'banner','status'=>1));
		if(count($config) == 0){
			$returnArr = array('code'=>500,'errmsg'=>'暂无配置');
		}else{
			$config = $config[0];
			$value = json_decode($config['value'],true);
			if($data['place']){
				$value = $value[$data['place']];
			}
			if($value){
				foreach($value as $key => $val){
					$value[$key] = Config::$currentServer . Config::$bannerUrl . $val;
				}
			}
			$returnArr = array('code'=>200,'errmsg'=>'','data'=>$value);
		}

		return $returnArr;
	}


}

// $_POST = array(
// 	'act'=>'banner',
// 	'place'=>'index'
// );

$configData = new configData();
$response = $configData->main($_POST);
echo $response;