<template>
	<view class="container">
		<block v-if="isload">
			<scroll-view scroll-y class="scroll-class">
				<view class="options-view" v-if='tacanSellPoint'>
					<view class="option-title">
						<text class="text-name">简介</text>
					</view>
					<view class="shop-list flex-row" style="font-size:28rpx;color: #666;">
						{{tacanSellPoint}}
					</view>
				</view>
				<block v-for="(item,index) in datalist">
					<view class="options-view" :key="index">
						<view class="option-title">
							<text class="text-name">{{item.category_name}} {{item.type == 2 ? '(请选择'+ item.selectnum +'份)':''}}</text>
						</view>
						<view class="shop-list flex-row">
							<block v-for="(items,indexs) in item.prolist">
								<view class="options-shop flex-col"   @click="selectProduct(index,item,items)" :style="{borderColor:items.checked ? t('color1') : '#f7f7f7'}" :key="indexs">
									<view class='shop-image flex-x-center flex-y-center'>
										<image :src="items.pic"></image>
									</view>
									<view class="shop-info-view" :style="{backgroundColor:item.type == 0 ? '#fff':''}">
										<view class="shop-name">{{items.proname}} x {{items.num || 1}}</view>
										<block v-if="items.guigedata && items.checked">
											<view class="gg-name">(默认规格)</view>
										</block>
										<block v-else>
											<view class="gg-name" v-if="items.ggname != '默认规格'">({{items.ggname}})</view>
										</block>
										<view class="addnum" v-if="item.type ==2">
											<view class="minus" @click="choiceMinusPlus(index,item,items,'Minus')" v-if="items.num > 0">-</view>	
											<text class="i" v-if="items.num > 0">{{items.num}}</text>
											<view class="plus" @click="choiceMinusPlus(index,item,items,'Plus')" v-if="items.num < item.selectnum && (Number(item.nowselectnum) < Number(item.selectnum))">+</view>
											<view class="plus no" v-else>+</view>
										</view>
									</view>
									<!-- 加价 -->
									<view class="jia-price" v-if='items.checked && item.type !=2 && Number(items.add_price) > 0'>
											+{{items.add_price}}
									</view>
									<view class="choose-view" :style="{background:t('color1'),border:t('color1')}" v-if="items.checked && item.type !=2">
										<image :src="pre_url+'/static/img/checkd.png'"></image>
									</view>
									<!-- 多选 -->
									<!-- 定制规格 -->
									<block v-if="items.guigedata && items.checked && items.guigelist.length > 1">
										<view class="gg-name-but" :style="{borderColor:t('color1'),background:'rgb(' + t('color1rgb')+',0.8'+')'}" @click="customizedChange(index,indexs,item.type,items)">定制规格</view>
									</block>
								</view>
							</block>
						</view>
					</view>
				</block>
				<view class="occupy-box"  :style="{height:'calc(env(safe-area-inset-bottom) + ' + footerheight + 'px)' }"></view>
			</scroll-view>
			<view class="footer flex-col" :class="menuindex>-1?'tabbarbot':''">
			<scroll-view scroll-y class="scrollview-class">
				<view class="select-display">
					<block v-for="(item,index) in addcartList">
						<view class="product-pit-view" :style="{borderColor:t('color1')}"  @click="productCustom(item,index)">
							<view class="select-shop-image">
								<image :src="item.pic"></image>
							</view>
							<view class="num-view" :style="{background:'#fff',border:'1px solid ' + t('color1'),color:t('color1'),fontSize:'18rpx'}" v-if="item.guigelist && item.guigedata.length > 1">
								定制
							</view>
							<view class="num-view" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  v-else>
								x1
							</view>
						</view>
					</block>
				</view>
				</scroll-view>
				<view class="addcart-view" @click="selectquantities(true)" :style="{background:t('color1')}" v-if="addcartListLength == addcartList.length">
					加入购物车
				</view>
				<view class="addcart-view please-select" v-else @click="tispChange">
					请选择餐品
				</view>
			</view>
		</block>
		<!-- 详情弹窗 -->
		<uni-popup id="popup" ref="popup" type="bottom" style="height: 88vh;">
			<view class="hotelpopup__content">
				<view class="popup-close" @click="popupClose">
					<image :src="`${pre_url}/static/img/hotel/popupClose.png`"></image>
				</view>
				<scroll-view scroll-y style="height: auto;max-height: 80vh;">
					<view class="custom-image-view">
						<image :src="customShopRes.pic"></image>
					</view>
					<!-- 多商品切换 -->
					<view class="selectproduct-list" v-if="customShopRes.num > 1 && productSwitchList.length">
						<scroll-view scroll-x style="width: 100%;white-space: nowrap;">
							<block v-for="(item,index) in productSwitchList">
								<view class="options-list" :style="item.popupCheck ? 'color:' + t('color1') + ';' + 'border-color:' + t('color1'):''" @click="shopSwitch(index)">
									第 {{index + 1}} 份
								</view>
							</block>
						</scroll-view>
					</view>
					<view class="customproname-view">
						{{customShopRes.proname}}
					</view>
					<view  class="gglist-view flex flex-col">
						<view v-for="(item, index) in customShopRes.guigedata" :key="index" class="gglist-view-options flex-col">
							<view class="name">{{item.title}}</view>
							<view class="item flex flex-y-center">
								<block v-for="(item2, index2) in item.items" :key="index2">
									<view :data-itemk="item.k" :data-idx="item2.k" class="item2" :style="customShopRes.ggselected[item.k]==item2.k? 'color:' + t('color1') + ';' + 'border-color:' + t('color1'):''" @tap="ggchange">{{item2.title}}</view>
								</block>
							</view>
						</view>
					</view>
				</scroll-view>
				<!-- 确定 -->
				<view class="popup-but-view flex flex-col">
					<view class="page_content">已选规格: {{nowguige.ggname}}</view>
					<view class="but-class" :style="'background: linear-gradient(90deg,rgba('+t('color1rgb')+',1) 0%,rgba('+t('color1rgb')+',1) 100%)'" @click="popupClose">确定</view>
				</view>
			</view>
		</uni-popup>
		<loading v-if="loading"></loading>
		<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
		<wxxieyi></wxxieyi>
	</view>
</template>
<script>
// import { object } from 'prop-types';
	var app = getApp();
	export default {
		data() {
			return {
				opt: {},
				loading: false,
				isload: false,
				menuindex: -1,
				nomore: false,
				nodata: false,
				st: 0,
				datalist:[],
				shopid:'',
				addcartList:[],
				addcartListLength:0,
				add_price:0,
				package_data:[],
				proid:'',
				footerheight:'',
				footerShow:false,
				pre_url: app.globalData.pre_url,
				customShopRes:{},
				ggselected:{},
				nowguige:{},
				ks:'',
				productSwitchList:[],
				tacanSellPoint:'',
				tableId:''
			};
		},
		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			this.shopid = this.opt.id || '';
			this.tableId = this.opt.tableId || '';
			uni.setNavigationBarTitle({
				title:this.opt.name
			})
			this.getdata();
		},
		onPullDownRefresh: function() {
			
		},
		onShow: function() {
			
		},
		onReachBottom: function() {

		},
		methods: {
			// 弹窗中份数切换
			shopSwitch(index){
				this.productSwitchList.forEach((item,indexs) => {
					if(indexs == index){
						item.popupCheck = true;
						this.customShopRes = item;
						this.nowguige = this.customShopRes.guigelist[item.ks];			
					}else{
						item.popupCheck = false;
					}
				})
			},
			// 选择定制
			customizedChange(index,indexs,type,e){
				if(e.guigelist && e.guigedata.length > 0){
					if(type == 2){
						// 每次点击都恢复默认值
						this.productSwitchList = [];
						for(let i = 0 ; i < e.num; i++){
							let copyitems = JSON.parse(JSON.stringify(e));
							copyitems.fatherIndex = index;
							e.childrenIndex = indexs;
							copyitems.fatherIndexggidarr = i;
							copyitems.popupCheck = false;
							this.productSwitchList.push(copyitems)
						}
						this.productSwitchList[0].ggidArr.forEach((item,index) => {
							Object.values(this.productSwitchList[0].guigelist).forEach(items => {
								if(item == items.ggid){
									this.productSwitchList[index].ks = items.ks;
									this.productSwitchList[index].ggname = items.ggname;
									this.productSwitchList[index].ggselected = items.ks.split(",")
								} 
							})
						})
						this.productSwitchList[0].popupCheck = true;
						this.customShopRes = this.productSwitchList[0];
						this.nowguige = this.customShopRes.guigelist[this.productSwitchList[0].ks];		
					}else{
						this.customShopRes = e;
						this.nowguige = this.customShopRes.guigelist[e.ks];
					}
					setTimeout(() => {
						this.$refs.popup.open();
					},300)
				}
			},
			//定制选择规格
			ggchange: function (e){
				var idx = e.currentTarget.dataset.idx;
				var itemk = e.currentTarget.dataset.itemk;
				var ggselected = JSON.parse(JSON.stringify(this.customShopRes.ggselected));
				ggselected[itemk] = idx;
				var ks = ggselected.join(',');
				if(this.customShopRes.not_selected.includes(ks)){
					app.error('暂不支持该规格组合，可变更其他规格下单');
					return false;
				}
				this.customShopRes.ggselected = ggselected;
				this.customShopRes.ks = ks;
				this.nowguige = this.customShopRes.guigelist[this.customShopRes.ks];
				this.customShopRes.ggid = this.nowguige.ggid;
				this.customShopRes.ggidArr[this.customShopRes.fatherIndexggidarr] = this.nowguige.ggid;
				this.customShopRes.ggname = this.nowguige.ggname;
				this.datalist[this.customShopRes.fatherIndex].prolist[this.customShopRes.childrenIndex].ggid = this.nowguige.ggid;
				this.datalist[this.customShopRes.fatherIndex].prolist[this.customShopRes.childrenIndex].ggidArr[this.customShopRes.fatherIndexggidarr]= this.nowguige.ggid;
				this.selectquantities(false);
			},
			popupClose(){
				this.$refs.popup.close();
			},
			customggChange(item,index){
				this.addcartList[item.fatherIndexs].ggid = item.ggid;
				this.addcartList[item.fatherIndexs].ggidArr[item.fatherIndex] = item.ggid;
				this.addcartList[item.fatherIndexs].guigelist.forEach((items,indexs) => {
					if(this.addcartList[item.fatherIndexs].ggid == items.ggid){
						items.isCheck = true;
					}else{
						items.isCheck = false;
					}
				})
				this.datalist[item.fatherIndex].prolist.forEach((items,indexs) => {
					if(items.proid == this.addcartList[item.fatherIndexs].proid){
						items.ggid = item.ggid;
						items.ggidArr[item.fatherIndexggidarr] = item.ggid;
					}
				})
			},
			//定制
			productCustom(item,index){
				if(item.guigelist && item.guigedata.length > 1){
					this.productSwitchList = [];
					this.customShopRes = item;
					this.nowguige = this.customShopRes.guigelist[item.ks];
					setTimeout(() => {
						this.$refs.popup.open();
					},300)
				}
			},
			addCart(){
				var that = this;
				that.loading = true;
				app.post('ApiRestaurantShop/addcart', {
					proid: that.shopid,
					package_data:that.package_data,
					add_price:that.add_price,
					num:'1',
					tableid:that.tableId
				}, function(res) {
					that.loading = false;
					if (res.status == 1) {
						app.success(res.msg);
						setTimeout(() => {
							app.goback();
						},500)
					} else {
						app.error(res.msg);
					}
				});
			},
			tispChange(){
				app.error('请选择商品')
			},
			getdata: function() {
				var that = this;
				that.loading = true;
				app.get('ApiRestaurantShop/getPackageData', {id:that.shopid}, function(res) {
					that.loading = false;
					that.business = res.business
					let dataList = JSON.parse(JSON.stringify(res.data));
					that.tacanSellPoint = res.product.sellpoint || '';
					dataList.forEach(item => {
						if(item.type == '0'){
							item.prolist.map(items => items.checked = true);
							that.addcartList.push(...item.prolist)
						}else if(item.type == 1){
							item.prolist.map(items => items.checked = false);
							item.prolist[0].checked = true;
							that.addcartList.push(item.prolist[0])
						}else{
							item.prolist.map(items => {
								items.checked = false;
								items.num = 0;
							});
							item.prolist[0].checked = true;
							item.prolist[0].num = item.selectnum;
							item.nowselectnum = item.selectnum;
							for(let i = 0 ; i < item.selectnum; i++){
								that.addcartList.push(item.prolist[0])
							}
						}
						// 定制商品初始值
						item.prolist.forEach(items => {
							items.ggidArr = [];
							if(items.guigelist){//是否是定制商品
								for(let i = 0 ; i < items.num ; i++){ //通过选中的数量循环
								items.ggidArr[i] = Object.values(items.guigelist)[0].ggid;//一个商品有几个数量，就会有几个规格
								let guigedata = items.guigedata;
								let ggselected = [];
								guigedata.forEach(itemsss => {
										ggselected.push(0);
								})
								items.ks = ggselected.join(',');
								items.ggselected = ggselected;
								}
							}
						})
					})
					that.addcartListLength = that.addcartList.length;
					that.datalist = dataList;
					that.cartList = res.cartList;
					that.numtotal = res.numtotal;
					that.numCat = res.numCat;
					that.sysset = res.sysset;
					that.table = res.table;
					that.selectquantities(false)
					that.$nextTick(() => {
						that.footcompute();
					})
					//计算每个高度
					var harr = [];
					var clientwidth = uni.getWindowInfo().windowWidth;
					var datalist = res.data;
					if (datalist && datalist.length > 0) {
						for (var i = 0; i < datalist.length; i++) {
							var child = datalist[i].prolist;
							harr.push(Math.ceil(child.length) * 200 / 750 * clientwidth);
						}
						that.harr = harr;
					} else {
						that.nodata = true;
					}
					that.loaded();
				});
			},
			selectProduct(indexs,item,items){
				let that = this;
				if(item.type == 0 || item.type == 2) return;
				if(item.type == 1){
					that.datalist[indexs].prolist.map(item => {
						if(item.ggid == 0){
							item.checked = item.proid == items.proid ? true:false;
						}else{
							item.checked = item.ggid == items.ggid ? true:false;
						}
					});
					that.selectquantities(false)
				}
			},
			choiceMinusPlus(indexs,item,items,type){
				let that = this;
				that.datalist[indexs].prolist.map(item => {
					if(type == 'Minus'){
						item.ggid == items.ggid ? item.num--:'';
						if(item.num && item.guigelist){
							item.ggidArr = [];
							for(let i = 0 ; i < item.num ; i++){
								if(item.guigelist){
									item.ggidArr[i] = Object.values(item.guigelist)[0].ggid;
									let guigedata = item.guigedata;
									let ggselected = [];
									guigedata.forEach(itemsss => {
											ggselected.push(0);
									})
									item.ks = ggselected.join(',');
									item.ggselected = ggselected;
								}
							}
						}
					}else{
						item.ggid == items.ggid ? item.num++:'';
						if(item.num && item.guigelist){
							item.ggidArr = [];
							for(let i = 0 ; i < item.num ; i++){
								if(item.guigelist){
									item.ggidArr[i] = Object.values(item.guigelist)[0].ggid;
									let guigedata = item.guigedata;
									let ggselected = [];
									guigedata.forEach(itemsss => {
											ggselected.push(0);
									})
									item.ks = ggselected.join(',');
									item.ggselected = ggselected;
								}
							}
						}
					}
					item.checked = item.num ? true:false;
				});
				that.datalist[indexs].nowselectnum = that.datalist[indexs].prolist.reduce((acc,curr) => acc+curr.num,0); //当前规格下选择数量
				that.selectquantities(false)
			},
			// 共用方法,当前选择的总数量，总加价价格
			selectquantities(type){
				let that = this;
				let arr = [];
				let arr2 = [];
				that.datalist.forEach((item,index) => {
					if(item.type == 0){
						item.prolist.forEach((items,indexs) => {
								if(items.checked){
									if(items.guigelist){
										items.ggid = items.ggidArr[0];
									}
									items.fatherIndex = index;
									items.childrenIndex = indexs;
									arr.push(items);
									arr2.push(items);
								}
						})
					}
					if(item.type == 1){
						item.prolist.forEach((items,indexs) => {
								if(items.checked){
									// 判断是否为定制商品
									if(items.guigelist){
										for(let i = 0 ; i < items.num ; i++){
											items.ggid = items.ggidArr[i];
											arr.push(items);
										}
									}else{
										arr.push(items);
									}
									items.fatherIndex = index;
									items.childrenIndex = indexs;
									items.fatherIndexggidarr = 0;
									arr2.push(items);
								}
						})
					}
					if(item.type == 2){
						let ggidArr = [];
						let ggnameArr = [];
						let ksArr = [];
						let ggselectedArr = [];
						item.prolist.forEach((items,indexs) => {
							if(items.num > 0){
								if(items.guigelist){
									items.ggidArr.forEach(ggidtext => {
										Object.values(items.guigelist).forEach(ggidtexts => {
											if(ggidtext == ggidtexts.ggid){
												ggnameArr.push(ggidtexts.ggname);
												ksArr.push(ggidtexts.ks);
												ggselectedArr.push(ggidtexts.ks.split(","));
											}
										})
									})
									for(let i = 0 ; i < items.num ; i++){
										items.ggid = items.ggidArr[i];
										items.ggname = ggnameArr[i];
										items.ks = ksArr[i];
										items.ggselected = ggselectedArr[i];
										let copyitems = JSON.parse(JSON.stringify(items));
										copyitems.num = 1;
										arr.push(copyitems);
									}
								}else{
									arr.push(items);
								}
								for(let i = 0 ; i < items.num; i++){
									let copyitems = JSON.parse(JSON.stringify(items));
									copyitems.ggid = items.ggidArr[i];
									copyitems.ggname = ggnameArr[i];
									copyitems.ks = ksArr[i];
									copyitems.ggselected = ggselectedArr[i];
									copyitems.fatherIndex = index;
									items.childrenIndex = indexs;
									copyitems.fatherIndexggidarr = i;
									arr2.push(copyitems)
								}
							}
						})
					}
				});
				that.addcartList = arr2;
				that.add_price = arr.reduce((acc,curr) => Number(acc) + Number(curr.add_price),0);
				that.package_data =JSON.parse(JSON.stringify(arr));
				type ? that.addCart() :'';
			},
			footcompute(){
				let that = this;
				uni.createSelectorQuery().select('.footer').boundingClientRect(res =>{
					that.footerheight = res.height + 20;
				}).exec();
			}
		}
	};
</script>
<style>
	page {position: relative;width: 100%;height: 100%;background: #fff;}
	.container {height: 100vh;width:100%;position: relative;}
	.scroll-class{height: 100%;width: 100%;}
	.container .options-view{width: 95%;margin: 30rpx auto;}
	.options-view .option-title{width: 100%;font-size: 28rpx;position: relative;}
	.option-title .text-name{font-weight: bold;color: black;position: relative;margin-left: 15rpx;}
	.options-view .option-title::after{content: "";width: 6rpx;height: 64%;background: black;position: absolute;left: 0;top: 22%;border-radius: 10px;}
	.options-view .shop-list{width: 100%;align-items: center;flex-wrap: wrap;margin: 30rpx 0rpx;justify-content: flex-start;}
	.shop-list .options-shop{width: 30%;height:305rpx;overflow: hidden;background: #f7f7f7;margin-bottom: 20rpx;border-radius: 10rpx;border: 3rpx solid;margin: 0rpx 5px;margin-bottom: 15rpx;position: relative;}
	.options-shop .shop-image{width: 100%;height:180rpx;background: #fff;}
	.options-shop .shop-image image{width: 100%;height:180rpx;overflow: hidden;border-radius: 2rpx;}
	.options-shop .shop-info-view{width: 100%;height:110rpx;position: relative;margin-top: 5rpx;overflow: hidden;}
	.shop-info-view .shop-name{text-align: center;font-size:22rpx;color: #848385;white-space: nowrap;text-overflow: ellipsis;overflow: hidden;}
	.shop-info-view .gg-name{text-align: center;font-size:22rpx;color: #848385;}
	.gg-name-but{border: 1px solid;border-radius:36rpx;text-align: center;font-size: 24rpx;width: 76%;color: #fff;
	position: absolute;top: 60rpx;left:50%;padding: 16rpx 0rpx;transform: translateX(-50%);}
	.jia-price{font-size: 24rpx;font-weight: bold;color: orangered;position: absolute;left: 5rpx;bottom: 0rpx;}
	.choose-view{width: 50rpx;height: 40rpx;position: absolute;right: -5rpx;bottom: -5rpx; display: flex;align-items: center;justify-content: center;
	border-radius: 28rpx 0rpx 0rpx 0rpx;}
	.choose-view image{width: 65%;height: 75%;}
	.addnum {width: 100%;position: absolute;right:0rpx;bottom: 3rpx;font-size: 28rpx;color: #666;display: flex;align-items: center;justify-content:flex-end;padding:0rpx 8rpx;}
	.addnum .plus {width: 45rpx;height: 45rpx;background: #FD4A46;color: #FFFFFF;	border-radius: 50%;display: flex;align-items: center;justify-content: center;font-size: 28rpx}
	.addnum .no{background: #dddddd !important;}
	.addnum .minus {width: 45rpx;height: 45rpx;background: #FFFFFF;	color: #FD4A46;	border: 1px solid #FD4A46;border-radius: 50%;display: flex;align-items: center;
		justify-content: center;font-size: 28rpx}
	.addnum .i {padding: 0 20rpx;color: #999999;font-size: 28rpx}
	.occupy-box{width: 100%;height: calc(env(safe-area-inset-bottom));}

	.footer {width: 96%;position: fixed;left: 50%;z-index: 8;display: flex;align-items: center;transform: translate(-50%);
	margin-bottom: env(safe-area-inset-bottom);bottom: 0;border-radius: 30rpx 30rpx 15rpx 15rpx;overflow: hidden;background-color: rgba(0,0,0,.8);}
	.select-display{width: 100%;padding: 20rpx 35rpx;display: flex;align-items: center;justify-content: flex-start;flex-wrap: wrap;margin-bottom: 20rpx;}
	.scrollview-class{max-height: 290rpx;height: auto;}
	.select-display .product-pit-view{width: 80rpx;height: 80rpx;border-radius: 50%;margin: 10rpx 15rpx;border: 4rpx solid;position: relative;}
	.select-display .select-shop-image{width: 100%;height: 100%;overflow: hidden;border-radius: 50%;}
	.select-display .select-shop-image image{width: 100%;height: 100%;}
	.select-display .num-view{width: 60rpx;font-size: 24rpx;color: #fff;border-radius: 20rpx;position: absolute;bottom: -15rpx;
	text-align: center;left: 50%;transform: translate(-50%);height: 30rpx;line-height: 30rpx;}
	.addcart-view{width: 100%;height: 100rpx;line-height: 100rpx;border-radius: 15rpx 15rpx 0rpx 0rpx;text-align: center;color: #fff;font-size: 32rpx;font-weight: bold;}
	.please-select{background-color: #a1a1a1 !important;}
	
	.uni-popup__wrapper-box{background: #f7f8fa;border-radius: 20rpx 20rpx 0rpx 0rpx;overflow: hidden;}
	.hotelpopup__content{width: 100%;height:auto;position: relative;background: #fff;}
	.hotelpopup__content .popup-close{position: fixed;right: 20rpx;top: 20rpx;width: 56rpx;height: 56rpx;z-index: 11;}
	.hotelpopup__content .popup-close image{width: 100%;height: 100%;}
	.hotelpopup__content .popup-but-view{width: 100%;position: sticky;bottom: 0rpx;padding: 0rpx 40rpx 20rpx;}
	.hotelpopup__content .popup-but-view .page_content{	font-size: 24rpx;color: #888888;font-weight: normal;padding: 20rpx 0rpx;}
	.hotelpopup__content .popup-but-view .but-class{width: 100%;padding: 22rpx;text-align: center;color: #FFFFFF;font-size: 32rpx;font-weight: bold;border-radius: 20rpx;background: linear-gradient(90deg, #06D470 0%, #06D4B9 100%); }
	.hotelpopup__content .popup-but-view .price-statistics{padding-bottom: 15rpx;}
	.hotelpopup__content .popup-but-view .price-statistics .title-text{font-size: 24rpx;}
	.hotelpopup__content .popup-but-view .price-statistics .price-text{padding: 0rpx 10rpx;align-items: center;}
	.custom-image-view{padding: 0rpx 0rpx 10rpx;}
	.custom-image-view image{width: 100%;overflow: hidden;}
	.customproname-view{padding: 10rpx 40rpx 20rpx;font-size: 32rpx;font-weight: bold;}
	.gglist-view{padding: 0rpx 40rpx;margin-bottom: 20rpx;}
	.gglist-view .gglist-view-options{margin-bottom: 30rpx;padding:0rpx 15rpx;}
	.gglist-view .gglist-view-options .name{font-size: 28rpx;color: #333;font-weight: bold;margin-bottom: 20rpx;}
	.gglist-view .gglist-view-options .item{ font-size: 30rpx;color: #333;flex-wrap:wrap}
	.gglist-view .gglist-view-options .item2{ height:60rpx;line-height:60rpx;margin-bottom:4px;border:0; border-radius:100rpx; padding:0 40rpx;color:#666666; 
	margin-right: 10rpx; font-size:26rpx;border: 1px solid #eee;}
	.selectproduct-list{width: 100%;padding: 20rpx 30rpx;}
	.selectproduct-list .options-list{height:60rpx;line-height:60rpx;margin-bottom:4px;border:0; border-radius:100rpx; padding:0 40rpx;color:#666666; 
	margin-right: 10rpx; font-size:26rpx;border: 1px solid #eee;display: inline-block;}
</style>
