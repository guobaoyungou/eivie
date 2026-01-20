<template>
<view class="container">
	<block v-if="isload">
	<form @submit="formSubmit" autocomplete="off">
		
		<view class="content2">
			<block>
			<view class="item2"><view class="f1">新上级{{t('会员')}}ID</view></view>
			<view class="item3">
				<view class="member-info" v-if="member_info.id">
					<view class="info-view flex-y-center">
						<image class="head-img" :src="member_info.headimg" v-if='member_info.headimg'></image>
						<image class="head-img" :src="pre_url+'/static/img/wxtx.png'" v-else></image>
						<view class="member-text-view">
							<view class="member-nickname">{{member_info.nickname}}</view>
							<view class="member-id">ID：{{member_info.id}}</view>
						</view>
					</view>
					<view class="query-button" :style="{color:t('color1')}" @click="switchMember">切换</view>
				</view>
				<view class="member-info" v-else>
					<input class="input" type="number" name="mid" :value="mid" :placeholder="'请输入新上级'+t('会员')+'ID'" placeholder-style="color:#999;font-size:36rpx" @input="memberInput"></input>
					<view class="query-button" :style="{color:t('color1')}" @click="changeQuery(mid)">查询</view<>
				</view>
			</view>
			<view class="item4" style="height: 1rpx;"></view>
			</block>
	
			<view class="item5">
				<text style="margin-right:10rpx" class='redtxt'>您当前剩余修改次数：{{remain_num}}次 </text>
			</view>
			<view class="item5" v-if="reject_reason">
				<text style="margin-right:10rpx" class='redtxt'>拒绝原因：{{reject_reason}} </text>
			</view>
		</view>

		<button class="btn" :style="{background:t('color1')}" form-type="submit">提交</button>
	</form>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
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
			mid:'',
			member_info:{},
			pre_url:app.globalData.pre_url,
			remain_num:0,
			reject_reason:''
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		var that = this;
		this.getdata();
  },
  methods: {
		switchMember(){
			this.member_info = {};
			this.mid = '';
		},
		memberInput(event){
			this.mid = event.detail.value;
		},
		changeQuery(mid){
			let that = this;
			if(!mid) return app.error('请输入'+that.t('会员')+'ID');
			that.loading = true
			app.get('ApiMy/getMemberBase',{mid:that.mid},function (res) {
				that.loading = false
				if(res.status == 1){
					that.member_info = res.data;
				}else{
					app.error('未查询到此'+that.t('会员'));
				}
			});
		},
		getdata: function () {
			var that = this;
			that.loading = true
			app.get('ApiMemberEditParent/index', {}, function (res) {
				that.loading = false
				if(res.status == 1){
					that.remain_num = res.data;
					that.reject_reason = res.reject_reason || '';
				}else{
					app.alert(res.msg);
				}
				
				that.loaded();
			});
		},
	
    formSubmit: function (e) {
			var that = this;
			if(that.mid>0){
				var mid = that.mid;
			}else{
				var mid = typeof(mid) != 'undefined' ? parseInt(e.detail.value.mid) : e.detail.value.mid;
			}

			if (typeof(mid) != 'undefined' && (mid == '' || mid == 0 || isNaN(mid))) {
				app.error("请输入新上级ID");
				return false;
			}

			app.confirm('确定要修改吗？', function(){
				app.showLoading();
				app.post('ApiMemberEditParent/save', {pid:mid}, function (data) {
					app.showLoading(false);
					if (data.status == 0) {
						app.error(data.msg);
					}else {
						app.success(data.msg);
						setTimeout(function () {
						  that.getdata();
						}, 1000);
					}
				}, '提交中');
			})
    }
  }
};
</script>
<style>
.container{display:flex;flex-direction:column}
.content2{width:94%;margin:10rpx 3%;border-radius:10rpx;display:flex;flex-direction:column;background:#fff}

.content2 .item2{display:flex;width:100%;padding:0 30rpx;padding-top:10rpx}
.content2 .item2 .f1{height:80rpx;line-height:80rpx;color:#999999;font-size:28rpx}

.content2 .item3{display:flex;width:100%;padding:0 30rpx;padding-bottom:20rpx}
.content2 .item3 .f1{height:100rpx;line-height:100rpx;font-size:60rpx;color:#333333;font-weight:bold;margin-right:20rpx}
.content2 .item3 .f2{display:flex;align-items:center;font-size:36rpx;color:#333333;font-weight:bold;flex: 1;}
.content2 .item3 .f2 .input{font-size:36rpx;height:100rpx;line-height:100rpx;width: 100%;}
.content2 .item3 .member-info{display:flex;align-items:center;flex: 1;}
.content2 .item3 .member-info .input{font-size:36rpx;height:100rpx;line-height:100rpx;width: 100%;color:#333333;font-weight:bold;}
.content2 .item3 .member-info .query-button{white-space: nowrap;font-size: 28rpx;border-radius: 8rpx;padding: 5rpx 8rpx;}
.content2 .item3 .member-info .info-view{flex: 1;}
.content2 .item3 .member-info .info-view .head-img{width: 90rpx;height: 90rpx;border-radius: 8rpx;overflow: hidden;}
.content2 .item3 .member-info .info-view .member-text-view{height: 90rpx;padding-left: 20rpx;display: flex;flex-direction: column;align-items: flex-start;justify-content: flex-start;}
.content2 .item3 .member-info .info-view .member-text-view .member-nickname{font-size: 28rpx;color: #333;font-weight: bold;}
.content2 .item3 .member-info .info-view .member-text-view .member-id{font-size: 24rpx;color: #999999;margin-top: 10rpx;}
.content2 .item3 .member-info-oneline .info-view .member-text-view {flex-direction: row; align-items: center;}

.content2 .item4{display:flex;width:94%;margin:0 3%;border-top:1px solid #F0F0F0;height:100rpx;line-height:100rpx;color:#8C8C8C;font-size:28rpx}
.content2 .redtxt{color: #FC4343;}
.content2 .item5{display:flex;width:94%;margin:0 3%;height:100rpx;line-height:100rpx;color:#8C8C8C;font-size:28rpx}

.btn{ height:100rpx;line-height: 100rpx;width:90%;margin:0 auto;border-radius:50rpx;margin-top:30rpx;color: #fff;font-size: 30rpx;font-weight:bold;}

</style>