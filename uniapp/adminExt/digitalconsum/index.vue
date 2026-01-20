<template>
<view>
	<block v-if="isload">
    <view class="content" :style="bgpic?'background-image:url('+bgpic+');background-repeat: no-repeat;background-size:100% 100%;':''">
      <view v-if="notices && notices.length>0" class="bobaobox" >
      	<swiper style="position:relative;height:54rpx;width:450rpx;" autoplay="true" :interval="5000" vertical="true">
      		<swiper-item v-for="(item, index) in notices" :key="index"  class="flex-y-center">
      			<image :src="item.headimg"style="width:40rpx;height:40rpx;border:1px solid rgba(255,255,255,0.7);border-radius:50%;margin-right:4px"></image>
      			<view style="width:400rpx;white-space:nowrap;overflow:hidden;text-overflow: ellipsis;font-size:22rpx">
      				<text style="padding-right:2px">{{item.nickname}}</text>
      				<text style="padding-right:4px">{{item.showtime}}</text>
      				<text>{{item.msg}}</text>
      			</view>
      		</swiper-item>
      	</swiper>
      </view>
      <!-- s -->
      <view v-if="set.show_digital_price==1 || set.show_pool==1" style="width: 100%;padding: 40rpx 30rpx;">
        <view style="width: 680rpx;margin: 0 auto;display: flex;justify-content: center;align-items: center;">
          <view v-if="set.show_digital_price==1" style="text-align: center;width: 50%;">
            <view>
              <text style="color: #FE0000;font-size: 50rpx;font-weight: bold;">{{set.digital_price || 0}}</text>
            </view>
            <view><text>实时价格</text></view>
          </view>
          <view v-if="set.show_pool==1" style="text-align: left;line-height: 40rpx;width: 50%;">
            <view style="display: flex;"  >
              <view style="width: 170rpx;">{{t('数字权益')}}总量</view> <view>{{set.digital_score}}</view>
            </view>
            <view style="display: flex;margin-top: 10rpx;">
              <view style="width: 170rpx;">{{t('数字奖金池')}}金额</view> <view>{{set.pool}}</view>
            </view>
          </view>
        </view>
      </view>
      <view :style="set.nobgcolor?'':'background:#fff'">
        <view class="box" style="width: 680rpx;margin: 0 auto;">
          <view class="box-main range" :style="{borderColor:t('color1')}">
            <view class="range-item" :style="range==1?'color:'+t('color1'):''" @tap="changeRange(1)">
              <view class="range-item-name" :style="range==1?'border-bottom:4rpx solid '+t('color1'):''">周</view>
            </view>
            <view class="range-item" :style="range==2?'color:'+t('color1'):''" @tap="changeRange(2)">
              <view class="range-item-name" :style="range==2?'border-bottom:4rpx solid '+t('color1'):''">月</view>
            </view>
            <view class="range-item" :style="range==3?'color:'+t('color1'):''" @tap="changeRange(3)">
              <view class="range-item-name" :style="range==3?'border-bottom:4rpx solid '+t('color1'):''">年</view>
            </view>
          </view>
        </view>
        <view class="echart" >
          <view class="echart-content" style="position: relative;z-index: 10;">
            <view v-if="showechart">
              <l-echart ref="chart" @finished="init" class="charts-box"></l-echart>
            </view>
          </view>
		  <view style="width: 100%;color:red;">
		    <view class="optred-price">
		         同比昨日价格涨幅：{{set.today_dif}} 涨幅百分比：{{set.today_dif_percent}}
		    </view>
		    <view class="optred-price">
		        同比初始价格涨幅：{{set.total_dif}} 涨幅百分比：{{set.total_dif_percent}}
		    </view>
		  </view>
        </view>
		
      </view>
	  <view class="dp-cover" v-if="set.rule_pic && set.rule_pic">
	  	<button @tap="showruleBox"  class="dp-cover-cover" :style="{
	  		zIndex:10,
	  		top:'50vh',
	  		left:'80vw',
			
	  		width:'130rpx',
	  		height:'130rpx',
	  		margin:'0rpx 0rpx',
	  		padding:'0rpx 0rpx',
	  		fontSize:'28rpx',
	  		border:'4rpx solid back',
	  		borderRadius:'65rpx'
	  	}" show-message-card="true">
			<image :src="set.rule_pic" style="width: 150rpx;" mode="aspectFit"/>
	  	</button>
	  </view>
      <view style="width: 100%;" :style="centerpic?'background-image:url('+centerpic+');background-repeat: no-repeat;background-size:100% 100%;':''">
        
		<view style="width: 680rpx;margin: 0rpx auto;display: flex;justify-content: center;padding: 30rpx 0;">
          <view style="text-align: center;">
            <view><text>我的{{t('数字权益')}}</text></view>
            <view>
              <text style="color: #FE0000;font-size: 50rpx;font-weight: bold;">{{member.digital_score}}</text>
            </view>
            <view><text>实时价值：</text> <text style="color: #FE0000;">{{member.alldigital_price || 0}}元</text></view>
          </view>
        </view>
        <view style="width: 100%;">
          <view class="optred">
            <view @tap="goto" :data-url="set.buy_url" class="optred1" :style="btnpic?'background-image:url('+btnpic+');background-size:100% 100%;background-repeat: no-repeat;':'background:'+t('color1')">
              <view style="line-height: 80rpx;">{{set.buy_btn_text || '购买'}}</view>
            </view>
            <view @tap="goto" data-url="/pagesD/digitalconsum/withdraw" class="optred1" :style="btnpic?'background-image:url('+btnpic+');background-size:100% 100%;background-repeat: no-repeat;':'background:'+t('color1')">
              <view style="line-height: 80rpx;">{{set.withdraw_btn_text || '兑换'}}</view>
            </view>
          </view>
        </view>
      </view>
      <!-- e -->
      
      <view class="join" :style="set.nobgcolor?'':'background:#fff'">
        <view class="jointitle">
          明细列表
          <view v-if="set.desc" @tap="changeshortcontent" class="shortcontent">{{t('奖金池')}}规则</view>
        </view>
        <scroll-view scroll-y="true" style="width: 710rpx;margin: 0 auto;;height: 410rpx;border:2rpx solid #f1f1f1;border-radius: 8rpx;">
          <block v-if="logs" v-for="(item,index) in logs" :key="index">
            <view class="joincontent">
              <view style="width: 530rpx;display: flex;align-items: center;">
                <view class="joinname" style="width: 100%;">{{item.createtime}} {{item.remark}}</view>
              </view>
              <view class="jointip">
                <text style="font-size: 30rpx;">{{item.value}}</text>
              </view>
            </view>
          </block>
        </scroll-view>
      </view>
    </view>
    <view v-if="showrule" class="xieyibox">
    	<view class="xieyibox-content">
    		<view style="overflow:scroll;height:100%;">
    			<parse :content="set.rule_text" @navigate="navigate"></parse>
    		</view>
    		<view style="position:absolute;z-index:9999;bottom:10px;left:0;right:0;margin:0 auto;text-align:center; width: 50%;height: 60rpx; line-height: 60rpx; color: #fff; border-radius: 8rpx;" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  @tap="hideruleBox">
				已知晓
			</view>
    	</view>
    </view>
	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
	<popmsg ref="popmsg"></popmsg>
</view>
</template>

<script>
var app = getApp();
var interval = null;

import lEchart from '@/echarts/l-echart/l-echart.vue';
import * as echarts from '@/echarts/static/echarts.min.js';

export default {
  components: {
      lEchart
  },
  data() {
    return {
      opt:{},
      loading:false,
      isload: false,
      nomore: false,
      nodata:false,
      menuindex:-1,
      pre_url:app.globalData.pre_url,
      data:{},
      bgpic:'',
      centerpic:'',
      btnpic:'',
      childs:'',
      notices:'',
      showshortcontent:false,
      set:{},
      member:{},
      logs:[],
      

      datalist: [],
      pagenum: 1,
      range:1,
      chartdata:{},
      yData:[],
      charttype:1,
      chartname:'数字价格',
      chartcolor:'#ee6666',
      ischooserange:false,
      rangeType:1,
      month:'',
      start_date:'',
      end_date:'',
      option: {},
      showechart:true,
      curdate:'',
      bonus_pool_total:0,
	  showrule:false
    }
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
    async init() {
      // chart 图表实例不能存在data里
      const chart = await this.$refs.chart.init(echarts);
      chart.setOption(this.option)
    },
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiDigitalConsum/bonuspool', {range:that.range}, function (res) {
				that.loading = false;
				if(res.status == 1){
          uni.setNavigationBarTitle({
          	title: that.t('数字权益')+'中心'
          });

					var set   = res.set;
					that.set = set;
					that.bgpic = set.bgpic || '';
					that.centerpic = set.centerpic || '';
					that.btnpic = set.btnpic || '';
					that.member = res.member;
					that.logs = res.logs
					if(res.notices){
						that.notices = res.notices;
					}

          that.chartdata = res.chartdata
          that.curdate = res.curdate;
          that.echartsInit();
          
          that.loaded();
				} else {
					if (res.msg) {
						app.alert(res.msg, function() {
							if (res.url) app.goto(res.url);
						});
					} else if (res.url) {
						app.goto(res.url);
					} else {
						app.alert('您无查看权限');
					}
				}
			});
		},
    changeshortcontent:function(){
      app.goto('/pagesD/bonuspoolgold/bonuspoolrule');
    },
    echartsInit:function(){
    	var that = this;
    	this.option = {
    		tooltip: {
    			trigger: 'axis'
    		},
    		legend: {
    			show:true,
    			selectedMode:"single"
    		},
    		grid: {
    			left: '1%',
    			right: '1%',
    			bottom: '1%',
    			containLabel: true
    		},
    		xAxis: {
    			type: 'category',
    			boundaryGap: false,
				
    			data: that.chartdata.xData
    		},
    		yAxis: {
    			type: 'value',
				axisLabel: {
				      // 更改x轴文字颜色的配置
				      textStyle: {
				        color: '#ff0000'
				      }
				}
    		},
    		series: [
    			{
    				name: that.t('数字价格')+'涨幅曲线图',
    				type: 'line',
    				stack: '总量',
    				data: that.chartdata.yData,
					label : { 
						normal: {
							show: true,
							// formatter:function(params){
							// 	if(params.value>0){
							// 		return params.value+'%'
							// 	}else{
							// 		return params.value;
							// 	}
								
							// }
						},
					}
    			}
    		]
    	};
    	this.init();
    },
    toggleTimeModal:function(){
    	this.showechart = !this.showechart
    	this.ischooserange = !this.ischooserange
    	if(this.ischooserange){
    		this.range = 5;
    	}
    },
    rangeTypeChange:function(rangType){
    	this.rangeType = rangType
    },
    bindDateChange:function(e){
    	var field = e.currentTarget.dataset.field;
    	this[field] = e.detail.value;
    },
    resetTimeChoose:function(){
    	this.month = '';
    	this.start_date = ''
    	this.end_date = ''
    	this.range = 1;
    	this.ischooserange = false;
    	this.getdata()
    	this.showechart = true
    },
    confirmTimeChoose:function(){
    	this.ischooserange = false;
    	this.showechart = true
    	this.getdata()
    },
    changeRange:function(range){
    	this.range = range;
    	this.getdata();
    },
	showruleBox: function () {
	  this.showrule = true;
	},
	hideruleBox: function () {
	  this.showrule = false;
	},
  }
};
</script>
<style>
  .content{width: 100%;position: relative;}
  .centercontent{width:680rpx;height: 680rpx;margin: 0 auto;overflow: hidden;position: relative;}
  .daojishi{text-align: center;width: 410rpx;margin:0 auto ;display: flex;justify-content: center;align-items: center;font-size: 24rpx;}
  .daojishi0{color: #fff;display: flex;justify-content: center;}
  .daojishi1{background-color: #E5472D;text-align: center;width: 36rpx;line-height: 36rpx;border-radius: 4rpx;}
  .daojishi2{color: #E5472D;text-align: center;width: 16rpx;}
  .jindu{background-color: #ED0523;width: 450rpx;margin:0 auto ;padding:10rpx;border-radius:60rpx 60rpx;box-shadow: 10rpx 10rpx 10rpx 0rpx #845B59;color: #fff;text-align: center;margin-top: 10rpx;display: flex;align-items: center;border: 4rpx solid #FD852E;}

  .optred{display: flex;justify-content:space-around;width:680rpx;margin:0rpx auto;color: #fff;text-align: center;padding: 40rpx 0;}
  .optred1{border-radius: 12rpx;width: 222rpx;height: 80rpx;font-size: 30rpx;font-weight: bold;}
  
  .optred-price{display: flex;justify-content:space-around;width:100%;margin:0rpx auto;color: #FF593E;text-align: center;padding: 10rpx 0;}

  .join{width:100%;margin: 0 auto;padding: 30rpx 0;}
  .jointitle{text-align: left;width:680rpx;margin: 0rpx auto;font-size: 30rpx;font-weight: bold;line-height: 70rpx;color: #FF593E;display: flex;justify-content: center;}
  .joincontent{width:680rpx;margin:0 auto;padding: 10rpx;display: flex;justify-content: space-between;align-items:center;border-radius: 4rpx;}
  .joinpic{width: 80rpx;height: 80rpx;border-radius: 80rpx;background-color: #f1f1f1;overflow: hidden;}
  .joinname{padding:0 10rpx;font-size: 32rpx;white-space: nowrap;text-overflow: ellipsis;overflow: hidden;}
  .jointip{width: 180rpx;color: #FF593E;font-weight: bold;text-align: right;padding-right: 10rpx;}
  
  .bobaobox {
  	position: fixed;
  	top: calc(var(--window-top) + 134rpx);
  	left: 20rpx;
  	z-index: 10;
  	background: rgba(0, 0, 0, 0.6);
  	border-radius: 30rpx;
  	color: #fff;
  	padding: 0 10rpx
  }
  .bobaobox_bottom {
  	position: fixed;
  	bottom: calc(env(safe-area-inset-bottom) + 150rpx);
  	left: 0;
  	right: 0;
  	width:470rpx;
  	margin:0 auto;
  	z-index: 10;
  	background: rgba(0, 0, 0, 0.6);
  	border-radius: 30rpx;
  	color: #fff;
  	padding: 0 10rpx
  }
  @supports (bottom: env(safe-area-inset-bottom)){
  	.bobaobox_bottom {
  		position: fixed;
  		bottom: calc(env(safe-area-inset-bottom) + 150rpx);
  		left: 0;
  		right: 0;
  		width:470rpx;
  		margin:0 auto;
  		z-index: 10;
  		background: rgba(0, 0, 0, 0.6);
  		border-radius: 30rpx;
  		color: #fff;
  		padding: 0 10rpx
  	}
  }
  .shortcontent{color: #A1351D;}
  
  /* 请根据实际需求修改父元素尺寸，组件自动识别宽高 */
  .charts-box {
    width: 100%;
    /* min-height: 600rpx; */
  }
  .box{width: 100%; padding: 10rpx 20rpx;}
  .box-title{border-bottom: 1px solid #ededed;padding-bottom:20rpx;display: flex;align-items: center;justify-content: space-between;}
  .box-title .title{display: flex;align-items: center;}
  .box-title .line{width: 6rpx;height: 24rpx;border-radius: 4rpx;margin-right: 16rpx;}
  .box-title .more{display: flex;align-items: center;justify-content: flex-end;color: #999;}
  .box-title .more .icon{width: 26rpx;height: 26rpx;}
  .box-main{}

  .range{display: flex;align-items: center;border-radius: 8rpx;}
  .range .range-item{text-align: left; padding: 10rpx 0;width: 200rpx;display: inline-block;}
  .range .range-item-name{width: 100rpx;text-align: center;}

  .tab{display: flex;align-items: center;margin-top: 20rpx;flex-wrap: wrap;}
  .tab-item{display: flex;flex-direction: column;align-items: center;padding: 10rpx 0;width: 33%;line-height: 60rpx;}
  .tab-item .tab-txt{color: #999;}
  .tab-item .tab-money{font-weight: bold;font-size: 30rpx;color: #222222;}
  .echart{width: 100%; padding: 30rpx 0;}
  .echart .box-title{padding-left: 30rpx;}
  .echart .echart-content{padding: 20rpx;}
  .echart-option{display: flex;justify-content: center;}
  .echart-line{min-height: 500rpx;width: 100%;display: flex;justify-content: center;}
  .echart-option .opt{padding:10rpx 30rpx;min-width: 200rpx;display: flex;align-items: center;}
  .echart-option .opt1{color: #ee6666;}
  .echart-option .opt1 .dot{background: #ee6666;border-radius: 50%;width: 20rpx;height: 20rpx;margin-right: 12rpx;}
  .echart-option .opt2{color: #4e9d77;}
  .echart-option .opt2 .dot{background: #4e9d77;border-radius: 50%;width: 20rpx;height: 20rpx;margin-right: 12rpx;}
  .headertab{display: flex;align-items: center;}
  .headertab .item{padding-bottom: 10rpx;margin-right: 40rpx;}
  .headertab .item.on{font-weight: bold;border-bottom: 2px solid;}
  /* .popup__title{border-bottom: 1px solid #ededed;padding: 20rpx;} */
  .popup__content{padding: 20rpx 50rpx;line-height: 60rpx;}
  .popup__bottom{position: absolute;bottom: 20rpx;width: 80%;left: 10%;color: #fff;display: flex;justify-content: center;}
  .popup__bottom .popup_btn{border-radius: 70rpx;color: #fff;width: 260rpx;}
  .popup__bottom .popup_btn.btn1{border: 1px solid #c9c9c9;color: #222222;}
  .time-date{display: flex;align-items: center;}
  .date{width: 200rpx;border-bottom: 1px solid #ededed;}
  .time-date .dt{width: 80rpx;text-align: center;}
  .month-label{font-weight: bold;font-size: 30rpx;}
  
  .dp-cover{height: auto; position: relative;}
  .dp-cover-cover{position:fixed;z-index:99999;cursor:pointer;display:flex;align-items:center;justify-content:center;overflow:hidden}

.xieyibox{width:100%;height:100%;position:fixed;top:0;left:0;z-index:99;background:rgba(0,0,0,0.7)}
.xieyibox-content{width:90%;margin:0 auto;/*  #ifdef  MP-TOUTIAO */height:60%;/*  #endif  *//*  #ifndef  MP-TOUTIAO */height:80%;/*  #endif  */margin-top:20%;background:#fff;color:#333;padding:5px 10px 50px 10px;position:relative;border-radius:2px;}
</style>