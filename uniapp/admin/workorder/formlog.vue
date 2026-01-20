<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['全部','待处理','处理中','已处理','待支付']" :itemst="['all','0','1','2','10']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
		<view style="width:100%;height:90rpx"></view>
		<view class="content" id="datalist">
			<view class="item"v-for="(item, index) in datalist" :key="index">
				<view class="f1">

						<view class="flex" style="justify-content: space-between;">
							<text class="t1"  @tap="goto" :data-url="'myformdetail?id=' + item.id" >工单类型：{{item.title}}</text>	
								<view class="f2">
									<text class="t1" v-if="item.status==0 && (!item.payorderid||item.paystatus==1)" style="color:#88e">待处理</text>
									<text class="t1" v-if="item.status==0 && item.payorderid && item.paystatus==0" style="color:red">待支付</text>
									<text class="t1" v-if="item.status==1" style="color:green">处理中</text>
									<text class="t1" v-if="item.status==2" style="color:red">已处理</text>
									<text class="t1" v-if="item.status==-1" style="color:red">已驳回</text>
								</view>
						</view>
						<view class="flex" style="justify-content: space-between;margin-top: 15rpx;">
							<text class="t2"  @tap="goto" :data-url="'myformdetail?id=' + item.id" >提交时间：{{item.createtime}}</text>
							<view class="jindu" @tap="jindu" :data-id="item.id"  v-if="!item.payorderid || (item.payorderid && item.paystatus==1)">查看进度</view>
						</view>
				</view>
			
			</view>
		</view>
	</block>
	
	<view class="modal" v-if="ishowjindu">
		<view class="modal_jindu">
					<view class="close" @tap="close"><image :src="pre_url+'/static/img/close.png'" /></view>
					<block v-if="jdlist.length>0">
						<view class="item " v-for="(item,index) in jdlist" :key="index">
							<view class="f1"><image :src="'/static/img/jindu' + (index==0?'2':'1') + '.png'"></image></view>
							<view class="f2">
								<text class="t2"> 时间：{{item.time}}</text>
								<text class="t1">{{item.desc}}({{item.remark}}) </text>
							</view>
						</view>
					</block>
					<block v-else>
							<view style="font-size:14px;color:#f05555;padding:10px;">等待处理</view>
					</block>
		</view>
	</view>
	<nodata v-if="nodata"></nodata>
	<nomore v-if="nomore"></nomore>
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

      st: 'all',
      datalist: [],
      pagenum: 1,
      nomore: false,
			ishowjindu:false,
			jdlist:[],
			nodata: false,
			pre_url: app.globalData.pre_url,
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 'all';
		this.formid = this.opt.formid || '';
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
    changetab: function (st) {
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
    getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var pagenum = that.pagenum;
      var st = that.st;
			this.nodata = false;
			this.nomore = false;
			this.loading = true;
      app.post('ApiAdminWorkorder/myformlog', {formid:that.formid,st: st,pagenum: pagenum}, function (res) {
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
		jindu:function(e){
			var that=this
			that.ishowjindu=true
			var id = e.currentTarget.dataset.id
			//读取进度表
			app.post('ApiWorkorder/selectjindu', { id: id }, function (res) {
					if(res.status==1){
						var data = res.data
						that.jdlist =data
					}
				
			})
		},
		close:function(e){
			var that=this
			that.ishowjindu=false
		}
  }
}
</script>
<style>

	.content{ width:100%;margin:0;}
	.content .item{ width:94%;margin:20rpx 3%;;background:#fff;border-radius:16rpx;padding:30rpx 30rpx;display:flex;align-items:center;}
	.content .item:last-child{border:0}
	.content .item .f1{width:100%;display:flex;flex-direction:column}
	.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
	.content .item .f1 .t2{color:#666666;margin-top:10rpx}
	.content .item .f1 .t3{color:#666666}
	.content .item .f2{width:20%;font-size:24rpx;text-align:right}
	.content .item .f2 .t1{color:#03bc01;font-size:24rpx;}
	.content .item .f2 .t2{color:#000000}
	.content .item .f3{ flex:1;font-size:30rpx;text-align:right}
	.content .item .f3 .t1{color:#03bc01}
	.content .item .f3 .t2{color:#000000}
	.jindu{ border: 1rpx solid #ccc; font-size: 24rpx; padding: 5rpx 10rpx; border-radius: 10rpx; color: #555;}
	
	
	.modal{ position: fixed; background:rgba(0,0,0,0.3); width: 100%; height: 100%; top:0; z-index: 1000;}
	.modal .modal_jindu{ background: #fff; position: absolute; top: 20%; align-items: center; margin: auto; width: 90%; left: 30rpx; border-radius: 10rpx; padding: 40rpx;}
	.modal_jindu .close image { width: 20rpx; height: 20rpx; position: absolute; top:10rpx; right: 20rpx;}
	.modal_jindu .on{color: #23aa5e;}
	.modal_jindu .item{display:flex;width: 96%;  margin: 0 2%;/*border-left: 1px #dadada solid;*/padding:0 0}
	.modal_jindu .item .f1{ width:60rpx;position:relative}
	/*.logistics img{width: 15px; height: 15px; position: absolute; left: -8px; top:11px;}*/
	.modal_jindu .item .f1 image{width: 30rpx; height: 100%; position: absolute; left: -16rpx; top: 0rpx;}
	.modal_jindu .item .f2{display:flex;flex-direction:column;flex:auto;padding:10rpx 0}
	.modal_jindu .item .f2 .t1{font-size: 30rpx;}
	.modal_jindu .item .f2 .t1{font-size: 26rpx;}
</style>