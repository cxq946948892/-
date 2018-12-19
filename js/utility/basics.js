utility = function() {
    this.config = new config;
};

utility.prototype = {
    //公用基础方法
    init: function() {
        console.log('use utility success')
    },
    /*
    * ajax请求方法
    */
    post: function($url,$data,$fun){
        var _this = this;
    	$.ajax({
            url:$url,
            type:'post',
            data: $data,
            dataType:'json',
            beforeSend:function(xhr){
                
            },
            success:function(data) {
                _this.is_login(data);
                $fun(data);

            },
            error:function(error){
                
            }
        })
    },
    /*
    * get请求方法
    */
    get: function($url,$data,$fun){
        var _this = this;
        if($data){
            $url += '?';
            for(var item in $data){
                $url += item +'='+$data[item]+'&';   
            }
            $url = _this.getLaststring($url);
        }
    	$.ajax({
            url:$url,
            type:'get',
            dataType:'json',
            success:function(data) {
                _this.is_login(data);
                $fun(data);
                
            },
            error:function(error){
                
            }
        })
    },
    /*
    * 图片上传方法A
    */
    uploadImg: function($ojb,$url,$data,$fun){
        var thisObj = $ojb;
        var _this = this;
        var allowType = ["gif", "jpeg", "jpg", "bmp",'png']; //可接受的类型
        var maxSize = 2;
        // 设置是否在上传中全局变量
        var isUploading  = false;

        thisObj.change(function(){
            if(!$url){
                _this.tips('请设置要上传的服务端地址');
                return;
            }

            var formData = new FormData();
            var files    = thisObj[0].files;
            var fileObj  = files[0];
            var inputName = thisObj.attr('name');

            if(files){
                // 目前仅支持单图上传
                formData.append(inputName, files[0]);
            }

            var postData = $data;
            if (postData) {
                for (var i in postData) {
                    formData.append(i, postData[i]);
                }
            }

            if(!fileObj){
                _this.tips('没有选中文件');
                return;
            }
            var fileName = fileObj.name;
            var fileSize = (fileObj.size)/(1024*1024);

            if (!_this.isAllowFile(fileName, allowType)) {
                _this.tips("图片类型必须是" + allowType.join("，") + "中的一种");
                return;
            }
            if(fileSize > maxSize){
                _this.tips('上传图片不能超过' + maxSize + 'M，当前上传图片的大小为'+fileSize.toFixed(2) + 'M');
                return;
            }

            if(isUploading == true){
                _this.tips('文件正在上传中，请稍候再试！');
                return;
            }

            // 将上传状态设为正在上传中
            isUploading = true;

            $.ajax({
                url: $url,
                type: "post",
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json',
                success:function(json){
                    // 将上传状态设为非上传中
                    isUploading = false;
                    $fun(json);
                },
                error:function(e){
                    console.log(e)
                }
            });
            thisObj.val(''); 
        });
    },
    /*
    * 图片上传方法
    */
    upload: function($url,$data,$fun,$maxNum){
        $maxNum = $maxNum?$maxNum:1;
        $("#upload-input").ajaxImageUpload({
            url: $url, //上传的服务器地址
            data: $data,
            maxNum: $maxNum, //允许上传图片数量
            zoom: true, //允许上传图片点击放大
            allowType: ["gif", "jpeg", "jpg", "bmp",'png'], //允许上传图片的类型
            maxSize :2, //允许上传图片的最大尺寸，单位M
            before: function () {
                //alert('上传前回调函数');
            },
            success:function(data){
                //alert('上传成功回调函数');
                $fun(data);
            },
            error:function (e) {
                //alert('上传失败回调函数');
                console.log(e);
            }
        });
    },
    /*
    * 是否是允许上传文件格式
    */
    isAllowFile: function(fileName, allowType){
        var fileExt = this.getFileExt(fileName).toLowerCase();
        if (!allowType) {
            allowType = ['jpg', 'jpeg', 'png', 'gif', 'bmp'];
        }
        if ($.inArray(fileExt, allowType) != -1) {
            return true;
        }
        return false;

    },
    /*
    * 获取上传文件的后缀名
    */
    getFileExt: function(fileName){
        if (!fileName) {
            return '';
        }

        var _index = fileName.lastIndexOf('.');
        if (_index < 1) {
            return '';
        }
        return fileName.substr(_index+1);
    },
    /*
    *判断有无登录
    */
    is_login: function(data){
        // 用户未登录
        if(data.status == 500){
            this.clearAllStorage(); //清空本地信息
            this.UrlLocation(this.config.local); //跳转至login
        }else{
            return data;
        }
    },

    /*
    * 获取URL参数
    */
    UrlSearch: function(){
        var name,value; 
        var str=location.href; //取得整个地址栏
        var num=str.indexOf("?") 
        str=str.substr(num+1); //取得所有参数   stringvar.substr(start [, length ]
        var arr=str.split("&"); //各个参数放到数组里
        var returnData = [];
        for(var i=0;i < arr.length;i++){
            num=arr[i].indexOf("=");
            if(num>0){ 
                name=arr[i].substring(0,num);
                value=arr[i].substr(num+1);
                returnData[name]=value;
            }
        }
        return returnData;
    },
    /*
    * 获取当前页面文件名
    */
    UrlName: function(){
        var a = location.href;
        var b = a.split("/");
        var c = b.slice(b.length-1, b.length).toString(String).split(".");
        return c.slice(0, 1)[0];
    },

    /*
    * 获取当前页面文件所处文件model 名字
    */
    UrlModelname: function(){
        var a = location.href;
        var b = a.split("/");
        var c = b.slice(b.length-2, b.length-1).toString(String).split(".");
        return c[0];
    },

    //获取当前页面主机部分
    UrlHost: function(){
        return window.location.host;
    },

    //跳转方法
    UrlLocation: function(url){
        if(url){
            window.location.href = url;
        }
    },

    /*
    *获取当前手机
    */
    IsMobile: function(test){
        var u = navigator.userAgent, app = navigator.appVersion;
        if(/AppleWebKit.*Mobile/i.test(navigator.userAgent) || (/MIDP|SymbianOS|NOKIA|SAMSUNG|LG|NEC|TCL|Alcatel|BIRD|DBTEL|Dopod|PHILIPS|HAIER|LENOVO|MOT-|Nokia|SonyEricsson|SIE-|Amoi|ZTE/.test(navigator.userAgent))){
                if(window.location.href.indexOf("?mobile")<0){
                    try{
                        if(/iPhone|mac|iPod|iPad/i.test(navigator.userAgent)){
                            return '0';
                        }else{
                            return '1';
                        }
                    }catch(e){}
                }   
        }else if( u.indexOf('iPad') > -1){
            return '0';
        }else{
            return '1';
        }
    },

    /*
    * 本地localStorage操作--读取
    */
    getStorage: function($key){
        var storage = window.localStorage;
        return JSON.parse(storage.getItem($key));
    },

    /*
    * 本地localStorage操作--设置
    */
    setStorage: function($key,$value){
        var storage = window.localStorage;
        return storage.setItem($key,JSON.stringify($value));
    },

    /*
    * 本地localStorage操作--删除
    */
    delStorage: function($key,$value){
        var storage = window.localStorage;
        return storage.removeItem($key);
    },

    /*
    * 本地localStorage操作--清空全部
    */
    clearAllStorage: function(){
        var storage = window.localStorage;
        return storage.clear();
    },

    /*
    * 弹出错误提示框
    */
    tips: function(str,type,hasBtn,clickDomCancel,url){
        // var _this = this;
        // type = type?type:'error';
        // str = str?str:'正在处理';
        // hasBtn = hasBtn?hasBtn:true;
        // clickDomCancel = clickDomCancel?clickDomCancel:true;

        // new TipBox({type:type,str:str,hasBtn:hasBtn,clickDomCancel:clickDomCancel});
        // if(url){
        //     $('.okoButton').click(function(){
        //         _this.UrlLocation(url);
        //     })
        // }

        $.alert(str,"提示",function(){
            // console.log(1)
        });
    },
    /**
     * 渲染分页
     * @param $total总条数
     * @param $per_page 一页加载的条数
     * @param $current_page 当前页
     * @param $last_page 最后一页(总页数)
     * @return str
     */
    Page: function(res,callback){
        var html = '';
        //设置参数
        $total = res.total;
        $per_page = res.per_page;
        $current_page = res.current_page;
        $last_page = res.last_page;

        html += '<ul class="pagination pagination-split">';
        html += '<li class=" ';
        if($current_page <= 1) {
            html += 'disabled';
        }
        html += '"><a ';
        if($current_page <= 1) {
            html += 'disabled';
        }else{
            html += ' class="aft_page" page="'+$current_page+'"';
        }

        html +=' >&laquo;</a></li>';

        //如果总页数超过10页
        if($last_page > 10 && $current_page < 10){
            for (var i = 1; i <= 9; i++) {
                html += '<li class=" ';
                if(i == $current_page){
                    html += 'active';
                }
                html += '"><a  class="page_turning" page="'+i+'">'+i+'</a></li>';
            };
            html += '<li class=""><a>...</a></li>';

            for (var i = ($last_page-4); i <= $last_page; i++) {
                html += '<li class=" ';
                if(i == $current_page){
                    html += 'active';
                }
                html += '"><a  class="page_turning" page="'+i+'">'+i+'</a></li>';
            };
        }else if($last_page > 10  && $current_page >= 10 && $current_page < 14){
            for (var i = $current_page-9; i <= $current_page; i++) {
                html += '<li class=" ';
                if(i == $current_page){
                    html += 'active';
                }
                html += '"><a  class="page_turning" page="'+i+'">'+i+'</a></li>';
            };
            html += '<li class=""><a>...</a></li>';

            for (var i = ($last_page-4); i <= $last_page; i++) {
                html += '<li class=" ';
                if(i == $current_page){
                    html += 'active';
                }
                html += '"><a  class="page_turning" page="'+i+'">'+i+'</a></li>';
            };

        }else if($last_page > 10  && $current_page >= 14 && $current_page < ($last_page-4)){
            for (var i = 1; i <= 2; i++) {
                html += '<li class=" ';
                if(i == $current_page){
                    html += 'active';
                }
                html += '"><a  class="page_turning" page="'+i+'">'+i+'</a></li>';
            };
            
            html += '<li class=""><a>...</a></li>';

            for (var i = $current_page-9; i <= $current_page; i++) {
                html += '<li class=" ';
                if(i == $current_page){
                    html += 'active';
                }
                html += '"><a  class="page_turning" page="'+i+'">'+i+'</a></li>';
            };
            html += '<li class=""><a>...</a></li>';

            for (var i = ($last_page-4); i <= $last_page; i++) {
                html += '<li class=" ';
                if(i == $current_page){
                    html += 'active';
                }
                html += '"><a  class="page_turning" page="'+i+'">'+i+'</a></li>';
            };

        }else if($last_page > 10  && $current_page >= 10 && $current_page >= ($last_page-4)){
            for (var i = 1; i <= 2; i++) {
                html += '<li class=" ';
                if(i == $current_page){
                    html += 'active';
                }
                html += '"><a  class="page_turning" page="'+i+'">'+i+'</a></li>';
            };
            html += '<li class=""><a>...</a></li>';

            for (var i = ($last_page-9); i <= $last_page; i++) {
                html += '<li class=" ';
                if(i == $current_page){
                    html += 'active';
                }
                html += '"><a  class="page_turning" page="'+i+'">'+i+'</a></li>';
            };

        }else{
            for (var i = 1; i <= $last_page; i++) {
                html += '<li class=" ';
                if(i == $current_page){
                    html += 'active';
                }
                html += '"><a  class="page_turning" page="'+i+'">'+i+'</a></li>';
            };
        }
        
        html += '<li class=" ';

        if($current_page >= $last_page) {
            html += 'disabled';
        }

        html +='"><a ';
        if($current_page >= $last_page) {
            html += 'disabled';
        }else{
            html += ' class="next_page" page="'+$current_page+'"';
        }

        html +=' >&raquo;</a></li>';

        //加上搜索框，直接跳转到某页
        html += '<li><a class="">跳转到</a></li>';
        html += '<li><input class="rediret" value="'+$current_page+'" /></li>';
        html += '<li><a class="go">GO!</a></li>';
        html += '</ul>';
        $('#page').html(html);
        callback();
    },

    //去除字符串最后一位
    getLaststring: function($str){
        return $str.substring(0,$str.length-1);
    },

    //设置cookie
    setCookie: function(c_name, value, expiredays) {
        var exdate = new Date();
        exdate.setTime(Number(exdate) + expiredays);
        document.cookie = c_name + "=" + escape(value) + ((expiredays == null) ? "" : ";expires=" + exdate.toGMTString());
    },

    //获取cookie
    getCookie: function(c_name) {
        if(document.cookie.length > 0) {
            c_start = document.cookie.indexOf(c_name + "=");//获取字符串的起点
            if(c_start != -1) {
                c_start = c_start + c_name.length + 1;//获取值的起点
                c_end = document.cookie.indexOf(";", c_start);//获取结尾处
                if(c_end == -1) c_end = document.cookie.length;//如果是最后一个，结尾就是cookie字符串的结尾
                return decodeURI(document.cookie.substring(c_start, c_end));//截取字符串返回
            }
        }
        return "";
    },

    //获取文本
    getTxet: function(type){
        var uxun = {
            "token": '代币',
            "amount": '余额'
        };
        var dzh = {
            "token": '游戏币',
            "amount": '卡金'
        };
        //优讯
        if(type == 1){
            return uxun;
        }else if(type == 2){
            return dzh;
        }else{
            return uxun;
        }
    },

    //设置文本
    keywordsupdate: function(channel_id){
        // console.log(channel_id)
        var text = this.getTxet(channel_id);
        $('.amount-s').html(text.amount);
        $('.token-s').html(text.token);
    },

    //时间戳转化
    formatDate: function(now) {
        var time = new Date(now*1000);
        var year=time.getFullYear(); 
        var month=time.getMonth()+1; 
        var date=time.getDate(); 
        var hour=time.getHours(); 
        var minute=time.getMinutes(); 
        var second=time.getSeconds(); 
        return year+"-"+month+"-"+date+" "+hour+":"+minute+":"+second; 
    }
    
};