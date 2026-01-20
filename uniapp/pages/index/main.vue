<template>
<view class="container" :style="{backgroundColor:pageinfo.bgcolor}" v-if="isload">
	<block>
		<dp :pagecontent="pagecontent" :menuindex="menuindex" :latitude="latitude" :longitude="longitude" @getdata="getdata" :htsignatureurl="htsignatureurl"></dp>
		<dp-guanggao :guanggaopic="guanggaopic" :guanggaourl="guanggaourl" :guanggaotype="guanggaotype" :param="guanggaoparam"></dp-guanggao>
		<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
		<dp-sharegive v-if="isshare" :sharegive="sharegive"  @toshare="toshare" ></dp-sharegive>
	</block>
	<popmsg ref="popmsg"></popmsg>
	<loading v-if="loading"></loading>
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
			pre_url: app.globalData.pre_url,
			id: 0,
			pageinfo: [],
			pagecontent: [],
			title: "",
			oglist: "", 
			guanggaopic: "",
			guanggaourl: "",
			guanggaotype: "1",
			guanggaoparam:{},
			latitude:'',
			longitude:'',
			area:'',
			mendianid:0,
			
			sysset:{},
			showlevel:2,
			show_location:0,
			curent_address:'',//当前位置: 城市或者收货地址
			arealist:[],
			show_nearbyarea:false,
			ischangeaddress:false,
			nearbyplacelist:[],
			myaddresslist:[],
			isshowalladdress:false,
			placekeyword:'',
			suggestionplacelist:[],
			sharegive:[],
			giveid:0,
			isshare:false,
			isend:false,
			fzcode:'',//活码code，用于分账
      htsignatureurl:''
		}
	},
	onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
	},
	onPullDownRefresh:function(e){
		this.isload = false;
		this.getdata();
	},
	onShareAppMessage:function(e){

		if(e.target){
			var id = e.target.dataset.id;
			var isanswer =  e.target.dataset.isanswer;
			if(id && !isanswer){
				app.error('请先回答问题');return;
			}
			var callback=e.target.dataset.callback
			if(callback){
				var that=this;
				that.giveid = e.target.dataset.id;
				return this._sharewx({title:this.title,callback:function(){that.sharecallback();}});
			}else{
				return this._sharewx({title:this.title});
			}
		}else{
			return this._sharewx();
		}	
	},
	onShareTimeline:function(){
		var sharewxdata = this._sharewx({title:this.title});
		var query = (sharewxdata.path).split('?')[1]+'&seetype=circle';
		console.log(sharewxdata)
		console.log(query)
		return {
			title: sharewxdata.title,
			imageUrl: sharewxdata.imageUrl,
			query: query
		}
	},
	onPageScroll: function (e) {
		uni.$emit('onPageScroll',e);
	},
	onShow() {
		if(app.globalData.platform=='wx' && app.globalData.hide_home_button==1){
		  uni.hideHomeButton();
		}
	},
	methods: {
		getdata:function(){
			var that = this;
			var opt = this.opt
			var id = 0;
			if (opt && opt.id) {
			  id = opt.id;
			}
			//读全局缓存的地区信息
			var locationCache =  app.getLocationCache();
			if(locationCache){
				if(locationCache.latitude){
					this.latitude = locationCache.latitude
					this.longitude = locationCache.longitude
				}
				if(locationCache.area){
					this.area = locationCache.area
					this.curent_address = locationCache.address
				}
				if(locationCache.mendian_id){
					this.mendianid = locationCache.mendian_id
				}
			}
			that.loading = true;
			app.get('ApiIndex/index', {id: id,latitude:that.latitude,longitude:that.longitude,area:that.area,mendian_id:that.mendianid,mode:that.opt.mode?that.opt.mode:'',deviceid:that.opt.deviceid,fzcode:this.opt.fzcode}, function (data) {
				that.loading = false;
				that.isload = true;
				uni.stopPullDownRefresh();
			  if (data.status == 2) {
			    //付费查看
			    app.goto('/pagesExt/pay/pay?id='+data.payorderid, 'redirect');
			    return;
			  }
			  if (data.status == 1) {
			    var pagecontent = data.pagecontent;
					that.title = data.pageinfo.title;
					that.oglist = data.oglist;
					that.guanggaopic = data.guanggaopic;
					that.guanggaourl = data.guanggaourl;
					that.guanggaotype = data.guanggaotype;
					that.guanggaoparam = data.guanggaoparam;
					that.pageinfo = data.pageinfo;
					that.pagecontent = data.pagecontent;
					that.sharegive = data.sharegive;
					that.isshare = data.isshare;
					that.isend = data.isend;
					if(data.isend){
						app.alert('活动已结束')	
					}
			    uni.setNavigationBarTitle({
			      title: data.pageinfo.title
			    });
					that.loaded({title:that.title});
					if(that.latitude=='' && that.longitude=='' && data.needlocation){
						app.getLocation(function (res) {
							that.latitude = res.latitude;
							that.longitude = res.longitude;
							that.getdata();
						});
					}
			  } else {
			    if (data.msg) {
			      app.alert(data.msg, function () {
			        if (data.url) app.goto(data.url);
			      });
			    } else if (data.url) {
			      app.goto(data.url);
			    } else {
			      app.alert('您无查看权限');
			    }
			  }
			});
		},
    hidehtqm:function (signatureurl){
      console.log(signatureurl)
      this.htsignatureurl = signatureurl ?? '';
    },
		sharecallback:function(){
			var that = this;
			app.post("ApiShareGive/give", {giveid:that.giveid}, function (res) {
				if (res.status == 1) {
					app.alert(res.msg);
				} else if (res.status == 0) {
					app.alert(res.msg);
				}
			});
		},
		toshare:function(e){
			var that=this
			console.log(this.opt.id)
			var giveid = e.giveid
			that.giveid = giveid
			var platform = app.getplatform()
			if(platform == 'mp' || platform == 'h5'){
				this._sharemp({title:this.title,callback:function(){that.sharecallback();}})
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
							sharedata.title = this.title;
							sharedata.summary = '';
							sharedata.href = app.globalData.pre_url +'/h5/'+app.globalData.aid+'.html#/pages/index/main?main=id_'+that.opt.id;
							sharedata.imageUrl = '';
							uni.share(sharedata);
						}
					}
				});
			}else{
				app.error('该终端不支持此操作');
			}
		}
	}
}
</script>
<style>
	.container{width: 100%;min-height: 100vh;overflow: hidden;}
</style>