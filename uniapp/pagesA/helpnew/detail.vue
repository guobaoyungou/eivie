<template>
<view>
    <block v-if="isload">
    	<view class="container">
    		<view class="header">
    			<text class="title" >{{detail.name}}</text>
    			<view class="artinfo">
    				<text class="t1">{{detail.createtime}}</text>
    				<!-- <text class="t2" v-if="detail.showauthor==1">{{detail.author}}</text> -->
    				<text class="t3" v-if="detail.showreadcount==1">阅读：{{detail.readcount}}</text>
    			</view>
    			<view style="padding:8rpx 0">
    				<dp :pagecontent="pagecontent" :richtype="richtype" :richurl="richurl"></dp>
    			</view>
    		</view>
    	</view>
    </block>
    <loading v-if="loading"></loading>
	</block>
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
      aid:app.globalData.aid,
      mid:app.globalData.mid,
      detail:[],
      datalist: [],
      pagenum: 1,
      id: 0,
      pagecontent: "",
      title: "",
      sharepic: "",
      nodata:false,
      nomore:false,
      
      richtype:0,
      richurl:'',
    };
  },
	onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
	},
	onShow:function() {
		this.setSeo();
	},
	onPullDownRefresh: function () {
		this.getdata();
	},
	onShareAppMessage:function(){
		var that = this;
		return this._sharewx({title:this.detail.name,desc:this.detail.subname,pic:this.detail.pic,callback: function() {
			that.sharecallback();
		}});
	},
	onShareTimeline:function(){
		var that = this;
		var sharewxdata = this._sharewx({title:this.detail.name,desc:this.detail.subname,pic:this.detail.pic,
		callback: function() {
			that.sharecallback();
		}});
		var query = (sharewxdata.path).split('?')[1]+'&seetype=circle';
		return {
			title: sharewxdata.title,
			imageUrl: sharewxdata.imageUrl,
			query: query
		}
	},
	onReachBottom: function () {
		if (!this.nodata && !this.nomore && this.detail.canpl==1) {
			this.pagenum = this.pagenum + 1
			this.getpllist();
		}
	},
	methods: {
	getdata:function(){
		var that = this;
		var id = that.opt.id;
		that.loading = true;
		app.get('ApiHelpnew/detail', {id: id}, function (res) {
			that.loading = false;
			if (res.status == 1){
				that.detail = res.detail;
				that.pagecontent = res.pagecontent;
				that.title = res.detail.name;
				that.sharepic = res.detail.pic;
				uni.setNavigationBarTitle({
					title: res.detail.name
				});
				// #ifdef MP-BAIDU
				if(that.detail.keywords){
					that.setSeo();
				}
				// #endif
			} else {
				app.alert(res.msg);
			}
			that.pagenum = 1;
			that.datalist = [];
			that.loaded({title:res.detail.name,desc:res.detail.subname,pic:res.detail.pic,callback: function() {
				that.sharecallback();
			}});
		});
	},
	setSeo(){
		var that =this;
		// #ifdef MP-BAIDU
		if(that.detail.keywords){
			swan.setPageInfo({
				title: that.detail.name,
				keywords: that.detail.keywords,
				description: that.detail.subname
			});
		}
		// #endif
	}
  }
};
</script>
<style>
.header{ background-color: #fff;padding: 10rpx 20rpx 0 20rpx;position: relative;display:flex;flex-direction:column;}
.header .title{width:100%;font-size: 36rpx;color:#333;line-height: 1.4;margin:10rpx 0;margin-top:20rpx;font-weight:bold}
.header .artinfo{width:100%;font-size:28rpx;color: #8c8c8c;font-style: normal;overflow: hidden;display:flex;margin:10rpx 0;}
.header .artinfo .t1{padding-right:8rpx}
.header .artinfo .t2{color:#777;padding-right:8rpx}
.header .artinfo .t3{text-align:right;flex:1;}
.header .subname{width:100%;font-size:28rpx;color: #888;border:1px dotted #ddd;border-radius:10rpx;margin:10rpx 0;padding:10rpx}


.pinglun{ width:96%;max-width:750px;margin:0 auto;position:fixed;display:flex;align-items:center;bottom:0;left:0;right:0;height:100rpx;background:#fff;z-index:10;border-top:1px solid #f7f7f7;padding:0 2%;box-sizing:content-box}
.pinglun .pinput{flex:1;color:#a5adb5;font-size:32rpx;padding:0;line-height:100rpx}
.pinglun .zan{padding:0 12rpx;line-height:100rpx}
.pinglun .zan image{width:48rpx;height:48rpx}
.pinglun .zan span{height:40rpx;line-height:50rpx;font-size:32rpx}
.pinglun .buybtn{margin-left:0.08rpx;background:#31C88E;height:72rpx;line-height:72rpx;padding:0 20rpx;color:#fff;border-radius:6rpx}

.plbox{width:100%;padding:40rpx 20rpx;background:#fff;margin-top:10px}
.plbox_title{font-size:28rpx;height:60rpx;line-height:60rpx;margin-bottom:20rpx}
.plbox_title .t1{color:#000;font-weight:bold}
.plbox_content .plcontent{vertical-align: middle;color:#111}
.plbox_content .plcontent image{ width:44rpx;height:44rpx;vertical-align: inherit;}
.plbox_content .item1{width:100%;margin-bottom:20rpx}
.plbox_content .item1 .f1{width:80rpx;}
.plbox_content .item1 .f1 image{width:60rpx;height:60rpx;border-radius:50%}
.plbox_content .item1 .f2{flex:1}
.plbox_content .item1 .f2 .t1{}
.plbox_content .item1 .f2 .t2{color:#000;margin:10rpx 0;line-height:60rpx;}
.plbox_content .item1 .f2 .t3{color:#999;font-size:20rpx}
.plbox_content .item1 .f2 .pzan image{width:32rpx;height:32rpx;margin-right:2px}
.plbox_content .item1 .f2 .phuifu{margin-left:6px;color:#507DAF}
.plbox_content .relist{width:100%;background:#f5f5f5;padding:4rpx 20rpx;margin-bottom:20rpx}
.plbox_content .relist .item2{font-size:24rpx;margin-bottom:10rpx}
.copyright{display:none}
.reward_typel{width: 150rpx;display: inline-block;border-radius: 8rpx 0 0 8rpx;}
.reward_typer{width: 150rpx;display: inline-block;border-radius: 0rpx 8rpx 8rpx 0rpx;}
.reward_content{width: 180rpx;border-radius: 8rpx;border: 2rpx solid #FA5151;float: left;margin-top: 20rpx;overflow: hidden;white-space: nowrap;}
.reward_num{background-color:#FA5151;color:#fff}
.reward_num_type{width:200rpx;margin:20rpx auto;height:60rpx;line-height: 60rpx;text-align: center;color: #536084;overflow: hidden;}
.zybox{background: #fff;width: 100%;padding: 0rpx 20rpx 20rpx 20rpx;margin-top: 25rpx;}
.zy_title{font-size: 34rpx;padding: 30rpx 0;}
.zy_title .zy_tip{font-size: 28rpx; color: #C0C0C0;margin-left: 20rpx;}
.zy_list{display: flex;height: 100rpx;border-bottom: 1px solid #EEEEEE;}
.zy_left{flex: 1;}
.zy_list .zy_image { width: 60rpx;height: 60rpx;}
.zy_list .zy_content{font-size: 26rpx;flex: 7;word-break: break-all}
.tobuy{height: 60rpx;line-height: 60rpx;color: #FFFFFF;border-radius: 32rpx;margin-left: 20rpx;flex-shrink: 0;padding: 0 50rpx;font-size: 24rpx; font-weight: bold;}
.btn_yl{height: 37rpx;line-height: 33rpx;color: #03a9f4;border: 1px solid #03a9f4;border-radius: 32rpx;padding: 0 15rpx;font-size: 24rpx;}
.suo_image{ width: 40rpx; height: 40rpx;flex: 1;}

</style>