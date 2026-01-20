<template>
<block v-if="isload">
<view class="container" :style="{backgroundColor:t('color1')}" >
	<view class="contentbox">
			<image :src="pre_url + '/static/imgsrc/rank_notext.png'" mode="widthFix">
			<view class="title">
				<view class="t1">{{sysset.fenhong_rank_title||'分红排行榜'}}</view>
				<view class="t2">{{sysset.fenhong_rank_desc||'历史分红累积榜单'}}</view>
			</view>
			<view class="content">
				<view class="top">
					<view class="t1">我的：{{totalcommission}}</view>
					<!-- <view class="border"></view> -->
<!-- 					<view class="t1">累计佣金：{{totalcommission}}</view>
 -->				</view>
				
				<!-- <view class="tab1">
					<block  >
						<view :class="'t1 '+(item==ranktype?'on':'')" :style="{color:(item==ranktype?t('color1'):'')}" :data-ranktype = "item" @tap="toranktype">累计佣金		
						<view class="before" v-if="item==ranktype" :style="'border-bottom:1rpx solid '+t('color1')"></view>
						
						</view>
					</block>
				</view> -->
				
				<view class="tab">
					<view class="t1">排名</view>
					<view class="t2">姓名</view>
					<view class="t3" :style="{color:t('color1')}">累计</view>
				</view>
			
				<view class="itembox">	
					<block v-for="(item, index) in datalist" :key="index" >
					<view class="item">
							<view class="t1" v-if="index<3"><image :src="pre_url+ '/static/img/comrank'+index+'.png'"></view>
							<view class="t1" v-else>{{index+1}}</view>
							<view class="t2"><image :src="item.headimg">{{item.nickname}}</view>
							<text class="t3"> {{item.sumcommission}}</text>
					</view>
					</block>
				</view>
			</view>
	</view>

	<nodata v-if="nodata"></nodata>
	<nomore v-if="nomore"></nomore>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
</view>
</block>
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
      nodata: false,
      nomore: false,
      datalist: [],
			textset:{},
      pagenum: 1,
			totalcommission:0,
			commission:0,
			rank_type:[],
			sysset:[],
			self_title:"",
			total_title:""
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		var that = this;
		this.getdata();
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
    getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var pagenum = that.pagenum;
			that.loading = true;
			that.nodata = false;
      that.nomore = false;
			
      app.post('ApiAgent/fenhongrank', {pagenum: pagenum}, function (res) {
				that.loading = false;
        var data = res.data.data;
				that.totalcommission = res.data.totalcommission
				// that.commission = res.data.commission
				// that.rank_type = res.data.rank_type
				that.sysset = res.data.sysset
				if(res.data.status==0){
						app.alert('排行榜未开启')
				}
				uni.setNavigationBarTitle({
					title:that.sysset.fenhong_rank_title
				})
        if (pagenum == 1) {
					that.textset = app.globalData.textset;
          that.datalist = data;
          if (data.length == 0) {
            that.nodata = true;
          }
					that.loaded();
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
		toranktype:function (e) {
			var that=this
			var ranktype = e.currentTarget.dataset.ranktype;
			that.ranktype = ranktype
			that.getdata()
		}
  }
};
</script>
<style>
.container{ padding: 20rpx; background: #FC3B36;}
.contentbox{ border-radius: 20rpx; width: 100%;}
.contentbox image{ border-top-left-radius: 10rpx; border-top-right-radius: 10rpx; width: 100%; border:none; display: block;}

.content{ background: #fff; display: flex; align-items: center; flex-direction: column; }
.content .top{ background: #F4F5F9; width: 90%;  margin-top: 20rpx; border-radius: 10rpx; display: flex; height: 70rpx; line-height: 70rpx; padding-left: 20rpx; display: flex; align-items: center; }
.content .top .border{ margin-right: 10rpx; height: 30rpx; border-right: 1rpx solid #999; margin: 0 30rpx; }
.content .tab{ display: flex; width: 90%; text-align: left;  line-height: 70rpx; margin-top: 20rpx; color: #666;}
.content .tab .t1{ width: 25%;}
.content .tab .t2{ width: 50%;padding-left: 20rpx;}
.content .tab .t3{ width: 30%;}

.content .tab1{ display: flex; border-bottom: 1rpx solid #dedede; width: 90%; height: 100rpx; line-height: 100rpx;}
.content .tab1 .t1{ text-align: center;margin: 0 30rpx; }
.content .tab1 .t1.on{ color:red;}


.content .itembox{width:96%;}
.content .item{width:100%; display:flex;padding:40rpx 20rpx;border-radius:8px;margin-top: 6rpx;align-items:center;}
.itembox .item:first-child{  background-image: linear-gradient(to right , #FFF3E5, #FFFFFC)}
.itembox .item:nth-child(2){ background-image: linear-gradient(to right , #DDECFF, #FFFFFC)}
.itembox .item:nth-child(3){background-image: linear-gradient(to right , #FFE1DE, #FFFFFC)}



.content .item image{ width: 80rpx; height: 80rpx; border-radius: 50%; margin-right: 20rpx; }
.content .item .t1{color:#000000;font-size:30rpx;width: 20%; }
.content .item .t2{color:#666666;font-size:24rpx; width: 60%; display: flex; align-items: center;}
.content .item .t3{ width: 20%; font-weight: bold;}


.title{position: absolute;left: 8%;top: 6%;}
.title .t1{font-size: 48rpx;font-weight: 700;line-height: 80rpx;color: #9C6238;}
.title .t2{font-size: 32rpx;line-height: 60rpx;color: #9C6238;}
.data-empty{background:#fff}
</style>