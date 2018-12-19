<?php

// 检验base64格式
function checkBase64($str){
	$patterns = '/^(data:\s*image\/(\w+);base64,)/';
	preg_match($patterns,$str,$matches);
	$allowType = array('jpg','jpeg','png','gif');
	if(!in_array($matches[2],$allowType)){
		return false;
	}else{
		$re = base64_decode(str_replace($matches[1], '', $str),true);
		if(!$re){
			return false;
		}
	}
	return array('type'=>$matches[2],'file'=>$re);
}

// 上传base64图片
function uploadBase64File($file){
	$dirFull = Config::$userImagesDir;
	$filename = date('YmdHis',time()) . rand(10000,99999) . '.' . $file['type'];
	$filenameFull = $dirFull . '/' . $filename;
	if(!is_dir($dirFull)){
		$re = mkdir($dirFull,0777,true);
		if(!$re){
			return array('code'=>1,'errmsg'=>'创建目录失败');
		}
	}
	$re = file_put_contents($filenameFull, $file['file']);
	if(!$re){
		return array('code'=>1,'errmsg'=>'上传失败');
	}else{
		return array('code'=>0,'errmsg'=>'','url'=> $filename);
	}
}