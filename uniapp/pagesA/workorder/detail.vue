<template>
<view>
	<block v-if="isload">
		<view class="detail">
			<parse :content="detail.content"></parse>
			
		</view>
		<view class="itembox">
				<view class="title"> 工单列表</view>
				<block v-if="detail.pid>0 || (detail.pid==0 && detail.id==id && ispcate==1)">
				<view class="item" v-for="(item,index) in datalist" @tap="goto" :data-url="'/pagesB/workorder/index?cateid='+item.id">
						<text>{{item.name}}</text>
						<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
				</view>
				</block>
				<block v-else>
					<view class="category" v-for="(item,index2) in datalist" >
						<view class="cate">		
							<view class="img" @tap="changeCTab" :data-id="item.id" :data-index="index2" >
								<image :src="pre_url+'/static/img/workorder/'+(curIndex2==index2?'down':'up')+'.png?v1'" mode="widthFix"/></view>
								<view>{{item.name}}</view>
						</view>
						<view class="item" v-if="curIndex2==index2" v-for="(subitem,subindex) in item.list" style="padding-left:50rpx;margin-top: 10rpx;"  @tap="goto" :data-url="'/pagesB/workorder/index?cateid='+subitem.id">
								<text>{{subitem.name}}</text>
								<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
						</view>
					</view>
				</block>
		</view>

	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
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
			detail:[],
			datalist:[],
			curIndex2:0,
			id:0,
			ispcate:0
		}
	},
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
	
		this.getdata();
  },
	methods: {
		getdata:function(){
			var that = this;
			var id = this.opt.id || 0;
			that.id = id;
			var bid = this.opt.bid || 0;
			that.loading = true;
			app.post('ApiWorkorder/getdetail', {id: id,bid:bid}, function (res) {
				that.loading = false;
				if (res.status == 0) {
					app.alert(res.msg);
					return;
				}
				var detail = res.data;
				that.detail = detail
				that.ispcate = res.ispcate
				that.datalist = res.datalist
				uni.setNavigationBarTitle({
					title: detail.name
				});
				
				that.loaded({title:detail.name,pic:detail.pic});
			});
		},
		//改变子分类
		changeCTab: function (e) {
			var that = this;
			var id = e.currentTarget.dataset.id;
			var index = parseInt(e.currentTarget.dataset.index);
			console.log(index)
			this.curIndex2 = index;
			this.curCid = id;
		},
	}
}
</script>
<style>
.detail{  background: #fff; padding: 30rpx;}
.itembox{ margin-top: 30rpx; background: #fff; padding: 30rpx; }
.itembox .title{ color: #000; font-weight: bold; font-size: 32rpx;}
.itembox .item{  margin-top: 30rpx; justify-content: space-between; display: flex;}

.itembox .category .cate{ display: flex;align-items: center;margin-top: 20rpx; font-weight:bold}
.itembox .category .img{ display: flex; align-items: center;}
.itembox .category .img image{ width: 50rpx; height: 50rpx;}

</style>