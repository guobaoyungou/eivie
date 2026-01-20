<template>
<view class="container">
	<block v-if="isload">
		<view class="top-tab flex-sb">
			<view class="tab-item" @tap="toptabchange" data-index="1">
				<view>求职</view>
				<view class="line" v-if="tabindex==1" :style="{background:t('color1')}"></view>
				<view class="line" v-else></view>
			</view>
			<view class="tab-item" @tap="toptabchange" data-index="2">
				<view>招聘</view>
				<view class="line" v-if="tabindex==2" :style="{background:t('color1')}"></view>
				<view class="line" v-else></view>
			</view>
		</view>
		<view class="search-container">
			<dd-tab :itemdata="['审核中','已通过','未通过','已下架']" :itemst="['0','1','2','3']" :st="st" :isfixed="false" @changetab="changetab"></dd-tab>
		</view>
		<view class="product-container">
			<!-- 求职 -->
					<view class="qiuzhi-itemlist" v-if="tabindex==1">
						<view class="item" v-for="(item,index) in datalist" :key="item.id" @click="goto" :data-url="'/zhaopin/qiuzhi/detail?id='+item.id">
								<view class="item1 flex">	
										<view class="qiuzhi-pic">
											<text class="status" :class="item.top_feetype>0?'':'hide'">已置顶</text>
											<image class="image" :style="'filter: blur('+item.mohu+'px);-webkit-filter: blur('+item.mohu+'px);-moz-filter: blur('+item.mohu+'px)'"  :src="item.thumb" mode="aspectFit"/>
										</view>
										<view class="qiuzhi-info">
											<view class="p1">
											{{item.name}}
											<image v-if="item.sex==1" src="../../static/img/nan.png"></image>
											<image v-if="item.sex==2" src="../../static/img/nv.png"></image>
											</view>
											<view class="p2"><text>期望薪资：</text><text class="number">{{item.salary}}</text></view>
											<view class="p2">
												<text>期望岗位：</text>
												<text>{{item.cnames}}</text>
											</view>
											<view class="p2">
												<text>期望城市：</text>
												<text>{{item.area}}</text>
											</view>
										</view>
								</view>
								
								<view class="item2" v-if="item.top_feetype>0">
									置顶到期时间：{{item.top_endtime}}
								</view>
								<!-- <view class="item2 flex" v-if="item.tags && item.tags.length>0">
									<view class="tagitem" v-for="(wf,wk) in item.tags" :key="wk">{{wf}}</view>
								</view> -->
								<view class="option flex-e">
									<block v-if="item.status==1">
										<view class="btn" @tap.stop="offline" :data-id="item.id">下架</view>
										<view class="btn st1" @tap.stop="goto" :data-url="'/zhaopin/qiuzhi/top?id='+item.id">置顶</view>
									</block>
									<block v-if="item.status==0 || item.status==2 || item.status==3">
										<view class="btn st1" @tap.stop="del" :data-id="item.id">删除</view>
										<view class="btn st2" v-if="item.status==2" @tap.stop="goto" :data-url="'/zhaopin/qiuzhi/add?id='+item.id">修改</view>
									</block>
									<view class="btn st3" v-if="item.apply_status==-1" @tap.stop="goto" data-url="/zhaopin/qiuzhi/apply">去认证</view>
								</view>
						</view>
					</view>
			<!-- 求职end -->
			<view class="product-itemlist" v-if="tabindex==2">
				<!-- 招聘 -->
				<view v-if="tabindex==2" class="item" v-for="(item,index) in datalist" :key="item.id" @click="goto" :data-url="'/zhaopin/zhaopin/detail?id='+item.id">
						<view class="item1 flex">	
								<view class="product-pic">
									<text class="status" :class="item.top_feetype>0?'':'hide'">已置顶</text>
									<image class="image" :src="item.thumb" mode="heightFix"/>
								</view>
								<view class="product-info">
									<view class="p1">{{item.title}}</view>
									<view class="p2">{{item.salary}}</view>
									<view class="p3 flex" >
										<view class="tagitem" v-if="item.cname">{{item.cname}}</view>
										<view class="tagitem" v-if="item.num">{{item.num}}人</view>
										<view class="tagitem" v-if="item.experience">{{item.experience}}</view>
										<view class="tagitem" v-if="item.education">{{item.education}}</view>
									</view>
									<view class="p4 flex-s" v-if="item.area">
											<image src="../../static/img/address3.png"></image>
											<text>{{item.area}}</text>
									</view>
								</view>
						</view>
						<view class="item2" v-if="item.top_feetype>0">
							置顶到期时间：{{item.top_endtime}}
						</view>
						<!-- <view class="item2 flex" v-if="item.welfare && item.welfare.length>0">
							<view class="tagitem" v-for="(wf,wk) in item.welfare" :key="wk">{{wf}}</view>
						</view> -->
						<view class="option flex-e">
							<block v-if="item.status==1">
								<view class="btn" v-if="item.assurance_id==0" @tap.stop="offline" :data-id="item.id">下架</view>
								<view class="btn st1" v-if="item.top_feetype==0 && item.assurance_id==0" @tap.stop="goto" :data-url="'/zhaopin/zhaopin/top?id='+item.id">置顶</view>
								<view v-if="item.assurance_id>0" class="btn disabled" @tap.stop="">已担保</view>
								<view v-if="item.assurance_id==0" class="btn st2" @tap.stop="goto" :data-url="'/zhaopin/zhaopin/top?isas=1&id='+item.id">担保</view>
							</block>
							<block v-if="item.status==0 || item.status==2 || item.status==3">
								<view class="btn st1" @tap.stop="del" :data-id="item.id">删除</view>
								<view class="btn st2" v-if="item.status==2" @tap.stop="goto" :data-url="'/zhaopin/zhaopin/add?id='+item.id">修改</view>
							</block>
						</view>
				</view>
				<!-- 招聘end -->
			</view>
		</view>
		<view class="tosign" @tap="goto" data-url="/zhaopin/notice/index" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)',color:'#ffffff'}">
			<view class="dot"></view>
			<view class="v">消息</view>
		</view>
	</block>
	<nomore text="没有更多信息了" v-if="nomore"></nomore>
	<nodata text="没有查找到相关信息" v-if="nodata"></nodata>
	<loading v-if="loading"></loading>
	<popmsg ref="popmsg"></popmsg>
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
			nomore:false,
			nodata:false,
      keyword: '',
      pagenum: 1,
      datalist: [],
      st:0,
			tabindex:1,
			isshowadd:false
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.bid = this.opt.bid ? this.opt.bid : 0;
		this.st = this.opt.st ? this.opt.st : 0;
		this.tabindex = this.opt.type==2?this.opt.type:1;
		console.log(this.tabindex)
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getlist(true);
    }
  },
  methods: {
		getdata:function(){
			this.getlist();
			this.loaded();
		},
		searchConfirm:function(e){
			this.getlist(false);
			this.loaded();
		},
		toptabchange(e){
			var that = this;
			var index = e.currentTarget.dataset.index;
			that.tabindex = index
			that.nodata = false
			that.nomore = false
			that.getlist(false)
		},
		showadd:function(){
			this.isshowadd = true
		},
		hideadd:function(){
			this.isshowadd = false
		},
		changetab: function (st) {
		    this.pagenum = 1;
		    this.st = st;
				this.nodata = false;
				this.nomore = false;
		    this.datalist = [];
		    uni.pageScrollTo({
		      scrollTop: 0,
		      duration: 0
		    });
		    this.getlist(false);
		},
    getlist: function (loadmore) {
      var that = this;
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var pagenum = that.pagenum;
      var keyword = that.keyword;
			var url = 'ApiZhaopin/zhaopinListMy'
			if(that.tabindex==1){
				url = 'ApiZhaopin/qiuzhiListMy'
			}
      app.get(url,{
				pagenum: pagenum,
				keyword:that.keyword,
				st:that.st
			}, function (res) {
				that.loading = false;
        var data = res.data;
        if (pagenum == 1) {
          that.datalist = data;
          if (data.length == 0) {
            that.nodata = true;
          }
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
		offline: function (e) {
		  var that = this;
		  var id = e.currentTarget.dataset.id;
			if(that.tabindex==1){
				var action = 'qiuzhiOffline'
			}else{
				var action = 'zhaopinOffline'
			}
		  app.confirm('确定下架该信息吗?', function () {
				app.showLoading('提交中');
		    app.post('ApiZhaopin/'+action, {id: id}, function (data) {
					app.showLoading(false);
		      app.success(data.msg);
		      setTimeout(function () {
		        that.getlist(false);
		      }, 1000);
		    });
		  });
		},
		del: function (e) {
		  var that = this;
		  var id = e.currentTarget.dataset.id;
			if(that.tabindex==1){
				var action = 'qiuzhiDel'
			}else{
				var action = 'zhaopinDel'
			}
		  app.confirm('确定删除该信息吗?', function () {
				app.showLoading('提交中');
		    app.post('ApiZhaopin/'+action, {id: id}, function (data) {
					app.showLoading(false);
		      app.success(data.msg);
		      setTimeout(function () {
		        that.getlist(false);
		      }, 1000);
		    });
		  });
		},
		assurance:function(e){
			var that = this;
			var id = e.currentTarget.dataset.id;
			var feetype = e.currentTarget.dataset.feetype;
			if(feetype>0){
				app.showLoading('提交中');
				app.post('ApiZhaopin/zhaopinAssurance', {id: id}, function (res) {
					app.showLoading(false);
					if(res.status==1){
						app.success(res.msg);
						setTimeout(function () {
							that.getlist(false);
						}, 1000);
					}else if(res.status==2){
						//缴纳保证金提醒
						app.confirm(res.msg+',去缴纳?', function () {
							var tourl = encodeURIComponent('/zhaopin/zhaopin/my?type=2&st=1')
							app.goto('assurancefee?tourl'+tourl)
						});
					}else{
						app.alert(res.msg);
					}
				});
			}else{
				app.confirm('担保招聘仅限置顶帖子\n是否现在置顶?', function () {
					app.goto('top?id='+id+'&as=1')
				});
			}
		}
		
		
  }
};
</script>
<style>
	@import "../common.css";
	.top-tab{padding: 30rpx 30rpx 0 30rpx;background: #FFFFFF;font-size: 32rpx;color: #222222;}
	.top-tab .tab-item{width: 40%;text-align: center;display: flex;flex-direction: column;align-items: center;justify-content: center;}
	.top-tab .tab-item .line{width: 50rpx;margin-top: 6rpx;border-radius: 10rpx;height:5rpx}
.product-container {width: 100%;margin-top: 16rpx;font-size:26rpx;padding:0;}
.product-itemlist{height: auto; position: relative;overflow: hidden; padding: 0px; }
.product-itemlist .item{width:100%;display: inline-block;margin-bottom: 20rpx;background: #fff;padding: 20rpx;}
.product-itemlist .item1{}
.product-itemlist .product-pic {width: 260rpx;height:200rpx;overflow:hidden;background: #ffffff;padding-bottom: 30%;position: relative;background: #ead4b642;border-radius:5px;}
.product-itemlist .product-pic .image{max-width: 100%;height: 214rpx;border-radius:5px;vertical-align: middle;top: -34rpx;}
.product-itemlist .product-info {width: 70%;padding:6rpx 10rpx 5rpx 20rpx;font-size: 24rpx;}
.product-itemlist .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product-itemlist .product-info .p2 {color:#FF3A69;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product-itemlist .product-info .p3 {font-size: 20rpx;padding: 10rpx 0;}
.product-itemlist .product-info .p3 .tagitem{background: #cce3fc4d;color: #457dc6;text-align: center;padding: 2rpx 8rpx;margin-right: 8rpx;}
.product-itemlist .product-info .p4{line-height: 42rpx;color: #999999;}
.product-itemlist .product-info .p4 image{width: 28rpx;height: 28rpx;vertical-align: text-bottom;}
.product-itemlist .item2{ border-top: 1rpx solid #f6f6f6;; padding-top: 20rpx; justify-content: flex-start; line-height: 42rpx; color: #6c6c6c; font-size: 24rpx;flex-wrap: wrap;}
.product-itemlist .item2 .tagitem{background: #f4f7fe;text-align: center;padding: 2rpx 8rpx;margin-right: 8rpx;white-space: normal;}
.product-itemlist .head  image{ width:42rpx ; height: 42rpx;  border-radius: 50%; vertical-align: middle; margin-right: 10rpx;}
.product-itemlist .text2{ color:#FF3A69; width: 128rpx;height: 48rpx;border-radius: 24rpx 0px 0px 24rpx; text-align: center;background: linear-gradient(-90deg, rgba(255, 235, 240, 0.4) 0%, #FDE6EC 100%);}

.product-itemlist .product-pic .status{color: #FFFFFF;background:#F05525;font-size: 20rpx;padding: 0 8rpx;position: relative;top: 0;z-index: 5;border-radius: 0 0 6rpx 0;}
.product-itemlist .product-pic .status.hide{opacity: 0;}



.qiuzhi-itemlist{height: auto; position: relative;overflow: hidden; padding: 0px; }
.qiuzhi-itemlist .item{width:100%;display: inline-block;margin-bottom: 20rpx;background: #fff;padding: 20rpx;padding-top: 0;}
.qiuzhi-itemlist .item1{align-items: center;padding: 20rpx 0;}
.qiuzhi-itemlist .qiuzhi-pic {width: 160rpx;height:160rpx;overflow:hidden;background: #ffffff;position: relative;}
.qiuzhi-itemlist .qiuzhi-pic .image{max-width: 100%;max-height: 100%;border-radius:5px;vertical-align: middle;top: -34rpx;}
.qiuzhi-itemlist .qiuzhi-pic .image.mohu{filter: blur(10px);-webkit-filter: blur(10px);-moz-filter: blur(10px);}
.qiuzhi-itemlist .qiuzhi-pic .status{color: #FFFFFF;background:#b1b2b2;font-size: 24rpx;padding: 0 8rpx;position: relative;top: 0;z-index: 5;border-radius: 0 0 6rpx 0;}
.qiuzhi-itemlist .qiuzhi-pic .st1{background:#FF3A69;}
.qiuzhi-itemlist .qiuzhi-pic .st2{background:#3889f6;}
.qiuzhi-itemlist .qiuzhi-pic .status{color: #FFFFFF;background:#F05525;font-size: 20rpx;padding: 0 8rpx;position: relative;top: 0;z-index: 5;border-radius: 0 0 6rpx 0;}
.qiuzhi-itemlist .qiuzhi-pic .status.hide{opacity: 0;}
.qiuzhi-itemlist .qiuzhi-info {padding-left:20rpx;color: #999;font-size: 24rpx;}
.qiuzhi-itemlist .qiuzhi-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.qiuzhi-itemlist .qiuzhi-info .p1 image{width: 30rpx;height:30rpx;vertical-align: text-bottom;}
.qiuzhi-itemlist .qiuzhi-info .number {color:#FF3A69;}
.qiuzhi-itemlist .qiuzhi-info .p2 {line-height: 40rpx;}

.qiuzhi-itemlist .item2{ border-top: 1rpx solid #f6f6f6;; padding-top: 14rpx; justify-content: flex-start; line-height: 36rpx; color: #6c6c6c; font-size: 24rpx;flex-wrap: wrap;}
.qiuzhi-itemlist .item2 .tagitem{background: #f4f7fe;text-align: center;padding: 2rpx 8rpx;margin-right: 8rpx;white-space: normal;}
.qiuzhi-itemlist .head  image{ width:42rpx ; height: 42rpx;  border-radius: 50%; vertical-align: middle; margin-right: 10rpx;}
.qiuzhi-itemlist .text2{ color:#FF3A69; width: 128rpx;height: 48rpx;border-radius: 24rpx 0px 0px 24rpx; text-align: center;background: linear-gradient(-90deg, rgba(255, 235, 240, 0.4) 0%, #FDE6EC 100%);}




.option .btn{min-width: 120rpx;text-align: center;border: 1rpx solid #e4e4e4;padding: 6rpx 10rpx;margin-left: 10rpx;color: #757575;font-size: 24rpx;}
.option .btn.st1{background: #F05525;color: #FFFFFF;border-color: #fcdacfdb;}
.option .btn.st3{background: #20a9ff;color: #FFFFFF;border-color: #20a9ff;}
.option .btn.st2{background: #009688;color: #FFFFFF;border-color: #009688;}
.option .btn.disabled{background: #CCCCCC;color: #FFFFFF;border-color: #CCCCCC;}

.tosign{width: 100rpx;height: 100rpx;background: #031028;color: #FFFFFF;position: fixed;bottom: 130rpx;right: 10rpx;display:flex;justify-content: center;align-items: center;
border-radius: 50%;flex-direction: column;text-align: center;font-size: 24rpx;}
.tosign .dot{width: 14rpx;height: 14rpx;background: #FF0000;position: relative;top: 6rpx;right: -24rpx;border-radius: 50%;}

.tabbar-item{display: flex;flex-direction: column;align-items: center;justify-content: center;color: #888;}
/* .tabbar-image-box{border-radius: 50%;border: 1rpx solid #b1b3b6;width: 50rpx;height: 50rpx;display: flex;justify-content: center;align-items: center;} */
.tabbar-text{color: #7b7b7b;}
.tabbar-icon {width: 50rpx;height: 50rpx;}
.popup__content{padding:30rpx;}
.popup__modal{border-radius: 0;max-height: 180rpx;min-height: 180rpx;border-radius: 20rpx 20rpx 0 0;}
.popup__content .add-item{display: flex;flex-direction: column;align-items: center;justify-content: center;padding: 0 30rpx;color: #888;}
.popup__content .add-item image{width: 80rpx;height: 80rpx;margin-bottom: 10rpx;}
</style>