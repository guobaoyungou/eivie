<template>
<view class="container">
	<block v-if="isload">
		<view v-for="(item, index) in datalist" :key="index" class="content flex" @tap.stop="changeradio" :data-id="item.id"  :data-index="index" :data-yystatus='item.yystatus'>
			
			<view class="btitle">
				<view class="radio" :style="sindex==index ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
			</view>
			<view class="f1">
				<view class="headimg"><image :src="item.headimg" /></view>
				<view class="text1">	
					<text class="t1">{{item.realname}} </text>
					<text class="t2" v-if="item.typename">{{item.typename}}</text>
					<view class="tags">
						<text class="t3"  v-if="item.citys">{{item.citys}}</text> 
						<text class="t3" v-if="item.age"> {{item.age}}岁</text> 
						<text class="t3">评分{{item.comment_score}}</text> 
			
					</view>
					<view class="f3">
						<text class="juli">距离<text class="t4">{{item.juli}}</text>{{item.juli_unit}}</text>
						<image :src="pre_url+'/static/img/tel2.png'"  class="tel" @tap.stop="goto" :data-url="'tel::'+item.tel"/>	</view>	
				</view>
		
			</view>
				
		</view>
		<view class="bottom"  v-if="fwname"><view class="btn" @tap="confirm" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" >确定指派<text v-if="fwname">{{fwname}}</text></view></view>
		<nodata v-if="nodata"></nodata>
		<nomore v-if="nomore"></nomore>
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
			loading:false,
      isload: false,
			menuindex:-1,
      datalist: [],
      type: "",
			keyword:'',
			nodata:false,
			sindex:'-1',
			linkman:'',
			fwname:'',
			workerid:0,
			pagenum: 1,
			nomore: false,
			pre_url:app.globalData.pre_url,
    }
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.id = opt.id
		this.type = opt.type
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
  methods: {
		getdata:function(loadmore){
			var that = this;
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
			var pagenum = that.pagenum;
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
			app.get('ApiAdminOrder/selectworker', {id:that.id,pagenum: pagenum}, function (res) {
				that.loading = false;
				var data = res.data;
			
				if (pagenum == 1) {
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

			});
		},
    //选择人员
		changeradio: function (e) {
		  var that = this;
		  var index = e.currentTarget.dataset.index;
			this.sindex = index
			that.workerid = that.datalist[index].id
			that.fwname = that.datalist[index].realname
		},
		confirm:function(e){
			var that=this
			if(!that.id || !that.fwname ) {
				 app.error('请选择服务人员');return;
			}
			app.showLoading('提交中');
			var othertype = this.type;
			app.post('ApiAdminOrder/yuyuepeisong', { type:'yuyue_order',orderid: that.id,worker_id:that.workerid,othertype:othertype}, function (res) {
				if(res.status==1){
					that.loading = false;
					app.success(res.msg);
					setTimeout(function () {
						app.goback()
					}, 1000)
				}else{
					app.error(res.msg);
				}
				
			})
		}
  }
};
</script>
<style>
.content{width:94%;margin:20rpx 3%;background:#fff;border-radius:5px;padding:20rpx;}
.content .f1{display:flex; width: 100%;}
.content .f1 image{ width:120rpx; height: 120rpx;border-radius: 10rpx; margin: 0 20rpx;}
.content .f1 .text1{ width: 100%;}
.content .f1 .t1{color:#2B2B2B;font-weight:bold;font-size:30rpx;margin-left:10rpx;}
.content .f1 .t2{color:#999999;font-size:28rpx; background: #ECF5F3;color:#6DBCC9;  margin-left: 10rpx; padding:3rpx 20rpx; font-size: 20rpx; border-radius: 18rpx;}
.content .btitle{ display: flex; align-items: center;}
.content .radio{flex-shrink:0;width: 36rpx;height: 36rpx;background: #FFFFFF;border: 3rpx solid #BFBFBF;border-radius: 50%;}
.content .radio .radio-img{width:100%;height:100%}
.content .tags { margin: 10rpx 0;}
.content .tags .t3{ font-size: 20rpx;; background: #ECF5F3;color:#6DBCC9; margin:10rpx; padding: 5rpx 10rpx; }
.content .juli{ margin-top: 10rpx; font-size: 20rpx; margin-left: 10rpx;}
.content .juli .t4{ color: red;}
.content .f3{ display: flex; justify-content: space-between; width: 100%;}
.content .f3 image{ width:40rpx; height: 40rpx;}

.bottom{ position: fixed; bottom: 0; background: #fff; width: 100%;}
.btn{ border:0;height:80rpx;line-height:80rpx;margin:20rpx auto;border-radius:6rpx;color:#fff; width: 90%; text-align: center; }
</style>