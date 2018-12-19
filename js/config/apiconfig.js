apiconfig = function () {
    this.config = new config;//加载配置类
    this.version = '/';
    this.ApiUrl; //后台接口地址
    this.local; //本地页面地址
    this.init();
};

apiconfig.prototype = {
    //公用基础方法
    init: function () {
        this.ApiUrl = this.config.ApiUrl;
        this.local = this.config.local;
    },

    //返回各个定义的接口
    api: function () {
        return {
            // demo
            'demo': this.ApiUrl + this.version + 'demo/index',
            //login
            'login': this.ApiUrl + this.version + 'wxlogin.php',//登录
            'goodsinfo': this.ApiUrl + this.version + 'goodsinfo.php',//商品列表接口
            'getuserinfo': this.ApiUrl + this.version + 'users.php',//用户信息接口
            'record': this.ApiUrl + this.version + 'record.php',//获取记录接口




        };
    },

    //返回当前页面的各个url地址
    localUrl: function () {
        return {
            'admin_view_index': this.local + '/admin_view/admin_user_info.html',//总公司主页
            'login': this.local,//登录界面

        };
    }


};