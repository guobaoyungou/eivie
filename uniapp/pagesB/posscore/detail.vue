<template>
<view class="container">
	<block v-if="isload">
			<view class="mymoney" :style="{background:t('color1')}">
				<view class="f1">设备数量</view>
				<view class="f2"><text style="font-size:26rpx"></text>{{possn_num}}</view>
				<view class="f1">奖励利率<text style="font-size:26rpx"></text>{{pos_score_ratio}}%</view>
				
			</view>
			<view class="content2">
<!-- 				<view class="item2" ><view class="f1">设备SN</view></view> -->
				<block>
					<view class="item3"><view class="f1">姓 名:</view><view class="f2"><input type="text" name="realname" @input="realnameinput" :value="realname"  placeholder="请输入姓名" placeholder-style="color:#999;font-size:30rpx"  style="font-size:30rpx"/></view></view>
				</block>
				<block>
					<view class="item3"><view class="f1">手机号:</view><view class="f2"><input type="text" name="phone" @input="phoneinput" :value="phone"  placeholder="请输入手机号" placeholder-style="color:#999;font-size:30rpx"  style="font-size:30rpx"/></view></view>
				</block>
				<block>
					<view class="item3"><view class="f1">设备SN:</view><view class="f2"><input type="text" name="possn" @input="possninput"  placeholder="请输入设备SN" placeholder-style="color:#999;font-size:30rpx"  style="font-size:30rpx"/></view></view>
				</block>
				<block v-if="desc">
					<view class="item3">
						<view class="f1">操作指引:</view>
						<view class="f2" style="width: 75%;">
						{{desc}}
						</view>
					</view>
				</block>
			</view>
			<view class="op">
				<view class="btn" @tap="tobind" :style="{background:t('color1')}">绑定</view>
			</view>
			<view class="content">
				<view class="content-title-view" :style="{color:t('color1')}">设备列表</view>
				<view v-for="(item, index) in possn_list" :key="index" class="item">
					<view class="f2">
							<text class="t1" :style="{color:t('color1')}">{{index+1}}</text>
					</view>
					<view class="f1">
							<text class="t1">{{item.possn}}</text>
							<text class="t2" v-if="item.status == 0">审核中</text>
							<text class="t2" v-if="item.status == 1">已通过</text>
							<text class="t2" v-if="item.status == 2">已驳回</text>
					</view>
				</view>
			</view>
			<nomore v-if="nomore"></nomore>
			<nodata v-if="nodata"></nodata>
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
		possn_num: '',
		possn_list: '',
		desc: "",
		textset: "",
		nodata:false,
		nomore: false,
		pos_score_ratio:'',
		realname:'',
		phone:''
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata:function(){
			var that = this;
			app.loading = true;
			app.get('ApiPosscore/possndes', {}, function (res) {
				app.loading = false;
				that.isload = true;
				that.textset = app.globalData.textset;
				uni.setNavigationBarTitle({
					title:  '设备列表'
				});
				that.possn_num = res.possn_num;
				that.possn_list = res.possn_list;
				that.desc = res.desc;
				that.realname = res.userinfo.realname;
				that.phone = res.userinfo.tel;
				that.pos_score_ratio = res.userinfo.pos_score_ratio;

				that.loaded();
			});
		},
		possninput: function (e) {
		  var possn = e.detail.value;
		    this.possn = possn;
		},realnameinput: function (e) {
		  var realname = e.detail.value;
		    this.realname = realname;
		},phoneinput: function (e) {
		  var phone = e.detail.value;
		    this.phone = phone;
		},
    tobind: function (e) {
      var that = this;
      var possn = that.possn;
      var realname = that.realname;
      var phone = that.phone;
	  if(!that.possn) return app.error('请输入设备SN');
	  if(!that.realname) return app.error('请输入姓名');
	  if(!that.phone || !app.isPhone(that.phone)) return app.error('请输入正确手机号');
			that.loading = true;
      app.post('ApiPosscore/possndes',{possn: possn,realname:realname,phone:phone},function (res) {
				that.loading = false;
			if (res.status == 0) {
			  app.error(res.msg);
			  return;
			}
			app.success(res.msg);
			that.getdata();
      });
    }
  }
};
</script>
<style>
.container{display:flex;flex-direction:column}
.mymoney{width:94%;margin:20rpx 3%;border-radius: 10rpx 56rpx 10rpx 10rpx;position:relative;display:flex;flex-direction:column;padding:70rpx 0}
.mymoney .f1{margin:0 0 0 60rpx;color:rgba(255,255,255,0.8);font-size:24rpx;}
.mymoney .f2{margin:20rpx 0 0 60rpx;color:#fff;font-size:64rpx;font-weight:bold}
.mymoney .f3{height:56rpx;padding:0 10rpx 0 20rpx;border-radius: 28rpx 0px 0px 28rpx;background:rgba(255,255,255,0.2);font-size:20rpx;font-weight:bold;color:#fff;display:flex;align-items:center;position:absolute;top:94rpx;right:0}
.content2{width:94%;margin:10rpx 3%;border-radius:10rpx;display:flex;flex-direction:column;background:#fff}
.content2 .item1{display:flex;width:100%;border-bottom:1px solid #F0F0F0;padding:0 30rpx}
.content2 .item1 .f1{flex:1;font-size:32rpx;color:#333333;font-weight:bold;height:120rpx;line-height:120rpx}
.content2 .item1 .f2{color:#FC4343;font-size:44rpx;font-weight:bold;height:120rpx;line-height:120rpx}

.content2 .item2{display:flex;width:100%;padding:0 30rpx}
.content2 .item2 .f1{height:60rpx;line-height:60rpx;color:#999999;font-size:28rpx}

.content2 .item3{display:flex;width:100%;padding:15rpx 0rpx;border-bottom:1px solid #F0F0F0;align-items: center;}
.content2 .item3 .f1{width:20%;font-size:26rpx;color:#333333;font-weight:bold;text-align: right;margin-right: 30rpx;white-space: nowrap;}
.content2 .item3 .f2{display:flex;align-items:center;font-size:30rpx;color:#333333;font-weight:bold}
.content2 .item3 .f2 input{height:60rpx;line-height:60rpx;}

.op{width:96%;margin:20rpx 2%;display:flex;align-items:center;margin-top:40rpx}
.op .btn{flex:1;height:80rpx;line-height:80rpx;background:#07C160;width:90%;margin:0 10rpx;border-radius:10rpx;color: #fff;font-size:28rpx;font-weight:bold;display:flex;align-items:center;justify-content:center}
.op .btn .img{width:48rpx;height:48rpx;margin-right:20rpx}


.content{width:94%;margin:40rpx 3%;border-radius:10rpx;display:flex;flex-direction:column;background:#fff}
.content .content-title-view{padding: 30rpx 20rpx 30rpx;font-size: 30rpx;font-weight: bold;}
.content .item{width:100%;padding:20rpx 20rpx;display:flex;align-items:center;border-top: 1px #F0F0F0 solid;justify-content: flex-start;}
.content .item .f1{flex:1;display:flex;flex-direction:column;margin-left: 30rpx;}
.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;padding-bottom: 10rpx;}
.content .item .f1 .t2{color:#666666}
.content .item .f1 .t3{color:#666666}
.content .item .f2{font-size:36rpx;padding: 0rpx 20rpx;}
.content .item .f2 .t1{color:#03bc01}
.content .item .f2 .t2{color:#000000}
.content .item .f3{ flex:1;width:200rpx;font-size:32rpx;text-align:right}
.content .item .f3 .t1{color:#03bc01}
.content .item .f3 .t2{color:#000000}
</style>