<template>
<view class="container">
	<block v-if="isload">
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入姓名/证件号码搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm" @input="searchChange"></input>
			</view>
		</view>
    <view  @tap="goto" data-url="userdetail" style="width: 170rpx;line-height: 60rpx;border-radius: 60rpx 60rpx;text-align: center;margin-left: 20rpx;" :style="'color:'+t('color1')+';background:rgba('+t('color1rgb')+',0.3)'">
      添加旅客
    </view>
		<view v-for="(item, index) in datalist" :key="index" class="content">
			<view class="f1">
				<text class="t1">{{item.name}}</text>
        <text class="t2"></text>
				<text class="flex1"></text>
				<image class="t3" :src="pre_url+'/static/img/edit.png'" @tap.stop="goto" :data-url="'userdetail?id=' + item.id" >
			</view>
      <view class="f2">{{item.typename}}:{{item.usercard}}</view>
			<view class="f3">
				<view v-if="type == 'choose'" @tap="chooseit" :data-index="index" class="flex-y-center">
					<view class="radio" :style="item.issel ? 'border:0;background:'+t('color1') : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
				</view>
				<view class="flex1"></view>
				<view class="del" :style="{color:t('color1')}" @tap.stop="del" :data-id="item.id">删除</view>
			</view>
		</view>
		<nodata v-if="nodata"></nodata>
		<view style="height:140rpx"></view>
		<view v-if="type == 'choose'" @tap="backchoose" class="btn-add" :class="menuindex>-1?'tabbarbot':'notabbarbot3'" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'">
      确定选择
    </view>
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
			pre_url:app.globalData.pre_url,
      userdata:[],
      
      setstatus:false,
    }
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.type = this.opt.type || '';
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
			app.get('ApiHanglvfeike/user', {keyword:that.keyword}, function (res) {
				that.loading = false;
				var datalist = res.data;
				if(datalist.length == 0){
					that.datalist = datalist;
					that.nodata = true;
				}else{
					that.datalist = datalist;
				}
				that.loaded();
			});
		},
    del: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      app.confirm('确定要删除此旅客吗?', function () {
        app.post("ApiHanglvfeike/userdel", {userid: id}, function (res) {
          app.success(res.msg);
          that.getdata();
        });
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
    chooseit:function(e){
      var that = this;
      var index= e.currentTarget.dataset.index;
      var datalist = that.datalist;
      datalist[index]['issel'] = !datalist[index]['issel'];
      
      that.userdata = [];
      var len = datalist.length;
      for(var i=0;i<len;i++){
        if(datalist[i]['issel']){
          var data = {
            'id': datalist[i]['id'],
            'name': datalist[i]['name'],
            'usercard': datalist[i]['usercard'],
            'typename': datalist[i]['typename'],
            'passengerType': datalist[i]['passengerType'],
          };
          that.userdata.push(data);
        }
      }
    },
    backchoose:function(){
      var that = this;
      if(that.setstatus){
        return
      }
      that.setstatus = true;
      setTimeout(function() {
          let pages = getCurrentPages();
          if (pages.length >= 2) {
              //let curPage = pages[pages.length - 1]; // 当前页面
              let prePage = pages[pages.length - 2]; // 上一页面
              prePage.$vm.userdata = that.userdata;
              uni.navigateBack();
          }
      }, 600);
    }
  }
};
</script>
<style>
.topsearch{width:94%;margin:16rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}

.content{width:94%;margin:20rpx 3%;background:#fff;border-radius:5px;padding:20rpx 40rpx;}
.content .f1{height:96rpx;line-height:96rpx;display:flex;align-items:center}
.content .f1 .t1{color:#2B2B2B;font-weight:bold;font-size:30rpx}
.content .f1 .t2{color:#999999;font-size:28rpx;margin-left:10rpx}
.content .f1 .t3{width:28rpx;height:28rpx}
.content .f2{color:#2b2b2b;font-size:26rpx;line-height:42rpx;padding-bottom:20rpx;border-bottom:1px solid #F2F2F2}
.content .f3{height:96rpx;display:flex;align-items:center}
.content .radio{flex-shrink:0;width: 36rpx;height: 36rpx;background: #FFFFFF;border: 3rpx solid #BFBFBF;border-radius: 50%;}
.content .radio .radio-img{width:100%;height:100%}
.content .mrtxt{color:#2B2B2B;font-size:26rpx;margin-left:10rpx}
.content .del{font-size:24rpx}

.container .btn-add{width:90%;max-width:700px;margin:0 auto;height:96rpx;line-height:96rpx;text-align:center;color:#fff;font-size:30rpx;font-weight:bold;border-radius:40rpx;position: fixed;left:0px;right:0;bottom:0;margin-bottom:20rpx;}
.container .btn-add2{width:43%;max-width:700px;margin:0 auto;height:96rpx;line-height:96rpx;text-align:center;color:#fff;font-size:30rpx;font-weight:bold;border-radius:40rpx;position: fixed;left:5%;bottom:0;margin-bottom:20rpx;}
.container .btn-add3{width:43%;max-width:700px;margin:0 auto;height:96rpx;line-height:96rpx;text-align:center;color:#fff;font-size:30rpx;font-weight:bold;border-radius:40rpx;position: fixed;right:5%;bottom:0;margin-bottom:20rpx;}
.btn2{height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;padding: 0 15rpx;flex-shrink: 0;margin: 0 0 0 15rpx;}
</style>