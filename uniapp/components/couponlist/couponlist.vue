<template>
    <view class="coupon-list">
        <view v-for="(item, index) in couponlist" :key="index" class="coupon" :style="couponstyle">
            <view class="left" :style="{color:t('color1')}" @tap="goto" :data-url="'/pagesExt/coupon/coupondetail?'+(choosecoupon?'rid':'id')+'=' + item.id">
                <text v-if="item.type==1" class="number">{{item.money}}</text>
                <view class="number complex" v-if="item.type==3">
                    <text v-if="choosetype =='choosecoupon'">{{item.limit_count}}</text>
                    <block v-else>
                        <text class="main">{{item.used_count}}</text>
                        <text class="minor">/{{item.limit_count}}</text>
                    </block>
                    <text class="t1" v-else style="font-size: 38rpx;">/</text>
                </view>
                <text v-if="item.type==10" class="number">{{item.discount/10}}</text>
                <text class="desc">{{descriptions[item.type]}}</text>
            </view>
            <view class="right">
                <view class="content">
                    <view class="info">
                        <text class="name">{{choosecoupon ? item.couponname : item.name}}</text>
                        <text :class="{'item':true,'compact':item.bid>0}">有效期：{{choosecoupon ? dateFormat(item.endtime,'Y.m.d H:i') : dateFormat(item.yxqdate,'Y.m.d H:i')}}</text>
                        <text class="item compact" v-if="item.bid>0">适用商家：{{item.bname}}</text>
                    </view>
                    <block v-if="choosecoupon">
                        <block v-if="choosetype =='choosecoupon'">
                            <button class="button" @tap="chooseCoupon" :data-rid="item.id" :data-key="index" v-if="selectedrid==item.id || inArray(item.id,selectedrids)">取消</button>
                            <button class="button" :style="{background:t('color1')}" @tap="chooseCoupon" :data-rid="item.id" :data-key="index" v-else>使用</button>
                        </block>
                        <block v-else>
                            <!-- 选择水票 -->
                            <button class="button" @tap="chooseWaterCoupon" :data-rid="item.id" :data-key="index" v-if="selectedrid==item.id || inArray(item.id,selectedrids)">取消</button>
                            <button class="button" :style="{background:t('color1')}" @tap="chooseWaterCoupon" :data-rid="item.id" :data-key="index" v-else>使用</button>
                        </block>
                    </block>
                    <block v-else>
                        <button class="button disabled" v-if="(item.haveget>=item.perlimit) && (item.perlimit > 0)">已领取</button>
                        <button class="button disabled" v-else-if="item.stock<=0">已抢光了</button>
                        <button class="button" v-else :style="{background:t('color1')}" @tap="getcoupon" :data-id="item.id" :data-price="item.price" :data-score="item.score" :data-key="index">领取</button>
                    </block>
                </view>
                <view class="rule" v-if="item.type==1 || item.type==4 || item.type==5 || item.type==6">
                    <text v-if="item.minprice>0">满{{item.minprice}}元可用</text>
                    <text v-else>无门槛</text>
                </view>
            </view>
        </view>
    </view>
</template>
<script>
	var app = getApp();
	export default {
		data(){
			return {
                descriptions: {
                    1: "代金券(元)", 2: "礼品券", 3: "计次券(次)", 4: "运费抵扣券", 5: "餐饮券", 6: "酒店券",
                    10: "折扣券(折)"
                }
			}
		},
		props: {
			menuindex:{default:-1},
			couponlist:{},
			couponstyle:{default:''},
			bid:{default:''},
			selectedrid:{default:''},
			selectedrids:{type:Array,default(){ return []}},
			choosecoupon:{default:false},
			choosetype:{default:'choosecoupon'},//选择类型，defaut:默认，shuipiao:水票选择
		},
		methods: {
			getcoupon:function(e){
				var that = this;
				var couponlist = that.couponlist;
				var key = e.currentTarget.dataset.key;
				var couponinfo = couponlist[key];
				if (app.globalData.platform == 'wx' && couponinfo && couponinfo.rewardedvideoad && wx.createRewardedVideoAd) {
					app.showLoading();
					if(!app.globalData.rewardedVideoAd[couponinfo.rewardedvideoad]){
						app.globalData.rewardedVideoAd[couponinfo.rewardedvideoad] = wx.createRewardedVideoAd({ adUnitId: couponinfo.rewardedvideoad});
					}
					var rewardedVideoAd = app.globalData.rewardedVideoAd[couponinfo.rewardedvideoad];
					rewardedVideoAd.load().then(() => {app.showLoading(false);rewardedVideoAd.show();}).catch(err => { app.alert('加载失败');});
					rewardedVideoAd.onError((err) => {
						app.showLoading(false);
						app.alert(err.errMsg);
						console.log('onError event emit', err)
						rewardedVideoAd.offLoad()
						rewardedVideoAd.offClose();
					});
					rewardedVideoAd.onClose(res => {
						app.globalData.rewardedVideoAd[couponinfo.rewardedvideoad] = null;
						if (res && res.isEnded) {
							//app.alert('播放结束 发放奖励');
							that.getcouponconfirm(e);
						} else {
							console.log('播放中途退出，不下发奖励');
						}
						rewardedVideoAd.offLoad()
						rewardedVideoAd.offClose();
					});
				}else{
					that.getcouponconfirm(e);
				}
			},
			getcouponconfirm:function(e){
				var that = this
				var id = e.currentTarget.dataset.id;
				var score = e.currentTarget.dataset.score;
				var price = e.currentTarget.dataset.price;
				if(price > 0){
					app.post('ApiCoupon/buycoupon', {id: id}, function (res) {
						if(res.status == 0) {
								app.error(res.msg);
						} else {
							app.goto('/pagesExt/pay/pay?id=' + res.payorderid);
						}
					})
					return;
				}
				if(score > 0){
					app.confirm('确定要消耗'+score+''+that.t('积分')+'兑换吗?',function(){
						app.showLoading('兑换中');
						app.post('ApiCoupon/getcoupon',{id:id},function(data){
							app.showLoading(false);
							if(data.status==0){
								app.error(data.msg);
							}else{
								app.success(data.msg);
								that.$emit('getcoupon');
							}
						});
					})
				}else{
					app.showLoading('领取中');
					app.post('ApiCoupon/getcoupon',{id:id},function(data){
						app.showLoading(false);
						if(data.status==0){
							app.error(data.msg);
						}else{
							app.success(data.msg);
							that.$emit('getcoupon');
						}
					});
				}
			},
			chooseCoupon:function(e){
				var rid = e.currentTarget.dataset.rid
				var key = e.currentTarget.dataset.key
				this.$emit('chooseCoupon',{rid:rid,bid:this.bid,key:key});
			},
			chooseWaterCoupon:function(e){
				var rid = e.currentTarget.dataset.rid
				var key = e.currentTarget.dataset.key
				this.$emit('chooseWaterCoupon',{rid:rid,bid:this.bid,key:key});
			}
		}
	}
</script>
<style lang="scss">
    .coupon-list {
        width: 100%;
        display: flex;
        flex-direction: column;
        padding: 0 30rpx;
        box-sizing: border-box;
        .coupon {
            width: 100%;
            background: #ffffff;
            border-radius: 16rpx;
            display: flex;
            flex-direction: row;
            align-items: center;
            padding: 36rpx 24rpx;
            box-sizing: border-box;
            &+.coupon {
                margin-top: 20rpx;
            }
            .left {
                width: 150rpx;
                display: flex;
                flex-direction: column;
                align-items: center;
                justify-content: center;
                .number {
                    font-size: 56rpx;
                    &.complex {
                        display: flex;
                        flex-direction: row;
                        align-items: baseline;
                        justify-content: center;
                        .main {
                            font-size: 64rpx;
                        }
                        .minor {
                            font-size: 24rpx;
                        }
                    }
                }
                .desc {
                    font-size: 24rpx;
                }
            }
            .right {
                margin-left: 24rpx;
                flex-grow: 1;
                display: flex;
                flex-direction: column;
                .content {
                    width: 100%;
                    display: flex;
                    flex-direction: row;
                    align-items: center;
                    justify-content: space-between;
                    .info {
                        display: flex;
                        flex-direction: column;
                        .name {
                            font-size: 30rpx;
                            color: #000000;
                        }
                        .item {
                            margin-top: 22rpx;
                            font-size: 24rpx;
                            color: rgba(58, 68, 99, 0.5);
                            &.compact {
                                margin-top: 0;
                            }
                        }
                    }
                    .button {
                        color: #ffffff;
                        height: 60rpx;
                        line-height: 60rpx;
                        min-width: 140rpx;
                        margin: 0;
                        font-size: 26rpx;
                        border-radius: 60rpx;
                        background: #555555;
                        &.disabled {
                            background: #9d9d9d;
                        }
                    }
                }
                .rule {
                    margin-top: 24rpx;
                    width: 100%;
                    font-size: 24rpx;
                    color: rgba(58, 68, 99, 0.5);
                }
            }
        }
    }
</style>