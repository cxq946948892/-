/** 
 * Created by cxq on 2018/5/15. 
 */
main = function() {
    this.title = '一起听歌';//默认标题
    this.basics = new utility;
    //this.loadscript = new loadscript; //加载页面文件的js
    this.config = new apiconfig;//加载配置类
    this.localUrl = this.config.localUrl();//本地路由地址
    this.userinfo = null;
    this.hostname = this.basics.UrlName(); //本页的名字
    this.modelname = this.basics.UrlModelname(); //本页的model
    this.menu; //菜单栏
    this.init();
};

main.prototype = {
    //公用基础方法
    init: function() {
        this.isLogin();
        //标记当前页面
        this.loadHtml();
        this.titleReset();
    },
    //加载头部，侧边栏，尾部
    loadHtml: function(){
  //   	$("#header").load('../header.html');
		// $("#sidebar").load('../sidebar.html');
		$("#footer").load('../footer.html');
        this.getScriptbyController();
    },
    // 更改title
    titleReset: function(){
    	$("title").html(this.title);
    },
    //获取当前页面的Controller
    getScriptbyController: function(){
    	var _this = this;
        var hostname = _this.hostname;
    	console.log("当前页面："+ _this.modelname +'/'+ hostname)
        this.addTag('script', {src: "../js/controller/"+hostname+'.js?'+new Date().getTime() },true);
    },
    //判断有没有登录,无登录就跳转至登录页面
    isLogin: function(){
        // this.userinfo = this.basics.getStorage('hc_userinfo');
        // //如果有本地玩家信息，则进行跳转
        // if(!this.userinfo){
        //     this.basics.clearAllStorage();
        //     this.basics.UrlLocation(this.localUrl.login);
        // }
    },
    addTag: function (name, attributes, sync) {
        var el = document.createElement(name),
            attrName;

        for (attrName in attributes) {
            el.setAttribute(attrName, attributes[attrName]);
        }
        sync ? window.document.write(this.outerHTML(el)) : this.headEl.appendChild(el);
    },
    outerHTML: function(node) {
        // if IE, Chrome take the internal method otherwise build one
        return node.outerHTML || (function (n) {
            var div = document.createElement('div'), h;
            div.appendChild(n);
            h = div.innerHTML;
            div = null;
            return h;
        })(node);
    }
};
_main = new main;

