<template>
<view class="dp-article" :style="{
	backgroundColor:params.bgcolor,margin:(params.margin_y*2.2)+'rpx '+(params.margin_x*2.2)+'rpx 0',
	padding:(params.padding_y*2.2)+'rpx '+(params.padding_x*2.2)+'rpx'
}">
	<!--单排-->
	<view v-if="params.style=='1'" class="article-item1" v-for="(item,index) in data" :key="item.id" @click="goto" :data-url="'/pagesExt/article/detail?id='+item.artid"
	:style="{borderBottom:params.showline && params.showline==1?' 1px solid '+params.linecolor:'',borderRadius:params.borderradius+'px'}">
		<view class="article-pic" v-if="params.showpic==1">
			<image v-if="params.pic_width>0&&params.pic_height>0" class="image" :src="item.pic" :style="'width: '+params.pic_width*2+'rpx;height:'+params.pic_height*2+'rpx'"/>
			<image v-else class="image" :src="item.pic" mode="widthFix"/>
		</view>
		<view class="article-info">
			<view class="p1" :style="params.title_size>0?'font-size: '+params.title_size*2+'rpx':''">{{item.name}}</view>
			<block  v-if="params.subtitle_status && params.subtitle_status=='1'">
				<view class="p3" :style="{color: '#333',fontSize: params.subtitle_size && params.subtitle_size > 0 ? params.subtitle_size * 2 + 'rpx' : ''}" v-if="item.subtitle">
						{{item.subtitle}}
				</view>
			</block>
			<block  v-if="item.po_status && item.po_status==1">
				<view class="p3" :style="params.title_size&&params.title_size>0?'font-size:'+params.title_size*2+'rpx':''" v-if="item.po_name">
						{{item.po_name}} {{item.po_content}}
				</view>
			</block>
			<block v-if="item.pt_status && item.pt_status==1">
				<view class="p3" :style="params.title_size&&params.title_size>0?'font-size:'+params.title_size*2+'rpx':''" v-if="item.pt_name">
						{{item.pt_name}} {{item.pt_content}}
				</view>
			</block>
			<block v-if="item.pth_status && item.pth_status==1">
				<view class="p3" :style="params.title_size&&params.title_size>0?'font-size:'+params.title_size*2+'rpx':''" v-if="item.pth_name">
						{{item.pth_name}} {{item.pth_content}}
				</view>
			</block>
			<block v-if="item.pf_status && item.pf_status==1">
				<view class="p3" :style="params.title_size&&params.title_size>0?'font-size:'+params.title_size*2+'rpx':''" v-if="item.pf_name">
						{{item.pf_name}} {{item.pf_content}}
				</view>
			</block>
			<view class="p2">
				<text style="overflow:hidden" class="flex1" v-if="params.showtime==1">{{item.createtime}}</text>
				<text style="overflow:hidden" v-if="params.showreadcount==1">阅读 {{item.readcount}}</text>
			</view>
		</view>
	</view>
	<!--双排-->
	<view v-if="params.style=='2'" class="article-item2" v-for="(item,index) in data" :style="{marginRight:index%2==0?'2%':'0'}" :key="item.id" @click="goto" :data-url="'/pagesExt/article/detail?id='+item.artid">
		<view class="article-pic" v-if="params.showpic==1">
			<image v-if="params.pic_width>0&&params.pic_height>0" class="image" :src="item.pic" :style="'width: '+params.pic_width*2+'rpx;height:'+params.pic_height*2+'rpx'"/>
			<image v-else class="image" :src="item.pic" mode="widthFix"/>
		</view>
		<view class="article-info">
			<view class="p1" :style="params.title_size>0?'font-size: '+params.title_size*2+'rpx':''">{{item.name}}</view>
			<block  v-if="params.subtitle_status && params.subtitle_status=='1'">
				<view class="p3" :style="{color: '#333',fontSize: params.subtitle_size && params.subtitle_size > 0 ? params.subtitle_size * 2 + 'rpx' : ''}"  v-if="item.subtitle">
						{{item.subtitle}}
				</view>
			</block>
			<block  v-if="item.po_status && item.po_status==1">
				<view class="p3" :style="params.title_size&&params.title_size>0?'font-size:'+params.title_size*2+'rpx':''" v-if="item.po_name">
					{{item.po_name}} {{item.po_content}}
				</view>
			</block>
			<block v-if="item.pt_status && item.pt_status==1">
				<view class="p3" :style="params.title_size&&params.title_size>0?'font-size:'+params.title_size*2+'rpx':''" v-if="item.pt_name">
					{{item.pt_name}} {{item.pt_content}}
				</view>
			</block>
			<block v-if="item.pth_status && item.pth_status==1">
				<view class="p3" :style="params.title_size&&params.title_size>0?'font-size:'+params.title_size*2+'rpx':''" v-if="item.pth_name">
					{{item.pth_name}} {{item.pth_content}}
				</view>
			</block>
			<block v-if="item.pf_status && item.pf_status==1">
				<view class="p3" :style="params.title_size&&params.title_size>0?'font-size:'+params.title_size*2+'rpx':''" v-if="item.pf_name">
					{{item.pf_name}} {{item.pf_content}}
				</view>
			</block>
			<view class="p2">
				<text style="overflow:hidden" class="flex1" v-if="params.showtime==1">{{item.createtime}}</text>
				<text style="overflow:hidden" v-if="params.showreadcount==1">阅读 {{item.readcount}}</text>
			</view>
		</view>
	</view>
	<!--左图-->
	<view v-if="params.style=='4'" class="article-itemlist" v-for="(item,index) in data" :key="item.id" @click="goto" :data-url="'/pagesExt/article/detail?id='+item.artid"
	:style="{borderBottom:params.showline && params.showline==1?' 1px solid '+params.linecolor:'',borderRadius:params.borderradius+'px'}">
		<view class="article-pic" v-if="params.showpic==1">
			<image v-if="params.pic_width>0&&params.pic_height>0" class="image" :src="item.pic" :style="'width: '+params.pic_width*2+'rpx;height:'+params.pic_height*2+'rpx'"/>
			<image v-else class="image" :src="item.pic" mode="widthFix"/>
		</view>
		<view class="article-info">
			<view class="p1" :style="params.title_size>0?'font-size: '+params.title_size*2+'rpx':''">{{item.name}}</view>
			<block  v-if="params.subtitle_status && params.subtitle_status=='1'">
				<view class="p3" :style="{color: '#333',fontSize: params.subtitle_size && params.subtitle_size > 0 ? params.subtitle_size * 2 + 'rpx' : ''}" v-if="item.subtitle">
						{{item.subtitle}}
				</view>
			</block>
			<block  v-if="item.po_status && item.po_status==1">
				<view class="p3" :style="params.title_size&&params.title_size>0?'font-size:'+params.title_size*2+'rpx':''" v-if="item.po_name">
					{{item.po_name}} {{item.po_content}}
				</view>
			</block>
			<block v-if="item.pt_status && item.pt_status==1">
				<view class="p3" :style="params.title_size&&params.title_size>0?'font-size:'+params.title_size*2+'rpx':''" v-if="item.pt_name">
					{{item.pt_name}} {{item.pt_content}}
				</view>
			</block>
			<block v-if="item.pth_status && item.pth_status==1">
				<view class="p3" :style="params.title_size&&params.title_size>0?'font-size:'+params.title_size*2+'rpx':''" v-if="item.pth_name">
					{{item.pth_name}} {{item.pth_content}}
				</view>
			</block>
			<block v-if="item.pf_status && item.pf_status==1">
				<view class="p3" :style="params.title_size&&params.title_size>0?'font-size:'+params.title_size*2+'rpx':''" v-if="item.pf_name">
					{{item.pf_name}} {{item.pf_content}}
				</view>
			</block>
			<view class="p2">
				<text style="overflow:hidden" class="flex1" v-if="params.showtime==1">{{item.createtime}}</text>
				<text style="overflow:hidden" v-if="params.showreadcount==1">阅读 {{item.readcount}}</text>
			</view>
		</view>
	</view>
	<!--右图-->
	<view v-if="params.style=='5'" class="article-itemlist" v-for="(item,index) in data" :key="item.id" @click="goto" :data-url="'/pagesExt/article/detail?id='+item.artid"
	:style="{borderBottom:params.showline && params.showline==1?' 1px solid '+params.linecolor:'',borderRadius:params.borderradius+'px'}">
		<view class="article-info" style="padding-left:10rpx">
			<view class="p1" :style="params.title_size>0?'font-size: '+params.title_size*2+'rpx':''">{{item.name}}</view>
			<block  v-if="params.subtitle_status && params.subtitle_status=='1'">
				<view class="p3" :style="{color: '#333',fontSize: params.subtitle_size && params.subtitle_size > 0 ? params.subtitle_size * 2 + 'rpx' : ''}" v-if="item.subtitle">
						{{item.subtitle}}
				</view>
			</block>
			<block  v-if="item.po_status && item.po_status==1">
				<view class="p3" :style="params.title_size&&params.title_size>0?'font-size:'+params.title_size*2+'rpx':''" v-if="item.po_name">
					{{item.po_name}} {{item.po_content}}
				</view>
			</block>
			<block v-if="item.pt_status && item.pt_status==1">
				<view class="p3" :style="params.title_size&&params.title_size>0?'font-size:'+params.title_size*2+'rpx':''" v-if="item.pt_name">
					{{item.pt_name}} {{item.pt_content}}
				</view>
			</block>
			<block v-if="item.pth_status && item.pth_status==1">
				<view class="p3" :style="params.title_size&&params.title_size>0?'font-size:'+params.title_size*2+'rpx':''" v-if="item.pth_name">
					{{item.pth_name}} {{item.pth_content}}
				</view>
			</block>
			<block v-if="item.pf_status && item.pf_status==1">
				<view class="p3" :style="params.title_size&&params.title_size>0?'font-size:'+params.title_size*2+'rpx':''" v-if="item.pf_name">
					{{item.pf_name}} {{item.pf_content}}
				</view>
			</block>
			<view class="p2">
				<text style="overflow:hidden" class="flex1" v-if="params.showtime==1">{{item.createtime}}</text>
				<text style="overflow:hidden" v-if="params.showreadcount==1">阅读 {{item.readcount}}</text>
			</view>
		</view>
		<view class="article-pic" v-if="params.showpic==1">
			<image v-if="params.pic_width>0&&params.pic_height>0" class="image" :src="item.pic" :style="'width: '+params.pic_width*2+'rpx;height:'+params.pic_height*2+'rpx'"/>
			<image v-else class="image" :src="item.pic" mode="widthFix"/>
		</view>
	</view>
	<waterfall-article v-if="params.style=='6'" :list="data" ref="waterfall" :showtime="params.showtime" :showreadcount="params.showreadcount" idKey="artid"></waterfall-article>
    <!-- 三排显示s -->
    <view v-if="params.style=='7'" class="article-item3" v-for="(item,index) in data" :style="{marginRight:(index+1)%3==0?'0':'2%'}" :key="item.id" @click="goto" :data-url="'/pagesExt/article/detail?id='+item.artid">
    	<view class="article-info">
    		<view class="p1" :style="params.title_size>0?'font-size: '+params.title_size*2+'rpx':''">{{item.name}}</view>
    	</view>
				<view class="article-info" v-if="params.subtitle_status && params.subtitle_status=='1'">
					<view class="p3" :style="{color: '#333',fontSize: params.subtitle_size && params.subtitle_size > 0 ? params.subtitle_size * 2 + 'rpx' : ''}" v-if="item.subtitle">
							{{item.subtitle}}
					</view>
				</view>
        <view class="article-info" v-if="item.po_status && item.po_status==1" style="padding:0rpx 20rpx;">
        	<view class="p3" :style="params.title_size&&params.title_size>0?'font-size:'+params.title_size*2+'rpx':''" v-if="item.po_name">
						{{item.po_name}} {{item.po_content}}
					</view>
        </view>
        <view class="article-info" v-if="item.pt_status && item.pt_status==1" style="padding:0rpx 20rpx;">
        	<view class="p3" :style="params.title_size&&params.title_size>0?'font-size:'+params.title_size*2+'rpx':''" v-if="item.pt_name">
						{{item.pt_name}} {{item.pt_content}}
					</view>
        </view>
        <view class="article-info" v-if="item.pth_status && item.pth_status==1" style="padding:0rpx 20rpx;">
        	<view class="p3" :style="params.title_size&&params.title_size>0?'font-size:'+params.title_size*2+'rpx':''" v-if="item.pth_name">
						{{item.pth_name}} {{item.pth_content}}
					</view>
        </view>
        <view class="article-info" v-if="item.pf_status && item.pf_status==1" style="padding:0rpx 20rpx;">
        	<view class="p3" :style="params.title_size&&params.title_size>0?'font-size:'+params.title_size*2+'rpx':''" v-if="item.pf_name">
						{{item.pf_name}} {{item.pf_content}}
					</view>
        </view>
    	<view class="article-pic" v-if="params.showpic==1">
    		<image v-if="params.pic_width>0&&params.pic_height>0" class="image" :src="item.pic" :style="'width: '+params.pic_width*2+'rpx;height:'+params.pic_height*2+'rpx'"/>
				<image v-else class="image" :src="item.pic" style="width: 100%;height: 100%;" />
    	</view>
    	<view class="article-info">
    		<view class="p2">
    			<view style="overflow:hidden" class="flex1" v-if="params.showtime==1">{{item.createtime}}</view>
    		</view>
				<view class="p2">
					<view style="overflow:hidden" v-if="params.showreadcount==1">阅读 {{item.readcount}}</view>
				</view>
    	</view>
    </view>
    <!-- 三排显示e -->
    <!--单排三图s-->
    <view v-if="params.style=='8'" class="article-item1" v-for="(item,index) in data" :key="item.id" @click="goto" :data-url="'/pagesExt/article/detail?id='+item.artid">
    	<view class="article-info">
    		<view class="p1" :style="params.title_size>0?'font-size: '+params.title_size*2+'rpx':''">{{item.name}}</view>
    	</view>
			<view class="article-info" v-if="params.subtitle_status && params.subtitle_status=='1'">
				<view class="p3" :style="{color: '#333',fontSize: params.subtitle_size && params.subtitle_size > 0 ? params.subtitle_size * 2 + 'rpx' : ''}" v-if="item.subtitle">
						{{item.subtitle}}
				</view>
			</view>
			<view class="article-info" v-if="item.po_status && item.po_status==1" style="padding:0rpx 20rpx;">
				<view class="p3" :style="params.title_size&&params.title_size>0?'font-size:'+params.title_size*2+'rpx':''" v-if="item.po_name">
					{{item.po_name}} {{item.po_content}}
				</view>
			</view>
			<view class="article-info" v-if="item.pt_status && item.pt_status==1" style="padding:0rpx 20rpx;">
				<view class="p3" :style="params.title_size&&params.title_size>0?'font-size:'+params.title_size*2+'rpx':''" v-if="item.pt_name">
					{{item.pt_name}} {{item.pt_content}}
				</view>
			</view>
			<view class="article-info" v-if="item.pth_status && item.pth_status==1" style="padding:0rpx 20rpx;">
				<view class="p3" :style="params.title_size&&params.title_size>0?'font-size:'+params.title_size*2+'rpx':''" v-if="item.pth_name">
					{{item.pth_name}} {{item.pth_content}}
				</view>
			</view>
			<view class="article-info" v-if="item.pf_status && item.pf_status==1" style="padding:0rpx 20rpx;">
				<view class="p3" :style="params.title_size&&params.title_size>0?'font-size:'+params.title_size*2+'rpx':''" v-if="item.pf_name">
					{{item.pf_name}} {{item.pf_content}}
				</view>
			</view>
			<view class="article-pic" v-if="params.showpic==1">
					<block v-if="item.pic" v-for="(img,index) in item.pics">
							<view style="display: inline-block;width: 32%;margin-left: 0.9%;overflow: hidden;">
									<image v-if="params.pic_width>0&&params.pic_height>0" class="image" :src="img" :style="'width: '+params.pic_width*2+'rpx;height:'+params.pic_height*2+'rpx;'"/>
									<image v-else class="image" :src="img" style="width: 220rpx;height: 220rpx;"/>
							</view>
					</block>
			</view>
    	<view class="article-info">
    		<view class="p2">
    			<text style="overflow:hidden" class="flex1" v-if="params.showtime==1">{{item.createtime}}</text>
    			<text style="overflow:hidden" v-if="params.showreadcount==1">阅读 {{item.readcount}}</text>
    		</view>
    	</view>
    </view>
    <!--单排三图e-->
	<!-- 左右滑动 -->
	<scroll-view v-if="params.style=='9'" scroll-x="true" style="width: 100%;white-space: nowrap;">
		<view class="article-item3 article-scroll" v-for="(item,index) in data" style="display: inline-block;" :key="item.id" @click="goto" :data-url="'/pagesExt/article/detail?id='+item.artid">
			<view class="article-info">
				<view class="p1 article-text" :style="params.title_size>0?'font-size: '+params.title_size*2+'rpx':''">{{item.name}}</view>
			</view>
			<view class="article-info" v-if="params.subtitle_status && params.subtitle_status=='1'">
				<view class="p3 article-text" :style="{color: '#333',fontSize: params.subtitle_size && params.subtitle_size > 0 ? params.subtitle_size * 2 + 'rpx' : ''}" v-if="item.subtitle">
						{{item.subtitle}}
				</view>
			</view>
		    <view class="article-info" v-if="item.po_status && item.po_status==1" style="padding:0rpx 20rpx;">
		    	<view class="p3 article-text" :style="params.title_size&&params.title_size>0?'font-size:'+params.title_size*2+'rpx':''" v-if="item.po_name">
							{{item.po_name}} {{item.po_content}}
					</view>
		    </view>
		    <view class="article-info" v-if="item.pt_status && item.pt_status==1" style="padding:0rpx 20rpx;">
		    	<view class="p3 article-text" :style="params.title_size&&params.title_size>0?'font-size:'+params.title_size*2+'rpx':''" v-if="item.pt_name">
							{{item.pt_name}} {{item.pt_content}}
					</view>
		    </view>
		    <view class="article-info" v-if="item.pth_status && item.pth_status==1" style="padding:0rpx 20rpx;">
		    	<view class="p3 article-text" :style="params.title_size&&params.title_size>0?'font-size:'+params.title_size*2+'rpx':''" v-if="item.pth_name">
							{{item.pth_name}} {{item.pth_content}}
					</view>
		    </view>
		    <view class="article-info" v-if="item.pf_status && item.pf_status==1" style="padding:0rpx 20rpx;">
		    	<view class="p3 article-text" :style="params.title_size&&params.title_size>0?'font-size:'+params.title_size*2+'rpx':''" v-if="item.pf_name">
							{{item.pf_name}} {{item.pf_content}}
					</view>
		    </view>
			<view class="article-pic" v-if="params.showpic==1">
				<image v-if="params.pic_width>0&&params.pic_height>0" class="image" :src="item.pic" :style="'width: '+params.pic_width*2+'rpx;height:'+params.pic_height*2+'rpx'"/>
				<image v-else class="image" :src="item.pic" mode="widthFix"/>
			</view>
			<view class="article-info">
				<view class="p2">
					<view style="overflow:hidden" class="flex1" v-if="params.showtime==1">{{item.createtime}}</view>
				</view>
				<view class="p2">
					<view style="overflow:hidden" v-if="params.showreadcount==1">阅读 {{item.readcount}}</view>
				</view>
			</view>
		</view>
	</scroll-view>
	<!-- 左右滑动 -->
</view>
</template>
<script>
	export default {
		props: {
			params:{},
			data:{}
		}
	}
</script>
<style>
.dp-article{height: auto; position: relative;overflow: hidden; padding:10rpx 0px; background: #fff;}
.dp-article .article-item1 {width: 100%;display: inline-block;position: relative;margin-bottom:12rpx;background: #fff;border-radius:12rpx;overflow:hidden}
.dp-article .article-item1 .article-pic {width:100%;height:auto;overflow:hidden;background: #ffffff;}
.dp-article .article-item1 .article-pic .image{width: 100%;height:auto}
.dp-article .article-item1 .article-info {padding:10rpx 20rpx;}
.dp-article .article-item1 .article-info .p1{color:#222222;font-weight:bold;font-size:28rpx;line-height:46rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.dp-article .article-item1 .article-info .t1{word-break: break-all;text-overflow: ellipsis;overflow: hidden;display: block;font-size: 32rpx;}
.dp-article .article-item1 .article-info .t2{word-break: break-all;text-overflow: ellipsis;padding-top:4rpx;overflow:hidden;}
.dp-article .article-item1 .article-info .p2{flex-grow:0;flex-shrink:0;display:flex;padding:10rpx 0;font-size:24rpx;color:#a88;overflow:hidden}
.dp-article .article-item1 .article-info .p3{color:#8c8c8c;font-size:28rpx;line-height:46rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;max-height:92rpx}

.dp-article .article-item2 {width: 49%;display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;border-radius:8rpx;}
/*.article-item2:nth-child(even){margin-right:2%}*/
.dp-article .article-item2 .article-pic {width: 100%;height:0;overflow:hidden;background: #ffffff;padding-bottom:70%;position: relative;border-radius:8rpx 8rpx 0 0;}
.dp-article .article-item2 .article-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-article .article-item2 .article-info {padding:10rpx 20rpx;display:flex;flex-direction:column;}
.dp-article .article-item2 .article-info .p1{color:#222222;font-weight:bold;font-size:28rpx;line-height:46rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.dp-article .article-item2 .article-info .p2{flex-grow:0;flex-shrink:0;display:flex;align-items:center;padding-top:10rpx;font-size:24rpx;color:#a88;overflow:hidden}

.dp-article .article-itemlist {width:100%;display: inline-block;position: relative;margin-bottom:12rpx;padding:12rpx;background: #fff;display:flex;border-radius:8rpx;}
.dp-article .article-itemlist .article-pic {width: 35%;height:0;overflow:hidden;background: #ffffff;padding-bottom: 25%;position: relative;flex-shrink: 0;vertical-align: middle;}
.dp-article .article-itemlist .article-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-article .article-itemlist .article-info {width: 65%;height:auto;overflow:hidden;padding:0 20rpx;display:flex;flex-direction:column;justify-content:space-between;flex: 1;}
.dp-article .article-itemlist .article-info .p1{color:#222222;font-weight:bold;font-size:28rpx;line-height:46rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;max-height:92rpx}
.dp-article .article-itemlist .article-info .p2{display:flex;flex-grow:0;flex-shrink:0;font-size:24rpx;color:#a88;overflow:hidden}


.dp-article .article-waterfall-info{padding:10rpx 20rpx 20rpx 20rpx;display:flex;flex-direction:column;}
.dp-article .article-waterfall-info .p1{color:#222222;font-weight:bold;font-size:28rpx;line-height:46rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.dp-article .article-waterfall-info .p2{flex-grow:0;flex-shrink:0;display:flex;align-items:center;padding-top:10rpx;font-size:24rpx;color:#a88;overflow:hidden}

.dp-article .article-item3 {width: 32%;display: inline-block;position: relative;margin-bottom: 12rpx;background: #fff;border-radius:8rpx;}
/*.article-item3:nth-child(even){margin-right:2%}*/
.dp-article .article-item3 .article-pic {width: 100%;height:0;overflow:hidden;background: #ffffff;padding-bottom:70%;position: relative;border-radius:8rpx 8rpx 0 0;}
.dp-article .article-item3 .article-pic .image{position:absolute;top:0;left:0;width: 100%;height:auto}
.dp-article .article-item3 .article-info {padding:10rpx 20rpx;display:flex;flex-direction:column;}
.dp-article .article-item3 .article-info .p1{color:#222222;font-weight:bold;font-size:28rpx;line-height:46rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.dp-article .article-item3 .article-info .p2{flex-grow:0;flex-shrink:0;display:flex;align-items:center;padding-top:10rpx;font-size:24rpx;color:#a88;overflow:hidden}

.p3{color:#8c8c8c;font-size:28rpx;line-height:46rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}

.article-scroll{margin-left: 20rpx;}
.article-scroll:first-child{margin-left: 0;}
.article-text{white-space: break-spaces;}
</style>