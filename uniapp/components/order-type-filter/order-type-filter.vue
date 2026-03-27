<template>
	<view class="order-type-filter">
		<scroll-view scroll-x class="filter-scroll" :show-scrollbar="false">
			<view class="filter-inner">
				<view
					v-for="(item, index) in typeList"
					:key="item.value"
					class="filter-item"
					:class="{active: currentValue == item.value}"
					@tap="onSelect(item.value)"
				>
					<text>{{item.label}}</text>
				</view>
			</view>
		</scroll-view>
	</view>
</template>

<script>
export default {
	name: 'order-type-filter',
	props: {
		types: {
			type: Array,
			default: function() {
				return [];
			}
		},
		value: {
			type: String,
			default: 'all'
		}
	},
	computed: {
		typeList: function() {
			var list = [{label: '全部', value: 'all'}];
			var labelMap = {
				'shop': '商城', 'collage': '拼团', 'seckill': '秒杀', 'tuangou': '团购',
				'kanjia': '砍价', 'lucky_collage': '幸运拼团', 'scoreshop': '积分兑换',
				'yuyue': '预约', 'kecheng': '课程', 'cycle': '周期购', 'ai_pick': '选片'
			};
			for (var i = 0; i < this.types.length; i++) {
				var t = this.types[i];
				if (labelMap[t]) {
					list.push({label: labelMap[t], value: t});
				}
			}
			return list;
		},
		currentValue: function() {
			return this.value;
		}
	},
	methods: {
		onSelect: function(val) {
			this.$emit('input', val);
			this.$emit('change', val);
		}
	}
};
</script>

<style>
.order-type-filter {
	width: 100%;
	background: #fff;
}
.filter-scroll {
	width: 100%;
	white-space: nowrap;
}
.filter-inner {
	display: inline-flex;
	padding: 0 20rpx;
}
.filter-item {
	display: inline-flex;
	align-items: center;
	justify-content: center;
	padding: 16rpx 28rpx;
	font-size: 26rpx;
	color: #666;
	position: relative;
	flex-shrink: 0;
}
.filter-item.active {
	color: #333;
	font-weight: bold;
}
.filter-item.active::after {
	content: '';
	position: absolute;
	bottom: 6rpx;
	left: 50%;
	transform: translateX(-50%);
	width: 40rpx;
	height: 4rpx;
	background: #ff5722;
	border-radius: 2rpx;
}
</style>
