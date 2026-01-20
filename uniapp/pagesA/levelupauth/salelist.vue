<template>
<view class="container">
	<block v-if="isload">

		<view class="box">
			<view class="title flex-sb">
				<view class="bold">剩余额度({{salelevel_money}})</view>
			</view>
			<view class="itembox" v-if="levellist.length>0">
				<block v-for="(item,index) in levellist">
					<view class="item flex-sb"  >
						<view class="left" @tap='goto' :data-url="'leveldetail?id='+item.id">
							<image class="headimg" :src="item.icon">
							<view class="info">	
								<view class="desc">{{item.name}}</view>
								<view class="remark txthide">升级费用：{{item.apply_paymoney}}</view>
							</view>
						</view>
						<view class="right">
							<button class="btn" @tap.stop="zhaunzeng" :data-levelid="item.id" :data-levelprice="item.apply_paymoney" v-if="getplatform() == 'h5' || getplatform() == 'mp' || getplatform() == 'app'">
								转赠
							</button>
							<button class="btn" v-else open-type="share" data-type='1' :data-levelid="item.id" :data-levelprice="item.apply_paymoney">转赠</button>
							<button class="btn" @tap.stop="tosale" :data-levelid="item.id" :data-levelprice="item.apply_paymoney" v-if="getplatform() == 'h5' || getplatform() == 'mp' || getplatform() == 'app'">
								售卖
							</button>
							<button class="btn" open-type="share" data-type='2' :data-levelid="item.id" :data-levelprice="item.apply_paymoney" v-else>
								售卖
							</button>
						</view>
					</view>
				</block>
			</view>
			<view v-if="levellist.length==0" class="nomore">-暂无可售卖会员等级-</view>
		</view>
		<view v-if="dialogShow" class="popup_modal" @tap="zhaunzeng">
			<view class="content1">
				<view class="title">我要转赠</view>
				
				<view class="f1">点击下方按钮，复制链接发送给好友</view>
				
				<view class="btnbox" @tap="shareScheme" v-if="getplatform() == 'wx'">
						<button class="btn2" :style="{background:t('color1')}"  >复制</button>
				</view>
				<view class="btnbox" v-else>
					<button class="btn2" :style="{background:t('color1')}" @tap="copy" :data-text="pre_url+'/h5/'+aid+'.html#/pagesA/levelupauth/give?pid='+frommid+'&levelid='+levelid" >复制</button>
				</view>
			</view>
		</view>
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
			platform:app.globalData.platform,
      isload: false,
      levellist: [],
      pagenum: 1,
      nomore: false,
      nodata: false,
			salelevel_money:0,
			frommid:0,
			dialogShow:false,
			levelid:0,
			aid:1,
			sharepic:''
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.sharepic = this.pre_url+'/static/img/levelupauth.jpg';
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
	onShareAppMessage: function (e) {
		var that=this
		var type =   e.target.dataset.type
		var levelid =   e.target.dataset.levelid
		var levelprice =  e.target.dataset.levelprice
		if(that.salelevel_money<parseFloat(levelprice)){
			app.alert('额度不足');return
		}
		var title = '您有一份会员等级待领取！';
		var sharepic  = that.sharepic;
		if(type==1){
				var sharelink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesA/levelupauth/give?scene=levelid_'+levelid+'-pid_'+that.frommid;
		}		
		if(type==2){
				var sharelink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesA/levelupauth/buy?scene=levelid_'+levelid+'-pid_'+that.frommid;
		}
		
		console.log({title:title,tolink:sharelink,pic:sharepic})
		var sharedata = this._sharewx({title:title,tolink:sharelink,pic:sharepic});
		return sharedata;
	},
	
	onShareTimeline:function(){
		var that=this
		var type =   e.target.dataset.type
		var levelid =   e.target.dataset.levelid
		var levelprice =  e.target.dataset.levelprice
		if(that.salelevel_money<parseFloat(levelprice)){
			app.alert('额度不足');return
		}
		var title = '您有一份会员等级待领取！';
		var sharepic = that.sharepic;
		if(type==1){
				var sharelink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesA/levelupauth/give?scene=id_'+levelid+'-pid_'+that.frommid;
		}else{
				var sharelink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesA/levelupauth/buy?scene=id_'+levelid+'-pid_'+that.frommid;
		}
	
		var sharewxdata = this._sharewx({title:title,tolink:sharelink,pic:sharepic});
		var query = (sharewxdata.path).split('?')[1]+'&seetype=circle';
		var link = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#'+(sharewxdata.path).split('?')[0]+'&seetype=circle';
	},
  methods: {
		getdata:function(){
			var that = this;0
			that.loading = true
			app.get('ApiLevelupAuth/salelist', {}, function (res) {
				that.loading = false;
				if(res.status==1){
					that.levellist = res.levellist
					that.salelevel_money = res.salelevel_money
					that.frommid = res.pid
					that.aid = app.globalData.aid
					that.loaded()	
				}else{
						app.alert('没有售卖权限');return
				}
			})
		},
		shareScheme: function (e) {
			var that = this;
			var levelid =  e.currentTarget.dataset.levelid
			app.showLoading();
			app.post('ApiLevelupAuth/getwxScheme', {levelid: levelid}, function (data) {
				app.showLoading(false);
				if (data.status == 0) {
					app.alert(data.msg);
				} else {
						that.showScheme = true;
						that.schemeurl=data.openlink
				}
			});
		},
		zhaunzeng:function(e){
			var that=this
			var levelprice =  e.currentTarget.dataset.levelprice
			if(that.salelevel_money<parseFloat(levelprice)){
				app.alert('额度不足');return
			}
			this.dialogShow = !this.dialogShow
			that.levelid =  e.currentTarget.dataset.levelid
		},
		tosale:function(e){
			var that = this;
			
			var levelprice =  e.currentTarget.dataset.levelprice

			if(that.salelevel_money<parseFloat(levelprice)){
				app.alert('额度不足');return
			}
			var levelid =  e.currentTarget.dataset.levelid
			var platform = app.getplatform()
			var frommid = that.frommid
			if(platform == 'mp' || platform == 'h5'){
				var sharelink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesA/levelupauth/buy?scene=pid_'+frommid+'-levelid_'+levelid;
				this._sharemp({title:"您有一份会员升级待查收，请尽快处理~",link:sharelink,pic:that.sharepic})
				app.error('点击右上角发送给好友或分享到朋友圈');
			}else if(platform == 'app'){
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
							sharedata.title = '您的好友向给您发送了一个会员等级';
							sharedata.summary = '您有一份会员等级待领取，请尽快处理~';
							sharedata.href = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesA/levelupauth/buy?scene=pid_'+that.payorder.frommid+'-levelid_'+levelid;
							sharedata.imageUrl = '';
							uni.share(sharedata);
						}
					}
				});
			}else{
				app.error('该终端不支持此操作');
			}
		},
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
.left .headimg{width: 80rpx; height: 80rpx;border-radius: 16rpx;}
.left .nickname{font-size: 30rpx;font-weight: 600;}
.left .desc{font-size: 30rpx;line-height: 36rpx; font-weight: bold;}
.txthide{max-width:100%;text-overflow: ellipsis;overflow: hidden;white-space: nowrap;}

.item .right{display: flex;justify-content: flex-end;flex-wrap: wrap;width: 260rpx;align-items: center;font-size: 24rpx;flex-shrink: 0;}
.right .btn{border: 1rpx solid #e5e5e5; text-align: center;color: #555;width: 120rpx;margin: 4rpx 0 4rpx 6rpx;border-radius: 10rpx;height: 60rpx;line-height: 60rpx;font-size: 24rpx;}
.right .btn1{border: none}

.remark{color: #fd0006;}


.popup_modal{ position: fixed; width: 100%; height: 100%; top:0; background: rgba(0,0,0,0.5);}
.popup_modal .content1{ background: #fff; position: absolute;top:30%; left: 8%; border-radius: 10rpx; width: 80%;} 
.popup_modal .content1 .title{ height: 100rpx; text-align: center; line-height: 100rpx;  font-weight: bold; font-size: 32rpx; border-bottom: 1rpx solid #f3f3f3;}
.popup_modal .content1 .item{ display: flex;padding:0 30rpx; margin-top: 30rpx; }
.popup_modal .content1  .f1{ height: 80rpx; line-height: 80rpx;margin: 30rpx;}

.btnbox{ display: flex; margin-bottom: 50rpx;}
.btnbox .btn1{ background: #e6e6e6}
.btnbox .btn1,.btnbox .btn2{ width: 40%; color: #fff; border-radius: 30rpx;}
</style>