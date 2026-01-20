<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['待审核','已通过','已驳回']" :itemst="['0','1','2']" :st="st" @changetab="changetab"></dd-tab>

		<view class="content" v-if="datalist && datalist.length>0">
			<block v-for="(item, index) in datalist" :key="index">
				<view class="item"  @tap="goto" :data-url="'detail?id='+item.id">
					<view class="itemL" @click="toteam(item.id)">
						<view class="f1">
							<image class="lgimg" :src="item.headimg"></image>
							<view class="t2">
								<text class="x1">{{item.xqname}}</text>
								<text class="x2" v-if="item.tel">手机号：{{item.tel}}</text>
								<text class="x2">申请时间：{{dateFormat(item.createtime)}}</text>

							</view>
						</view>
						<view class="op">
							<view class="st1"  v-if="item.check_status==1">已通过</view>
							<view class="st0"  v-if="item.check_status==0">待审核</view>
							<view class="st2"  v-if="item.check_status==2">已驳回</view>
						</view>
						<button @tap="showaudit" :data-id="item.id" class="btn" :style="'background:rgba('+t('color1rgb')+',0.9)'" v-if="!item.check_status">审 核</button>
					</view>
				</view>
			</block>
		</view>

		<view v-if="isshowaudit" class="alert_popup" @tap="hideaudit">
			<view class="alert_popup_content" @tap.stop="function(){return}">
				<view class="form-item">
					<text class="form-label">审核结果：</text>
					<view class="form-radio">
						<label class="radio" @tap="changeAudit(1)"><radio style="transform: scale(0.8);" :color="t('color1')" value="1" :checked="auditst==1?true:false"/>审核通过</label>
						<label class="radio" @tap="changeAudit(2)"><radio style="transform: scale(0.8);" :color="t('color1')" value="2" :checked="auditst==2?true:false" />审核拒绝</label>
					</view>
				</view>
				<view class="form-item" v-if="auditst==2">
					<text class="form-label">驳回原因：</text>
					<view class="form-txt">
						<textarea v-model="auditremark" style="width: 450rpx;" auto-height=""/>
					</view>
				</view>
				<view class="form-sub"><button class="btn" :style="{background:t('color1')}" @tap="auditSub">确 定</button></view>
			</view>
		</view>
	</block>
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
			pre_url: app.globalData.pre_url,
			st: 0,
			datalist: [],
			pagenum: 1,
			userlevel:{},
			userinfo:{},
			keyword:'',
			nodata: false,
			nomore: false,
			mid:0,
			range: [],
			tabdata:[],
			tabitems:[],
			user:{},
			isshowaudit:false,
			auditst:1,
			auditremark:'',
			auditid:0,
			count:0
    }
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1
      this.getlist(true);
    }
  },
  methods: {
		getdata:function(){
			this.getlist();
		},
    getlist: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var st = that.st;
      var pagenum = that.pagenum;
      var keyword = that.keyword;
			that.loading = true;
			that.nodata = false;
      that.nomore = false;
			var mid = that.mid;
      app.post('ApiAdminMendian/index', {pagenum: pagenum,keyword:keyword,st:that.st}, function (res) {
				that.loading = false;
        var data = res.datalist;
				that.count =res.count
        if (pagenum == 1) {
          that.datalist = data;
					that.user = res.user
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
    searchChange: function (e) {
      this.keyword = e.detail.value;
    },
    searchConfirm: function (e) {
      var that = this;
      var keyword = e.detail.value;
      that.keyword = keyword;
      that.getdata();
    },
		changetab: function (st) {
		  this.st = st;
			this.pagenum = 1;
			this.getlist()
		},
		showaudit:function(e){
			var that = this;
			that.isshowaudit = true;
			that.auditremark = '';
			that.auditst = 1;
			that.auditid = e.currentTarget.dataset.id;
		},
		changeAudit:function(st){
			this.auditst = st
		},
		hideaudit:function(){
			this.isshowaudit = false
		},
		auditSub:function(){
			var that = this;
			 app.post('ApiAdminMendian/setcheckst', {id:that.auditid,st:that.auditst,reason:that.auditremark}, function (res) {
				  if(res.status==1){
						that.isshowaudit = false;
						app.success(res.msg);
						setTimeout(function () {
						 		that.getdata();
						}, 1000);
					}else{
						app.error(res.msg);
					}
			 })
		}
	}
};
</script>
<style>

.topsearch{width:94%;margin:16rpx 3%;}
.topsearch .f1{height:70rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}

.content{width:94%;margin:0 3%;margin-top: 20rpx;}
.content .label{display:flex;width: 100%;padding: 16rpx;color: #333;}
.content .label .t1{flex:1}
.content .label .t2{ width:300rpx;text-align:right}

.content .item{width: 100%;padding:32rpx 20rpx;line-height: 40rpx;margin-bottom: 20rpx;background: #fff;border-radius:10rpx;}
.content .itemL{display: flex;justify-content: space-between;align-items: center; position: relative;}
.content .itemL .btn{ position: absolute; bottom:0; right: 0;width: 120rpx;border-radius: 60rpx;height: 50rpx;line-height: 50rpx;color: #fff;font-size: 24rpx;}
.content .itemL  .op{ position: absolute; right:10rpx; top:0;}
.content .itemL .op .st1{ color: #219241; }
.content .itemL .op .st0{ color: #F7C952; }
.content .itemL .op .st2{ color: #FD5C58; }

.content .item image{width: 120rpx;height: 120rpx;border-radius:50%}
.content .item .lgimg{width: 120rpx;height: 120rpx;border-radius:10rpx}
.content .item .f1{display:flex;align-items:center;flex: 1;}
.content .item .f1 .t2{display:flex;flex-direction:column;padding-left:20rpx;}
.content .item .f1 .t2 .x1{color: #333;font-size:28rpx;}
.content .item .f1 .t2 .x2{color: #999;font-size:24rpx;}
.content .item .f2{border-top: 1px solid #f6f6f6; margin-top: 20rpx;padding-top: 20rpx;}
.content .item .f2 .money{font-weight: bold;}

.tr{display: flex;justify-content: space-between;align-items: center;}
.yejilabel{padding: 20rpx;background: #fff;border-radius:10rpx;font-weight: bold;}

.alert_popup{position: fixed;background: rgba(0,0,0, 0.4); width: 100%; height:100%; top:0;display: flex;justify-content: center;z-index: 900;flex-direction: column;align-items: center;}
.alert_popup_content{background: #fff;width: 90%;margin: auto 5%;padding: 30rpx;}
.alert_popup_title{margin-bottom: 30rpx;text-align: center;}
.alert_popup_content{border-radius: 10rpx;}
.form-item{display: flex;margin-bottom: 30rpx;}
.form-item .form-label{flex-shrink: 0;color: #888;}
.form-radio{display: flex;justify-content: center}
.form-radio .radio{width: 200rpx;}
.form-txt{background: #efefef;height: 160rpx;padding: 10rpx;overflow-y: scroll}
.form-sub{display: flex;justify-content:flex-end;margin-top: 30rpx;}
.form-sub .btn{width: 200rpx;height: 70rpx;line-height: 70rpx;color: #fff;border-radius: 70rpx;}
</style>