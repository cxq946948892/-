/*
* 主页逻辑
*/
controller = function() {
    this.hostName = 'index';
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
    	this.login();
        //注册支付方法
        this.pay();
        //注册查看更多事件
        this.getrecord();
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
    //创建好了后请求数据
    created: function(){
        var _this = this;
        $('.weui-tab').tab({
            defaultIndex: 0,
            activeClass: 'weui-bar__item_on',
            onToggle: function (index) {
                switch (index) {
                    case 0:
                        //购买惠民卡
                        _this.shopping();
                        break;
                    case 1:
                        //余额记录
                        _this.record();
                        break;
                    case 2:
                        //个人信息
                        _this.user();
                        break;
                    default:
                        break;
                }
            }
        })
    },

    //购买惠民卡
    shopping: function(){
        //商品列表
        this.goodslist();
    },

    //余额记录
    record: function(){
        console.log('现在是余额记录页面')
        //先清空记录
        $('.record-list').html('');
        $("#getmore").html('查看更多<i id="loading" class="weui-loading"></i>');
        //先渲染页面
        this.getpage(1);
        this.page = 2;
    },

    //个人信息
    user: function(){
        console.log('现在是个人信息页面')
        //设置个人信息
        this.setuserinfo();

    },

    //获取余额分页
    getrecord: function(){
        var _this = this;
        var maxpage;
        $('#loading').hide();
        $('#getmore').on('click', function() {
            console.log(_this.page)
            maxpage = sessionStorage['maxpage'];
            if(_this.page <= maxpage) {
                _this.getpage(_this.page);
                _this.page++;
            }
        });
    },

    //获取余额记录接口
    getpage: function(page){
        var _this = this;
        var condition = {
            'act' : 'record',
            'page' : page,
            'pagesize' : _this.pagesize,
            'user_id' : _this.userinfo.user_id
        };
        _this.basics.post(_this.api.record,condition,function(data){
            $('#loading').hide();
            _this.addamonuthtml(data,page);
            
        });
    },

    //渲染余额记录html
    addamonuthtml: function(data,page){
        var _this = this;
        var html = '';
        var list = data.list;
        
        if (list) {
            var count = list.length;
            for (var i = 0; i < count; i++) {
                html += '<div class="weui-record">';
                html += '<div class="weui-record-left">';
                html += '<p class="record-time">'+list[i]['pruchase_date']+'</p>';
                html += '<p class="record-type">'+list[i]['type_name']+'</p>';
                html += '</div>';
                html += '<div class="weui-record-right">';
                html += '<p class="record-money">'+list[i]['price']+'</p>';
                html += '</div>';
                html += '</div>';
            }
        } else {
            html = '<div class="weui-loadmore weui-loadmore_line">';
            html += '<span class="weui-loadmore__tips">暂无数据</span>';
            html += '</div>';
        }
        if(list){
            $('.record-list').append(html);
        }else{
            $('.record-list').html(html);
        }

        var maxpage = Math.ceil(data.total / _this.pagesize);
        sessionStorage['maxpage'] = maxpage;

        if(page == maxpage){
            $("#getmore").html("没有更多数据了");
            return false;
        }
    },

    //设置个人信息
    setuserinfo: function(){
        var _this = this;
        var condition = {
            'act' : 'getuserinfo',
            'user_id' : _this.userinfo.user_id
        };
        _this.basics.post(_this.api.getuserinfo,condition,function(data){
            // console.log(data)
            if(data.code == 200){
                $('.headimage').attr('src',data.result.user_header_url);
                $('.weui-user-text-span').html(data.result.user_nickname);
            }else{
                _this.basics.tips(data.error,'error');
            }
        });
    },

    //注册卡片切换方法
    cardSwitch: function(price){
        //设置默认的支付金额
        $('.pay-price').html(price);
        $(".card").on("click", function(){
            $('.pay-price').html($(this).attr('price'));
            $('.card').removeClass('active-card');
            $(this).addClass('active-card');
        });
    },

    //支付金额的切换方法
    moneySwitch: function(){
        
    },

    //注册支付方法
    pay: function(){
        $(".pay-btn").on("click", function(){
            console.log(2)
        });
    },

    //商品列表
    goodslist: function(){
        var _this = this;
        var condition = {
            'act' : 'goodsinfo',
            'user_id' : _this.userinfo.user_id
        };
        //请求接口
        _this.basics.post(_this.api.goodsinfo,condition,function(data){
            // console.log(data)
            if(data && (data.code == 200 || data.code == 300) ){
                //设置我的余额
                $('.amount').html(data.mymoney);
                //渲染table
                _this.addTableList(data.result);
                //注册卡片切换方法
                _this.cardSwitch(data.result[0]['price']);
            }else{
                _this.basics.tips(data.errmsg,'error');
            }
        });
    },

    //渲染table
    addTableList: function (data) {
        var _this = this;
        var html = '';
        var list = data;
        
        if (list) {
            var count = list.length;
            for (var i = 0; i < count; i++) {
                html += '<div class="card ';
                if(i == 0){
                    html += 'normal-card active-card';
                }else{
                    html += 'normal-card ';
                }
                html += '" price="'+list[i]['price']+'" >';
                html += '<div class="card-left">';
                html += '<div class="font-14 center">'+list[i]['name']+'</div>';
                html += '<div class="center">'+list[i]['title']+'</div>';
                html += '<div class="font-14 center red-gh">'+list[i]['money']+'</div>';
                html += '</div>';
                html += '<div class="card-text">'+list[i]['msg']+'</div>';
                html += '</div>';
            }
        } else {
            html = '<div class="weui-loadmore weui-loadmore_line">';
            html += '<span class="weui-loadmore__tips">暂无数据</span>';
            html += '</div>';
        }
        $('.goodslist').html(html);
    },

};
var _controller = new controller;