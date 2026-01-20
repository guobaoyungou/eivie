<template>
<view class="container">
	<block v-if="isload">
		<view v-for="(item, index) in datalist" :key="index" class="content flex" @tap.stop="setdefault" :data-id="item.id" :data-realname="item.realname" :data-tel="item.tel" :data-yystatus='item.yystatus'>
			<view class="f1">
				<view class="headimg">
					<image v-if="item.headimg" :src="item.headimg"  />
					<image v-else :src="pre_url+'/static/img/touxiang.png'"/>
				</view>
				<view class="text1">	
					<text class="t1">{{item.realname}} </text>
					<text class="t2" v-if="item.typename">{{item.typename}}</text>
					<view class="text2">{{item.jineng}}</view>
				</view>
				
			</view>
			<view :class="'yuyue '+ (item.yystatus==-1?'hui':'')" >预约</view>
		</view>
		<nodata v-if="nodata"></nodata>
		<view style="height:140rpx"></view>
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
			pre_url:app.globalData.pre_url,
			loading:false,
      isload: false,
			menuindex:-1,

      datalist: [],
      type: "",
			keyword:'',
			nodata:false,
			sindex:'',
			linkman:'',
      prodata:'',
      setstatus:false,
      gotype: 1,//选择人员后操作 默认1、去购买页面 2、返回上一层
    }
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.yydate = opt.yydate
		this.prodata = opt.prodata;
		this.type = this.opt.type || '';
		this.sindex = opt.sindex;
		this.linkman = opt.linkman;
		this.tel = opt.tel;
    this.gotype = this.opt.gotype || 1;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			that.nodata = false;
			app.get('ApiYuyue/selectpeople', {yydate:that.yydate,keyword:that.keyword,prodata:that.prodata}, function (res) {
				that.loading = false;
				var datalist = res.data;
				that.datalist = datalist;
				if (datalist.length == 0) {
					that.nodata = true;
				}
				that.loaded();
			});
		},
    //选择人员
    setdefault: function (e) {
      var that = this;
      var fromPage = this.opt.fromPage;
      var Id = e.currentTarget.dataset.id;
      var realname = e.currentTarget.dataset.realname;
      var tel = e.currentTarget.dataset.tel;
			if(e.currentTarget.dataset.yystatus==-1){
				app.error('此时间不可预约');return
			}
      if(that.setstatus){
        return
      }
      that.setstatus = true;
      if(that.gotype == 1){
        app.goto('/activity/yuyue/buy?prodata='+that.prodata+'&worker_id=' + Id+'&sindex='+that.sindex+'&linkman='+that.linkman+'&tel='+that.tel);
      }else if(that.gotype == 2){
        setTimeout(function() {
            let pages = getCurrentPages();
            if (pages.length >= 2) {
                //let curPage = pages[pages.length - 1]; // 当前页面
                let prePage = pages[pages.length - 2]; // 上一页面
                prePage.$vm.workerid  = Id;
                prePage.$vm.realname  = realname;
                prePage.$vm.tel       = tel;
                uni.navigateBack();
            }
        }, 600);
      }
    },
  }
};
</script>
<style>
.topsearch{width:94%;margin:16rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}

.content{width:94%;margin:20rpx 3%;background:#fff;border-radius:5px;padding:20rpx 40rpx; justify-content: space-between;}
.content .f1{display:flex;align-items:center}
.content .f1 image{ width: 80rpx; height: 80rpx;}
.content .f1 .t1{color:#2B2B2B;font-weight:bold;font-size:30rpx;margin-left:10rpx;}
.content .f1 .t2{color:#999999;font-size:28rpx; background: #E8E8F7;color:#7A83EC; margin-left: 10rpx; padding:3rpx 20rpx; font-size: 20rpx; border-radius: 18rpx;}
.content .f1 .t3{ margin-left:10rpx;display: block; height: 40rpx;line-height: 40rpx}
.content .f2{color:#2b2b2b;font-size:26rpx;line-height:42rpx;padding-bottom:20rpx;}
.content .f3{height:96rpx;display:flex;align-items:center}
.content .radio{flex-shrink:0;width: 36rpx;height: 36rpx;background: #FFFFFF;border: 3rpx solid #BFBFBF;border-radius: 50%;}
.content .radio .radio-img{width:100%;height:100%}
.content .mrtxt{color:#2B2B2B;font-size:26rpx;margin-left:10rpx}


.text2{ margin-left: 10rpx; color:#999999; font-size: 20rpx;}
.yuyue{ background: #7A83EC; height: 40rpx; line-height: 40rpx; padding: 0 10rpx; color:#fff; border-radius:28rpx; width: 80rpx; font-size: 20rpx; text-align: center; margin-top: 20rpx;}
.yuyue.hui{  background: #bbb; }
.container .btn-add{width:90%;max-width:700px;margin:0 auto;height:96rpx;line-height:96rpx;text-align:center;color:#fff;font-size:30rpx;font-weight:bold;border-radius:40rpx;position: fixed;left:0px;right:0;bottom:20rpx;}
</style>