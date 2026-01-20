<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['未使用','已使用','已过期']" :itemst="['0','1','2']" :st="st" :isfixed="false" @changetab="changetab"></dd-tab>

		<view class="coupon-list">
			<view v-for="(item, index) in datalist" :key="index" class="coupon" @tap.stop="goto" @tap="gtdetail(item.type)" :data-url="'coupondetail?rid=' + item.id" :style="!item.from_mid && (item.isgive == 1 || item.isgive == 2)?'padding-left:40rpx':''">
				<view class="radiobox" @tap.stop="changeradio" :data-index="index"><view class="radio" :style="item.checked ? 'background:'+t('color1')+';border:0' : ''" v-if="item.status == 0 && !item.from_mid && (item.isgive == 1 || item.isgive == 2)"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view></view>
				<view class="pt_left">
					<view class="pt_left-content">
						<view class="f1" :style="{color:t('color1')}" v-if="item.type==1"><text class="t0">￥</text><text class="t1">{{item.money}}</text></view>
						<view class="f1" :style="{color:t('color1')}" v-if="item.type==10"><text class="t1">{{(item.discount/10)}}</text><text class="t0">折</text></view>
						<view class="f1" :style="{color:t('color1')}" v-if="item.type==3"><text class="t1" style="font-size: 50rpx;">{{item.used_count}}/{{item.limit_count}}</text><text class="t2">次</text></view>
						<view class="f1" :style="{color:t('color1')}" v-if="item.type==5"><text class="t0">￥</text><text class="t1">{{item.money}}</text></view>
						<view class="f1" :style="{color:t('color1')}" v-if="item.type==6"><text class="t0">￥</text><text class="t1">{{item.money}}</text></view>
						<block v-if="item.type!=1 && item.type!=10 && item.type!=3 && item.type!=5 &&  item.type!=6">
							<view class="f1" :style="{color:t('color1')}">{{item.type_txt}}</view>
						</block>
						<view class="f2" :style="{color:t('color1')}" v-if="item.type==1 || item.type==4 || item.type==5 || item.type==10 ||  item.type==6">
							<text v-if="item.minprice>0">满{{item.minprice}}元可用</text>
							<text v-else>无门槛</text>
						</view>
					</view>
				</view>
				<view class="pt_right">
					<view class="f1">
						<view class="t1">{{item.couponname}}</view>
            <view class="t1" v-if="item.num && item.num>0" :style="{color:t('color1')}">共{{item.num}}张</view>
						<text class="t2" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}">{{item.type_txt}}</text>
						<text class="t2" v-if="item.status == 0 && !item.from_mid && (item.isgive == 1 || item.isgive == 2)" :style="{background:'rgba('+t('color2rgb')+',0.1)',color:t('color2')}">可赠送</text>
						<view class="t3" v-if="item.type!=20" :style="item.bid>0?'margin-top:0':'margin-top:10rpx'">有效期至 {{item.endtime}}</view>
						<view class="t4" v-if="item.bid>0">适用商家：{{item.bname}}</view>
					</view>
          <view style="display: flex;flex-wrap: wrap;align-items: center;justify-content: center;width: 140rpx;">
            <block v-if="item.isgive!=2 && st==0">
              <block v-if="item.type==1 || item.type==10">
                <button class="btn" :style="{background:'linear-gradient(270deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" v-if="inArray(item.fwtype,[0,1,2])" @tap.stop="goto" :data-url="'/pages/shop/prolist?cpid='+item.couponid+(item.bid?'&bid='+item.bid:'')">去使用</button>
                <button class="btn" :style="{background:'linear-gradient(270deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" v-if="item.fwtype == 4" @tap.stop="goto" :data-url="'/activity/yuyue/prolist?cpid='+item.couponid+(item.bid?'&bid='+item.bid:'')">去使用</button>
              </block>
              <block v-else-if="item.type==6">
                <button class="btn" :style="{background:'linear-gradient(270deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" v-if="inArray(item.fwtype,[0,1,2])" @tap.stop="goto" :data-url="'/hotel/index/index'">去使用</button>
              </block>
              <block v-else>
                <button class="btn" @tap="gtdetail(item.type)" :style="{background:'linear-gradient(270deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" @tap.stop="goto" :data-url="'coupondetail?rid=' + item.id">去使用</button>
              </block>
            </block>
            <image class="sygq" v-if="st==1" :src="pre_url+'/static/img/ysy.png'"></image>
            <image class="sygq" v-if="st==2" :src="pre_url+'/static/img/ygq.png'"></image>
            <block v-if="item.num && item.num>1">
              <button class="btn" :style="{border:'2rpx solid '+t('color1'),color:t('color1'),marginTop:'20rpx'}" @tap.stop="goto" :data-url="'/pagesC/coupon/mymorecoupon?id='+item.id">查看全部</button>
            </block>
          </view>
				</view>
			</view>
      <block v-if="checkednum > 0" >
        <view v-if="coupon_transfer" style="width: 100%;height:284rpx ;"></view>
        <view v-else style="width: 100%;height:10rpx ;"></view>
      </block>
		</view>
		<nomore v-if="nomore"></nomore>
		<nodata v-if="nodata"></nodata>
		<view class="giveopbox" v-if="checkednum > 0" :class="menuindex>-1?'tabbarbot':'notabbarbot3'">
			<block v-if="rdata.is_direct_give">
					<button class="btn-give"  @tap="showDirectGiveShow"  :style="{background:'linear-gradient(90deg,'+t('color2')+' 0%,rgba('+t('color2rgb')+',0.8) 100%)'}" >转赠好友({{checkednum}}张)</button>
			</block>
			<block v-else>
        <button v-if="coupon_transfer" class="btn-give"  @tap="couponTransferOpen" style="margin-bottom: 20rpx;background-color: #fff;" :style="{border:'2rpx solid '+t('color2'),color:t('color2')}" >直接转增({{checkednum}}张)</button>
				<view class="btn-give" @tap="shareapp" v-if="getplatform() == 'app'" :style="{background:'linear-gradient(90deg,'+t('color2')+' 0%,rgba('+t('color2rgb')+',0.8) 100%)'}">转赠好友({{checkednum}}张)</view>
				<view class="btn-give" @tap="sharemp" v-else-if="getplatform() == 'mp'" :style="{background:'linear-gradient(90deg,'+t('color2')+' 0%,rgba('+t('color2rgb')+',0.8) 100%)'}">转赠好友({{checkednum}}张)</view>
				<view class="btn-give" @tap="sharemp" v-else-if="getplatform() == 'h5'" :style="{background:'linear-gradient(90deg,'+t('color2')+' 0%,rgba('+t('color2rgb')+',0.8) 100%)'}">转赠好友({{checkednum}}张)</view>
				<button class="btn-give" open-type="share" v-else :style="{background:'linear-gradient(90deg,'+t('color2')+' 0%,rgba('+t('color2rgb')+',0.8) 100%)'}" >转赠好友({{checkednum}}张)</button>
			</block>
		</view>
    <uni-popup id="couponTransfer" ref="couponTransfer" type="dialog">
      <view class="uni-popup-dialog">
        <view class="uni-dialog-title">
          <text class="uni-dialog-title-text">直接转增({{checkednum}}张)</text>
        </view>
        <view class="uni-dialog-content">
          <view style="padding: 0 20rpx;width: 100%;line-height: 60rpx;">
            <view style="margin-bottom: 20rpx;display: flex;">
              对方的ID：<input @input="transferInput" @blur="changeQuery" data-field='transfermid' class="input" type="number" :value="transfermid" placeholder="请输入对方ID" placeholder-style="color:#999;font-size:32rpx;height:60rpx;line-height: 60rpx;" style="border-bottom: 2rpx solid #f1f1f1;width: 460rpx;height:60rpx;line-height: 60rpx;" ></input>
            </view>
            <view style="margin-bottom: 20rpx;display: flex;">
              或手机号：<input @input="transferInput" @blur="changeQuery" data-field='transfertel' class="input" type="number" :value="transfertel" placeholder="请输入对方手机号" placeholder-style="color:#999;font-size:32rpx;height:60rpx;line-height: 60rpx;" style="border-bottom: 2rpx solid #f1f1f1;width: 460rpx;height:60rpx;line-height: 60rpx;" ></input>
            </view>
            <view style="margin-bottom: 20rpx;display: flex;">
              <view>对方详情：</view>
              <view v-if="transfer_memberinfo.id" style="display: flex;">
                <image class="head-img" :src="transfer_memberinfo.headimg" v-if='transfer_memberinfo.headimg'></image>
                <image class="head-img" :src="pre_url+'/static/img/wxtx.png'" v-else></image>
                <view class="member-text-view">
                	<view class="member-nickname">{{transfer_memberinfo.nickname}}</view>
                	<view class="member-id">ID：{{transfer_memberinfo.id}}</view>
                </view>
              </view>
              <view v-else>无</view>
            </view>
          </view>
        </view>
        <view class="uni-dialog-button-group">
          <view class="uni-dialog-button" @tap="couponTransferClose">
            <text class="uni-dialog-button-text">取消</text>
          </view>
          <view class="uni-dialog-button" :style="{color:t('color1')}" @tap="couponTransferConfirm">
            <text class="uni-dialog-button-text">确定</text>
          </view>
        </view>
      </view>
    </uni-popup>
		<view style="display:none">{{test}}</view>
		<view v-if="directGiveShow" class="popup__container">
			<view class="popup__overlay" @tap.stop="showDirectGiveShow"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">- 转赠给好友 -</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx"
						@tap.stop="showDirectGiveShow" />
				</view>
				<view class="popup__content invoiceBox">
					<form @submit="directGiveSubmit" @reset="formReset" report-submit="true">
						<view class="orderinfo">
							<view class="item">
								<text class="t1">转赠ID</text>
								<input class="t2" type="text" placeholder="请输入转增ID" placeholder-style="font-size:28rpx;color:#BBBBBB" name="tomid"  :value="tomid" ></input>
							</view>
						</view>
						<button class="btn" form-type="submit" :style="{background:t('color1')}">确定</button>
						<view style="padding-top:30rpx"></view>
					</form>
				</view>
			</view>
		</view>
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
			
      st: 0,
      datalist: [],
      pagenum: 1,
      nomore: false,
			nodata:false,
			givecheckbox:false,
			checkednum:0,
			test:'',
			shareTitle:'',
			sharePic:'',
			shareDesc:'',
			shareLink:'',
			tmplids:[],
			rdata:[],
			ids:'',//赠送IDs
			directGiveShow:false,//直接赠送的弹窗
      fold:1,//是否折叠 0：不折叠 1：折叠
      
      coupon_transfer:0,//是否可以直接转增用户
      transfer_memberinfo:{},//转增用户信息
      transfermid:'',//转增用户ID
      transfertel:''//转增用户手机号
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 0;
  },
  onShow: function (opt) {
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
	onShareAppMessage:function(){
		console.log('this.shareLink');
		console.log(this.shareLink);
		return this._sharewx({title:this.shareTitle,pic:this.sharePic,desc:this.shareDesc,link:this.shareLink});
	},
	onShareTimeline:function(){
		var sharewxdata = this._sharewx({title:this.shareTitle,pic:this.sharePic,desc:this.shareDesc,link:this.shareLink});
		var query = (sharewxdata.path).split('?')[1]+'&seetype=circle';
		console.log(sharewxdata)
		console.log(query)
		return {
			title: sharewxdata.title,
			imageUrl: sharewxdata.imageUrl,
			query: query
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
      var st = that.st;
      var bid = that.opt && (that.opt.bid || that.opt.bid === '0') ? that.opt.bid : '';
			that.loading = true;
			that.nomore = false;
			that.nodata = false;
      app.post('ApiCoupon/mycoupon', {st: st,pagenum: pagenum,bid: bid,fold:that.fold}, function (res) {
				that.loading = false;
				uni.setNavigationBarTitle({
					title: '我的' + that.t('优惠券')
				});
				that.rdata =  res.rdata;
        var data = res.data;
        if (pagenum == 1) {
					that.checkednum = 0;
					that.pics = res.pics;
					that.clist = res.clist;
					that.givecheckbox = res.givecheckbox;
          that.coupon_transfer = res.coupon_transfer || 0;
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
				that.tmplids = res.tmplids;
				
      });
    },
		gtdetail:function(ctype){
			if(ctype == 3){
				var that = this;
				that.subscribeMessage();
			}
		},
    changetab: function (st) {
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
    changeradio: function (e) {
      var that = this;
      var index = e.currentTarget.dataset.index;
			var datalist = that.datalist;
      var checked = datalist[index].checked;
			if(checked){
				datalist[index].checked = false;
				that.checkednum--;
			}else{
				datalist[index].checked = true;
				that.checkednum++;
			}
			that.datalist = datalist;
			that.test = Math.random();
			console.log(that.checkednum);
			var ids = [];
			for(var i in datalist){
				if(datalist[i].checked){
					ids.push(datalist[i].id);
				}
			}
			ids = ids.join(',');
			that.ids = ids;
			that.shareTitle = '送你'+that.checkednum+'张'+that.t('优惠券');
			that.shareDesc = '点击前往查看领取';
			that.sharePic = app.globalData.initdata.logo;
			if(app.globalData.platform == 'h5' || app.globalData.platform == 'mp' || app.globalData.platform == 'app'){
				that.shareLink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesExt/coupon/coupongive?scene=rids_'+ids+'-pid_' + app.globalData.mid;
			}else{
				that.shareLink = '/pagesExt/coupon/coupongive?scene=rids_'+ids+'-pid_' + app.globalData.mid;
			}
			console.log(that.shareLink);
			that.loaded({title:that.shareTitle,pic:that.sharePic,desc:that.shareDesc,link:that.shareLink});
    },
		sharemp:function(){
			app.error('点击右上角发送给好友或分享到朋友圈');
			this.sharetypevisible = false
		},
		shareapp:function(){
			var that = this;
			that.sharetypevisible = false;
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
						sharedata.title = that.shareTitle;
						sharedata.summary = that.shareDesc;
						sharedata.href = that.shareLink;
						sharedata.imageUrl = that.sharePic;
						
						uni.share(sharedata);
					}
		    }
		  });
		},
		showDirectGiveShow(){
			this.directGiveShow = !this.directGiveShow;
		},
		directGiveSubmit(e){
			var that = this;
			var ids = this.ids;
			var mid = e.detail.value.tomid;
			if(!mid){
				app.error('请输入转赠ID');
				return;
			}
			
			that.loading = true;
			app.post('ApiCoupon/couponDirectGive', {mid:mid,ids:ids}, function (res) {
				that.loading = false;
				that.directGiveShow = false;
				if(res.status ==0){
					app.error(res.msg);
					return;
				}else{
					app.success(res.msg);
					
					that.getdata();
				}
			});
		},
    couponTransferOpen:function(){
      this.$refs.couponTransfer.open();
    },
    couponTransferConfirm:function(){
      var that = this;
      var ids = [];
      var datalist = that.datalist;
      for(var i in datalist){
      	if(datalist[i].checked){
      		ids.push(datalist[i].id);
      	}
      }
      if(ids.length<=0){
        app.error('请选择要转增的'+that.t('优惠券'));return;
      }
      ids = ids.join(',');
      console.log(ids)
      app.confirm('确定直接转增给此用户吗？',function(){
        app.showLoading();
        app.post('ApiCoupon/couponTransfer', {mid:that.transfer_memberinfo.id,ids:ids}, function (data) {
          app.showLoading(false);
          if (data.status == 1) {
            app.success(data.msg);
            setTimeout(function () {
              that.$refs.couponTransfer.close();
              that.getdata();
            }, 1000);
          }else {
            app.error(data.msg);
            return;
          }
        }, '提交中');
      })

    },
    changeQuery(){
    	let that = this;
    	if(!that.transfermid && !that.transfertel){
        that.transfer_memberinfo = {};
        return app.error('请输入对方ID或对方手机号');
      } 
    	that.loading = true
    	app.get('ApiMy/getMemberBase',{mid:that.transfermid,tel:that.transfertel},function (res) {
    		that.loading = false
    		if(res.status == 1){
    			that.transfer_memberinfo = res.data;
    		}else{
          that.transfer_memberinfo = {};
    			app.error('未查询到此会员！');
    		}
    	});
    },
    couponTransferClose:function(){
    	this.$refs.couponTransfer.close();
    },
    transferInput(e){
      var that = this;
      var field  = e.currentTarget.dataset.field;
    	that[field]= e.detail.value;
    },
  }
};
</script>
<style>

.coupon-list{width:100%;padding:20rpx}
.coupon{width:100%;display:flex;margin-bottom:20rpx;border-radius:10rpx;overflow:hidden;align-items:center;position:relative;background: #fff;}
.coupon .pt_left{background: #fff;min-height:200rpx;color: #FFF;width:30%;display:flex;flex-direction:column;align-items:center;justify-content:center}
.coupon .pt_left-content{width:100%;height:100%;margin:30rpx 0;border-right:1px solid #EEEEEE;display:flex;flex-direction:column;align-items:center;justify-content:center}
.coupon .pt_left .f1{font-size:40rpx;font-weight:bold;text-align:center;}
.coupon .pt_left .t0{padding-right:0;}
.coupon .pt_left .t1{font-size:60rpx;}
.coupon .pt_left .t2{padding-left:10rpx;}
.coupon .pt_left .f2{font-size:20rpx;color:#4E535B;text-align:center;}
.coupon .pt_right{background: #fff;width:70%;display:flex;min-height:200rpx;text-align: left;padding:20rpx 20rpx;position:relative;justify-content: space-between;align-items: center;}
.coupon .pt_right .f1{flex-grow: 1;flex-shrink: 1;}
.coupon .pt_right .f1 .t1{font-size:28rpx;color:#2B2B2B;font-weight:bold;height:60rpx;line-height:60rpx;overflow:hidden}
.coupon .pt_right .f1 .t2{height:36rpx;line-height:36rpx;font-size:20rpx;font-weight:bold;padding:0 16rpx;border-radius:4rpx; margin-right: 16rpx;}
.coupon .pt_right .f1 .t2:last-child {margin-right: 0;}
.coupon .pt_right .f1 .t3{font-size:20rpx;color:#999999;height:46rpx;line-height:46rpx;}
.coupon .pt_right .f1 .t4{font-size:20rpx;color:#999999;height:46rpx;line-height:46rpx;max-width: 76%;text-overflow: ellipsis;overflow: hidden;white-space: nowrap;}
.coupon .pt_right .btn{border-radius:28rpx;width:140rpx;height:56rpx;line-height:56rpx;color:#fff}
.coupon .pt_right .sygq{margin-top:20rpx;width:100rpx;height:100rpx;}

.coupon .pt_left.bg3{background:#ffffff;color:#b9b9b9!important}
.coupon .pt_right.bg3 .t1{color:#b9b9b9!important}
.coupon .pt_right.bg3 .t3{color:#b9b9b9!important}
.coupon .pt_right.bg3 .t4{color:#999999!important}

.coupon .radiobox{position:absolute;left:0;padding:20rpx}
.coupon .radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;}
.coupon .radio .radio-img{width:100%;height:100%}
.giveopbox{position:fixed;bottom:0;left:0;width:100%;}
.btn-give{width:90%;margin:30rpx 5%;height:96rpx; line-height:96rpx; text-align:center;color: #fff;font-size:30rpx;font-weight:bold;border-radius:48rpx;}

.popup__modal{min-height: 500rpx;}
.orderinfo{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;overflow:hidden}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;flex-shrink:0}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}
.invoiceBox .btn{ height:80rpx;line-height: 80rpx;width:90%;margin:0 auto;border-radius:40rpx;margin-top:40rpx;color: #fff;font-size: 28rpx;font-weight:bold}

.uni-popup-dialog {width: 720rpx;border-radius: 10rpx;background-color: #fff;}
.uni-dialog-title {display: flex;flex-direction: row;justify-content: center;padding-top: 30rpx;padding-bottom: 10rpx;}
.uni-dialog-title-text {font-size: 32rpx;font-weight: 500;}
.uni-dialog-content {display: flex;flex-direction: row;justify-content: center;align-items: center;padding: 5px 15px 15px 15px;}
.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;}
.uni-dialog-button-group {display: flex;flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
.uni-dialog-button {display: flex;flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;}
.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
.uni-dialog-button-text {font-size: 14px;}
.uni-button-color {color: #007aff;}

.head-img{width: 90rpx;height: 90rpx;border-radius: 50%;overflow: hidden;}
.member-text-view{height: 90rpx;padding-left: 20rpx;display: flex;flex-direction: column;align-items: flex-start;justify-content: flex-start;}
.member-text-view .member-nickname{font-size: 28rpx;color: #333;font-weight: bold;}
.member-text-view .member-id{font-size: 24rpx;color: #999999;margin-top: 10rpx;}
</style>