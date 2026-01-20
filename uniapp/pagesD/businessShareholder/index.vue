<template>
<view class="container">
	<block v-if="isload">
    <view style="width: 720rpx;margin: 0 auto;margin-bottom: 20rpx;">
      <view v-for="(item,index) in banks" :key="index" style="padding:10rpx 20rpx;border: 2rpx solid #f1f1f1;border-radius: 12rpx 12rpx;line-height: 50rpx;margin-top: 20rpx;">
        <view v-if="item.type == 0">
          <view>银行名称：{{item.bankname}}</view>
          <view>户名：{{item.bankcarduser}}</view>
          <view style="display: flex;">
            <view>账户：{{item.bankcardnum}}</view>
            <view @tap="fuzhi" :data-content="item.bankcardnum" style="color: #fff;background-color: red;padding: 0 20rpx;border-radius: 12rpx 12rpx;margin-left: 20rpx;">复制</view>
          </view>
        </view>
        <view v-else style="display: flex;align-items: center;justify-content: space-between;">
          <view>{{item.ewmname}}：{{item.ewmcardnum || ''}}</view>
          <view style="height: 100rpx;"><image :src="item.ewmpic" @tap="previewImage" :data-url="item.ewmpic" mode="heightFix" style="height: 100%"></image></view>
        </view>
      </view>
    </view>
		<form @submit="formSubmit">
			<view class="form">
        <view v-if="id == 0">
          <view  class="form-item">
          	<view class="label">选择门店类型</view>
          	<picker @change="pickerShareholder" :value="shareholderindex" :range="shareholders" range-key="name" style="width: 100%;">
          	  <view style="display: flex;justify-content:space-between;background-color:#f1f1f1;height: 70rpx;line-height: 70rpx;padding: 0 20rpx;align-items: center;">
          	    <view>{{shareholders[shareholderindex]['name']}}</view>
          	    <image style="width: 40rpx;height: 40rpx;" :src="pre_url+'/static/img/arrowdown.png'"></image>
          	  </view>
          	</picker>
          </view>
          <view class="form-item">
          	<view class="label">需要支付金额</view>
          	<input class="input" type="text" :value="shareholders[shareholderindex]['price']+'元'" disabled placeholder-style="font-size:28rpx;color:#BBBBBB" ></input>
          </view>
        </view>
        <view v-else>
          <view class="form-item">
          	<view class="label">入股份额(剩余份额：{{record.needjoinnum}}份)</view>
          	<input class="input" type="number" name="num" @input="inputNum" :value="num" placeholder-style="font-size:28rpx;color:#BBBBBB" ></input>
          </view>
          
          <view class="form-item">
          	<view class="label">需要支付金额</view>
          	<input class="input" type="text" name="totalprice" :value="totalprice" disabled placeholder-style="font-size:28rpx;color:#BBBBBB" ></input>
          </view>
        </view>
        
        <view v-if="!opttype" class="form-item">
        	<view class="label">上传打款凭证</view>
        	<view style="flex-wrap:wrap;display: flex;">
        	  <view v-for="(item, index) in pics" :key="index" class="layui-imgbox">
        	    <view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="pics"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
        	    <view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
        	  </view>
        	  <view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pics" data-pernum="9" v-if="pics.length<3"></view>
        	</view>
        	<input type="text" hidden="true" name="pics" :value="pics.join(',')" maxlength="-1"></input>
        </view>
			</view>
      <view v-if="!opttype">
        <button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit">提交</button>
        
        <button class="savebtn" @tap="goto" data-url="/pagesD/businessShareholder/applylog" :style="'border:2rpx solid '+t('color1')+';color:'+t('color1')" >查看申请记录</button>
      </view>
      <view v-else-if="opttype == 'share' && !sharetypevisible">
          <button @tap="shareClick" class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'">点击分享</button>
      </view>
		</form>
	</block>
  
  <view class="posterDialog schemeDialog" v-if="showScheme">
  	<view class="main">
  		<view class="close" @tap="schemeDialogClose"><image class="img" :src="pre_url+'/static/img/close.png'"/></view>
  		<view class="schemecon">
  			<view style="line-height: 30rpx;text-align:center;padding: 10rpx;">{{schemetitle}}</view>
  			<view >邀请链接：<text style="color: #00A0E9;word-break:break-all">{{schemeurl}}</text></view>
  			<view class="copybtn" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  @tap.stop="copy" :data-text="'邀请链接：'+schemeurl"> 一键复制 </view>
  		</view>
  	</view>
  </view>
  <view v-if="sharetypevisible" class="popup__container">
  	<view class="popup__overlay" @tap.stop="handleClickMask"></view>
  	<view class="popup__modal" style="height:320rpx;min-height:320rpx">
  		<view class="popup__content">
  			<view class="sharetypecontent">
  				<!-- #ifdef APP -->
  				<view class="f1" @tap="shareapp">
  					<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
  					<text class="t1">分享给好友</text>
  				</view>
  				<!-- #endif -->
  				<!-- #ifdef H5 -->
  				<view class="f1" @tap="sharemp" v-if="getplatform() == 'mp'">
  					<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
  					<text class="t1">分享给好友</text>
  				</view>
          <view class="f1" @tap="shareh5" v-if="getplatform() == 'h5'">
          	<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
          	<text class="t1">分享给好友</text>
          </view>
  				<!-- #endif -->
  				<!-- #ifndef H5 -->
  				<button @tap="hideshow" class="f1" open-type="share" v-if="getplatform() != 'h5'">
  					<image class="img" :src="pre_url+'/static/img/sharefriends.png'"/>
  					<text class="t1">分享给好友</text>
  				</button>
  				<!-- #endif -->
  			</view>
  		</view>
  	</view>
  </view>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
	<wxxieyi></wxxieyi>
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
      
      opttype:'',
      type:0,
			id:0,
      set:{},
      banks:[],
      //发起众筹门店类型信息
      shareholderindex:0,
      shareholders:[],
      //申请入股记录信息
      num:1,
      record:[],
      totalprice:0,
      
      pics:[],
      
      //分享
      pid:0,
      sharetypevisible:false,
      xcx_scheme:false,
      showScheme:false,
      schemeurl:'',
      schemetitle:'分享链接',
      
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
    this.type = this.opt.type || 0;
		this.opttype = this.opt.opttype || '';
    this.id = this.opt.id || 0;
    if(this.type==1 || this.id >0){
      uni.setNavigationBarTitle({
          title: '参与门店投资'
      });
    }else{
      uni.setNavigationBarTitle({
          title: '发起门店投资'
      });
    }
    
    this.pid = this.opt.pid || 0;
    
    //#ifdef H5
    var pid = this.opt.pid || app.globalData.mid;
    var linkurl = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesD/businessShareholder/index?scene=id_'+this.id+'-pid_' + pid;
    this._sharemp({link:linkurl});
    //#endif

    this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
	onShareAppMessage:function(){
		var pid = this.opt.pid || app.globalData.mid;
		var linkurl = '/pagesD/businessShareholder/index?scene=id_'+this.id+'-pid_' + pid;
		return this._sharewx({link:linkurl});
	},
	onShareTimeline:function(){
		var pid = this.opt.pid || app.globalData.mid;
		var linkurl = '/pagesD/businessShareholder/index?scene=id_'+this.id+'-pid_' + pid;
		 this._sharewx({link:linkurl});
	},
  methods: {
		getdata:function(){
			var that = this;
      that.loading = true;
      app.post('ApiBusinessShareholder/index', {type: that.type,type2:this.opt.type,id: that.id}, function (res) {
        that.loading = false;
        if(res.status == 1){
          that.set = res.set;
          that.banks = res.banks;
          that.shareholders = res.shareholders;
          that.record = res.record;
          if(that.type==1 || that.id >0){
            that.type=1;
            that.id = res.record.id;
            var totalprice = res.record.joineveryprice;
            that.totalprice = Math.round(totalprice*100)/100;
          } 
          that.loaded();
        }else{
          app.alert(res.msg);
        }
      });
		},
    fuzhi:function(e){
      var content = e.currentTarget.dataset.content
    	if(!content){
        app.success('无内容可复制');
    		return;
    	}
    	var that = this;
    	uni.setClipboardData({
    		data: content,
    		success: function (res) {
    			app.success('复制成功');
    		},
    		fail:function(res){
    			app.error('复制失败')
    		}
    	});
    },
    pickerShareholder: function(e) {
      var that = this;
      var shareholderindex  = e.detail.value
      that.shareholderindex = shareholderindex;
    },
    inputNum:function(e){
      var that = this;
      var num = e.detail.value;
      if(num> that.record.joineverynum){
        that.num = that.record.joineverynum;
        app.error('份额超出');return;
      }
      that.num = num;
      var totalprice = that.record.joineveryprice * num;
      that.totalprice = Math.round(totalprice*100)/100;
    },
    formSubmit: function (e) {
      var that = this;
      var formdata = e.detail.value;
      var pics = formdata.pics;
      if(!pics){
        app.error('请上传凭证');
        return;
      }
      
      var id,shid,num = 0;
      if(that.id == 0){
          var shid = that.shareholders[that.shareholderindex]['id'];
      }else{
          var id = that.id;
          var num = formdata.num;
            
          if(num<=0){
            app.error('请选择份额');return;
          }
          if(num> that.record.joineverynum){
            app.error('份额超出');return;
          }
      }
      
      app.confirm('确定提交？',function(){
        that.loading = true;
        app.post('ApiBusinessShareholder/apply', {type: that.type,pics:pics,id:id,num:num,shid:shid}, function (res) {
          that.loading = false;
          if(res.status == 1){
            app.success(res.msg);
            setTimeout(function(){
              var st = 0;
              if(that.id) st = 1;
              app.goto('/pagesD/businessShareholder/applylog?st='+st,'redirect');
            },1000)
          }else{
            app.alert(res.msg);
          }
        });
      })
      
    },
    uppicsDialogClose:function(){
      this.$refs.uppicsDialog.close();
    },
    uploadimg:function(e){
    	var that = this;
    	var pernum = parseInt(e.currentTarget.dataset.pernum);
    	if(!pernum) pernum = 1;
    	var field= e.currentTarget.dataset.field
    	var pics = that[field]
    	if(!pics) pics = [];
    	app.chooseImage(function(urls){
    		for(var i=0;i<urls.length;i++){
    			pics.push(urls[i]);
    		}
    		if(field == 'pic') that.pic = pics;
    		if(field == 'pics') that.pics = pics;
    	},pernum);
    },
    removeimg:function(e){
    	var that = this;
    	var index= e.currentTarget.dataset.index
    	var field= e.currentTarget.dataset.field
    	if(field == 'pic'){
    		var pics = that.pic
    		pics.splice(index,1);
    		that.pic = pics;
    	}else if(field == 'pics'){
    		var pics = that.pics
    		pics.splice(index,1);
    		that.pics = pics;
    	}
    },
    shareClick: function () {
      //#ifdef H5
      if(this.getplatform() == 'mp'){
        app.error('可点击右上角发送给好友或分享到朋友圈');
        return;
      }
      //#endif
    	this.sharetypevisible = true;
    },
    handleClickMask: function () {
    	this.sharetypevisible = false
    },
    sharemp:function(){
    	app.error('可点击右上角发送给好友或分享到朋友圈');
    	// this.sharetypevisible = false;
     // this.showScheme = true;
     // this.schemeurl  = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesD/businessShareholder/index?scene=id_'+this.id+'-pid_' + this.pid;
     // this.schemetitle= '复制链接 或者 点击右上角发送给好友或分享到朋友圈'
    },
    shareh5:function(){
     this.showScheme = true;
     this.schemeurl  = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesD/businessShareholder/index?scene=id_'+this.id+'-pid_' + this.pid;
    },
    shareapp:function(){
    	// #ifdef APP
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
    				sharedata.title = that.product.sharetitle || that.product.name;
    				sharedata.summary = that.product.sharedesc || that.product.sellpoint;
    				sharedata.href = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pagesD/businessShareholder/index?scene=scene=id_'+that.id+'-pid_' + that.pid;
    				sharedata.imageUrl = that.product.pic;
    				var sharelist = app.globalData.initdata.sharelist;
    				if(sharelist){
    					for(var i=0;i<sharelist.length;i++){
    						if(sharelist[i]['indexurl'] == '/pagesD/businessShareholder/index'){
    							sharedata.title = sharelist[i].title;
    							sharedata.summary = sharelist[i].desc;
    							sharedata.imageUrl = sharelist[i].pic;
    							if(sharelist[i].url){
    								var sharelink = sharelist[i].url;
    								if(sharelink.indexOf('/') === 0){
    									sharelink = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#'+ sharelink;
    								}
                    if(that.id>0){
                    	 sharelink += (sharelink.indexOf('?') === -1 ? '?' : '&') + 'id='+that.id;
                    }
    								if(that.pid>0){
    									 sharelink += (sharelink.indexOf('?') === -1 ? '?' : '&') + 'pid='+that.pid;
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
    	// #endif
    },
    shareScheme: function () {
    	var that = this;
    	app.showLoading();
    	app.post('ApiShopwindow/getwxScheme', {pid: that.pid}, function (data) {
    		app.showLoading(false);
    		if (data.status == 0) {
    			app.alert(data.msg);
    		} else {
    				that.showScheme = true;
    				that.schemeurl=data.openlink
    		}
    	});
    },
    schemeDialogClose: function () {
    	this.showScheme = false;
      this.schemeurl  ='';
    },
  }
};
</script>
<style>
page{background-color: #fff;}
.container{display:flex;flex-direction:column}
.form{ width: 720rpx;margin:0 auto;background: #FFF;}
.form-item{dwidth:100%;margin-bottom: 20rpx;}
.form-item:last-child{border:0}
.form-item .label{ color:#333;font-weight:normal;line-height: 70rpx; text-align:left;}
.form-item .input{ height: 70rpx; line-height: 70rpx;padding:0 20rpx ;background-color: #f1f1f1;}
.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none; }

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}

.schemeDialog {background:rgba(0,0,0,0.4);z-index:12;}
.schemeDialog .main{ position: absolute;top:30%}
.schemecon{padding: 40rpx 30rpx; }
.copybtn{ text-align: center; margin-top: 30rpx; padding:15rpx 20rpx; border-radius: 50rpx; color:#fff}
</style>