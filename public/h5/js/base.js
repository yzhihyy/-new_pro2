// 测试域名
//var baseUrl = "http://testapi.kemiandan.com";
// 正式域名
var baseUrl = "https://api.kemiandan.com";

var Loading = {
    /**
     * @param {Object} config
     * type: 加载框类型 1-7
     * content: 加载提示的文字,只有类型为7时才需要传
     */
    show:function(config){
        if(config === undefined){
            config = {type:1};
        }
        var tipText = config.content;
        if(tipText === undefined){
            tipText = "正在加载";
        }
        var loadingStr = "";
        
        switch (config.type){
            case 1:
                loadingStr += "<div class='kkjy-page-loading'>";
                loadingStr += "  <div class='kkjy-dialog-white-mask'>";
                loadingStr += "    <div class='load1'>";
                loadingStr += "      <div class='loader'></div>";
                loadingStr += "    </div>";
                loadingStr += "  </div>";
                loadingStr += "</div>";        
               break;
            case 2:
                loadingStr += "<div class='kkjy-page-loading'>";
                loadingStr += "  <div class='kkjy-dialog-white-mask'>";
                loadingStr += "    <div class='load2'>";
                loadingStr += "      <div class='loader'></div>";
                loadingStr += "    </div>";
                loadingStr += "  </div>";
                loadingStr += "</div>";            
               break;
            case 3:
                loadingStr += "<div class='kkjy-page-loading'>";
                loadingStr += "  <div class='kkjy-dialog-white-mask'>";
                loadingStr += "    <div class='load3'>";
                loadingStr += "      <div class='loader'></div>";
                loadingStr += "    </div>";
                loadingStr += "  </div>";
                loadingStr += "</div>";   
               break;
            case 4:
                loadingStr += "<div class='kkjy-page-loading'>";
                loadingStr += "  <div class='kkjy-dialog-white-mask'>";
                loadingStr += "    <div class='load4'>";
                loadingStr += "    </div>";
                loadingStr += "  </div>";
                loadingStr += "</div>";         
               break;
            case 5:
                loadingStr += "<div class='kkjy-page-loading'>";
                loadingStr += "  <div class='kkjy-dialog-white-mask'>";
                loadingStr += "    <div class='load5'>";
                loadingStr += "    </div>";
                loadingStr += "  </div>";
                loadingStr += "</div>";         
               break;
            case 6:
                loadingStr += "<div class='kkjy-page-loading'>";
                loadingStr += "  <div class='kkjy-dialog-white-mask'>";
                loadingStr += "    <div class='load6'>";
                loadingStr += "    </div>";
                loadingStr += "  </div>";
                loadingStr += "</div>";         
               break;
            case 7:
                loadingStr += "<div class='kkjy-page-loading'>";
                loadingStr += "  <div class='kkjy-dialog-white-mask'>";
                loadingStr += "    <div class='kkjy-loading'>";
                loadingStr += "      <div class='kkjy-loading-icon'></div>";
                loadingStr += "      <div class='kkjy-loading-txt'>"+ tipText +"</div>";
                loadingStr += "    </div>";
                loadingStr += "  </div>";
                loadingStr += "</div>";
                break;
            case 8:
                loadingStr += "<div class='kkjy-page-loading'>";
                loadingStr += "  <div class='kkjy-dialog-white-mask'>";
                loadingStr += "    <div class='load8'>";                
                loadingStr += "    </div>";
                loadingStr += "  </div>";
                loadingStr += "</div>";
            default:
                break;
        }
        var Loading = document.querySelector('.kkjy-page-loading');
        if(Loading){
            Loading.remove();
        }
        document.body.insertAdjacentHTML('beforeend',loadingStr);
    },
    hide:function(){
        var Loading = document.querySelector('.kkjy-page-loading');
        Loading.remove();
    }    
}

/**
 * @param {Object} config
 * message: 提示的内容
 * position: 提示的位置  可选值: "top"  "bottom"  "middle"  默认值:"middle"
 * duration: 提示时间   默认值:2000
 */
function Toast(config){
    if(!config){
        config = {};
    }
    config.position = config.position || "middle";
    if(config.duration === undefined){
        config.duration = 2000;
    }
    var position = '';
    switch (config.position){
    	case 'top':
    	   position = 'kkjy-toast is-placetop';
    	   break;
    	case 'bottom':
    	   position = 'kkjy-toast is-placebottom';
    	   break;
    	case 'middle':
    	   position = 'kkjy-toast is-placemiddle';
    	   break;
    	default:
    	   break;
    }
    var toast = '';
    var toast=document.createElement("div");
    toast.setAttribute('class',position);
    toast.insertAdjacentHTML('beforeend',"<span class='kkjy-toast-text'>"+ config.message +"</span>");
    var removeDom = function(){
        toast.removeEventListener('transitionend',removeDom);
        toast.remove();
    }
    toast.addEventListener('transitionend',removeDom);
    document.body.appendChild(toast);
    var timer = setTimeout(function(){     
        toast.style.opacity = 0;
        clearTimeout(timer);
        timer = null;
    },config.duration);
}

var Utils ={
    //秒数转化为时分秒
    formatSeconds:function(value){
        var days = parseInt(value / (60 * 60 * 24));
        var hours = parseInt((value % (60 * 60 * 24)) / (60 * 60));
        var minutes = parseInt((value % (60 * 60)) / 60);
        var seconds = value % 60;
        return {
            days: days < 10 ? '0' + days : days,
            hours: hours < 10 ? '0' + hours : hours,
            minutes: minutes < 10 ? '0' + minutes : minutes,
            seconds: seconds < 10 ? '0' + seconds : seconds,
        }
    },
    //获取url中的参数
    getQueryString:function(name){
        var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i"); // 匹配目标参数
        var result = window.location.search.substr(1).match(reg); // 对querystring匹配目标参数
        if (result != null) {
            return decodeURIComponent(result[2]);
        } else {
            return '';
        }        
    },
    // 判断是否是微信环境
    isWeiXinClient:function(){
        var ua = navigator.userAgent.toLowerCase();
        if(ua.match(/MicroMessenger/i)=="micromessenger") {
            return true;
        } else {
            return false;
        }        
    },
    // 加法
    accAdd: function(arg1, arg2){
        arg1 = arg1.toString(), arg2 = arg2.toString();
        var arg1Arr = arg1.split("."), arg2Arr = arg2.split("."), d1 = arg1Arr.length == 2 ? arg1Arr[1] : "", d2 = arg2Arr.length == 2 ? arg2Arr[1] : "";
        var maxLen = Math.max(d1.length, d2.length);
        var m = Math.pow(10, maxLen);
        var result = Number(((arg1 * m + arg2 * m) / m).toFixed(maxLen));
        var d = arguments[2];
        return typeof d === "number" ? Number((result).toFixed(d)) : result;        
    },
    // 减法
    subtr: function(arg1, arg2){
        return Utils.accAdd(arg1, -Number(arg2), arguments[2]);
    },
    // 乘法
    accMul: function(arg1, arg2){
        var r1 = arg1.toString(), r2 = arg2.toString(), m, resultVal, d = arguments[2];
        m = (r1.split(".")[1] ? r1.split(".")[1].length : 0) + (r2.split(".")[1] ? r2.split(".")[1].length : 0);
        resultVal = Number(r1.replace(".", "")) * Number(r2.replace(".", "")) / Math.pow(10, m);
        return typeof d !== "number" ? Number(resultVal) : Number(resultVal.toFixed(parseInt(d)));        
    },
    // 判断是否为ios
    isIOS:function(){
        if(/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)){
            return true;
        }
        return false;
    },
    /**
     * 通知app事件
     * @param {Object} type
     * 方法名
     * @param {Object} param
     * 参数
     */
    noticeApp:function(type, param) {
        if(/(iPhone|iPad|iPod|iOS)/i.test(navigator.userAgent)) {
            window.webkit.messageHandlers[type].postMessage(param);
        } else if(/(Android)/i.test(navigator.userAgent)) {
            var androidParams = [];
            for(var i in param) {
                androidParams.push(param[i]);
            }
            javascript: android[type](androidParams.join('|'));
        }
    }
}
