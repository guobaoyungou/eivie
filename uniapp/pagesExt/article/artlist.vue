<template>
<view class="container">
	<block v-if="isload">
		<view class="topsearch flex-y-center">
			<view class="f1 flex-y-center" v-if="!showlocation">
				<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				<input :value="keyword" :placeholder="set.search_placeholder ? set.search_placeholder : '搜索感兴趣的文章'" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
			</view>
			<view class="f1 areasearch" v-if="showlocation">
				<uni-data-picker :localdata="arealist" popup-title="所在地区" @change="areachange"  :placeholder="'所在地区'">
					<view class="area-picker">
						<text class="txt" :style="{color:area_name?'#222222':''}">{{area_name?area_name:'全部'}}</text>
						<image :src="pre_url+'/static/img/arrowdown.png'"></image>
					</view>
				</uni-data-picker>
				<view class="area-input">
					<input :value="keyword" placeholder="搜索" placeholder-style="font-size:24rpx;color:#C2C2C2" @confirm="searchConfirm"></input>
					<image class="img" :src="pre_url+'/static/img/search_ico.png'"></image>
				</view>
			</view>
		</view>
		<dd-tab :itemdata="cnamelist" :itemst="cidlist" :st="cid" :isfixed="false" @changetab="changetab" v-if="clist.length>0 && !look_type"></dd-tab>

		<view class="article_list">
			<!--横排-->
			<view v-if="listtype=='0'" class="article-itemlist" v-for="(item,index) in datalist" :key="item.id" @click="goto" :data-url="'detail?id='+item.id">
				<view class="article-pic">
					<image class="image" :src="item.pic" mode="widthFix"/>
				</view>
				<view class="article-info">
					<view class="p1" :style="set.title_size&&set.title_size>0?'font-size:'+set.title_size*2+'rpx':''">{{item.name}}</view>
					<view v-if="set.subtitle_status && item.subtitle" class="p3" :style="{color: '#333',fontSize: set.subtitle_size && set.subtitle_size > 0 ? set.subtitle_size * 2 + 'rpx' : ''}">
					  {{item.subtitle}}
					</view>
					<block v-if="item.po_status && item.po_status==1">
						<view class="p3" :style="set.title_size&&set.title_size>0?'font-size:'+set.title_size*2+'rpx':''" v-if="item.po_name">
							{{item.po_name}} {{item.po_content}}
						</view>
					</block>
					<block v-if="item.pt_status && item.pt_status==1">
						<view class="p3" :style="set.title_size&&set.title_size>0?'font-size:'+set.title_size*2+'rpx':''" v-if="item.pt_name">
							{{item.pt_name}} {{item.pt_content}}
						</view>
					</block>
					<block v-if="item.pth_status && item.pth_status==1">
						<view class="p3" :style="set.title_size&&set.title_size>0?'font-size:'+set.title_size*2+'rpx':''" v-if="item.pth_name">
							{{item.pth_name}} {{item.pth_content}}
						</view>
					</block>
					<block v-if="item.pf_status && item.pf_status==1">
						<view class="p3" :style="set.title_size&&set.title_size>0?'font-size:'+set.title_size*2+'rpx':''" v-if="item.pf_name">
							{{item.pf_name}} {{item.pf_content}}
						</view>
					</block>
					<view class="p2">
						<text style="overflow:hidden" class="flex1" v-if='show_time == 1'>{{item.createtime}}</text>
						<text style="overflow:hidden" v-if="show_readcount == 1">阅读 {{item.readcount}}</text>
					</view>
					<view class="p2" v-if="item.activity_status">
						<text style="overflow:hidden" v-if="item.activity_status == 1">未开始</text>
						<text style="overflow:hidden" v-if="item.activity_status == 2">进行中</text>
						<text style="overflow:hidden" v-if="item.activity_status == 3">已结束</text>
					</view>
				</view>
			</view>
			<!--双排-->
			<view v-if="listtype=='1'" class="article-item2" v-for="(item,index) in datalist" :key="item.id" :style="{marginRight:index%2==0?'2%':'0'}" @click="goto" :data-url="'detail?id='+item.id">
				<view class="article-pic">
					<image class="image" :src="item.pic" mode="widthFix"/>
				</view>
				<view class="article-info">
					<view class="p1" :style="set.title_size&&set.title_size>0?'font-size:'+set.title_size*2+'rpx':''">{{item.name}}</view>
					<view v-if="set.subtitle_status && item.subtitle" class="p3" :style="{color: '#333',fontSize: set.subtitle_size && set.subtitle_size > 0 ? set.subtitle_size * 2 + 'rpx' : ''}">
					  {{item.subtitle}}
					</view>
					<block v-if="item.po_status && item.po_status==1">
						<view class="p3" :style="set.title_size&&set.title_size>0?'font-size:'+set.title_size*2+'rpx':''" v-if="item.po_name">
								{{item.po_name}} {{item.po_content}}
						</view>
					</block>
					<block v-if="item.pt_status && item.pt_status==1">
						<view class="p3" :style="set.title_size&&set.title_size>0?'font-size:'+set.title_size*2+'rpx':''" v-if="item.pt_name">
								{{item.pt_name}} {{item.pt_content}}
						</view>
					</block>
					<block v-if="item.pth_status && item.pth_status==1">
						<view class="p3" :style="set.title_size&&set.title_size>0?'font-size:'+set.title_size*2+'rpx':''" v-if="item.pth_name">
								{{item.pth_name}} {{item.pth_content}}
						</view>
					</block>
					<block v-if="item.pf_status && item.pf_status==1">
						<view class="p3" :style="set.title_size&&set.title_size>0?'font-size:'+set.title_size*2+'rpx':''" v-if="item.pf_name">
								{{item.pf_name}} {{item.pf_content}}
						</view>
					</block>
					<view class="p2">
						<text style="overflow:hidden" class="flex1" v-if='show_time == 1'>{{item.createtime}}</text>
						<text style="overflow:hidden" v-if="show_readcount == 1">阅读 {{item.readcount}}</text>
					</view>
					<view class="p2" v-if="item.activity_status">
						<text style="overflow:hidden" v-if="item.activity_status == 1">未开始</text>
						<text style="overflow:hidden" v-if="item.activity_status == 2">进行中</text>
						<text style="overflow:hidden" v-if="item.activity_status == 3">已结束</text>
					</view>
				</view>
			</view>
			<waterfall-article v-if="listtype=='2'" :list="datalist" ref="waterfall" :showreadcount="show_readcount" :showtime="show_time"></waterfall-article>
			<!--单排-->
			<view v-if="listtype=='3'" class="article-item1" v-for="(item,index) in datalist" :key="item.id" @click="goto" :data-url="'detail?id='+item.id">
				<view class="article-pic">
					<image class="image" :src="item.pic" mode="widthFix"/>
				</view>
				<view class="article-info">
					<view class="p1" :style="set.title_size&&set.title_size>0?'font-size:'+set.title_size*2+'rpx':''">{{item.name}}</view>
					<view v-if="set.subtitle_status && item.subtitle" class="p3" :style="{color: '#333',fontSize: set.subtitle_size && set.subtitle_size > 0 ? set.subtitle_size * 2 + 'rpx' : ''}">
					  {{item.subtitle}}
					</view>
					<block v-if="item.po_status && item.po_status==1">
						<view class="p3" :style="set.title_size&&set.title_size>0?'font-size:'+set.title_size*2+'rpx':''" v-if="item.po_name">
								{{item.po_name}} {{item.po_content}}
						</view>
					</block>
					<block v-if="item.pt_status && item.pt_status==1">
						<view class="p3" :style="set.title_size&&set.title_size>0?'font-size:'+set.title_size*2+'rpx':''" v-if="item.pt_name">
								{{item.pt_name}} {{item.pt_content}}
						</view>
					</block>
					<block v-if="item.pth_status && item.pth_status==1">
						<view class="p3" :style="set.title_size&&set.title_size>0?'font-size:'+set.title_size*2+'rpx':''" v-if="item.pth_name">
								{{item.pth_name}} {{item.pth_content}}
						</view>
					</block>
					<block v-if="item.pf_status && item.pf_status==1">
						<view class="p3" :style="set.title_size&&set.title_size>0?'font-size:'+set.title_size*2+'rpx':''" v-if="item.pf_name">
								{{item.pf_name}} {{item.pf_content}}
						</view>
					</block>
					<view class="p2">
						<text style="overflow:hidden" class="flex1" v-if='show_time == 1'>{{item.createtime}}</text>
						<text style="overflow:hidden" v-if="show_readcount == 1">阅读 {{item.readcount}}</text>
					</view>
					<view class="p2" v-if="item.activity_status">
						<text style="overflow:hidden" v-if="item.activity_status == 1">未开始</text>
						<text style="overflow:hidden" v-if="item.activity_status == 2">进行中</text>
						<text style="overflow:hidden" v-if="item.activity_status == 3">已结束</text>
					</view>
				</view>
			</view>
			<!-- 三排显示s -->
			<view v-if="listtype=='4'" class="article-item3" v-for="(item,index) in datalist" :key="item.id" :style="{marginRight:(index+1)%3==0?'0':'2%'}" @click="goto" :data-url="'detail?id='+item.id">
				<view class="article-info">
					<view class="p1" :style="set.title_size&&set.title_size>0?'font-size:'+set.title_size*2+'rpx':''">{{item.name}}</view>
				</view>
				<view v-if="set.subtitle_status && item.subtitle" class="p3" :style="{color: '#333',fontSize: set.subtitle_size && set.subtitle_size > 0 ? set.subtitle_size * 2 + 'rpx' : ''}">
				  {{item.subtitle}}
				</view>
				<view class="article-pic">
					<image class="image" :src="item.pic" mode="widthFix"/>
				</view>
				<view class="article-info" v-if="item.po_status && item.po_status==1" style="padding:0rpx 20rpx;">
					<view class="p3" :style="set.title_size&&set.title_size>0?'font-size:'+set.title_size*2+'rpx':''" v-if="item.po_name">
								{{item.po_name}} {{item.po_content}}
						</view>
				</view>
				<view class="article-info" v-if="item.pt_status && item.pt_status==1" style="padding:0rpx 20rpx;">
					<view class="p3" :style="set.title_size&&set.title_size>0?'font-size:'+set.title_size*2+'rpx':''" v-if="item.pt_name">
								{{item.pt_name}} {{item.pt_content}}
						</view>
				</view>
				<view class="article-info" v-if="item.pth_status && item.pth_status==1" style="padding:0rpx 20rpx;">
					<view class="p3" :style="set.title_size&&set.title_size>0?'font-size:'+set.title_size*2+'rpx':''" v-if="item.pth_name">
								{{item.pth_name}} {{item.pth_content}}
						</view>
				</view>
				<view class="article-info" v-if="item.pf_status && item.pf_status==1" style="padding:0rpx 20rpx;">
					<view class="p3" :style="set.title_size&&set.title_size>0?'font-size:'+set.title_size*2+'rpx':''" v-if="item.pf_name">
								{{item.pf_name}} {{item.pf_content}}
						</view>
				</view>
				<view class="article-info">
					<view class="p2">
						<text style="overflow:hidden" class="flex1" v-if='show_time == 1'>{{item.createtime}}</text>
					</view>
					<view class="p2">
						<text style="overflow:hidden" v-if="show_readcount == 1">阅读 {{item.readcount}}</text>
					</view>
					<view class="p2" v-if="item.activity_status">
						<text style="overflow:hidden" v-if="item.activity_status == 1">未开始</text>
						<text style="overflow:hidden" v-if="item.activity_status == 2">进行中</text>
						<text style="overflow:hidden" v-if="item.activity_status == 3">已结束</text>
					</view>
				</view>
			</view>
			<!-- 三排显示e -->
            
			<!--单排三图s-->
			<view v-if="listtype=='5'" class="article-item1" v-for="(item,index) in datalist" :key="item.id" @click="goto" :data-url="'detail?id='+item.id">
				<view class="article-info">
					<view class="p1" :style="set.title_size&&set.title_size>0?'font-size:'+set.title_size*2+'rpx':''">{{item.name}}</view>
				</view>
				<view v-if="set.subtitle_status && item.subtitle" class="p3" :style="{color: '#333',fontSize: set.subtitle_size && set.subtitle_size > 0 ? set.subtitle_size * 2 + 'rpx' : ''}">
				  {{item.subtitle}}
				</view>
					<view class="article-pic">
							<block v-if="item.pic" v-for="(img,index) in item.pic">
									<image class="image" :src="img" style="width: 220rpx;height: 220rpx;margin: 8rpx;"/>
							</block>
					</view>
					<view class="article-info" v-if="item.po_status && item.po_status==1" style="padding:0rpx 20rpx;">
						<view class="p3" :style="set.title_size&&set.title_size>0?'font-size:'+set.title_size*2+'rpx':''" v-if="item.po_name">
									{{item.po_name}} {{item.po_content}}
							</view>
					</view>
					<view class="article-info" v-if="item.pt_status && item.pt_status==1" style="padding:0rpx 20rpx;">
						<view class="p3" :style="set.title_size&&set.title_size>0?'font-size:'+set.title_size*2+'rpx':''" v-if="item.pt_name">
									{{item.pt_name}} {{item.pt_content}}
							</view>
					</view>
					<view class="article-info" v-if="item.pth_status && item.pth_status==1" style="padding:0rpx 20rpx;">
						<view class="p3" :style="set.title_size&&set.title_size>0?'font-size:'+set.title_size*2+'rpx':''" v-if="item.pth_name">
									{{item.pth_name}} {{item.pth_content}}
							</view>
					</view>
					<view class="article-info" v-if="item.pf_status && item.pf_status==1" style="padding:0rpx 20rpx;">
						<view class="p3" :style="set.title_size&&set.title_size>0?'font-size:'+set.title_size*2+'rpx':''" v-if="item.pf_name">
									{{item.pf_name}} {{item.pf_content}}
							</view>
					</view>
				<view class="article-info">
					<view class="p2">
						<text style="overflow:hidden" class="flex1" v-if='show_time == 1'>{{item.createtime}}</text>
						<text style="overflow:hidden" v-if="show_readcount == 1">阅读 {{item.readcount}}</text>
					</view>
					<view class="p2" v-if="item.activity_status">
						<text style="overflow:hidden" v-if="item.activity_status == 1">未开始</text>
						<text style="overflow:hidden" v-if="item.activity_status == 2">进行中</text>
						<text style="overflow:hidden" v-if="item.activity_status == 3">已结束</text>
					</view>
				</view>
			</view>
			<!--单排三图e-->
		</view>
	</block>
	<nodata v-if="nodata"></nodata>
	<nomore v-if="nomore"></nomore>
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
			listtype:0,
			set:'',
			look_type:false,
			showlocation:false,
			arealist:[],
			area_name:'',
			area:[],
			show_time:1,//显示发布时间
			show_readcount:1,//显示阅读量
			pre_url:app.globalData.pre_url,
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.cid = this.opt.cid || 0;	
		this.bid = this.opt.bid || 0;
		this.look_type = this.opt.look_type || false;
		if(this.opt.keyword) {
			this.keyword = this.opt.keyword;
		}
		var locationCache = app.getLocationCache();
		if(locationCache){
			var sysArea = locationCache.area;
			var article_area = locationCache.article_area;
			var cacheAreaArr = [];
			if(article_area){
				cacheAreaArr = article_area.split(',');
			}else if(sysArea){
				//定位信息同步到文章
				app.setLocationCache('article_area',sysArea)
				cacheAreaArr = sysArea.split(',');
			}
			
			if(cacheAreaArr.length>0){
				this.area = cacheAreaArr;
				this.area_name = cacheAreaArr[cacheAreaArr.length-1];
			}
		}
    this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
    if (!this.nomore && !this.nodata) {
      this.pagenum = this.pagenum + 1;
      this.getdata(true);
    }
  },
  methods: {
    getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var pagenum = that.pagenum;
      var keyword = that.keyword;
      var cid = that.cid;
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
      app.post('ApiArticle/getartlist', {bid:that.bid,cid: cid,pagenum: pagenum,keyword:keyword,area:that.area}, function (res) {
				that.loading = false;
        var data = res.data;
        if (pagenum == 1) {
					that.listtype = res.listtype || 0;
					that.clist    = res.clist;
					that.set      = res.set;
					that.showlocation = res.showlocation;
					if(app.isNull(that.set.show_readcount)){
						that.show_readcount = that.set.show_readcount;
					}
					if(app.isNull(that.set.show_time)){
						that.show_time = that.set.show_time;
					}
					
					if((res.clist).length > 0){
						var cnamelist = [];
						var cidlist = [];
						cnamelist.push('全部');
						cidlist.push('0');
						for(var i in that.clist){
							cnamelist.push(that.clist[i].name);
							cidlist.push(that.clist[i].id);
						}
						that.cnamelist = cnamelist;
						that.cidlist = cidlist;
						if(that.area.length==0){
							that.needLoction = true;
						}
					}

					uni.setNavigationBarTitle({
						title: res.title
					});
          that.datalist = data;
          if (data.length == 0) {
            that.nodata = true;
          }
					if(that.showlocation){
						if(that.area_name==''){
							app.getLocation(function(res) {
								//如果从当前地址切到当前城市，则重新定位用户位置
								app.post('ApiAddress/getAreaByLocation', {latitude:res.latitude,longitude:res.longitude}, function(res) {
									if(res.status==1){
										that.area.push(res.province);
										that.area.push(res.city);
										that.area.push(res.district);
										that.area_name = res.district;
										app.setLocationCache('article_area',that.area.join(','));
										that.getdata()
									}
								})
							})
						}
						uni.request({
							url: app.globalData.pre_url+'/static/area.json',
							data: {},
							method: 'GET',
							header: { 'content-type': 'application/json' },
							success: function(res2) {
								var arealist = res2.data;
								//追加全部
								for(var i in arealist){
									// res2.data = [{text: "全国", value: "0",children:[]},...res2.data]
									var provinceT = arealist[i];
									var citys = provinceT.children;
									for(var c in citys){
										var city = citys[c]
										var districts = city.children;
										var districts = [{text: "全部", value: "0",children:[]}].concat(districts);
										citys[c]['children'] = districts;
									}
									arealist[i]['children'] = [{text: "全部", value: "0",children:[]}].concat(citys);
								}
								var newarealist = [{text: "全部", value: "0",children:[]}].concat(arealist);
								that.arealist = newarealist;
								// console.log(that.arealist)
							}
						});
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
      });
    },
    searchConfirm: function (e) {
      var that = this;
      var keyword = e.detail.value;
      that.keyword = keyword
      that.getdata();
    },
    changetab: function (cid) {
      this.cid = cid;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
			if(this.listtype==2){
				this.$refs.waterfall.refresh();
			}
      this.getdata();
    },
		areachange:function(e){
			var that = this
			const value = e.detail.value
			var area_name = '';
			var area = [];
			console.log(e);
			for(var i in value){
				if(value[i].text!='全部'){
					area.push(value[i].text)
					area_name = value[i].text
				}
			}
			if(area.length==0){
				area.push('全部')
				area_name = '全部'
			}
			app.setLocationCache('article_area',area.join(','))
			that.area = area;
			that.area_name = area_name;
			that.getdata();
		}
  }
};
</script>
<style>
page{background:#f6f6f7}
.topsearch{width:100%;padding:20rpx 20rpx;background:#fff}
.topsearch .f1{height:70rpx;border-radius:35rpx;border:0;background-color:#f5f5f5;flex:1;overflow:hidden}
.topsearch .f1 image{width:30rpx;height:30rpx;margin-left:10px}
.topsearch .f1 input{height:100%;flex:1;padding:0 20rpx;font-size:28rpx;color:#333;background-color:#f5f5f5;}

.article_list{padding:10rpx 16rpx;background:#f6f6f7;margin-top:6rpx;}
.article_list .article-item1 {width:100%;display: inline-block;position: relative;margin-bottom:16rpx;background: #fff;border-radius:12rpx;overflow:hidden}
.article_list .article-item1 .article-pic {width:100%;height:auto;overflow:hidden;background: #ffffff;}
.article_list .article-item1 .article-pic .image{width: 100%;height:auto}
.article_list .article-item1 .article-info {padding:10rpx 20rpx 20rpx 20rpx;}
.article_list .article-item1 .article-info .p1{color:#222222;font-weight:bold;font-size:28rpx;line-height:46rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.article_list .article-item1 .article-info .t1{word-break: break-all;text-overflow: ellipsis;overflow: hidden;display: block;font-size: 32rpx;}
.article_list .article-item1 .article-info .t2{word-break: break-all;text-overflow: ellipsis;padding-top:4rpx;overflow:hidden;}
.article_list .article-item1 .article-info .p2{flex-grow:0;flex-shrink:0;display:flex;padding:10rpx 0;font-size:24rpx;color:#a88;overflow:hidden}

.article_list .article-item2 {width: 49%;display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;border-radius:8rpx;}
/*.article-item2:nth-child(even){margin-right:2%}*/
.article_list .article-item2 .article-pic {width: 100%;height:0;overflow:hidden;background: #ffffff;padding-bottom:70%;position: relative;border-radius:8rpx 8rpx 0 0;}
.article_list .article-item2 .article-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.article_list .article-item2 .article-info {padding:10rpx 20rpx 20rpx 20rpx;display:flex;flex-direction:column;}
.article_list .article-item2 .article-info .p1{color:#222222;font-weight:bold;font-size:28rpx;line-height:46rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.article_list .article-item2 .article-info .p2{flex-grow:0;flex-shrink:0;display:flex;align-items:center;padding-top:10rpx;font-size:24rpx;color:#a88;overflow:hidden}

.article_list .article-itemlist {width:100%;display: inline-block;position: relative;margin-bottom:12rpx;padding:12rpx;background: #fff;display:flex;border-radius:8rpx;}
.article_list .article-itemlist .article-pic {width: 35%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 25%;position: relative;}
.article_list .article-itemlist .article-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.article_list .article-itemlist .article-info {width: 65%;height:auto;overflow:hidden;padding:0 20rpx;display:flex;flex-direction:column;justify-content:space-between}
.article_list .article-itemlist .article-info .p1{color:#222222;font-weight:bold;font-size:28rpx;line-height:46rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;max-height:92rpx}
.article_list .article-itemlist .article-info .p2{display:flex;flex-grow:0;flex-shrink:0;font-size:24rpx;color:#a88;overflow:hidden;padding-bottom:6rpx}

.article_list .article-item3 {width: 32%;display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;border-radius:8rpx;}
/*.article-item3:nth-child(even){margin-right:2%}*/
.article_list .article-item3 .article-pic {width: 100%;height:0;overflow:hidden;background: #ffffff;padding-bottom:70%;position: relative;border-radius:8rpx 8rpx 0 0;}
.article_list .article-item3 .article-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.article_list .article-item3 .article-info {padding:10rpx 20rpx 20rpx 20rpx;display:flex;flex-direction:column;}
.article_list .article-item3 .article-info .p1{color:#222222;font-weight:bold;font-size:28rpx;line-height:46rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.article_list .article-item3 .article-info .p2{flex-grow:0;flex-shrink:0;display:flex;align-items:center;padding-top:10rpx;font-size:24rpx;color:#a88;overflow:hidden}

.p3{color:#8c8c8c;font-size:28rpx;line-height:46rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}

.areasearch{display: flex;align-items: center;}
.area-picker{display: flex;max-width: 250rpx;flex-shrink: 0;align-items: center;color:#999;padding-left: 20rpx;font-size: 28rpx;justify-content: space-between;}
.area-picker .txt{max-width: 180rpx;overflow: hidden;text-overflow: ellipsis;flex: 1;white-space: nowrap;}
.area-picker image{width: 24rpx;height: 24rpx;}
.area-input{display: flex;align-items: center;justify-content: space-between;flex: 1;padding-right:30rpx; border-left: 1px solid #e1e1e1;margin-left: 20rpx;}
</style>