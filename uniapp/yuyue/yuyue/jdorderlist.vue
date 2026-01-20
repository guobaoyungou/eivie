<template>
<view class="container">
	<block v-if="isload">
		<view>
			<view class="search-container">
				<view class="search-box">
					<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
					<input class="search-text" placeholder="搜索商家" placeholder-style="color:#aaa;font-size:24rpx" @confirm="searchConfirm"/>
				</view>
			</view>
			<block v-for="(item, index) in datalist" :key="item.id">
			<view class="order-box" @tap="goto" :data-url="'jdorderdetail?id=' + item.id">
				<view class="head">
					<view class="fwtype1" v-if="item.fwtype != 2">到店</view><view v-else class="fwtype2">上门</view>
					<view v-if="item.fwtype==1 || item.fwtype==3">
						<view class="f1" v-if="item.status==3"><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/>已完成</view>
						<view class="f1" v-if="item.status==1"><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/><text class="t1">等待客户上门</text> </view>
						<view class="f1" v-if="item.status==2 && !item.endtime"><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/><text class="t1" style="margin-left:10rpx">服务中</text></view>
						<view class="f1" v-if="item.status==2 && item.endtime"><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/><text class="t1" style="margin-left:10rpx">待确认</text></view>
						<view class="f1" v-if="item.status==10"><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/><text class="t1" style="margin-left:10rpx">已取消</text></view>
					</view>
					<view v-else-if="item.fwtype==2">
						<view class="f1" v-if="item.status==3"><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/>已完成</view>
						<view class="f1" v-else-if="item.status==1"><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/>期望上门时间<text class="t1">{{item.orderinfo.yydate}}</text> </view>
						<block v-else-if="item.status==2 && !item.endtime">
								<view class="f1" v-if="showaddmoney">
									<block v-if="!item.sign_status">
										<image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/>已到达，等待服务
									</block>
									<block v-else="!item.sign_status">
										<image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/>已到达，正在服务
									</block>
								</view>
								<view class="f1" v-else>	<image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/>已到达，服务中</view>
						</block>
						<block v-else-if="item.status==2 && item.endtime">
							<view class="f1">	<image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/>待确认</view>
						</block>
						<view class="f1" v-if="item.status==1"><image :src="pre_url+'/static/img/peisong/ps_time.png'" class="img"/>已取消</view>
					</view>
					<view class="flex1"></view>
					<view class="f2">
						<view class="t1" v-if="item.showprice">
							<text>￥{{item.order_totalprice}}</text>
							<text class="t11" v-if="item.ticheng>0">(￥{{item.ticheng}})</text>
						</view>
						<block v-else><text class="t1">{{item.ticheng}}</text>元</block>
					</view>
				</view>
				<view class="content">
          <block v-if="!item.orderinfo.protype">
            <view class="f1" v-if="item.fwtype == 2">
              <view class="t1"><text class="x1">{{item.juli}}</text><text class="x2">{{item.juli_unit}}</text></view>
              <view class="t2"><image :src="pre_url+'/static/img/peisong/ps_juli.png'" class="img"/></view>
              <view class="t3"><text class="x1">{{item.juli2}}</text><text class="x2">{{item.juli2_unit}}</text></view>
            </view>
            <view class="f2">
              <view class="t1">{{item.binfo.name}}</view>
              <view class="t2">{{item.binfo.address}}</view>
              <view class="t3">{{item.orderinfo.address}}</view>
              <view class="t2">{{item.orderinfo.area}}</view>
            </view>
            <view class="f3"  @tap.stop="daohang" :data-index="index" data-protype="0" :data-fwtype="item.fwtype"><image :src="pre_url+'/static/img/peisong/ps_daohang.png'" class="img"/></view>
          </block>
          <block v-else>
            <view class="f1" style="margin-top: 38rpx;">
              <view class="t3"><text class="x1">{{item.juli2}}</text><text class="x2">{{item.juli2_unit}}</text></view>
            </view>
            <view class="f2">
              <view class="t3">{{item.orderinfo.address}}</view>
              <view class="t2">{{item.orderinfo.area}}</view>
            </view>
            <view class="f3" @tap.stop="daohang" :data-index="index" data-protype="1"><image :src="pre_url+'/static/img/peisong/ps_daohang.png'" class="img"/></view>
          </block>
				</view>
				<view v-if="item.fwtype==1 || item.fwtype==3" class="op">
					<view class="t3" v-if="item.order_status==0">用户待支付</view>
					<block v-if="item.order_status!=0">
						<view class="t1" v-if="item.status==1">已接单，待顾客上门</view>
						<view class="t1" v-if="item.status==2 && !item.endtime">顾客已到达</view>
						<view class="t1" v-if="item.status==2 && item.endtime">待确认</view>
						<view class="t1" v-if="item.status==3">已完成</view>
					</block>
					<view class="flex1"></view>
					<view class="btn3" v-if="item.order_status==0" @tap.stop="changeprice" :data-id="item.id" :data-price="item.order_totalprice">
							改 价
					</view>
					<block v-if="item.order_status!=0">
						<view class="btn1" @tap.stop="setst" :data-id="item.id" data-st="2" v-if="item.status==1">顾客已到达</view>
						<view class="btn1" @tap.stop="setst" :data-id="item.id" data-st="3" v-if="item.status==2 && !item.endtime">我已完成</view>
					</block>
				</view>
				<view v-else-if="item.fwtype==2" class="op">
						<view class="t3" v-if="item.order_status==0">用户待支付</view>
						<block v-if="item.order_status!=0">
							<view class="t1" v-if="item.status==1">已接单，等待上门</view>
							<view class="t1" v-if="item.status==2 && !item.endtime">已到达，共用时{{item.useminute}}分钟</view>
							<view class="t1" v-if="item.status==2 && item.endtime">待确认</view>
							<view class="t1" v-if="item.status==3">已完成</view>
						</block>
						<view class="flex1"></view>
						<!-- 订单未支付 -->
						<view class="btn3" v-if="item.order_status==0" @tap.stop="changeprice" :data-id="item.id" :data-price="item.order_totalprice">
								改 价
						</view>
						<block v-if="item.order_status!=0">
							<block v-if="showaddmoney">
									<view class="btn1 btn2" @tap.stop="addmoney" v-if="item.sign_status==1 && item.status==2 && item.addprice<=0" :data-id="item.id">补差价</view>							<view class="btn1" @tap.stop="setst" :data-id="item.id" data-st="2" v-if="item.status==1">出发</view>
									<view class="btn1 btn2" @tap.stop="showpaycode" v-if="item.addprice>0" :data-id="item.id" :data-key="index">查看补余款</view>
									<view class="btn1" @tap.stop="setst" :data-id="item.id" data-st="5" v-if="!item.sign_status && item.status==2">开始服务</view>
									<view class="btn1" @tap.stop="setst" :data-id="item.id" data-st="3" v-if="item.sign_status==1 && item.status==2">服务完成</view>
							</block>
							<block v-else>
								<block v-if="item.status==1">
									<view class="btn1" @tap.stop="setst" :data-id="item.id" data-st="10" v-if="yuyuecar && item.protype && cancancel" style="margin-right: 20rpx;background: #fff;color: #222;border: 2rpx solid #222;">取消</view>
									<block v-if="yuyuecar && item.protype && needstartpic">
										<view class="btn1" @tap.stop="goto" :data-url="'/pagesA/yuyuecar/uppic?id='+item.id+'&st=2'">我已到达</view>
									</block>
									<block v-else>
										<view class="btn1" @tap.stop="setst" :data-id="item.id" data-st="2" v-if="item.status==1">我已到达</view>
									</block>
								</block>
								
								<block v-if="item.status==2 && !item.endtime">
									<view class="btn1 btn2" @tap.stop="goto" :data-url="'servicepics?id='+item.id" v-if="showservicepics">服务图片</view>		
									<block v-if="yuyuecar && item.protype && needendpic">
										<view class="btn1" @tap.stop="goto" :data-url="'/pagesA/yuyuecar/uppic?id='+item.id+'&st=3'">我已完成</view>
									</block>
									<block v-else>
										<view class="btn1" @tap.stop="setst" :data-id="item.id" data-st="3">我已完成</view>
									</block>
								</block>
							</block>
					</block>
				</view>
			</view>
			</block>
		
			<nomore v-if="nomore"></nomore>
			<nodata v-if="nodata"></nodata>
		</view>
		<!-- <view style="width:100%;height:120rpx"></view> -->
		<!-- <view class="bottom">
			<view class="my">
				<image src="/static/img/my.png" class="img"/>
				<text>我的</text>
			</view>
			<view class="btn1" @tap="setpsst" data-st="1" v-if="psuser.status==0">暂停接单中</view>
			<view class="btn2" :style="{background:t('color1')}" @tap="setpsst" data-st="0" v-if="psuser.status==1">开启接单中</view>
		</view> -->
		
		<view class="tabbar" v-if="showtabbar">
			<view class="tabbar-bot"></view>
			<view class="tabbar-bar" style="background-color:#ffffff">
				<view @tap="goto" data-url="dating" data-opentype="reLaunch" class="tabbar-item">
					<view class="tabbar-image-box">
						<image class="tabbar-icon" :src="pre_url+'/static/img/peisong/home.png'"></image>
					</view>
					<view class="tabbar-text">大厅</view>
				</view>
				<view @tap="goto" data-url="jdorderlist" data-opentype="reLaunch" class="tabbar-item">
					<view class="tabbar-image-box">
						<image class="tabbar-icon" :src="pre_url+'/static/img/peisong/order'+(st!=3?'2':'')+'.png'"></image>
					</view>
					<view class="tabbar-text" :class="st!=3?'active':''">订单</view>
				</view>
				<view @tap="goto" data-url="jdorderlist?st=3" data-opentype="reLaunch" class="tabbar-item">
					<view class="tabbar-image-box">
						<image class="tabbar-icon" :src="pre_url+'/static/img/peisong/orderwc'+(st==3?'2':'')+'.png'"></image>
					</view>
					<view class="tabbar-text" :class="st==3?'active':''">已完成</view>
				</view>
				<view v-if="showform" @tap="goto" data-url="formlog" data-opentype="reLaunch" class="tabbar-item">
					<view class="tabbar-image-box">
						<image class="tabbar-icon" :src="pre_url+'/static/img/peisong/dangan.png'"></image>
					</view>
					<view class="tabbar-text">档案</view>
				</view>
				<view @tap="goto" data-url="my" data-opentype="reLaunch" class="tabbar-item">
					<view class="tabbar-image-box">
						<image class="tabbar-icon" :src="pre_url+'/static/img/peisong/my.png'"></image>
					</view>
					<view class="tabbar-text">我的</view>
				</view>
			</view>
		</view>
		
		<view class="modal" v-if="showmodal">
			<view class="addmoney">
					<view class="title">{{addprice>0?'修改':'创建'}}补余款</view>
					<view class="item">
						<label class="label">金额：</label><input type="text" @input="bindMoney" name="blance_price" :value="addprice" placeholder="请输入补余款金额"  placeholder-style="font-size:24rpx"/>元
					</view>
					<view class="btn"><button class="btn-cancel" @tap="cancel">取消</button><button class="confirm"  :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'"  @tap.stop="addconfirm">确定</button></view>
			</view>
		</view>	
		
		<view class="modal" v-if="showpaycodes">
			<view class="addmoney">
					<view class="title">查看补余款</view>
					<view class="item" >
						<label>金额：</label><text class="price">{{addprice}}</text>元
					</view>
					<view class="item" style="padding-top: 0;">
						<label>支付状态：</label> <text class="t2" v-if="addmoneystatus==1"> 已支付</text> 
						<text class="t2" v-if="addmoneystatus==0" style="color:red;"> 待支付</text>
					</view>
					<view class="qrcode"><image :src="paycode"></view>
					<view class="btn"><button class="btn-cancel" @tap="cancel">关闭</button> 
					
					<button class="btn-update" v-if="addmoneystatus==0" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'"  @tap="update" :data-key="index" :data-id="id"  >修改差价</button></view>
			</view>
		</view>	
		<!-- 未付款订单改价 -->
		<view class="modal" v-if="ischangeprice">
			<view class="addmoney changeprice">
					<view class="title">订单改价</view>
					<view class="item flex-y-center">
						<label class="label">订单金额：</label><input type="text" name="blance_price" v-model="totalprice" placeholder="订单金额"  placeholder-style="font-size:24rpx"/>元
					</view>
					<view class="flex-xy-center"><button class="cbtn" @tap="changepriceCancel">取消</button><button class="cbtn"  @tap.stop="changepriceSub">确定</button></view>
			</view>
		</view>
	</block>

	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
	<view style="display:none">{{timestamp}}</view>
	<wxxieyi></wxxieyi>
</view>
</template>

<script>
var app = getApp();

export default {
  data() {
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			pre_url:app.globalData.pre_url,

      st: '11',
      datalist: [],
      pagenum: 1,
      nomore: false,
      nodata: false,
			keyword:'',
			interval1:null,
			timestamp:'',
			showform:0,
			showtabbar:false,
			showaddmoney:false,
			showmodal:false,
			addprice:0,
			showpaycodes:false,
			paycode:'',
			addmoneystatus:0,
      
      yuyuecar:false,
      cancancel:false,
      needstartpic:false,
      startpic:'',
      needendpic:false,
      endpic:'',
			ischangeprice:false,
			totalprice:0,
			changeid:0,
			showservicepics:false
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || '11';
		if(this.opt.mid){
			this.showtabbar = false;
		}else{
			this.showtabbar = true;
		}
		this.getdata();
  },
	onUnload:function(){
		clearInterval(this.interval1);
	},
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdata(true);
    }
  },
  methods: {
    changetab: function (st) {
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
    getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var st = that.st;
      var pagenum = that.pagenum;
      var keyword = that.keyword;
			that.nodata = false;
			that.nomore = false;
      app.post('ApiYuyueWorker/orderlist', {st: st,pagenum: pagenum,keyword:keyword,mid:this.opt.mid}, function (res) {
				if(res.status==0){
					app.alert(res.msg);
					return;
				}
        var data = res.datalist;
        if (pagenum == 1) {
					that.datalist = data;
					that.nowtime = res.nowtime
					that.showform = res.showform;
					that.showaddmoney = res.addmoney
          if (data.length == 0) {
            that.nodata = true;
          }
          if(res.yuyuecar){
            that.yuyuecar = true
            if(res.cancancel){
              that.cancancel = res.cancancel;
            }
            if(res.needstartpic){
              that.needstartpic = res.needstartpic;
            }
            if(res.needendpic){
              that.needendpic = res.needendpic;
            }
          }
					if(res.showservicepics){
						that.showservicepics = res.showservicepics;
					}
					that.loaded();
					that.updatemylocation();
					clearInterval(that.interval1);
					that.interval1 = setInterval(function(){
						that.updatemylocation(true);
						that.nowtime = that.nowtime + 10;
					},10000)
        }else{
          if (data.length == 0) {
            that.nomore = true;
          } else {
            var datalist = that.datalist;
            var newdata = datalist.concat(data);
            that.datalist = newdata;
          }
        }
      });
    },
		updatemylocation:function(){
			var that = this;
			app.getLocation(function(res){
				var longitude = res.longitude;
				var latitude = res.latitude;
				var datalist = that.datalist;
				console.log(datalist);
				for(var i in datalist){
					var thisdata = datalist[i];
					var rs = that.getdistance(thisdata.longitude2,thisdata.latitude2,longitude,latitude,1);
					thisdata.juli2 = rs.juli;
					thisdata.juli2_unit = rs.unit;
					thisdata.leftminute = parseInt((thisdata.yujitime - that.nowtime) / 60);
					datalist[i] = thisdata;
				}
				that.datalist = datalist;
				that.timestamp = parseInt((new Date().getTime())/1000);
				app.get('ApiYuyueWorker/updatemylocation',{longitude:longitude,latitude:latitude,t:that.timestamp},function(){
					//if(needload) that.getdata();
				});
			});
		},
		getdistance: function (lng1, lat1, lng2, lat2) {
			if(!lat1 || !lng1 || !lat2 || !lng2) return '';
			var rad1 = lat1 * Math.PI / 180.0;
			var rad2 = lat2 * Math.PI / 180.0;
			var a = rad1 - rad2;
			var b = lng1 * Math.PI / 180.0 - lng2 * Math.PI / 180.0;
			var r = 6378137;
			var juli = r * 2 * Math.asin(Math.sqrt(Math.pow(Math.sin(a / 2), 2) + Math.cos(rad1) * Math.cos(rad2) * Math.pow(Math.sin(b / 2), 2)));
			var unit = 'm';
			if(juli> 1000){
				juli = juli/1000;
				unit = 'km';
			}
			juli = juli.toFixed(1);
			return {juli:juli,unit:unit}
		},
    setst: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      var st = e.currentTarget.dataset.st;
			if(st == 2){
				if(that.showaddmoney){
						var tips = '确定已经出发吗?';
				}else{
						var tips = '确定改为已到达吗?';
				}
			}else if(st == 3){
				var tips = '确定改为已完成吗?';
			}else if(st == 5){
				var tips = '确定开始服务吗?';
			}else if(st == 10){
        var tips = '确定要取消订单吗?';
      }
      app.confirm(tips, function () {
				app.showLoading('提交中');
        app.post('ApiYuyueWorker/setst', {id: id,st:st}, function (data) {
					app.showLoading(false);
					if(data.status==1){
						app.success(data.msg);
						setTimeout(function () {
						  that.getdata();
						}, 1000);
					}else{
						app.error(data.msg);
					}
         
        });
      });
    },
		daohang:function(e){
			var that = this;
			var index = e.currentTarget.dataset.index;
			var datainfo = that.datalist[index];
      var protype = e.currentTarget.dataset.protype;
      var fwtype = e.currentTarget.dataset.fwtype;

      if(protype==1 || fwtype == 2){
      	var list = ['导航到用户'];
      }else{
      	var list = ['导航到商家'];
      }
			uni.showActionSheet({
        itemList: list,
        success: function (res) {
					if(res.tapIndex >= 0){
						if (res.tapIndex == 0) {
							if(protype==1){
								// 用户
								var longitude = datainfo.longitude2
								var latitude = datainfo.latitude2
								var name = datainfo.orderinfo.address
								var address = datainfo.orderinfo.address
							}else{
								if(fwtype != 2){
									// 到店
									var longitude = datainfo.longitude
									var latitude = datainfo.latitude
									var name = datainfo.binfo.name
									var address = datainfo.binfo.address
								}else{
									// 上门
									var longitude = datainfo.longitude2
									var latitude = datainfo.latitude2
									var name = datainfo.orderinfo.address
									var address = datainfo.orderinfo.address
								}
							}
						}
						uni.openLocation({
							latitude:parseFloat(latitude),
							longitude:parseFloat(longitude),
							name:name,
							address:address,
							scale: 13,
							success: function () {
                console.log('success');
							},
							fail:function(res){
								console.log(res);
							}
						})
					}
				}
			});
		},
    searchConfirm: function (e) {
      var that = this;
      var keyword = e.detail.value;
      that.keyword = keyword
      that.getdata();
    },
		addmoney:function(e){
			var that=this
			this.showmodal=true
			this.id= e.currentTarget.dataset.id
		},
		cancel:function(e){
			var that=this
			this.showmodal=false
			this.showpaycodes=false
		},
		bindMoney:function(e){
			var that=this
			that.addprice = e.detail.value
		},
		addconfirm:function(e){
			var that = this
			if(!that.addprice){
				app.error('请输入金额');
				return;
			} 
			app.post('ApiYuyueWorker/addmoney', {id:that.id,price:that.addprice,addmoneyPayorderid:that.addmoneyPayorderid}, function (data) {
				app.showLoading(false);
				app.success(data.msg);
				if(data.payorderid){
						that.showmodal=false
						that.getdata()
				}
			});
		},
		showpaycode:function(e){
			var that=this
			this.showpaycodes=true
			var index= e.currentTarget.dataset.key
			that.index = index
			this.addprice = 	that.datalist[index].addprice
			this.paycode = 	that.datalist[index].paycode
			this.addmoneystatus = 	that.datalist[index].addmoneystatus
			this.addmoneyPayorderid = 	that.datalist[index].addmoneyPayorderid
			this.id= e.currentTarget.dataset.id
		},
		update:function(e){
			var that=this
			this.showmodal=true
			this.showpaycodes=false
			var index= e.currentTarget.dataset.key
			that.addprice = that.datalist[index].addprice
			
		},
		changeprice:function(e){
			this.changeid = e.currentTarget.dataset.id;
			this.totalprice = e.currentTarget.dataset.price;
			this.ischangeprice = true;
		},
		changepriceCancel:function(){
			this.ischangeprice = false;
		},
		changepriceSub:function(){
			var that = this;
			app.showLoading('提交中...');
			app.post('ApiYuyueWorker/changeprice', {id:that.changeid,totalprice:that.totalprice}, function (data) {
				app.showLoading(false);
				if(data.status==1){
					that.ischangeprice = false;
					setTimeout(function(){
						that.getdata()
					},1000)
				}else{
					app.error(data.msg);
				}
			});
		},
  }
};
</script>
<style>
@import "./common.css";
.container{ width:100%;display:flex;flex-direction:column}
.search-container {width: 100%;height:100rpx;padding: 20rpx 23rpx 20rpx 23rpx;background-color: #fff;position: relative;overflow: hidden;border-bottom:1px solid #f5f5f5}
.search-box {display:flex;align-items:center;height:60rpx;border-radius:30rpx;border:0;background-color:#f7f7f7;flex:1}
.search-box .img{width:24rpx;height:24rpx;margin-right:10rpx;margin-left:30rpx}
.search-box .search-text {font-size:24rpx;color:#222;width: 100%;}

.order-box{ width: 94%;margin:20rpx 3%;padding:6rpx 3%; background: #fff;border-radius:8px}
.order-box .head{ display:flex;width:100%; border-bottom: 1px #f5f5f5 solid; height:88rpx; line-height:88rpx; overflow: hidden; color: #999;}
.order-box .head .f1{display:flex;align-items:center;color:#222222;font-size: 26rpx;}
.order-box .head .f1 .img{width:24rpx;height:24rpx;margin-right:4px}
.order-box .head .f1 .t1{color:#06A051;margin-right:10rpx}
.order-box .head .f2{color:#FF6F30}
.order-box .head .f2 .t1{font-size:36rpx;margin-right:4rpx}
.order-box .head .f2 .t11{font-size:30rpx;color: #999;}

.order-box .content{display:flex;justify-content:space-between;width: 100%; padding:16rpx 0px;border-bottom: 1px solid #f5f5f5;position:relative}
.order-box .content .f1{width:100rpx;display:flex;flex-direction:column;align-items:center}
.order-box .content .f1 .t1{display:flex;flex-direction:column;align-items:center}
.order-box .content .f1 .t1 .x1{color:#FF6F30;font-size:28rpx;font-weight:bold}
.order-box .content .f1 .t1 .x2{color:#999999;font-size:24rpx;margin-bottom:8rpx}
.order-box .content .f1 .t2 .img{width:12rpx;height:36rpx}

.order-box .content .f1 .t3{display:flex;flex-direction:column;align-items:center}
.order-box .content .f1 .t3 .x1{color:#FF6F30;font-size:28rpx;font-weight:bold}
.order-box .content .f1 .t3 .x2{color:#999999;font-size:24rpx}
.order-box .content .f2{flex:1;padding:0 20rpx}
.order-box .content .f2 .t1{font-size:36rpx;color:#222222;font-weight:bold;line-height:50rpx;margin-bottom:6rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.order-box .content .f2 .t2{font-size:24rpx;color:#222222;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.order-box .content .f2 .t3{font-size:36rpx;color:#222222;font-weight:bold;line-height:50rpx;margin-top:30rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}
.order-box .content .f3 .img{width:72rpx;height:168rpx}

.order-box .op{display:flex;justify-content:flex-end;align-items:center;width:100%; padding:20rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}
.order-box .op .t1{color:#06A051;font-weight:bold}
.order-box .op .t3{color:#ff711d;}
.order-box .op .btn1{background:linear-gradient(-90deg, #06A051 0%, #03B269 100%);height:70rpx;line-height:70rpx;color:#fff;border-radius:10rpx;text-align:center;font-size:28rpx; padding: 0 20rpx; font-size: 24rpx;}
.order-box .op .btn2{ margin-right: 20rpx; font-size: 24rpx; background:rgb(5,162,83,0.1) ; color: #06A152; border: 1rpx solid #1AB271; }
.order-box .op .btn3{font-size: 24rpx; background: #ff8f4d; color:#ffffff; height:60rpx;border-radius: 10rpx;min-width: 110rpx;display: flex;align-items: center;justify-content: center;}

.modal{ position: fixed; width: 100%; height: 100%; bottom: 0; background: rgb(0,0,0,0.4); z-index: 100; display: flex; justify-content: center;}
.modal .addmoney{ width: 100%; background: #fff; width: 80%; position: absolute; top: 30%; border-radius: 10rpx; }
.modal .title{ height: 80rpx; ;line-height: 80rpx; text-align: center; font-weight: bold; border-bottom: 1rpx solid #f5f5f5; font-size: 32rpx; }
.modal .item{ display: flex; padding: 30rpx;}
.modal .item input{ width: 200rpx;}
.modal .item label{ width:200rpx; text-align: right; font-weight: bold;}
.modal .item .t2{ color: #008000; font-weight: bold;}
.modal .btn{ display: flex; margin: 30rpx 20rpx; }
.modal .btn .btn-cancel{  background-color: #F2F2F2; width: 150rpx; border-radius: 10rpx;}
.modal .btn .confirm{ width: 150rpx; border-radius: 10rpx; color: #fff;}
.modal .btn .btn-update{ width: 150rpx; border-radius: 10rpx; color: #fff; }
.modal .addmoney .price{ color: red; font-size: 32rpx; font-weight: bold;}
.modal .qrcode{ display: flex; align-items: center;}
.modal .qrcode image{width: 300rpx; height: 300rpx; margin: auto;}
.changeprice{height: 400rpx;}
.changeprice .item{height: 160rpx;}
.changeprice .item input{border-bottom: 1px solid #e1e1e1;padding:14rpx;line-height: 70rpx;height: 70rpx;text-align: center;}
.changeprice .title{font-weight: normal;font-size: 28rpx;}
.changeprice .cbtn{border: 1px solid #e1e1e1;width: 35%;text-align: center;border-radius: 8rpx;margin:20rpx 10rpx;}
.changeprice .cbtn:last-child{background: #ff8f4d; color:#ffffff;}
.fwtype1{background: linear-gradient(-90deg, #06A051 0%, #03B269 100%);color: #fff;border-radius: 8rpx;
	text-align: center;margin: 24rpx 10rpx;padding: 5rpx 8rpx;line-height: 33rpx;font-size: 22rpx;}
.fwtype2{background: #FF6F30;color: #fff;border-radius: 8rpx;text-align: center;margin: 24rpx 8rpx;padding: 5rpx 10rpx;line-height: 33rpx;font-size: 22rpx;}
</style>