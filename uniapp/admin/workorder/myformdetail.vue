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
				<text class="t1">标题</text>
				<text class="t2">{{detail.title}}</text>
			</view>
			<view v-for="(item, index) in formcontent" :key="index" class="item">
				<text class="t1">{{item.val1}}</text>
				<text class="t2" v-if="item.key!='upload'">{{detail['form'+index]}}</text>
				<view class="t2" style="display: flex; justify-content: flex-end;" v-else>
					<view v-for="(sub, indx) in detail['form'+index]" :key="indx">
						<image :src="sub" style="width:50px; margin-left: 10rpx;" mode="widthFix" @tap="previewImage" :data-url="sub"></image>
					</view>
				</view>
			</view>
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
		</view>
		<view style="width:100%;height:160rpx"></view>
		<view class="bottom notabbarbot">
			<view v-if="detail.status>0" class="btn2" @tap="jindu"  :data-id="detail.id">查看进度</view>
			<view class="btn2" @tap="del" :data-id="detail.id">删除</view>
		</view>
		<uni-popup id="dialogSetst2" ref="dialogSetst2" type="dialog">
			<uni-popup-dialog mode="input" title="驳回原因" :value="detail.reason" placeholder="请输入驳回原因" @confirm="setst2confirm"></uni-popup-dialog>
		</uni-popup>
	</block>
	
	<view class="modal" v-if="ishowjindu">
		<view class="modal_jindu">
				<view class="close" @tap="closejd"><image :src="pre_url+'/static/img/close.png'" /></view>
				<block v-if="jdlist.length>0">
		
					<view class="item " v-for="(item,index) in jdlist" :key="index" style="display: flex;">
						<view class="f1"><image :src="'/static/img/jindu' + (index==0?'2':'1') + '.png'"></image></view>
						<view class="f2">
							<text class="t2"> 时间：{{item.time}}</text>
							<text class="t1">{{item.desc}}({{item.remark}}) </text>
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
					<view class="title">选择流程</view>
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
									<label>备注:</label><textarea placeholder="输入内容" name="content" maxlength="-1"> </textarea>
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
			pre_url:app.globalData.pre_url,
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
		this.getliucheng();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiAdminWorkorder/myformdetail', {id: that.opt.id}, function (res) {
				that.loading = false;
				that.form = res.form;
				that.formcontent = res.formcontent;
				that.detail = res.detail;
				that.loaded();
			});
		},
		getliucheng:function(e){
			var that=this
			app.post('ApiAdminWorkorder/getliucheng', {}, function (res) {
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
		}
  }
};
</script>
<style>

.orderinfo{ width:100%;margin-top:10rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}

.bottom{ width: 100%;height:92rpx;padding: 0 20rpx;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;}

.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.btn3{position:absolute;top:60rpx;right:10rpx;font-size:24rpx;width:120rpx;height:50rpx;line-height:50rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}

.modal{ position: fixed; background:rgba(0,0,0,0.3); width: 100%; height: 100%; top:0; z-index: 100;}
.modal .modal_jindu{ background: #fff; position: absolute; top: 20%; align-items: center; margin: auto; width: 90%; left: 30rpx; border-radius: 10rpx; padding: 40rpx;}
.modal_jindu .close image { width: 20rpx; height: 20rpx; position: absolute; top:10rpx; right: 20rpx;}
.modal_jindu .title{ font-size: 32rpx; font-weight: bold;}
.uni-list{ margin-top: 30rpx;}
.uni-list-cell{ display: flex; height: 80rpx;}
.beizhu label{ width: 100rpx;}
.modal_jindu .btn{  background: #1658c6; border-radius: 3px;line-height: 24px; border: none; padding: 0 10px;color: #fff;font-size: 20px; text-align: center; width: 300px;  display: flex; height: 40px; justify-content: center;align-items: center;}


.modal_jindu .item .f1{ width:60rpx;position:relative}
/*.logistics img{width: 15px; height: 15px; position: absolute; left: -8px; top:11px;}*/
.modal_jindu .item .f1 image{width: 30rpx; height: 100%; position: absolute; left: -16rpx; top: 0rpx;}
.modal_jindu .item .f2{display:flex;flex-direction:column;flex:auto;padding:10rpx 0}
.modal_jindu .item .f2 .t1{font-size: 30rpx;}
.modal_jindu .item .f2 .t1{font-size: 26rpx;}

</style>