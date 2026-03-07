<script>
	export default {
		globalData: {
			pre_url: '',
			baseurl: '',
			session_id: '',
			aid: 0,
			mid: 0,
			pid: 0,
			needAuth: 0,
			platform: 'wx',
			platform2: '',
			isdouyin: 0,
			sysset: [],
			indexurl: '/pages/index/index',
			menudata: [],
			menu2data: [],
			currentIndex: -1,
			initdata: {},
			textset: [],
			isinit: false,
			socketOpen: false,
			socket_token: '',
			socketMsgQueue: [],
			socketConnecttimes: 0,
			socketInterval: null,
			scene: 0,
			homeNavigationCustom: 0,
			usercenterNavigationCustom: 0,
			businessindexNavigationCustom: 0,
			navigationBarBackgroundColor: '#ffffff',
			navigationBarTextStyle: 'black',
			rewardedVideoAd:{},
			seetype:'',
			trid: 0,
			hide_home_button:0,
			indextipstatus:false,
			qrcode:'',
			priceRate:0,//价格倍率
			maidan_bid:0,//买单页面商户id
			wcode:'',//活码参数
			regbid:0,//商家推荐注册
			othermid:0,//本系统其他平台用户ID
			wxregyqcode:0,//微信注册邀请码
			tel_foreign:0,//海外手机号0关闭1开启
			adset_rewardedVideoAd:null,//adset 激励视频
      h5ToWxTempParam:0,//扫普通二维码打开小程序传参的数据id，可以是任何id，如：抽奖id，商品id，优惠券id等，不长期储存，在相应业务页面获取到后即可覆盖或销毁。
      choujiang_code:'',//扫普通二维码打开小程序抽奖页面传参的抽奖码code
      regsource:'',//注册来源，如海报邀请码
			copyinfo:'',//剪切版
			mdid:'',//门店id
		},
		onLaunch: function(options) {
			var extConfig = uni.getExtConfigSync ? uni.getExtConfigSync() : {};
			if (extConfig && extConfig.aid) {
				this.globalData.aid = extConfig.aid;
				this.globalData.pre_url = extConfig.baseurl;
				this.globalData.homeNavigationCustom = extConfig.homeNavigationCustom || 0;
				this.globalData.usercenterNavigationCustom = extConfig.usercenterNavigationCustom || 0;
				this.globalData.businessindexNavigationCustom = extConfig.businessindexNavigationCustom || 0;
				this.globalData.navigationBarBackgroundColor = extConfig.navigationBarBackgroundColor || '#ffffff';
				this.globalData.navigationBarTextStyle = extConfig.navigationBarTextStyle || 'black';
				this.globalData.hide_home_button = extConfig.hideHomeButton || 0;
			} else {
				var siteInfo = require("./siteinfo.js");
				this.globalData.aid = siteInfo.uniacid;
				// #ifdef H5
				var url = (window.location.href).split('#')[0];
				var url = url.split('?')[0];
				var urlArr = url.split('/');
				var htmlname = urlArr[urlArr.length - 1];
				var uniacid = htmlname.replace('.html', '');
				var reg = /^[0-9]+$/;
				if (uniacid && reg.test(uniacid)) {
					this.globalData.aid = uniacid;
				}
				// #endif
				this.globalData.pre_url = siteInfo.siteroot;
				this.globalData.homeNavigationCustom = siteInfo.homeNavigationCustom || 0;
				this.globalData.usercenterNavigationCustom = siteInfo.usercenterNavigationCustom || 0;
				this.globalData.businessindexNavigationCustom = siteInfo.businessindexNavigationCustom || 0;
				this.globalData.navigationBarBackgroundColor = siteInfo.navigationBarBackgroundColor || '#ffffff';
				this.globalData.navigationBarTextStyle = siteInfo.navigationBarTextStyle || 'black';
				this.globalData.hide_home_button = siteInfo.hideHomeButton || 0;
			}
			this.globalData.baseurl = this.globalData.pre_url + '/?s=/';
			this.globalData.session_id = uni.getStorageSync('session_id');
			var opts = this.getopts(options.query);
			if (opts && opts.pid) {
				this.globalData.pid = opts.pid;
				uni.setStorageSync('pid', this.globalData.pid);
			} else {
				//var pid = uni.getStorageSync('pid');
				//if (pid) this.globalData.pid = pid;
			}
			if (opts && opts.uid) {
				this.globalData.uid = opts.uid;
			}
			if (opts && opts.priceRate) {
				this.globalData.priceRate = opts.priceRate;
			}
      if (opts && opts.trid) {
      	this.globalData.trid = opts.trid;
      }
      if(options && options.query && options.query.seetype){
          this.globalData.seetype = options.query.seetype;
      }else{
          if (opts && opts.seetype) {
            this.globalData.seetype = opts.seetype;
          }
      }
      if (opts && opts.regbid) {
      	this.globalData.regbid = opts.regbid;
      }
			if (opts && opts.wxregyqcode) {
				this.globalData.wxregyqcode = opts.wxregyqcode;
			}
      if (opts && opts.othermid) {
      	this.globalData.othermid = opts.othermid;
      	uni.setStorageSync('othermid', this.globalData.othermid);
      }
      if (opts && opts.ispr) {
      	this.globalData.regsource = 'poster';//标记来源为分享海报
      }
			if(opts && opts.mdid){
				this.globalData.mdid = opts.mdid;
			}
			// #ifdef APP-PLUS
			this.globalData.platform = 'app';
			var app = this;
			uni.getSystemInfo({
				success: function(res) {
					app.globalData.platform2 = res.platform;
				}
			})
			// #endif
			// #ifdef H5
			this.globalData.platform = 'h5';
			if (navigator.userAgent.indexOf('MicroMessenger') > -1) {
				this.globalData.platform = 'mp';
			}
			// #endif
			// #ifdef MP-WEIXIN
			this.globalData.platform = 'wx';
			this.checkUpdateVersion();
			// #endif
			// #ifdef MP-ALIPAY
			this.globalData.platform = 'alipay';
			// #endif
			// #ifdef MP-BAIDU
			this.globalData.platform = 'baidu';
			// #endif
			// #ifdef MP-TOUTIAO
			this.globalData.platform = 'toutiao';
			var sysinfo = tt.getSystemInfoSync();
			if (sysinfo.appName == 'Douyin') {
				this.globalData.isdouyin = 1;
			}
			// #endif
			// #ifdef MP-QQ
			this.globalData.platform = 'qq';
			// #endif
			console.log(this.globalData.platform);

			//#ifndef MP-TOUTIAO
			var app = this;
			uni.onSocketOpen(function(res) {
				app.globalData.socketOpen = true;
				for (var i = 0; i < app.globalData.socketMsgQueue.length; i++) {
					if(i != 0) return;
					app.sendSocketMessage(app.globalData.socketMsgQueue[i]);
				}
				app.globalData.socketMsgQueue = [];
			});
			uni.onSocketClose(function(res) {
				app.globalData.socketOpen = false;
				console.log('连接关闭');
				console.log(res);
			});
			uni.onSocketMessage(function(res) {
				console.log('收到服务器内容：' + res.data);
				try {
					var data = JSON.parse(res.data);
					var needpopup = true;
					var pages = getCurrentPages();
					var currentPage = pages[pages.length - 1];
					if (!currentPage) return;
					if (currentPage && currentPage.$vm.hasOwnProperty('receiveMessage')) {
						var rs = currentPage.$vm.receiveMessage(data);
						needpopup = !rs;
					}
					if (needpopup && (data.type == 'tokefu' || data.type == 'tokehu' || data.type ==
							'peisong' || data.type == 'notice')) { //需要弹窗提示
						currentPage.$vm.$refs.popmsg.open(data);
					}
				} catch (e) {}
			});
			//#endif
		},
		onShow: function(options) {
			var that = this;
			console.log('onShow');
			console.log(options);
			console.log('---------xxxx');
			var opts = this.getopts(options.query);
			if (opts && opts.pid) {
				this.globalData.pid = opts.pid;
				uni.setStorageSync('pid', this.globalData.pid);
			}
			if (options && options.scene) {
				this.globalData.scene = options.scene;
				if(options.scene == 1154){
						this.globalData.seetype = 'circle';//查看类型为朋友圈
				}
        //识别小程序码
        if(options.scene == 1047 || options.scene == 1048 || options.scene == 1049){
          this.globalData.regsource = 'poster';//来源归类为分享海报
        }
			}
			//解析扫描普通二维码进入小程序的bid参数 start
			var qrcode_url = '';
			// #ifdef MP-ALIPAY
			if(options && options.query && options.query.id){
				this.globalData.ali_id = options.query.id;
			}
			if (options && options.query && options.query.qrCode) {
			    console.log('支付宝扫码进入')
			    qrcode_url = decodeURIComponent(options.query.qrCode);   
			}
			// #endif
			// #ifdef MP-WEIXIN
			if (options && options.query && options.query.q) {
			    console.log('微信扫码进入')
			    qrcode_url = decodeURIComponent(options.query.q);   
			}
			// #endif
			var maidan_bid = 0;
			var wcode = '';
			//二维码链接中解析bid参数
			var h5ToWxTempParam = 0;
			var choujiang_code = '';
			if(qrcode_url && qrcode_url.indexOf("?")>0){
				console.log(qrcode_url)
				var paraString = qrcode_url.substring(qrcode_url.indexOf("?")+1,qrcode_url.length).split("&");
				console.log(paraString);
				var paraObj = {}  
				for (var i=0; i<paraString.length; i++){
					var j = paraString[i];
					var key = j.substring(0,j.indexOf("=")).toLowerCase();
					if(key=='bid'){
						maidan_bid = j.substring(j.indexOf("=")+1,j.length)
					}
					if(key == 'a' && options.path =='pagesA/w'){
						wcode = j.substring(j.indexOf("=")+1,j.length)
					}
          if(key=='id'){
            h5ToWxTempParam = j.substring(j.indexOf("=")+1,j.length)
				}  
          if(key=='code'){
            choujiang_code = j.substring(j.indexOf("=")+1,j.length)
			}
        }
      }
			if(wcode){
				this.globalData.wcode = wcode;
			}
			if(maidan_bid){
				this.globalData.maidan_bid = maidan_bid;
			}
      if(h5ToWxTempParam){
        this.globalData.h5ToWxTempParam = h5ToWxTempParam;
      }
      if(choujiang_code){
        this.globalData.choujiang_code = choujiang_code;
      }
			//解析扫描普通二维码进入小程序的bid参数 end
			
			// #ifdef MP-WEIXIN
			// 分享卡片/订阅消息/扫码二维码/广告/朋友圈等场景才能调用getShareParams接口获取以下参数
			const sceneList = [1007, 1008, 1014, 1044, 1045, 1046, 1047, 1048, 1049, 1073, 1154, 1155];
			if (sceneList.includes(this.globalData.scene)) {
				console.log('---------00')
				try {
					let livePlayer = requirePlugin('live-player-plugin');
					console.log(livePlayer)
					console.log('---------11');
					livePlayer.getShareParams().then(res => {
						let custom_params = res.custom_params;
						console.log(custom_params)
						if (custom_params && custom_params.pid) {
							that.globalData.pid = custom_params.pid;
						}
					}).catch(err => {
						console.log('get share params err ', err);
					});
				} catch (e) {
					console.log('live-player-plugin err ', e);
				}
			}
			// #endif

			//#ifndef MP-TOUTIAO
			if (that.globalData.mid && that.globalData.socket_token) {
				var app = this;
				app.openSocket();
				/*
				app.sendSocketMessage({type: 'khinit',data: {aid: app.globalData.aid,mid: app.globalData.mid,platform:app.globalData.platform} });
				clearInterval(app.globalData.socketInterval);
				app.globalData.socketInterval = setInterval(function () {
					app.sendSocketMessage({type: 'connect'});
				}, 25000);
				*/
			}
			//#endif
		},
		methods: {
			isPhone: function(param, type=1) {
				if(this.globalData.tel_foreign){
					// 适用于国外手机号的通用的正则表达式
					var reg = /^\+?[0-9\s\-]{7,15}$/;
					return reg.test(param);
				}
				var regs = {
					1:/^(86|(\+86))?1[3-9]\d{9}$/, //手机号
					2:/^(\d{3,4}-)?\d{7,8}$/, //座机
					3:/^400[16789]?-?\d{3}-?\d{4}$/ //400电话
				};
				var regExp = regs[type];
				return regExp.test(param);
			},
			isIdCard: function(param) {
				var regExp =
					/^(^[1-9]\d{7}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])\d{3}$)|(^[1-9]\d{5}[1-9]\d{3}((0\d)|(1[0-2]))(([0|1|2]\d)|3[0-1])((\d{4})|\d{3}[Xx])$)$/;
				return regExp.test(param);
			},
			getopts: function(opt) {
        if(opt && opt.frompage){
          var frompagepos = opt.frompage.indexOf('amp;');
          if ( frompagepos> -1) {
            opt.frompage = opt.frompage.replace("amp;", "");
          }
        }
        // #ifdef MP-ALIPAY
        if(this.globalData && this.globalData.ali_id){
          opt.id = this.globalData.ali_id;
        }
        // #endif

				if (opt && opt.scene) {
					var scene = opt.scene
					var scenes = scene.split('-');
					//var opts = {};
					for (var i in scenes) {
						var thisscenes = scenes[i].split('_');
						opt[thisscenes[0]] = thisscenes[1];
					}
					return opt;
				} else {
					return opt
				}
			},
			alert: function(content, confirmfun) {
				uni.showModal({
					title: '提示信息',
					showCancel: false,
					content: content.toString(),
					success: function(res) {
						if (res.confirm) {
							typeof confirmfun == 'function' && confirmfun();
						}
					}
				});
			},
			confirm: function(content, confirmfun, cancelfun) {
				uni.showModal({
					title: '操作确认',
					content: content.toString(),
					showCancel: true,
					success: function(res) {
						if (res.confirm) {
							typeof confirmfun == 'function' && confirmfun();
						} else {
							typeof cancelfun == 'function' && cancelfun();
						}
					}
				});
			},
			success: function(title, successfun) {
				if (undefined == title) title = '操作成功';
				var title = title.toString();
				uni.showToast({
					title: title,
					icon: (title.length > 8 ? 'none' : 'success'),
					success: function(res) {
						typeof successfun == 'function' && successfun();
					}
				});
			},
			error: function(title, duration) {
				if (title === false) {
					uni.hideToast();
				} else {
					if (this.isNull(duration)) duration = 2500;
					if (undefined == title) title = '操作失败';
					uni.showToast({
						title: title.toString(),
						icon: 'none',
						duration: duration
					});
				}
			},
			showLoading: function(title) {
				if (title === false) {
					uni.hideLoading();
				} else {
					if (undefined == title) title = '加载中';
					uni.showLoading({
						title: title.toString(),
						mask: true
					});
				}
			},
			inArray: function(search, array) {
				for (var i in array) {
					if (array[i] == search) {
						return true;
					}
				}
				return false;
			},
			isNull: function(e) {
				return e == undefined || e == "undefined" || e == null || e == "";
			},
			parseJSON: function(e) {
				try {
					return JSON.parse(e);
				} catch (t) {
					return undefined;
				}
			},
			getparams: function(url) {
				if (url.indexOf('?') === -1) return {};
				var query = url.split('?')[1];
				var vars = query.split("&");
				var params = {};
				for (var i = 0; i < vars.length; i++) {
					var pair = vars[i].split("=");
					params[pair[0]] = pair[1];
				}
				return params;
			},
			goto: async function(tourl, opentype) {
				var app = this;
				var params = app.getparams(tourl);
				console.log(params);
				if (params && params.reloadthispage == 1) {
					var thispage = getCurrentPages().pop();
					thispage.$vm.opt = params;
					thispage.$vm.getdata();
					return;
				}
				if (app.isNull(opentype) && tourl) {
					//var currentIndex = -1;
					var tablist = app.globalData.menudata['list'];
					if (tablist) {
						for (var i = 0; i < tablist.length; i++) {
							if (tablist[i]['pagePath'] == tourl) {
								opentype = 'reLaunch';
							}
						}
					}
				}
				if (tourl.indexOf('pages/') === 0) tourl = '/' + tourl;
				if (tourl.indexOf('/pages/commission/') === 0) tourl = tourl.replace('/pages/commission/','/activity/commission/');//2.4.2
				if (tourl.indexOf('/pages/sign/') === 0) tourl = tourl.replace('/pages/sign/','/pagesExt/sign/');//2.4.3
				if (tourl.indexOf('/pages/business/') === 0) tourl = tourl.replace('/pages/business/','/pagesExt/business/');//2.4.3
				if (tourl.indexOf('/pages/lipin/') === 0) tourl = tourl.replace('/pages/lipin/','/pagesExt/lipin/');//2.4.3
				if (tourl.indexOf('/pages/order/') === 0) tourl = tourl.replace('/pages/order/','/pagesExt/order/');//2.4.4
				if (tourl.indexOf('/pages/coupon/') === 0) tourl = tourl.replace('/pages/coupon/','/pagesExt/coupon/');//2.4.8
				if (tourl.indexOf('/pages/my/') === 0 && tourl.indexOf('/pages/my/usercenter') !== 0) tourl = tourl.replace('/pages/my/','/pagesExt/my/');//2.4.6
				if (tourl.indexOf('/activity/dscj/') === 0) tourl = tourl.replace('/activity/dscj/','/pagesA/dscj/');//2.5.1
				if (tourl.indexOf('/activity/hongbaoEveryday/') === 0) tourl = tourl.replace('/activity/hongbaoEveryday/','/pagesA/hongbaoEveryday/');//2.5.1
				if (tourl.indexOf('/activity/searchmember/') === 0) tourl = tourl.replace('/activity/searchmember/','/pagesA/searchmember/');//2.5.1
				if (tourl.indexOf('/pages/money/') === 0) tourl = tourl.replace('/pages/money/','/pagesExt/money/');//2.5.4
				if (tourl.indexOf('/pages/pay/') === 0) tourl = tourl.replace('/pages/pay/','/pagesExt/pay/');//2.5.4
				if (tourl.indexOf('/pages/article/') === 0) tourl = tourl.replace('/pages/article/','/pagesExt/article/');//2.5.5
				if (tourl.indexOf('/pages/form/') === 0) tourl = tourl.replace('/pages/form/','/pagesA/form/');//2.5.5
				if (tourl.indexOf('/admin/order/maidanlog') === 0) tourl = tourl.replace('/admin/order/maidanlog/','/adminExt/order/maidanlog');//2.5.5
				if (tourl.indexOf('/pages/shop/buy') === 0) tourl = tourl.replace('/pages/shop/buy','/pagesB/shop/buy');//2.5.9
				if (tourl.indexOf('/activity/express/') === 0) tourl = tourl.replace('/activity/express/','/pagesB/express/');//2.6.1
				if (tourl.indexOf('/activity/workorder/') === 0) tourl = tourl.replace('/activity/workorder/','/pagesB/workorder/');//2.6.1
				if (tourl.indexOf('/pages/address/') === 0) tourl = tourl.replace('/pages/address/','/pagesB/address/');//2.6.1
				if (tourl.indexOf('/pages/kefu/index') === 0) tourl = tourl.replace('/pages/kefu/index','/pagesB/kefu/index');//2.6.1
				if (tourl.indexOf('/pages/index/getpwd') === 0) tourl = tourl.replace('/pages/index/getpwd','/pagesB/index/getpwd');//2.6.1
				if (tourl.indexOf('/pages/maidan/') === 0) tourl = tourl.replace('/pages/maidan/','/pagesB/maidan/');//2.6.2
				if (tourl.indexOf('/pages/shop/commentlist') === 0) tourl = tourl.replace('/pages/shop/commentlist','/pagesB/shop/commentlist');//2.6.2
				if (tourl.indexOf('/pages/shop/classify2') === 0) tourl = tourl.replace('/pages/shop/classify2','/pagesB/shop/classify2');//2.6.4
				if (tourl.indexOf('/pages/shop/product') === 0) tourl = tourl.replace('/pages/shop/product','/pagesExt/shop/product');
				if (tourl.indexOf('/pages/shop/cart') === 0) tourl = tourl.replace('/pages/shop/cart','/pagesExt/shop/cart');
				if (tourl.indexOf('/pages/shop/prolist') === 0) tourl = tourl.replace('/pages/shop/prolist','/pagesExt/shop/prolist');
				if (tourl.indexOf('/pages/shop/search') === 0) tourl = tourl.replace('/pages/shop/search','/pagesExt/shop/search');
				if (tourl.indexOf('/pages/shop/category') === 0) tourl = tourl.replace('/pages/shop/category','/pagesExt/shop/category');
				if (tourl.indexOf('/pages/shop/classify') === 0 && tourl.indexOf('/pages/shop/classify2') !== 0) tourl = tourl.replace('/pages/shop/classify','/pagesExt/shop/classify');
				if (tourl.indexOf('/pages/shop/fastbuy') === 0) tourl = tourl.replace('/pages/shop/fastbuy','/pagesExt/shop/fastbuy');
				if (tourl.indexOf('/pages/shop/mendian') === 0) tourl = tourl.replace('/pages/shop/mendian','/pagesExt/shop/mendian');
				if (tourl.indexOf('/pages/index/reg') === 0) tourl = tourl.replace('/pages/index/reg','/pagesExt/index/reg');
				if (tourl.indexOf('/pages/index/login') === 0) tourl = tourl.replace('/pages/index/login','/pagesExt/index/login');
				if (tourl.indexOf('/activity/yuyue/') === 0) tourl = tourl.replace('/activity/yuyue/','/yuyue/yuyue/');//2.6.6
				// #ifdef MP-TOUTIAO
				if (app.globalData.isdouyin == 1 && tourl.indexOf('/pages/shop/product') === 0) {
					app.showLoading('加载中');
					app.post('ApiShop/getDouyinProductId', {
						proid: params.id
					}, function(res) {
						app.showLoading(false);
						if (res.status == 1) {
							tt.openEcGood({
								promotionId: res.douyin_product_id,
								fail: function(res2) {
									app.alert(res2.errMsg)
								}
							});
						} else {
							app.alert(res.msg)
						}
					});
					return;
				}
				// #endif
				if (tourl.indexOf('[ID]') > 0) {
					tourl = tourl.replace('[ID]',app.globalData.mid);
				}
				if (tourl.indexOf('[手机号]') > 0) {
					app.post('ApiMy/getmemberinfo',{},function(res){
						tourl = tourl.replace('[手机号]',res.data.tel);
						app.goto(tourl, opentype);
					});
					return;
				}
				// #ifdef MP-WEIXIN
				if (app.globalData.platform == 'wx' && tourl.indexOf('https://work.weixin.qq.com/kfid/') === 0) {
					wx.openCustomerServiceChat({
						extInfo: {
							url: tourl
						},
						corpId: app.globalData.initdata.corpid,
						success(res) {},
						fail(res) {
							console.log('openCustomerServiceChat');
							console.log(res);
							app.alert(res.errCode+' '+res.errMsg);
						},
					});
					return;
				}
				// #endif
				if (tourl == 'scan::') {
					if (app.globalData.platform == 'h5') {
						app.alert('请使用微信扫一扫功能扫码');
						return;
					} else if (app.globalData.platform == 'mp') {
						//#ifdef H5
						// 公众号H5环境，保持原有扫码逻辑
						var jweixin = require('jweixin-module');
						jweixin.ready(function() {
							jweixin.scanQRCode({
								needResult: 0,
								scanType: ["qrCode", "barCode"],
								success: function(res) {
									var content = res.resultStr;
								}
							});
						});
						//#endif
					} else {
						// #ifndef H5
						// 小程序 / APP 环境，弹出 ActionSheet 选择操作
						uni.showActionSheet({
							itemList: ['扫一扫', '拍照上传'],
							success: function(sheetRes) {
								if (sheetRes.tapIndex === 0) {
									// 扫码：保持原有逻辑
									uni.scanCode({
										success: function(res) {
											console.log(res);
											if (res.path) {
												app.goto('/' + res.path);
											} else {
												var content = res.result;
												app.goto(content);
											}
										}
									});
								} else if (sheetRes.tapIndex === 1) {
									// 拍照上传
									app._scanTakePhotoAndUpload();
								}
							}
						});
						// #endif
					}
					return;
				}
				if (tourl == 'share::') {
					if (app.globalData.platform == 'h5' || app.globalData.platform == 'mp') {
						app.error('点击右上角发送给好友或分享到朋友圈');
					}
					if (app.globalData.platform == 'app') {
						//#ifdef APP-PLUS
						uni.showActionSheet({
							itemList: ['发送给微信好友', '分享到微信朋友圈'],
							success: function(res) {
								if (res.tapIndex >= 0) {
									var scene = 'WXSceneSession';
									if (res.tapIndex == 1) {
										scene = 'WXSenceTimeline';
									}
									var sharedata = {};
									sharedata.provider = 'weixin';
									sharedata.type = 0;
									sharedata.scene = scene;
									sharedata.title = app.globalData.initdata.name;
									var fullurl = app._fullurl();
									if (app.globalData.mid > 0) {
										fullurl += (fullurl.indexOf('?') === -1 ? '?' : '&') + 'pid=' + app
											.globalData.mid
									}
									sharedata.href = app.globalData.pre_url + '/h5/' + app.globalData.aid +
										'.html#' + fullurl;
									sharedata.imageUrl = app.globalData.initdata.logo;
									sharedata.summary = app.globalData.initdata.desc;

									var sharelist = app.globalData.initdata.sharelist;
									if(sharelist){
										for(var i=0;i<sharelist.length;i++){
											if(sharelist[i]['indexurl'] == app._fullurl()){
												sharedata.title = sharelist[i].title;
												sharedata.summary = sharelist[i].desc;
												sharedata.imageUrl = sharelist[i].pic;
												if(sharelist[i].url){
													var sharelink = sharelist[i].url;
													if(sharelink.indexOf('/') === 0){
														sharelink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#'+ sharelink;
													}
													if(app.globalData.mid>0){
														 sharelink += (sharelink.indexOf('?') === -1 ? '?' : '&') + 'pid='+app.globalData.mid;
													}
													sharedata.href = sharelink;
												}
											}
										}
									}
									uni.share(sharedata);
								}
							}
						});
						//#endif
					}
					return;
				}
				if (!tourl || tourl == 'contact::' || tourl == 'share::') return;
				if (tourl.indexOf('tel::') === 0) {
					//打电话
					// #ifdef APP-PLUS
					let result = await this.$store.dispatch("requestPermissions",'CALL_PHONE')
					if (result !== 1) return;
					// #endif
					tourl = tourl.slice(5);
					uni.makePhoneCall({
						phoneNumber: tourl
					});
					return;
				}
				if (tourl.indexOf('tel:') === 0) {
					//打电话
					// #ifdef APP-PLUS
					let result = await this.$store.dispatch("requestPermissions",'CALL_PHONE')
					if (result !== 1) return;
					// #endif
					tourl = tourl.slice(4);
					uni.makePhoneCall({
						phoneNumber: tourl
					});
					return;
				}
				if (tourl.indexOf('url::') === 0) { //外部链接
          //外部链接奖励
          app.get('ApiIndex/getCustom',{type:'wurl_reward',tourl:tourl}, function (customs) {});
					if (app.globalData.platform == 'h5' || app.globalData.platform == 'mp') {
						location.href = tourl.slice(5);
					} else {
						tourl = '/pages/index/webView?url=' + encodeURIComponent(tourl.slice(5));
					}
				}
				if (tourl.indexOf('https://') === 0 || tourl.indexOf('http://') === 0) { //外部链接
          //外部链接奖励
          app.get('ApiIndex/getCustom',{type:'wurl_reward',tourl:tourl}, function (customs) {});
					//判断外链参数
					if(app.globalData.initdata){
						if(app.globalData.initdata.encryptparamtype==1){
							tourl += (tourl.indexOf('?') > 0 ? '&' : '?') + 'dianda_aid=' + app.globalData.aid + '&dianda_mid=' + app.globalData.mid;
						}else if(app.globalData.initdata.encryptparamtype==2 && app.globalData.initdata.encryptparam){
							//加密参数「对接阶段保留明文」
							tourl += (tourl.indexOf('?') > 0 ? '&' : '?') + 'dianda_aid=' + app.globalData.aid + '&dianda_mid=' + app.globalData.mid;
							tourl += (tourl.indexOf('?') > 0 ? '&' : '?')+'dianda='+app.globalData.initdata.encryptparam
						}
					}
					//绑定公众号追加mid
					if(tourl.indexOf('ApiMpBind/mpbind') > 0){
						tourl += '&mid=' + app.globalData.mid;
					}

					if (app.globalData.platform == 'h5' || app.globalData.platform == 'mp') {
						location.href = tourl;
					} else {
						tourl = '/pages/index/webView?url=' + encodeURIComponent(tourl);
					}
				}
				if (tourl.indexOf('miniProgram::') === 0) {
					//其他小程序
					tourl = tourl.slice(13);
					var tourlArr = tourl.split('|'); //console.log(tourlArr)
          var sendmid = false;//是否传递此平台用户ID
          if(tourlArr && tourlArr.length>=1){
            for(var i=0;i<tourlArr.length;i++){
              if(tourlArr[i] == 'sendmid'){
                sendmid = true;
              }
            }
          }
          var params = {
            tourl:tourl,
            tourlArr:tourlArr
          }
          if(sendmid){
            app.post('ApiMy/getmemberinfo',{},function(res){
              if(res.status == 1){
                params['sendmid'] = true;
                params['othermid']= res.data.id;
                app.toMiniProgram(params);
              }else{
                app.alert('登录失败')
              }
            });
          }else{
            app.toMiniProgram(params);
          }
					return;
				}
				if (tourl.indexOf('embeddedMiniProgram::') === 0) {
					console.log('半屏小程序打开');
					tourl = tourl.slice(21);
					var tourlArr = tourl.split('|'); console.log(tourlArr)
					uni.openEmbeddedMiniProgram({
						appId: tourlArr[0],
						path: tourlArr[1] ? tourlArr[1] : '',
						extraData: {},
						success(res) {
							console.log('半屏小程序打开');
						}
					})
				}
				if (tourl == 'getmembercard::') {
					//领取会员卡
					app.post('ApiCoupon/getmembercardparam', {
						card_id: ''
					}, function(res) {
						if (res.status == 0) {
							app.alert(res.msg);
							return;
						}
						if (app.globalData.platform == 'wx') {
							uni.navigateToMiniProgram({
								appId: 'wxeb490c6f9b154ef9',
								// 固定为此appid，不可改动
								extraData: res.extraData,
								// 包括encrypt_card_id outer_str biz三个字段，须从step3中获得的链接中获取参数
								success: function() {},
								fail: function() {},
								complete: function() {}
							});
						} else {
							location.href = res.ret_url;
						}
					});
					return;
				}
				if (tourl.indexOf('location::') === 0) {
					//坐标导航
					tourl = tourl.slice(10);
					var tourlArr = tourl.split('|');
					var jwd = tourlArr[1].split(',');
					uni.openLocation({
						latitude: parseFloat(jwd[1]),
						longitude: parseFloat(jwd[0]),
						name: tourlArr[0],//支付宝必填
						address: tourlArr[0],//支付宝必填
						scale: 13,
						fail: function (error) {
							console.log(error);
						}
					});
					return;
				}
				if (tourl.indexOf('plugin-private://wx2b03c6e691cd7370/pages/live-player-plugin') === 0) {
					//小程序直播 带参数
					tourl = tourl + '&custom_params=' + encodeURIComponent(JSON.stringify({
						pid: app.globalData.mid
					}));
				}
				if (tourl.indexOf('copy::') === 0) {
					//复制
					tourl = tourl.slice(6);
					var tourlArr = tourl.split('|');
					uni.setClipboardData({
						data: tourlArr[0],
						showToast: false,
						success: function () {
							if(tourlArr[1]) app.alert(tourlArr[1]);
							else app.alert('复制成功');
						},
						fail: function (cres) {
							console.log(cres)
						}
					});
					return;
				}
				if (tourl.indexOf('rewardedVideoAd::') === 0) {
					console.log('rewardedVideoAd')
					tourl = tourl.slice(17);
					var tourlArr = tourl.split('|');
					if(wx.createRewardedVideoAd){
						app.showLoading();
						app.post('ApiRewardVideoAd/getunitid',{hid:tourlArr[0]},function(postres){
							//app.showLoading(false);
							if(postres.status == 0){
								app.alert(postres.msg);return;
							}
							if(!app.globalData.rewardedVideoAd[postres.unitid]){
								app.globalData.rewardedVideoAd[postres.unitid] = wx.createRewardedVideoAd({ adUnitId: postres.unitid});
							}
							var rewardedVideoAd = app.globalData.rewardedVideoAd[postres.unitid];
							rewardedVideoAd.load().then(() => {app.showLoading(false);rewardedVideoAd.show();}).catch(err => { app.alert('加载失败');});
							rewardedVideoAd.onError((err) => {
								app.showLoading(false);
								app.alert(err);
								console.log('onError event emit', err)
								rewardedVideoAd.offLoad()
								rewardedVideoAd.offClose();
							});
							rewardedVideoAd.onClose(res => {
								if (res && res.isEnded) {
									//app.alert('播放结束 发放奖励');
									app.post('ApiRewardVideoAd/givereward',{hid:tourlArr[0]},function(res3){
										if(res3.status == 0){
											app.alert(res3.msg);
										}else{
											app.success(res3.msg);
											if(res3.url){
												app.goto(res3.url,'redirect');
											}
											
										}
									});
								} else {
									console.log('播放中途退出，不下发奖励');
								}
								rewardedVideoAd.offLoad()
								rewardedVideoAd.offClose();
							});
						});
					}
					return false;
				}
				//#ifdef APP-PLUS
				if(tourl.indexOf('adsetReward::') === 0){
					app.showLoading();
					tourl = tourl.slice(13);
					var tourldata = tourl.split('|');
					if(uni.createRewardedVideoAd){
						if(app.globalData.adset_rewardedVideoAd ==null){
							 app.globalData.adset_rewardedVideoAd  = uni.createRewardedVideoAd({
								adpid:tourldata[1],	
								// adpid:'1507000689'
							}) // 仅用于HBuilder基座调试 adpid: '1507000689'
						}
						var adset_rewardedVideoAd = app.globalData.adset_rewardedVideoAd;
						adset_rewardedVideoAd.load().then(() => { app.showLoading(false);adset_rewardedVideoAd.show()}).catch(err => { app.alert('加载失败');});
						 adset_rewardedVideoAd.onError((err) => {
						 // 广告加载失败
							app.showLoading(false);
							app.alert(err.errMsg+err.code);
							adset_rewardedVideoAd.offLoad()
							adset_rewardedVideoAd.offClose();
							app.globalData.adset_rewardedVideoAd.offLoad();
							app.globalData.adset_rewardedVideoAd.offClose();
							adset_rewardedVideoAd = null;
							app.globalData.adset_rewardedVideoAd =null;
						 })
						adset_rewardedVideoAd.onClose((res) => {
							app.showLoading(false);
							adset_rewardedVideoAd.offLoad()
							adset_rewardedVideoAd.offClose();
							adset_rewardedVideoAd = null;
							app.globalData.adset_rewardedVideoAd.offLoad();
							app.globalData.adset_rewardedVideoAd.offClose();
							app.globalData.adset_rewardedVideoAd =null;
							if (res && res.isEnded) {
							      // 正常播放结束
							  app.post('ApiRewardVideoAd/giveAdsetReward',{id:tourldata[0]},function(postres){
								//app.showLoading(false);
								if(postres.status == 0){
									app.alert(postres.msg);return;
								} 	
							  });
							} else {
							  // 播放中途退出
							
							}
							
						})
					}	
					return false;
				}	
				//#endif
				if (tourl.indexOf('sendsms::') === 0) {
					tourl = tourl.slice(9);
					var tourlArr = tourl.split('|');
					wx.chooseContact({
						success:function(res){
							console.log(res);
							if(res.phoneNumber){
								app.confirm('确定要给'+res.displayName+'('+res.phoneNumber+')发送短信吗?',function(){
									app.showLoading();
									app.post('ApiIndex/sendsmsurl',{tel:res.phoneNumber,tmpl:tourlArr[0],link:tourlArr[1]},function(res2){
										app.showLoading(false);
										if(res2.status == 0){
											app.alert(res2.msg);
										}else{
											app.success(res2.msg);
										}
									});
								})
								//app.alert('手机号:'+res.phoneNumber)
							}else{
								app.alert('未获取到手机号')
							}
						}
					});
					return false;
				}
				if (tourl.indexOf('channelsUserProfile::') === 0) {
					//console.log('视频号主页');
					//#ifdef MP-WEIXIN
					let finderUserName = tourl.slice(21);
					wx.openChannelsUserProfile({
						finderUserName: finderUserName,
						success(res) {
						},
						fail(res) {
							console.log(res);
							//app.alert(res.errno+res.errMsg)
						},
					})
					//#endif
				}
				if (tourl.indexOf('channelsLive::') === 0) {
					//console.log('视频号直播');
					//#ifdef MP-WEIXIN
					let finderUserName = tourl.slice(14);
					wx.openChannelsLive({
						finderUserName: finderUserName,
						success(res) {
						},
						fail(res) {
							console.log(res);
							//app.alert(res.errno+res.errMsg)
						},
					})
					//#endif
				}
				if (tourl.indexOf('connectwifi::') === 0) {
					//连接wifi
					uni.getSystemInfo({
						success: (res) => {
							console.log(res,'xxxx');
							let system = ''
							// console.log("当前手机型号===>",res)
							if(res.platform == 'android'){
								system = parseInt(res.platform.substr(8))
							}
							if(res.platform =='ios'){
								system = parseInt(res.platform.substr(4))
							}
							if(res.platform == 'android' && system < 6){
								uni.showToast({
									title:'手机版本不支持',
									icon:'none'
								})
								return
							}
							if(res.platform == 'ios' && system < 11.2){
								uni.showToast({
									title:'手机版本不支持',
									icon:'none'
								})
								return
							}	
						}
					})

					tourl = tourl.slice(13);
					var tourlArr = tourl.split('|');
					var wifi_name = tourlArr[0];
					var wifi_password = tourlArr[1];
					app.showLoading('连接中');
					if(app.globalData.platform =='wx'){
						//#ifdef MP-WEIXIN
						uni.startWifi({
							success: (res) => {
								console.log(res,'启动wifi')
							},
							fail: (error) => {
								app.error();
							}
						})
						
						uni.connectWifi({
							SSID:wifi_name, //Wi-Fi 设备名称
							password:wifi_password,//Wi-Fi 密码
							success: (res) => {
								app.success('连接成功');
							},
							fail: (error) => {
								console.log(error)
								app.error('连接失败')
							}
						})
						//#endif
					}else if(app.globalData.platform =='alipay'){
						//#ifdef MP-ALIPAY
					
						my.startWifi({
							success: function(res) {
								// 再连接 Wi-Fi
								my.connectWifi({
									SSID: wifi_name,
									password: wifi_password,
									success: function(res) {
										app.success('连接成功');
										app.showLoading(false);
									},fail:function(res2){
										app.error('连接失败')
									}
								})
							}
						})
						//#endif
					}
					
				}
				if (app.isNull(opentype)) {
					var mv = tourl.split("?");
					var urlpath = mv[0];
					if (app.globalData.platform == 'h5' && urlpath == app._fullurl()) {
						opentype = 'reLaunch';
					} else {
						opentype = 'navigate';
					}
				}
				if (tourl.indexOf('mppage::') === 0) {
					// #ifdef MP-WEIXIN
					tourl = tourl.slice(8);
					 wx.openOfficialAccountProfile({
					     username: tourl,
					     success: res => {},
					     fail: err => {
								 app.error('无效参数，请检查')
							 }
					 })
					return;
					// #endif
				}
				app.globalData.rewardedVideoAd = {};
				if (opentype == 'switchTab') {
					var args = new Object();
					var name, value;
					var url = tourl; //跳转链接
					var num = url.indexOf("?");
					if (num >= 0) {
						var urlbase = url.substr(0, num);
						var str = url.substr(num + 1); //取得所有参数

						var arr = str.split("&"); //各个参数放到数组里

						for (var i = 0; i < arr.length; i++) {
							num = arr[i].indexOf("=");

							if (num > 0) {
								name = arr[i].substring(0, num);
								value = arr[i].substr(num + 1);
								args[name] = value;
							}
						}
						uni.switchTab({
							url: urlbase,
							success: function(e) {
								var page = getCurrentPages().pop();
								if (page == undefined || page == null) return;
								page.onLoad(args);
							}
						});
					} else {
						uni.switchTab({
							url: tourl
						});
					}
				} else if (opentype == 'redirect' || opentype == 'redirectTo') {
					uni.redirectTo({
						url: tourl
					});
				} else if (opentype == 'reLaunch') {
					uni.reLaunch({
						url: tourl
					});
				} else {
					var pages = getCurrentPages();

					if (pages.length >= 10) {
						uni.redirectTo({
							url: tourl
						});
					} else {
						uni.navigateTo({
							url: tourl
						});
					}
				}
			},
      toMiniProgram:function(params= {tourl:'',tourlArr:[]}){
        var app = this;
        var tourl    = params && params['tourl'] || '';
        var tourlArr = params && params['tourlArr'] || [];
        if(tourlArr && tourlArr[1] && params && params['sendmid'] && params['othermid'] && params['othermid']>0){
          var pos = tourlArr[1].indexOf('?');
          if(pos && pos>0){
            tourlArr[1] += '&othermid='+params['othermid'];
          }else{
            tourlArr[1] += '?othermid='+params['othermid'];
          }
        }
        if(app.globalData.platform == 'app'){
        	//#ifdef APP-PLUS
        	plus.share.getServices(function(res){  
        		var sweixin = null;  
        		for(var i=0;i<res.length;i++){  
        			var t = res[i];  
        			if(t.id == 'weixin'){  
        				sweixin = t;  
        			}  
        		}  
        		if(sweixin){  
        			sweixin.launchMiniProgram({  
        				id:tourlArr[2],  
        				type: 0,
        				path:tourlArr[1] ? tourlArr[1] : '',
        			});  
        		}
        	},function(res){  
        		console.log(JSON.stringify(res));  
        	});
        	//#endif
        }else{
					//#ifdef MP-WEIXIN || MP-ALIPAY || MP-BAIDU || MP-TOUTIAO
        	uni.navigateToMiniProgram({
        		appId: tourlArr[0],
        		path: tourlArr[1] ? tourlArr[1] : '',
        		complete: function() {
        		},
        		success:function(){
        			if(tourlArr[3]){
        				app.post('ApiRewardVideoAd/givereward',{hid:tourlArr[3]},function(res3){
        					if(res3.status == 0){
        						//app.alert(res3.msg);
        					}else{
        						app.success(res3.msg);
        						if(res3.url){
        							app.goto(res3.url,'redirect');
        						}
        					}
        				});
        			}
        		}
        	});
					//#endif
        }
      },
			goback: function(isreload) {
				var app = this;
				var pages = getCurrentPages();
				if (isreload && pages.length > 1) {
					var prePage = pages[pages.length - 2];
					prePage.$vm.getdata();
				}
				if (pages.length == 1) {
					app.goto(app.globalData.indexurl, 'reLaunch');
				} else {
					uni.navigateBack({
						fail: function() {
							app.goto(app.globalData.indexurl, 'reLaunch');
						}
					});
				}
			},
			post: function(url, param, callback) {
				this.request('POST', url, param, callback);
			},
			get: function(url, param, callback) {
				this.request('GET', url, param, callback);
			},
			request: function(method, url, param, callback) {
				var oldurl = url;
				var app = this;
				if (url.substring(0, 8) != 'https://') {
					url = app.globalData.baseurl + url;
					url += (url.indexOf('?') > 0 ? '&' : '?') + 'aid=' + app.globalData.aid + '&platform=' + app
						.globalData.platform + '&session_id=' + app.globalData.session_id + '&pid=' + app.globalData
						.pid;
					if (app.globalData.isdouyin == 1) {
						url += '&isdouyin=1';
					}
					if (!app.globalData.isinit) {
						url += '&needinit=1';
						if (app.globalData.uid) url += '&uid=' + app.globalData.uid;
					}
					if (app.globalData.seetype) {
						url += '&seetype=' + app.globalData.seetype;
					}
					if (app.globalData.scene) {
						url += '&scene=' + app.globalData.scene;
					}
					if (app.globalData.priceRate) {
						url += '&priceRate=' + app.globalData.priceRate;
					}
				}
				uni.request({
					url: url,
					//仅为示例，并非真实的接口地址
					data: param,
					method: method,
					success: function(res) {
						app.setinitdata(res);
				 
						if (res.data && res.data.status == -1) { //跳转登录页
							if(res.data.data){
								if(res.data.data.pid && res.data.data.pid>0){
								app.globalData.pid = res.data.data.pid;
								uni.setStorageSync('pid',res.data.data.pid);
								}
							}
							app.showLoading(false);
							if((app.globalData.initdata.logintype).length == 0 && app.inArray(app.globalData.platform,['wx','mp','baidu','qq','toutiao','alipay'])) return;
							var pages = getCurrentPages();
							var currentPage = pages[pages.length - 1];
							currentPage.$vm.loading = false;
							var frompage = '';
							var nowurl = app._url();						 
							var opentype = 'reLaunch';
							if (app.globalData.platform == 'baidu') {
								if (nowurl == '/pages/my/usercenter') {
									opentype = 'redirect';
								} else {
									opentype = 'navigate';
								}
							}
							if (nowurl != '/pages/index/login' && nowurl != '/pages/index/reg' && nowurl !=
								'/pages/index/getpwd') {
								var frompage = encodeURIComponent(app._fullurl());
							}
							if (res.data.authlogin == 1 || res.data.authlogin == 2) {
								app.authlogin(function(res2) {
									if (res2.status == 1) {
										if (res2.msg) app.success(res2.msg);
										currentPage.$vm.getdata();
									} else if (res2.status == 2) {
										app.goto('/pages/index/login?frompage=' + frompage +
											'&logintype=4&login_bind=1', opentype);
									}else {
										console.log(res2);
										app.goto('/pages/index/login?frompage=' + frompage,
											opentype);
									}
								},{authlogin:res.data.authlogin,ali_appid:res.data.ali_appid});
							} else {
								app.goto('/pages/index/login?frompage=' + frompage, opentype);
								return;
							}
						} else if (res.data && res.data.status == -10) { //跳转管理员登录
							app.showLoading(false);
							var pages = getCurrentPages();
							var currentPage = pages[pages.length - 1];
							currentPage.$vm.loading = false
							//管理员登录
							app.goto('/admin/index/login', 'redirect');
						} else if (res.data && res.data.status == -2) { //公众号或h5跳转
							app.showLoading(false);
							var pages = getCurrentPages();
							var currentPage = pages[pages.length - 1];
							currentPage.$vm.loading = false
							//跳转
							location.href = res.data.url
						} else if (res.data && res.data.status == -3) { //跳转到指定页
							app.showLoading(false);
							var pages = getCurrentPages();
							var currentPage = pages[pages.length - 1];
							currentPage.$vm.loading = false
							//跳转
							app.goto(res.data.url, 'redirect');
						} else if (res.data && res.data.status == -4) { //弹窗提示并跳转到指定页
							app.showLoading(false);
							var pages = getCurrentPages();
							var currentPage = pages[pages.length - 1];
							currentPage.$vm.loading = false
							// 判断是否为手机后台商品编辑页面，带有上一页的筛选参数
							if(currentPage.route == 'admin/product/edit'){
								let cids = currentPage.options.cids;
								//弹出提示
								app.alert(res.data.msg, function() {
									if (res.data.url) {
										const url = res.data.url + '?cids=' + cids;
										app.goto(url, 'redirect');
									}
								});
							}else{
								//弹出提示
								app.alert(res.data.msg, function() {
									if (res.data.url) {
										app.goto(res.data.url, 'redirect');
									}
								});								
							}
						} else if (res.data && res.data.status == -5) { //弹窗提示并跳转上一页
							app.showLoading(false);
							var pages = getCurrentPages();
							var currentPage = pages[pages.length - 1];
							currentPage.$vm.loading = false
							//弹出提示
							app.alert(res.data.msg, function() {
								uni.navigateBack({ delta: 1 })
							});
						}else if (res.data && res.data.status == -6) { //弹窗提示并跳转上一页
							app.showLoading(false);
							var pages = getCurrentPages();
							var currentPage = pages[pages.length - 1];
							    currentPage.$vm.loading = false							 					 							 
								app.alert(res.data.msg, function(frompage) {									 		
								var frompage = '/pages/my/usercenter';
 						    	  app.goto('/pages/index/login?frompage='+frompage+'&logintype=5&checknickname=1', 'reLaunch')
								});		 		 
					
						} else {
							typeof callback == "function" && callback(res.data);
						}
					},
					fail: function(res) {
						try {
							if (oldurl != 'ApiIndex/linked') {
								if (res.errMsg != 'request:fail timeout') {
									console.log(res);
									//app.alert('请求失败：' + JSON.stringify(res));
								}
							}
						} catch (e) {}
					}
				});
			},
			baselogin:function(callback){
				console.log('baselogin');
				var app = this;
				if (app.globalData.platform == 'wx') {
					//#ifdef MP-WEIXIN
					wx.login({
						success(res1) {
							var code = res1.code;
							app.post('ApiIndex/wxbaselogin', {
								code: code,
								pid: app.globalData.pid
							}, function(res) {
								typeof callback == "function" && callback(res);
							});
						}
					});
					//#endif
				} else if (app.globalData.platform == 'baidu') {
					//#ifdef MP-BAIDU
					uni.getLoginCode({
						success(res1) {
							console.log('getLoginCode',res1);
							var code = res1.code;
							app.post('ApiIndex/baidulogin', {
								code: code,
								pid: app.globalData.pid
							}, function(res) {
								typeof callback == "function" && callback(res);
							});
						},
						fail(res2){
							console.log(res2)
						}
					});
					//#endif
				} else if (app.globalData.platform == 'qq') {
					//#ifdef MP-QQ
					qq.login({
						success(res1) {
							app.post('ApiIndex/qqlogin', {
								code: res1.code,
								pid: app.globalData.pid
							}, function(res) {
								typeof callback == "function" && callback(res);
							});
						}
					})
					//#endif
				} else if (app.globalData.platform == 'toutiao') {
					//#ifdef MP-TOUTIAO
					tt.login({
						force: true,
						success(res1) {
							app.post('ApiIndex/toutiaologin', {
								code: res1.code,
								pid: app.globalData.pid
							}, function(res) {
								typeof callback == "function" && callback(res);
							});
						}
					})
					//#endif
				} else if (app.globalData.platform == 'alipay') {
					//#ifdef MP-ALIPAY
					my.getAuthCode({
						scopes: 'auth_base',
						success(res1) {
							app.post('ApiIndex/alipaylogin', {
								code: res1.authCode,
								pid: app.globalData.pid
							}, function(res) {
								typeof callback == "function" && callback(res);
							});
						}
					});
					//#endif
				}else if (app.globalData.platform == 'mp') {
					//#ifdef H5
					var frompage = '';
					var nowurl = app._url();
					if (nowurl != '/pages/index/login' && nowurl != '/pages/index/reg' && nowurl !=
						'/pages/index/getpwd') {
						frompage = encodeURIComponent(app._fullurl());
					}
					location.href = app.globalData.pre_url + '/index.php?s=ApiIndex/mpbaselogin&aid=' + app.globalData
						.aid + '&session_id=' + app.globalData.session_id + '&pid=' + app.globalData.pid +
						'&frompage=' + encodeURIComponent(frompage);
					//#endif
				}
			},
			authlogin: function(callback, params) {
				var app = this;
				var authlogin = 0;
				if (params && params.authlogin) {
					authlogin = params.authlogin;
				}
				if (app.globalData.platform == 'wx') {
					//#ifdef MP-WEIXIN
					if(authlogin==1 || authlogin==2){
						wx.login({
							success(res1) {
								var code = res1.code;
								app.post('ApiIndex/wxlogin', {
									code: code,
									authlogin:authlogin,
									pid: app.globalData.pid,
									yqcode:!params?'':params.yqcode,
									regbid:app.globalData.regbid,
                  regsource:app.globalData.regsource,
									mdid:app.globalData.mdid
								}, function(res) {
									typeof callback == "function" && callback(res);
								});
							}
						});
					}else{
						wx.getUserProfile({
							lang: 'zh_CN',
							desc: '用于展示头像昵称',
							success: function(res2) {
								console.log(res2)
								var userinfo = res2.userInfo;
								wx.login({
									success(res1) {
										var code = res1.code;
										app.post('ApiIndex/wxlogin', {
											code: code,
											userinfo: userinfo,
											pid: app.globalData.pid,
											yqcode:!params?'':params.yqcode,
											regbid:app.globalData.regbid,
                      regsource:app.globalData.regsource,
											mdid:app.globalData.mdid
										}, function(res) {
											typeof callback == "function" && callback(res);
										});
									}
								});
							},
							fail: function(res2) {
								console.log(res2)
								if (res2.errMsg == 'getUserProfile:fail auth deny') {

								} else {
									typeof callback == "function" && callback({
										status: 0,
										msg: res2.errMsg
									});
								}
							}
						});
					}
					//#endif
				} else if (app.globalData.platform == 'mp' || app.globalData.platform == 'h5') {
					//#ifdef H5
					var frompage = '';
					var nowurl = app._url();
					if (nowurl != '/pages/index/login' && nowurl != '/pages/index/reg' && nowurl !=
						'/pages/index/getpwd') {
						frompage = encodeURIComponent(app._fullurl());
					}
					if (params && params.frompage) {
						frompage = params.frompage;
					}
					if (navigator.userAgent.indexOf('AlipayClient') > -1) {
						ap.getAuthCode ({
						    appId :  params.ali_appid ,
						    scopes : ['auth_base'],
						},function(res){
						   //var res = JSON.stringify(res);
						    if(!res.error && res.authCode){
						        app.post('ApiIndex/alipaylogin', {
						        	code: res.authCode,
						        	pid: app.globalData.pid,
						          platform:"h5",
                      silent:1,
                      regbid:app.globalData.regbid,
                      regsource:app.globalData.regsource,
											mdid:app.globalData.mdid
						        }, function(res2) {
									typeof callback == "function" && callback(res2);
						        });
						    }else{
						      app.showLoading(false);
						      return
						    }
						});
					}else{
						location.href = app.globalData.pre_url + '/index.php?s=ApiIndex/shouquan&aid=' + app.globalData
							.aid + '&session_id=' + app.globalData.session_id + '&pid=' + app.globalData.pid + '&authlogin=' + authlogin + '&regbid=' + app.globalData.regbid + '&regsource=' + app.globalData.regsource +'&mdid=' + app.globalData.mdid +
							'&frompage=' + encodeURIComponent(frompage);
					}
					//#endif
				} else if (app.globalData.platform == 'app') {
					//#ifdef APP-PLUS
					plus.oauth.getServices(function(services) {
						console.log(services)
						let s = services[0]
						for (var i in services) {
							var service = services[i];
							if (service.id == 'weixin') {
								s = service;
							}
						}
						console.log(s)
						console.log('x----')
						s.authorize(function(e) {
							console.log(e);
							var code = e.code;
							app.post('ApiIndex/appwxlogin', {
								code: code,
								pid: app.globalData.pid,
                regbid:app.globalData.regbid,
                regsource:app.globalData.regsource,
                mdid:app.globalData.mdid
							}, function(res) {
								typeof callback == "function" && callback(res);
							});
						},function(err) {
								typeof callback == "function" && callback({
									status: 0,
									msg: JSON.stringify(err)
								});
						});
					},function(err){
						typeof callback == "function" && callback({
							status: 0,
							msg: JSON.stringify(err)
						});
					});
					//#endif
				} else if (app.globalData.platform == 'baidu') {
					//#ifdef MP-BAIDU
					uni.getLoginCode({
						success: res => {
							console.log('getLoginCode success', res);
							var code = res.code;
							app.post('ApiIndex/baidulogin', {
								code: code,
								pid: app.globalData.pid,
                regbid:app.globalData.regbid,
                regsource:app.globalData.regsource,
                mdid:app.globalData.mdid
							}, function(res) {
								typeof callback == "function" && callback(res);
							});
						},
						fail: err => {
							typeof callback == "function" && callback({
								status: 0,
								msg: err.errMsg
							});
						}
					});
					//#endif
				} else if (app.globalData.platform == 'qq') {
					//#ifdef MP-QQ
					qq.login({
						success(res) {
							if (res.code) {
								app.post('ApiIndex/qqlogin', {
									code: res.code,
									pid: app.globalData.pid,
                  regbid:app.globalData.regbid,
                  regsource:app.globalData.regsource,
                  mdid:app.globalData.mdid
								}, function(res) {
									typeof callback == "function" && callback(res);
								});
							} else {
								typeof callback == "function" && callback({
									status: 0,
									msg: res.errMsg
								});
							}
						},
						fail(res) {
							typeof callback == "function" && callback({
								status: 0,
								msg: res.errMsg
							});
						}
					})
					//#endif
				} else if (app.globalData.platform == 'toutiao') {
					//#ifdef MP-TOUTIAO
					tt.login({
						force: true,
						success(res) {
							if (res.code) {
								app.post('ApiIndex/toutiaologin', {
									code: res.code,
									pid: app.globalData.pid,
                  regbid:app.globalData.regbid,
                  regsource:app.globalData.regsource,
                  mdid:app.globalData.mdid
								}, function(res) {
									typeof callback == "function" && callback(res);
								});
							} else {
								typeof callback == "function" && callback({
									status: 0,
									msg: res.errMsg
								});
							}
						},
						fail(res) {
							typeof callback == "function" && callback({
								status: 0,
								msg: res.errMsg
							});
						}
					})
					//#endif
				} else if (app.globalData.platform == 'alipay') {
					//#ifdef MP-ALIPAY
					my.getAuthCode({
						scopes: 'auth_base',
						success: (res) => {
							console.log(res)
							if (res.authCode) {
								app.post('ApiIndex/alipaylogin', {
									code: res.authCode,
									pid: app.globalData.pid,
                  regbid:app.globalData.regbid,
                  regsource:app.globalData.regsource,
                  mdid:app.globalData.mdid
								}, function(res) {
									typeof callback == "function" && callback(res);
								});
							}
						},
						fail: (res) => {
							typeof callback == "function" && callback({
								status: 0,
								msg: res.errMsg
							});
						}
					});
					//#endif
				}
			},
			setinitdata: function(res) {
				var app = this;
				var oldmid = app.globalData.mid;
				if (res && res.data && (res.data.mid || res.data.mid === 0) && app.globalData.mid != res.data.mid) {
					app.globalData.mid = res.data.mid;
					if (res.data.session_id) {
						uni.setStorageSync('session_id', res.data.session_id);
						app.globalData.session_id = res.data.session_id;
					}
					if (app.globalData.mid) {
						app.globalData.socket_token = res.data.socket_token
						app.openSocket();
						uni.removeStorageSync('pid');
					}
				}
				if (res && res.data && res.data._initdata) {
					app.globalData.isinit = true;
					res.data._initdata.pre_url = app.globalData.pre_url;
					app.globalData.initdata = res.data._initdata;
					app.globalData.mid = res.data._initdata.mid;
					app.globalData.isdouyin = res.data._initdata.isdouyin;
					uni.setStorageSync('session_id', res.data._initdata.session_id);
					app.globalData.session_id = res.data._initdata.session_id;
					
					var pages = getCurrentPages(); //获取加载的页面
					var currentPage = pages[pages.length - 1]; //获取当前页面的对象
					var url = '/' + (currentPage.route ? currentPage.route : currentPage.__route__); //当前页面url 
					if(res.data._initdata.copyinfo && url != '/pagesB/maidan/pay' && !this.globalData.copyinfo){
						this.globalData.copyinfo = res.data._initdata.copyinfo;
						console.log(res.data._initdata.copyinfo)
						app.copy(res.data._initdata.copyinfo);
					}
					app.globalData.tel_foreign = res.data._initdata.tel_foreign || 0;
					if (app.globalData.platform == 'mp') {
						//#ifdef H5
						var share_package = res.data.share_package;
						var jweixin = require('jweixin-module');
						jweixin.config({
							debug: false,
							appId: share_package.appId,
							timestamp: share_package.timestamp,
							nonceStr: share_package.nonceStr,
							signature: share_package.signature,
							jsApiList: [
								'checkJsApi',
								'onMenuShareAppMessage',
								'onMenuShareTimeline',
								'updateAppMessageShareData',
								'updateTimelineShareData',
								'chooseImage',
								'previewImage',
								'uploadImage',
								'openLocation',
								'getLocation',
								'closeWindow',
								'scanQRCode',
								'chooseWXPay',
								'addCard',
								'chooseCard',
								'openCard'
							],
							'openTagList': ['wx-open-launch-weapp']
						});
						//#endif
					}
					if (app.globalData.mid) {
						app.globalData.socket_token = res.data.socket_token
						app.openSocket();
						uni.removeStorageSync('pid');
					}
					if(!app.globalData.mid && (app.globalData.initdata.logintype).length == 0){
						app.baselogin(function(res2){
								var pages = getCurrentPages(); //获取加载的页面
								var currentPage = pages[pages.length - 1]; //获取当前页面的对象
								var url = '/' + (currentPage.route ? currentPage.route : currentPage.__route__); //当前页面url 
								if(url == '/pages/index/login'){
									if(app.globalData.platform == 'baidu'){
										var opts = currentPage.options;
									}else{
										var opts = currentPage.$vm.opt;
									}
									console.log(opts)
									if(opts && opts.frompage){
										app.goto(decodeURIComponent(opts.frompage), 'redirect');
									}else{
										app.goto('/pages/my/usercenter', 'redirect');
									}
								}else{
									currentPage.$vm.getdata();
								}
						});
					}
				}else{
					app.globalData.isinit = false;
				}
				if (app.globalData.mid && app.globalData.mid != oldmid) {
					if (app.globalData.platform == 'wx') {
						//#ifdef MP-WEIXIN
						wx.login({
							success: function(res) {
								if (res.code) {
									app.post('ApiIndex/setwxopenid', {
										code: res.code
									}, function() {})
								}
							}
						});
						//#endif
					} else if (app.globalData.platform == 'alipay') {
						//#ifdef MP-ALIPAY
						my.getAuthCode({
							scopes: ['auth_base'],
							success: (res) => {
								if (res.authCode) {
									app.post('ApiIndex/setalipayopenid', {
										code: res.authCode
									}, function() {})
								}
							},
						});
						//#endif
					} else if (app.globalData.platform == 'baidu') {
						//#ifdef MP-BAIDU
						uni.getLoginCode({
							success: (res) => {
								if (res.code) {
									app.post('ApiIndex/setbaiduopenid', {
										code: res.code
									}, function() {})
								}
							},
							fail: err => {
								console.log('getLoginCode fail', err);
							}
						});
						//#endif
					} else if (app.globalData.platform == 'toutiao') {
						//#ifdef MP-TOUTIAO
						tt.login({
							force: true,
							success(res) {
								app.post('ApiIndex/settoutiaoopenid', {
									code: res.code
								}, function(res) {});
							}
						});
						//#endif
					}
				}

			},
			//启用socket连接
			openSocket: function() {
				console.log('openSocket');
				//#ifndef MP-TOUTIAO
				var app = this;
				// 如果已经连接，先不要重复连接
				if (app.globalData.socketOpen) {
				  console.log('WebSocket已连接，不需要重新连接');
				  return;
				}
				app.globalData.socketOpen = false;
				uni.closeSocket();
				uni.connectSocket({
					url: (app.globalData.pre_url).replace('https://', "wss://") + '/wss'
				});
				app.sendSocketMessage({
					type: 'khinit',
					data: {
						aid: app.globalData.aid,
						mid: app.globalData.mid,
						platform: app.globalData.platform
					}
				});
				clearInterval(app.globalData.socketInterval);
				app.globalData.socketInterval = setInterval(function() {
					app.sendSocketMessage({
						type: 'connect'
					});
				}, 25000);
				//#endif
			},
			sendSocketMessage: function(msg) {
				var app = this;
				if (!msg.token) msg.token = this.globalData.socket_token;
				if (app.globalData.socketOpen) {
					console.log(msg);
					uni.sendSocketMessage({
						data: JSON.stringify(msg),
						fail: function(res) {
							console.log(res)
							console.log('发送失败');
							if (app.globalData.socketConnecttimes < 1) {
								app.globalData.socketConnecttimes++;
								app.globalData.socketMsgQueue.push(msg);
								console.log('openSocket 重连');
								app.openSocket();
							}
						},
						success: function() {
							console.log('发送成功');
							app.globalData.socketConnecttimes = 0;
						}
					});
				} else {
					this.globalData.socketMsgQueue.push(msg);
				}
			},
			// 拍照上传（由 scan:: ActionSheet 触发）
			_scanTakePhotoAndUpload: function() {
				var app = this;
				var uploadUrl = app.globalData.baseurl + 'ApiImageupload/uploadImg/aid/' + app.globalData.aid + '/platform/' + app.globalData.platform + '/session_id/' + app.globalData.session_id;

				// 上传图片的公共方法
				function doUpload(filePath) {
					app.showLoading('上传中');
					uni.uploadFile({
						url: uploadUrl,
						filePath: filePath,
						name: 'file',
						success: function(res) {
							app.showLoading(false);
							try {
								var data = typeof res.data == 'string' ? JSON.parse(res.data) : res.data;
								if (data.status == 1) {
									uni.showToast({ title: '上传成功', icon: 'success' });
									console.log('[scan] 拍照上传成功，图片URL:', data.url);
									// 预览上传成功的图片
									if (data.url) {
										uni.previewImage({ current: data.url, urls: [data.url] });
									}
								} else {
									app.alert(data.msg || '上传失败');
								}
							} catch(e) {
								app.alert('上传失败，请重试');
							}
						},
						fail: function(res) {
							app.showLoading(false);
							app.alert('上传失败，请重试');
						}
					});
				}

				// #ifdef MP-WEIXIN
				// 微信小程序：检查相机权限后调用 wx.chooseMedia
				wx.getSetting({
					success: function(settingRes) {
						var authCamera = settingRes.authSetting['scope.camera'];
						if (authCamera === true) {
							// 已授权，直接拍照
							wx.chooseMedia({
								count: 1,
								mediaType: ['image'],
								sourceType: ['camera'],
								camera: 'back',
								success: function(r) {
									doUpload(r.tempFiles[0].tempFilePath);
								},
								fail: function(err) {
									if (err.errMsg && err.errMsg.indexOf('cancel') !== -1) return;
									console.log('[scan] chooseMedia fail:', err);
								}
							});
						} else if (authCamera === undefined) {
							// 未请求过，发起授权
							wx.authorize({
								scope: 'scope.camera',
								success: function() {
									wx.chooseMedia({
										count: 1,
										mediaType: ['image'],
										sourceType: ['camera'],
										camera: 'back',
										success: function(r) {
											doUpload(r.tempFiles[0].tempFilePath);
										},
										fail: function(err) {
											if (err.errMsg && err.errMsg.indexOf('cancel') !== -1) return;
											console.log('[scan] chooseMedia fail after authorize:', err);
										}
									});
								},
								fail: function() {
									// 用户拒绝授权，引导前往设置页
									wx.showModal({
										title: '权限提示',
										content: '需要相机权限才能拍照，请前往设置页开启',
										showCancel: true,
										confirmText: '去设置',
										success: function(modalRes) {
											if (modalRes.confirm) {
												wx.openSetting();
											}
										}
									});
								}
							});
						} else {
							// 之前已拒绝，引导前往设置页
							wx.showModal({
								title: '权限提示',
								content: '需要相机权限才能拍照，请前往设置页开启',
								showCancel: true,
								confirmText: '去设置',
								success: function(modalRes) {
									if (modalRes.confirm) {
										wx.openSetting();
									}
								}
							});
						}
					}
				});
				return;
				// #endif

				// #ifdef MP-QQ
				// QQ小程序
				uni.chooseImage({
					count: 1,
					sizeType: ['compressed'],
					sourceType: ['camera'],
					success: function(res) {
						doUpload(res.tempFilePaths[0]);
					},
					fail: function(err) {
						if (err.errMsg && err.errMsg.indexOf('cancel') !== -1) return;
					}
				});
				return;
				// #endif

				// #ifdef APP-PLUS
				// APP端
				uni.chooseImage({
					count: 1,
					sizeType: ['compressed'],
					sourceType: ['camera'],
					success: function(res) {
						doUpload(res.tempFilePaths[0]);
					},
					fail: function(err) {
						if (err.errMsg && err.errMsg.indexOf('cancel') !== -1) return;
					}
				});
				return;
				// #endif

				// 其他小程序平台
				uni.chooseImage({
					count: 1,
					sizeType: ['compressed'],
					sourceType: ['camera'],
					success: function(res) {
						doUpload(res.tempFilePaths[0]);
					},
					fail: function(err) {
						if (err.errMsg && err.errMsg.indexOf('cancel') !== -1) return;
					}
				});
			},
			chooseImage: async function(callback, count,otherParam=0) {
				var app = this;
				// #ifdef APP-PLUS
				uni.showActionSheet({
					itemList: ['拍摄', '从相册选择'],
					success: async function (res) {
						if(res.tapIndex){ //相册
							let result2 =  await app.$store.dispatch("requestPermissions",'WRITE_EXTERNAL_STORAGE');
							if (result2 !== 1) return;
							uni.chooseImage({
								count: count || 1,
								sizeType: ['original', 'compressed'],
								sourceType: ['album'],
								success: function(res) {
									var tempFilePaths = res.tempFilePaths,
										imageUrls = [];
									var uploadednum = 0;
									for (var i = 0; i < tempFilePaths.length; i++) {
										imageUrls.push('');
										app.showLoading('上传中');
										uni.uploadFile({
											url: app.globalData.baseurl + 'ApiImageupload/uploadImg/aid/' + app
												.globalData.aid + '/platform/' + app.globalData.platform +
												'/session_id/' +
												app.globalData.session_id+'/sortnum/'+i+'/other_param/'+otherParam,
											filePath: tempFilePaths[i],
											name: 'file',
											success: function(res) {
												app.showLoading(false);
												if(typeof res.data == 'string'){
													//兼容微信小程序
													var data = JSON.parse(res.data);
												}else{
													//兼容百度小程序
													var data = res.data;
												}
												if (data.status == 1) {
													uploadednum++;
													imageUrls[parseInt(data.sortnum)] = data.url;
													if (uploadednum == tempFilePaths.length) {
														typeof callback == 'function' && callback(imageUrls);
													}
												} else {
													app.alert(data.msg);
												}
											},
											fail: function(res) {
												app.showLoading(false);
												app.alert(res.errMsg);
											}
										});
									}
								},
								fail: function(res) {}
							});
						}else{ //拍摄
							let result = await app.$store.dispatch("requestPermissions",'CAMERA');
							if (result !== 1) return;
							uni.chooseImage({
								count: count || 1,
								sizeType: ['original', 'compressed'],
								sourceType: ['camera'],
								success: function(res) {
									var tempFilePaths = res.tempFilePaths,
										imageUrls = [];
									var uploadednum = 0;
									for (var i = 0; i < tempFilePaths.length; i++) {
										imageUrls.push('');
										app.showLoading('上传中');
										uni.uploadFile({
											url: app.globalData.baseurl + 'ApiImageupload/uploadImg/aid/' + app
												.globalData.aid + '/platform/' + app.globalData.platform +
												'/session_id/' +
												app.globalData.session_id+'/sortnum/'+i+'/other_param/'+otherParam,
											filePath: tempFilePaths[i],
											name: 'file',
											success: function(res) {
												app.showLoading(false);
												if(typeof res.data == 'string'){
													//兼容微信小程序
													var data = JSON.parse(res.data);
												}else{
													//兼容百度小程序
													var data = res.data;
												}
												if (data.status == 1) {
													uploadednum++;
													imageUrls[parseInt(data.sortnum)] = data.url;
													if (uploadednum == tempFilePaths.length) {
														typeof callback == 'function' && callback(imageUrls);
													}
												} else {
													app.alert(data.msg);
												}
											},
											fail: function(res) {
												app.showLoading(false);
												app.alert(res.errMsg);
											}
										});
									}
								},
								fail: function(res) {}
							});
						}
					},
					fail: function (res) {
						console.log(res.errMsg);
					}
				});
				// #endif
				// #ifndef APP-PLUS
				uni.chooseImage({
					count: count || 1,
					sizeType: ['original', 'compressed'],
					sourceType: ['album', 'camera'],
					success: function(res) {
						var tempFilePaths = res.tempFilePaths,
							imageUrls = [];
						var uploadednum = 0;
						for (var i = 0; i < tempFilePaths.length; i++) {
							imageUrls.push('');
							app.showLoading('上传中');
							uni.uploadFile({
								url: app.globalData.baseurl + 'ApiImageupload/uploadImg/aid/' + app
									.globalData.aid + '/platform/' + app.globalData.platform +
									'/session_id/' +
									app.globalData.session_id+'/sortnum/'+i+'/other_param/'+otherParam,
								filePath: tempFilePaths[i],
								name: 'file',
								success: function(res) {
									app.showLoading(false);
									if(typeof res.data == 'string'){
										//兼容微信小程序
										var data = JSON.parse(res.data);
									}else{
										//兼容百度小程序
										var data = res.data;
									}
									if (data.status == 1) {
										uploadednum++;
										imageUrls[parseInt(data.sortnum)] = data.url;

										if (uploadednum == tempFilePaths.length) {
											//console.log(imageUrls);
											typeof callback == 'function' && callback(imageUrls);
										}
									} else {
										app.alert(data.msg);
									}
								},
								fail: function(res) {
									app.showLoading(false);
									app.alert(res.errMsg);
								}
							});
						}
					},
					fail: function(res) { //alert(res.errMsg);
					}
				});
				// #endif
			},
			chooseFile: async function(callback, count) {
				var app = this;
				var up_url = app.globalData.baseurl + 'ApiImageupload/uploadImg/aid/' + app.globalData.aid + '/platform/' + app.globalData.platform +'/session_id/' +app.globalData.session_id;
				// #ifdef H5
				uni.chooseFile({
				    count: 1, //默认100
				    success: function (res) {
				        console.log(res);
				        const tempFilePaths = res.tempFiles;
				        
				        //for (var i = 0; i < tempFilePaths.length; i++) {
				        	app.showLoading('上传中');
				            console.log(tempFilePaths[0]);
				        	uni.uploadFile({
				        		url: up_url,
				        		filePath: tempFilePaths[0]['path'],
				        		name: 'file',
				        		success: function(res) {
				        			app.showLoading(false);
				        			var data = JSON.parse(res.data);
				        			if (data.status == 1) {
												typeof callback == 'function' && callback(data.url);
				        			} else {
				        				app.alert(data.msg);
				        			}
				        		},
				        		fail: function(res) {
				        			app.showLoading(false);
				        			app.alert(res.errMsg);
				        		}
				        	});
				        //}
				    }
				});
				// #endif
				// #ifdef MP-WEIXIN
				wx.chooseMessageFile({
				  count: 1,
				  type: 'file',
				  success (res) {
				    const tempFilePaths = res.tempFiles
				    console.log(tempFilePaths);
				    	app.showLoading('上传中');
				    	uni.uploadFile({
				    		url: up_url,
				    		filePath: tempFilePaths[0]['path'],
				    		name: 'file',
				    		success: function(res) {
				    			app.showLoading(false);
				    			var data = JSON.parse(res.data);
				    			if (data.status == 1) {
											typeof callback == 'function' && callback(data.url);
				    			} else {
				    				app.alert(data.msg);
				    			}
				    		},
				    		fail: function(res) {
				    			app.showLoading(false);
				    			app.alert(res.errMsg);
				    		}
				    	});
				    //}
				  },
				  complete(res){
				      console.log(res)
				  }
				})
				// #endif
			},
			// distance 距离为m
			getMapZoom:function(distance){
				let zoom = 10;
				distance = Number(distance)
				if (0 <= distance && distance <= 25) {
					zoom = 16
					return zoom;
				} else if (25 < distance && distance <= 50) {
					zoom = 16
					return zoom;
				} else if (50 < distance && distance <= 100) {
					zoom = 16
					return zoom;
				} else if (100 < distance && distance <= 200) {
					zoom = 15
					return zoom;
				} else if (200 < distance && distance <= 500) {
					zoom = 15
					return zoom;
				} else if (500 < distance && distance <= 1000) {
					zoom = 15
					return zoom;
				} else if (1000 < distance && distance <= 2000) {
					zoom = 13
					return zoom;
				} else if (2000 < distance && distance <= 5000) {
					zoom = 13
					return zoom;
				} else if (5000 < distance && distance <= 10000) {
					zoom = 11
					return zoom;
				} else if (10000 < distance && distance <= 20000) {
					zoom = 10
					return zoom;
				} else if (20000 < distance && distance <= 30000) {
					zoom = 9
					return zoom;
				} else if (30000 < distance && distance <= 50000) {
					zoom = 9
					return zoom;
				} else if (50000 < distance && distance <= 100000) {
					zoom = 8
					return zoom;
				} else if (100000 < distance && distance <= 200000) {
					zoom = 7
					return zoom;
				} else if (200000 < distance && distance <= 400000) {
					zoom = 6
					return zoom;
				} else if (400000 < distance && distance <= 1000000) {
					zoom = 5
					return zoom;
				}
				return 10;
			},
			getLocation: async function(callback1, callback2) {
				var that = this;
				if (this.globalData.platform == 'mp') {
					//#ifdef H5
					var jweixin = require('jweixin-module');
					jweixin.ready(function() {
						jweixin.getLocation({
							type: 'gcj02',
							success: function(res) {
								that.setCache('getLocationCatch', res);
								typeof callback1 == 'function' && callback1(res);
							},
							fail: function(res) {
								var locationCatch = that.getCache('getLocationCatch');
								if (locationCatch) {
									typeof callback1 == 'function' && callback1(locationCatch);
								} else {
									typeof callback2 == 'function' && callback2(res);
								}
							}
						});
					});
					//#endif
				} else if (this.globalData.platform == 'alipay') {
					//#ifdef MP-ALIPAY
					uni.getLocation({
						success: function(res) {
							that.setCache('getLocationCatch', res);
							typeof callback1 == 'function' && callback1(res);
						},
						fail: function(res) {
							var locationCatch = that.getCache('getLocationCatch');
							if (locationCatch) {
								typeof callback1 == 'function' && callback1(locationCatch);
							} else {
								typeof callback2 == 'function' && callback2(res);
							}
							if(res.extErrorCode == 2001 || res.extErrorCode == 2002 || res.extErrorCode == 2003){}else{
								uni.showModal({
									title: '提示',
									content: '请在系统设置中打开定位服务',
									success: function (res) {
										if (res.confirm) {
										} else if (res.cancel) {
										}
									}
								});
							}
						}
					});
					//#endif
				} else {
					// #ifdef APP-PLUS
						let result = await this.$store.dispatch("requestPermissions",'ACCESS_FINE_LOCATION')
						if (result !== 1) return typeof callback2 == 'function' && callback2('用户拒绝授权');
					// #endif
					uni.getLocation({
						type: 'gcj02',
						success: function(res) {
							that.setCache('getLocationCatch', res);
							typeof callback1 == 'function' && callback1(res);
						},
						fail: function(res) {
							var locationCatch = that.getCache('getLocationCatch');
							if (locationCatch) {
								typeof callback1 == 'function' && callback1(locationCatch);
							} else {
								typeof callback2 == 'function' && callback2(res);
							}
							let showModalTitle = ''
							if(res.errMsg == 'getLocation:fail auth deny' || res.errMsg =='getLocation:fail:auth denied' || res.errMsg == 'getLocation:fail authorize no response'){
								// 用户取消定位
							}else	if(res.errMsg == 'getLocation:fail system permission denied' || res.errMsg == 'getLocation:fail:system permission denied' || res.errMsg == 'getLocation:fail:ERROR_NOCELL&WIFI_LOCATIONSWITCHOFF'){
								showModalTitle='请在系统设置中打开定位服务'
							}else	if(res.errMsg == 'getLocation:fail:ERROR_NETWORK'){
								showModalTitle = '获取位置信息失败，网络异常'
							}else	if(res.errMsg == 'getLocation:fail:timeout'){
								showModalTitle = '获取位置信息失败，定位超时'
							}else{
								showModalTitle = '获取位置信息失败'+res.errMsg
							}
							if(showModalTitle){
								// #ifndef APP || H5
								if(showModalTitle == '请在系统设置中打开定位服务'){
									uni.showModal({
										title: '提示',
										content: showModalTitle,
										confirmText:'去设置',
										success: function (res) {
											if (res.confirm) {
												uni.openSetting({
													success(res) {
														console.log(res.authSetting)
													}
												});
												showModalTitle ='';
											} else if (res.cancel) {
												showModalTitle ='';
											}
										}
									});
									return;
								}
								// #endif
								uni.showModal({
									title: '提示',
									content: showModalTitle,
									success: function (res) {
										if (res.confirm) {
											showModalTitle ='';
										} else if (res.cancel) {
											showModalTitle ='';
										}
									}
								});
							}
						}
					});
				}
			},
			getLocationCache:function(key=''){
				var app = this;
				var locationCache = app.getCache('locationCache');
				if(locationCache){
					if(key){
						return locationCache[key]
					}else{
						return locationCache
					}
				}else{
					if(key){
						return  '';
					}else{
						return {}
					}
				}
			},
			setLocationCache:function(key='',val='',expireTime=0){
				var app = this;
				var locationCache = app.getCache('locationCache');
				if(!locationCache){
					locationCache = {}
					locationCache[key] = val
				}else{
					locationCache[key] = val
				}
				app.setCache('locationCache',locationCache,expireTime)
			},
			setLocationCacheData:function(data,expireTime=0){
				var app = this;
				app.setCache('locationCache',data,expireTime)
			},
			//expire_time过期分钟数
			setCache: function(key, value, expire_time) {
				//如果设置了过期时间，则读取的时候 如果过期，就清除expire_time 过期分钟数
				if(!expire_time){
					expire_time = 0;
				}
				var expire_time = parseInt(expire_time);
				if(expire_time>0){
					var curtime = new Date().getTime()
					uni.setStorageSync(key+'_expiretime_second', expire_time*60*1000);
					uni.setStorageSync(key+'_expiretime', curtime + (expire_time*60*1000));
				}
				return uni.setStorageSync(key, value);
			},
			getCache: function(key) {
				var app = this;
				var cacheVal = uni.getStorageSync(key);
				if(cacheVal){
					//是不是设置了过期时间：没设置直接返回，设置了判断是否过期
					var expirekey = key+'_expiretime';
					var expiretime = uni.getStorageSync(expirekey);
					if(expiretime){
						expiretime = parseInt(expiretime)
						//当前时间戳
						var curtime = new Date().getTime()
						// console.log('ct:'+curtime)
						// console.log('et:'+expiretime)
						//判断是不是过期
						if(expiretime<curtime){
							//过期了 清掉
							// console.log('过期')
							app.removeCache(key)
							app.removeCache(expirekey)
							return '';
						}else{
							// console.log('未过期')
							var cacheSecond = parseInt(uni.getStorageSync(key+'_expiretime_second'));
							app.setCache(expirekey,curtime+cacheSecond);
							return uni.getStorageSync(key);
						}
					}else{
						return cacheVal;
					}
				}
				return cacheVal;
			},
			removeCache: function(key) {
				var $ = this;
				if ($.isNull(key)) {
					uni.clearStorageSync();
				} else {
					uni.removeStorageSync(key);
					uni.removeStorageSync(key+'_expiretime');
				}
			},
			_url: function() {
				//获取当前页url
				var pages = getCurrentPages(); //获取加载的页面
				var currentPage = pages[pages.length - 1]; //获取当前页面的对象
				var url = '/' + (currentPage.route ? currentPage.route : currentPage.__route__); //当前页面url 
				return url;
			},
			_fullurl: function() {
				var pages = getCurrentPages(); //获取加载的页面
				var currentPage = pages[pages.length - 1]; //获取当前页面的对象
				if(currentPage.__page__ && currentPage.__page__.fullPath) return currentPage.__page__.fullPath;
				if(currentPage.$page && currentPage.$page.fullPath) return currentPage.$page.fullPath;
				var url = '/' + (currentPage.route ? currentPage.route : currentPage.__route__); //当前页面url 
				if (this.globalData.platform == 'baidu') {
					var opts = currentPage.options;
				} else {
					var opts = currentPage.$vm.opt;
				}
				console.log(opts)
				var params = [];
				for (var i in opts) {
					params.push(i + '=' + opts[i]);
				}
				if (params.length > 0) {
					url += '?' + params.join('&');
				}
				console.log(url)
				return url;
			},
			checkUpdateVersion() {
				if (wx.canIUse('getUpdateManager')) {
					//创建 UpdateManager 实例
					const updateManager = wx.getUpdateManager();
					//console.log('是否进入模拟更新');
					//检测版本更新
					updateManager.onCheckForUpdate(function(res) {
						//console.log('是否获取版本');
						// 请求完新版本信息的回调
						if (res.hasUpdate) {
							//监听小程序有版本更新事件
							updateManager.onUpdateReady(function() {
								//TODO 新的版本已经下载好，调用 applyUpdate 应用新版本并重启 （ 此处进行了自动更新操作）
								updateManager.applyUpdate();
							})
							updateManager.onUpdateFailed(function() {
								// 新版本下载失败
								//wx.showModal({
								//	title: '已经有新版本喽~',
								//	content: '请您删除当前小程序，到微信 “发现-小程序” 页，重新搜索打开哦~',
								//})
							})
						}
					})
				} else {
					//TODO 此时微信版本太低（一般而言版本都是支持的）
					//wx.showModal({
					//  title: '溫馨提示',
					//  content: '当前微信版本过低，无法使用该功能，请升级到最新微信版本后重试。'
					//})
				}
			},
      copy:function(data){
        if(data){
          uni.setClipboardData({
          	data: data,
            showToast:false
          });
        }
      },
			// 防抖
			Debounce:function(fn,t){
				const delay = t || 500
				let timer
				return function() {
					const args = arguments
					if (timer) {
						clearTimeout(timer)
					}
					timer = setTimeout(() => {
						timer = null
						fn.apply(this, args)
					}, delay)
				}
			},
			// 节流
			Throttle:function(fn, t){
				let last
				let timer
				const interval = t || 500
				return function() {
					const args = arguments
					const now = +new Date()
					if (last && now - last < interval) {
						clearTimeout(timer)
						timer = setTimeout(() => {
							last = now
							fn.apply(this, args)
						}, interval)
					} else {
						last = now
						fn.apply(this, args)
					}
				}
			}
		}
	};
</script>
<style>
	@import "./common.css";
	@import "./iconfont.css";
	@import '@/components/parse/parse.css';
</style>
