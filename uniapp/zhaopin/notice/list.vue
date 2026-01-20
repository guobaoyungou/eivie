<template>
<view class="container">
	<block v-if="isload">
		<view class="search-container">
			<dd-tab :itemdata="['未查看','已查看']" :itemst="['0','1']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
		</view>
		<view class="main">
			<view class="box" v-for="(item,index) in datalist" :key="index" @tap="godetail" :data-id="item.id" :data-zid="item.zhaopin_id" :data-qid="item.qiuzhi_id">
				<view class="flex-s">
					<view class="pic">
						<image :src="item.pic"></image>
					</view>
					<view class="content">
						<view class="title">{{item.title}}</view>
						<view class="tips">{{item.desc}}</view>
						<view class="time">{{item.createtime}}</view>
					</view>
				</view>
				<view class="flex-e option">
					<view @tap.stop="chat" :data-id="item.id" :data-mid="item.mid" :data-zid="item.zhaopin_id" :data-qid="item.qiuzhi_id"  class="btn" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)',color:'#ffffff'}">联系他</view>
					<block v-if="type==2">
						<view class="btn btn1" v-if="item.record.status==0" @tap.stop="confirm" :data-zpid="item.zhaopin_id" :data-qzid="item.qiuzhi_id">确定应聘</view>
						<view class="btn btn1" v-if="item.record.status==1" @tap.stop="recordOption" data-type="2" :data-recordid="item.record.id">去面试</view>
						<view class="btn btn1" v-if="item.record.status==2" @tap.stop="recordOption" data-type="3" :data-recordid="item.record.id">确定入职</view>
						<view class="btn btn1" v-if="item.record.status==3 && item.record.contract_status==1" @tap.stop="goto" :data-url="'/zhaopin/zhaopin/recorddetail?type=1&id='+item.record.id">查看合同</view>
						<view class="btn btn2" v-if="item.record.status==3">已入职</view>
					</block>
					<block v-if="type==1">
						<view class="btn btn1" v-if="item.record.status==3 && (item.record.contract_status==0 || item.record.contract_status==2)" @tap.stop="goto" :data-url="'/zhaopin/zhaopin/recorddetail?type=2&id='+item.record.id">上传合同</view>
						<view class="btn btn2" v-if="item.record.contract_status==3">合同已审核</view>
					</block>
				</view>
			</view>
		</view>
	</block>
	<loading v-if="loading"></loading>
	<nomore text="没有更多信息了" v-if="nomore"></nomore>
	<nodata text="没有查找到相关信息" v-if="nodata"></nodata>
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
			pagenum:1,
			nomore:false,
			nodata:false,
			type:0,
			st:0,
			datalist:[]
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.type = this.opt.type ? this.opt.type : 1;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getlist(false);
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getlist(true);
    }
  },
  methods: {
		getdata:function(){
			var that = this;
			that.getlist(false);
			that.loaded();
		},
		changetab: function (st) {
		    this.pagenum = 1;
		    this.st = st;
				this.nodata = false;
				this.nomore = false;
		    this.datalist = [];
		    uni.pageScrollTo({
		      scrollTop: 0,
		      duration: 0
		    });
		    this.getlist(false);
		},
		getlist: function (loadmore) {
		  var that = this;
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
		  var pagenum = that.pagenum;
		  var keyword = that.keyword;
		  app.post('ApiZhaopin/noticeList',{
				pagenum: pagenum,
				isread:that.st,
				type:that.type
			}, function (res) {
				that.loading = false;
		    var data = res.data;
		    if (pagenum == 1) {
		      that.datalist = data;
		      if (data.length == 0) {
		        that.nodata = true;
		      }
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
		godetail:function(e){
			var that = this;
			var qiuzhi_id = e.currentTarget.dataset.qid;
			var zhaopin_id = e.currentTarget.dataset.zid;
			var id = e.currentTarget.dataset.id;
			var url = '';
			app.post('ApiZhaopin/noticeSee',{id:id},function(res){
				if(that.type==1){
					 url = '/zhaopin/qiuzhi/detail?id='+qiuzhi_id
				}else if(that.type==2){
					 url = '/zhaopin/zhaopin/detail?id='+zhaopin_id
				}
				if(url){
					app.goto(url,'redirect')
				}
			})
		},
		chat:function(e){
			var that = this;
			var qiuzhi_id = e.currentTarget.dataset.qid;
			var zhaopin_id = e.currentTarget.dataset.zid;
			var mid = e.currentTarget.dataset.mid;
			var id = e.currentTarget.dataset.id;
			var url = '';
			if(that.type==1){
				 url = '/zhaopin/zhaopin/chat?type=2&id='+zhaopin_id+'&tomid='+mid+'&sid='+id
			}else if(that.type==2){
				 url = '/zhaopin/qiuzhi/chat?type=1&id='+qiuzhi_id+'&tomid='+mid+'&sid='+id
			}
			if(url){
					app.goto(url,'redirect')
			}
		},
		confirm:function(e){
			var that = this 
			var id = e.currentTarget.dataset.zpid
			var id1 = e.currentTarget.dataset.qzid
			app.confirm('确定应聘吗？',function(){
				app.post('ApiZhaopin/zhaopinConfirm',{id:id,id1:id1}, function (res) {
					that.loading = false;
					if(res.status==1){
						that.getlist()
					}else{
						app.alert(res.msg)
					}
				});
			})
		},
		recordOption:function(e){
			var that = this 
			var id = e.currentTarget.dataset.recordid
			var type = e.currentTarget.dataset.type
			var tip = '确定操作吗？'
			if(type==2){
				tip = '确定去面试吗？'
			}else if(type==3){
				tip = '确定入职吗？'
			}
			app.confirm(tip,function(){
				app.post('ApiZhaopin/zhaopinRecordOption',{id:id,type:type}, function (res) {
					that.loading = false;
					if(res.status==1){
						that.getlist()
					}else{
						app.alert(res.msg)
					}
				});
			})
		},
  }
};
</script>
<style>
	@import "../common.css";
	.container{padding: 0;}
	.main{margin-top: 100rpx;}
	.box{background: #FFFFFF;padding: 20rpx 30rpx;border-bottom: 1rpx solid #efefef;}
	.box:last-child{border: none;}
	.box .content{flex: 1;}
	.box .title{font-size: 32rpx;font-weight: bold;line-height: 60rpx;}
	.box .pic{flex-shrink: 0;width: 120rpx;}
	.box .time{color: #CCCCCC;}
	.box .pic image{height: 100rpx;width: 100rpx;border-radius: 10rpx;}
	.box .option{border-top: 1rpx solid #EFEFEF;margin-top: 20rpx;padding-top: 20rpx;}
	.option .btn{align-items: flex-end;min-width: 110rpx;height: 60rpx;line-height: 60rpx;text-align: center;border-radius: 6rpx;color: #222222;margin-left:4rpx;padding: 0 8rpx;}
	.option .btn1{background: #009688;color: #FFFFFF;}
	.option .btn2{background: #CCCCCC;color: #FFFFFF;}
</style>