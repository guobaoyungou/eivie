<template>
  <view class="waterfalls-box" :style="{ height: height + 'px' }">
    <view
      v-for="(item, index) of list"
      class="waterfalls-list"
      :key="item[idKey]"
      :id="'waterfalls-list-id-' + item[idKey]"
      :ref="'waterfalls-list-id-' + item[idKey]"
      :style="{
        '--offset': offset + 'px',
        '--cols': cols,
        top: allPositionArr[index] ? allPositionArr[index].top : 0,
        left: allPositionArr[index] ? allPositionArr[index].left : 0,
      }"
      @click="goto" :data-url="'/pagesExt/article/detail?id='+item[idKey]"
    >
      <image
        class="waterfalls-list-image"
        mode="widthFix"
        :style="imageStyle"
        :src="item[imageSrcKey] || ' '"
        @load="imageLoadHandle(index)"
        @error="imageLoadHandle(index)"
      />
      <view class="article-waterfall-info">
				<view class="p1">{{item.name}}</view>
				<view v-if="item.subtitle_status && item.subtitle" class="p3" :style="{color: '#333',fontSize: item.subtitle_size && item.subtitle_size > 0 ? item.subtitle_size * 2 + 'rpx' : ''}">
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
					<text style="overflow:hidden" class="flex1" v-if="showtime=='1'">{{item.createtime}}</text>
					<text style="overflow:hidden" v-if="showreadcount=='1'">阅读 {{item.readcount}}</text>
				</view>
			</view>
    </view>
  </view>
</template>
<script>
export default {
  props: {
    list: { type: Array, required: true },
    // offset 间距，单位为 px
    offset: { type: Number, default: 8 },
    // 列表渲染的 key 的键名，值必须唯一，默认为 id
    idKey: { type: String, default: "id" },
    // 图片 src 的键名
    imageSrcKey: { type: String, default: "pic" },
    // 列数
    cols: { type: Number, default: 2, validator: (num) => num >= 2 },
    imageStyle: { type: Object },
		showtime: {type: String, default: "1" },
		showreadcount: {type: String, default: "1" }
  },
  data() {
    return {
      topArr: [], // left, right 多个时依次表示第几列的数据
      allPositionArr: [], // 保存所有的位置信息
      allHeightArr: [], // 保存所有的 height 信息
      height: 0, // 外层包裹高度
      oldNum: 0,
      num: 0,
    };
  },
  created() {
    this.refresh();
  },
  methods: {
    imageLoadHandle(index) {
      const id = "waterfalls-list-id-" + this.list[index][this.idKey],
        query = uni.createSelectorQuery().in(this);
				setTimeout(() => {
					query.select("#" + id).fields({ size: true }, (data) => {
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
					      const { shorterIndex, shorterValue } = getTopArrMsg();
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
  },
};
</script>
<style scoped>
.waterfalls-box {position: relative;width: 100%;overflow: hidden;}
.waterfalls-box .waterfalls-list {width: calc((100% - var(--offset) * (var(--cols) - 1)) / var(--cols));position: absolute;background-color: #fff;border-radius: 8rpx;left: calc(-50% - var(--offset));}
.waterfalls-box .waterfalls-list .waterfalls-list-image {width: 100%;will-change: transform;border-radius:8rpx 8rpx 0 0;display: block;}

.article-waterfall-info{padding:10rpx 20rpx 20rpx 20rpx;display:flex;flex-direction:column;}
.article-waterfall-info .p1{color:#222222;font-weight:bold;font-size:28rpx;line-height:46rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
.article-waterfall-info .p2{flex-grow:0;flex-shrink:0;display:flex;align-items:center;padding-top:10rpx;font-size:24rpx;color:#a88;overflow:hidden}
.p3{color:#8c8c8c;font-size:28rpx;line-height:46rpx;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:2;overflow:hidden;}
</style>
