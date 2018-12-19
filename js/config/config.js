config = function() {
    this.ApiUrl; //php后台请求接口地址
    this.local; //本地地址
    this.appid; //微信的appid
    this.init();
};

config.prototype = {
    //公用基础方法
    init: function() {
        this.updateconfig();
    },

    //根据不同环境更新配置
    updateconfig: function(){
        var host = this.UrlHost();
        var _this = this;
        switch(host){
            case 'localhost':
                _this.ApiUrl = 'http://localhost/hc/hc_api';
                _this.local = 'http://localhost/hc/index.html';
                _this.appid = 'wx7bf0507c368c75c8';
                break;
            case '192.168.1.55':
                _this.ApiUrl = 'http://192.168.1.55/hc/hc_api';
                _this.local = 'http://192.168.1.55/hc/index.html';
                _this.appid = 'wx7bf0507c368c75c8';
                break;
            case 'hmk.dcgames.cn':
                _this.ApiUrl = 'http://hmk.dcgames.cn/hc_api';
                _this.local = 'http://hmk.dcgames.cn/index.html';
                _this.appid = 'wx825838e84705a950';
                break;
            default:
        }
    },
    //获取当前页面主机部分
    UrlHost: function(){
        return window.location.host;
    },
};