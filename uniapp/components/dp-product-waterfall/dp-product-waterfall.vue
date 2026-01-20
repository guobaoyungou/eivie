<template>
	<view class="waterfalls-box" :style="{ height: height + 'px' }">
		<view v-for="(item, index) of list" class="waterfalls-list" :key="item[idfield]"
			:id="'waterfalls-list-id-' + item[idfield]" :ref="'waterfalls-list-id-' + item[idfield]" :style="{
        '--offset': offset + 'px',
        '--cols': cols,
				'background':probgcolor,
        top: allPositionArr[index] ? allPositionArr[index].top : 0,
        left: allPositionArr[index] ? allPositionArr[index].left : 0,
      }"  @click="toDetail(index)">
			<image class="waterfalls-list-image" mode="widthFix" :style="imageStyle" :src="item[imageSrcKey] || ' '"
				@load="imageLoadHandle(index)" @error="imageLoadHandle(index)" />
			<image class="saleimg" :src="saleimg" v-if="saleimg!=''" mode="widthFix" />
			<view>
				<view class="product-info">
					<view class="p1" v-if="showname == 1">{{item.name}}</view>
					<view class="binfo flex-y-center" v-if="showbname&&item.binfo">
						<image :src="item.binfo.logo" class="t1"><text class="t2">{{item.binfo.name}}</text>
					</view>
          <view :style="{color:t('color1')}" v-if="item.showgivescore">
            <text style="font-size: 24rpx;">赠送{{item.showgivescore}}{{t('积分')}}</text>
          </view>
					<view  v-if="item.show_cost && item.price_type != 1" :style="{color:item.cost_color?item.cost_color:'#999',fontSize:'36rpx'}"><text style="font-size: 24rpx;">{{item.cost_tag}}</text>{{item.cost_price}}</view>      
					<view class="p2">
						<view class="p2-1 flex-y-center" v-if="(!item.show_sellprice || (item.show_sellprice && item.show_sellprice==true)) && ( item.price_type != 1 || item.sell_price > 0) && showprice == '1'">
							<view class="t1" :style="{color:item.price_color?item.price_color:t('color1')}">
								<text style="font-size:24rpx">{{item.price_tag?item.price_tag:'￥'}}</text>{{item.sell_price}}<text style="font-size:24rpx" v-if="item.product_unit">/{{item.product_unit}}</text>
                <text v-if="item.price_show && item.price_show_text" style="margin: 0 15rpx;font-size: 22rpx;font-weight: 400;">{{item.price_show_text}}</text>
								<text v-if="item.product_type==2 && item.unit_price && item.unit_price>0" class="t1-m" :style="{color:t('color1')}">
									(约{{item.unit_price}}元/斤)
								</text>
							</view>
							<text class="t2"
								v-if="item.show_sellprice == '1' && item.market_price*1 > item.sell_price*1  && showprice == '1'">￥{{item.market_price}}</text>
							<text class="t3" v-if="item.juli" style="color:#888;">{{item.juli}}</text>
						</view>
						<view class="p2-1" v-if="item.xunjia_text && item.price_type == 1 && item.sell_price <= 0"
							style="height: 50rpx;line-height: 44rpx;">
							<text v-if="showstyle!=1" class="t1" :style="{color:t('color1'),fontSize:'30rpx'}">询价</text>
							<text v-if="showstyle==1" class="t1" :style="{color:t('color1')}">询价</text>
							<block v-if="item.xunjia_text && item.price_type == 1">
								<view class="lianxi" :style="{background:t('color1')}" @tap.stop="showLinkChange"
									:data-lx_name="item.lx_name" :data-lx_bid="item.lx_bid"
									:data-lx_bname="item.lx_bname" :data-lx_tel="item.lx_tel" data-btntype="2">
									{{item.xunjia_text?item.xunjia_text:'联系TA'}}</view>
							</block>
						</view>
					</view>
          <!-- 商品处显示会员价 -->
          <view v-if="item.price_show && item.price_show == 1" style="line-height: 46rpx;">
            <text style="font-size:24rpx">￥{{item.sell_putongprice}}</text>
          </view>
          <view v-if="item.priceshows && item.priceshows.length>0">
            <view v-for="(item2,index2) in item.priceshows" style="line-height: 46rpx;">
              <text style="font-size:24rpx">￥{{item2.sell_price}}</text>
              <text style="margin-left: 15rpx;font-size: 22rpx;font-weight: 400;">{{item2.price_show_text}}</text>
            </view>
          </view>
					<view class="couponitem" v-if="showcommission == 1 && item.commission_price > 0">
						<view class="f1">
							<view class="t" :style="{background:'rgba('+t('color2rgb')+',0.1)',color:t('color2')}">
								<text>{{t('佣金')}}{{item.commission_price}}{{item.commission_desc}}</text>
							</view>
						</view>
					</view>
					<view class="p1" v-if="item.merchant_name"
						style="color: #666;font-size: 24rpx;white-space: nowrap;text-overflow: ellipsis;margin-top: 6rpx;height: 30rpx;line-height: 30rpx;font-weight: normal;">
						<text>{{item.merchant_name}}</text></view>
					<view class="p1" v-if="item.main_business"
						style="color: #666;font-size: 24rpx;margin-top: 4rpx;font-weight: normal;">
						<text>{{item.main_business}}</text></view>
          <view class="p3" v-if="item.product_type==3">
          	<text>手工费: ￥{{item.hand_fee?item.hand_fee:0}}</text>
          </view>
					<view class="p3" v-if="(showsales=='1' && item.sales>0) || showstock=='1'">
						<text v-if="showsales=='1' && item.sales>0">已售{{item.sales}}</text>
						<text v-if="(showsales=='1' && item.sales>0) && showstock=='1'"
							style="padding:0 4px;font-size:22rpx">|</text>
						<text v-if="showstock=='1'">仅剩{{item.stock}}</text>
					</view>
					<view v-if="(showsales !='1' ||  item.sales<=0) && item.main_business" style="height: 44rpx;">
					</view>

					<view class="p4" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}"
						v-if="showcart==1 && !item.price_type && item.hide_cart!=true" @click.stop="buydialogChange"
						:data-proid="item[idfield]"><text class="iconfont icon_gouwuche"></text></view>
					<view class="p4" :style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}"
						v-if="showcart==2 && !item.price_type && item.hide_cart!=true" @click.stop="buydialogChange"
						:data-proid="item[idfield]">
						<image :src="cartimg" class="img" /></text>
					</view>
				</view>
				<view v-if="showcoupon==1 && (item.couponlist).length>0" class="couponitem">
					<view class="f1">
						<view v-for="(coupon, index2) in item.couponlist" :key="index2" class="t"
							:style="{background:'rgba('+t('color1rgb')+',0.1)',color:t('color1')}">
							<text v-if="coupon.minprice > 0">满{{coupon.minprice}}减{{coupon.money}}</text>
							<text v-if="coupon.minprice == 0">{{coupon.money}}元无门槛</text>
						</view>
					</view>
				</view>
				<view class="bg-desc" v-if="item.hongbaoEdu > 0"
					:style="{background:'linear-gradient(90deg,'+t('color2')+' 0%,rgba('+t('color2rgb')+',0.8) 100%)'}">
					可获额度 +{{item.hongbaoEdu}}</view>
			</view>
		</view>
		<block v-if="productType == 4">
			<block v-if="ggNum == 2">
				<buydialog-pifa v-if="buydialogShow" :proid="proid" @buydialogChange="buydialogChange" @showLinkChange="showLinkChange" :menuindex="menuindex" />
			</block>
			<block v-else>
				<buydialog-pifa2 v-if="buydialogShow" :proid="proid" @buydialogChange="buydialogChange" @showLinkChange="showLinkChange" :menuindex="menuindex" />
			</block>
		</block>
		<block v-else>
			<buydialog v-if="buydialogShow" :proid="proid" @addcart="addcart" @buydialogChange="buydialogChange" :menuindex="menuindex"></buydialog>
		</block>
		<view class="posterDialog linkDialog" v-if="showLinkStatus">
			<view class="main">
				<view class="close" @tap="showLinkChange">
					<image class="img" :src="pre_url+'/static/img/close.png'" />
				</view>
				<view class="content">
					<view class="title">{{lx_name}}</view>
					<view class="row" v-if="lx_bid > 0">
						<view class="f1" style="width: 150rpx;">店铺名称</view>
						<view class="f2" style="width: 100%;max-width: 470rpx;display: flex;" @tap="goto" :data-url="'/pagesExt/business/index?id='+lx_bid">
						  <view style="width: 100%;white-space: nowrap;text-overflow: ellipsis;overflow: hidden;">{{lx_bname}}</view>
						  <view style="flex: 1;"></view>
						  <image :src="pre_url+'/static/img/arrowright.png'" class="image"/>
						</view>
					</view>
					<view class="row" v-if="lx_tel">
						<view class="f1" style="width: 150rpx;">联系电话</view>
						<view class="f2" style="width: 100%;max-width: 470rpx;" @tap="goto" :data-url="'tel::'+lx_tel" :style="{color:t('color1')}">{{lx_tel}}
							<image :src="pre_url+'/static/img/copy.png'" class="copyicon" @tap.stop="copy" :data-text="lx_tel">
							</image>
						</view>
					</view>
				</view>
			</view>
		</view>
	</view>
</template>
<script>
	var app = getApp();
	export default {
		props: {
			list: {
				type: Array,
				required: true
			},
			// offset 间距，单位为 px
			offset: {
				type: Number,
				default: 8
			},
			// 列表渲染的 key 的键名，值必须唯一，默认为 id
			idfield: {
				type: String,
				default: "id"
			},
			// 图片 src 的键名
			imageSrcKey: {
				type: String,
				default: "pic"
			},
			// 列数
			cols: {
				type: Number,
				default: 2,
				validator: (num) => num >= 2
			},
			imageStyle: {
				type: Object
			},

			showstyle: {
				default: 2
			},
			menuindex: {
				default: -1
			},
			saleimg: {
				default: ''
			},
			showname: {
				default: 1
			},
			namecolor: {
				default: '#333'
			},
			showprice: {
				default: '1'
			},
			showcost: {
				default: '0'
			},
			showsales: {
				default: '1'
			},
			showcart: {
				default: '1'
			},
			cartimg: {
				default: '/static/imgsrc/cart.svg'
			},
			showstock: {
				default: '0'
			},
			showbname: {
				default: '0'
			},
			showcoupon: {
				default: '0'
			},
			showcommission: {
				default: '0'
			},
			probgcolor:{default:'#fff'}
      
		},
		data() {
			return {
				topArr: [], // left, right 多个时依次表示第几列的数据
				allPositionArr: [], // 保存所有的位置信息
				allHeightArr: [], // 保存所有的 height 信息
				height: 0, // 外层包裹高度
				oldNum: 0,
				num: 0,
				buydialogShow: false,
				proid: 0,
				showLinkStatus: false,
        lx_bname:'',
				lx_name: '',
				lx_bid: '',
				lx_tel: '',
				productType:'',
				ggNum:'',
				pre_url: app.globalData.pre_url,
			};
		},
		created() {
			this.refresh();
		},
		methods: {
			buydialogChange: function(e) {
				if (!this.buydialogShow) {
					this.proid = e.currentTarget.dataset.proid;
					this.list.forEach(item => {
						if(item[this.idfield] == this.proid){
							this.productType = item.product_type;
							if(item.product_type == 4){
								if(item.gg_num){
									this.ggNum = item.gg_num;
								}else if(item.guigedata){
									this.ggNum = Object.keys(JSON.parse(item.guigedata)).length;
								}
							}
						}
					})
				}
				this.buydialogShow = !this.buydialogShow;
			},
			addcart: function() {
				this.$emit('addcart');
			},
			showLinkChange: function(e) {
				var that = this;
				that.showLinkStatus = !that.showLinkStatus;
				that.lx_name = e.currentTarget.dataset.lx_name;
				that.lx_bid = e.currentTarget.dataset.lx_bid;
				that.lx_bname = e.currentTarget.dataset.lx_bname;
				that.lx_tel = e.currentTarget.dataset.lx_tel;
			},

			imageLoadHandle(index) {
				const id = "waterfalls-list-id-" + this.list[index][this.idfield],
					query = uni.createSelectorQuery().in(this);
					setTimeout(() => {
					query.select("#" + id)
						.fields({
							size: true
						}, (data) => {
							this.num++;
							this.$set(this.allHeightArr, index, data.height);
							if (this.num === this.list.length) {
								for (let i = this.oldNum; i < this.num; i++) {
									const getTopArrMsg = () => {
										let arrtmp = [...this.topArr].sort((a, b) => a - b);
										return {
											shorterIndex: this.topArr.indexOf(arrtmp[0]),
											shorterValue: arrtmp[0],
											longerIndex: this.topArr.indexOf(arrtmp[this.cols - 1]),
											longerValue: arrtmp[this.cols - 1],
										};
									};
									const {
										shorterIndex,
										shorterValue
									} = getTopArrMsg();
									const position = {
										top: shorterValue + "px",
										left: (data.width + this.offset) * shorterIndex + "px",
									};
									this.$set(this.allPositionArr, i, position);
									this.topArr[shorterIndex] =
										shorterValue + this.allHeightArr[i] + this.offset;
									this.height = getTopArrMsg().longerValue - this.offset;
								}
								this.oldNum = this.num;
								this.$emit("image-load");
							}
						})
						.exec();
					},100)
			},
			refresh() {
				let arr = [];
				for (let i = 0; i < this.cols; i++) {
					arr.push(0);
				}
				this.topArr = arr;
				this.num = 0;
				this.oldNum = 0;
				this.height = 0;
			},
			toDetail:function(key){
				var that = this;
				var item = that.list[key];
				var id = item[that.idfield];
				var url = '/pages/shop/product?id='+id;//默认链接
				//来自商品柜
				if(item.device_id){
					var dgid = item.id;
					var deviceno = item.device_no;
					var lane = item.goods_lane;
					var prodata  = id+','+item.ggid+','+item.stock;
					var devicedata = deviceno+','+lane;
					url = url+'&dgprodata='+prodata+'&devicedata='+devicedata;
				}
				app.goto(url);
			}
		},
	};
</script>
<style scoped>
	.waterfalls-box {
		position: relative;
		width: 100%;
		overflow: hidden;
	}

	.waterfalls-box .waterfalls-list {
		width: calc((100% - var(--offset) * (var(--cols) - 1)) / var(--cols));
		position: absolute;
		background-color: #fff;
		border-radius: 8rpx;
		left: calc(-50% - var(--offset));
	}

	.waterfalls-box .waterfalls-list .waterfalls-list-image {
		width: 100%;
		will-change: transform;
		border-radius: 8rpx 8rpx 0 0;
		display: block;
	}


	.waterfalls-box .saleimg {
		position: absolute;
		width: 60px;
		height: auto;
		top: 0;
	}

	.waterfalls-box .product-info {
		padding: 20rpx 20rpx;
		position: relative;
	}

	.waterfalls-box .product-info .p1 {
		color: #323232;
		font-weight: bold;
		font-size: 28rpx;
		line-height: 36rpx;
		margin-bottom: 10rpx;
		display: -webkit-box;
		-webkit-box-orient: vertical;
		-webkit-line-clamp: 2;
		overflow: hidden;
		word-break: break-all;
	}

	.waterfalls-box .product-info .p2 {
		display: flex;
		align-items: center;
		overflow: hidden;
		padding: 2px 0
	}

	.waterfalls-box .product-info .p2-1 {
		flex-grow: 1;
		flex-shrink: 1;
		height: 40rpx;
		line-height: 40rpx;
		overflow: hidden;
		white-space: nowrap
	}

	.waterfalls-box .product-info .p2-1 .t1 {
		font-size: 36rpx;
	}

	.waterfalls-box .product-info .p2-1 .t2 {
		margin-left: 10rpx;
		font-size: 24rpx;
		color: #aaa;
		text-decoration: line-through;
		/*letter-spacing:-1px*/
	}

	.waterfalls-box .product-info .p2-1 .t3 {
		margin-left: 10rpx;
		font-size: 22rpx;
		color: #999;
	}

	.waterfalls-box .product-info .p2-2 {
		font-size: 20rpx;
		height: 40rpx;
		line-height: 40rpx;
		text-align: right;
		padding-left: 20rpx;
		color: #999
	}

	.waterfalls-box .product-info .p3 {
		color: #999999;
		font-size: 20rpx;
		margin-top: 10rpx
	}

	.waterfalls-box .product-info .p4 {
		width: 52rpx;
		height: 52rpx;
		border-radius: 50%;
		position: absolute;
		display: relative;
		bottom: 16rpx;
		right: 20rpx;
		text-align: center;
	}

	.waterfalls-box .product-info .p4 .icon_gouwuche {
		font-size: 30rpx;
		height: 52rpx;
		line-height: 52rpx
	}

	.waterfalls-box .product-info .p4 .img {
		width: 100%;
		height: 100%
	}
	.waterfalls-box .product-info .p2 .t1-m {font-size: 32rpx;padding-left: 8rpx;}

	.waterfalls-box .product-info .binfo {
		padding: 6rpx 0;
		display: flex;
		align-items: center;
		min-width: 0;
	}

	.waterfalls-box .product-info .binfo .t1 {
		width: 30rpx;
		height: 30rpx;
		border-radius: 50%;
		margin-right: 10rpx;
		flex-shrink: 0;
	}

	.waterfalls-box .product-info .binfo .t2 {
		color: #666;
		font-size: 24rpx;
		font-weight: normal;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}

	.waterfalls-box .couponitem {
		width: 100%;
		/* padding: 0 20rpx 20rpx 20rpx; */
		font-size: 24rpx;
		color: #333;
		display: flex;
		align-items: center;
	}

	.waterfalls-box .couponitem .f1 {
		flex: 1;
		display: flex;
		flex-wrap: nowrap;
		overflow: hidden
	}

	.waterfalls-box .couponitem .f1 .t {
		margin-right: 10rpx;
		border-radius: 3px;
		font-size: 22rpx;
		height: 40rpx;
		line-height: 40rpx;
		padding: 0 10rpx;
		flex-shrink: 0;
		overflow: hidden
	}

	.bg-desc {
		color: #fff;
		padding: 10rpx 20rpx;
	}

	.lianxi {
		color: #fff;
		border-radius: 50rpx 50rpx;
		line-height: 50rpx;
		text-align: center;
		font-size: 22rpx;
		padding: 0 14rpx;
		display: inline-block;
		float: right;
	}
</style>
