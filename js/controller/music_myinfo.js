/*
* 主页逻辑
*/
controller = function() {
    this.hostName = 'music_home';
    this.basics = new utility; //基础方法类
    this.config = new apiconfig; //基础配置类
    this.localUrl = this.config.localUrl();//本地路由地址
    this.api = this.config.api();//后台请求地址
    this.userinfo = null;//本地用户信息
    this.request = {}; //提交登录的参数
    this.wxUrl; //微信跳转地址
    this.pagesize = 2; //默认页数
    this.page = 2; //翻页
    this.init();
};

controller.prototype = {
    //公用基础方法
    init: function() {
        // this.wxUrl = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid='+this.config.config.appid+'&redirect_uri='+this.config.config.local+'&response_type=code&scope=snsapi_userinfo&state=STATE#wechat_redirect';
        this.wxUrl = 'http://localhost/hc/redirect.html';
    },
    //判断有无登录（本地有无localstorage）
    login: function(){
        var _this = this;
        this.userinfo = this.basics.getStorage('hc_userinfo');
        //如果有本地玩家信息，则进行跳转
        if(this.userinfo){
            //设置权限
            this.created();
        }else{
            //判断code参数是否存在
            var urlparam = this.basics.UrlSearch();
            console.log(urlparam)
            if(urlparam && urlparam.code){
                //设置刚登录的账号
                this.submitLogin(urlparam.code);
            }else{
                window.location.href = _this.wxUrl;
            }
        }
    },
    //没有登录过，执行登录
    submitLogin: function(code){
        var _this = this;
        _this.request.code = code;
        //登录请求
        _this.basics.post(_this.api.login,_this.request,function(data){
            console.log(data)
            if(data.code == 200){
                _this.userinfo = data.result;
                _this.basics.setStorage('hc_userinfo',data.result);   //设置Storage userinfo
                _this.created();
            }else{
                if(data.code == 250){
                    // 微信登录，先去获取code
                    window.location.href = _this.wxUrl;
                }else{
                    _this.basics.tips(data.error,'error');
                }
            }
        });
    },
};
var _controller = new controller;