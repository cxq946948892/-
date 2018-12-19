<?php

header('Access-Control-Allow-Origin:*');
header('Content-type:application/json');

require_once('lib/config.php');
require_once('lib/mysql.php');
require_once('lib/function.php');

class gamesnews{
	private $mysql;
	private $act;

	public function __construct() {
		$this->ResponseHandler();
	}
	private function ResponseHandler() {
		$this->mysql = new MysqlDriver(Config::$mysql_config);
	}

	public function main($data){
		$this->act = $data['act'];
		$postData = $this->prepareData($data);

		$actArr = array(
			'newsList',			// 列表
			'newsContent',		// 内容
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

	private function newsList($data){
		if(!isset($data['page'])){
			$page = 1;
		}else{
			$page = (int)$data['page'];
		}
		$size = 10;
		$limit = ($page - 1) * $size ."," . $size;
		$re = $this->mysql->select('wc_games_news','id,title,create_time',array('type'=>1),'order by create_time DESC limit '.$limit);
		$p = $this->mysql->select('wc_games_news','count(*) as num',array('type'=>1));
		$tp = ceil($p[0]['num'] / $size);
		return array('code'=>200,'errmsg'=>'','data'=>$re,'totalpage'=>$tp);
	}

	private function newsContent($data){
		if(!$data['id']){
			return array('code'=>500,'errmsg'=>'参数错误');
		}
		$re = $this->mysql->select('wc_games_news','content',array('type'=>1,'id'=>$data['id']));
		if(count($re) == 0){
			return array('code'=>500,'errmsg'=>'id错误');
		}
		return array('code'=>200,'errmsg'=>'','data'=>$re[0]['content']);
	}
}

// $_POST = array(
// 	'act'=>'newsList',
// 	'page'=>1
// );

// $_POST = array(
// 	'act'=>'newsContent',
// 	'id'=>1
// );

$gamesnews = new gamesnews();
$response = $gamesnews->main($_POST);
echo $response;