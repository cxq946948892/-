<?php

/**
 *
 * 柳州银行支付工具类
 * 
 */

class bolzPayHandler{

	//---------------------------------------------------数据处理--------------------------------------------------------
	/** 统一下单接口地址 */
	var $gateUrl;
	// 校验回调真假
	var $checkNotifyUrl ;
	// 支付接口地址
	var $payUrl ;
	// 手机网页支付网关
	var $mbPayUrl ;

	// 商户id
	var $partner;
	/** 密钥 */
	var $key;
	/** 请求的参数 */
	var $parameters;
	/** debug信息 */
	var $debugInfo;
	//请求内容，无论post和get，都用get方式提供
	var $reqContent = array();
	//应答内容
	var $resContent;
	//错误信息
	var $errInfo;
	//超时时间
	var $timeOut;
	//http状态码
	var $responseCode;
	/** 应答的参数 */
	var $resParameters;
	//原始内容
	var $content;
	var $notify_url;
	
	function __construct() {
		$this->initHandler();
	}
	
	function initHandler() {
		// $this->checkNotifyUrl = "http://testepay.bolz.cn:4080/epaygate/notifyIdQuery.htm";
		// $this->gateUrl = "http://testepay.bolz.cn:4080/epaygate/unifiedorder.htm";
		$this->gateUrl = "https://epay.bolz.cn/epaygate/unifiedorder.htm";
		$this->checkNotifyUrl = "https://epay.bolz.cn/epaygate/notifyIdQuery.htm";

		// $this->mbPayUrl = "http://testepay.bolz.cn:4080/epaygate/mb/Wirelesspaygate.htm";
		// $this->payUrl = "http://testepay.bolz.cn:4080/epaygate/pay.htm";
		// $this->mbPayUrl = "https://epay.bolz.cn/epaygate/mb/Wirelesspaygate.htm";
		// $this->payUrl = "https://epay.bolz.cn/epaygate/pay.htm";
		$this->key = "";
		$this->partner = '';
		$this->parameters = array();
		$this->debugInfo = "";
		$this->resContent = "";
		$this->errInfo = "";
		$this->timeOut = 120;
		$this->responseCode = 0;
		$this->resParameters = array();
		$this->content = "";
	}

	function setPartner($partner){
		$this->partner = $partner;
	}
	
	function setKey($key){
		$this->key = $key;
	}

	function setNotifyUrl($notify_url){
		$this->notify_url = $notify_url;
	}

	/**
	*获取参数值
	*/
	function getParameter($parameter) {
		return isset($this->parameters[$parameter])?$this->parameters[$parameter]:'';
	}
	
	/**
	*设置参数值
	*/
	function setParameter($parameter, $parameterValue) {
		$this->parameters[$parameter] = $parameterValue;
	}

    /**
     * 一次性设置参数
     */
    function setReqParams($post,$filterField=null){
        if($filterField !== null){
            foreach($filterField as $k=>$v){
                unset($post[$v]);
            }
        }
        
        //判断是否存在空值，空值不提交
        foreach($post as $k=>$v){
            if(empty($v)){
                unset($post[$k]);
            }
        }

        $this->parameters = $post;
    }
	
	/**
	*获取带参数的请求URL
	*/
	function getRequestURL() {
	
		$this->createSign();
		
		$reqPar = "";
		ksort($this->parameters);
		foreach($this->parameters as $k => $v) {
			$reqPar .= $k . "=" . urlencode($v) . "&";
		}
		
		//去掉最后一个&
		$reqPar = substr($reqPar, 0, strlen($reqPar)-1);
		
		$requestURL = $this->gateUrl . "?" . $reqPar;
		
		return $requestURL;
		
	}
		
	/**
	*创建md5摘要,规则是:按参数名称a-z排序,遇到空值的参数不参加签名。
	*/
	function createSign() {
		$signPars = "";
		$parameters = $this->parameters;
		ksort($parameters);
		foreach($parameters as $k => $v) {
			if("" != $v && "sign" != $k && "sign_type" != $k) {
				$signPars .= $k . "=" . $v . "&";
			}
		}
		$signPars = substr($signPars,0,-1);
		$signPars .= $this->key;
		//$signPars .= "key=" . $this->key;return $signPars;
		//return $signPars;
		$sign = md5($signPars);
		$this->setParameter("sign", $sign);
		
		//debug信息
		$this->_setDebugInfo($signPars . " => sign:" . $sign);
		
	}	
	
	/**
	*设置debug信息
	*/
	function _setDebugInfo($debugInfo) {
		$this->debugInfo = $debugInfo;
	}

	//---------------------------------------------------发起请求--------------------------------------------------------
	

	//设置请求内容
	function setReqContent($url,$data) {
		$this->reqContent['url'] = $url;
        $this->reqContent['data'] = $data;
	}

	//执行http调用
	function curlHttp() {
		//启动一个CURL会话
		$ch = curl_init();
		// 设置curl允许执行的最长秒数
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeOut);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER,false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST,false);
        // 页面跳转继续跟踪
        // curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		// 获取的信息以文件流的形式返回，而不是直接输出。
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        //发送一个常规的POST请求。
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_URL, $this->reqContent['url']);
        //要传送的所有数据
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($this->reqContent['data']));
		// 执行操作
		$res = curl_exec($ch);
		$this->responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		if ($res == NULL) { 
		   $this->errInfo = "call http err :" . curl_errno($ch) . " - " . curl_error($ch) ;
		   curl_close($ch);
		   return false;
		} else if($this->responseCode  != "200") {
			$this->errInfo = "call http err httpcode=" . $this->responseCode  ;
			curl_close($ch);
			return false;
		}
		
		curl_close($ch);
		$this->resContent = $res;
		return true;
	}


	//---------------------------------------------------------------处理返回数据-----------------------------------------
	
	//设置原始内容
	function setContent($content) {
		$this->content = $content;
		
		$xml = simplexml_load_string($this->content);
		$encode = $this->getXmlEncode($this->content);
		
		if($xml && $xml->children()) {
			foreach ($xml->children() as $node){
				//有子节点
				if($node->children()) {
					$k = $node->getName();
					$nodeXml = $node->asXML();
					$v = substr($nodeXml, strlen($k)+2, strlen($nodeXml)-2*strlen($k)-5);
					
				} else {
					$k = $node->getName();
					$v = (string)$node;
				}
				
				if($encode!="" && $encode != "UTF-8") {
					$k = iconv("UTF-8", $encode, $k);
					$v = iconv("UTF-8", $encode, $v);
				}
				
				$this->setresParameter($k, $v);			
			}
		}
	}
	
	/**
	*获取参数值
	*/	
	function getresParameter($parameter) {
		return isset($this->resParameters[$parameter])?$this->resParameters[$parameter] : '';
	}
	
	/**
	*设置参数值
	*/	
	function setresParameter($parameter, $parameterValue) {
		$this->resParameters[$parameter] = $parameterValue;
	}
	
	/**
	*是否威富通签名,规则是:按参数名称a-z排序,遇到空值的参数不参加签名。
	*true:是
	*false:否
	*/	
	function checkNotifySign() {
		$signPars = "";
		ksort($this->resParameters);
		foreach($this->resParameters as $k => $v) {
			if("sign" != $k && "" != $v && 'sign_type' != $k) {
				$signPars .= $k . "=" . $v . "&";
			}
		}
		$signPars = substr($signPars,0,-1);
		$signPars .= $this->key;
		
		$sign = md5($signPars);
		
		$tenpaySign = $this->getresParameter("sign");
				
		//debug信息
		$this->_setDebugInfo($signPars . " => sign:" . $sign .
				" tenpaySign:" . $this->getresParameter("sign"));
		
		return $sign == $tenpaySign;
		
	}

	
	//获取xml编码
	function getXmlEncode($xml) {
		$ret = preg_match ("/<?xml[^>]* encoding=\"(.*)\"[^>]* ?>/i", $xml, $arr);
		if($ret) {
			return strtoupper ( $arr[1] );
		} else {
			return "";
		}
	}

	
	/**
	 * 是否财付通签名
	 * @param signParameterArray 签名的参数数组
	 * @return boolean
	 */	
	function _isTenpaySign($signParameterArray) {
	
		$signPars = "";
		foreach($signParameterArray as $k) {
			$v = $this->getParameter($k);
			if("sign" != $k && "" != $v) {
				$signPars .= $k . "=" . $v . "&";
			}			
		}
		$signPars .= "key=" . $this->key;
		
		$sign = strtolower(md5($signPars));
		
		$tenpaySign = strtolower($this->getParameter("sign"));
				
		//debug信息
		$this->_setDebugInfo($signPars . " => sign:" . $sign .
				" tenpaySign:" . $this->getParameter("sign"));
		
		return $sign == $tenpaySign;		
		
	
	}

}