<template>
<view class="container">
	<block v-if="isload">
		<view class="content">
			<view class="top flex">
				<view class="headimg"><image :src="data.headimg"> </view>
				<view class="f1">
					<view class="t1">{{data.realname}}</view>
					<view class="t2">{{data.jineng}}</view>
					<view class="t3">
						<text class="t11"><text class="bold">{{data.totalnum}}</text> 次服务</text>
						<text class="t11"><text class="bold">{{data.comment_score}}</text> 评价</text>
					</view>
				</view>
			</view>

			<view v-if="data.desc" class="desc" >
				{{data.desc}}
			</view>
			<!-- #ifdef MP-TOUTIAO -->
			<view class="dp-cover" v-if="video_status">
				<button open-type="share" data-channel="video" class="dp-cover-cover" :style="{
					zIndex:10,
					top:'20vh',
					left:'80vw',
					width:'110rpx',
					height:'110rpx'
				}">
					<image :src="pre_url+'/static/img/uploadvideo2.png'" :style="{width:'110rpx',height:'110rpx'}"/>
				</button>
			</view>
			<!-- #endif -->
			
			<view class="list">
				<view class="tab">
					<view :class="'item ' + (curTopIndex == 0 ? 'on' : '') " @tap="switchTopTab" :data-index="0">服务 {{data.count}}<view class="after" :style="{background:t('color1')}"></view></view>
					<view :class="'item ' + (curTopIndex == 1 ? 'on' : '') " @tap="switchTopTab" :data-index="1">评价 {{data.comment_score}}<view class="after" :style="{background:t('color1')}"></view></view>
					<view 	v-if="data.showdesc" :class="'item ' + (curTopIndex == 2 ? 'on' : '') " @tap="switchTopTab" :data-index="2">个人资料 <view class="after" :style="{background:t('color1')}"></view></view>
				</view>
				<view v-if="curTopIndex==0" v-for="(item, index) in datalist" :key="index" class="content2 flex" :data-id="item.id">
					<view class="f1" @click="goto" :data-url="'product?id='+item.id" >
						<view class="headimg"><image :src="item.pic" /></view>
						<view class="text1" style="margin-bottom:16rpx">	
							<text class="t1">{{item.name}} </text>
							<!-- <view class="text2">服务时长：{{item.fwlong}}分钟</view> -->
							<view class="text2">已售 {{item.sales}}</view>
							<view class="text3">
								<text class="t4">￥<text class="price"> {{item.sell_price}}</text> </text>
							</view>
						</view>	
					</view>
					<view>
						<view class="yuyue"  @click="goto" :data-url="'product?id='+item.id">预约</view>
					</view>
				</view>
				<view class="comment" v-if="curTopIndex==1">
					<view v-for="(item, index) in datalist" :key="index" class="item">
						<view class="f1">
							<image class="t1" :src="item.headimg"/>
							<view class="t2">{{item.nickname}}</view>
							<view class="flex1"></view>
							<view class="t3"><image class="img" v-for="(item2,index2) in [0,1,2,3,4]" :key="index2"  :src="'/static/img/star' + (item.score>item2?'2':'') + '.png'"/></view>
						</view>
						<view style="color:#777;font-size:22rpx;">{{item.createtime}}</view>
						<view class="f2">
							<text class="t1">{{item.content}}</text>
							<view class="t2">
								<block v-if="item.content_pic!=''">
									<block v-for="(itemp, index) in item.content_pic" :key="index">
										<view @tap="previewImage" :data-url="itemp" :data-urls="item.content_pic">
											<image :src="itemp" mode="widthFix"/>
										</view>
									</block>
								</block>
							</view>
						</view>
						<view class="f3" v-if="item.reply_content">
							<view class="arrow"></view>
							<view class="t1">商家回复：{{item.reply_content}}</view>
						</view>
					</view>
				</view>
				
				<view v-if="curTopIndex==2">
					<view class="plugdesc">
						<view class="item"><label class="t1">服务类目：</label>{{data.typename}}</view>
						<view class="item"><label class="t1">服务城市：</label>{{data.citys}}</view>
						<view class="item"><label class="t1">服务公里数：</label>{{data.fuwu_juli}}km</view>
						<view class="item"><label class="t1">性别：</label>{{data.sex}}</view>
						<view class="item"><label class="t1">年龄：</label>{{data.age}}</view>
						<view class="item"><label class="t1">联系信息：</label>{{data.tel}}</view>
					</view>
				</view>
			
			</view>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
		<view style="height:140rpx"></view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
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
			data:[],
      datalist: [],
      type: "",
			keyword:'',
			nodata:false,
			curTopIndex:0,
			
			video_status:0,
			video_title:'',
			video_tag:[],
			pagenum:1,
			nomore: false
    }
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.type = this.opt.type || '';
		this.getdata();
  },
	onShareAppMessage:function(shareOption){
		//#ifdef MP-TOUTIAO
		console.log(shareOption);
			return {
				title: this.video_title,
				channel: "video",
				extra: {
				        hashtag_list: this.video_tag,
				      },
				success: () => {
					console.log("分享成功");
				},
				 fail: (res) => {
				    console.log(res);
				    // 可根据 res.errCode 处理失败case
				  },
			};
		//#endif
		return this._sharewx({title:this.realname,pic:this.headimg});
	},
	onReachBottom: function () {
	  if (!this.nodata && !this.nomore) {
	    this.pagenum = this.pagenum + 1;
	    this.getdatalist(true);
	  }
	},
	onPullDownRefresh: function () {
		this.getdatalist();
	},
  methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			that.nodata = false;
			var that = this;
			var id = this.opt.id || 0;
			app.get('ApiYuyue/peodetail', {id:id}, function (res) {
				that.loading = false;
				var data = res.data;
				that.data = data;
				if(res.set){
					that.video_status = res.set.video_status;
					that.video_title = res.set.video_title;
					that.video_tag = res.set.video_tag;
				}
				that.loaded();
				that.getdatalist();
			});
		},
		switchTopTab: function (e) {
		  var that = this;
		  var index = parseInt(e.currentTarget.dataset.index);
		  this.curTopIndex = index;
			if(index<2){
				this.datalist = [];
				this.getdatalist();
			}
			if(index==2) that.nodata = false;
			
		},   
		getdatalist: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
			var that = this;
			var pagenum = that.pagenum;
			var id = that.opt.id ? that.opt.id : '';
			var order = that.order;
			var field = that.field; 
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
			app.post('ApiYuyue/getdlist', {curTopIndex:that.curTopIndex,pagenum: pagenum,field: field,order: order,id:id}, function (res) { 
				that.loading = false;
				uni.stopPullDownRefresh();
				var data = res.data;
				if (data.length == 0) {
					if(pagenum == 1){
						that.nodata = true;
					}else{
						that.nomore = true;
					}
				}
				var datalist = that.datalist;
				var newdata = datalist.concat(data);
				that.datalist = newdata;
			});
		 
		},
		scrolltolower: function () {
			if (!this.nomore) {
				this.pagenum = this.pagenum + 1;    
				this.getdatalist(true);
			}
		},
  }
};
</script>
<style>
.container{ background: #fff; }
.headimg{ width: 160rpx; height: 160rpx;margin-right: 20rpx;}
.headimg image{ width: 160rpx; height: 160rpx; border-radius: 50%; }
.top .f1{ margin-left: 10rpx;}
.f1 .t1{ color:#323232;  font-size: 32rpx; font-weight: bold; margin-top: 20rpx;}
.f1 .t2{ color:#999;  font-size: 24rpx; }
.f1 .t3{ margin-top: 30rpx;color:#999;font-size: 0.24rpx;}
.f1 .t3 .bold{ font-size: 36rpx; color: #323232; font-weight: bold;margin:0 8rpx;}
.f1 .t11{ margin-right: 30rpx; font-size: 24rpx;}
.desc{ color: #6d6e74; font-size: 26rpx; margin-top: 30rpx; padding:0 30rpx;}
.tab{ margin-top: 20rpx; display: flex; }
.tab .item{ padding-right:20rpx; color: #323232;font-size: 28rpx;  margin-right: 40rpx; line-height: 60rpx; overflow: hidden;position:relative; }
.tab .after{display:none;position:absolute;left:45%;margin-left:-20rpx;bottom:0rpx;height:6rpx;border-radius:1.5px;width:40rpx}
.tab .on .after{display:block}
.top{ padding: 30rpx;}

.plugdesc { padding: 30rpx 0;}
.plugdesc .item{ display: flex; padding: 10rpx 0;  color: #778899; }
.plugdesc .item .t1{ text-align: right; width: 180rpx; margin-right: 20rpx; color: #333;}

.list{ padding: 0 40rpx;}
.content2{width:100%;background:#fff;border-radius:5px; justify-content: space-between; margin-top: 40rpx; border-bottom: 1px solid #EEEEEE;}
.content2 .f1{display:flex;align-items:center}
.content2 .f1 image{ width: 140rpx; height: 140rpx; border-radius: 10rpx;}
.content2 .f1 .t1{color:#2B2B2B;font-weight:bold;font-size:32rpx;margin-left:10rpx;}
.content2 .f1 .t2{color:#999999;font-size:28rpx; background: #E8E8F7;color:#7A83EC; margin-left: 10rpx; padding:3rpx 20rpx; font-size: 20rpx; border-radius: 18rpx;}
.content2 .f1 .t3{ margin-left:10rpx;display: block; height: 40rpx;line-height: 40rpx;}
.content2 .f2{color:#2b2b2b;font-size:26rpx;line-height:42rpx;padding-bottom:20rpx;}
.content2 .f3{height:96rpx;display:flex;align-items:center}
.content2 .radio{flex-shrink:0;width: 36rpx;height: 36rpx;background: #FFFFFF;border: 3rpx solid #BFBFBF;border-radius: 50%;}
.content2 .radio .radio-img{width:100%;height:100%}
.content2 .mrtxt{color:#2B2B2B;font-size:26rpx;margin-left:10rpx}
.text2{ margin-left: 10rpx; color:#999999; font-size: 20rpx;margin-top: 10rpx;}
.text3{ margin-left: 10rpx; color:#999999; font-size: 20rpx;margin-top: 10rpx;}
.text3 .t5{ margin-left: 20rpx;}
.text3 .t5 text{ color:#7A83EC}
.text3 .t4 text{ color:#FF5347}
.text3 .t4 { color:#FF5347}
.text3 .t4 .price{ font-weight: bold;font-size: 30rpx;}
.yuyue{ background: #7A83EC; width:136rpx;height: 60rpx; line-height: 60rpx; padding: 0 10rpx; color:#fff; border-radius:28rpx; ; font-size: 20rpx; text-align: center; margin-top: 20rpx;}

.comment{display:flex;flex-direction:column;padding:10rpx 0;}
.comment .item{background-color:#fff;padding:10rpx 20rpx;display:flex;flex-direction:column;}
.comment .item .f1{display:flex;width:100%;align-items:center;padding:10rpx 0;}
.comment .item .f1 .t1{width:70rpx;height:70rpx;border-radius:50%;}
.comment .item .f1 .t2{padding-left:10rpx;color:#333;font-weight:bold;font-size:30rpx;}
.comment .item .f1 .t3{text-align:right;}
.comment .item .f1 .t3 .img{width:24rpx;height:24rpx;margin-left:10rpx}
.comment .item .score{ font-size: 24rpx;color:#f99716;}
.comment .item .score image{ width: 140rpx; height: 50rpx; vertical-align: middle;  margin-bottom:6rpx; margin-right: 6rpx;}
.comment .item .f2{display:flex;flex-direction:column;width:100%;padding:10rpx 0;}
.comment .item .f2 .t1{color:#333;font-size:28rpx;}
.comment .item .f2 .t2{display:flex;width:100%}
.comment .item .f2 .t2 image{width:100rpx;height:100rpx;margin:10rpx;}
.comment .item .f2 .t3{color:#aaa;font-size:24rpx;}
.comment .item .f2 .t3{color:#aaa;font-size:24rpx;}
.comment .item .f3{width:100%;padding:10rpx 0;position:relative}
.comment .item .f3 .arrow{width: 16rpx;height: 16rpx;background:#eee;transform: rotate(45deg);position:absolute;top:0rpx;left:36rpx}
.comment .item .f3 .t1{width:100%;border-radius:10rpx;padding:10rpx;font-size:22rpx;color:#888;background:#eee}
.dp-cover{height: auto; position: relative;}
.dp-cover-cover{position:fixed;z-index:99999;cursor:pointer;display:flex;align-items:center;justify-content:center;overflow:hidden;background-color: inherit;}
	
</style>