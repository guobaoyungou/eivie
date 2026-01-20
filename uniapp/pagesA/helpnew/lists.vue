<template>
	<view>
		<view class="content-view">
			<view class="banner-view">
				<image :src="pic" mode="widthFix"></image>
			</view>
			<view class="bottom-view flex-col">
				<view class="search-view">
					<image class="search-image" :src="pre_url+'/static/img/search_ico.png'"></image>
					<input type="text" placeholder="搜索问题相关关键词..."  @confirm="searchConfirm" placeholder-style="font-size:28rpx;color: rgba(130, 130, 167, 0.8);" />
				</view>
				<view class="list-view">
					<view class="shaixuan-view">
						<view @click="changeSelect" :class="[selectType ? 'xial-view-active-no':'xial-view-active','xial-view']">
							<image :src="pre_url+'/static/img/xiangshang.png'"></image>
						</view>
						
						<scroll-view scroll-x style="white-space: nowrap;width: 90%;height: 100%;margin-left: 20rpx;">
							<view :class="[cid == 0 ? 'wenti-title-active':'','wenti-title']" @click="changeWenti(0)">全部问题</view>
							<block v-for="(item,index) in clist">
								<view :class="[item.id == cid ? 'wenti-title-active':'','wenti-title']" @click="changeWenti(item.id)">{{item.name}}</view>
							</block>
						</scroll-view>
						<view :class="[selectType ? 'search-view-active':'search-view-active-no','select-view']">
							<scroll-view scroll-y style="width: 100%;padding: 10rpx 20rpx;height: 400rpx;">
								<block v-for="(item,index) in cidlist">
									<view :class="[item.id == child_id ? 'select-view-wenti-active':'','select-view-wenti']" @click="changeChild(item.id)">{{item.name}}</view>
								</block>
							</scroll-view>
						</view>
					</view>
					<view class="option-list-view">
						<scroll-view scroll-y style="width: 100%;height: 60vh;padding-bottom: 100rpx;">
							<block v-for="(item,index) in datalist">
								<view class="list-option" @click="goto" :data-url="'/pagesA/helpnew/detail?id='+item.id">
									<view class="list-text-view">
										<view class="text-view">{{item.name}}</view>
										<view v-if="item.is_hot==1" class="hot-view">热</view>
									</view>
									<view class="left-icon">
										<image :src="pre_url+'/static/img/arrowright.png'"></image>
									</view>
								</view>
							</block>
							<nodata v-if="nodata"></nodata>
							<nomore v-if="nomore"></nomore>
						</scroll-view>
						
					</view>
					
				</view>
			</view>
		</view>
	</view>
</template>

<script>
	var app = getApp();
	export default{
		data(){
			return{
				navigationMenu:{},
				statusBarHeight: 20,
				platform: app.globalData.platform,
				pre_url:app.globalData.pre_url,
				selectType:false,
				opt:{},
				loading:false,
				isload: false,
				menuindex:-1,
	
				nodata:false,
				nomore:false,
				keyword:'',
				datalist: [],
				pagenum: 1,
				clist:[],
				cnamelist:[],
				cidlist:[],
				datalist: [],
				cid: 0,
				bid: 0,
				child_id:0,
				listtype:0,
				set:'',
				look_type:false,
				pic:''
			}
		},
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.getdata();
		},
		onPullDownRefresh:function () {
			this.getdata();
		},
		onReachBottom: function () {
		  if (!this.nomore && !this.nodata) {
		    this.pagenum = this.pagenum + 1;
		    this.getdata(true);
		  }
		},
		methods:{
			getdata: function (loadmore) {
					if(!loadmore){
						this.pagenum = 1;
						this.datalist = [];
					}
			  var that = this;
			  var pagenum = that.pagenum;
			  var keyword = that.keyword;
			  var cid = that.cid;
			  var child_id = that.child_id;
					console.log(cid)
					that.loading = true;
					that.nodata = false;
					that.nomore = false;
			  app.post('ApiHelpnew/gethelplist', {bid:that.bid,cid: cid,child_id:child_id,pagenum: pagenum,keyword:keyword}, function (res) {
				that.loading = false;
			    var data = res.data;
			    if (pagenum == 1) {
					that.listtype = res.listtype || 0;
					that.clist    = res.clist;
					that.set      = res.set;
					that.cidlist = res.cidlist;
	
					uni.setNavigationBarTitle({
						title: res.title
					});
					that.datalist = data;
					if (data.length == 0) {
						that.nodata = true;
					}
					that.pic = res.pic;
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
			searchConfirm: function (e) {
				var that = this;
				var keyword = e.detail.value;
				that.keyword = keyword
				that.getdata();
			},
			
			changeWenti(cid){
				this.cid = cid;
				this.child_id = 0;
				this.selectType = false;
				this.getdata();
			},
			changeChild(cid){
				this.child_id = cid;
				this.selectType = false;
				this.getdata();
			},
			changeSelect(){
				this.selectType = !this.selectType;
			}
		}
	}
</script>

<style>
	.content-view{width: 100%;position: relative;}
	.content-view .banner-view{width: 100%;height: 50vh;}
	.banner-view image{width: 100%;}
	.bottom-view{width: 100%;position: absolute;top:25vh;}
	.bottom-view .search-image{width: 35rpx;height: 35rpx;margin: 0 40rpx;}
	.bottom-view .search-view{display: flex;align-items: center;justify-content: flex-start;width: 95%;margin: 0 auto;background: #fff;height: 100rpx;line-height: 100rpx;border-radius: 30px;}
	.bottom-view .search-view input{width: 75%;height: 100%;}
	.bottom-view .list-view{width: 100%;height: auto;margin-top: 40rpx;background: linear-gradient(180deg, #e1e4ec 0%, #e8eaef 100%);border-radius: 24px 24px 0px 0px;}
	.list-view .shaixuan-view .xial-view{width: 50rpx;height: 50rpx;display: flex;align-items: center;justify-content: center;transform: rotate(180deg);}
	.list-view .shaixuan-view .xial-view-active{transform: rotate(90deg);transition: all .3s;}
	.list-view .shaixuan-view .xial-view-active-no{transform: rotate(180deg);transition: all .3s;}
	.list-view .shaixuan-view .xial-view image{width: 30rpx;height: 30rpx;}
	.list-view .shaixuan-view .select-view{width: 100%;height:0rpx;position: absolute;left: 0;z-index: 222;top: 97rpx;border-radius: 0rpx 0rpx 30rpx 30rpx;
	background: #f0f2f7;overflow: hidden;}
	.shaixuan-view .select-view .select-view-wenti{width: 100%;font-size: 28rpx;color: rgba(20, 20, 25, 0.3);padding: 8rpx 20rpx;position: relative;}
	.shaixuan-view .select-view .select-view-wenti-active{color: #333;font-weight: bold;}
	.shaixuan-view .select-view .select-view-wenti-active::after{content: " "; width: 10rpx;height: 30rpx;background: #F94B30;border-radius: 5rpx;position: absolute;left: 0%;
	top: 50%;transform: translateY(-50%);}
	.list-view .shaixuan-view .search-view-active{height: 400rpx !important;transition: all .4s;}
	.list-view .shaixuan-view .search-view-active-no{height: 0rpx !important;transition: all .2s;}
	.bottom-view .list-view .shaixuan-view{width: 93%;height:100rpx;margin:0 auto;display: flex;align-items: center;justify-content: flex-start;position: relative;}
	.bottom-view .list-view .shaixuan-view .wenti-title{height: 100rpx;line-height:100rpx;font-size: 28rpx;color: rgba(20, 20, 25, 0.3);display: inline-block;margin-right: 25rpx;
	position: relative;}
	.bottom-view .list-view .shaixuan-view .wenti-title-active{color: #333;font-weight: bold;}
	.bottom-view .list-view .shaixuan-view .wenti-title-active::after{content: " ";width: 52rpx;height: 8rpx;background: #F94B30;position: absolute;bottom: 12rpx;left: 50%;border-radius: 8rpx;
	transform: translate(-50%);}
	.bottom-view .list-view .option-list-view{width: 93%;margin:0 auto;margin-top: 30rpx;}
	.bottom-view .list-view .option-list-view .list-option{width: 100%;background: #FFFFFF;height: 88rpx;line-height: 88rpx;border-radius: 16rpx;margin-bottom: 20rpx;position: relative;
	display: flex;align-items: center;justify-content: space-between;padding: 0rpx 40rpx 0rpx;}
	.list-view .option-list-view .list-option::after{content: " ";width: 10rpx;height: 10rpx;border-radius:50%;background: #F94B30;position: absolute;left: 30rpx;top: 50%;transform: translateY(-50%);
	}
	.option-list-view .list-option .list-text-view{width: 88%;margin-left: 30rpx;display: flex;align-items: center;justify-content: flex-start;}
	.list-option .list-text-view .text-view{font-size: 28rpx;color: #141419;}
	.list-option .list-text-view  .hot-view{font-size: 20rpx;width: 30rpx;height: 30rpx;display: flex;align-items: center;justify-content: center;background: #F94B30;border-radius: 6rpx;color: #fff;
	margin-left: 20rpx;text-align: center;line-height: 30rpx;}
	.option-list-view .list-option .left-icon{width: 30rpx;height: 30rpx;display: flex;align-items: center;justify-content: center;}
	.option-list-view .list-option .left-icon image{width: 100%;height: 100%;}
	
	
	.navigation {width: 100%;padding-bottom:10px;overflow: hidden;position: fixed;top: 0;}
	.navcontent {display: flex;align-items: center;padding-left: 10px;}
	.header-location-top{position: relative;display: flex;justify-content: center;align-items: center;flex:1;}
	.header-back-but{position: absolute;left:12rpx;display: flex;align-items: center;width: 35rpx;height: 35rpx;overflow: hidden;}
	.header-back-but image{width: 17rpx;height: 31rpx;} 
	.header-page-title{display: flex;flex: 1;align-items: center;justify-content: center;font-size: 34rpx;letter-spacing: 2rpx;}
</style>