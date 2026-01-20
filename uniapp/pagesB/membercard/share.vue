<template>
	<view class="container">
		<block v-if="isload">
			<view class="top">
				<view class="cardbg"  v-if="data.bg_type==1">
					<image :src="data.background_pic_url" />
				</view>
				<view class="cardbg" :style="'background: '+data.color" v-else></view>
				<view class="hyk flex">
					<view class="f1"><image :src="data.logo_url" ></view>
					<view class="f2">
						<text class="t1">{{data.brand_name}}</text>
						<text class="t2">{{data.title}}</text>
					</view>
				</view>
			</view>	
			<view style="height: 100rpx;"> </view>
			<view class="center1">
				<view class="f1" @tap="showqrcode">
					<image :src="pre_url+'/static/img/membercard/codeicon.png'" />
					<text class="t1">分享二维码</text>
				</view>
				<view class="f1" @tap="showsharelog">
					<image  :src="pre_url+'/static/img/membercard/logicon.png'" />
					<text  class="t1">分享记录</text>
				</view>
			</view>
			
			<view class="showcode" v-if="isshowcode">
				<view class="t1">识别图中二维码，领取会员卡</view>
				<view class="code">
					<image :src="info.sharecode">
				</view>
			</view>
			
			<view class="center2" v-if="isshowsharelog">
				
				<view class="title"> 我的分享记录 共邀请好友{{info.tjcount}}人</view>
				<block v-if="info.tjcount>0">
					<view class="item" v-if="sharelog" v-for="(item,index) in sharelog">
						<view class="headimg">
							<image  :src="item.headimg" />
							<view class="f1">
								<view class="t1">{{item.nickname}}</view>
								<view class="t2">{{dateFormat(item.createtime)}}</view>
							</view>
						</view>
						<view class="kkimg"><image :src="pre_url+'/static/img/membercard/kkimg.png'"></view>
					</view>
				</block>
				<block v-else>
						<view style="text-align: center;color: #999;">暂无邀请记录</view>
				</block>
			</view>
			
			
			<view class="center3">
				<view class="title">
					<view> 邀请好友 <text style="color: #D0735A;"> 开卡 </text>  即可得到奖励 </view>
					<view style="color: #999;font-size:24rpx;" @tap='goto' :data-url="'jiangli?id='+data.id">查看我的奖励	<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text></view>
				</view>
				<view class="item">
					<view class="score" v-if="data.parent_givescore>0">
						<view class="f1"><image :src="pre_url+'/static/img/membercard/integral.png'" /></view>
						<view class="f2"><text class="t1">{{data.parent_givescore}}</text><text>{{t('积分')}}</text></view>
					</view>
					<view class="score" v-if="data.parent_givemoney>0">
						<view class="f1"><image :src="pre_url+'/static/img/membercard/coupon2.png'" /></view>
						<view class="f2"><text class="t1">{{data.parent_givemoney}}</text><text>{{t('余额')}}</text></view>
					</view>
				
				</view>	
		
				<block v-if="data.parent_give_coupon" v-for="(item,index) in data.parent_couponList">
					<view class="cooupondesc" >
							<image class="cimg" :src="pre_url+'/static/img/membercard/coupon_bg.png'" mode="widthFix">
							<view class="coupon">
								<view class="f1">
									<image :src="pre_url+'/static/img/membercard/coupon_icon.png'" mode="widthFix"/>
									<text class="t1">{{item}}</text>
								</view>
								<text class="t2">X 1</text>
							</view>
					</view>
				</block>
			</view>
				
			<view class="rule" @tap="showrule">
				<label>活动规则</label><text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
			</view>	
				
			<view class="detail">
				<view class="title">会员卡详情</view>
				<view class="content">
					<view class="itembox">
						<view class='item'><label>特权说明</label><text class="t1">{{data.prerogative}}</text></view>
						<view class='item'>
							<label>联系电话</label>
							<text class="t2" @tap="goto" :data-url="'tel::'+data.service_phone">{{data.service_phone}}</text>
						</view> 
						<view class='item'><label>使用说明</label><text class="t1">{{data.description}}</text></view>
					</view>
				</view>
			</view>	






			<view style="height: 200rpx;"></view>
		
			<view class="bottom">
				
				<button @tap="goto" :data-url="data.ret_url">进入会员卡</button>
				<button  class="btn" @tap.stop="share" :data-cardid="data.id"  v-if="getplatform() == 'h5' || getplatform() == 'mp' || getplatform() == 'app'">
					立即分享
				</button>
	
			</view>
			
			
			<view class="sharemodal" v-if="isshowshare">
				<view class="hands"><image :src="pre_url+'/static/img/membercard/m-hand.png'" mode="widthFix"></view>
				<view class="image"><image :src="pre_url+'/static/img/membercard/m-share.png'" mode="widthFix"></view>
				<view class="title"  @tap.stop="closeshare" >跳过</view>
			</view>
			
			
			
			<view id="modal_wrap" class="modal_wrap" v-if="isshowrule">
				<view class="modal_box">
					<view class="modal_title">
						<i></i>
						<text class="title">活动规则</text>
					</view>
					<view class="content">
							{{data.rule}}
					</view>
					<view class="close_btn" @tap.stop="closerule">X</view>
				</view>
			</view>
			
		</block>		
		
		
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
			textset:{},
			data:{},
			coupon:{},
			shareTitle:'',
			sharePic:'',
			shareDesc:'',
			shareLink:'',
			mid:0,
			info:[],
			sharelog:[],
			url:'',
			dialogShow:false,
			isshowsharelog:false,
			isshowcode:false,
			isshowshare:false,
			isshowrule:false
    }
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onShareTimeline:function(){
		var that=this
		var title = '您有一份会员卡待领取！';
		var sharepic = that.data.logo_url;
		var sharelink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesB/membercard/receive?scene=id_'+that.data.id+'-pid_'+that.info.mid;
		var sharewxdata = this._sharewx({title:title,tolink:sharelink,pic:sharepic});
		var query = (sharewxdata.path).split('?')[1]+'&seetype=circle';
		var link = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#'+(sharewxdata.path).split('?')[0]+'&seetype=circle';
	},
	
	methods: {
		getdata: function () {
			var that = this; 
			that.loading = true;
			app.get('ApiMembercardCustom/gethyk', {id: that.opt.id}, function (res) {
				that.loading = false;
				uni.setNavigationBarTitle({
					title:  '会员卡'
				});
				if(res.status==1){
					that.data = res.data
					that.info = res.info
					that.sharelog =res.sharelog
					that.loaded(true);
					var sharelink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesB/membercard/receive?scene=id_'+res.data.id+'-pid_'+res.info.mid;
					that._sharemp({title:"您有一张会员卡待领取",link:sharelink,pic:res.data.logo_url})
					
				}else{
					if(res.msg){
							app.alert(res.msg);
					}
					setTimeout(function () {
						app.goto(res.url,'redirectTo');
					}, 1000);
					
				}

			});
		},

		showqrcode:function(e){
				var that = this;
				that.isshowcode = true;
				that.isshowsharelog = false;
		},
		showsharelog:function(e){
				var that = this;
				that.isshowsharelog = true;
				that.isshowcode = false;
		},
		share:function(e){
			var that=this
			var sharelink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesB/membercard/receive?scene=id_'+that.data.id+'-pid_'+that.info.mid;
			this._sharemp({title:"您有一张会员卡待领取",link:sharelink,pic:that.data.logo_url})
			that.isshowshare = true;
		},
		closeshare:function(e){
			var that=this
			that.isshowshare = false;
		},
		showrule:function(e){
			var that=this
			that.isshowrule = true;
		},
		closerule:function(e){
			var that=this
			that.isshowrule = false;
		}
	}
}
</script>
<style>
	page{ background: #fff;}
	
	.top{ position: relative; height:350rpx; width: 100%;}
	.cardbg{ position: absolute; width: 88%; height:350rpx;margin: 50rpx;border-radius: 10rpx;}
	.cardbg image{ width: 100%; height:100%;border-radius: 10rpx;}
	.hyk{ margin-top: 30rpx; z-index: 1000;position: absolute; left:10%; top:20%}
	.hyk .f1 image{ width: 100rpx;height: 100rpx; border-radius:50%}
	.hyk .f2 { display: flex;flex-direction: column;margin-left: 20rpx;}
	.hyk .f2 .t1{ margin-top: 10rpx;color: #fff;font-size: 30rpx;font-weight: bold;}
	.hyk .f2 .t2{ color: #F5F2F2;font-size: 24rpx;}
	
	.showcode{ display: flex; flex-direction: column; align-items: center; margin-top: 50rpx;}
	.showcode .t1{ text-align: center; }
	.showcode .code image{ width: 400rpx; height: 400rpx; margin-top: 20rpx;}
	
	
	.center1{ display: flex;justify-content: space-between;margin: 0 50rpx;}
	.center1 .f1{ width: 47%; box-shadow: 0 0 8rpx 8rpx #F6F6F6; display:flex;flex-direction: column;align-items: center;padding: 20rpx 0;border-radius: 10rpx;}
	.center1 image{ width: 100rpx; height:100rpx}
	.center1 .t1{  font-weight:bold}

	.center2{ margin: 30rpx 50rpx;}
	.center2 .title{ text-align: center; font-size:30rpx; font-weight:bold;margin:50rpx;}
	.center2 .item{ display: flex;position: relative;border-bottom:2rpx solid #F6F6F6;padding-bottom: 20rpx;}
	.center2 .headimg { display: flex;align-items: center;}
	.center2 .headimg .f1{ color: #333;}
	.center2 .headimg image{ width: 100rpx;height: 100rpx;border-radius: 50%;margin-right: 20rpx;}
	.center2 .kkimg{ position: absolute;right: 0;}
	.center2 .kkimg image{ width: 120rpx;height: 120rpx;}

	
	
	.center3{ background: #fff;margin:0 50rpx; padding:20rpx; border-radius:10rpx ;box-shadow: 0 0 8rpx 8rpx #F6F6F6; margin-top: 60rpx; }
	.center3 .title{ font-size:26rpx;display: flex;justify-content: space-between;}
	.center3 .item{ display: flex;margin-top: 30rpx;}
	.center3 .item .score{ margin-right: 60rpx; }
	.center3 .item .f1{ display: flex; justify-content: center; }
	.center3 .item .f1 image{ width: 70rpx;height: 70rpx;border-radius: 50%;  }
	.center3 .item .f2 { display: flex;text-align: center; margin-top: 10rpx; }
	.center3 .item .f2 .t1{ color:#D0735A ;margin-right: 10rpx;}
	.center3 .item .f2 .t2{ font-display: flex; font-size:24rpx; color:#999;text-align: center; }

	.cooupondesc{ display: flex;align-items: center; position: relative; margin-top: 30rpx; }
	.cooupondesc .cimg{ width: 100%;}
	.cooupondesc .t1{ font-size: 26rpx;color: #999;margin-left: 10rpx;}
	.cooupondesc .coupon{ position: absolute;  display: flex; left:10%; justify-content: space-between;width: 80%; align-items: center;color: #fff; }
	.cooupondesc .coupon .f1{ display: flex; align-items: center;}
	.cooupondesc .coupon .t1{ color: #fff;}
	.cooupondesc .coupon .t2{ font-size: 24rpx;}
	.cooupondesc .coupon image{ width:40rpx ;}
	
	.rule{ padding: 20rpx 30rpx; margin: 30rpx 50rpx; display: flex; justify-content: space-between; width: 88%;box-shadow: 0 0 8rpx 4rpx #F6F6F6; border-radius:10rpx ;}
	
	.detail{margin:30rpx 50rpx; }
	.detail .title{ font-size: 26rpx;font-weight: bold;}
	.detail .content{ display:flex;margin-top: 20rpx;box-shadow: 0 0 8rpx 4rpx #F6F6F6; border-radius:10rpx ; }
	.detail .itembox { background: #fff; width: 100%;border-radius: 10rpx;  padding:10rpx 20rpx;}
	.detail .itembox .item{ height: 80rpx;line-height: 80rpx; font-size:24rpx}
	.detail .itembox .item label{ color: #999;}
	.detail .itembox .item .t1{ margin-left: 20rpx;}
	.detail .itembox .item .t2{ margin-left: 20rpx;color:#80B76F}
	
	
	

	.bottom{ background: #F6F6F6;position: fixed; bottom:0; width:100%; display: flex; }
	.bottom button{ width: 45%;margin: 20rpx auto;color: #fff; border-radius:15rpx; background-color: #C5A67A; }
	
	
	.btnbox{ display: flex; margin-bottom: 50rpx;}
	.btnbox .btn1{ background: #e6e6e6}
	.btnbox .btn1,.btnbox .btn2{ width: 40%; color: #fff; border-radius: 30rpx;}
	
	.sharemodal{ position: fixed; bottom: 0; width: 100%; height: 100%; top:0; background: rgba(0, 0, 0, 0.5); display: flex; justify-content: center;}
	.sharemodal .image{ width: 100%; display: flex; align-items: center; justify-content: center;}
	.sharemodal  image{width:400rpx}
	.sharemodal .hands{ width: 124rpx; height: 208rpx;position: absolute;  top:0%; right:25rpx}
	.sharemodal .hands image{ width: 100%; height: 100%;}
	.sharemodal .title{ color: #fff; position: absolute; top:65%; font-size: 32rpx;}
	
	.modal_wrap{ position: fixed; bottom: 0; width: 100%; height: 100%; top:0; background: rgba(0, 0, 0, 0.5); display: flex; justify-content: center;}
	.modal_wrap .modal_box { position: absolute;  left: 0px; right: 0px;  top: 160rpx;   bottom: 0px;  margin: 100rpx auto auto;  width: 515rpx;  height: 692rpx; background: #fff;  padding: 020rpx; font-size: 24rpx;  box-sizing: border-box;  border-radius: 24rpx;
	}
	.modal_wrap .modal_title { position: relative; height: 100rpx; line-height: 100rpx;  font-size: 32rpx; text-align: center;}
	.modal_wrap .close_btn {
	    position: absolute;
	    left: 50%;
	    width: 90rpx;
	    height: 90rpx;
	    transform: translateX(-50%);
	    border-radius: 50%;
	    text-align: center;
	    line-height: 90rpx;
	    background: #fff;
			bottom:-20%
	}
</style>
