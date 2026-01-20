<template>
    <view class="container" :style="{backgroundImage:'url(' + set.bgpic + ')'}" v-if="isload">
        <view class="title-board">
            <image class="background" :src="`${PRE_URL}/static/img/farm/board.png`" mode="widthFix"/>
            <view class="text">
                <text>{{farm_textset['农场']}}</text>
            </view>
        </view>
		<!-- 会员信息卡片 -->
		<view class="member-card" @tap="goto" data-url="/pages/my/usercenter">
			<image class="member-avatar" :src="member.headimg" mode="aspectFill"/>
			<view class="member-info">
				<text class="member-name">{{member.nickname}}</text>
				<view class="member-level" v-if="!isNull(member.level_name)">
					<text class="level-badge">{{member.level_name}}</text>
				</view>
			</view>
		</view>
		<view class="dp-cover" v-if="set.rule_pic && set.rule_pic">
			<button @tap="showruleBox"  class="dp-cover-cover" :style="{
				zIndex:10,
				top:'4vh',
				left:'80vw',
					
				width:'130rpx',
				height:'130rpx',
				margin:'0rpx 0rpx',
				padding:'0rpx 0rpx',
				fontSize:'28rpx',
				border:'4rpx solid back',
				borderRadius:'65rpx'
			}" show-message-card="true">
					<image :src="set.rule_pic" style="width: 150rpx;" mode="aspectFit"/>
			</button>
		</view>
		
		<view class="score-price" v-if="set.show_score_price" :style="{color:set.score_price_color}" >
			<text>{{set.score_price}}</text>
			<text>昨日{{farm_textset['美宝']}}值</text>
		</view>
        <view class="main">
            <view class="menu-box">
				<view class="item" @tap="goto" data-url='/pagesD/farm/buy'>
				    <image class="icon" :src="logos.buy" mode="widthFix"/>
					<!-- <text class="badge">{{farm_member.tree_count}}</text> -->
				    <text class="label" :style="{background:set.btn_color}">去兑换</text>
				</view>
                <view class="item">
                    <image class="icon" :src="logos.tree" mode="widthFix"/>
					<text class="badge">{{farm_member.tree_count}}</text>
                    <text class="label" :style="{background:set.btn_color}">{{farm_textset['摇钱树']}}</text>
                </view>
                <view class="item" @tap="goto" data-url='/pagesD/farm/landlist'>
                    <image class="icon" :src="logos.land" mode="widthFix"/>
					<text class="badge">{{farm_member.land || 0}}</text>
                    <text class="label" :style="{background:set.btn_color}">{{farm_textset['女王地']}}</text>
                </view>
            </view>
            <view class="tree-box">
				<block v-if="!isNull(tree)">
					<image :class="{tree:true,grow:isGrowing}" :src="tree.tree_pic" mode="widthFix"/>
					<view class="progress-bar" >
						<image class="background" :src="`${PRE_URL}/static/img/farm/jdt_bg.png`" mode="widthFix"/>
						<view class="inner">
							<view class="clip-box" :style="{width:`${growthProgress}%`,backgroundImage:`url(${PRE_URL}/static/img/farm/jdt.png)`}"></view>
						</view>
						<view class="progress-text">
							<text class="progress-value">{{tree.tree_progress}}%</text>
						</view>
					</view>
					<view v-if="waterDropping" class="water"></view>
					<image v-if="isWatering" :class="{bottle:true,pour:isBottlePouring}" :src="`${PRE_URL}/static/img/farm/bottle.png`" mode="widthFix" />
					<block v-if="isFertilizing">
						<image class="shovel" :src="`${PRE_URL}/static/img/farm/shovel.png`" mode="widthFix" />
						<view class="fertilizer f1"></view>
						<view class="fertilizer f2"></view>
						<view class="fertilizer f3"></view>
						<view class="fertilizer f4"></view>
					</block>
				</block>
				<block v-else>
					<view class="empty-state">
						<!-- <image class="empty-land" :src="land_pic" mode="widthFix"/> -->
						<view class="plant-btn" :style="{background:set.btn_color}" @tap="goto" data-url="/pagesD/farm/landlist?tab_st=0">
							<text class="btn-text">去种植</text>
							<text class="btn-icon">❯</text>
						</view>
					</view>
				</block>
            </view>
			
			
            <view class="menu-box">
				<view class="item">
				</view>
                <view class="item" @tap="goto" data-url='/pagesD/farm/fertlog?st=fruit'>
                    <image class="icon" :src="logos.fruit" mode="widthFix"/>
					<text class="badge">{{farm_member.fruit || 0}}</text>
                    <text class="label" :style="{background:set.btn_color}">{{farm_textset['美宝']}}</text>
                </view>
                <view class="item" @tap="goto" data-url='/pagesD/farm/fertlog?st=seed'>
                    <image class="icon" :src="logos.seed" mode="widthFix"/>
					<text class="badge">{{farm_member.seed || 0}}</text>
                    <text class="label" :style="{background:set.btn_color}">{{farm_textset['种子']}}</text>
                </view>
            </view>
        </view>
        <view class="prop-box">
            <view class="item" v-for="(item, index) in fert_set" :key="index" @tap="useTool" :data-type="item.type" :data-name="item.name" :data-num="item.num" >
            	<image class="icon" :src="item.logo" mode="widthFix"/>
				<text class="badge">{{item.num}}</text>
            	<text class="label" :style="{background:set.btn_color}">{{item.name}}</text>
            	<text class="desc">加速 {{item.speed_rate}}%</text>
            </view>
        </view>
		<view v-if="showrule" class="xieyibox">
			<view class="xieyibox-content">
				<view style="overflow:scroll;height:100%;">
					<parse :content="set.rule_text" @navigate="navigate"></parse>
				</view>
				<view style="position:absolute;z-index:9999;bottom:10px;left:0;right:0;margin:0 auto;text-align:center; width: 50%;height: 60rpx; line-height: 60rpx; color: #fff; border-radius: 8rpx;" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  @tap="hideruleBox">
					已知晓
				</view>
			</view>
		</view>
		<loading v-if="loading"></loading>
    </view>
</template>

<script>
    const app = getApp();
    export default {
        data() {
            return {
				opt: {},
				loading: false,
				isload: false,
                PRE_URL: app.globalData.pre_url,
                treeImage: app.globalData.pre_url+'/static/img/farm/tree.png',
                // 正在生长
                isGrowing: false,
                growthProgress: 15,
              
                isWatering: false,
                isBottlePouring: false,
                waterDropping: false,
                isFertilizing: false,
                nwdVisible: false,
				
                set:{},//系统设置
				farm_member:{},//个人数据
                farm_textset:{},//文本自定义
                logos:{},//图标自定义
                fert_set:[],//肥料数据
                tree:{},//当前摇钱树数据
                land_pic:'',//土地图片
				showrule:false,//显示规则
				member:{},//会员信息
            }
        },
		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			this.land_id = this.opt.id || 0;
			this.getdata();
		},
		
		onPullDownRefresh: function() {
			// this.getdata();
		},
		
		onShow: function() {
			//this.getdata();
		},
        methods: {
			getdata: function() {
				var that = this;
				that.loading = true;
				app.post('ApiFarm/land_detail', {land_id:that.land_id}, function (res) {
					that.loading = false;
					if(res.status==0){
						app.error(res.msg);
						return;
					}
					var farm_textset = res.farm_textset;
					that.farm_textset = farm_textset;
					that.logos = res.logos;
					that.fert_set = res.fert_set;
					console.log(that.fert_set);
					var tree = res.tree;
					that.tree = tree;
					that.growthProgress = tree?tree.tree_progress:0;
					that.set = res.set;
					that.land_pic = res.land_pic;
					that.farm_member = res.farm_member;
					that.member = res.member;
					if(res.land){
						that.land_id = res.land.id;
					}
					uni.setNavigationBarTitle({
						title: farm_textset['农场']
					});
					if(tree){
						that.isGrowing = true;
						setTimeout(() => {
							that.isGrowing = false;
						}, 600)
					}
					that.loaded();
				});
			},
			useTool: function(e) {
				var type = e.currentTarget.dataset.type;
				var fert_name = e.currentTarget.dataset.name;
				var num = e.currentTarget.dataset.num;
				console.log(num);
				var that = this;
				if(num<=0){
					app.confirm('当前'+fert_name+'库存不足，是否立即兑换？', function() {
						app.goto('/pagesD/farm/buy');
						return;
					})
				}else{
					app.confirm('确定使用该'+fert_name+'吗？', function() {
						that.loading = true;
						app.post('ApiFarm/use_fert', {land_id:that.land_id,fert_type:type}, function (res) {
							that.loading = false;
							if(res.status==0){
								app.error(res.msg);
								return;
							}
							that.useProp(type,res.tree_progress)
							that.growthProgress = res.tree_progress
							setTimeout(() => {
								that.getdata();
								//that.growthProgress = res.tree_progress || 0;
							}, 600)
						});
					});
				}
				
			},
            useProp(type,tree_progress) {                
                // 请求接口
                // 回调处理
                // 营养水
                if (type === 'yingyangshui') {
                    this.isWatering = true;
                    // 等待浇水或施肥动画结束
                    setTimeout(() => {
                        // 开始倾斜瓶子
                        if (this.isWatering) {
                            this.isBottlePouring = true;
                        }
                        setTimeout(() => {
                            // 开始水滴动画
                            if (this.isWatering) {
                                this.waterDropping = true;
                            }
                            setTimeout(() => {
                                // 停止浇水
                                this.isWatering = false;
                                this.isBottlePouring = false;
                                this.waterDropping = false;
                                // 开始生长动画
                                this.isGrowing = true;
                                setTimeout(() => {
                                    this.isGrowing = false;
                                    // 修改底部进度条
                                    // this.growthProgress = tree_progress;
                                }, 600)
                            }, 600)
                        }, 300)
                    }, 300)
                }
                else {
                    this.isFertilizing = true;
                    setTimeout(() => {
                        this.isFertilizing = false;
                        // 开始生长动画
                        this.isGrowing = true;
                        setTimeout(() => {
                            this.isGrowing = false;
                            // 修改底部进度条
                            // this.growthProgress = tree_progress;
                        }, 600)
                    }, 1000)
                }
                
            },
			showruleBox: function () {
			  this.showrule = true;
			},
			hideruleBox: function () {
			  this.showrule = false;
			},
        }
    }
</script>

<style lang="scss">
    .container {
        width: 100%;
        min-height: 100vh;
        padding: 40rpx;
        box-sizing: border-box;
        background-size: cover;
        background-position: center;
        background-attachment: fixed;
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: space-between;
        .title-board {
            width: 60%;
            position: relative;
            // margin: 10rpx 0;
            .background {
                width: 100%;
            }
            .text {
                position: absolute;
                inset: 0;
                display: flex;
                flex-direction: row;
                align-items: center;
                justify-content: center;
                color: #fff;
                font-size: 40rpx;
            }
        }
        .main {
            width: 100%;
            display: flex;
            flex-direction: row;
            justify-content: space-between;
            gap: 32rpx;
            .menu-box {
                width: 20%;
                // margin-top: 100rpx;
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 100rpx;
                .item {
                    width: 100%;
                    display: flex;
                    flex-direction: column;
                    align-items: center;
                    position: relative;
                    .icon {
                        width: 70%;
                    }
                    .badge {
                        position: absolute;
                        top: -24rpx;
                        right: 6rpx;
                        min-width: 32rpx;
                        height: 32rpx;
                        line-height: 32rpx;
                        padding: 0 8rpx;
                        background: linear-gradient(135deg, #FF4444 0%, #FF6666 100%);
                        color: #FFFFFF;
                        font-size: 20rpx;
                        font-weight: bold;
                        border-radius: 16rpx;
                        text-align: center;
                        box-sizing: border-box;
                        box-shadow: 0 2rpx 8rpx rgba(255, 68, 68, 0.4);
                        z-index: 10;
                    }
                    .label {
                        width: 100%;
                        background: linear-gradient(to top, #ffdd2f, #fff283);
                        padding: 6rpx 0;
                        margin-top: -10rpx;
                        box-sizing: border-box;
                        text-align: center;
                        border-radius: 20rpx;
                        color: #a07235;
                    }
                }
            }
            .tree-box {
                position: relative;
                width: 60%;
                display: flex;
                flex-direction: column;
                align-items: center;
                align-self: flex-end;
                margin-top: auto;
                margin-bottom: 0;
                flex-shrink: 0;
                min-height: fit-content;
                .tree {
                    width: 100%;
                    transition: transform 0.6s ease;
                    @keyframes scaleUp {
                      0%   { transform: scale(1) translateY(0.0001px); }
                      100% { transform: scale(1.05) translateY(0); }
                    }
                    &.grow {
                        will-change: transform;
                        transform: translateZ(0);
                        transform-origin: center bottom;
                        animation: scaleUp 0.6s cubic-bezier(.22,.61,.36,1);
                    }
                }
                /* 水滴（流动） */
                .water {
                  position: absolute;
                  width: 20rpx;
                  height: 20rpx;
                  background: #4fc3f7;
                  border-radius: 50%;
                  bottom: 200rpx;
                  left: calc(50% - 30rpx);

                  animation: water-drop 0.6s linear forwards;
                }

                @keyframes water-drop {
                  0% { transform: translateY(0) scale(1); opacity: 1; }
                  100% { transform: translateY(100rpx) scale(0.6); opacity: 0.1; }
                }
                .bottle {
                  position: absolute;
                  width: 60rpx;
                  bottom: 160rpx;
                  left: 50%;
                  transition: transform 0.3s ease;
                  &.pour {
                    transform: rotate(-40deg);
                  }
                }
                /* 肥料颗粒基础样式 */
                .fertilizer {
                  width: 28rpx;
                  height: 28rpx;
                  background: #caa45a;
                  border-radius: 50%;
                  position: absolute;
                  bottom: 260rpx;
                  opacity: 0;
                
                  animation: fertilizer-drop 1s ease-in-out infinite;
                  &.f1 {
                      animation-delay: 0s;
                      left: calc(50% - 80rpx);
                  }
                  &.f2 {
                      animation-delay: 0.1s;
                      left: calc(50% - 36rpx);
                  }
                  &.f3 {
                      animation-delay: 0.2s;
                      left: calc(50% + 8rpx);
                  }
                  &.f4 {
                      animation-delay: 0.3s;
                      left: calc(50% + 52rpx);
                  }
                }
                /* 下落动画 */
                @keyframes fertilizer-drop {
                  0% {
                    opacity: 0;
                    transform: translateX(-50%) translateY(0);
                  }
                  20% {
                    opacity: 1;
                  }
                  60% {
                    transform: translateX(-50%) translateY(160rpx);
                  }
                  80% {
                    opacity: 0;
                  }
                  100% {
                    opacity: 0;
                    transform: translateX(-50%) translateY(0);
                  }
                }
                .shovel {
                  position: absolute;
                  width: 60rpx;
                  bottom: 280rpx;
                  left: calc(50% - 80rpx);
                  animation: sweep 1s linear forwards;
                }
                @keyframes sweep {
                  0% {
                    left: calc(50% - 80rpx);
                    opacity: 1;
                  }
                  20% {
                    opacity: 1;
                  }
                  60% {
                    opacity: 1;
                  }
                  80% {
                    opacity: 1;
                  }
                  100% {
                    left: calc(50% + 70rpx);
                    opacity: 0;
                  }
                }
                .progress-bar {
                    margin-top: 10rpx;
                    width: 80%;
                    position: relative;
                    .background {
                        width: 100%;
                        display: block;
                    }
                    .inner {
						height: 32rpx;
                        position: absolute;
                        inset: 16% 4.3% 28% 4.3%;
                        .clip-box {
                            width: 100%;
                            height: 100%;
                            background-repeat: no-repeat;
                            background-size: cover;
                        }
                    }
                    .progress-text {
                        position: absolute;
                        top: 50%;
                        left: 50%;
                        transform: translate(-50%, -50%);
                        z-index: 10;
                    }
                    .progress-value {
                        color: #FFFFFF;
                        font-size: 28rpx;
                        font-weight: bold;
                        text-shadow: 0 2rpx 4rpx rgba(0, 0, 0, 0.5),
                                    0 0 8rpx rgba(255, 221, 47, 0.6);
                        white-space: nowrap;
                    }
                }
            }
        }
        
        /* 空状态样式 */
        .empty-state {
            width: 100%;
            display: flex;
            flex-direction: column;
            align-items: center;
            .empty-land {
                width: 100%;
                opacity: 0.6;
            }
            .plant-prompt {
                margin-top: 20rpx;
                display: flex;
                flex-direction: row;
                align-items: center;
                gap: 12rpx;
                .prompt-icon {
                    font-size: 40rpx;
                }
                .prompt-text {
                    font-size: 28rpx;
                    color: rgba(255, 255, 255, 0.8);
                    text-shadow: 0 2rpx 4rpx rgba(0, 0, 0, 0.3);
                }
            }
            .plant-btn {
                margin-top: 50%;
                display: flex;
                flex-direction: row;
                align-items: center;
                gap: 12rpx;
                padding: 16rpx 48rpx;
                background: linear-gradient(135deg, #F5A623 0%, #F8B739 100%);
                border-radius: 50rpx;
                box-shadow: 0 6rpx 20rpx rgba(245, 166, 35, 0.4);
                .btn-text {
                    font-size: 32rpx;
                    font-weight: bold;
					color: #a07235;
                    text-shadow: 0 2rpx 4rpx rgba(0, 0, 0, 0.2);
                }
                .btn-icon {
                    font-size: 28rpx;
                    color: #a07235;
                    font-weight: bold;
                }
            }
        }
        
        .prop-box {
            width: 100%;
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 16rpx;
            .item {
                width: 25%;
                display: flex;
                flex-direction: column;
                align-items: center;
                position: relative;
                .icon {
                    width: 70%;
                }
                .badge {
                    position: absolute;
                    top: -22rpx;
                    right: 6rpx;
                    min-width: 32rpx;
                    height: 32rpx;
                    line-height: 32rpx;
                    padding: 0 8rpx;
                    background: linear-gradient(135deg, #FF4444 0%, #FF6666 100%);
                    color: #FFFFFF;
                    font-size: 20rpx;
                    font-weight: bold;
                    border-radius: 16rpx;
                    text-align: center;
                    box-sizing: border-box;
                    box-shadow: 0 2rpx 8rpx rgba(255, 68, 68, 0.4);
                    z-index: 10;
                }
                .label {
                    width: 100%;
                    background: linear-gradient(to top, #ffdd2f, #fff283);
                    padding: 6rpx 0;
                    margin-top: -10rpx;
                    box-sizing: border-box;
                    text-align: center;
                    border-radius: 20rpx;
                    color: #a07235;
                }
                .desc {
                    color: #f9fdbc;
                }
            }
        }
        .nwd-content {
            width: 100%;
            display: flex;
            flex-direction: column;
            padding: 40rpx;
            box-sizing: border-box;
            .header {
                width: 100%;
                display: flex;
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
                background: #eca043;
                color: #fff;
                padding: 40rpx;
                box-sizing: border-box;
                border-radius: 32rpx;
                font-size: 40rpx;
            }
            .middle {
                width: 100%;
                display: flex;
                flex-direction: row;
                align-items: center;
                justify-content: space-around;
                padding: 20rpx;
                box-sizing: border-box;
            }
            .list {
                width: 100%;
                display: flex;
                flex-direction: column;
                .item {
                    width: 100%;
                    display: flex;
                    flex-direction: row;
                    align-items: center;
                    background: #e4e4e4;
                    padding: 20rpx;
                    box-sizing: border-box;
                    border-radius: 32rpx;
                    &+.item {
                        margin-top: 20rpx;
                    }
                    .num {
                        width: 90rpx;
                    }
                    .media {
                        width: 100rpx;
                        display: flex;
                        flex-direction: column;
                        align-items: center;
                        .icon {
                            width: 100%;
                        }
                    }
                    .progress-bar {
                        margin: 0 10rpx;
                        flex-grow: 1;
                        position: relative;
                        .background {
                            width: 100%;
                        }
                        .inner {
                            position: absolute;
                            inset: 20% 4.3% 35% 4.3%;
                            .clip-box {
                                width: 100%;
                                height: 100%;
                                background-repeat: no-repeat;
                                background-size: cover;
                            }
                        }
                    }
                    .progress {
                        width: 80rpx;
                    }
                    .button {
                        width: 120rpx;
                        background: #fd4a46;
                        color: #fff;
                        border-radius: 99999rpx;
                        text-align: center;
                        padding: 10rpx;
                        box-sizing: border-box;
                    }
                }
            }
        }
    }
	
	.score-price{width: 123px;top:1vh;left: 35vw;position: relative;display: flex;justify-content: center;align-items: center;flex-direction: column;}
	.dp-cover{height: auto; position: relative;}
	.dp-cover-cover{position:fixed;z-index:99999;cursor:pointer;display:flex;align-items:center;justify-content:center;overflow:hidden}
	
	/* 会员信息卡片样式 */
	.member-card {
		position: absolute;
		top: 250rpx;
		left: 20rpx;
		// z-index: 100;
		display: flex;
		flex-direction: row;
		align-items: center;
		// background: rgba(255, 255, 255, 0.95);
		border-radius: 50rpx;
		padding: 12rpx 24rpx 12rpx 12rpx;
		box-shadow: 0 4rpx 16rpx rgba(0, 0, 0, 0.15);
		backdrop-filter: blur(10rpx);
		gap: 16rpx;
	}
	
	.member-avatar {
		width: 80rpx;
		height: 80rpx;
		border-radius: 50%;
		border: 3rpx solid #F5A623;
		box-shadow: 0 2rpx 8rpx rgba(245, 166, 35, 0.3);
		object-fit: cover;
	}
	
	.member-info {
		display: flex;
		flex-direction: column;
		gap: 8rpx;
	}
	
	.member-name {
		font-size: 28rpx;
		font-weight: bold;
		color: #333333;
		max-width: 200rpx;
		overflow: hidden;
		text-overflow: ellipsis;
		white-space: nowrap;
	}
	
	.member-level {
		display: flex;
		flex-direction: row;
		align-items: center;
	}
	
	.level-badge {
		background: linear-gradient(135deg, #F5A623 0%, #F8B739 100%);
		color: #FFFFFF;
		font-size: 20rpx;
		font-weight: bold;
		padding: 4rpx 16rpx;
		border-radius: 12rpx;
		box-shadow: 0 2rpx 6rpx rgba(245, 166, 35, 0.3);
	}
	
	.xieyibox{width:100%;height:100%;position:fixed;top:0;left:0;z-index:99;background:rgba(0,0,0,0.7)}
	.xieyibox-content{width:90%;margin:0 auto;/*  #ifdef  MP-TOUTIAO */height:60%;/*  #endif  *//*  #ifndef  MP-TOUTIAO */height:80%;/*  #endif  */margin-top:20%;background:#fff;color:#333;padding:5px 10px 50px 10px;position:relative;border-radius:2px;}
</style>