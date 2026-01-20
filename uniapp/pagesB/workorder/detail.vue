<template>
<view class="container">
	<block v-if="isload">
		<view class="orderinfo">
			<view class="item">
				<text class="t1">工单名称</text>
				<text class="t2">{{detail.title}}</text>
			</view>
			<block v-for="(fitem,findex) in formcontent" class="item" >
				<view class="dp-form-separate" @tap.stop="formChange" :data-index="findex" >{{fitem.name}}<image :src="pre_url+'/static/img/workorder/'+(currentindex==findex?'down':'up')+'.png'"></view>
				<view class="parentitem" :style="(currentindex!=findex?'display:none':'')">
					<view class="item" v-for="(item,idx) in fitem.list" >
						<text class="t1">{{item.val1}}</text>
						<text class="t2" v-if="item.key!='upload' && item.key!='upload_file' && item.key!='upload_video'" >{{detail['form'+idx]}}</text>
						<view class="t2" style="display: flex; justify-content: flex-end;"  v-if="item.key=='upload'">
							<view v-for="(sub, indx) in detail['form'+idx]" :key="indx">
								<image :src="sub" style="width:50px; margin-left: 10rpx;" mode="widthFix" @tap="previewImage" :data-url="sub"></image>
							</view>
						</view>
						<!-- #ifdef !H5 && !MP-WEIXIN -->
						<view class="t2" v-if="item.key=='upload_file'" style="overflow: hidden;white-space: pre-wrap;word-wrap: break-word;color: #4786BC;">
								{{detail['form'+idx]}}
						</view>
						<!-- #endif -->
						<!-- #ifdef H5 || MP-WEIXIN -->
						<view class="t2" v-if="item.key=='upload_file'"  @tap="download" :data-file="detail['form'+idx]" style="overflow: hidden;white-space: pre-wrap;word-wrap: break-word;color: #4786BC;">
								点击下载查看
						</view>
						<!-- #endif -->
						<view class="t2"  v-if="item.key=='upload_video'">
								<video  :src="detail['form'+idx]" style="width: 100%;"/></video>
						</view>	
					</view>
				</view>	
			</block>
			
			<view class="item">
				<text class="t1">提交时间</text>
				<text class="t2">{{detail.createtime}}</text>
			</view>
			<view class="item">
				<text class="t1">审核状态</text>
				<text class="t2" v-if="detail.status==0 && (!detail.payorderid||detail.paystatus==1)" style="color:#88e">待处理</text>
				<text class="t2" v-if="detail.status==0 && detail.payorderid && detail.paystatus==0" style="color:red">待支付</text>
				<text class="t2" v-if="detail.status==1" style="color:#F7B52D">处理中</text>
				<text class="t2" v-if="detail.status==2" style="color:green">已完成</text>
				<text class="t2" v-if="detail.status==-1" style="color:red">已驳回</text>
			</view>
			<view class="item" v-if="detail.status==-1">
				<text class="t1">驳回原因</text>
				<text class="t2" style="color:red">{{detail.reason}}</text>
			</view>
			<block v-if="detail.payorderid">
			<view class="item">
				<text class="t1">付款金额</text>
				<text class="t2" style="font-size:32rpx;color:#e94745">￥{{detail.money}}</text>
			</view>
			<view class="item">
				<text class="t1">付款方式</text>
				<text class="t2">{{detail.paytype}}</text>
			</view>
			<view class="item">
				<text class="t1">付款状态</text>
				<text class="t2" v-if="detail.paystatus==1 && detail.isrefund==0" style="color:green">已付款</text>
				<text class="t2" v-if="detail.paystatus==1 && detail.isrefund==1" style="color:red">已退款</text>
				<text class="t2" v-if="detail.paystatus==0" style="color:red">未付款</text>
			</view>
			<view class="item" v-if="detail.paystatus>0 && detail.paytime">
				<text class="t1">付款时间</text>
				<text class="t2">{{detail.paytime}}</text>
			</view>
			</block>
			<block v-if="detail.ordertype==1 && detail.glordernum && detail.orderid">
				<view class="item">
					<text class="t1">关联订单</text>
					<text class="t2"  @tap="goto" :data-url="'pages/order/detail?id='+detail.orderid">商城订单（{{detail.glordernum}})
					</text>
				</view>
			</block>	
			<block v-if="detail.ordertype==2 && detail.glordernum && detail.orderid">
				<view class="item" >
					<text class="t1" >关联订单</text>
					<text class="t2" @tap="goto" :data-url="'/activity/yuyue/orderdetail?id='+detail.orderid">预约订单（{{detail.glordernum}})
					</text>
				</view>
			</block>	
			
			<block v-if="detail.iscomment==1">
				<view class="item">
					<text class="t1">满意度</text>
					<text class="t2" v-if="detail.comment_status==1" style="color:red">不满意</text>
					<text class="t2" v-if="detail.comment_status==2" style="color:#88e">一般</text>
					<text class="t2" v-if="detail.comment_status==3" style="color:green">满意</text>
					</text>
				</view>
			</block>	
		</view>
		
		<view style="width:100%;height:160rpx"></view>

		<view class="bottom notabbarbot">
			<view class="btn2" v-if="detail.status==2 && detail.iscomment==0" @tap="jindu"  >评价</view>
			<view class="btn2" @tap="todel" :data-id="detail.id">删除</view>
			<view class="btn2" @tap="goto" :data-url="detail.fromurl" v-if="detail.fromurl">再次提交</view>
			<block v-if="detail.payorderid && detail.paystatus==0">
				<view class="btn1" :style="{background:t('color1')}" @tap="goto" :data-url="'/pagesExt/pay/pay?id=' + detail.payorderid">去付款</view>
			</block>
		</view>
		
		<view class="modal" v-if="ishowjindu">
			<view class="modal_jindu">
				<form @submit="formsubmit">
					<view class="close" @tap="close"><image :src="pre_url+'/static/img/close.png'" /></view>
					<block v-if="jdlist.length>0">
						<view class="item " v-for="(item,index) in jdlist" :key="index">
							<view class="f1"><image :src="'/static/img/jindu' + (index==0?'2':'1') + '.png'"></image></view>
							<view class="f2">
								<text class="t2"> 时间：{{item.time}}</text>
								<text class="t1">{{item.desc}}({{item.remark}}) </text>
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
						<view class="hfbox"  v-if="detail.status!=2">
								<label>回复:</label><textarea placeholder="输入内容" name="content" :value="jdlist[0].hfremark" maxlength="-1"></textarea>
						</view>
						<view class="btnbox" v-if="detail.status!=2">
							<view class="f1">
								<button class="btn1" @tap="confirmend" >确认结束</button>
								<button class="btn2" form-type="submit" >提交</button>
							</view>
						</view>
						<view class="btnbox" v-if="detail.status==2 && detail.iscomment==0">
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
						<view class="btnbox" v-if="detail.status==2 && detail.iscomment==1">
							<view class="pjitem">
								<view class="item">
									<view style="margin-right: 10rpx;">满意度:</view>
									<view  class="t1" v-if="comment_status==1">不满意</view>
									<view class="t1" v-if="comment_status==2">一般</view>
									<view class="t1" v-if="comment_status==3">满意</view>
								</view>
							</view>
						</view>
					</block>
					<block v-else>
							<view style="font-size:14px;color:#f05555;padding:10px;">等待处理</view>
					</block>
				</form>
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

export default {
  data() {
    return {
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			pre_url:app.globalData.pre_url,
			detail:{},
			formcontent:[],
			ishowjindu:false,
			jdlist:[],
			currentindex:0
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiWorkorder/formdetail', {id: that.opt.id}, function (res) {
				that.loading = false;
				that.form = res.form;
				that.formcontent = res.formcontent;
				that.detail = res.detail;
				that.loaded();
			});
		},
		todel:function(e){
			var id = e.currentTarget.dataset.id;
			app.confirm('确定要删除吗?',function(){
				app.showLoading('删除中');
        app.post('ApiWorkorder/formdelete', {id: id}, function (data) {
					app.showLoading(false);
          app.success(data.msg);
          setTimeout(function () {
            app.goback(true);
          }, 1000);
        });
			})
		},
		jindu:function(e){
			var that=this
			that.ishowjindu=true
			var id = that.detail.id
			//读取进度表
			app.post('ApiWorkorder/selectjindu', { id: id }, function (res) {
					if(res.status==1){
						var data = res.data
						that.jdlist =data
					}
			})
		},
		radioChange: function(evt) {
			 var that=this
			 var commentstatus = evt.detail.value
			 that.commentstatus = commentstatus
		},
		close:function(e){
			var that=this
			that.ishowjindu=false
		},
		tocomment: function (e) {
		  var that = this;			
			app.showLoading();
			var id = that.detail.id
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
		download:function(e){
		    var that = this;
		    var file = e.currentTarget.dataset.file;
		    // #ifdef H5
		        window.location.href= file;
		    // #endif
		    
		    // #ifdef MP-WEIXIN
		    uni.downloadFile({
		    	url: file, 
		    	success: (res) => {
						var filePath = res.tempFilePath;
		    		if (res.statusCode === 200) {
		    			uni.openDocument({
								filePath: filePath,
								showMenu: true,
								success: function (res) {
									console.log('打开文档成功');
								}
							});
		    		}
		    	}
		    });
		    // #endif
		},
		formChange:function(e){
			var that = this;
			var index = e.currentTarget.dataset.index;
			that.currentindex = index
		},
  },
};
</script>
<style>

.orderinfo{ width:100%;margin-top:10rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;}
.orderinfo .item .t1.title{font-size: 36rpx;font-weight: 600;line-height: 80rpx;width:100%}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}

.bottom{ width: 100%; height:92rpx;padding: 0 20rpx;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;}

.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;}
.btn3{position:absolute;top:60rpx;right:10rpx;font-size:24rpx;width:120rpx;height:50rpx;line-height:50rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}

jindu{ border: 1rpx solid #ccc; font-size: 24rpx; padding: 5rpx 10rpx; border-radius: 10rpx; color: #555;}
	
	.modal{ position: fixed; background:rgba(0,0,0,0.3); width: 100%; height: 100%; top:0; z-index: 1;}
	.modal .modal_jindu{ background: #fff; position: absolute; top: 20%; align-items: center; margin: auto; width: 90%; left: 30rpx; border-radius: 10rpx; padding: 40rpx;max-height: 600rpx;overflow-y:auto; }
	.modal_jindu .close image { width: 30rpx; height: 30rpx; position: absolute; top:10rpx; right: 20rpx;}
	.modal_jindu .on{color: #23aa5e;}
	.modal_jindu .item{display:flex;width: 96%;  margin: 0 2%;/*border-left: 1px #dadada solid;*/padding:0 0}
	.modal_jindu .item .f1{position:relative}
	/*.logistics img{width: 15px; height: 15px; position: absolute; left: -8px; top:11px;}*/
	.modal_jindu .item .f1 image{width: 30rpx; height: 80rpx; position: absolute; left: -16rpx; top: 0rpx;}
	.modal_jindu .item .f2{display:flex;flex-direction:column;flex:auto;padding:10rpx 0; margin-left: 30rpx;}
	.modal_jindu .item .f2 .t1{font-size: 30rpx; width: 100%;word-break:break-all;}
	.modal_jindu .item .f2 .t1{font-size: 24rpx;}
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
	
	.form-item4{width:100%;background: #fff; padding: 20rpx 0rpx;margin-top:1px}
	.form-item4 .label{ width:150rpx;}
	.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
	.layui-imgbox-img{display: block;width:150rpx;height:150rpx;padding:2px;background-color: #f6f6f6;overflow:hidden ;margin-top: 20rpx;}
	.layui-imgbox-img>image{max-width:100%;}
	.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
	
	.pjitem{ width: 100%;}
	.pjitem radio{ transform:scale(0.7)}
	.pjitem label{ margin-right: 20rpx;}
	.pjitem .btn1{ margin-top: 30rpx;}
	.pjitem .t1{ color:#008000;}
	
	.dp-form-separate{width: 100%;padding: 20rpx 0;font-size: 30rpx;font-weight: bold;color: #454545; display:flex; justify-content: space-between;align-items: center;}
	.dp-form-separate image{ width:40rpx; height:40rpx }
	
</style>