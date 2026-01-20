<template>
<view class="container">
	<block v-if="isload">

		<dd-tab :itemdata="['全部','待处理','处理中','已处理','待支付']" :itemst="['all','0','1','2','10']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
		<view style="width:100%;height:90rpx"></view>

		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" placeholder="输入关键字搜索"  @input="searchInput"  placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
			</view>
		</view>
		<view class="searchbox">
			<view class="cates">
				<uni-data-picker :localdata="cateList" :border="false" :placeholder="catenames || '请选择类型'" @change="catechange"></uni-data-picker>
			</view>
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
			<view class="item"  v-for="(item, index) in datalist" :key="index">
				<view class="itembox">
						<view class="f1">
							<view class="title">
								<view class="t11">用户姓名：<text class="t1" @tap="goto" :data-url="'detail?id='+item.id">{{item.nickname}}</text>	</view>
								<view class="t11">工单类型：<text class="t1" @tap="goto" :data-url="'detail?id='+item.id">{{item.cname}}</text>	</view>
								<view class="t11">工单名称：<text class="t1" @tap="goto" :data-url="'detail?id='+item.id">{{item.title}}</text>	</view>
							</view>
							
							<view class="f2">
								<text class="t1" v-if="item.status==0 && (!item.payorderid||item.paystatus==1)" style="color:#88e">等待处理</text>
								<text class="t1" v-if="item.status==0 && item.payorderid && item.paystatus==0" style="color:red">待支付</text>
								<text class="t1" v-if="item.status==1" style="color:green">{{item.clname}}</text>
								<text class="t1" v-if="item.status==2" style="color:red">已完成</text>
								<text class="t1" v-if="item.status==-1" style="color:red">已驳回</text>
							</view>
						</view>
						<view class="flex" style="justify-content: space-between;">
							<view class="time">提交时间：<text class="t2" @tap="goto" :data-url="'detail?id='+item.id">{{item.createtime}}</text></view>
							<view class="jindu" @tap="goto" :data-url="'/pagesA/workorder/workjindu?id='+item.id"  v-if="!item.payorderid || (item.payorderid && item.paystatus==1)" >查看进度</view>
						</view>
						<block v-if="item.ordertype==1 && item.glordernum && item.orderid">
							<view class="flex" style="justify-content: space-between;">
								<text class="t2" @tap="goto" :data-url="'pages/order/detail?id='+item.orderid">关联订单：商城订单（{{item.glordernum}}）</text>
							</view>
						</block>
						<block v-if="item.ordertype==2 && item.glordernum && item.orderid">
							<view class="flex" style="justify-content: space-between;">
								<text class="t2" @tap="goto" :data-url="'/activity/yuyue/orderdetail?id='+item.orderid">关联订单：预约订单（{{item.glordernum}}）</text>
							</view>
						</block>
				</view>
			</view>
		</view>
		
		<view class="modal" v-if="ishowjindu">
			<view class="modal_jindu">
				<form @submit="formsubmit" style="width: 100%;">
						<view class="close" @tap="close"><image :src="pre_url+'/static/img/close.png'" /></view>
						<block v-if="jdlist.length>0">
							<view class="item " v-for="(item,index) in jdlist" :key="index">
								<view class="f1"><image :src="'/static/img/jindu' + (index==0?'2':'1') + '.png'"></image></view>
								
								<view class="f2">
									<text class="t2"> 时间：{{item.time}}</text>
									<text class="t1">{{item.desc}}({{item.remark}}) </text>
									<view v-if="item.content_pic.length>0" v-for="(pic, ind) in item.content_pic">
											<view class="layui-imgbox-img"><image :src="pic" @tap="previewImage" :data-url="pic" mode="widthFix"></image></view>
									</view>
									<view v-for="(hf,hfindex) in item.hflist" :key="hfindex">
										<view class="t3" v-if="hf.hfremark" >我的回复：{{hf.hfremark}} </view>
										<view class="t4" v-if="hf.hftime" >回复时间：{{hf.hftime}} </view>
										<view v-if="hf.hfcontent_pic.length>0" v-for="(pic2, ind2) in hf.hfcontent_pic">
												<view class="layui-imgbox-img"><image :src="pic2" @tap="previewImage" :data-url="pic2" mode="widthFix"></image></view>
										</view>
									</view>
								</view>
							</view>
							<input type="hidden" name="lcid" :value="jdlist[0].id" style="display: none;" />
							
							<view class="huifu" v-if="statuss!=2">
								<view class="form-item4 flex-col">
									<view class="label"><label>回复:</label></view>
									<view id="content_picpreview" class="flex" style="flex-wrap:wrap;padding-top:20rpx">
										<view v-for="(item, index) in content_pic" :key="index" class="layui-imgbox">
											<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="content_pic" ><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
											<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
											<!-- <view class="layui-imgbox-repeat" bindtap="xuanzhuan" data-index="{{index}}" data-field="content_pic" wx:if="{{!comment.id}}"><text class="fa fa-repeat"></text></view> -->
										</view>
										<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 40rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="content_pic" v-if="content_pic.length<5"></view>
									</view>
								</view>
								<view class="hfbox"  >
										<textarea placeholder="输入回复内容" name="content" :value="jdlist[0].hfremark" maxlength="-1"></textarea>
								</view>
								<view class="btnbox">
									<view class="f1">
										<button class="btn1" @tap="confirmend" >确认结束</button>
										<button class="btn2" form-type="submit" >提交</button>
									</view>
								</view>
							</view>
							<view class="btnbox" v-if="statuss==2 && iscomment==0">
								<view class="pjitem">
									<view class="item">
										<view style="margin-right: 10rpx;">满意度</view>
										<radio-group @change="radioChange">
											<label class="radio"><radio value="1"   />不满意</label>
											<label class="radio"><radio value="2" />一般</label>
											<label class="radio"><radio value="3" />满意</label>
										</radio-group>	
									</view>
										<button class="btn1" @tap="tocomment" >确认评价</button>
								</view>
							</view>
							<view class="btnbox" v-if="statuss==2 && iscomment==1">
								<view class="pjitem">
									<view class="item">
										<view style="margin-right: 10rpx;">满意度:</view>
										<view  class="t1" v-if="comment_status==1"  style="color:red">不满意</view>
										<view class="t1" v-if="comment_status==2" style="color:#88e">一般</view>
										<view class="t1" v-if="comment_status==3" style="color:green">满意</view>
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
					</form>
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
			nodata:false,
      st: 'all',
      datalist: [],
      pagenum: 1,
      nomore: false,
			ishowjindu:false,
			jdlist:[],
			statuss:0,
			iscomment:0,
			comment_status:0,
			commentstatus:0,
			content_pic: [],
			tempFilePaths: "",
			pre_url:app.globalData.pre_url,
			cateList:[],
		  childCate: [],
			catenames:'',
			keyword:'',
			begindate:'',
			enddate:''
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
		this.formid = this.opt.formid || '';
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdatalist();
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore) {
      this.pagenum = this.pagenum + 1;
      this.getdatalist(true);
    }
  },
  methods: {
		getdata:function(){
			var that=this
			this.loading = true;
			app.get('ApiWorkorder/record', { }, function (res) {
					that.loading = false;
					var cateList = res.catelist;	 
					that.cateList = cateList;
					console.log(cateList);
					that.getdatalist()
			})
		},
		catechange(e) {
			var that=this
			const value = e.detail.value
			if(value[0].value>0){
				that.catenames = value[1].text;
				that.cid = value[1].value
			}else{
				that.catenames =  value[0].text;
				that.cid = value[0].value
			}

		},
		searchInput:function(e){
			this.keyword = e.detail.value
		},
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
		bindDateChange: function(e) {
				this.begindate = e.detail.value
		},
		bindDateChange2: function(e) {
				this.enddate = e.detail.value
		},
		search:function(e){
			  var that = this;
				that.getdatalist();
		},
    changetab: function (st) {
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
    getdatalist: function (loadmore) {
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
      app.post('ApiWorkorder/record', { keyword:that.keyword, cid:that.cid, begindate:that.begindate,enddate:that.enddate, formid:that.formid,st: st,pagenum: pagenum}, function (res) {
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
			that.id = id;
			that.statuss = e.currentTarget.dataset.status
			that.iscomment = e.currentTarget.dataset.iscomment
			that.comment_status = e.currentTarget.dataset.comment_status
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
		},
		formsubmit: function (e) {
		  var that = this;		
		  var formdata = e.detail.value;
		  var content = formdata.content;

		  if (content == '') {
		    app.error('请填写回复内容');
		    return false;
		  }
	  	var content_pic = that.content_pic;
			app.showLoading();
		  app.post('ApiWorkorder/addhuifu', {formdata:formdata,hfcontent_pic:content_pic.join(',')}, function (res) {
				var res  = res
		  	app.showLoading(false);
		    if (res.status == 1) {
		      app.success('回复成功');
					setTimeout(function () {
					  that.getdata();
						that.ishowjindu=false
					}, 1000);
			
		    } else {
		      app.alert(res.msg);
		    }
		
		  });
		},
		
		confirmend: function (e) {
		  var that = this;			
			app.showLoading();
			var id = that.id
		  app.post('ApiWorkorder/confirmend', {id:id}, function (res) {
				var res  = res
		  	app.showLoading(false);
		    if (res.status == 1) {
		      app.success('操作成功');
					setTimeout(function () {
					  that.getdata();
						that.ishowjindu=false
					}, 1000);
		    } else {
		      app.alert(res.msg);
		    }
		
		  });
		},
		radioChange: function(evt) {
			 var that=this
			 var commentstatus = evt.detail.value
			 that.commentstatus = commentstatus
		},
		tocomment: function (e) {
		  var that = this;			
			app.showLoading();
			var id = that.id
			if(!that.commentstatus){
				 app.error('请选择满意度');return;
			}
		  app.post('ApiWorkorder/tocomment', {id:id,commentstatus:that.commentstatus}, function (res) {
				var res  = res
		  	app.showLoading(false);
		    if (res.status == 1) {
		      app.success('评价成功');
					setTimeout(function () {
					  that.getdata();
						that.ishowjindu=false
					}, 1000);
		    } else {
		      app.alert(res.msg);
		    }
		
		  });
		},
		uploadimg:function(e){
			var that = this;
			var field= e.currentTarget.dataset.field
			var pics = that[field]
			if(!pics) pics = [];
		
			app.chooseImage(function(urls){
				for(var i=0;i<urls.length;i++){
					pics.push(urls[i]);
				}
				console.log(pics);
			},5)
		},
		removeimg:function(e){
			var that = this;
			var index= e.currentTarget.dataset.index
			var field= e.currentTarget.dataset.field
			var pics = that[field]
			pics.splice(index,1)
		},
  }
}
</script>
<style>
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
	
	
	.content{ width:100%;margin:0;}
	.content .item{ width:94%;margin:20rpx 3%;;background:#fff;border-radius:16rpx;padding:30rpx 30rpx;display:flex;align-items:center;}
	.content .item:last-child{border:0}
	.itembox{ width: 100%;}
	.content .item .f1{width:100%;display:flex;justify-content: space-between;}
	.content .item .f1 .title{ display: flex;flex-direction: column;font-size: 26rpx; color: #999;}
	.content .item .f1 .t11{ height: 50rpx;}
	.content .item .f1 .t1{color:#000000;font-size:26rpx;word-break:break-all;overflow:hidden;text-overflow:ellipsis;}
	.content .item .time{color:#999; }
	.content .item .t2{color:#666666;margin-top:10rpx}
	
	.content .item .f1 .t3{color:#666666}
	.content .item .f2{width:20%;font-size:24rpx;text-align:right}
	.content .item .f2 .t1{color:#03bc01;font-size:24rpx;}
	.content .item .f2 .t2{color:#000000}

	.jindu{ border: 1rpx solid #ccc; font-size: 24rpx; padding: 10rpx 20rpx; border-radius: 10rpx; color: #555;}
	
	.modal{ position: fixed; background:rgba(0,0,0,0.3); width: 100%; height: 100%; top:0; z-index: 1;}
	.modal .modal_jindu{ background: #fff; position: absolute; top: 20%; align-items: center; margin: auto; width: 90%; left: 30rpx; border-radius: 10rpx; padding: 40rpx;max-height: 600rpx; overflow-y:auto;  display: flex; flex-wrap: wrap;}
	.modal_jindu .close image { width: 30rpx; height: 30rpx;position: fixed; top:21%;right: 60rpx;}
	.modal_jindu .on{color: #23aa5e;}
	.modal_jindu .item{display:flex;width: 96%;padding:0 0}
	.modal_jindu .item .f1{position:relative}
	/*.logistics img{width: 15px; height: 15px; position: absolute; left: -8px; top:11px;}*/
	.modal_jindu .item .f1 image{width: 30rpx; height: 100rpx; position: absolute; left: -16rpx; top: 0rpx;}
	.modal_jindu .item .f2{display:flex;flex-direction:column;flex:auto;padding:10rpx 0; margin-left: 30rpx;}
	.modal_jindu .item .f2 .t1{font-size: 26rpx;width: 100%;word-break:break-all;}
	.modal_jindu .item .f2 .t2{font-size: 26rpx; }
	.modal_jindu .item .f2 .t3{font-size: 24rpx; color:#008000; margin-top: 10rpx;}
	.modal_jindu .item .f2 .t4{font-size: 24rpx;  color:#008000;}
		
	.modal_jindu .hfbox{ margin-top: 30rpx; display: flex;}
	.modal_jindu .hfbox label{ width: 120rpx;}
	.modal_jindu .hfbox textarea{ border: 1rpx solid #f5f5f5; padding: 10rpx 20rpx; font-size: 24rpx;height: 100rpx; border-radius: 5rpx;}
	.modal_jindu .btnbox {display: flex; margin-top: 30rpx;  width: 100%;  justify-content: flex-end;}	
	.modal_jindu .btnbox .f1{ display: flex;}
	.modal_jindu .btnbox .btn1,	.modal_jindu .btnbox .btn2{ display: flex; padding: 0 20rpx;font-size: 24rpx; height: 60rpx; border-radius: 6rpx; width: 160rpx; align-items: center;justify-content: center;}
	.modal_jindu .btnbox .btn1{ background: #F2B93B; color: #fff; margin-right: 20rpx;}
	.modal_jindu .btnbox .btn2{ background: #8CB6C0; color: #fff;}
	
	.pjitem{ width: 100%;}
	.pjitem radio{ transform:scale(0.7)}
	.pjitem label{ margin-right: 20rpx;}
	.pjitem .btn1{ margin-top: 30rpx;}
	.pjitem .t1{ color:#008000;}
	
	.form-item4{width:100%;background: #fff; padding: 20rpx 0rpx;margin-top:1px}
	.form-item4 .label{ width:150rpx;}
	.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
	.layui-imgbox-img{display: block;width:150rpx;height:150rpx;padding:2px;background-color: #f6f6f6;overflow:hidden ;margin-top: 20rpx;}
	.layui-imgbox-img>image{max-width:100%;}
	.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
	.uploadbtn{position:relative;height:150rpx;width:150rpx}
	.layui-imgbox-img{display: block;width:150rpx;height:150rpx;padding:2px;background-color: #f6f6f6;overflow:hidden ;margin-top: 20rpx;}
	.layui-imgbox-img>image{max-width:100%;}

</style>