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
						<text class="f2" v-if="item.id == team.mid">团长</text>
					</view>
				</view>
				<view class="join-text" v-if="team.status==1 ">
					<view v-if="!product.collage_type">仅剩<text class="join-te1">{{team.teamnum-team.num}}</text>个名额</view>
					<view style="font-size:28rpx;color:#f80"> {{rtimeformat}} 后结束</view>
				</view>
				<view class="join-text" v-if="team.status==2">已满员,拼团成功</view>
				<view class="join-text" v-if="team.status==3">拼团失败</view>
				<button class="join-btn" @tap="shareClick" v-if="team.status==1 && haveme==1">邀请好友参团</button>
        <block v-if="team.status==1 && haveme==0">
          <button class="join-btn" @tap="buydialogChange">我要参团</button>
          <button class="join-btn2" @tap="goto" :data-url="'product?id=' + product.id + '&teampid='+teamid" style="margin-top: 30rpx;">参与更多拼团</button>
        </block>
				
			</view>
			<view class="teambox" v-if="show_mingpian">
				<block v-for="(item, index) in userlist" :key="index" class="item">
				<view class="item1" v-if="item.id>0">
					<view class="f1">
						<image :src="item.headimg"></image>
						<view class="t2">
							<text class="x1">{{item.nickname}}</text>
							<text class="x2" v-if="item.id == team.mid">团长</text>
						</view>
					</view>
					<view class="f2" v-if="item.mingpian_id>0" @tap="goto" :data-url="'/pagesExt/mingpian/index?id='+item.mingpian_id">
						<text class="x1">查看名片</text>
					</view>
				</view>
				</block>
			</view>
			
			
			<view :hidden="buydialogHidden">
				<view class="buydialog-mask">
					<view class="buydialog" :class="menuindex>-1?'tabbarbot':'notabbarbot'">
						<view class="close" @tap="buydialogChange">
							<image :src="pre_url+'/static/img/close.png'" class="image"></image>
						</view>
						<view class="title">
							<image :src="guigelist[ks].pic?guigelist[ks].pic:product.pic" class="img" @tap="previewImage" :data-url="guigelist[ks].pic?guigelist[ks].pic:product.pic"></image>
							<!-- <text class="name">{{product.name}}</text> -->
							<view class="price" v-if="btntype==1"><text class="t1">￥</text>{{guigelist[ks].market_price}}</view>
							<view class="price" v-else><text class="t1">￥</text>{{guigelist[ks].sell_price}} <text v-if="guigelist[ks].market_price > guigelist[ks].sell_price" class="t2">￥{{guigelist[ks].market_price}}</text></view>
							<view class="choosename">已选规格: {{guigelist[ks].name}}</view>
							<view class="stock">剩余{{guigelist[ks].stock}}件</view>
						</view>
			
						<view v-for="(item, index) in guigedata" :key="index" class="guigelist flex-col">
							<view class="name">{{item.title}}</view>
							<view class="item flex flex-y-center">
								<block v-for="(item2, index2) in item.items" :key="index2">
									<view :data-itemk="item.k" :data-idx="item2.k" :class="'item2 ' + (ggselected[item.k]==item2.k ? 'on':'')" @tap="ggchange">{{item2.title}}</view>
								</block>
							</view>
						</view>
						<view class="buynum flex flex-y-center" v-if="!product.collage_type">
							<view class="flex1">购买数量：</view>
							<view class="addnum">
								<view class="minus"><image class="img" :src="pre_url+'/static/img/cart-minus.png'" @tap="gwcminus"/></view>
								<input class="input" type="number" :value="gwcnum" @input="gwcinput"></input>
								<view class="plus"><image class="img" :src="pre_url+'/static/img/cart-plus.png'" @tap="gwcplus"/></view>
							</view>
						</view>
						<view class="op">
								<button class="tobuy" :style="{background:t('color1')}" @tap="tobuy" data-type="3">确 定</button>
						</view>
					</view>
				</view>
			</view>
		</view>


		<view v-if="sharetypevisible" class="popup__container">
			<view class="popup__overlay" @tap.stop="handleClickMask"></view>
			<view class="popup__modal" style="height:320rpx;min-height:320rpx">
				<!-- <view class="popup__title">
					<text class="popup__title-text">请选择分享方式</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="hidePstimeDialog"/>
				</view> -->
				<view class="popup__content">
					<view class="sharetypecontent">
						<view class="f1" @tap="shareapp" v-if="getplatform() == 'app'">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
							<text class="t1">分享给好友</text>
						</view>
						<view class="f1" @tap="sharemp" v-else-if="getplatform() == 'mp'">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
							<text class="t1">分享给好友</text>
						</view>
						<!-- <view class="f1" @tap="sharemp" v-else-if="getplatform() == 'h5'">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
							<text class="t1">分享给好友</text>
						</view> -->
						<button class="f1" open-type="share" v-else-if="getplatform() != 'h5'">
							<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
							<text class="t1">分享给好友</text>
						</button>
						<view class="f2" @tap="showPoster">
							<image class="img" :src="pre_url+'/static/img/sharepic.png'"/>
							<text class="t1">生成分享图片</text>
						</view>
					</view>
				</view>
			</view>
		</view>
		<view class="posterDialog" v-if="showposter">
			<view class="main">
				<view class="close" @tap="posterDialogClose"><image class="img" :src="pre_url+'/static/img/close.png'"/></view>
				<view class="content">
					<image class="img" :src="posterpic" mode="widthFix" @tap="previewImage" :data-url="posterpic"></image>
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
      tabnum: 1,
      ggselected: [],
			guigedata: [],
			guigelist:[],
			haveme:0,
      ks: '',
      gwcnum: 1,
      showdetail: false,
      buydialogHidden: true,
      team: [],
      userlist: [],
      product: [],
      rtime: '',
      rtimeformat: '',
      isfavorite: "",
      sharetypevisible: false,
      showposter: false,
      posterpic: "",
	    show_mingpian:false,
      teamid:0,
      teampid:0,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
    var teamid   = this.opt.teamid  || 0;
		this.teamid  = teamid;
    if(this.opt.tpid || this.opt.teampid){
      this.teampid = teamid;
    }
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
			app.get('ApiCollage/team', {teamid: that.teamid,teampid: that.teampid}, function (res) {
				that.loading = false;
        if(res.status == 1) {
          that.ggselected  = res.ggselected;
          that.guigedata  = res.guigedata;
          that.guigelist  = res.guigelist;
          that.haveme  = res.haveme;
          that.ks  = res.ks;
          that.product  = res.product;
          that.rtime  = res.rtime;
          that.shopset  = res.shopset;
          that.sysset   = res.sysset;
          that.team     = res.team;
          that.teamid   = res.team.id;
          if(that.teampid){
            that.teampid  = res.team.id;
          }
          that.userlist = res.userlist;
          that.show_mingpian = res.show_mingpian;
          that.getrtime();
          setInterval(function () {
            that.getrtime();
          }, 1000);
          var link = '/activity/collage/team?scene=id_'+that.product.id+'-pid_' + app.globalData.mid+'-teamid_'+that.team.id+'-tpid_1';
          that.loaded({title:'就差你了，快来一起拼团~ ',desc:res.product.name,pic:res.product.pic,link:link});
        } else {
          if (res.msg) {
            app.alert(res.msg, function() {
              if (res.url) app.goto(res.url);
            });
          } else if (res.url) {
            if(res.opentype){
              app.goto(res.url,res.opentype);
            }else{
              app.goto(res.url);
            }
          } else {
            app.alert('您无查看权限');
          }
        }
			});
		},
    buydialogChange: function (e) {
      this.buydialogHidden = !this.buydialogHidden
    },
    //选择规格
    ggchange: function (e) {
      var idx = e.currentTarget.dataset.idx;
      var itemk = e.currentTarget.dataset.itemk;
      var ggselected = this.ggselected;
      ggselected[itemk] = idx;
      var ks = ggselected.join(',');
      this.ggselected = ggselected;
      this.ks = ks;
    },
    //加
    gwcplus: function (e) {
      var gwcnum = this.gwcnum + 1;
      var ggselected = this.ks;

      if (gwcnum > this.guigelist[ggselected].stock) {
        app.error('库存不足');
        return;
      }
      this.gwcnum = this.gwcnum + 1;
    },
    //减
    gwcminus: function (e) {
      var gwcnum = this.gwcnum - 1;
      var ggselected = this.ks;

      if (gwcnum <= 0) {
        return;
      }
      this.gwcnum = this.gwcnum - 1;
    },
    //输入
    gwcinput: function (e) {
      var ggselected = this.ks;
      var gwcnum = parseInt(e.detail.value);
      if (gwcnum < 1) return 1;
      if (gwcnum > this.guigelist[ggselected].stock) {
        return this.guigelist[ggselected].stock;
      }
      this.gwcnum = gwcnum;
    },
    tobuy: function (e) {
      var type = e.currentTarget.dataset.type;
      var that = this;
      var ggselected = that.ks;
      var proid = that.product.id;
      var ggid = that.guigelist[ggselected].id;
      var num = that.gwcnum; //var prodata = proid + ',' + ggid + ',' + num;

      app.goto('buy?proid=' + proid + '&num=' + num + '&ggid=' + ggid + '&buytype=' + type + '&teamid=' + that.team.id);
    },
    getrtime: function () {
      var rtime = this.rtime - 1;
      if (rtime < 0) {
        this.rtimeformat = '0秒';
        this.rtime = rtime;
      } else {
        var hours = Math.floor(rtime / 3600); //计算相差分钟数  
        var leave2 = rtime % 3600; //计算小时数后剩余的毫秒数  
        var minutes = Math.floor(leave2 / 60); //计算相差秒数  
        var seconds = leave2 % 60; //计算分钟数后剩余的毫秒数
        var rtimeformat = hours + "小时" + minutes + "分" + seconds + "秒";
        this.rtimeformat = rtimeformat;
        this.rtime = rtime;
      }
    },
    shareClick: function () {
      this.sharetypevisible = true;
    },
    handleClickMask: function () {
      this.sharetypevisible = false;
    },
    showPoster: function () {
      var that = this;
      that.showposter = true;
      that.sharetypevisible = false;
			app.showLoading('努力生成中');
      app.post('ApiCollage/getTeamPoster', {proid: that.product.id,teamid: that.team.id}, function (data) {
				app.showLoading(false);
        if (data.status == 0) {
          app.alert(data.msg);
        } else {
          that.posterpic = data.poster;
        }
      });
    },
    posterDialogClose: function () {
      this.showposter = false;
    },
		sharemp:function(){
			app.error('点击右上角发送给好友或分享到朋友圈');
			this.sharetypevisible = false
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
						sharedata.title = that.product.name;
						//sharedata.summary = app.globalData.initdata.desc;
						sharedata.href = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/activity/collage/team?scene=id_'+that.product.id+'-pid_' + app.globalData.mid+'-teamid_'+that.team.id+'-tpid_1';
						sharedata.imageUrl = that.product.pic;
						var sharelist = app.globalData.initdata.sharelist;
						if(sharelist){
							for(var i=0;i<sharelist.length;i++){
								if(sharelist[i]['indexurl'] == '/activity/collage/team'){
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
.userlist .item{display: inline-block;width:120rpx; height:120rpx;position: relative;}
.userlist .item .f1{width:100rpx; height:100rpx;border-radius: 50%;border: 1px #ffc32a solid;}
.userlist .item .f2{background: #ffab33;border-radius:100rpx;padding:4rpx 16rpx;border:1px #fff solid;position: absolute;top: 0px; left: -20rpx;color: #9f7200;font-size: 30rpx;}

.join-text{color:#000;padding: 30rpx 0;font-size:36rpx;font-weight: 600;background: #fff; text-align: center;width: 100%;}

.join-btn{width: 90%;margin:20rpx 5%;background: linear-gradient(90deg, #FF3143 0%, #FE6748 100%);color: #fff;font-size: 30rpx;height:80rpx;border-radius:40rpx}
.join-btn2{width: 90%;margin:20rpx 5%;border: 2rpx solid #FF3143;color: #FF3143;font-size: 30rpx;height:80rpx;border-radius:40rpx}

.buydialog-mask{ position: fixed; top: 0px; left: 0px; width: 100%; background: rgba(0,0,0,0.5); bottom: 0px;z-index:10}
.buydialog{ position: fixed; width: 100%; left: 0px; bottom: 0px; background: #fff;z-index:11;border-radius:20rpx 20rpx 0px 0px}
.buydialog .close{ position: absolute; top: 0; right: 0;padding:20rpx;z-index:12}
.buydialog .close .image{ width: 30rpx; height:30rpx; }
.buydialog .title{ width: 94%;position: relative; margin: 0 3%; padding:20rpx 0px; border-bottom:0; height: 190rpx;}
.buydialog .title .img{ width: 160rpx; height: 160rpx; position: absolute; top: 20rpx; border-radius: 10rpx; border: 0 #e5e5e5 solid;background-color: #fff}
.buydialog .title .price{ padding-left:180rpx;width:100%;font-size: 36rpx;height:70rpx; color: #FC4343;overflow: hidden;}
.buydialog .title .price .t1{ font-size:26rpx}
.buydialog .title .price .t2{ font-size:26rpx;text-decoration:line-through;color:#aaa}
.buydialog .title .choosename{ padding-left:180rpx;width: 100%;font-size: 24rpx;height: 42rpx;line-height:42rpx;color:#888888}
.buydialog .title .stock{ padding-left:180rpx;width: 100%;font-size: 24rpx;height: 42rpx;line-height:42rpx;color:#888888}

.buydialog .guigelist{ width: 94%; position: relative; margin: 0 3%; padding:0px 0px 10px 0px; border-bottom: 0; }
.buydialog .guigelist .name{ height:70rpx; line-height: 70rpx;}
.buydialog .guigelist .item{ font-size: 30rpx;color: #333;flex-wrap:wrap}
.buydialog .guigelist .item2{ height:60rpx;line-height:60rpx;margin-bottom:4px;border:0; border-radius:4rpx; padding:0 40rpx;color:#666666; margin-right: 10rpx; font-size:26rpx;background:#F4F4F4}
.buydialog .guigelist .on{color:#FC4343;background:rgba(252,67,67,0.1);font-weight:bold}
.buydialog .buynum{ width: 94%; position: relative; margin: 0 3%; padding:10px 0px 10px 0px; }
.buydialog .addnum {font-size: 30rpx;color: #666;width:auto;display:flex;align-items:center}
.buydialog .addnum .plus {width:48rpx;height:36rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.buydialog .addnum .minus {width:48rpx;height:36rpx;background:#F6F8F7;display:flex;align-items:center;justify-content:center}
.buydialog .addnum .img{width:24rpx;height:24rpx}
.buydialog .addnum .input{flex:1;width:70rpx;border:0;text-align:center;color:#2B2B2B;font-size:24rpx}
.buydialog .op{width:90%;margin:20rpx 5%;border-radius:36rpx;overflow:hidden;display:flex;margin-top:100rpx;}
.buydialog .addcart{flex:1;height:72rpx; line-height: 72rpx;color: #fff; border-radius: 0px; border: none; font-size:28rpx;font-weight:bold}
.buydialog .tobuy{flex:1;height: 72rpx; line-height: 72rpx;color: #fff; border-radius: 0px; border: none;}
.buydialog .nostock{flex:1;height: 72rpx; line-height: 72rpx; background:#aaa; color: #fff; border-radius: 0px; border: none;}

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