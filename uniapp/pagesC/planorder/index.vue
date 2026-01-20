<template>
	<view class="view-width">
		<block v-if="isload">
      <view v-if="taskprogress<100">
        <view v-if="set.bgpic" class="head-class" :style="{background:'url('+set.bgpic+')',backgroundSize:'cover',backgroundRepeat:'no-repeat'}"></view>
        <view class="task-wc">
          <view class="task-title" style="font-size: 30rpx;padding-left: 10rpx;" :style="'border-left: 6rpx solid '+t('color1')">
            <text :style="'color:'+t('color1')">请依次完成下列任务({{finishnum}}/{{tasknum}})</text>
          </view>
          <view class="taskprogress">
            <progress :percent="taskprogress" :activeColor="t('color1')" stroke-width="10" border-radius="10" style="width: 100%;"/>
          </view>
        </view>

        <view class="tasklist">
          <view>
            <block v-for="(item, index) in datalist" :key="index"> 
              <view class="task-item" :style="{color:t('color1'),background:'rgba('+t('color1rgb')+',0.1)'}">
                <view style="display: flex;align-items: center;padding: 20rpx;">
                  <view  style="font-weight: bold;">任务<text style="font-weight: bold;margin-left: 4rpx;">{{index+1>=10?index:'0'+(index+1)}}</text></view> 
                  <view style="font-size: 24rpx;margin-left: 20rpx;">完成({{item.buynum}}/{{item.num}})</view>
                </view>
                <view class="task-item-bottom" :style="{background:'rgba('+t('color1rgb')+',0.2)'}" >
                  <view style="width: 470rpx;">在[{{item.name}}]完成{{item.num}}笔订单</view>
                  <view @tap="goTask" :data-id="item.id" :data-index="index" class="opt" :style="'background-color:'+t('color1')">
                    {{item.buynum}}/{{item.num}}
                  </view>
                </view>
              </view>
            </block>
            <nodata v-if="nodata"></nodata>
          </view>
          <nomore v-if="nomore"></nomore>
        </view>
        <view style="width: 100%;height: 20rpx;"></view>
      </view>
      <view v-else>
        <form @submit="formSubmit">
        	<view class="form">
            <view class="form-item" style="height: 260rpx;">
              <view class="label">店铺LOGO</view>
            	<view class="f2" style="width: 100%;flex: 1;display: flex;justify-content: flex-end;">
            		<view v-for="(item, index) in pic" :key="index" class="layui-imgbox">
            			<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="pic"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
            			<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
            		</view>
            		<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pic" data-pernum="1" v-if="pic.length==0"></view>
            	</view>
            	<input type="hidden" hidden="true" name="pic" :value="pic.join(',')" maxlength="-1"/>
            </view>
            <view v-if="licensestatus" class="form-item" style="height: 260rpx;">
              <view class="label">{{license}}</view>
            	<view class="f2" style="width: 100%;flex: 1;display: flex;justify-content: flex-end;">
            		<view v-for="(item, index) in pic2" :key="index" class="layui-imgbox">
            			<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="pic2"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
            			<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
            		</view>
            		<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pic2" data-pernum="1" v-if="pic2.length==0"></view>
            	</view>
            	<input type="hidden" hidden="true" name="pic2" :value="pic2.join(',')" maxlength="-1"/>
            </view>
        		<view class="form-item">
        			<view class="label">店铺名称</view>
              <input class="input" type="text" placeholder="请输入店铺名称" placeholder-style="font-size:28rpx;color:#BBBBBB" name="name" value=""></input>
        		</view>
        	</view>
        	<button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit">确定开店</button>
        </form>
      </view>
		</block>
		<popmsg ref="popmsg"></popmsg>
		<loading v-if="loading"></loading>
	</view>
</template>

<script>
	const app = getApp();
	export default {
		data(){
			return{
				opt:{},
				loading:false,
				isload:false,
        nodata:false,
        nomore:false,
				pre_url:app.globalData.pre_url,
        
        taskprogress:0,
        set:'',
        datalist:[],
        pic:[],
        
        tasknum:0,
        finishnum:0,

        pic2:[],
        licensestatus:false,
        license:''
        
			}
		},
		onLoad(opt){
			this.opt = app.getopts(opt);
      this.getdata();
		},
    onPullDownRefresh: function () {
    	this.getdata();
    },
		methods:{
			// 页面信息
			getdata: function (loadmore) {
				if(!loadmore){
					this.datalist = [];
				}
			  var that = this;
				that.nodata = false;
				that.nomore = false;
				that.loading = true;
				app.get('ApiPlanorder/index', {}, function (res) {
					that.loading = false;
          if(res.status == 1){
            uni.setNavigationBarTitle({
            	title: res.title
            });
            if(res.licensestatus){
              that.licensestatus = res.licensestatus;
              that.license = res.license;
            }
            
            var data = res.data;
            that.taskprogress = res.taskprogress;
            that.tasknum  = res.tasknum;
            that.finishnum= res.finishnum;
            that.set = res.set;
            that.datalist = data;
            if (data.length == 0) {
              that.nodata = true;
            }
            that.loaded();
          }else if(res.status == 3){
            app.goto(res.url,'redirect');
          }else {
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
      goTask:function(e){
        var that = this;
        var id = e.currentTarget.dataset.id;
        var index = e.currentTarget.dataset.index;
        app.post('ApiPlanorder/checktask', {id:id,index:index}, function (res) {
        	that.loading = false;
          if(res.status == 1){
            app.goto('/pagesC/planorder/shop?type=task&shopid='+id)
          }else{
            app.alert(res.msg)
          }
        });
      },
      formSubmit: function (e) {
        var that = this;
        var formdata   = e.detail.value;
        var data = {
          formdata:formdata,
        }
      	app.showLoading('提交中');
        app.post('ApiPlanorder/index',data, function (res) {
      		app.showLoading(false);
          if (res.status == 1) {
            app.success(res.msg);
            setTimeout(function () {
              app.goto('/pagesC/planorder/shop','redirect');
            }, 900);
          }else {
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
      uploadimg:function(e){
      	var that = this;
      	var pernum = parseInt(e.currentTarget.dataset.pernum);
      	if(!pernum) pernum = 1;
      	var field= e.currentTarget.dataset.field
      	var pics = that[field]
      	if(!pics) pics = [];
      	app.chooseImage(function(urls){
      		for(var i=0;i<urls.length;i++){
      			pics.push(urls[i]);
      		}
      		if(field == 'pic') that.pic = pics;
      		if(field == 'pic2') that.pic2 = pics;
      	},pernum);
      },
      removeimg:function(e){
      	var that = this;
      	var index= e.currentTarget.dataset.index
      	var field= e.currentTarget.dataset.field
      	if(field == 'pic'){
      		var pics = that.pic
      		pics.splice(index,1);
      		that.pic = pics;
      	}else if(field == 'pic2'){
      		var pics = that.pic2
      		pics.splice(index,1);
      		that.pic2 = pics;
      	}
      },
		}
	}
</script>

<style>
  page{
    height: auto;
    min-height: 100%;
    background: #fff;
  }
  .view-width{width: 100%;height: auto;position: relative;}
  .head-class{height: 375rpx;color: #fff;;position: relative;}
  .navigation {
  	position: absolute;
  	padding: 10rpx 30rpx;
  	width: 100%;
  	box-sizing: border-box;
  	display: flex;
  	z-index: 10;
  }
  .imgback{width: 40rpx;height: 40rpx;}
  .log{position: absolute;right: 0;padding:0 10rpx 0 30rpx;line-height:70rpx;border-radius: 70rpx 0 0 70rpx;}
  .taskprogress-wc{width: 100%;position: absolute;}
  .task-wc{width: 700rpx;margin: 30rpx auto;font-weight: bold;}
  .taskprogress{width: 700rpx;margin: 0 auto;display: flex;justify-content: space-between;margin-top: 20rpx;border-radius: 20rpx 20rpx; overflow: hidden;}
  .tasklist{width: 700rpx;margin: 0 auto;background-color: #fff;border-radius: 4rpx;}
  .task-item{border-bottom: 2rpx solid #f1f1f1;align-items: center;border-radius: 20rpx;overflow: hidden;margin-bottom: 20rpx;}
  .task-item:last-child{margin-bottom: 0;}
  .task-item-bottom{font-size: 28rpx;padding: 20rpx;display: flex;align-items: center;justify-content: space-between;}
  .opt{width: 140rpx;height: 60rpx;line-height: 60rpx;border-radius: 60rpx 60rpx;text-align: center;color:#fff}
  
  #mask-rule,#mask {position: fixed;left: 0;top: 0;z-index: 999;width: 100%;height: 100%;background-color: rgba(0, 0, 0, 0.85);}
  #mask-rule .box-rule {position: relative;margin: 30% auto;width: 95%;height: 800rpx;border-radius: 8rpx;background-color: #fff;}
  #mask-rule .content{height:670rpx;}
  #mask-rule .box-rule .star {position: absolute;left: 50%;top: -100rpx;margin-left: -130rpx;width: 259rpx;height:87rpx;}
  #mask-rule .box-rule .h2 {width: 100%;text-align: center;line-height: 34rpx;font-size: 34rpx;font-weight: normal;padding: 20rpx 0;}
  #mask-rule #close-rule {position: absolute;right: 34rpx;top: 24rpx;width: 36rpx;height: 36rpx;}
  
  .radio{transform:scale(.7);}
  .checkbox{transform:scale(.7);}
  .container{display:flex;flex-direction:column}
  .addfromwx{width:94%;margin:20rpx 3% 0 3%;border-radius:5px;padding:20rpx 3%;background: #FFF;display:flex;align-items:center;color:#666;font-size:28rpx;}
  .addfromwx .img{width:40rpx;height:40rpx;margin-right:20rpx;}
  .form{ width:710rpx;margin:20rpx auto;border-radius:5px;padding: 0 3%;background: #FFF;}
  .form-item{display:flex;align-items:center;width:100%;border-bottom: 1px #ededed solid;height:98rpx;}
  .form-item:last-child{border:0}
  .form-item .label{ color:#8B8B8B;font-weight:bold;height: 60rpx; line-height: 60rpx; text-align:left;width:160rpx;padding-right:20rpx}
  .form-item .input{ flex:1;height: 60rpx; line-height: 60rpx;text-align:right}
  .itemright{flex: 1;display: flex;justify-content: flex-end;}
  
  .savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 60rpx 5%; border: none; }
  
  .layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
  .layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
  .layui-imgbox-img>image{max-width:100%;}
  .layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
  .uploadbtn{position:relative;height:200rpx;width:200rpx}
</style>
