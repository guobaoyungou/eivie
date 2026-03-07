<template>
<view class="dp-tab" :style="{
	margin:params.margin_y*2.2+'rpx '+params.margin_x*2.2+'rpx 0',
	borderRadius:params.borderradius*2.2+'rpx'
}">
	<view v-if="needfixed==1" style="width:100%;height:90rpx;"></view>
	<view class="dsn-tab-box" :class="needfixed==1 ? ((homeNavigationCustom > 1 && currentPage == 'pages/index/index') ? 'fixed-custom':'fixed'):''" :style="{background:params.bgcolor,padding:params.padding_y*2.2+'rpx '+params.padding_x*2.2+'rpx',Top:(homeNavigationCustom > 1 && currentPage == 'pages/index/index' && navigationHieght > 0) ? navigationHieght+'px' :''}">
	<scroll-view scroll-x="true" style="overflow: visible !important">
		<view class="dsn-tab-box-content">
			<view v-for="(item,index) in data" :key="index" class="dp-tab-item" @tap="changetab" :data-index="index" :style="{fontSize:params.fontsize1*2+'rpx',color:(tabindex==index?params.color2:params.color1)}">{{item.name}}<view class="dp-tab-item-after" :style="{background:params.arrowcolor}" v-if="params.arrowshow==1 && tabindex==index"></view></view>
		</view>
	</scroll-view>
	</view>
	<view class="dp-tab-content" :style="{background:params.bgcolor2,padding:params.padding_y*2.2+'rpx '+params.padding_x*2.2+'rpx'}">
		<block v-for="(setData, index) in pagecontent" :key="index">
			<block v-if="setData.temp=='notice'">
				<dp-notice :params="setData.params" :data="setData.data"></dp-notice>
			</block>
			<block v-if="setData.temp=='banner'">
				<dp-banner :params="setData.params" :data="setData.data"></dp-banner> 
			</block>
			<block v-if="setData.temp=='search'">
				<dp-search :params="setData.params" :data="setData.data"></dp-search>
			</block>
			<block v-if="setData.temp=='text'">
				<dp-text :params="setData.params" :data="setData.data"></dp-text>
			</block>
			<block v-if="setData.temp=='title'">
				<dp-title :params="setData.params" :data="setData.data"></dp-title>
			</block>
			<block v-if="setData.temp=='dhlist'">
				<dp-dhlist :params="setData.params" :data="setData.data"></dp-dhlist>
			</block>
			<block v-if="setData.temp=='line'">
				<dp-line :params="setData.params" :data="setData.data"></dp-line>
			</block>
			<block v-if="setData.temp=='blank'">
				<dp-blank :params="setData.params" :data="setData.data"></dp-blank>
			</block>
			<block v-if="setData.temp=='menu'">
				<dp-menu :params="setData.params" :data="setData.data"></dp-menu> 
			</block>
			<block v-if="setData.temp=='map'">
				<dp-map :params="setData.params" :data="setData.data"></dp-map> 
			</block>
			<block v-if="setData.temp=='cube'">
				<dp-cube :params="setData.params" :data="setData.data" :tabcub="params"></dp-cube> 
			</block>
			<block v-if="setData.temp=='picture'">
				<dp-picture :params="setData.params" :data="setData.data"></dp-picture> 
			</block>
			<block v-if="setData.temp=='pictures'"> 
				<dp-pictures :params="setData.params" :data="setData.data"></dp-pictures> 
			</block>
			<block v-if="setData.temp=='video'">
				<dp-video :params="setData.params" :data="setData.data"></dp-video> 
			</block>
			<block v-if="setData.temp=='tab'">
				<dp-tab :params="setData.params" :data="setData.data" :tabid="setData.id" :menuindex="menuindex"></dp-tab> 
			</block>
			<block v-if="setData.temp=='shop'">
				<dp-shop :params="setData.params" :data="setData.data" :shopinfo="setData.shopinfo"></dp-shop> 
			</block>
			<block v-if="setData.temp=='product'">
				<dp-product :params="setData.params" :data="setData.data" :menuindex="menuindex"></dp-product> 
			</block>
			<block v-if="setData.temp=='collage'">
				<dp-collage :params="setData.params" :data="setData.data" :menuindex="menuindex"></dp-collage> 
			</block>
			<block v-if="setData.temp=='luckycollage'">
				<dp-luckycollage :params="setData.params" :data="setData.data"></dp-luckycollage> 
			</block>

			<block v-if="setData.temp=='kanjia'">
				<dp-kanjia :params="setData.params" :data="setData.data" :menuindex="menuindex"></dp-kanjia> 
			</block>
			<block v-if="setData.temp=='yuyue'">
				<dp-yuyue :params="setData.params" :data="setData.data" :menuindex="menuindex"></dp-yuyue>
			</block>
			<block v-if="setData.temp=='seckill'">
				<dp-seckill :params="setData.params" :data="setData.data" :menuindex="menuindex"></dp-seckill> 
			</block>
			<block v-if="setData.temp=='scoreshop'">
				<dp-scoreshop :params="setData.params" :data="setData.data" :menuindex="menuindex"></dp-scoreshop> 
			</block>
			<block v-if="setData.temp=='tuangou'">
				<dp-tuangou :params="setData.params" :data="setData.data" :menuindex="menuindex"></dp-tuangou> 
			</block>
			<block v-if="setData.temp=='kecheng'">
				<dp-kecheng :params="setData.params" :data="setData.data" :menuindex="menuindex"></dp-kecheng> 
			</block>

			<block v-if="setData.temp=='coupon'">
				<dp-coupon :params="setData.params" :data="setData.data"></dp-coupon> 
			</block>
			<block v-if="setData.temp=='article'">
				<dp-article :params="setData.params" :data="setData.data"></dp-article> 
			</block>
			<block v-if="setData.temp=='business'">
				<dp-business :params="setData.params" :data="setData.data" :menuindex="menuindex"></dp-business> 
			</block>

			<block v-if="setData.temp=='button'">
				<dp-button :params="setData.params" :data="setData.data"></dp-button> 
			</block>
			<block v-if="setData.temp=='hotspot'">
				<dp-hotspot :params="setData.params" :data="setData.data"></dp-hotspot> 
			</block>
			<block v-if="setData.temp=='cover'">
				<dp-cover :params="setData.params" :data="setData.data"></dp-cover> 
			</block>
			<block v-if="setData.temp=='richtext'">
				<dp-richtext :params="setData.params" :data="setData.data" :content="setData.content" :richtype="richtype" :richurl="richurl"></dp-richtext>
			</block>
			<block v-if="setData.temp=='form'">
				<dp-form :params="setData.params" :data="setData.data" :content="setData.content"></dp-form> 
			</block>

			<block v-if="setData.temp=='userinfo'">
				<dp-userinfo :params="setData.params" :data="setData.data" :content="setData.content"></dp-userinfo> 
			</block>
			<block v-if="setData.temp=='wxad'">
				<dp-wxad :params="setData.params" :data="setData.data"></dp-wxad> 
			</block>

			<block v-if="setData.temp=='photo_generation'">
				<dp-photo-generation :params="setData.params" :data="setData.data" :menuindex="menuindex"></dp-photo-generation>
			</block>
			<block v-if="setData.temp=='video_generation'">
				<dp-video-generation :params="setData.params" :data="setData.data" :menuindex="menuindex"></dp-video-generation>
			</block>
		</block>
	</view>
	<loading v-if="loading"></loading>
</view>
</template>
<script>
	var app = getApp();
	export default {
		data(){
			return {
				loading:false,
				tabindex:0,
				pagecontent:[],
				latitude:'',
				longitude:'',
				tabtop:0,
				needfixed:0,
				homeNavigationCustom: app.globalData.homeNavigationCustom,
				currentPage:'',
			}
    },
		props: {
			params:{},
			data:{},
			tabid:{default:''},
			menuindex:{default:-1},
      richtype:{default:0},
      richurl:{default:''},
			navigationHieght:{default:0},
		},
		mounted:function(){
			var that = this;
			var pages = getCurrentPages();
			that.currentPage = pages[pages.length - 1].route; //获取当前页面的对象
			this.changetab({currentTarget:{dataset:{index:0}}});
			if(this.params.fixedtop == 1){
				setTimeout(function(){
					let view0 = uni.createSelectorQuery().in(that).select('.dsn-tab-box')
					view0.fields({
						size: false,//是否返回节点尺寸（width height）
						rect: true,//是否返回节点的 scrollLeft scrollTop，节点必须是 scroll-view 或者 viewport
						scrollOffset: false,//是否返回节点的 scrollLeft scrollTop，节点必须是 scroll-view 或者 viewport
					}, (res) => {
						if(app.globalData.homeNavigationCustom > 1 && that.currentPage == 'pages/index/index'){
							that.tabtop = res.top - 91;
						}else{
							that.tabtop = res.top;
						}
					}).exec();
				},100);
				uni.$on('onPageScroll',function(data){
					that.scrollTop = data.scrollTop;
					if(data.scrollTop > 0 && data.scrollTop >= that.tabtop){
						//console.log('------');
						that.needfixed = 1;
					}else{
						that.needfixed = 0;
					}
				});
			}
		},
		methods:{
			changetab:function(e){
				var that = this
				var idx = e.currentTarget.dataset.index;
				that.tabindex = idx;
				//console.log(that.data[idx].id)
				//console.log(that.tabid)
				that.getdata();
			},
			getdata:function(){
				var that = this;
				that.loading = true;
                this.pagecontent = [];
				app.post('ApiIndex/gettabcontent', {tabid: that.tabid,tabindexid:that.data[that.tabindex].id,latitude:that.latitude,longitude:that.longitude}, function (res) {
					that.loading = false;
					that.pagecontent = res.pagecontent;
					if(that.latitude=='' && that.longitude=='' && res.needlocation){
						//console.log('getLocation')
						app.getLocation(function (res) {
							//console.log(res)
							that.latitude = res.latitude;
							that.longitude = res.longitude;
							that.getdata();
						},function(res2){
							console.log(res2)
						});
					}
				})
			},
			getIndexdata:function(){
					this.$emit('getdata');
			},
		}
	}
</script>
<style>
.dp-tab {height:auto;position:relative;background: rgb(255,255,255,0);overflow:hidden}
.dsn-tab-box{display:flex;width:100%;height:90rpx;background: #fff;}
.dsn-tab-box.fixed{position:fixed;top:0;left:0;z-index:9;}
.dsn-tab-box.fixed-custom{position:fixed;top:89px;z-index:100;}
.dsn-tab-box-content{flex-grow: 0;flex-shrink: 0;display:flex;align-items:center;flex-wrap:nowrap;position:relative;}
.dp-tab-item{flex-grow:1;padding:0 20rpx;flex-shrink: 0;font-size:28rpx; text-align:center; color:#666; height: 90rpx; line-height: 90rpx;overflow: hidden;position:relative}
.dp-tab-item-after{position:absolute;left:50%;margin-left:-24rpx;bottom:10rpx;height:3px;border-radius:1.5px;width:48rpx}
.dp-tab-content{min-height:100rpx}
</style>