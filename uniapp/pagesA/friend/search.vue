<template>
<view class="container">
	<block v-if="isload">
		<view class="top">
			<view class="top-search">
				<image :src="pre_url + '/static/img/search_ico.png'" class="search-icon">
				<input class="input" type="text" @confirm="searchFriend" v-model="keyword" placeholder="输入昵称|姓名|手机号检索" placeholder-style="font-size:26rpx;color:#999">
			</view>
		</view>
		<view class="box">
			<view class="itembox" v-if="datalist.length>0">
				<block v-for="(item,index) in datalist">
					<view class="item flex-sb">
						<view class="left">
							<image class="headimg" :src="item.headimg" @tap="goto" :data-url="'scan?fmid='+item.fmid">
							<view class="info">
								<view class="nickname txthide">{{item.nickname}}</view>
								<view class="desc">等级：{{item.levelname}}</view>
								<view class="desc txthide">备注：{{item.remark}}</view>
							</view>
						</view>
						<view class="right">
							<button class="btn" v-if="btnauth.moneyTransfer" @tap="goto" :data-url="'/pagesExt/money/rechargeToMember?mid='+item.fmid">转余额</button>
							<button class="btn" v-if="btnauth.scoreTransfer" @tap="goto" :data-url="'/pagesExt/my/scoreTransfer?mid='+item.fmid">转积分</button>
							<!-- <button class="btn btn1" :style="'background:rgba('+t('color1rgb')+',0.6);color:#FFF'">转换分组</button> -->
							<button class="btn" @tap.stop="showChangeGroupModal" :data-name="item.groupname" :data-mid="item.fmid">{{item.groupname?'转换':'加入'}}分组</button>
							<button class="btn" v-if="btnauth.moneyTransfer || btnauth.scoreTransfer" @tap="goto" :data-url="'transferlog?fmid='+item.fmid">转账明细</button>
						</view>
					</view>
				</block>
			</view>
			<view v-if="datalist.length==0" class="nomore">-暂无匹配好友-</view>
		</view>
		<!-- 换分组start -->
		<view v-if="isshowChangeGroupModal" class="popup__container group_modal popup_store">
			<view class="popup__overlay" @tap.stop="hideChangeGroupModal"></view>
			<view class="popup__modal" style="">
				<view class="popup__title">
					<text class="popup__title-text">转换分组</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="hideChangeGroupModal"/>
				</view>
				<view class="popup__content">
					<view class="form-item" v-if="oldGroupName!=''">
						<view class="form-label">现在分组</view>
						<view class="form-value form-txt">
							{{oldGroupName}}
						</view>
					</view>
					<view class="form-item">
						<view class="form-label">好友分组</view>
						<view class="form-value ">
								<picker class="picker" name="store_id" @change="pickerChange" :range="groupList" range-key="name">
									<view class="pickerbox">
										<view class="uni-input" :class="newGroupId>0?'':'hui'">{{newGroupName?newGroupName:'请选择分组'}}</view>	
										<image :src="pre_url+'/static/img/arrowright.png'" class="img"/>
									</view>
								</picker>
						</view>
					</view>
					<view class="bottom">
						<view class="btn cancel" @tap.stop="hideChangeGroupModal">取消</view>
						<view class="btn ok" @tap="changeGroup">确定</view>
					</view>
				</view>
			</view>
		</view>
		<!-- 换分组End -->
	</block>
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
			pre_url:app.globalData.pre_url,
      isload: false,
      groupList: [],
      datalist: [],
      pagenum: 1,
      nomore: false,
      nodata: false,
			keyword:'',
			btnauth:{},
			isshowChangeGroupModal:false,
			groupname:'',
			oldGroupName:'',
			newGroupId:0,
			newGroupName:''
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.keyword = this.opt.keyword
		this.getdata();
		this.getlist()
  },
	onPullDownRefresh: function () {
		this.getdata();
		this.getlist()
	},
  methods: {
		getdata:function(){
			var that = this;0
			that.loading = true
			app.get('ApiFriend/searchFriend', {}, function (res) {
				that.loading = false;
				that.groupList = res.groupList
				that.btnauth = res.btnauth
				that.loaded()
			})
		},
    getlist: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var pagenum = that.pagenum;
			// that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.get('ApiFriend/getlist', {pagenum: pagenum,keyword:that.keyword}, function (res) {
				that.loading = false;
				var data = res.datalist;
				if (pagenum == 1) {
					that.datalist = data;
				}else{
				  if (data.length >0) {
				    var datalist = that.datalist;
				    var newdata = datalist.concat(data);
				    that.datalist = newdata;
				  }
				}
      })
    },
		showChangeGroupModal:function(e){
			this.isshowChangeGroupModal = true
			this.oldGroupName = e.currentTarget.dataset.name
			this.changeMid = e.currentTarget.dataset.mid
		},
		hideChangeGroupModal:function(){
			this.isshowChangeGroupModal = false
		},
		pickerChange:function(e){
			var that = this;
			var index = e.detail.value;
			var group = that.groupList[index]
			that.newGroupId = group.id
			that.newGroupName = group.name
		},
		changeGroupShow:function(e){
			var that = this;
			var index = e.currentTarget.dataset.index;
			var groupList = that.groupList;
			var is_show = groupList[index].is_show;
			that.groupList[index].is_show = !is_show;
		},
		changeGroup:function(){
			var that = this;
			if(that.newGroupId==0){
				app.error('请选择好友分组');return;
			}
			app.showLoading('提交中');
			app.post('ApiFriend/changeGroup', {fmid:that.changeMid,group_id:that.newGroupId}, function (res) {
				app.showLoading(false);
				if(res.status==1){
					that.isshowChangeGroupModal = false;
					that.newGroupId = 0;
					that.newGroupName = '';
					app.success(res.msg);
					setTimeout(function () {
					  that.getdata()
					}, 1000);
				}else{
					app.error(res.msg);
				}
			})
		},
		searchFriend:function(){
			this.getlist();
		}
		
  }
};
</script>
<style>
.container{ width:100%;}
.flex-sb{display: flex;justify-content: space-between;align-items: center;}
.flex-s{display: flex;align-items: center;}
.top{width: 100%;padding:20rpx 26rpx;z-index: 9999;background: #FFFFFF;}
.top-search{display: flex;align-items: center; background: #F6F6F6;padding: 12rpx 20rpx;border-radius: 50rpx;}
.search-icon{width: 30rpx;height: 30rpx;margin-right: 10rpx;}
.box{background: #FFFFFF;margin-top: 10rpx;padding: 20rpx;}
.box .title{border-bottom: 1rpx solid #f0f0f0;padding-bottom: 20rpx;}
.bold{font-weight: 600;}
.nomore{text-align: center;padding-top:20rpx;color: #999;font-size: 24rpx;}

.item{border-bottom: 1rpx solid #f0f0f0;padding: 20rpx 0;}
.item:last-child{border: 0;padding-bottom: 0;}
.item .left{display: flex;flex:1;flex-shrink: 0;}
.left .info{padding: 0 20rpx;flex:1;max-width: 340rpx;}
.left .headimg{width: 110rpx; height: 110rpx;border-radius: 16rpx;}
.left .nickname{font-size: 30rpx;font-weight: 600;}
.left .desc{color: #999;font-size: 24rpx;line-height: 36rpx;}
.txthide{max-width:100%;text-overflow: ellipsis;overflow: hidden;white-space: nowrap;}

.item .right{display: flex;justify-content: flex-end;flex-wrap: wrap;width: 260rpx;align-items: center;font-size: 24rpx;flex-shrink: 0;}
.right .btn{border: 1rpx solid #e5e5e5; text-align: center;color: #555;width: 120rpx;margin: 4rpx 0 4rpx 6rpx;border-radius: 10rpx;height: 60rpx;line-height: 60rpx;font-size: 24rpx;}
.right .btn1{border: none}
.group-item{margin-bottom: 20rpx;}
.group-item .down{height: 30rpx;width: 30rpx;}
.group-item .tag{background: #f3f3f3;border-bottom: 1rpx solid #f0f0f0;padding: 20rpx 10rpx;}
.group-list{/* padding: 0 10rpx; */}
.group-list .item .nickname{font-weight: normal;font-size: 28rpx;}
.group-list .item .headimg{width: 100rpx;height: 100rpx;border-radius: 6rpx;}
.group-list .item .left .desc{line-height: 30rpx;}

.add_modal{width: 90%;top: 50%;left: 5%;bottom: unset;}
.add_modal .popup__modal{border-radius:20rpx;min-height: 300rpx;}
.add_modal .popup__content{padding: 20rpx;}
.add_method{display: flex;align-items: center;font-size: 30rpx;padding: 20rpx 30rpx;    background: #f7f7f7; margin: 20rpx;}
.add_method:first-child{margin-top: 0;}
.add_method image{width: 40rpx;height: 40rpx;margin-right: 20rpx;}

.group_modal{width: 90%;top: 50%;left: 5%;bottom: unset;}
.group_modal .popup__modal{border-radius:20rpx;min-height: 300rpx;}
.group_modal .popup__content{padding:30rpx;}
.group_modal .bottom{display: flex;justify-content: center;align-items: center;margin-top: 60rpx;}
.group_modal .bottom .btn{min-width: 160rpx;border-radius: 16rpx;color: #FFFFFF;background: #344D86;margin-left: 30rpx;line-height: 60rpx;text-align: center;}
.group_modal .bottom .btn.cancel{background: #e2a206;}
.group_modal .form-item{display: flex;align-items: center;}
.group_modal .form-item .form-value{background:#f2f2f2;border-radius: 6rpx;flex:1}
.group_modal .form-item .form-value.form-txt{background: none;padding-left: 20rpx;}
.group_modal .form-item .form-label{color: #999;font-size: 28rpx;flex-shrink: 0;padding: 0 10rpx;}
.group_modal .form-item input{height: 80rpx;line-height: 80rpx;padding: 0 20rpx;}

.popup_store .pickerbox{display: flex;justify-content: space-between;align-items: center;flex: 1;height: 80rpx;line-height: 80rpx;padding: 0 20rpx;}
.popup_store .pickerbox .picker{width: 100%;font-size: 28rpx;text-align: right;}
.popup_store .pickerbox .picker .hui{color: #BCC1CE;font-size: 30rpx;}
.popup_store .pickerbox image{width: 30rpx;height: 30rpx;}
.popup_store .pickerbox{width: 100%;}
.popup_store .pickerbox .picker{flex-shrink: 0;height: 70rpx;line-height:70rpx;overflow: hidden;width: 90%;}
.popup_store .form-item{border-bottom: 1rpx solid #f0f0f0;padding: 30rpx 0;}

.qrcode_box{display: flex;flex-direction: column;align-items: center;}
.qrcode_box image{width: 400rpx; height: 400rpx;}
.qrcode_box .tips{color: #999;font-size: 24rpx;}
.qrcode_modal{top: 63%;}
.qrcode_modal .popup__content{padding-top: 0;}
</style>