<template>
	<view
		class="dp-btn"
		:class="{
			'dp-btn--primary': type === 'primary',
			'dp-btn--success': type === 'success',
			'dp-btn--warn': type === 'warn',
			'dp-btn--error': type === 'error',
			'dp-btn--large': size === 'large',
			'dp-btn--base': size === 'base',
			'dp-btn--small': size === 'small',
			'dp-btn--radius': radius,
			'dp-btn--plain': plain,
			'dp-btn--disabled': disabled,
			'dp-btn--loading': loading,
			'dp-btn--block': block,
			'dp-btn--text': text,
		}">
		<image class="dp-btn--icon" src="data:image/svg+xml;base64,PHN2ZyB4bWxucz0iaHR0cDovL3d3dy53My5vcmcvMjAwMC9zdmciIHdpZHRoPSIxMjAiIGhlaWdodD0iMTIwIiB2aWV3Qm94PSIwIDAgMTAwIDEwMCI+PHBhdGggZmlsbD0ibm9uZSIgZD0iTTAgMGgxMDB2MTAwSDB6Ii8+PHJlY3Qgd2lkdGg9IjciIGhlaWdodD0iMjAiIHg9IjQ2LjUiIHk9IjQwIiBmaWxsPSIjRTlFOUU5IiByeD0iNSIgcnk9IjUiIHRyYW5zZm9ybT0idHJhbnNsYXRlKDAgLTMwKSIvPjxyZWN0IHdpZHRoPSI3IiBoZWlnaHQ9IjIwIiB4PSI0Ni41IiB5PSI0MCIgZmlsbD0iIzk4OTY5NyIgcng9IjUiIHJ5PSI1IiB0cmFuc2Zvcm09InJvdGF0ZSgzMCAxMDUuOTggNjUpIi8+PHJlY3Qgd2lkdGg9IjciIGhlaWdodD0iMjAiIHg9IjQ2LjUiIHk9IjQwIiBmaWxsPSIjOUI5OTlBIiByeD0iNSIgcnk9IjUiIHRyYW5zZm9ybT0icm90YXRlKDYwIDc1Ljk4IDY1KSIvPjxyZWN0IHdpZHRoPSI3IiBoZWlnaHQ9IjIwIiB4PSI0Ni41IiB5PSI0MCIgZmlsbD0iI0EzQTFBMiIgcng9IjUiIHJ5PSI1IiB0cmFuc2Zvcm09InJvdGF0ZSg5MCA2NSA2NSkiLz48cmVjdCB3aWR0aD0iNyIgaGVpZ2h0PSIyMCIgeD0iNDYuNSIgeT0iNDAiIGZpbGw9IiNBQkE5QUEiIHJ4PSI1IiByeT0iNSIgdHJhbnNmb3JtPSJyb3RhdGUoMTIwIDU4LjY2IDY1KSIvPjxyZWN0IHdpZHRoPSI3IiBoZWlnaHQ9IjIwIiB4PSI0Ni41IiB5PSI0MCIgZmlsbD0iI0IyQjJCMiIgcng9IjUiIHJ5PSI1IiB0cmFuc2Zvcm09InJvdGF0ZSgxNTAgNTQuMDIgNjUpIi8+PHJlY3Qgd2lkdGg9IjciIGhlaWdodD0iMjAiIHg9IjQ2LjUiIHk9IjQwIiBmaWxsPSIjQkFCOEI5IiByeD0iNSIgcnk9IjUiIHRyYW5zZm9ybT0icm90YXRlKDE4MCA1MCA2NSkiLz48cmVjdCB3aWR0aD0iNyIgaGVpZ2h0PSIyMCIgeD0iNDYuNSIgeT0iNDAiIGZpbGw9IiNDMkMwQzEiIHJ4PSI1IiByeT0iNSIgdHJhbnNmb3JtPSJyb3RhdGUoLTE1MCA0NS45OCA2NSkiLz48cmVjdCB3aWR0aD0iNyIgaGVpZ2h0PSIyMCIgeD0iNDYuNSIgeT0iNDAiIGZpbGw9IiNDQkNCQ0IiIHJ4PSI1IiByeT0iNSIgdHJhbnNmb3JtPSJyb3RhdGUoLTEyMCA0MS4zNCA2NSkiLz48cmVjdCB3aWR0aD0iNyIgaGVpZ2h0PSIyMCIgeD0iNDYuNSIgeT0iNDAiIGZpbGw9IiNEMkQyRDIiIHJ4PSI1IiByeT0iNSIgdHJhbnNmb3JtPSJyb3RhdGUoLTkwIDM1IDY1KSIvPjxyZWN0IHdpZHRoPSI3IiBoZWlnaHQ9IjIwIiB4PSI0Ni41IiB5PSI0MCIgZmlsbD0iI0RBREFEQSIgcng9IjUiIHJ5PSI1IiB0cmFuc2Zvcm09InJvdGF0ZSgtNjAgMjQuMDIgNjUpIi8+PHJlY3Qgd2lkdGg9IjciIGhlaWdodD0iMjAiIHg9IjQ2LjUiIHk9IjQwIiBmaWxsPSIjRTJFMkUyIiByeD0iNSIgcnk9IjUiIHRyYW5zZm9ybT0icm90YXRlKC0zMCAtNS45OCA2NSkiLz48L3N2Zz4=" mode="" v-if="loading"></image>
		<view>
			<slot></slot>
		</view>
	</view>
</template>

<script>
	export default {
		name:"dp-btn",
		props:{
			type: {
				type: String,
				default: '' // primary success warn error
			},
			plain: {
				type: Boolean,
				default: false
			},
			loading: {
				type: Boolean,
				default: false
			},
			disabled: {
				type: Boolean,
				default: false
			},
			block: {
				type: Boolean,
				default: false
			},
			text: {
				type: Boolean,
				default: false
			},
			size: {
				type: String,
				default: 'base' // large base small
			},
			radius: {
				type: Boolean,
				default: false
			}
		},
		methods: {}
	}
</script>

<style lang="scss" scoped>
	.dp-btn {
		background-color: $uni-text-color-placeholder;
		color: $uni-text-color-inverse;
		text-align: center;
		display: flex;
		justify-content: center;
		align-items: center;
		&--primary {
			background-color: $uni-color-primary;
		}
		&--success {
			background-color: $uni-color-success;
		}
		&--warn {
			background-color: $uni-color-warning;
		}
		&--error {
			background-color: $uni-color-error;
		}
		&--plain {
			border: 1px solid $uni-border-color;
			background-color: transparent !important;
		}
		&--disabled,
		&--loading {
			pointer-events: none;
			opacity: $uni-opacity-disabled;
		}
		&--large {
			border-radius: $uni-border-radius-lg;
			padding: $uni-spacing-col-lg;
			font-size: $uni-font-size-lg;
		}
		&--base {
			border-radius: $uni-border-radius-base;
			padding: $uni-spacing-col-base $uni-spacing-row-base;
			font-size: $uni-font-size-base;
		}
		&--small {
			border-radius: $uni-border-radius-sm;
			padding: $uni-spacing-col-sm $uni-spacing-row-sm;
			font-size: $uni-font-size-sm;
		}
		&--text {
			background-color: transparent !important;
			border: 0;
			color: $uni-text-color;
		}
	}
	.dp-btn--plain.dp-btn--primary {
		border-color: $uni-color-primary;
		color: $uni-color-primary;
	}
	.dp-btn--plain.dp-btn--success {
		border-color: $uni-color-success;
		color: $uni-color-success;
	}
	.dp-btn--plain.dp-btn--warn {
		border-color: $uni-color-warning;
		color: $uni-color-warning;
	}
	.dp-btn--plain.dp-btn--error {
		border-color: $uni-color-error;
		color: $uni-color-error;
	}
	.dp-btn--large.dp-btn--block {
		padding: $uni-spacing-col-lg*1.5 $uni-spacing-row-lg;
	}
	.dp-btn--base.dp-btn--block {
		padding: $uni-spacing-col-base*2 $uni-spacing-row-base;
	}
	.dp-btn--small.dp-btn--block {
		padding: $uni-spacing-col-sm*2.5 $uni-spacing-row-sm;
	}
	.dp-btn--text.dp-btn--primary {
		color: $uni-color-primary;
	}
	.dp-btn--text.dp-btn--success {
		color: $uni-color-success;
	}
	.dp-btn--text.dp-btn--warn {
		color: $uni-color-warning;
	}
	.dp-btn--text.dp-btn--error {
		color: $uni-color-error;
	}
	.dp-btn--radius {
		border-radius: 100rpx;
	}
	.dp-btn--icon {
		width: 36rpx;
		height: 36rpx;
		animation-name: animation;
		animation-duration: 2s;
		animation-iteration-count: infinite;
		animation-timing-function: linear;
	}
	@keyframes animation {
	  0% {
	    transform: rotate(0deg);
	  }
	  100% {
	    transform: rotate(360deg);
	  }
	}
</style>
<!-- 
属性名称		数据类型		必需		默认值		说明
type		string		否					按钮类型：primary、success、warn、error
size		string		否		base		按钮大小：large(大)、base(中，默认)、small(小)
plain		boolean		否		false		是否空心
disabled	boolean		否		false		禁用
loading		boolean		否		false		加载中
block		boolean		否		false		块状按钮，作为块使用时，请一定要添加此属性
text		boolean		否		false		文字按钮，text的优先级大于plain
radius		boolean		否		false		圆形按钮
 -->