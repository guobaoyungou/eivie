<template>
<view>
	<block v-if="isload">
		<view class="container">
			<view class="topbg">
				<image :src="pre_url + '/static/img/collage_teambg.png'" class="image"/>
			</view>
			<view class="topbox" @tap="goto" :data-url="'product?id=' + product.id + '&teampid='+teamid">
				<view class="left">
					<image :src="product.pic"></image>
				</view>
				<view class="right">
					<view class="f1">{{product.name}}</view>
					<view class="f2"><view class="t1" v-if="!product.collage_type">{{product.teamnum}}人团</view></view>
					<view class="f3">
						<view class="t1">￥</view>
						<view class="t2">{{product.sell_price}}</view>
						<view class="t3">{{product.sales}}人已拼</view>
					</view>
				</view>
			</view>

			<view class="teambox">
				<view class="userlist">
					<view v-for="(item, index) in userlist" :key="index" class="item">
						<image :src="item.headimg?item.headimg:pre_url+'/static/img/wh.png'" class="f1"></image>
						<text class="f2" v-if="item.tuanzhang ==1">团长</text>
						<text v-if="item.nickname !=''">{{item.nickname}}</text>
						<view style="height: 40rpx;" v-else></view>
						
					</view>
					
				</view>
			</view>
		</view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
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
		userlist: [],
		product:[]
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
    var teamid   = this.opt.teamid  || 0;
		this.teamid  = teamid;
  },
  onShow: function (opt) {
  	this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
	onShareAppMessage:function(){
    var link = '/activity/collage/team?scene=id_'+this.product.id+'-pid_' + app.globalData.mid+'-teamid_'+this.team.id+'-tpid_1';
		return this._sharewx({title:'就差你了，快来一起拼团~ ' + this.product.name,pic:this.product.pic,link:link});
	},
	onShareTimeline:function(){
    var link = '/activity/collage/team?scene=id_'+this.product.id+'-pid_' + app.globalData.mid+'-teamid_'+this.team.id+'-tpid_1';
		var sharewxdata = this._sharewx({title:'就差你了，快来一起拼团~ ' + this.product.name,pic:this.product.pic,link:link});
		var query = (sharewxdata.path).split('?')[1]+'&seetype=circle';
		return {
			title: sharewxdata.title,
			imageUrl: sharewxdata.imageUrl,
			query: query
		}
	},
	methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiAdminCollage/team', {teamid: that.teamid}, function (res) {
				that.loading = false;
				if(res.status == 1) {
				  that.product  = res.product;
				  that.userlist = res.userlist;
				} else {
					 app.alert('您无查看权限');
				}
				 that.loaded();
			});	
		}
	}
};
</script>
<style>
.topbg{width:100%;height:248rpx;position:relative;z-index:0}
.topbg .image{width:100%;height:100%}
.topbox{width:94%;margin:0 3%;margin-top:-140rpx;background:#fff;border-radius:16rpx;padding:24rpx;display:flex;position:relative;z-index:1}
.topbox .left{flex-shrink:0;width:240rpx;height:240rpx;}
.topbox .left image{width:100%;height:100%}
.topbox .right{flex:1;padding-left:20rpx;padding-right:20rpx;display:flex;flex-direction:column}
.topbox .right .f1{color:#32201B;height:80rpx;line-height:40rpx;font-size:30rpx;font-weight:bold;overflow:hidden}
.topbox .right .f2{display:flex;margin-top:10rpx}
.topbox .right .f2 .t1{display:flex;background:rgba(255, 49, 67,0.2);border-radius:20rpx;padding:0 20rpx;height:40rpx;line-height:40rpx;color:#FF3143;font-size:24rpx;}
.topbox .right .f3{display:flex;align-items:center;color:#FF3143;margin-top:40rpx}
.topbox .right .f3 .t1{font-size:28rpx}
.topbox .right .f3 .t2{font-size:40rpx;font-weight:bold;flex:1}
.topbox .right .f3 .t3{font-size:26rpx;font-weight:bold;}

.teambox{width:94%;margin:0 3%;margin-top:20rpx;background:#fff;border-radius:16rpx;padding:24rpx;display:flex;flex-direction:column}

.userlist{width: 100%;background: #fff;text-align: center;padding-top:40rpx;margin-top:20rpx;}
.userlist .item{display: inline-block;width:180rpx;position: relative;text-align: center; overflow: hidden;text-overflow: ellipsis;}
.userlist .item .f1{width:100rpx; height:100rpx;border-radius: 50%;border: 1px #ffc32a solid;}
.userlist .item .f2{background: #ffab33;border-radius:100rpx;padding:4rpx 16rpx;border:1px #fff solid;position: absolute;top: 0px; left: -20rpx;color: #9f7200;font-size: 30rpx;}
.userlist .nickname{height: 30rpx;width: 180rpx;text-align: center;overflow: hidden;}
.join-text{color:#000;padding: 30rpx 0;font-size:36rpx;font-weight: 600;background: #fff; text-align: center;width: 100%;}

.join-btn{width: 90%;margin:20rpx 5%;background: linear-gradient(90deg, #FF3143 0%, #FE6748 100%);color: #fff;font-size: 30rpx;height:80rpx;border-radius:40rpx}
.join-btn2{width: 90%;margin:20rpx 5%;border: 2rpx solid #FF3143;color: #FF3143;font-size: 30rpx;height:80rpx;border-radius:40rpx}

.teambox .item1{width: 100%;padding:32rpx 20rpx;border-top: 1px #eaeaea solid;min-height: 112rpx;display:flex;align-items:center;}
.teambox .item1 image{width: 90rpx;height: 90rpx;border-radius:4px}
.teambox .item1 .f1{display:flex;flex:1;align-items:center;}
.teambox .item1 .f1 .t2{display:flex;flex-direction:column;padding-left:20rpx}
.teambox .item1 .f1 .t2 .x1{color: #333;font-size:26rpx;}

.teambox .item1 .f2{display:flex;flex-direction:column;width:200rpx;border-left:1px solid #eee;text-align: right;}
.teambox .item1 .f2 .t1{ font-size: 40rpx;color: #666;height: 40rpx;line-height: 40rpx;}
.teambox .item1 .f2 .t2{ font-size: 28rpx;color: #999;height: 50rpx;line-height: 50rpx;}
.teambox .item1 .f2 .t4{ display:flex;margin-top:10rpx;margin-left: 10rpx;color: #666; flex-wrap: wrap;font-size:18rpx;}
</style>