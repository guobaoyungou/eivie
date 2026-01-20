<template>
	<view class="body">
		<block v-if="isload">
			<view class="header" :style="{background:'linear-gradient(180deg,'+themeColor+' 0%,rgba('+t('color1rgb')+',0) 100%)'}">
				{{detail.name}}
			</view>
			<view class="container">
				<view class="content box" v-if="detail.content">
					<rich-text :nodes="detail.content"></rich-text>
				</view>
				
					<form @submit="formSubmit">
						<view class="form-main box">
							<view class="form-item">
								<view class="form-label"><text class="required">*</text>姓名</view>
								<view class="form-value">
									<input type="text" name="name" :value="form.name" placeholder="请填写姓名"
										placeholder-class="placeholder">
								</view>
							</view>
							<view class="form-item">
								<view class="form-label"><text class="required">*</text>性别</view>
								<view class="form-value form-radio">
									<radio-group name="sex">
										<label>
											<radio value="1" style="transform: scale(0.8);" :checked="form.sex==1?true:false"></radio>男
										</label>
										<label>
											<radio value="2" style="transform: scale(0.8);" :checked="form.sex==2?true:false"></radio>女
										</label>
									</radio-group>
								</view>
							</view>
							<view class="form-item">
								<view class="form-label"><text class="required">*</text>年龄</view>
								<view class="form-value flex-sb">
									<input type="digit" name="age" :value="form.age" placeholder="请填写年龄"
										placeholder-class="placeholder">
									<view class="form-unit">岁</view>
								</view>
							</view>
							<view class="form-item">
								<view class="form-label"><text class="required">*</text>联系方式</view>
								<view class="form-value">
									<input type="text" name="tel" :value="form.tel" placeholder="请填写联系方式"
										placeholder-class="placeholder">
								</view>
							</view>
							<view class="form-item">
								<view class="form-label"><text class="required">*</text>门店</view>
								<view class="form-value form-select" @tap="showMendianModal">
									<view class="select-txt" :class="bid_index>-1?'':'placeholder'">{{bid_index>-1?bidlist[bid_index].name:'请选择门店'}}</view>
									<view class="select-more"><text class="iconfont iconjiantou" style="font-size: 24rpx;color: #BBBBBB;"></text></view>
								</view>
							</view>
							<view class="form-item">
								<view class="form-label">家庭地址</view>
								<view class="form-value">
									<input type="text" name="address" :value="form.address" placeholder="请填写家庭地址"
										placeholder-class="placeholder">
								</view>
							</view>
						</view>
						<view style="height: 120rpx;"></view>
						<view class="form-opt">
							<button form-type="submit" class="btn" :style="{background:themeColor}">开始评测</button>
							<block>
									<view  class="share" @tap="shareapp" v-if="getplatform() == 'app'">
										<image :src="pre_url+'/static/img/share.png'">
										<text>分享</text>
									</view>
									<view  class="share" @tap="sharemp" v-else-if="getplatform() == 'mp'">
										<image :src="pre_url+'/static/img/share.png'">
										<text>分享</text>
									</view>
									<view  class="share" @tap="sharemp" v-else-if="getplatform() == 'h5'">
										<image :src="pre_url+'/static/img/share.png'">
										<text>分享</text>
									</view>
									<button  class="share" open-type="share" v-else>
										<image :src="pre_url+'/static/img/share.png'">
										<text class="txt">分享</text>
									</button>
							</block>
						</view>
					</form>
			</view>
			<!-- 门店选择start -->
			<view v-if="isshowmendianmodal" class="modal-mendian">
				<view class="modal-content">
					<view class="popup__container popup_mendian" v-if="isshowmendianmodal" style="z-index: 999999;">
						<view class="popup__overlay" @tap.stop="hideMendianModal"></view>
						<view class="popup__modal">
							<view class="popup__title">
								<text class="popup__title-text">请选择门店</text>
								<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx"
									@tap.stop="hideMendianModal" />
							</view>
							<view class="popup__content">
								<block v-for="(item,index) in bidlist" :key="index">
									<view class="mendian-info" @tap="changeMendian" :data-index="index"
										:data-id="item.id"
										:style="{background:(index==bid_index?'rgba('+t('color1rgb')+',0.1)':'')}">
										<view class="b1">
											<image :src="item.logo"></image>
										</view>
										<view class="b2">
											<view class="t1">{{item.name}}</view>
											<view class="t2 flex-y-center">
												<view class="mendian-distance" v-if="item.distance">{{item.distance}}</view>
												<block v-if="item.address || item.area">
													<view class="line" v-if="item.distance"> </view>
													<view class="mendian-address">
														{{item.address?item.address:item.area}}</view>
												</block>
											</view>
										</view>
									</view>
								</block>
							</view>
						</view>
					</view>
				</view>
			</view>
			<!-- 门店选择end -->
		</block>
		<loading v-if="loading"></loading>
		<popmsg ref="popmsg"></popmsg>
		<wxxieyi></wxxieyi>
	</view>
</template>
<script>
	var app = getApp();

	export default {
		data() {
			return {
				opt: {},
				loading: false,
				isload: false,
				indexurl: app.globalData.indexurl,
				current: 0,
				pagecontent: "",
				id: 0,
				detail: {},
				form: {},
				isshowmendianmodal:false,
				bidlist:[],
				bid_index:-1,
				latitude:'',
				longitude:'',
				canSubmit:true,
				custom:{},
				themeColor:'',
				pre_url: app.globalData.pre_url
			};
		},
		onLoad: function(opt) {
			var that = this;
			var opt = app.getopts(opt);
			that.opt = opt;
			that.id = that.opt.id || 0
			that.getdata();
		},
		onPullDownRefresh: function() {
			this.getdata();
		},
		methods: {
			getdata: function() {
				var that = this;
				var id = that.opt.id;
				that.loading = true;
				app.get('ApiHealth/main', {
					id: id,
					latitude:that.latitude,
					longitude:that.longitude
				}, function(res) {
					that.loading = false;
					if (res.status == 1) {
						that.detail = res.data
						that.bidlist = res.bidlist
						that.form = res.form
						that.custom = res.custom
						if(that.custom.PSQI){
							that.themeColor = '#229989'
						}else{
							that.themeColor = that.t('color1');
						}
						if(that.form.bid>0){
							for(let i in that.bidlist){
								if(that.bidlist[i].id==that.form.bid){
									that.bid_index = i;
									break;
								}
							}
						}
						uni.setNavigationBarTitle({
							title: res.data.name
						});
						that.loaded({
							title: res.data.name
						});
						if(!that.latitude){
							app.getLocation(function(res1){
								that.longitude = res1.longitude;
								that.latitude = res1.latitude;
								that.getdata()
							})
						}
					} else {
						app.alert(res.msg,function(){
							app.goto('/pages/index/index');
						});
					}
				});
			},
			showMendianModal:function(){
				var that = this;
				that.isshowmendianmodal = true
			},
			hideMendianModal:function(){
				this.isshowmendianmodal = false
			},
			changeMendian:function(e){
				var that = this;
				// var mendianid = e.currentTarget.dataset.id;
				var index = e.currentTarget.dataset.index;
				that.bid_index = index
				that.isshowmendianmodal = false
			},
			formSubmit:function(e){
				var that = this;
				if(!that.canSubmit){
					return;
				}
				var formdata = e.detail.value;
				if (formdata.name == '') {
				  app.error('请填写姓名');return;
				}
				if (formdata.sex == '') {
				  app.error('请选择性别');return;
				}
				if (formdata.age == '') {
				  app.error('请填写年龄');return;
				}
				if (formdata.tel == '') {
				  app.error('请填写联系方式');return;
				}
				if (formdata.tel == '') {
				  app.error('请填写联系方式');return;
				}
				if (!app.isPhone(formdata.tel)) {
				  app.error("手机号码有误");
				  return false;
				}
				if(that.bid_index<0){
					app.error("请选择门店");
					return false;
				}
				formdata['ha_id'] = that.id
				formdata['bid'] = that.bidlist[that.bid_index].id
				// app.showLoading('提交中');
				that.canSubmit = false
				app.post("ApiHealth/saveFormInfo", formdata, function (data) {
					// app.showLoading(false);
				  if (data.status == 1) {
				    // app.success(data.msg);
						app.goto('question?fid='+data.fid);
				  } else {
						that.canSubmit = true
				    app.error(data.msg);
				  }
				});
			},
			sharewx:function(){
				app.error('点击右上角发送给好友或分享到朋友圈');
			},
			sharemp:function(){
				app.error('点击右上角发送给好友或分享到朋友圈');
			},
			shareapp:function(){
				var that = this;
				uni.showActionSheet({
			    itemList: ['发送给微信好友', '分享到微信朋友圈'],
			    success: function (res){
						if(res.tapIndex >= 0){
							var scene = 'WXSceneSession';
							if (res.tapIndex == 1) {
								scene = 'WXSenceTimeline';
							}
							var sharedata = {};
							sharedata.provider = 'weixin';
							sharedata.type = 0;
							sharedata.scene = scene;
							sharedata.title = that.health.name;
							sharedata.summary = that.health.desc;
							sharedata.href = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesA/health/main?scene=id_'+that.health.id+'-pid_' + app.globalData.mid;
							sharedata.imageUrl = that.health.pic;
							var sharelist = app.globalData.initdata.sharelist;
							if(sharelist){
								for(var i=0;i<sharelist.length;i++){
									if(sharelist[i]['indexurl'] == '/pagesA/health/main'){
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
			  })
			}
		}
	}
</script>
<style>
	page {
		position: relative;
		width: 100%;
		height: 100%;
	}

	.flex-sb {
		display: flex;
		justify-content: space-between;
		align-items: center;
	}

	.header {
		height: 260rpx;
		position: absolute;
		top: 0;
		width: 100%;
		padding-top: 70rpx;
		text-align: center;
		font-weight: bold;
		font-size: 32rpx;
	}

	.container {
		position: absolute;
		width: 100%;
		top: 160rpx;
		border-radius: 16rpx;
	}
	.box{background: #FFFFFF;border-radius: 24rpx;padding: 30rpx;margin-bottom: 20rpx;width: 92%;margin: 0 4% 20rpx 4%;}

	.content {
		line-height: 40rpx;
		font-size: 24rpx;
	}

	.placeholder {
		font-size: 26rpx;
		color: #BBBBBB;
	}
	.form-item {
		display: flex;
		align-items: center;
		margin-bottom: 20rpx;
	}

	.form-label {
		flex-shrink: 0;
		width: 68px;
		text-align: right;
		margin-right: 20rpx;
	}

	.form-value {
		flex: 1;
		border: 1rpx solid #EDEDED;
		padding: 0 10rpx;
		height: 60rpx;
		line-height: 60rpx;
		max-width: 100%;
		overflow: hidden;
	}

	.form-radio {
		border: none;
	}

	.form-radio label {
		margin-right: 20rpx;
	}
	.form-select{display: flex;justify-content: space-between;align-items: center;}
	.form-value input {
		font-size: 28rpx;
		border-radius: 8rpx;
		height: 100%;
		line-height: 100%;
	}

	.form-label .required {
		color: #ff2400;
	}

	.form-unit {
		color: #999;
	}

	.form-opt {position: fixed;bottom: 0;width: 92%;left:4%;background: #F6F6F6;height: 120rpx;display: flex;align-items: center;justify-content: space-between;font-size: 24rpx;color: #333;z-index: 1000;}
	.btn {
		border: 1rpx solid #EDEDED;
		text-align: center;
		border-radius: 16rpx;
		color: #FFFFFF;
		background: #009688;
		flex: 1;
		margin: 0 20rpx;
		height: 76rpx;
		line-height: 76rpx;
	}
	.select-more{flex-shrink: 0;}
	.select-txt{white-space: nowrap;overflow: hidden;text-overflow: ellipsis;}
	.share{display: flex;flex-direction: column;align-items: center;width: 130rpx;}
	.share .txt{height: 36rpx;line-height: 36rpx;font-size: 26rpx;color: #333;}
	.share image{width: 36rpx;height: 34rpx;}


	.modal-mendian {
		background: rgba(0, 0, 0, 0.5);
		position: fixed;
		height: 100%;
		width: 100%;
		top: 0;
		left: 0;
		align-items: flex-end;
		margin: 0;
		z-index: 1900000;
	}

	.modal-content {
		width: 100%;
		height: 100%;
		overflow-y: auto;
	}


	/* 门店 */
	.popup_mendian .popup__content {
		padding: 0 20rpx;
	}

	.popup_mendian .popup__modal {
		min-height: auto;
	}

	.popup_mendian .mendian-info {
		display: flex;
		align-items: center;
		width: 100%;
		background: #F6F6F6;
		padding: 20rpx;
		margin-bottom: 20rpx;
		border-radius: 6rpx;
	}

	.popup_mendian .mendian-info .b1 {
		background-color: #fbfbfb;
	}

	.popup_mendian .mendian-info .b1 image {
		height: 100rpx;
		width: 100rpx;
		border-radius: 6rpx;
		border: 1px solid #e8e8e8;
	}

	.popup_mendian .mendian-info .b2 {
		flex: 1;
		line-height: 38rpx;
		margin-left: 20rpx;
		overflow: hidden;
	}

	.popup_mendian .mendian-info .b2 .t1 {
		padding-bottom: 10rpx;
	}

	.popup_mendian .mendian-info .b2 .t2 {
		font-size: 24rpx;
		color: #999;
	}

	.popup_mendian .mendian-info .b3 {
		display: flex;
		justify-content: flex-end;
		flex-shrink: 0;
		padding-left: 20rpx;
	}

	.popup_mendian .mendian-info .b3 image {
		width: 40rpx;
		height: 40rpx;
	}

	.popup_mendian .mendian-info .tag {
		padding: 0 10rpx;
		margin-right: 10rpx;
		display: inline-block;
		font-size: 22rpx;
		border-radius: 8rpx;
		flex-shrink: 0;
	}

	.popup_mendian .mendian-info .mendian-address {
		text-overflow: ellipsis;
		flex: 1;
		width: 300rpx;
		white-space: nowrap;
	}

	.popup_mendian .mendian-info .line {
		border-right: 1rpx solid #999;
		width: 10rpx;
		flex-shrink: 0;
		height: 16rpx;
		padding-left: 10rpx;
		margin-right: 12rpx;
	}

	.popup_mendian .mendian-info .mendian-distance {
		color: #3b3b3b;
		font-weight: 600;
		flex-shrink: 0;
	}
</style>
