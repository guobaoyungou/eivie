<template>
  <view class="container">
    <block v-if="isload">
      <dd-tab :itemdata="['全部','待审核','已通过','已驳回','已关闭']" :itemst="['all','0','1','2','3']" :st="st" :isfixed="true"
        @changetab="changetab"></dd-tab>
      <view style="width:100%;height:100rpx"></view>
      <view class="content" v-if="datalist && datalist.length>0">
        <block v-for="(item, index) in datalist" :key="index">
          <view class="item" @tap="goto" :data-url="'recorddetail?id=' + item.id">
            <view class="f1">
              <view class="t2">
                <text class="x1">报销记录</text>
                <text class="x2">记录编号：{{item.id}}</text>
                <text class="x2">扣除{{t('积分')}}：{{item.deduct_score}}</text>
                <text class="x2">打款金额：{{item.money}}</text>
                <text class="x2">提交日期：{{item.createtime}}</text>
              </view>
            </view>
            <view class="f2">
              <text class="t1 red" v-if="item.status == 0">待审核</text>
              <text class="t1 green" v-if="item.status == 1">已通过</text>
              <text class="t1 yellow" v-if="item.status == 2">已驳回</text>
              <text class="t1 green" v-if="item.status == 1 && item.payment_status == 2">已打款</text>
              <text class="t1" v-if="item.status == 3 && item.payment_status == 3">已关闭</text>
            </view>
          </view>
          <view class="op" v-if="item.status == 0">
            <view class="btn2" @tap="todel" :data-id="item.id">删除</view>
          </view>
        </block>
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
        opt: {},
        loading: false,
        isload: false,
        nodata: false,
        nomore: false,
        menuindex: -1,

        st: 'all',
        pagenum: 1,
        keyword: '',
        datalist: [],
      };
    },

    onLoad: function(opt) {
      this.opt = app.getopts(opt);
      this.mid = this.opt.mid;
      this.getdata();
    },
    onPullDownRefresh: function() {
      this.getdata();
    },
    onReachBottom: function() {
      if (!this.nodata && !this.nomore) {
        this.pagenum = this.pagenum + 1
        this.getdata(true);
      }
    },
    methods: {
      getdata: function(loadmore) {
        if (!loadmore) {
          this.pagenum = 1;
          this.datalist = [];
        }
        var that = this;
        var pagenum = that.pagenum;
        var st = that.st;
        that.nodata = false;
        that.nomore = false;
        that.loading = true;
        app.post('ApiInvoiceBaoxiao/recordlist', {
          st: st,
          pagenum: pagenum,
          keyword: that.keyword
        }, function(res) {
          that.loading = false;
          var data = res.datalist;
          if (pagenum == 1) {
            that.datalist = data;
            if (data.length == 0) {
              that.nodata = true;
            }
            that.loaded();
          } else {
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
      changetab: function(st) {
        this.st = st;
        uni.pageScrollTo({
          scrollTop: 0,
          duration: 0
        });
        this.getdata();
      },
      searchConfirm: function(e) {
        this.keyword = e.detail.value;
        this.getdata(false);
      },
      todel: function(e) {
        var that = this;
        var id = e.currentTarget.dataset.id;
        app.confirm("确定要删除吗", function() {
          app.showLoading('提交中');
          app.post('ApiInvoiceBaoxiao/del', {
            id: id
          }, function(data) {
            app.showLoading(false);
            app.success(data.msg);
            setTimeout(function() {
              that.getdata();
            }, 1000)
          })
        });
        return;
      }
    }
  };
</script>
<style>
  .topsearch{width:94%;margin:16rpx 3%}
  .topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
  .topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
  .topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333}
  .content{width:94%;margin:0 3%;border-radius:16rpx;background:#fff;margin-top:20rpx}
  .content .item{width:100%;padding:32rpx 20rpx;border-top:1px #eaeaea solid;min-height:112rpx;display:flex;align-items:center}
  .content .item .f1{display:flex;flex:1;align-items:center}
  .content .item .f1 .t2{display:flex;flex-direction:column;padding-left:20rpx}
  .content .item .f1 .t2 .x1{color:#333;font-size:30rpx;font-weight:bold;margin-bottom:10rpx}
  .content .item .f1 .t2 .x2{color:#737373;font-size:24rpx;line-height:40rpx}
  .content .item .f2{display:flex;flex-direction:column;width:200rpx;text-align:right}
  .content .item .f2 .t1{height:40rpx;line-height:40rpx;text-align:center}
  .op{display:flex;flex-wrap:wrap;justify-content:flex-end;align-items:center;width:100%;padding:10rpx 0px;border-top:1px #f4f4f4 solid;color:#555}
  .btn1{margin-left:20rpx;margin-top:10rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
  .btn2{margin-left:20rpx;margin-top:10rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
  .red{color:red}
  .yellow{color:#ffc107}
  .green{color:green}
</style>