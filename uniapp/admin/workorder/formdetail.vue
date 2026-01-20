<template>
<view class="container">
	<block v-if="isload">
		<view class="orderinfo">
			<view class="item">
				<text class="t1">提交人</text>
				<text class="flex1"></text>
				<image :src="detail.headimg" style="width:80rpx;height:80rpx;margin-right:8rpx"/>
				<text  style="height:80rpx;line-height:80rpx">{{detail.nickname}}</text>
			</view>
			<view class="item">
				<text class="t1">{{t('会员')}}ID</text>
				<text class="t2">{{detail.mid}}</text>
			</view>
			
			<view class="item">
				<text class="t1">工单分类</text>
				<view class="t2" @tap="goto" :data-url="'updatecate?id='+detail.id">{{detail.cname}}
						<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
				</view>
			</view>
			
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
				<text class="t2" v-if="detail.status==1" style="color:green">处理中</text>
				<text class="t2" v-if="detail.status==2" style="color:green">已完成</text>
				<text class="t2" v-if="detail.status==-1" style="color:red">已驳回</text>
				
			</view>
			<view class="item" v-if="detail.status==-1">
				<text class="t1">驳回原因</text>
				<text class="t2" style="color:red">{{detail.reason}}</text>
			</view>
			<block v-if="form.payset==1">
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
			<view v-if="detail.status>0" class="btn2" @tap="goto"  :data-url="'jindu?id='+detail.id+'&cid='+detail.cid">查看进度</view>
			<view v-if="detail.status==0" class="btn2" @tap="setst2" :data-st="-1" :data-id="detail.id">驳回</view>
			<view class="btn2" @tap="del" :data-id="detail.id">删除</view>
		</view>
		
		
		
		
		<uni-popup id="dialogSetst2" ref="dialogSetst2" type="dialog">
			<uni-popup-dialog mode="input" title="驳回原因" :value="detail.reason" placeholder="请输入驳回原因"  @confirm="setst2confirm"></uni-popup-dialog>
		</uni-popup>
	</block>
	
	<view class="modaljd" v-if="ishowjindu">
		<view class="modal_jindu2">
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
				<block v-else>
						<view style="font-size:14px;color:#f05555;padding:10px;">等待处理</view>
				</block>
		</view>
	</view>
	
	
	<view class="modal" v-if="showstatus">
		<view class="modal_jindu">
			<form   @submit="formsubmit">
					<view class="close" @tap="close"><image :src="pre_url+'/static/img/close.png'" /></view>
					<view class="title">选择处理流程</view>
					<view class="uni-list">
							<radio-group name="liucheng">
									<label class="uni-list-cell uni-list-cell-pd" v-for="(item, index) in lclist" :key="index">
											<view>
													<radio :value="''+item.id" style="transform:scale(0.7)"/>
											</view>
											<view>{{item.name}}</view>
									</label>
							</radio-group>
							<view class="beizhu flex">
									<label>备注1:</label><textarea placeholder="输入内容"  style="height: 100rpx;" name="content" maxlength="-1"></textarea>
							</view>
					</view>
					<button class="btn" form-type="submit">提交</button>
			</form>
		</view>
	</view>
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
			detail:{},
			formcontent:[],
			showstatus:0,
			ishowjindu:false,
			jdlist:[],
			lclist:[],
			content_pic: [],
			tempFilePaths: "",
			pre_url:app.globalData.pre_url,
			content1:'11',
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
	onShow:function(){
		
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiAdminWorkorder/formdetail', {id: that.opt.id}, function (res) {
				that.loading = false;
				that.form = res.form;
				that.formcontent = res.formcontent;
				that.detail = res.detail;
				that.content_pic=[]
				that.content1 = '';
				console.log(that.content1 );
				that.loaded();
				that.getliucheng();
			});
		},
		getliucheng:function(e){
			var that=this
			app.post('ApiAdminWorkorder/getliucheng', {cid: that.detail.formid}, function (res) {
					var lclist = res.datalist;
					that.lclist = lclist;
					
			});
		},
		setst:function(e){
			var that = this;
			var id = e.currentTarget.dataset.id;
			that.id=id
			that.showstatus=true;
		},
		close:function(e){
			var that=this
			that.showstatus=false
		},
		
		formsubmit: function (e) {
		  var that = this;		
		  var formdata = e.detail.value;
		  var content = formdata.content;
		  var liucheng = formdata.liucheng;
		  if (liucheng == '') {
		    app.error('请选择流程');
		    return false;
		  }
			var content_pic = that.content_pic;
			app.showLoading();
		  app.post('ApiAdminWorkorder/addjindu', {logid:that.opt.id,lcid:liucheng,content: content,content_pic: content_pic.join(',')}, function (res) {
				var res  = res
		  	app.showLoading(false);
		    if (res.status == 1) {
		      app.alert('处理成功');
					setTimeout(function () {
					  //that.getdata();
							app.goto('formdetail?id='+that.opt.id);
					}, 1000);
		    } else {
		      app.alert(res.msg);
		    }

		  });
		},
		
		setst2:function(e){
			this.$refs.dialogSetst2.open();
		},
		setst2confirm:function(done,value){
			this.$refs.dialogSetst2.close();
      var that = this;
			var content_pic = that.content_pic;
      app.post('ApiAdminWorkorder/formsetst', {id: that.opt.id,st:-1,reason:value,content_pic: content_pic.join(',')}, function (data) {
        app.success(data.msg);
        setTimeout(function () {
          that.getdata();
        }, 1000);
      });
		},
		del:function(e){
			var that = this;
			var id = e.currentTarget.dataset.id;
			app.confirm('确定要删除吗?',function(){
				app.post('ApiAdminWorkorder/formdel', {id:id}, function (res) {
					app.success(res.msg);
					setTimeout(function () {
						app.goto('/admin/index/index');
					},1000);
				})
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
		closejd:function(e){
			var that=this
			that.ishowjindu=false
		},
		formChange:function(e){
			var that = this;
			var index = e.currentTarget.dataset.index;
			that.currentindex = index
		},
  }
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

.bottom{ width: 100%;height:92rpx;padding: 0 20rpx;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;}

.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.btn3{position:absolute;top:60rpx;right:10rpx;font-size:24rpx;width:120rpx;height:50rpx;line-height:50rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}

.modaljd{ position: fixed; background:rgba(0,0,0,0.3); width: 100%; height: 100%; top:0; z-index: 100;}
.modaljd .modal_jindu2{ background: #fff; position: absolute; top: 20%; align-items: center; margin: auto; width: 90%; left: 30rpx; border-radius: 10rpx; padding: 40rpx; max-height: 600rpx; overflow-y:auto; display: flex; flex-wrap: wrap;}
.modal_jindu2 .close image { width: 30rpx; height: 30rpx; position: fixed; top:21%;right: 60rpx;}
.modal_jindu2 .title{ font-size: 32rpx; font-weight: bold;}
.modal_jindu2 .item .f1{position:relative; }
/*.logistics img{width: 15px; height: 15px; position: absolute; left: -8px; top:11px;}*/
.modal_jindu2 .item .f1 image{width: 30rpx; height:100rpx;padding:10rpx 0;position: absolute; left: -16rpx; top: 0rpx;}
.modal_jindu2 .item .f2{display:flex;flex-direction:column;flex:auto;padding:10rpx 0; margin-left:30rpx; }
.modal_jindu2 .item .f2 .t1{font-size: 30rpx;word-break: break-word; width: 90%;}
.modal_jindu2 .item .f2 .t1{font-size: 26rpx;}
.modal_jindu2 .item .f2 .t3{font-size: 24rpx; color:#008000; margin-top: 10rpx;}
.modal_jindu2 .item .f2 .t4{font-size: 24rpx;  color:#008000;}

/*流程处理*/
.modal .modal_jindu{ background: #fff;  align-items: center; margin: auto; width: 100%; margin-top: 10rpx; padding: 40rpx;}
.modal_jindu .title{ font-size: 32rpx; font-weight: bold;}
.modal_jindu .btn{  background: #1658c6; border-radius: 3px;line-height: 24px; border: none; padding: 0 10px;color: #fff;font-size: 20px; text-align: center; width: 300px;  display: flex; height: 40px; justify-content: center;align-items: center;}

.uni-list{ margin-top: 30rpx;}
.uni-list-cell{ display: flex; height: 80rpx;}
.beizhu label{ width: 100rpx;}



.form-item4{width:100%;background: #fff; padding: 20rpx 0rpx;margin-top:1px}
.form-item4 .label{ width:150rpx;}
.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:150rpx;height:150rpx;padding:2px;background-color: #f6f6f6;overflow:hidden ;margin-top: 20rpx;}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}

.dp-form-separate{width: 100%;padding: 20rpx 0;font-size: 30rpx;font-weight: bold;color: #454545; display:flex; justify-content: space-between;align-items: center;}
.dp-form-separate image{ width:40rpx; height:40rpx }
	
</style>