// 测试域名
//var baseUrl = "https://testapi.kemiandan.com";
// 正式域名
var baseUrl = "https://api.kemiandan.com";

init();

// 计时器
var timer = null;

// 初始化
function init(){
	var toast = '';
	toast += '<div id="toast" class="flex-center-center" style="display: none;">';
	toast += '	<div class="content"></div>';
	toast += '</div>';
	// body 添加 Toast
	document.body.insertAdjacentHTML('beforeend',toast);
	
	var loading = '';
	loading += '<div id="loading">';
	loading += '  <div class="loader"></div>';
	loading += '</div>';
	// body 添加 loading
	document.body.insertAdjacentHTML('beforeend',loading);
}

// 显示loading
function showLoading(){
	document.getElementById('loading').style.display = 'block';
}
// 隐藏loadding
function hideLoading(){
	document.getElementById('loading').style.display = 'none';
}

// 判断是否是微信环境
function isWeiXinClient(){
    var ua = navigator.userAgent.toLowerCase();
    if(ua.match(/MicroMessenger/i)=="micromessenger") {
        return true;
    } else {
        return false;
    }
}

//获取url中的参数
function getQueryString(name) {
	var reg = new RegExp("(^|&)" + name + "=([^&]*)(&|$)", "i"); // 匹配目标参数
	var result = window.location.search.substr(1).match(reg); // 对querystring匹配目标参数
	if (result != null) {
		return decodeURIComponent(result[2]);
	} else {
		return null;
	}
}

// 错误提示
function openMark(message) {
    if(timer != null) {
        clearTimeout(timer);
    }
    $('#toast .content').text(message);
    $('#toast').show();
    timer = setTimeout(function() {
        timer = null;
        $('#toast').hide();
    }, 1000);
}


//加法
function accAdd(arg1, arg2) {
  	arg1 = arg1.toString(), arg2 = arg2.toString();
  	var arg1Arr = arg1.split("."), arg2Arr = arg2.split("."), d1 = arg1Arr.length == 2 ? arg1Arr[1] : "", d2 = arg2Arr.length == 2 ? arg2Arr[1] : "";
  	var maxLen = Math.max(d1.length, d2.length);
  	var m = Math.pow(10, maxLen);
  	var result = Number(((arg1 * m + arg2 * m) / m).toFixed(maxLen));
  	var d = arguments[2];
  	return typeof d === "number" ? Number((result).toFixed(d)) : result;
}
// 减法
function subtr(arg1, arg2) {
  	return accAdd(arg1, -Number(arg2), arguments[2]);
}