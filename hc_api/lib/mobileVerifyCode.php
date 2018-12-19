<?php

/**
 * 发送 / 校验手机验证码
 * 发送手机信息
 */

class mobileVerifyCode{

    private $appid ;
    private $appkey ;
    private $templId ;
    private $sign ;
    public  $error ;

    public function __construct($parameters = array(),$user_redis,$uid){
        $this->appid = $parameters['appid'];
        $this->appkey = $parameters['appkey'];
        $this->templId = $parameters['templId'];
        $this->sign = $parameters['sign'];
        $this->user_redis = $user_redis;
        $this->uid = $uid;
    }
    
    /**
     * 发送手机验证码
     * @param  [type]  $code    [验证码]
     * @param  [type]  $message [完整信息]
     * @param  [type]  $mobile  [手机号]
     * @param  integer $timeout [过期时间：分钟]
     * @return [type]  boolean  
     */
    public function sendCode($mobile = '',$params = array()){
        if(!preg_match("/^[1][356789][0-9]{9}$/",$mobile)) {
            $this->error = array('errorCode'=>1,'errmsg'=>'手机号码格式错误');
            return false;
        }
        $timeout = isset($params[1]) ? intval($params[1]) : 1;
        $code = $params[0];

        $singleSender = new SmsSingleSender($this->appid, $this->appkey);
        $result = $singleSender->sendWithParam(86, $mobile, $this->templId, $params, '', "", "");
        $rsp = json_decode($result,true);

        if($rsp['result'] === 0){
            $this->user_redis->data_redis->hMSet("mobile_code:{$this->uid}",array('dc_verify'=>$code,'dc_verify_timeout'=>time() + $timeout * 60));
            return true;
        }else{
            $this->error = array('errorCode'=>$rsp['result'],'errmsg'=>$rsp['errmsg']);
            return false;
        }
    }

    public function checkCode($code){
        $data = $this->user_redis->data_redis->hMget("mobile_code:{$this->uid}",array('dc_verify','dc_verify_timeout'));
        if(!isset($data['dc_verify_timeout']) || time() > $data['dc_verify_timeout']){
            return false;
        }
        if(!isset($data['dc_verify']) || (int)$data['dc_verify'] !== (int)$code){
            return false;
        }
        $this->user_redis->data_redis->hMSet("mobile_code:{$this->uid}",array('dc_verify'=>'','dc_verify_timeout'=>''));
        return true;
    }

    public function sendMessage($mobile,$message){

    }
}



/**
 * 
 *  Works well with php5.3 and php5.6.
 *  
 *  腾讯云短信API
 *  
 */

class SmsSingleSender {
    var $url;
    var $appid;
    var $appkey;
    var $util;
    function __construct($appid, $appkey) {
        $this->url = "https://yun.tim.qq.com/v5/tlssmssvr/sendsms";
        $this->appid =  $appid;
        $this->appkey = $appkey;
        $this->util = new SmsSenderUtil();
    }
    /**
     * 普通单发，明确指定内容，如果有多个签名，请在内容中以【】的方式添加到信息内容中，否则系统将使用默认签名
     * @param int $type 短信类型，0 为普通短信，1 营销短信
     * @param string $nationCode 国家码，如 86 为中国
     * @param string $phoneNumber 不带国家码的手机号
     * @param string $msg 信息内容，必须与申请的模板格式一致，否则将返回错误
     * @param string $extend 扩展码，可填空串
     * @param string $ext 服务端原样返回的参数，可填空串
     * @return string json string { "result": xxxxx, "errmsg": "xxxxxx" ... }，被省略的内容参见协议文档
     */
    function send($type, $nationCode, $phoneNumber, $msg, $extend = "", $ext = "") {
        /*
        请求包体
        {
            "tel": {
                "nationcode": "86",
                "mobile": "13788888888"
            },
            "type": 0,
            "msg": "你的验证码是1234",
            "sig": "fdba654e05bc0d15796713a1a1a2318c",
            "time": 1479888540,
            "extend": "",
            "ext": ""
        }
        应答包体
        {
            "result": 0,
            "errmsg": "OK",
            "ext": "",
            "sid": "xxxxxxx",
            "fee": 1
        }
        */
        $random = $this->util->getRandom();
        $curTime = time();
        $wholeUrl = $this->url . "?sdkappid=" . $this->appid . "&random=" . $random;
        // 按照协议组织 post 包体
        $data = new \stdClass();
        $tel = new \stdClass();
        $tel->nationcode = "".$nationCode;
        $tel->mobile = "".$phoneNumber;
        $data->tel = $tel;
        $data->type = (int)$type;
        $data->msg = $msg;
        $data->sig = hash("sha256",
            "appkey=".$this->appkey."&random=".$random."&time=".$curTime."&mobile=".$phoneNumber, FALSE);
        $data->time = $curTime;
        $data->extend = $extend;
        $data->ext = $ext;
        return $this->util->sendCurlPost($wholeUrl, $data);
    }
    /**
     * 指定模板单发
     * @param string $nationCode 国家码，如 86 为中国
     * @param string $phoneNumber 不带国家码的手机号
     * @param int $templId 模板 id
     * @param array $params 模板参数列表，如模板 {1}...{2}...{3}，那么需要带三个参数
     * @param string $sign 签名，如果填空串，系统会使用默认签名
     * @param string $extend 扩展码，可填空串
     * @param string $ext 服务端原样返回的参数，可填空串
     * @return string json string { "result": xxxxx, "errmsg": "xxxxxx"  ... }，被省略的内容参见协议文档
     */
    function sendWithParam($nationCode, $phoneNumber, $templId = 0, $params, $sign = "", $extend = "", $ext = "") {
        /*
        请求包体
        {
            "tel": {
                "nationcode": "86",
                "mobile": "13788888888"
            },
            "sign": "腾讯云",
            "tpl_id": 19,
            "params": [
                "验证码",
                "1234",
                "4"
            ],
            "sig": "fdba654e05bc0d15796713a1a1a2318c",
            "time": 1479888540,
            "extend": "",
            "ext": ""
        }
        应答包体
        {
            "result": 0,
            "errmsg": "OK",
            "ext": "",
            "sid": "xxxxxxx",
            "fee": 1
        }
        */
        $random = $this->util->getRandom();
        $curTime = time();
        $wholeUrl = $this->url . "?sdkappid=" . $this->appid . "&random=" . $random;
        // 按照协议组织 post 包体
        $data = new \stdClass();
        $tel = new \stdClass();
        $tel->nationcode = "".$nationCode;
        $tel->mobile = "".$phoneNumber;
        $data->tel = $tel;
        $data->sig = $this->util->calculateSigForTempl($this->appkey, $random, $curTime, $phoneNumber);
        $data->tpl_id = $templId;
        $data->params = $params;
        $data->sign = $sign;
        $data->time = $curTime;
        $data->extend = $extend;
        $data->ext = $ext;
        return $this->util->sendCurlPost($wholeUrl, $data);
    }
}

class SmsMultiSender {
    var $url;
    var $appid;
    var $appkey;
    var $util;
    function __construct($appid, $appkey) {
        $this->url = "https://yun.tim.qq.com/v5/tlssmssvr/sendmultisms2";
        $this->appid =  $appid;
        $this->appkey = $appkey;
        $this->util = new SmsSenderUtil();
    }
    /**
     * 普通群发，明确指定内容，如果有多个签名，请在内容中以【】的方式添加到信息内容中，否则系统将使用默认签名
     * 【注意】海外短信无群发功能
     * @param int $type 短信类型，0 为普通短信，1 营销短信
     * @param string $nationCode 国家码，如 86 为中国
     * @param string $phoneNumbers 不带国家码的手机号列表
     * @param string $msg 信息内容，必须与申请的模板格式一致，否则将返回错误
     * @param string $extend 扩展码，可填空串
     * @param string $ext 服务端原样返回的参数，可填空串
     * @return string json string { "result": xxxxx, "errmsg": "xxxxxx" ... }，被省略的内容参见协议文档
     */
    function send($type, $nationCode, $phoneNumbers, $msg, $extend = "", $ext = "") {
        /*
        请求包体
        {
            "tel": [
                {
                    "nationcode": "86",
                    "mobile": "13788888888"
                },
                {
                    "nationcode": "86",
                    "mobile": "13788888889"
                }
            ],
            "type": 0,
            "msg": "你的验证码是1234",
            "sig": "fdba654e05bc0d15796713a1a1a2318c",
            "time": 1479888540,
            "extend": "",
            "ext": ""
        }
        应答包体
        {
            "result": 0,
            "errmsg": "OK",
            "ext": "",
            "detail": [
                {
                    "result": 0,
                    "errmsg": "OK",
                    "mobile": "13788888888",
                    "nationcode": "86",
                    "sid": "xxxxxxx",
                    "fee": 1
                },
                {
                    "result": 0,
                    "errmsg": "OK",
                    "mobile": "13788888889",
                    "nationcode": "86",
                    "sid": "xxxxxxx",
                    "fee": 1
                }
            ]
        }
        */
        $random = $this->util->getRandom();
        $curTime = time();
        $wholeUrl = $this->url . "?sdkappid=" . $this->appid . "&random=" . $random;
        $data = new \stdClass();
        $data->tel = $this->util->phoneNumbersToArray($nationCode, $phoneNumbers);
        $data->type = $type;
        $data->msg = $msg;
        $data->sig = $this->util->calculateSig($this->appkey, $random, $curTime, $phoneNumbers);
        $data->time = $curTime;
        $data->extend = $extend;
        $data->ext = $ext;
        return $this->util->sendCurlPost($wholeUrl, $data);
    }
    /**
     * 指定模板群发
     * 【注意】海外短信无群发功能
     * @param string $nationCode 国家码，如 86 为中国
     * @param array $phoneNumbers 不带国家码的手机号列表
     * @param int $templId 模板 id
     * @param array $params 模板参数列表，如模板 {1}...{2}...{3}，那么需要带三个参数
     * @param string $sign 签名，如果填空串，系统会使用默认签名
     * @param string $extend 扩展码，可填空串
     * @param string $ext 服务端原样返回的参数，可填空串
     * @return string json string { "result": xxxxx, "errmsg": "xxxxxx" ... }，被省略的内容参见协议文档
     */
    function sendWithParam($nationCode, $phoneNumbers, $templId, $params, $sign = "", $extend ="", $ext = "") {
        /*
        请求包体
        {
            "tel": [
                {
                    "nationcode": "86",
                    "mobile": "13788888888"
                },
                {
                    "nationcode": "86",
                    "mobile": "13788888889"
                }
            ],
            "sign": "腾讯云",
            "tpl_id": 19,
            "params": [
                "验证码",
                "1234",
                "4"
            ],
            "sig": "fdba654e05bc0d15796713a1a1a2318c",
            "time": 1479888540,
            "extend": "",
            "ext": ""
        }
        应答包体
        {
            "result": 0,
            "errmsg": "OK",
            "ext": "",
            "detail": [
                {
                    "result": 0,
                    "errmsg": "OK",
                    "mobile": "13788888888",
                    "nationcode": "86",
                    "sid": "xxxxxxx",
                    "fee": 1
                },
                {
                    "result": 0,
                    "errmsg": "OK",
                    "mobile": "13788888889",
                    "nationcode": "86",
                    "sid": "xxxxxxx",
                    "fee": 1
                }
            ]
        }
        */
        $random = $this->util->getRandom();
        $curTime = time();
        $wholeUrl = $this->url . "?sdkappid=" . $this->appid . "&random=" . $random;
        $data = new \stdClass();
        $data->tel = $this->util->phoneNumbersToArray($nationCode, $phoneNumbers);
        $data->sign = $sign;
        $data->tpl_id = $templId;
        $data->params = $params;
        $data->sig = $this->util->calculateSigForTemplAndPhoneNumbers(
            $this->appkey, $random, $curTime, $phoneNumbers);
        $data->time = $curTime;
        $data->extend = $extend;
        $data->ext = $ext;
        return $this->util->sendCurlPost($wholeUrl, $data);
    }
}

class SmsSenderUtil {
    function getRandom() {
        return rand(100000, 999999);
    }
    function calculateSig($appkey, $random, $curTime, $phoneNumbers) {
        $phoneNumbersString = $phoneNumbers[0];
        for ($i = 1; $i < count($phoneNumbers); $i++) {
            $phoneNumbersString .= ("," . $phoneNumbers[$i]);
        }
        return hash("sha256", "appkey=".$appkey."&random=".$random
            ."&time=".$curTime."&mobile=".$phoneNumbersString);
    }
    function calculateSigForTemplAndPhoneNumbers($appkey, $random, $curTime, $phoneNumbers) {
        $phoneNumbersString = $phoneNumbers[0];
        for ($i = 1; $i < count($phoneNumbers); $i++) {
            $phoneNumbersString .= ("," . $phoneNumbers[$i]);
        }
        return hash("sha256", "appkey=".$appkey."&random=".$random
            ."&time=".$curTime."&mobile=".$phoneNumbersString);
    }
    function phoneNumbersToArray($nationCode, $phoneNumbers) {
        $i = 0;
        $tel = array();
        do {
            $telElement = new \stdClass();
            $telElement->nationcode = $nationCode;
            $telElement->mobile = $phoneNumbers[$i];
            array_push($tel, $telElement);
        } while (++$i < count($phoneNumbers));
        return $tel;
    }
    function calculateSigForTempl($appkey, $random, $curTime, $phoneNumber) {
        $phoneNumbers = array($phoneNumber);
        return $this->calculateSigForTemplAndPhoneNumbers($appkey, $random, $curTime, $phoneNumbers);
    }
    function sendCurlPost($url, $dataObj) {
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_HEADER, 0);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_POST, 1);
        curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($dataObj));
        curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
        $ret = curl_exec($curl);
        if (false == $ret) {
            // curl_exec failed
            $result = "{ \"result\":" . -2 . ",\"errmsg\":\"" . curl_error($curl) . "\"}";
        } else {
            $rsp = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            if (200 != $rsp) {
                $result = "{ \"result\":" . -1 . ",\"errmsg\":\"". $rsp . " " . curl_error($curl) ."\"}";
            } else {
                $result = $ret;
            }
        }
        curl_close($curl);
        return $result;
    }
}

class SmsVoiceVerifyCodeSender {
    var $url;
    var $appid;
    var $appkey;
    var $util;
    function __construct($appid, $appkey) {
        $this->url = "https://yun.tim.qq.com/v5/tlsvoicesvr/sendvoice";
        $this->appid =  $appid;
        $this->appkey = $appkey;
        $this->util = new SmsSenderUtil();
    }
    /**
     * 语言验证码发送
     * @param string $nationCode 国家码，如 86 为中国
     * @param string $phoneNumber 不带国家码的手机号
     * @param string $msg 信息内容，必须与申请的模板格式一致，否则将返回错误
     * @param intger $playtimes 信息内容，必须与申请的模板格式一致，否则将返回错误
     * @param string $ext 服务端原样返回的参数，可填空串
     * @return string json string { "result": xxxxx, "errmsg": "xxxxxx" ... }，被省略的内容参见协议文档
     */
    function send($nationCode, $phoneNumber, $msg, $playtimes = 2, $ext = "") {
         /*
            {
            "tel": {
                "nationcode": "86", //国家码
                "mobile": "13788888888" //手机号码
            },
            "msg": "1234", //验证码，支持英文字母、数字及组合；实际发送给用户时，语音验证码内容前会添加"您的验证码是"语音提示。
            "playtimes": 2, //播放次数，可选，最多3次，默认2次
            "sig": "30db206bfd3fea7ef0db929998642c8ea54cc7042a779c5a0d9897358f6e9505", //app凭证，具体计算方式见下注
            "time": 1457336869, //unix时间戳，请求发起时间，如果和系统时间相差超过10分钟则会返回失败
            "ext": "" //用户的session内容，腾讯server回包中会原样返回，可选字段，不需要就填空。
        }*/
        $random = $this->util->getRandom();
        $curTime = time();
        $wholeUrl = $this->url . "?sdkappid=" . $this->appid . "&random=" . $random;
        // 按照协议组织 post 包体
        $data = new \stdClass();
        $tel = new \stdClass();
        $tel->nationcode = "".$nationCode;
        $tel->mobile = "".$phoneNumber;
        $data->tel = $tel;
        $data->msg = $msg;
        $data->playtimes = $playtimes;
        $data->sig = hash("sha256",
            "appkey=".$this->appkey."&random=".$random."&time=".$curTime."&mobile=".$phoneNumber, FALSE);
        $data->time = $curTime;
        $data->ext = $ext;
        return $this->util->sendCurlPost($wholeUrl, $data);
    }
}

class SmsVoicePromptSender {
    var $url;
    var $appid;
    var $appkey;
    var $util;
    
    function __construct($appid, $appkey) {
        $this->url = "https://yun.tim.qq.com/v5/tlsvoicesvr/sendvoiceprompt";
        $this->appid =  $appid;
        $this->appkey = $appkey;
        $this->util = new SmsSenderUtil();
    }
    
    /**
     * 语言验证码发送
     * @param string $nationCode 国家码，如 86 为中国
     * @param string $phoneNumber 不带国家码的手机号
     * @param string $prompttype 语音类型目前固定值，2
     * @param string $msg 信息内容，必须与申请的模板格式一致，否则将返回错误
     * @param string $playtimes 播放次数
     * @param string $ext 服务端原样返回的参数，可填空串
     * @return string json string { "result": xxxxx, "errmsg": "xxxxxx" ... }，被省略的内容参见协议文档
     */
    function send($nationCode, $phoneNumber, $prompttype,$msg, $playtimes = 2, $ext = "") {
        /*
         {
         "tel": {
         "nationcode": "86", //国家码
         "mobile": "13788888888" //手机号码
         },
         "prompttype": 2, //语音类型，目前固定为2
         "promptfile": "语音内容文本", //通知内容，utf8编码，支持中文英文、数字及组合，需要和语音内容模版相匹配
         "playtimes": 2, //播放次数，可选，最多3次，默认2次
         "sig": "30db206bfd3fea7ef0db929998642c8ea54cc7042a779c5a0d9897358f6e9505", //app凭证，具体计算方式见下注
         "time": 1457336869, //unix时间戳，请求发起时间，如果和系统时间相差超过10分钟则会返回失败
         "ext": "" //用户的session内容，腾讯server回包中会原样返回，可选字段，不需要就填空。
         }
         }*/
        $random = $this->util->getRandom();
        $curTime = time();
        $wholeUrl = $this->url . "?sdkappid=" . $this->appid . "&random=" . $random;
        
        // 按照协议组织 post 包体
        $data = new \stdClass();
        $tel = new \stdClass();
        $tel->nationcode = "".$nationCode;
        $tel->mobile = "".$phoneNumber;
        
        $data->tel = $tel;
        $data->promptfile = $msg;
        $data->prompttype = $prompttype;//固定值
        $data->playtimes = $playtimes;
        $data->sig = hash("sha256",
                          "appkey=".$this->appkey."&random=".$random."&time=".$curTime."&mobile=".$phoneNumber, FALSE);
        $data->time = $curTime;
        $data->ext = $ext;
        return $this->util->sendCurlPost($wholeUrl, $data);
    }
}