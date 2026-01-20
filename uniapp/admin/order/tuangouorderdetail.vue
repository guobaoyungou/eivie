<template>
<view class="container">
	<block v-if="isload">
		<view class="ordertop" :style="'background:url(' + pre_url + '/static/img/ordertop.png);background-size:100%'">
			<view class="f1" v-if="detail.status==0">
				<view class="t1">等待买家付款</view>
			</view>
			<view class="f1" v-if="detail.status==1">
				<view class="t1">已成功付款</view>
				<view class="t2" v-if="detail.freight_type!=1">请尽快发货</view>
				<view class="t2" v-if="detail.freight_type==1">待提货</view>
			</view>
			<view class="f1" v-if="detail.status==2">
				<view class="t1">订单已发货</view>
				<view class="t2" v-if="detail.freight_type!=3">发货信息：{{detail.express_com}} {{detail.express_no}}</view>
			</view>
			<view class="f1" v-if="detail.status==3">
				<view class="t1">订单已完成</view>
			</view>
			<view class="f1" v-if="detail.status==4">
				<view class="t1">订单已取消</view>
			</view>
		</view>
		<view class="address">
			<view class="img">
				<image :src="pre_url+'/static/img/address3.png'"></image>
			</view>
			<view class="info">
				<text class="t1" user-select="true">{{detail.linkman}} {{detail.tel}}</text>
				<text class="t2" v-if="detail.freight_type!=1 && detail.freight_type!=3" user-select="true">地址：{{detail.area}}{{detail.address}}</text>
				<text class="t2" v-if="detail.freight_type==1" @tap="openLocation" :data-address="storeinfo.address" :data-latitude="storeinfo.latitude" :data-longitude="storeinfo.longitude" user-select="true">取货地点：{{storeinfo.name}} - {{storeinfo.address}}</text>
			</view>
		</view>
		<view class="product">
			<view class="content">
				<view @tap="goto" :data-url="'/activity/tuangou/product?id=' + detail.proid">
					<image :src="detail.propic"></image>
				</view>
				<view class="detail">
					<text class="t1">{{detail.proname}}</text>
					<view class="t3"><text class="x1 flex1">￥{{detail.sell_price}}</text><text class="x2">×{{detail.num}}</text></view>
				</view>
			</view>
		</view>
		
		<view class="orderinfo" v-if="(detail.status==3 || detail.status==2) && (detail.freight_type==3 || detail.freight_type==4)">
			<view class="item flex-col">
				<text class="t1" style="color:#111">发货信息</text>
				<text class="t2" style="text-align:left;margin-top:10rpx;padding:0 10rpx" user-select="true">{{detail.freight_content}}</text>
			</view>
		</view>
		<view class="orderinfo">
			<view class="item">
				<text class="t1">下单人</text>
				<text class="flex1"></text>
				<image :src="detail.headimg" style="width:80rpx;height:80rpx;margin-right:8rpx"/>
				<text  style="height:80rpx;line-height:80rpx">{{detail.nickname}}</text>
			</view>
			<view class="item">
				<text class="t1">{{t('会员')}}ID</text>
				<text class="t2">{{detail.mid}}</text>
			</view>
		</view>
		<view class="orderinfo" v-if="detail.remark">
			<view class="item">
				<text class="t1">备注</text>
				<text class="t2" user-select="true">{{detail.remark}}</text>
			</view>
		</view>
		<view class="orderinfo">
			<view class="item">
				<text class="t1">订单编号</text>
				<text class="t2" user-select="true">{{detail.ordernum}}</text>
			</view>
			<view class="item">
				<text class="t1">下单时间</text>
				<text class="t2">{{detail.createtime}}</text>
			</view>
			<view class="item" v-if="detail.status>0 && detail.paytime">
				<text class="t1">支付时间</text>
				<text class="t2">{{detail.paytime}}</text>
			</view>
			<view class="item" v-if="detail.status>0 && detail.paytime">
				<text class="t1">支付方式</text>
				<text class="t2">{{detail.paytype}}</text>
			</view>
			<view class="item" v-if="detail.status>1 && detail.send_time">
				<text class="t1">发货时间</text>
				<text class="t2">{{detail.send_time}}</text>
			</view>
			<view class="item" v-if="detail.status==3 && detail.collect_time">
				<text class="t1">收货时间</text>
				<text class="t2">{{detail.collect_time}}</text>
			</view>
		</view>
		<view class="orderinfo">
			<view class="item">
				<text class="t1">商品金额</text>
				<text class="t2 red">¥{{detail.product_price}}</text>
			</view>
			<view class="item" v-if="detail.disprice > 0">
				<text class="t1">{{t('会员')}}折扣</text>
				<text class="t2 red">-¥{{detail.leveldk_money}}</text>
			</view>
			<view class="item" v-if="detail.jianmoney > 0">
				<text class="t1">满减活动</text>
				<text class="t2 red">-¥{{detail.manjian_money}}</text>
			</view>
			<view class="item">
				<text class="t1">配送方式</text>
				<text class="t2">{{detail.freight_text}}</text>
			</view>
			<view class="item" v-if="detail.freight_type==1 && detail.freightprice > 0">
				<text class="t1">服务费</text>
				<text class="t2 red">+¥{{detail.freight_price}}</text>
			</view>
			<view class="item" v-if="detail.freight_time">
				<text class="t1">{{detail.freight_type!=1?'配送':'提货'}}时间</text>
				<text class="t2">{{detail.freight_time}}</text>
			</view>
			<view class="item" v-if="detail.couponmoney > 0">
				<text class="t1">{{t('优惠券')}}抵扣</text>
				<text class="t2 red">-¥{{detail.coupon_money}}</text>
			</view>
			
			<view class="item" v-if="detail.scoredk > 0">
				<text class="t1">{{t('积分')}}抵扣</text>
				<text class="t2 red">-¥{{detail.scoredk_money}}</text>
			</view>
			<view class="item">
				<text class="t1">预付款</text>
				<text class="t2 red">¥{{detail.totalprice}}</text>
			</view>
			<view class="item" v-if="detail.tuimoney > 0">
				<text class="t1">已退款</text>
				<text class="t2 red">¥{{detail.tuimoney}}</text>
			</view>
			<view class="item" v-if="detail.tuimoney > 0">
				<text class="t1">实付金额</text>
				<text class="t2 red">¥{{detail.real_price}}</text>
			</view>

			<view class="item">
				<text class="t1">订单状态</text>
				<text class="t2" v-if="detail.status==0">未付款</text>
				<block v-if="detail.status==1">
					<text v-if="detail.freight_type!=1" class="t2">待发货</text>
					<text v-if="detail.freight_type==1" class="t2">待提货</text>
				</block>
				<text class="t2" v-if="detail.status==2">已发货</text>
				<text class="t2" v-if="detail.status==3">已收货</text>
				<text class="t2" v-if="detail.status==4">已关闭</text>
			</view>
			<view class="item" v-if="detail.refund_status>0">
				<text class="t1">退款状态</text>
				<text class="t2 red" v-if="detail.refund_status==1">审核中,¥{{detail.refund_money}}</text>
				<text class="t2 red" v-if="detail.refund_status==2">已退款,¥{{detail.refund_money}}</text>
				<text class="t2 red" v-if="detail.refund_status==3">已驳回,¥{{detail.refund_money}}</text>
			</view>
			<view class="item" v-if="detail.refund_status>0">
				<text class="t1">退款原因</text>
				<text class="t2 red">{{detail.refund_reason}}</text>
			</view>
			<view class="item" v-if="detail.refund_checkremark">
				<text class="t1">审核备注</text>
				<text class="t2 red">{{detail.refund_checkremark}}</text>
			</view>
			
			<view class="item" v-if="detail.isfuwu && detail.fuwuendtime > 0">
				<text class="t1">到期时间</text>
				<text class="t2 red">{{_.dateFormat(detail.fuwuendtime,'Y-m-d H:i')}}</text>
			</view>
			<view class="item flex-col" v-if="(detail.status==1 || detail.status==2) && detail.freight_type==1">
				<text class="t1">核销码</text>
				<view class="flex-x-center">
					<image :src="detail.hexiao_qr" style="width:400rpx;height:400rpx" @tap="previewImage" :data-url="detail.hexiao_qr"></image>
				</view>
			</view>
		</view>
		
		<view class="orderinfo" v-if="(detail.formdata).length > 0">
			<view class="item" v-for="item in detail.formdata" :key="index">
				<text class="t1">{{item[0]}}</text>
				<view class="t2" v-if="item[2]=='upload'"><image :src="item[1]" style="width:400rpx;height:auto" mode="widthFix" @tap="previewImage" :data-url="item[1]"/></view>
				<text class="t2" v-else user-select="true">{{item[1]}}</text>
			</view>
		</view>

		<view style="width:100%;height:calc(160rpx + env(safe-area-inset-bottom));"></view>

		<view class="bottom notabbarbot">
			<view v-if="detail.refund_status==1" class="btn2" @tap="refundnopass" :data-id="detail.id">退款驳回</view>
			<view v-if="detail.refund_status==1" class="btn2" @tap="refundpass" :data-id="detail.id">退款通过</view>
			<view v-if="detail.status==0" class="btn2" @tap="closeOrder" :data-id="detail.id">关闭订单</view>
			<view v-if="detail.status==0 && detail.bid==0" class="btn2" @tap="ispay" :data-id="detail.id">改为已支付</view>
			<view v-if="detail.status==1" class="btn2" @tap="fahuo" :data-id="detail.id">发货</view>
			<view v-if="(detail.status==1 || detail.status==2) && detail.freight_type==1" class="btn2" @tap="hexiao" :data-id="detail.id">核销</view>
			<view v-if="detail.status==1 && detail.canpeisong" class="btn2">
                <view v-if="detail.myt_status" @tap="peisongMyt" :data-id="detail.id">麦芽田配送</view>
                <view v-else @tap="peisong" :data-id="detail.id">配送</view>
            </view>
			<view v-if="(detail.status==2 || detail.status==3) && detail.express_com" class="btn2" @tap="goto" :data-url="'/pagesExt/order/logistics?express_com='+detail.express_com+'&express_no='+detail.express_no">查物流</view>
			<view v-if="detail.status==2 && detail.freight_type==10" class="btn2" @tap="fahuo" :data-id="detail.id">修改物流</view>
			<view v-if="detail.status==4" class="btn2" @tap="delOrder" :data-id="detail.id">删除</view>
			<view class="btn2" @tap="setremark" :data-id="detail.id">设置备注</view>
		</view>
		<uni-popup id="dialogSetremark" ref="dialogSetremark" type="dialog">
			<uni-popup-dialog mode="input" title="设置备注" :value="detail.remark" placeholder="请输入备注" @confirm="setremarkconfirm"></uni-popup-dialog>
		</uni-popup>

		<uni-popup id="dialogExpress" ref="dialogExpress" type="dialog">
			<view class="uni-popup-dialog">
				<view class="uni-dialog-title">
					<text class="uni-dialog-title-text">发货</text>
				</view>
				<view class="uni-dialog-content">
					<view>
						<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx">
							<text style="font-size:28rpx;color:#000">快递公司：</text>
							<picker @change="expresschange" :value="express_index" :range="expressdata" style="font-size:28rpx;border: 1px #eee solid;padding:10rpx;height:70rpx;border-radius:4px;flex:1">
								<view class="picker">{{expressdata[express_index]}}</view>
							</picker>
						</view> 
						<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
							<view style="font-size:28rpx;color:#555">快递单号：</view>
							<input type="text" placeholder="请输入快递单号" @input="setexpressno" style="border: 1px #eee solid;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
						</view>
					</view>
				</view>
				<view class="uni-dialog-button-group">
					<view class="uni-dialog-button" @click="dialogExpressClose">
						<text class="uni-dialog-button-text">取消</text>
					</view>
					<view class="uni-dialog-button uni-border-left" @click="confirmfahuo">
						<text class="uni-dialog-button-text uni-button-color">确定</text>
					</view>
				</view>
			</view>
		</uni-popup>
		<uni-popup id="dialogPeisong" ref="dialogPeisong" type="dialog">
			<view class="uni-popup-dialog">
				<view class="uni-dialog-title">
					<text class="uni-dialog-title-text">请选择配送员</text>
				</view>
				<view class="uni-dialog-content">
					<view>
						<picker @change="peisongChange" :value="index2" :range="peisonguser2" style="font-size:28rpx;border: 1px #eee solid;padding:10rpx;height:70rpx;border-radius:4px;flex:1">
							<view class="picker">{{peisonguser2[index2]}}</view>
						</picker>
					</view>
				</view>
				<view class="uni-dialog-button-group">
					<view class="uni-dialog-button" @click="dialogPeisongClose">
						<text class="uni-dialog-button-text">取消</text>
					</view>
					<view class="uni-dialog-button uni-border-left" @click="confirmPeisong">
						<text class="uni-dialog-button-text uni-button-color">确定</text>
					</view>
				</view>
			</view>
		</uni-popup>

		
		<uni-popup id="dialogExpress10" ref="dialogExpress10" type="dialog">
			<view class="uni-popup-dialog">
				<view class="uni-dialog-title">
					<text class="uni-dialog-title-text">发货信息</text>
				</view>
				<view class="uni-dialog-content">
					<view>
						<view class="form-item flex" style="border-bottom:0;">
							<view class="f1" style="margin-right:20rpx">物流单照片</view>
							<view class="f2">
								<view class="layui-imgbox" v-if="express_pic">
									<view class="layui-imgbox-close" @tap="removeimg" :data-index="0" data-field="express_pic"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
									<view class="layui-imgbox-img"><image :src="express_pic" @tap="previewImage" :data-url="express_pic" mode="widthFix"></image></view>
								</view>
								<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="express_pic" data-pernum="1" v-else></view>
							</view>
							<input type="text" hidden="true" name="express_pic" :value="express_pic" maxlength="-1"/>
						</view>
						<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
							<view style="font-size:28rpx;color:#555">发货人：</view>
							<input type="text" placeholder="请输入发货人信息" @input="setexpress_fhname" style="border: 1px #eee solid;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
						</view>
						<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
							<view style="font-size:28rpx;color:#555">发货地址：</view>
							<input type="text" placeholder="请输入发货地址" @input="setexpress_fhaddress" style="border: 1px #eee solid;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
						</view>
						<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
							<view style="font-size:28rpx;color:#555">收货人：</view>
							<input type="text" placeholder="请输入发货人信息" @input="setexpress_shname" style="border: 1px #eee solid;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
						</view>
						<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
							<view style="font-size:28rpx;color:#555">收货地址：</view>
							<input type="text" placeholder="请输入发货地址" @input="setexpress_shaddress" style="border: 1px #eee solid;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
						</view>
						<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
							<view style="font-size:28rpx;color:#555">备注：</view>
							<input type="text" placeholder="请输入备注" @input="setexpress_remark" style="border: 1px #eee solid;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
						</view>
					</view>
				</view>
				<view class="uni-dialog-button-group">
					<view class="uni-dialog-button" @click="dialogExpress10Close">
						<text class="uni-dialog-button-text">取消</text>
					</view>
					<view class="uni-dialog-button uni-border-left" @click="confirmfahuo10">
						<text class="uni-dialog-button-text uni-button-color">确定</text>
					</view>
				</view>
			</view>
		</uni-popup>
        
        <uni-popup id="dialogExpress11" ref="dialogExpress11" type="dialog">
        	<view class="uni-popup-dialog">
        		<view class="uni-dialog-title">
        			<text class="uni-dialog-title-text">配送设置</text>
        		</view>
        		<view class="uni-dialog-content">
        			<view>
                        <view v-if="detail.myt_shop" class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
                        	<view style="font-size:28rpx;color:#555">门店：</view>
                            <picker @change="mytshopChange" :value="mytindex" :range="detail.myt_shoplist"  range-key='name' style="font-size:28rpx;border: 1px #eee solid;padding:10rpx;height:70rpx;border-radius:4px;flex:1;line-height: 52rpx;">
                            	<view class="picker">{{detail.myt_shoplist[mytindex]['name']}}</view>
                            </picker>
                        </view>
        				<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
        					<view style="font-size:28rpx;color:#555">重量：</view>
        					<input type="text" placeholder="请输入重量(选填)" @input="mytWeight" style="border: 1px #eee solid;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
        				</view>
        				<view class="flex-y-center flex-x-center" style="margin:20rpx 20rpx;">
        					<view style="font-size:28rpx;color:#555">备注：</view>
        					<input type="text" placeholder="请输入备注(选填)" @input="mytRemark" style="border: 1px #eee solid;padding: 10rpx;height:70rpx;border-radius:4px;flex:1;font-size:28rpx;"/>
        				</view>
        			</view>
        		</view>
        		<view class="uni-dialog-button-group">
        			<view class="uni-dialog-button" @click="dialogExpress11Close">
        				<text class="uni-dialog-button-text">取消</text>
        			</view>
        			<view class="uni-dialog-button uni-border-left" @click="confirmfahuo11">
        				<text class="uni-dialog-button-text uni-button-color">确定</text>
        			</view>
        		</view>
        	</view>
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
			expressdata:[],
			express_index:0,
			express_no:'',
      prodata: '',
      detail: "",
			team:{},
      prolist: "",
      shopset: "",
      storeinfo: "",
      lefttime: "",
			peisonguser:[],
			peisonguser2:[],
			index2:0,
			express_pic:'',
			express_fhname:'',
			express_fhaddress:'',
			express_shname:'',
			express_shaddress:'',
			express_remark:'',
			mdid:0,
            
            myt_weight:'',
            myt_remark:'',
            mytindex:0,
            myt_shop_id:0
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
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
			app.get('ApiAdminOrder/tuangouorderdetail', {id: that.opt.id}, function (res) {
				that.loading = false;
				that.expressdata = res.expressdata;
				that.detail = res.detail;
				that.team = res.team;
				that.storeinfo = res.storeinfo;
				that.mdid = res.mdid;
				that.loaded();
			});
		},
		setremark:function(){
			this.$refs.dialogSetremark.open();
		},
		setremarkconfirm: function (done, remark) {
			this.$refs.dialogSetremark.close();
			var that = this
			app.post('ApiAdminOrder/setremark', { type:'tuangou',orderid: that.detail.id,content:remark }, function (res) {
				app.success(res.msg);
				setTimeout(function () {
					that.getdata();
				}, 1000)
			})
    },
		fahuo:function(){
			if(this.detail.freight_type==10){
				this.$refs.dialogExpress10.open();
			}else{
				this.$refs.dialogExpress.open();
			}
		},
		zongbuFahuo:function(e){
			var that = this
			app.confirm('确定要选择总部发货吗?', function () {
				app.showLoading('提交中');
				app.post('ApiAdminOrder/zongbuFahuo', { type:'tuangou',orderid: that.detail.id }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
				})
			});
		},
		dialogExpressClose:function(){
			this.$refs.dialogExpress.close();
		},
		dialogExpress10Close:function(){
			this.$refs.dialogExpress10.close();
		},
		expresschange:function(e){
			this.express_index = e.detail.value;
		},
		setexpressno:function(e){
			this.express_no = e.detail.value;
		},
		confirmfahuo:function(){
			this.$refs.dialogExpress.close();
			var that = this
			var express_com = this.expressdata[this.express_index]
			app.post('ApiAdminOrder/sendExpress', { type:'tuangou',orderid: that.detail.id,express_no:that.express_no,express_com:express_com}, function (res) {
				app.success(res.msg);
				setTimeout(function () {
					that.getdata();
				}, 1000)
			})
		},
		setexpress_pic:function(e){
			this.express_pic = e.detail.value;
		},
		setexpress_fhname:function(e){
			this.express_fhname = e.detail.value;
		},
		setexpress_fhaddress:function(e){
			this.express_fhaddress = e.detail.value;
		},
		setexpress_shname:function(e){
			this.express_shname = e.detail.value;
		},
		setexpress_shaddress:function(e){
			this.express_shaddress = e.detail.value;
		},
		setexpress_remark:function(e){
			this.express_remark = e.detail.value;
		},
		confirmfahuo10:function(){
			this.$refs.dialogExpress10.close();
			var that = this
			var express_com = this.expressdata[this.express_index]
			app.post('ApiAdminOrder/sendExpress', { type:'tuangou',orderid: that.detail.id,pic:that.express_pic,fhname:that.express_fhname,fhaddress:that.express_fhaddress,shname:that.express_shname,shaddress:that.express_shaddress,remark:that.express_remark}, function (res) {
				app.success(res.msg);
				setTimeout(function () {
					that.getdata();
				}, 1000)
			})
		},
		ispay:function(e){
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.confirm('确定要改为已支付吗?', function () {
				app.showLoading('提交中');
				app.post('ApiAdminOrder/ispay', { type:'tuangou',orderid: orderid }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
				})
			});
		},
		hexiao:function(e){
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.confirm('确定要核销并改为已完成状态吗?', function () {
				app.showLoading('提交中');
				app.post('ApiAdminOrder/hexiao', { type:'tuangou',orderid: orderid }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
				})
			});
		},
		delOrder: function (e) {
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.showLoading('删除中');
			app.confirm('确定要删除该订单吗?', function () {
				app.post('ApiAdminOrder/delOrder', { type:'tuangou',orderid: orderid }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						app.goto('shoporder');
					}, 1000)
				});
			})
		},
		closeOrder: function (e) {
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.confirm('确定要关闭该订单吗?', function () {
				app.showLoading('提交中');
				app.post('ApiAdminOrder/closeOrder', { type:'tuangou',orderid: orderid }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function (){
						that.getdata();
					}, 1000)
				});
			})
		},
		refundnopass: function (e) {
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.confirm('确定要驳回退款申请吗?', function () {
				app.showLoading('提交中');
				app.post('ApiAdminOrder/refundnopass', { type:'tuangou',orderid: orderid }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
				})
			});
		},
		refundpass: function (e) {
			var that = this;
			var orderid = e.currentTarget.dataset.id
			app.confirm('确定要审核通过并退款吗?', function () {
				app.showLoading('提交中');
				app.post('ApiAdminOrder/refundpass', { type:'tuangou',orderid: orderid }, function (data) {
					app.showLoading(false);
					app.success(data.msg);
					setTimeout(function () {
						that.getdata();
					}, 1000)
				})
			});
		},
		peisong:function(){
			var that = this;
			that.loading = true;
			app.post('ApiAdminOrder/getpeisonguser',{type:'tuangou_order',orderid:that.detail.id},function(res){
				that.loading = false;
				var peisonguser = res.peisonguser
				var paidantype = res.paidantype
				var psfee = res.psfee
				var ticheng = res.ticheng

				var peisonguser2 = [];
				for(var i in peisonguser){
					peisonguser2.push(peisonguser[i].title);
				}
				that.peisonguser = res.peisonguser;
				that.peisonguser2 = peisonguser2;
				if(paidantype==1){
					that.$refs.dialogPeisong.open();
				}else{
					if(that.detail.bid == 0){
						var tips='选择配送员配送，订单将发布到抢单大厅由配送员抢单，配送员提成￥'+ticheng+'，确定要配送员配送吗？';
					}else{
						var tips='选择配送员配送，订单将发布到抢单大厅由配送员抢单，需扣除配送费￥'+psfee+'，确定要配送员配送吗？';
					}
					if(paidantype == 2){
						var psid = '-1';
					}else{
						var psid = '0';
					}
					app.confirm(tips,function(){
						app.post('ApiAdminOrder/peisong', { type:'tuangou_order',orderid: that.detail.id,psid:psid}, function (res) {
							app.success(res.msg);
							setTimeout(function () {
								that.getdata();
							}, 1000)
						})
					})
				}
			})
		},
		dialogPeisongClose:function(){
			this.$refs.dialogPeisong.close();
		},
		peisongChange:function(e){
			this.index2 = e.detail.value;
		},
		confirmPeisong:function(){
			var that = this
			var psid = this.peisonguser[this.index2].id
			app.post('ApiAdminOrder/peisong', { type:'tuangou_order',orderid: that.detail.id,psid:psid}, function (res) {
				app.success(res.msg);
				that.$refs.dialogPeisong.close();
				setTimeout(function () {
					that.getdata();
				}, 1000)
			})
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
				if(field == 'express_pic') that.express_pic = pics[0];
			},pernum);
		},
		removeimg:function(e){
			var that = this;
			var index= e.currentTarget.dataset.index
			var field= e.currentTarget.dataset.field
			if(field == 'express_pic'){
				that.express_pic = '';
			}
		},
        peisongMyt:function(e){
            var that = this;
            var detail = that.detail;
            if(detail.myt_set){
                this.$refs.dialogExpress11.open();
            }else{
                that.goMyt();
            }
        },
        goMyt:function(){
            var that = this;
            var detail = that.detail;
            var tips='选择麦芽田配送，订单将派单到第三方配送平台，并扣除相应费用，确定要派单吗？';
            app.confirm(tips,function(){
                that.$refs.dialogExpress11.close();
                that.loading = true;
                var data = {
                    type:'tuangou_order',
                    orderid: detail.id,
                    myt_weight:that.myt_weight,
                    myt_remark:that.myt_remark,
                    myt_shop_id:that.myt_shop_id
                }
                app.post('ApiAdminOrder/peisong', data, function (res) {
                    that.loading = false;
                    if(res.status == 1){
                        app.success(res.msg);
                        setTimeout(function () {
                            that.getdata();
                        }, 1000)
                    }else{
                        app.alert(res.msg)
                    }
                    
                })
            })
        },
        confirmfahuo11:function(){
        	var that = this
        	that.goMyt();
        },
        dialogExpress11Close:function(){
        	this.$refs.dialogExpress11.close();
        },
        mytWeight:function(e){
        	this.myt_weight = e.detail.value;
        },
        mytRemark:function(e){
        	this.myt_remark = e.detail.value;
        },
        mytshopChange:function(e){
            var that = this;
            var detail   = that.detail;
            var mytindex = e.detail.value;
            that.mytindex = mytindex;
            //that.myt_name  = detail.myt_shoplist[mytindex]['name'];
            that.myt_shop_id    = detail.myt_shoplist[mytindex]['id'];
        },
  }
};
</script>
<style>
.ordertop{width:100%;height:220rpx;padding:50rpx 0 0 70rpx}
.ordertop .f1{color:#fff}
.ordertop .f1 .t1{font-size:32rpx;height:60rpx;line-height:60rpx}
.ordertop .f1 .t2{font-size:24rpx}

.address{ display:flex;width: 100%; padding: 20rpx 3%; background: #FFF;}
.address .img{width:40rpx}
.address image{width:40rpx; height:40rpx;}
.address .info{flex:1;display:flex;flex-direction:column;}
.address .info .t1{font-size:28rpx;font-weight:bold;color:#333}
.address .info .t2{font-size:24rpx;color:#999}

.product{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.product .content{display:flex;position:relative;width: 100%; padding:16rpx 0px;border-bottom: 1px #e5e5e5 dashed;}
.product .content:last-child{ border-bottom: 0; }
.product .content image{ width: 140rpx; height: 140rpx;}
.product .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.product .content .detail .t1{font-size:26rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.product .content .detail .t2{height: 46rpx;line-height: 46rpx;color: #999;overflow: hidden;font-size: 26rpx;}
.product .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246;}
.product .content .detail .x1{ flex:1}
.product .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}
.product .content .comment{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc702 solid; border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.product .content .comment2{position:absolute;top:64rpx;right:10rpx;border: 1px #ffc7c2 solid; border-radius:10rpx;background:#fff; color: #ffc7c2;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.orderinfo{width:94%;margin:0 3%;border-radius:8rpx;margin-top:16rpx;padding: 14rpx 3%;background: #FFF;}
.orderinfo .item{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;}
.orderinfo .item:last-child{ border-bottom: 0;}
.orderinfo .item .t1{width:200rpx;}
.orderinfo .item .t2{flex:1;text-align:right}
.orderinfo .item .red{color:red}

.bottom{ width: 100%;height:calc(92rpx + env(safe-area-inset-bottom));padding: 0 20rpx;background: #fff; position: fixed; bottom: 0px;left: 0px;display:flex;justify-content:flex-end;align-items:center;}

.btn1{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center}
.btn2{margin-left:20rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}
.btn3{position:absolute;top:60rpx;right:10rpx;font-size:24rpx;width:120rpx;height:50rpx;line-height:50rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center}

.btitle{ width:100%;height:100rpx;background:#fff;padding:0 20rpx;border-bottom:1px solid #f5f5f5}
.btitle .comment{border: 1px #ffc702 solid;border-radius:10rpx;background:#fff; color: #ffc702;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}
.btitle .comment2{border: 1px #ffc7c0 solid;border-radius:10rpx;background:#fff; color: #ffc7c0;  padding: 0 10rpx; height: 46rpx; line-height: 46rpx;}

.picker{display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:1;overflow:hidden;}

.uni-popup-dialog {width: 300px;border-radius: 5px;background-color: #fff;}
.uni-dialog-title {display: flex;flex-direction: row;justify-content: center;padding-top: 15px;padding-bottom: 5px;}
.uni-dialog-title-text {font-size: 16px;font-weight: 500;}
.uni-dialog-content {display: flex;flex-direction: row;justify-content: center;align-items: center;padding: 5px 15px 15px 15px;}
.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;}
.uni-dialog-button-group {display: flex;flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
.uni-dialog-button {display: flex;flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;}
.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
.uni-dialog-button-text {font-size: 14px;}
.uni-button-color {color: #007aff;}

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}
</style>