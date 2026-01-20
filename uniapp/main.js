import Vue from 'vue';
import App from './App';
import store from './androidPrivacy.js'
//Vue.prototype.goto = function(e){
//	$.goto(e.currentTarget.dataset.url)
//};
//#ifdef APP-PLUS
import {requestMerchantTransfer} from "@/uni_modules/uni-requestMerchantTransfer"
//#endif

Vue.config.productionTip = false;
Vue.mixin({
	onShareAppMessage:function(){
		return this._sharewx();
	},
	//滥用于营销、诱导等行为将会被打击https://developers.weixin.qq.com/miniprogram/dev/framework/open-ability/share-timeline.html
	// onShareTimeline:function(){
	// 	var sharewxdata = this._sharewx();
	// 	var query = (sharewxdata.path).split('?')[1]+'&seetype=circle';
	// 	return {
	// 		title: sharewxdata.title,
	// 		imageUrl: sharewxdata.imageUrl,
	// 		query: query
	// 	}
	// },
	onNavigationBarButtonTap(e) {
		console.log(e)
		var app = getApp();
		if(e.type == 'home'){
			var nowurl = app._url();
			if(nowurl.indexOf('/admin/') === 0){
				app.goto('/admin/index/index','reLaunch');
			}else{
				app.goto(app.globalData.indexurl,'reLaunch');
			}
		}
  },
	methods: {
		goto:function(e){
			getApp().goto(e.currentTarget.dataset.url,e.currentTarget.dataset.opentype)
		},
		goback:function(){
			getApp().goback();
		},
		getmenuindex:function(menuindex){
			this.menuindex = menuindex
		},
		loaded:function(obj){
			if(obj && obj.title && !obj.desc) obj.desc = obj.title
			var that = this;
			uni.stopPullDownRefresh();
			var app = getApp();
			if(app.globalData.isinit == false){
				app.get('ApiIndex/linked',{},function(){
					that.isload = true;
					that._sharemp(obj);
				});
			}else{
				this.isload = true;
				this._sharemp(obj);
			}
		},
		getdata:function(){
			var that = this;
			getApp().get('ApiIndex/linked',{},function(){
				that.loaded();
			});
		},
		getplatform:function(){
			return getApp().globalData.platform;
		},
		_sharemp:function(obj){
			//#ifdef H5
			var app = getApp();
			if(app.globalData.platform != 'mp') return;
			if(!obj) obj = {};
			if(obj.link){
				var scene = [];
				var sharepath = obj.link;
				let scenes = '';
				if(obj.link.indexOf('pid') == -1){
					if(app.globalData.mid){
						scene.push('pid_'+app.globalData.mid);
						scenes = scene.join('-');
					}
				}
				if(sharepath && sharepath.indexOf('#') > 0){
					sharepath = sharepath.split('#')[1];
				}
				if(scenes && (obj.link.indexOf('scene') == -1)){
					if(obj.link && obj.link.indexOf('?') > 0){
						sharepath = sharepath + "&scene="+scenes + '&t='+parseInt((new Date().getTime())/1000);
					}else{
						sharepath = sharepath + "?scene="+scenes + '&t='+parseInt((new Date().getTime())/1000);
					}
				}
				obj.link = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#'+ sharepath;
			}else{
				obj.link = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#'+ this.sharepath();
			} 
			var currenturl = '/' + (this.route ? this.route : this.__route__); //当前页面url
			var query = '';
			if(this.opt && this.opt.id){
				query+='?id='+this.opt.id
			}else if(this.opt && this.opt.cid){
				query+='?cid='+this.opt.cid
			}else if(this.opt && this.opt.gid){
				query+='?gid='+this.opt.gid
			}else if(this.opt && this.opt.bid){
				query+='?bid='+this.opt.bid
			}
			var currentfullurl = currenturl + query
			var sharelist = app.globalData.initdata.sharelist;
			if(sharelist){
				for(var i=0;i<sharelist.length;i++){
					if((sharelist[i]['is_rootpath']==1 && sharelist[i]['indexurl'] == currenturl) || (!sharelist[i]['is_rootpath'] && sharelist[i]['indexurl'] == currentfullurl)){
						obj.title = sharelist[i].title;
						obj.desc = sharelist[i].desc;
						obj.pic = sharelist[i].pic;
						if(sharelist[i].url){
							var sharelink = sharelist[i].url;
							if(sharelink.indexOf('/') === 0){
								sharelink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#'+ sharelink;
							}
							if(app.globalData.mid>0){
								 sharelink += (sharelink.indexOf('?') === -1 ? '?' : '&') + 'pid='+app.globalData.mid;
							}
							obj.link = sharelink;
						}
					}
				}
			}
			//app.alert('分享信息' + JSON.stringify(obj));
			var jweixin = require('jweixin-module');
			jweixin.ready(function () {   //需在用户可能点击分享按钮前就先调用
				jweixin.onMenuShareAppMessage({ 
					title: obj.title || app.globalData.initdata.name,
					desc: obj.desc || app.globalData.initdata.desc,
					link: obj.link || '',
					imgUrl: obj.pic || app.globalData.initdata.logo,
					success: function () {
						obj.callback && obj.callback();
					},
				})
				jweixin.onMenuShareTimeline({ 
					title: obj.title || app.globalData.initdata.name,
					desc: obj.desc || app.globalData.initdata.desc,
					link: obj.link || '',
					imgUrl: obj.pic || app.globalData.initdata.logo,
					success: function () {
						obj.callback && obj.callback();
					},
				})
			});
			//#endif
		},
		_sharewx:function(obj){
			if(!obj) obj = {};
			var app = getApp();
			var pages = getCurrentPages(); //获取加载的页面
			var currentPage = pages[pages.length - 1]; //获取当前页面的对象
			var currenturl = '/' + (currentPage.route ? currentPage.route : currentPage.__route__); //当前页面url 
			var query = ''
			
			var opt = this.opt;
			if(this.opt && this.opt.id){
				query+='?id='+this.opt.id
			}else if(this.opt && this.opt.cid){
				query+='?cid='+this.opt.cid
			}else if(this.opt && this.opt.gid){
				query+='?gid='+this.opt.gid
			}else if(this.opt && this.opt.bid){
				query+='?bid='+this.opt.bid
			}
			var currentfullurl = currenturl+query
			var sharelist = app.globalData.initdata.sharelist;
			if(sharelist){
				for(var i=0;i<sharelist.length;i++){
					if((sharelist[i]['is_rootpath']==1 && sharelist[i]['indexurl'] == currenturl) || (!sharelist[i]['is_rootpath'] && sharelist[i]['indexurl'] == currentfullurl)){
						obj.title = sharelist[i].title;
						obj.desc = sharelist[i].desc;
						obj.pic = sharelist[i].pic;
						obj.link = sharelist[i].url;
					}
				}
			}
			//拼接推荐人参数
			var scene = [];
			if(obj.link){
				var sharepath = obj.link;
				let scenes = '';
				if(obj.link.indexOf('pid') == -1){
					if(app.globalData.mid){
						scene.push('pid_'+app.globalData.mid);
						scenes = scene.join('-');
					}
				}
				if(sharepath && sharepath.indexOf('#') > 0){
					sharepath = sharepath.split('#')[1];
				}
				if(scenes && (obj.link.indexOf('scene') == -1)){
					if(obj.link && obj.link.indexOf('?') > 0){
						sharepath = sharepath + "&scene="+scenes + '&t='+parseInt((new Date().getTime())/1000);
					}else{
						sharepath = sharepath + "?scene="+scenes + '&t='+parseInt((new Date().getTime())/1000);
					}
				}
			}else{
				//如果有指定页面 则跳指定,没有则为本页面
				if(obj.tolink){
					let scenes = '';
					if(obj.tolink.indexOf('pid') == -1){
						if(app.globalData.mid){
							scene.push('pid_'+app.globalData.mid);
							scenes = scene.join('-');
						}
					}
					if(obj.tolink.indexOf('#') > 0){
						sharepath = obj.tolink.split('#')[1];
					}else{
						sharepath = obj.tolink
					}
					if(scenes && (obj.tolink.indexOf('scene') == -1)){
						if(obj.tolink.indexOf('?') > 0){
							sharepath = sharepath + "&scene="+scenes + '&t='+parseInt((new Date().getTime())/1000);
						}else{
							sharepath = sharepath + "?scene="+scenes + '&t='+parseInt((new Date().getTime())/1000);
						}
					}
				}else{
					var sharepath = this.sharepath();
				}
			}
			console.log('sharepath',sharepath);
			if(obj.title){
				var title = obj.title
			}else{
				var title = app.globalData.initdata.name;
			}
			if(obj.pic){
				var imgUrl = obj.pic
			}else{
				var imgUrl = '';
			}
			typeof obj.callback == 'function' && obj.callback();
			return {
				title: title,
				path: sharepath, 
				imageUrl: imgUrl
			}
		},
		sharepath:function(){
			var app = getApp();
			var opt = this.opt;
			//  #ifdef MP-ALIPAY
			var pages = getCurrentPages(); //获取加载的页面
			var currentPage = pages[pages.length - 1]; //获取当前页面的对象
			var currentpath = '/' + (currentPage.route ? currentPage.route : currentPage.__route__); //当前页面url 
			// #endif
			// #ifndef MP-ALIPAY
			var currentpath = '/' + (this.route ? this.route : this.__route__); //当前页面url
			// #endif
			var scene = [];
			for(var i in opt){
				if(i != 'pid' && i != 'scene'){
					scene.push(i+'_'+opt[i]);
				}
			}
			console.log(app.globalData.mid);
			if(app.globalData.mid){
				scene.push('pid_'+app.globalData.mid);
			}
			var scenes = scene.join('-');
			if(scenes){
				currentpath = currentpath + "?scene="+scenes + '&t='+parseInt((new Date().getTime())/1000);
			}
			return currentpath;
		},
		t:function(text){
			if(text=='color1'){
				return getApp().globalData.initdata.color1;
			}else if(text=='color2'){
				return getApp().globalData.initdata.color2;
			}else if(text=='color1rgb'){
				var color1rgb = getApp().globalData.initdata.color1rgb;
				return color1rgb['red']+','+color1rgb['green']+','+color1rgb['blue'];
			}else if(text=='color2rgb'){
				var color2rgb = getApp().globalData.initdata.color2rgb;
				return color2rgb['red']+','+color2rgb['green']+','+color2rgb['blue'];
			}else{
				return getApp().globalData.initdata.textset ? (getApp().globalData.initdata.textset[text] ? getApp().globalData.initdata.textset[text] : text) : text;
			}
		},
		inArray: function (search, array) {
			for (var i in array) {
				if (array[i] == search) {
					return true;
				}
			}
			return false;
		},
		isNull:function(param){
			if(this.isObject(param)){
				return this.isEmptyObject(param);
			}
			return (param == undefined || param == "undefined" || param == null || param == "");
		},
		isEmpty:function(list){
			if (!list || list.length === 0) {
				return true;
			}
			if(this.isObject(list)){
				return this.isEmptyObject(list);
			}
			return (!list || list.length === 0 || (list.length === 1 && (!list[(0)] || list[(0)].length === 0)))
		},
		isEmptyObject:function(obj) {
		  return JSON.stringify(obj) === '{}';
		},
		isObject:function(obj) {
		  return typeof obj === 'object' && obj !== null;
		},
		dateFormat:function(time,format){
			if(format == undefined || format == "undefined" || format == null || format == "") format = 'Y-m-d H:i:s';
			var date = new Date();
			if(time != '' || time > 0) {
				date = new Date(time * 1000);
			}
			
			var Y = date.getFullYear();
			var m = (date.getMonth() + 1 < 10 ? '0' + (date.getMonth() + 1) : date.getMonth() + 1) ;
			var d = date.getDate() < 10 ? '0' + date.getDate() : date.getDate();
			var H = date.getHours() < 10 ? '0' + date.getHours() : date.getHours();
			var i = date.getMinutes() < 10 ? '0' + date.getMinutes() : date.getMinutes();
			var s = date.getSeconds() < 10 ? '0' + date.getSeconds() : date.getSeconds();
			format = format.replace('Y',Y);
			format = format.replace('m',m);
			format = format.replace('d',d);
			format = format.replace('H',H);
			format = format.replace('i',i);
			format = format.replace('s',s);
			return format;
		},
		getDistance: function (lat1, lng1, lat2, lng2) {
			if(!lat1 || !lng1 || !lat2 || !lng2) return '';
			var rad1 = lat1 * Math.PI / 180.0;
			var rad2 = lat2 * Math.PI / 180.0;
			var a = rad1 - rad2;
			var b = lng1 * Math.PI / 180.0 - lng2 * Math.PI / 180.0;
			var r = 6378137;
			var juli = r * 2 * Math.asin(Math.sqrt(Math.pow(Math.sin(a / 2), 2) + Math.cos(rad1) * Math.cos(rad2) * Math.pow(Math.sin(b / 2), 2)));
			juli = juli/1000
			juli = juli.toFixed(2);
			return juli;
		},
		showMap:function(e){
			let latitude = parseFloat(e.currentTarget.dataset.latitude);
			let longitude = parseFloat(e.currentTarget.dataset.longitude);
			let scale = e.currentTarget.dataset.scale?parseInt(e.currentTarget.dataset.scale):13;
			let name = e.currentTarget.dataset.name;
			let address = e.currentTarget.dataset.address;
			uni.openLocation({
			 latitude:latitude,
			 longitude:longitude,
			 name:name,
			 address:address,
			 scale: 13
			})
		},
		previewImage: function (e) {
			var imgurl = e.currentTarget.dataset.url
			var imgurls = e.currentTarget.dataset.urls
			if (!imgurls) imgurls = imgurl;
			if(!imgurls) return;
			if (typeof (imgurls) == 'string') imgurls = imgurls.split(',');
			uni.previewImage({
				current: imgurl, 
				urls: imgurls 
			})
		},
		copy: function(e) {
			uni.setClipboardData({
				data: e.currentTarget.dataset.text,
				success: function () {
					getApp().error('复制成功')
				}
			});
		},
		subscribeMessage:function(callback){
			var app = getApp();
			// #ifdef MP-WEIXIN
			var that = this;
			var tmplids = that.tmplids;
			if(tmplids && tmplids.length > 0){
				uni.requestSubscribeMessage({
					tmplIds: tmplids,
					success:function(res) {
						for(var i in tmplids){
							if(res[tmplids[i]] == 'accept'){
								app.post('ApiIndex/subscribemessage',{tmplid:tmplids[i]},function(){})
							}
						}
						console.log(res)
						typeof callback == "function" && callback(res);
					},
					fail:function(res){
						console.log(res)
						typeof callback == "function" && callback(res);
					}
				})
			}else{
				typeof callback == "function" && callback();
			}
			// #endif
			// #ifndef MP-WEIXIN
			typeof callback == "function" && callback();
			// #endif
		},
		shoukuan:function(id,type='',redirect_url=''){
			//id=>数据表id type=>数据表名 redirect_url=>处理完成后要跳转的链接
			console.log('进入main.js方法');
			return new Promise((resolve, reject) => {
				var that = this;
				if(app.globalData.platform != 'wx' && app.globalData.platform != 'mp' && app.globalData.platform != 'app'){
					app.error('请在微信环境下操作');
					return;
				}
				that.loading = true;
				app.post('ApiMy/getwithdrawinfo', {id:id,type:type}, function (res) {
					that.loading = false;
					var detail = res.detail;
					var appinfo = res.appinfo;
					if(detail.platform!=app.globalData.platform){
						app.error('请在提现发起端操作收款');
						return;
					}
					if(app.globalData.platform == 'wx' ){
						if (wx.canIUse('requestMerchantTransfer')) {
						  wx.requestMerchantTransfer({
							mchId: appinfo.wxpay_mchid,
							appId: wx.getAccountInfoSync().miniProgram.appId,
							package: detail.wx_package_info,
							success: (res) => {
							  // res.err_msg将在页面展示成功后返回应用时返回ok，并不代表付款成功
							  console.log('success:', res);
							  that.loading = true;
							  app.post('ApiMy/check_withdraw_result', {id:id,type:type}, function (res) {
							  	that.loading = false;
							  	if(res.status==1){
							  		if(redirect_url){
							  			setTimeout(function () {
							  				app.goto(redirect_url);
							  			}, 1000); 
							  		}
							  		app.success(res.msg);
									resolve(true);
							  	}else{
							  		app.error(res.msg);
							  		if(redirect_url){
							  			setTimeout(function () {
							  				app.goto(redirect_url);
							  			}, 1000); 
							  		}
									resolve(false);
							  	}
							  })
							},
							fail: (res) => {
							  console.log('fail:', res);
							  if(redirect_url){
								 setTimeout(function () {
								   app.goto(redirect_url);
								 }, 1000); 
							  }
							  resolve(false);
							},
						  });
						} else {
							app.error('你的微信版本过低，请更新至最新版本。');
							if(redirect_url){
								setTimeout(function () {
									app.goto(redirect_url);
								}, 1000); 
							}
							resolve(false);
						}
					}else if(app.globalData.platform == 'mp'){
						var jweixin = require('jweixin-module');
						console.log(jweixin);
						jweixin.ready(function () {
						  jweixin.checkJsApi({
							jsApiList: ['requestMerchantTransfer'],
							success: function (res) {
							  if (res.checkResult['requestMerchantTransfer']) {
								WeixinJSBridge.invoke('requestMerchantTransfer', {
									mchId: appinfo.wxpay_mchid,
									appId: appinfo.appid,
									package: detail.wx_package_info,
								  },
								  function (res) {
									if (res.err_msg === 'requestMerchantTransfer:ok') {
									  // res.err_msg将在页面展示成功后返回应用时返回success，并不代表付款成功
									  that.loading = true;
									  app.post('ApiMy/check_withdraw_result', {id:id,type:type}, function (res) {
									  	that.loading = false;
									  	if(res.status==1){
									  		if(redirect_url){
									  			setTimeout(function () {
									  				app.goto(redirect_url);
									  			}, 1000); 
									  		}
									  		app.success(res.msg);
											resolve(true);
									  	}else{
									  		app.error(res.msg);
									  		if(redirect_url){
									  			setTimeout(function () {
									  				app.goto(redirect_url);
									  			}, 1000); 
									  		}
											resolve(false);
									  	}
									  })
									}else{
										if(redirect_url){
											app.goto(redirect_url);
										}
									}
								  }
								);
							  } else {
								alert('你的微信版本过低，请更新至最新版本。');
								if(redirect_url){
									setTimeout(function () {
										app.goto(redirect_url);
									}, 1000); 
								}
								resolve(false);
							  }
							}
						  });
						});
					}else if(app.globalData.platform == 'app'){
						//#ifdef APP-PLUS
						// app端调用商家转账收款 (安卓和iOS)
						
						requestMerchantTransfer({
							mchId: appinfo.wxpay_mchid,
							appId: appinfo.appid,
							package: detail.wx_package_info,
							success: (res) => {
								console.log('app端收款成功:', res);
								// 检查收款结果
								that.loading = true;
								app.post('ApiMy/check_withdraw_result', {id:id,type:type}, function (res) {
									console.log(res);
									that.loading = false;
									if(res.status==1){
										if(redirect_url){
											setTimeout(function () {
												app.goto(redirect_url);
											}, 1000); 
										}
										app.success(res.msg);
										resolve(true);
									}else{
										app.error(res.msg);
										// if(redirect_url){
										// 	setTimeout(function () {
										// 		app.goto(redirect_url);
										// 	}, 1000); 
										// }
										resolve(false);
									}
								})
							},
							fail: (res) => {
								console.log('app端收款失败:', res);
								// 处理不同的错误情况
								if(res.errMsg){
									if(res.errMsg.includes('cancel')){
										app.error('用户取消收款');
									}else if(res.errMsg.includes('not support')){
										app.error('当前版本不支持商家转账，请更新应用版本');
									}else if(res.errMsg.includes('fail')){
										app.error('收款失败，请重试');
									}else{
										app.error('收款异常：' + res.errMsg);
									}
								}else{
									app.error('收款失败，请重试');
								}
								if(redirect_url){
									setTimeout(function () {
										app.goto(redirect_url);
									}, 1000); 
								}
								resolve(false);
							},
							complete: (res) => {
								console.log('app端收款调用完成:', res);
							}
						});
						//#endif
					}else{
						app.error('当前环境不支持商家转账功能');
						if(redirect_url){
							setTimeout(function () {
								app.goto(redirect_url);
							}, 1000); 
						}
						resolve(false);
					}
				});
				
			});
		}
	}
});

App.mpType = 'app';

const app = new Vue({
    ...App,store
});
app.$mount();
