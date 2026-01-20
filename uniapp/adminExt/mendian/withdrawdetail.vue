<template>
<view class="container">
	<block v-if="isload">

		<view class="orderinfo">
			<view class="item">
				<text  class="t1">社区名称</text>
				<text class="t2">{{detail.xqname}}</text>
			</view>
			
			<view class="item" >
				<text class="t1">姓名</text>
				<text class="t2" user-select="true" selectable="true">{{detail.name}}</text>
			</view>
			<view class="item">
				<text  class="t1">电话</text>
				<text class="t2">{{detail.tel}}</text>
			</view>
			<view class="item">
				<text  class="t1">提现方式</text>
				<text class="t2">{{detail.paytype}}</text>
			</view>
		
			<view class="item">
				<text  class="t1">提现金额</text>
				<text class="t2">￥{{detail.txmoney}}</text>
			</view>
			
			<view class="item">
				<text  class="t1">打款金额</text>
				<text class="t2">￥{{detail.money}}</text>
			</view>
			
			<view class="item">
				<text class="t1">申请时间</text>
				<text class="t2">{{detail.createtime}}</text>
			</view>


			<view class="item">
				<text class="t1">状态</text>
				<text class="t2 st0" v-if="detail.status==0">待审核</text>
				<text class="t2 st1" v-if="detail.status==1">已审核</text>
				<text class="t2 st2" v-if="detail.status==2">已驳回</text>
				<text class="t2 st3" v-if="detail.status==3">已打款</text>
			</view>
		</view>
		

		
		<block >
		<view class="orderinfo">
			<view class="title">收款账号</view>
			<block v-if="detail.paytype=='微信钱包'">

				<view class="item" >
					<text class="t1">微信号</text>
					<text class="t2" user-select="true" selectable="true">{{detail.weixin}}</text>
					<text class="copy" style="color: #B0543D;margin-left: 20rpx;font-weight: bold;" @tap.stop="copy" :data-text="detail.weixin">复制</text>
				</view>
			</block>
			<block v-if="detail.paytype=='支付宝'">
				<view class="item" >
					<text class="t1">姓名</text>
					<text class="t2" user-select="true" selectable="true">{{detail.aliacountname}}</text>
					<text class="copy" style="color: #B0543D;margin-left: 20rpx;font-weight: bold;" @tap.stop="copy" :data-text="detail.aliacountname">复制</text>
				</view>
				<view class="item">
					<text  class="t1">支付宝账号</text>
					<text class="t2">{{detail.aliacount}}</text>
					<text class="copy" style="color: #B0543D;margin-left: 20rpx;font-weight: bold;"  @tap.stop="copy" :data-text="detail.aliacount">复制</text>
				</view>
			</block>
			<block v-if="detail.paytype=='银行卡'">
				<view class="item">
					<text  class="t1">开户银行</text>
					<text class="t2">{{detail.bankname}}</text>
					<text class="copy" style="color: #B0543D;margin-left: 20rpx;font-weight: bold;"  @tap.stop="copy" :data-text="detail.bankname">复制</text>
				</view>
				<view class="item" v-if="detail.bankcarduser">
					<text  class="t1">银行卡姓名</text>
					<text class="t2">{{detail.bankcarduser}}</text>
					<text class="copy" style="color: #B0543D;margin-left: 20rpx;font-weight: bold;"  @tap.stop="copy" :data-text="detail.bankcarduser">复制</text>
				</view>
				<view class="item" v-if="detail.bankcardnum">
					<text  class="t1">银行卡号</text>
					<text class="t2">{{detail.bankcardnum}}</text>
					<text class="copy" style="color: #B0543D;margin-left: 20rpx;font-weight: bold;" @tap.stop="copy" :data-text="detail.bankcardnum">复制</text>
				</view>
			</block>
		
		</view>
		</block>
		

		<view style="width:100%;height:160rpx"></view>

		<view class="bottom notabbarbot" >
			<block v-if="detail.status==0">
				<view class="btn2" @tap="confirm" :data-id="detail.id" data-st='1'>通过</view>
				<view class="btn2" @tap="confirm" :data-id="detail.id" data-st='2'>驳回</view>
			</block>		
			<block v-if="detail.status==1">
					<view class="btn2" @tap="confirm" :data-id="detail.id" data-st='3'>确认打款</view>
					<block v-if="detail.paytype=='微信钱包'">
						<view class="btn2" @tap="confirm" data-st='10'>微信打款</view>
					</block>
			</block>
		</view>
		<uni-popup id="dialogSetremark" ref="dialogSetremark" type="dialog">
			<uni-popup-dialog mode="input" title="驳回原因" :value="detail.remark" placeholder="请输入驳回原因" @confirm="setremarkconfirm"></uni-popup-dialog>
		</uni-popup>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
var app = getApp();
var interval = null;

export default {
  data() {
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			pre_url:app.globalData.pre_url,
      detail: ""
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.transfertype =		this.opt.transfertype
		this.btype =		this.opt.btype
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onUnload: function () {
    clearInterval(interval);
  },
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiAdminMendian/withdrawdetail', {id: that.opt.id}, function (res) {
				uni.stopPullDownRefresh();
				that.loading = false;
				that.detail = res.detail;
				that.isload = 1;
				that.loaded();
			});
		},
		setremarkconfirm: function (done, remark) {
			this.$refs.dialogSetremark.close();
			var that = this
			//app.confirm('确定要驳回吗？', function () {
				app.post('ApiAdminMendian/withdrawlogsetst', {id: that.detail.id,reason:remark,st:that.st }, function (res) {
					app.success(res.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
				})
		//	})
    },

   confirm: function (e) {
      var that = this;
			var st = e.currentTarget.dataset.st;
			var msg = '';
			 if (st == 1) {
						msg = '确定要审核通过吗?';
				}
				if (st == 2) {
						msg = '确定要驳回吗?';
						that.st = st
						this.$refs.dialogSetremark.open();return;
				}
				if (st == 3) {
						msg = '确定您已经通过其他方式打款给用户了吗?';
				}
				if (st == 10) {
						msg = '确定要微信打款吗?';
				}

      app.confirm(msg, function () {
        app.post('ApiAdminMendian/withdrawlogsetst', {id: that.detail.id,st:st,reason:''}, function (data) {
          app.success(data.msg);
          setTimeout(function () {
            that.getdata();
          }, 1000);
        });
      });
    },
	
  }
};
</script>
<style>
.ordertop{width:100%;height:220rpx;padding:50rpx 0 0 70rpx}
.ordertop .f1{color:#fff}
.ordertop .f1 .t1{font-size:32rpx;height:60rpx;line-height:60rpx}
.ordertop .f1 .t2{font-size:24rpx}


.orderinfo{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .title{ margin:20rpx 0rpx 10rpx 0rpx; font-size: 30rpx; font-weight: bold;}

.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .st1{ color: #219241; }
.orderinfo .item .st0{ color: #F7C952; }
.orderinfo .item .st2{ color: #FD5C58; }


.orderinfo .item .red{color:red}
.orderinfo .item .pic{width:200rpx}

.tips{ background: #FAF5DD; border-rdius:20rpx;padding: 30rpx; margin:30rpx 20rpx 0 20rpx;color:#CBA758}

.bottom{ width: 100%;height:92rpx;padding: 0 20rpx;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;}

.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.btn3{position:absolute;top:60rpx;right:10rpx;font-size:24rpx;width:120rpx;height:50rpx;line-height:50rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.btn4{font-size:24rpx;width:50%;height:70rpx;line-height:70rpx;color:#fff;background:#fff;border-radius:3px;display: flex;align-items: center;margin:30rpx auto;justify-content: center;}


.uploadbtn{position:relative;height:200rpx;width:200rpx}

</style>