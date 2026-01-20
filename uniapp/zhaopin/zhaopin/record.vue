<template>
<view style="width:100%" v-if="isload">
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
	<view class="topsearch flex-y-center">
		<view class="f1 flex-y-center">
			<image class="img" src="/static/img/search_ico.png"></image>
			<input :value="keyword" placeholder="输入关键字搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
		</view>
	</view>
	<view class="product-container">
		<view class="product-itemlist">
			<view class="item" v-for="(item,index) in datalist" :key="item.id" @click="goto" :data-url="'/zhaopin/zhaopin/recorddetail?id='+item.id+'&type='+tabindex">
				<block v-if="tabindex==1">
					<view class="item1">
							<view class="product-info">
								<view class="p1">
								<text class="qiuicon">求</text>{{item.qiuzhi.title}}
								</view>
								<view class="p2"><text>期望薪资：</text><text class="number">{{item.qiuzhi.salary}}</text></view>
								<view class="p2">
									<text>期望岗位：</text>
									<text>{{item.qiuzhi.cnames}}</text>
								</view>
							</view>
							<view class="product-info product-info2">
								<view class="p1">
								<text class="zhaoicon">招</text><text class="business" v-if="item.business_name">{{item.business_name}}-</text>{{item.zhaopin.title}}
								</view>
								<view class="p2"><text>薪资范围：</text><text class="number">{{item.zhaopin.salary}}</text></view>
								<view class="p2">
									<text>招聘岗位：</text>
									<text>{{item.zhaopin.cname}}</text>
								</view>
							</view>
					</view>
					<view class="option flex-c">
							<view class="btn">查看合同</view>
					</view>
				</block>
					<block v-if="tabindex==2">
						<view class="item1">
								<view class="product-info">
									<view class="p1">
									<text class="zhaoicon">招</text><text class="business" v-if="item.business_name">{{item.business_name}}-</text>{{item.zhaopin.title}}
									</view>
									<view class="p2"><text>薪资范围：</text><text class="number">{{item.zhaopin.salary}}</text></view>
									<view class="p2">
										<text>招聘岗位：</text>
										<text>{{item.zhaopin.cname}}</text>
									</view>
								</view>
								<view class="product-info product-info2">
									<view class="p1">
									<text class="qiuicon">求</text>{{item.qiuzhi.title}}
									</view>
									<view class="p2"><text>期望薪资：</text><text class="number">{{item.qiuzhi.salary}}</text></view>
									<view class="p2">
										<text>期望岗位：</text>
										<text>{{item.qiuzhi.cnames}}</text>
									</view>
								</view>
						</view>
						<view class="option flex-c">
								<view class="btn" @tap.stop="uploadpics" :data-id="item.id" v-if="item.contract_status==0">{{item.contract_pics.length>0?'追加':'上传'}}合同</view>
								<view class="btn" v-if="item.contract_pics.length>0">查看合同</view>
						</view>
					</block>
			</view>
		</view>
	</view>
	<view class="tosign" @tap="goto" data-url="/zhaopin/notice/index" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)',color:'#ffffff'}">
		<view class="v">消息</view>
	</view>
	<nomore text="没有更多信息了" v-if="nomore"></nomore>
	<nodata text="没有查找到相关信息" v-if="nodata"></nodata>
	<loading v-if="loading"></loading>
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
			tabindex:1
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.bid = this.opt.bid ? this.opt.bid : 0;
		this.st = this.opt.st ? this.opt.st : 0;
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
    getlist: function (loadmore) {
      var that = this;
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var pagenum = that.pagenum;
      var keyword = that.keyword;
      app.post('ApiZhaopin/zhaopinRecord',{
				pagenum: pagenum,
				keyword:that.keyword,
				type:that.tabindex
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
		uploadpics: function (e) {
			var that = this;
			var id = e.currentTarget.dataset.id;
			var pics = [];
			app.chooseImage(function(urls){
				for(var i=0;i<urls.length;i++){
					pics.push(urls[i]);
				}
				if(pics.length>0){
					app.confirm('确定上传？',function(){
						app.post("ApiZhaopin/contracntUpload", {id:id,pics:pics.join(',')}, function (data) {
							app.showLoading(false);
						  if (data.status == 1) {
						    app.success(data.msg);
						    setTimeout(function () {
									that.getlist(false);
						    }, 1000);
						  } else {
						    app.error(data.msg);
						  }
						});
					})
				}
			},20)
			
		},
	
  }
};
</script>
<style>
@import "../common.css";
	.top-tab{padding:20rpx 30rpx;background: #FFFFFF;font-size: 32rpx;color: #222222;}
	.top-tab .tab-item{width: 40%;text-align: center;display: flex;flex-direction: column;align-items: center;justify-content: center;}
	.top-tab .tab-item .line{width: 50rpx;margin-top: 6rpx;border-radius: 10rpx;height:5rpx}
.topsearch{width:94%;margin:16rpx 3%;margin-top: 20rpx;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.product-container {width: 100%;margin-top: 20rpx;font-size:26rpx;padding:0;}
.product-itemlist{height: auto; position: relative;overflow: hidden; padding: 0px; }
.product-itemlist .item{width:100%;display: inline-block;margin-bottom: 20rpx;background: #fff;padding: 20rpx;padding-top: 0;}
.product-itemlist .item1{align-items: center;padding: 20rpx;padding-bottom: 0;}
.product-itemlist .product-info {color: #999;font-size: 24rpx;padding-bottom:10rpx;}
.product-itemlist .product-info2 {border-top: 1rpx dashed #e8e8e8;padding-top:10rpx;text-align: right;}
.product-itemlist .product-info .p1 {color:#323232;font-weight:bold;font-size:28rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product-itemlist .product-info .number {color:#FF3A69;}
.product-itemlist .product-info .p2 {line-height: 40rpx;}
.product-itemlist .item2{ border-top: 1rpx solid #f6f6f6;; padding-top: 14rpx; justify-content: flex-start; line-height: 36rpx; color: #6c6c6c; font-size: 24rpx;flex-wrap: wrap;}
.product-itemlist .item2 .tagitem{background: #f4f7fe;text-align: center;padding: 2rpx 8rpx;margin-right: 8rpx;white-space: normal;}
.product-itemlist .text2{ color:#FF3A69; width: 128rpx;height: 48rpx;border-radius: 24rpx 0px 0px 24rpx; text-align: center;background: linear-gradient(-90deg, rgba(255, 235, 240, 0.4) 0%, #FDE6EC 100%);}
.option{padding-top: 20rpx;border-top: 1rpx solid #e8e8e8;}
.option .btn{min-width: 120rpx;text-align: center;border: 1rpx solid #e4e4e4;padding: 6rpx 10rpx;margin-left: 10rpx;color: #757575;font-size: 24rpx;}
.option .btn.st1{background: #F05525;color: #FFFFFF;border-color: #fcdacfdb;}

.tosign{width: 100rpx;height: 100rpx;background: #031028;color: #FFFFFF;position: fixed;bottom: 30rpx;right: 10rpx;display:flex;justify-content: center;align-items: center;
border-radius: 50%;flex-direction: column;text-align: center;font-size: 24rpx;}
.tosign .dot{width: 14rpx;height: 14rpx;background: #FF0000;position: relative;top: 6rpx;right: -24rpx;border-radius: 50%;}

.product-info .qiuicon{background: #ff9b0540;color: #ff9b05; border-radius: 6rpx;padding: 0 6rpx;font-weight: normal;font-size: 24rpx;margin-right: 4rpx;}
.product-info .zhaoicon{background: #00968833;color: #009688;border-radius: 6rpx;padding: 0 6rpx;font-weight: normal;font-size: 24rpx;}

</style>