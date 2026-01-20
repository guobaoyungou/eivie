<template>
<view class="container">
	<block v-if="isload">
		<dd-tab :itemdata="['全部','待处理','处理中 ','已完成','待支付']" :itemst="['all','0','1','2','10']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
		<view style="width:100%;height:90rpx"></view>
		
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入关键字搜索"  @input="searchInput"  placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
			</view>
			<view style="margin: 0 20rpx;">
				
				<picker @change="pickerChange" :value="cindex" :range="cateArr" range-key="name" style="height:80rpx;line-height:80rpx;border-bottom:1px solid #EEEEEE">
					<view class="picker">{{cindex==-1? '请选择工单' : cateArr[cindex].name}}</view>
				</picker>
			</view>
		</view>
		<view class="searchbox">
		
			<!-- 日期选择 -->
			<view class="date">
				<view class="begindate">
					<picker mode="date" :value="begindate" :start="startDate" :end="endDate" @change="bindDateChange" >
						<view class="uni-input">{{begindate?begindate:'选择开始日期'}}</view>
					</picker>				
				</view>
				-
				<view class="enddate">
					<picker mode="date" :value="enddate" :start="startDate" :end="endDate" @change="bindDateChange2">
						<view class="uni-input"> {{enddate?enddate:'选择结束日期'}}</view>
					</picker>		
				</view>
			</view>
			<view class="searchbtn" :style="{background:t('color1')}" @tap="search">搜索</view>
		</view>
		
		
		<view class="content" id="datalist">
			<view class="item" v-for="(item, index) in datalist" :key="index">
				<view class="f1"  @tap.stop="goto" :data-url="'formdetail?id=' + item.id">
						<view class="itembox">
							<view style="justify-content: space-between;">
								<view class="t1" >用户昵称：{{item.nickname}}</view>
								<view class="t1" >工单类型：{{item.cname}}</view>	
								<text class="t1" >工单名称：{{item.title}}</text>
							</view>	
							<view class="f2" >
								<text class="t1" v-if="item.status==0 && (!item.payorderid||item.paystatus==1)" style="color:#88e" @tap.stop="goto"  :data-url="'jindu?id='+item.id+'&cid='+item.cid" :data-status="item.status"  >{{item.clname}}</text>
								<text class="t1" v-if="item.status==0 && item.payorderid && item.paystatus==0" style="color:red">待支付</text>
								<text class="t1" v-if="item.status==1" style="color:green" @tap.stop="goto"  :data-url="'jindu?id='+item.id+'&cid='+item.cid" :data-status="item.status" >{{item.clname}}</text>
								<text class="t1" v-if="item.status==2" style="color:green">已完成</text>
								<text class="t1" v-if="item.status==-1" style="color:red">已驳回</text>
							</view>
						</view>	
						<view class="flex" style="justify-content: space-between; margin-top: 20rpx;">
							<view  @tap.stop="goto" :data-url="'formdetail?id=' + item.id">
								<text class="t2">提交时间：{{item.createtime}}</text>
								<text class="t2" v-if="item.paynum" user-select="true" selectable="true">{{item.paynum}}</text>
							</view>
							<view class="jindu" @tap.stop="goto" :data-url="'jindu?id='+item.id+'&cid='+item.cid" :data-status="item.status">查看进度</view>
						</view>
			
			
			
				</view>
	
			</view>
		</view>
		<view class="modal" v-if="ishowjindu">
			<view class="modal_jindu">
					<view class="close" @tap="closejd"><image :src="pre_url+'/static/img/close.png'" /></view>
					<block v-if="jdlist.length>0">
			
						<view class="item " v-for="(item,index) in jdlist" :key="index" style="display: flex;">
							<view class="f1"><image :src="'/static/img/jindu' + (index==0?'2':'1') + '.png'"></image></view>
							<view class="f2">
								<text class="t2"> 时间：{{item.time}}</text>
								<text class="t1">{{item.desc}}({{item.remark}}) </text>
								<view v-if="item.content_pic.length>0" v-for="(pic, ind) in item.content_pic">
										<view class="layui-imgbox-img"><image :src="pic" @tap="previewImage" :data-url="pic" mode="widthFix"></image></view>
								</view>
								<view v-for="(hf,hfindex) in item.hflist" :key="hfindex">
									<view class="t3" v-if="hf.hfremark" >用户回复：{{hf.hfremark}} </view>
									<view class="t4" v-if="hf.hftime" >回复时间：{{hf.hftime}} </view>
									<view v-if="hf.hfcontent_pic.length>0" v-for="(pic2, ind2) in hf.hfcontent_pic">
											<view class="layui-imgbox-img"><image :src="pic2" @tap="previewImage" :data-url="pic2" mode="widthFix"></image></view>
									</view>
								</view>
							</view>
						</view>
					</block>
					<block v-else-if="statuss==-1">
							<view style="font-size:14px;color:#f05555;padding:10px;">工单已驳回</view>
					</block>
					<block v-else>
							<view style="font-size:14px;color:#f05555;padding:10px;">等待处理</view>
					</block>
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
		const currentDate = this.getDate({
				format: true
		})
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			statuss:0,
      st: 'all',
      datalist: [],
      pagenum: 1,
      nomore: false,
			lclist:[],
			current:0,
			ishowjindu:false,
			jdlist:[],
			content_pic:[],
			cid:0,
			begindate:'',
			enddate:'',
			keyword:'',
			nodata: false,
			cateArr:[],
			cindex:-1,
			pre_url:app.globalData.pre_url,
    };
  },
	computed: {
			startDate() {
					return this.getDate('start');
			},
			endDate() {
					return this.getDate('end');
			}
	},
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 'all';
		this.cid = this.opt.cid
		this.cateid = this.opt.cateid
		this.getdata();
		this.getliucheng();
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
		getDate(type) {
				const date = new Date();
				let year = date.getFullYear();
				let month = date.getMonth() + 1;
				let day = date.getDate();
				if (type === 'start') {
						year = year - 60;
				} else if (type === 'end') {
						year = year;
				}
				month = month > 9 ? month : '0' + month;
				day = day > 9 ? day : '0' + day;
				return `${year}-${month}-${day}`;
		},
		searchInput:function(e){
			this.keyword = e.detail.value
		},
		search:function(e){
			  var that = this;
				that.getdata();
		},
		bindDateChange: function(e) {
				this.begindate = e.detail.value
		},
		bindDateChange2: function(e) {
				this.enddate = e.detail.value
		},
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
			if(this.cindex!=-1){
				that.cateid = that.cateArr[this.cindex].id;
			}
      app.post('ApiAdminWorkorder/formlog', {keyword:that.keyword, begindate:that.begindate,enddate:that.enddate, st: st,pagenum: pagenum,cid:that.cid,formid:that.cateid}, function (res) {
				that.loading = false;
        var data = res.data;
				that.cateArr = res.catelist
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
		getliucheng:function(e){
			var that=this
			app.post('ApiAdminWorkorder/getliucheng', {}, function (res) {
					var lclist = res.datalist;
					that.lclist = lclist;
					
			});
		},
		radioChange:function(e){
			var that=this
			console.log(e)
		},
		jindu:function(e){
			var that=this
			that.ishowjindu=true
			var id = e.currentTarget.dataset.id
			that.statuss = e.currentTarget.dataset.status
			//读取进度表
			app.post('ApiWorkorder/selectjindu', { id: id }, function (res) {
					if(res.status==1){
						var data = res.data
						that.jdlist =data
					}
				
			})
		},
		closejd:function(e){
			var that=this
			that.ishowjindu=false
		},
		pickerChange: function (e) {
		  this.cindex = e.detail.value;
		},
  }
}
</script>
<style>

	.content{ width:100%;margin:0;}
	.content .item{ width:94%;margin:20rpx 3%;;background:#fff;border-radius:16rpx;padding:30rpx 30rpx;display:flex;align-items:center;}
	.content .item:last-child{border:0}
	.content .item .f1{width:100%;display:flex;flex-direction:column}
	.content .item .f1 .t1{color:#000000;font-size:30rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis; height:50rpx}
	.content .item .f1 .t2{color:#666666;margin-top:10rpx}
	.content .item .f1 .t3{color:#666666}
	.content .item .f2{width:20%;font-size:32rpx;text-align:right}
	.content .item .f2 .t1{color:#03bc01}
	.content .item .f2 .t2{color:#000000}
	.content .item .f3{ flex:1;font-size:30rpx;text-align:right}
	.content .item .f3 .t1{color:#03bc01}
	.content .item .f3 .t2{color:#000000}
	.content .item .f1 .itembox{ display: flex; justify-content: space-between;}
	
	
	.jindu{ border: 1rpx solid #ccc; font-size: 24rpx; padding: 5rpx 10rpx; border-radius: 10rpx; color: #555;}
	
	
	.topsearch{width:94%;margin:10rpx 3%;}
	.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
	.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
	.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
	
	.searchbox{ width: 100%;background: #FFFFFF;padding:20rpx 26rpx;z-index: 9999;display: flex;justify-content: space-between;align-items: center; }
	.searchbox .picker{display: flex;justify-content: space-between;align-items: center;border:1rpx solid #e0e0e0;border-radius: 6rpx;padding:0 12rpx;height: 64rpx;line-height: 70rpx; }
	.searchbox .picker .picker-txt{text-overflow: ellipsis;white-space: nowrap;overflow: hidden;height: 70rpx; width: 300rpx;}
	.searchbox .down{width: 20rpx;height: 20rpx;margin-left: 10rpx;flex-shrink: 0;}
	.pickerD{width: 50%;margin-left: 20rpx;}
	.searchbox .cates{ width: 30%;overflow: hidden;}
	.date{ display: flex;align-items: center;}
	.date .begindate{ }
	.date .begindate .uni-input{ color: grey;}
	.date .enddate { margin:0 10rpx;}
	.date .enddate .uni-input{  color: grey;}
	.searchbtn{ display: flex; width: 120rpx;height: 50rpx;color: #fff;line-height: 50rpx; border-radius:50rpx;align-items: center;justify-content: center;}
	
	.modal{ position: fixed; background:rgba(0,0,0,0.3); width: 100%; height: 100%; top:0; z-index: 100;}
	.modal .modal_jindu{ background: #fff; position: absolute; top: 20%; align-items: center; margin: auto; width: 90%; left: 30rpx; border-radius: 10rpx; padding: 40rpx;overflow-y:auto; display: flex; flex-wrap: wrap; max-height: 600rpx;}
	.modal_jindu .close image { width: 30rpx; height: 30rpx;position: fixed; top:21%;right: 60rpx;}
	.modal_jindu .title{ font-size: 32rpx; font-weight: bold;}
	.uni-list{ margin-top: 30rpx;}
	.uni-list-cell{ display: flex; height: 80rpx;}
	.beizhu label{ width: 100rpx;}
	.modal_jindu .btn{  background: #1658c6; border-radius: 3px;line-height: 24px; border: none; padding: 0 10px;color: #fff;font-size: 20px; text-align: center; width: 300px;  display: flex; height: 40px; justify-content: center;align-items: center;}
	.beizhu textarea{  height: 100rpx;}
	
	.modal_jindu .item{ }
	.modal_jindu .item .f1{ position:relative}
	/*.logistics img{width: 15px; height: 15px; position: absolute; left: -8px; top:11px;}*/
	.modal_jindu .item .f1 image{width: 30rpx; height: 100rpx; position: absolute; left: -16rpx; top: 0rpx;}
	.modal_jindu .item .f2{display:flex;flex-direction:column;flex:auto;padding:10rpx 0; margin-left: 30rpx;}
	.modal_jindu .item .f2 .t1{font-size: 30rpx; width: 100%;word-break:break-all; }
	.modal_jindu .item .f2 .t1{font-size: 26rpx;}
	.modal_jindu .item .f2 .t3{font-size: 24rpx; color:#008000; margin-top: 10rpx;}
	.modal_jindu .item .f2 .t4{font-size: 24rpx;  color:#008000;}
	
	
	.layui-imgbox-img{display: block;width:150rpx;height:150rpx;padding:2px;background-color: #f6f6f6;overflow:hidden ;margin-top: 20rpx;}
	.layui-imgbox-img>image{max-width:100%;}
</style>