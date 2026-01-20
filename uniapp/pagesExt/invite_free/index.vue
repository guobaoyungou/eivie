<template>
<view class="container">
	<dd-tab :itemdata="['免单中','待免单','已免单']" :itemst="['2','1','3']" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
    <block v-if="isload">
		<view style="width:100%;height:100rpx"></view>
		<view class="order-content">
			<block v-for="(item, index) in datalist" :key="index">
				<view class="order-box" @tap="goto" :data-url="'/pagesExt/order/detail?id=' + item.id">
					<block v-for="(item2, idx) in item.prolist" :key="idx">
						<view class="content" :style="idx+1==item.procount?'border-bottom:none':''">
							<view @tap.stop="goto" :data-url="'/pages/shop/product?id=' + item2.proid">
								<image :src="item2.pic"></image>
							</view>
							<view class="detail">
								<text class="t1">{{item2.name}}</text>
								<text class="t2">{{item2.ggname}}</text>
								<!-- <view class="t3">
									<text class="x1 flex1">￥{{item2.sell_price}}</text>
									<text class="x2">×{{item2.num}}</text>
								</view> -->
							</view>
						</view>
					</block>
					<view v-if="st == 2 || st == 3 " class="bottom" style="text-align: right;">
						<text style="text-decoration: line-through;color: red;">￥{{item.totalprice}}</text>
                        <text style="margin-left: 10rpx;">本次免单金额</text>
                        <block v-if="st == 3 ">
                            <text v-if="item.status==4" class="st4" style="color: red;">免单失败</text>
                        </block>
					</view>
                    <view v-else class="bottom" style="text-align: right;">
                    	总金额<text style="color: red;">￥{{item.totalprice}}</text>
                    </view>
				</view>
			</block>
            <block v-if="st == 2 && (!datalist || datalist.length<=0)">
                <view style="width: 700rpx;margin: 0 auto;border-radius: 12rpx;border: 2rpx solid #eee;overflow: hidden;text-align: center;line-height: 60rpx;background-color: #fff;padding: 20rpx;margin-top: 10rpx;margin-bottom: 20rpx;">
                    <view style="text-align: center;line-height:80rpx">
                        暂无免单
                    </view>
                </view>
            </block>
		</view>
        <block v-if="st!=2">
            <nomore v-if="nomore"></nomore>
            <nodata v-if="nodata"></nodata>
        </block>
        <block v-if=" st == 2 && set && set.status == 1">
            <view  style="width: 700rpx;margin: 0 auto;border-radius: 12rpx;border: 2rpx solid #eee;overflow: hidden;text-align: center;line-height: 60rpx;background-color: #fff;padding: 20rpx;">
                <view style="width: 328rpx;float: left;">
                    <view>邀请好友进度</view>
                    <view class="canvas_view" :style="canvas_status?'display:block':'display:none'">
                        <canvas style="width: 200rpx; height: 200rpx;" canvas-id="hycirclefCanvas" id="hycirclefCanvas"></canvas>
                    </view>
                    <view>已邀请:{{now_num}}/{{all_num}}人</view>
                </view>
                <view style="width: 328rpx;float: left;">
                    <view>累计金额进度</view>
                    <view class="canvas_view" :style="canvas_status?'display:block':'display:none'">
                        <canvas style="width: 200rpx; height: 200rpx;" canvas-id="moneycirclefCanvas" id="moneycirclefCanvas"></canvas>
                    </view>
                    <view>累计金额:{{now_money}}/{{all_money}}</view>
                </view>
            </view>
            <view @tap="changegz"  style="width: 700rpx;margin: 0 auto;border-radius: 12rpx;border: 2rpx solid #eee;overflow: hidden;text-align: left;line-height: 60rpx;background-color: #fff;padding: 20rpx;margin-top: 20rpx;">
                <text>免单规则</text>
                <text style="float: right;">></text>
            </view>
            <view style="width: 700rpx;margin: 0 auto;border-radius: 12rpx;border: 2rpx solid #eee;overflow: hidden;text-align: center;line-height: 60rpx;background-color: #fff;padding: 20rpx;margin-top: 20rpx;margin-bottom: 20rpx;">
                <view style="text-align:left;">我邀请的好友</view>
                <block v-if="hylist">
                    <scroll-view  :scrollWithAnimation="animation" scroll-y="true" :class="menuindex>-1?'tabbarbot':''" style="width: 100%;max-height: 500rpx;">
                        <view  v-for="(item, index) in hylist" :key="index" style="padding-bottom: 20rpx;border-bottom: 2rpx solid #eee;">
                            <view style="overflow: hidden;">
                                <image :src="item.headimg" style="width: 50rpx;height: 50rpx;border-radius: 50%;overflow: hidden;float: left;margin-top: 4rpx;"></image>
                                <text style="float: left;margin-left: 10rpx;">{{item.nickname}}</text>
                            </view>
                            <view style="overflow: hidden;text-align: left;">
                                <view style="width: 25%;float: left;">
                                    <view>加入时间</view>
                                    <view style="font-size: 22rpx;line-height: 30rpx;">{{item.createtime}}</view>
                                </view>
                                <view style="width: 25%;float: left;">
                                    <view>加购</view>
                                    <view style="font-size: 22rpx;line-height: 30rpx;">{{item.cart_num}}</view>
                                </view>
                                <view style="width: 25%;float: left;">
                                    <view>下单</view>
                                    <view style="font-size: 22rpx;line-height: 30rpx;">{{item.order_num}}</view>
                                </view>
                                <view style="width: 25%;float: left;white-space:pre-wrap">
                                    <view>金额</view>
                                    <view style="font-size: 22rpx;line-height: 30rpx;">{{item.order_money}}</view>
                                </view>
                            </view>
                        </view>
                    </scroll-view>
                </block>
                <block v-else>
                    <view style="text-align: center;line-height:80rpx">
                        暂无信息
                    </view>
                </block>
            </view>
        </block>
	</block>
    <block v-if="open_gz">
        <view @tap="changegz" style="width:100%;height: 100%;background-color: #000;position: fixed;opacity: 0.5;z-index: 99;top:0"></view>
        <view style="width: 700rpx;margin: 0 auto;position: fixed;top:10%;left: 25rpx;z-index: 100;">
            <view style="background-color: #fff;border-radius: 20rpx;overflow: hidden;width: 100%;padding:10rpx 20rpx;">
                <scroll-view  :scrollWithAnimation="animation" scroll-y="true" :class="menuindex>-1?'tabbarbot':''" style="width: 100%;height: 800rpx;min-height: 200rpx;">
                <parse :content="set.shuoming"></parse>
                </scroll-view>
            </view>
            <view @tap="changegz" style="width: 80rpx;height: 80rpx;line-height: 80rpx;text-align: center;font-size: 30rpx;background-color: #fff;margin: 0 auto;border-radius: 50%;margin-top: 20rpx;">
                X
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
            st: '2',
            datalist: [],
            pagenum: 1,
            nomore: false,
            nodata:false,
            set:'',
            now_num:0,
            all_num:0,
            now_money:0,
            all_money:0,
            hylist:'',//好友列表
            open_gz:false,
            tmplids:'',
            canvas_status:true
    };
  },
    onLoad: function (opt) {
        this.opt = app.getopts(opt);
        if(this.opt && this.opt.st){
            this.st = this.opt.st;
        }
        this.getdata();
    },
	onPullDownRefresh: function () {
		this.getdata();
	},
    onReachBottom: function () {
        if (this.st != 2 && !this.nodata && !this.nomore) {
          this.pagenum = this.pagenum + 1;
          this.getdata(true);
        }
    },
	onNavigationBarSearchInputConfirmed:function(e){
		this.searchConfirm({detail:{value:e.text}});
	},
  methods: {
        getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
            var that = this;
            var pagenum = that.pagenum;
            var st = that.st;
            that.nodata = false;
            that.nomore = false;
            that.loading = true;
            app.post('ApiInviteFree/orderlist', {st: st,pagenum: pagenum,keyword:that.keyword}, function (res) {
                that.loading = false;
                if(res.status == 1){
                    that.tmplids = res.free_tmplids;
                    var data = res.datalist;
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
                    if(st == 2){
                        if(res.set){
                            that.set = res.set;
                            var now_num = res.now_num?res.now_num:0;
                            that.now_num = now_num;
                            var all_num = res.all_num?res.all_num:0;
                            that.all_num = all_num;
                            that.hyjinDu(now_num,all_num);
                            
                            var now_money = res.now_money?res.now_money:0;
                            that.now_money = now_money;
                            var all_money = res.all_money?res.all_money:0;
                            that.all_money = all_money;
                            that.moneyjinDu(now_money,all_money);
                            
                            if(res.hylist){
                                that.hylist = res.hylist;
                            }else{
                                that.hylist = '';
                            }
                        }else{
                            that.set = '';
                        }
                    }
                }else{
                    app.alert(res.msg)
                }
                
            });
        },
        changetab: function (st) {
            this.st = st;
            uni.pageScrollTo({
                scrollTop: 0,
                duration: 0
            });
            this.pagenum = 1;
            this.getdata();
        },
		hideSelectExpressDialog:function(){
			this.$refs.dialogSelectExpress.close();
		},
		showhxqr:function(e){
			this.hexiao_qr = e.currentTarget.dataset.hexiao_qr
			this.$refs.dialogHxqr.open();
		},
		closeHxqr:function(){
			this.$refs.dialogHxqr.close();
		},
		searchConfirm:function(e){
			this.keyword = e.detail.value;
            this.getdata(false);
		},
        hyjinDu:function(now_num,all_num){
            //屏幕
            var windowWidth = uni.getSystemInfoSync().windowWidth ;
            if(windowWidth<350){
                var width = 30;
            }else{
                var width = 40;
            }

            /**
             * 环形进度条
             * arc(x, y, r, startAngle, endAngle, anticlockwise):
             * 以(x, y) 为圆心，以r 为半径，90°代表0.5 * PI
             * 从 startAngle 弧度开始到endAngle弧度结束。
             * anticlosewise 是布尔值，true 表示逆时针，false 表示顺时针(默认是顺时针
             */
                // 画整个圆环
                const ctx = uni.createCanvasContext('hycirclefCanvas')
                ctx.beginPath()
                ctx.arc(50, 50, width, 0, 2 * Math.PI) // 50为canvas宽度一班代表居中
                ctx.setStrokeStyle('#E7E7E7')
                ctx.setLineWidth(5)
                ctx.stroke()

                if(all_num>0){
                    
                    if(now_num>0){
                        var jd       = now_num/all_num *2-0.5;
                        if(jd<0){
                            jd  = 1.5 -jd;
                            var jd_num = parseInt(jd*1000)/1000;
                        }else{
                            var jd_num = parseInt(jd*1000)/1000;
                            if(jd_num>=1.5){
                                jd_num = 1.499;
                            }
                        }
                        var jd_pi  = jd_num*Math.PI;  
                        var now_num_text = parseInt(now_num/all_num*100)+'%';
                    }else{
                        var jd_pi  = 1.5*Math.PI;
                        var now_num_text = '0%';
                        
                    }
                }else{
                    if(now_num>0){
                        var jd_pi  = 1.499*Math.PI;
                        var now_num_text = '100%'; 
                    }else{
                        var jd_pi  = 1.5*Math.PI;
                        var now_num_text = '0%'; 
                    }
                }
                
                // 进度
                ctx.beginPath()
                ctx.arc(50, 50, width, 1.5* Math.PI, jd_pi)
                ctx.setStrokeStyle('#0152D8')
                ctx.setLineWidth(5)
                ctx.stroke()

                
                // 中心字体
                ctx.setFillStyle('#333')
                ctx.setFontSize(14)
                ctx.setTextAlign('center')
                ctx.fillText(now_num_text, 50, 55)
                ctx.stroke()
                //#ifdef H5
                setTimeout(function(){
                    ctx.draw()
                },600)
                //#endif
                //#ifndef H5
                    ctx.draw()
                //#endif
            //
        },
        moneyjinDu:function(now_money,all_money){
            //屏幕
            var windowWidth = uni.getSystemInfoSync().windowWidth ;
            if(windowWidth<350){
                var width = 30;
            }else{
                var width = 40;
            }
            
            /**
             * 环形进度条
             * arc(x, y, r, startAngle, endAngle, anticlockwise):
             * 以(x, y) 为圆心，以r 为半径，90°代表0.5 * PI
             * 从 startAngle 弧度开始到endAngle弧度结束。
             * anticlosewise 是布尔值，true 表示逆时针，false 表示顺时针(默认是顺时针
             */
                // 画整个圆环
                const ctx = uni.createCanvasContext('moneycirclefCanvas')
                ctx.beginPath()
                ctx.arc(50, 50, width, 0, 2 * Math.PI) // 50为canvas宽度一班代表居中
                ctx.setStrokeStyle('#E7E7E7')
                ctx.setLineWidth(5)
                ctx.stroke()
                
                if(all_money>0){
                    
                    if(now_money>0){
                        var jd       = now_money/all_money *2-0.5;
                        if(jd<0){
                            jd  = 1.5 -jd;
                            var jd_money = parseInt(jd*1000)/1000;
                        }else{
                            var jd_money = parseInt(jd*1000)/1000;
                            if(jd_money>=1.5){
                                jd_money = 1.499;
                            }
                        }
                        var jd_pi  = jd_money*Math.PI;  
                        var now_money_text = parseInt(now_money/all_money*100)+'%';
                    }else{
                        var jd_pi  = 1.5*Math.PI;
                        var now_money_text = '0%';
                        
                    }
                }else{
                    if(now_money>0){
                        var jd_pi  = 1.499*Math.PI;
                        var now_money_text = '100%'; 
                    }else{
                        var jd_pi  = 1.5*Math.PI;
                        var now_money_text = '0%'; 
                    }
                }
                
                // 进度
                ctx.beginPath()
                ctx.arc(50, 50, width, 1.5* Math.PI, jd_pi )
                ctx.setStrokeStyle('#0152D8')
                ctx.setLineWidth(5)
                ctx.stroke()
        
                // 中心字体
                ctx.setFillStyle('#333')
                ctx.setFontSize(14)
                ctx.setTextAlign('center')
                ctx.fillText(now_money_text, 50, 55)
                ctx.stroke()
                //#ifdef H5
                setTimeout(function(){
                    ctx.draw()
                },600)
                //#endif
                //#ifndef H5
                    ctx.draw()
                //#endif
            //
        },
        changegz:function(){
           var that = this;
           var  open_gz = that.open_gz;
           that.open_gz = !open_gz;
           if(that.open_gz){
               that.canvas_status = false;
               // #ifdef MP-WEIXIN
                    var tmplids = that.tmplids;
                    console.log(tmplids)
                    if(tmplids && tmplids.length > 0){
                        uni.requestSubscribeMessage({
                            tmplIds: tmplids,
                            success:function(res) {
                            },
                            fail:function(res){

                            }
                        })
                    }
                //#endif
            }else{
                that.canvas_status = true;
            }

        },
  }
};
</script>
<style>
.container{ width:100%;}
.topsearch{width:94%;margin:10rpx 3%;}
.topsearch .f1{height:60rpx;border-radius:30rpx;border:0;background-color:#fff;flex:1}
.topsearch .f1 .img{width:24rpx;height:24rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;}
.order-content{display:flex;flex-direction:column}
.order-box{ width: 94%;margin:10rpx 3%;padding:6rpx 3%; background: #fff;border-radius:8px}
.order-box .head{ display:flex;width:100%; border-bottom: 1px #f4f4f4 solid; height: 70rpx; line-height: 70rpx; overflow: hidden; color: #999;}
.order-box .head .f1{display:flex;align-items:center;color:#333}
.order-box .head image{width:34rpx;height:34rpx;margin-right:4px}
.order-box .head .st0{ width: 140rpx; color: #ff8758; text-align: right; }
.order-box .head .st1{ width: 140rpx; color: #ffc702; text-align: right; }
.order-box .head .st2{ width: 140rpx; color: #ff4246; text-align: right; }
.order-box .head .st3{ width: 140rpx; color: #999; text-align: right; }
.order-box .head .st4{ width: 140rpx; color: #bbb; text-align: right; }

.order-box .content{display:flex;width: 100%; padding:16rpx 0px;border-bottom: 1px #f4f4f4 dashed;position:relative}
.order-box .content:last-child{ border-bottom: 0; }
.order-box .content image{ width: 140rpx; height: 140rpx;}
.order-box .content .detail{display:flex;flex-direction:column;margin-left:14rpx;flex:1}
.order-box .content .detail .t1{font-size:26rpx;line-height:36rpx;margin-bottom:10rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.order-box .content .detail .t2{height: 46rpx;line-height: 46rpx;color: #999;overflow: hidden;font-size: 26rpx;}
.order-box .content .detail .t3{display:flex;height:40rpx;line-height:40rpx;color: #ff4246;}
.order-box .content .detail .x1{ flex:1}
.order-box .content .detail .x2{ width:100rpx;font-size:32rpx;text-align:right;margin-right:8rpx}

.order-box .bottom{ width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}
.order-box .op{ display:flex;flex-wrap: wrap;justify-content:flex-end;align-items:center;width:100%; padding: 10rpx 0px; border-top: 1px #f4f4f4 solid; color: #555;}

.btn1{margin-left:20rpx; margin-top: 10rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#fff;border-radius:3px;text-align:center;}
.btn2{margin-left:20rpx; margin-top: 10rpx;width:160rpx;height:60rpx;line-height:60rpx;color:#333;background:#fff;border:1px solid #cdcdcd;border-radius:3px;text-align:center;}

.hxqrbox{background:#fff;padding:50rpx;position:relative;border-radius:20rpx}
.hxqrbox .img{width:400rpx;height:400rpx}
.hxqrbox .txt{color:#666;margin-top:20rpx;font-size:26rpx;text-align:center}
.hxqrbox .close{width:50rpx;height:50rpx;position:absolute;bottom:-100rpx;left:50%;margin-left:-25rpx;border:1px solid rgba(255,255,255,0.5);border-radius:50%;padding:8rpx}

.st0{ width: 140rpx; color: #ff8758; text-align: right;margin-left: 20rpx; }
.st1{ width: 140rpx; color: #ffc702; text-align: right;margin-left: 20rpx; }
.st2{ width: 140rpx; color: #ff4246; text-align: right;margin-left: 20rpx; }
.st3{ width: 140rpx; color: #999; text-align: right;margin-left: 20rpx;}
.st4{ width: 140rpx; color: #bbb; text-align: right;margin-left: 20rpx; }

.canvas_view{width: 200rpx;height: 200rpx;margin: 0 auto;position: relative;z-index: 9;}
</style>