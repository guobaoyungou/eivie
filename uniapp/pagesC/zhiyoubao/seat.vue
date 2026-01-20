<template>
  <view class="sel-seat">
    <block v-if="isload">
      <view style="background-color:#ffff ;position: relative;z-index: 9;">
        <view style="width: 680rpx;margin: auto;line-height: 40rpx;padding: 10rpx 0;text-align: center;">
          <view style="font-weight: bold;">{{perform.title}}</view>
          <view >日期：{{perform.performDate}} {{perform.performTime}}</view>
        </view>
      </view>
      <view class="seat-main">
        <!-- <scroll-view style="height:100%;width: 100%;" :scroll-y="true" :scroll-x="true"> -->
          <movable-area style="height:100%" class="vm-area">
            <movable-view :style="'width: '+boxWidth+'px;height:'+boxHeight+'px;'" :inertia="true" :scale="true" :scale-min="0.95" :x="-(boxWidth/2)+200"
             :scale-max="2" direction="all" :out-of-bounds="true" @scale="onScale">
             <view class="sm-title">
               <text class="text">舞台方向</text>
             </view>
              <!-- <view class="sm-screen">
                <text class="text">银幕中央</text>
              </view> -->
              <!-- <view class="sm-line-center"></view> -->
              <!-- seat content -->

              <view v-for="(item,index) in seatArray" :key="index" class="sm-cell" :style="'height:'+seatSize+'px;justify-content:'+ cellJustifycontent">
                <block v-for="(seat,col) in item" :key="col">
                  <block v-if="seat.twoSeater && seat.twoSeater == 1">
                    <view v-if="seat.type>-3" class="dp-ib-suang" :style="'width:'+ (seatSize*2+3) +'px;height:'+seatSize+'px'"
                     @click="handleChooseSeat(index,col)">
                      <block v-if="seat.type>=0">
                        <image v-if="seat.type===1"  :src="pre_url+'/static/img/seat/issel2.png'" style="width: 100%;height: 100%;position:absolute;top:0;left: 0;"></image>
                        <view class="dp-item":style="'background:'+seat.areacolor"></view>
                      </block>
                      <block v-else-if="seat.type == -1">
                        <view class="dp-item" style="background:#dddddd"></view>
                      </block>
                      <!-- 不可选座状态 -->
                      <!-- <image class="sm-icon-s" :src="pre_url+'/static/img/seat/shuangzuonotselect.png'" mode="aspectFit"></image> -->
                    </view>
                  </block>
                  <block v-else>
                    <view v-if="seat.type>-3" class="dp-ib" :style="'width:'+seatSize+'px;height:'+seatSize+'px'"
                     @click="handleChooseSeat(index,col)">
                      <block v-if="seat.type>=0">
                        <image v-if="seat.type===1"  :src="pre_url+'/static/img/seat/issel.png'" style="width: 100%;height: 100%;position:absolute;top:0;left: 0;"></image>
                        <view class="dp-item":style="'background:'+seat.areacolor"></view>
                      </block>
                      <block v-else-if="seat.type == -1">
                        <view class="dp-item" style="background:#dddddd"></view>
                      </block>
                       <!-- <image v-if="seat.type===0" class="sm-icon" :src="pre_url+'/static/img/seat/unselected.png'" mode="aspectFit"></image>
                       <image v-else-if="seat.type===1" class="sm-icon" :src="pre_url+'/static/img/seat/selected.png'" mode="aspectFit"></image>
                       <image v-else-if="seat.type===2" class="sm-icon" :src="pre_url+'/static/img/seat/bought.png'" mode="aspectFit"></image> -->
                       <!-- 不可选座状态 -->
                       <!-- <image class="sm-icon" :src="pre_url+'/static/img/seat/notselected.png'" mode="aspectFit"></image> -->
                    </view>
                  </block>
                </block>
              </view>
              <view v-if="mArr && mArr.length>0" class="sm-line-index" :style="'left: '+(10-moveX/scale)+'px;'">
                <text class="text" :style="'height:'+seatSize+'px;'" v-for="(m,mindex) in mArr" :key="mindex">{{m}}</text>
              </view>
            </movable-view>
          </movable-area>
        <!-- </scroll-view> -->
      </view>
      <!-- foot -->
      <view style="width: 100%;height: 400rpx;"></view>
      <view class="seat-foot" style="position: fixed;width: 100%;bottom: 0;left: 0;">
        <view class="fix-tips">
          <block v-for="(item,index) in areaData" :key="index">
            <view class="v-tips">
              <view class="tip-areacolor" :style="'background:'+item.areacolor"></view>
              <text class="text">{{item.areaname}}</text>
            </view>
          </block>
          <view class="v-tips">
            <view class="tip-areacolor" style="background:#dddddd"></view>
            <text class="text">不可选</text>
          </view>
        </view>
        <!-- <view class="sf-recommend" v-if="SelectNum === 0">
          <text class="text">推荐选座</text>
          <view class="sfr-tag" v-for="num in Math.min(max, 6)" :key="num"
            @click="smartChoose(num+1)">
            <text class="text">{{ num+1 }}人</text>
          </view>
        </view> -->
        <view class="sf-arselect" >
          <view class="text">
            已选(<text style="color: red;">{{SelectNum}}</text>)
          </view>
          <scroll-view scroll-x="true" style="margin-bottom: 20rpx;">
            <view class="scr-wrap">
              <view class="sfr-selt" v-for="(optItem,optindex) in optArr" :key="optItem.SeatCode" @click="handleChooseSeat(optItem.rowIndex, optItem.colIndex)">
                <!-- <image src="/static/close.png" class="sfr-close"></image> -->
                <view>
                  <view class="text" style="line-height: 40rpx;">{{optItem.RowNum+'排'+optItem.ColumnNum+'座'}}</view>
                  <view v-if="optItem.Price>=0" class="price">￥{{optItem.Price}}</view>
                </view>
                <view class="sfr-close">X</view>
              </view>
            </view>
          </scroll-view>
        </view>
        <view class="f-btn">
          <view v-if="SelectNum >0"  @click="buySeat" style="background-color: #F45664;">
            <view class="text"><text v-if="aPrice>=0">￥{{aPrice}}</text>确认座位</view>
          </view>
          <view v-else style="background-color: #F45664;opacity: 0.7;">
            <text class="text">请选座位</text>
          </view>
        </view>
      </view>
    </block>

    <loading v-if="loading"></loading>
    <dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
    <popmsg ref="popmsg"></popmsg>
    <wxxieyi></wxxieyi>
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
        pre_url: app.globalData.pre_url,
        isload:false,
        
        id:0,
        type:0,//类型 0：选择 1：换座
        orderid:0,//订单id 换座时需要传
        ogid:0,//订单商品表id 换座时需要传
        perform:'',//节目信息
        fids:[],//选择的座位fid

        scaleMin:1,//h5端为解决1无法缩小问题，设为0.95
        boxWidth: 0, //屏幕宽度px
        boxHeight:0,//屏幕高度px
        space: ' ', //空格
        seatArray: [], //影院座位的二维数组,-2为空白处，-1为不可售，0为未购座位，1为已选座位,一维行，二维列
        seatRow: 0, //影院座位行数
        seatCol: 0, //影院座位列数
        seatSize: 0, //座位尺寸
        SelectNum: 0, //选择座位数
        moveX: 0, //水平移动偏移量
        scale: 1, //放大倍数
        minRow: 0, //从第几行开始排座位
        minCol: 0, //从第几列开始排座位
        showTis: true, //显示选座提示
        seatList: [], //接口获取的原始位置
        mArr: [], //排数提示
        optArr: [], //选中的座位数组。
        isWXAPP:false,
				seatData:[],//座椅数据
        areaData:[],//区域数据
				max:0,
        cellJustifycontent:'',
        areaSeatType:0,//0：默认样式，仅读取接口返回 1：固定样式，
        areaid:0,
      };
    },
		computed: {
			aPrice() {
        let totalAmount = ''
        if (this.optArr && this.optArr.length) {
          totalAmount = this.optArr.map(item => Number(item.Price)).reduce((prev, curr) => prev + curr)
        }
				return totalAmount
			},
			// rpxNum() {
			// 	return this.boxWidth / 750
			// },
			// pxNum() {
			// 	return 750 / this.boxWidth
			// },
		},
    onLoad: function (opt) {
    	this.opt = app.getopts(opt);
      this.id = this.opt.id || 0;
      this.type = this.opt.type || 0;
      this.orderid = this.opt.orderid || 0;
      this.ogid = this.opt.ogid || 0;
      this.areaid = this.opt.areaid || 0;
    	this.getdata();
    },
    onShow:function(e){
    },
		methods: {
      getdata:function(){
      	var that = this;
      	that.loading = true;
      	app.post('ApiZhiyoubao/getAreaSeats', {id: that.id,type: that.type,orderid: that.orderid,ogid: that.ogid,areaid:that.areaid}, function (res) {
      		that.loading = false;
      		if (res.status == 1) {
              that.max = res.max;
              that.perform  = res.perform;
              that.areaData = res.areaData;
              that.seatData = res.seatData;
              that.boxWidth  = res.boxWidth;
              that.boxHeight = res.boxHeight;
              that.seatSize  = res.seatSize;
              that.cellJustifycontent = res.cellJustifycontent;
              that.areaSeatType = res.areaSeatType;
              that.mArr = res.mArr;
              console.log(that.mArr )
              that.initData();
              that.loaded();
      		}else{
            app.alert(res.msg,function(){
            	app.goback();
            });
            return;
          }
      		
      	});
      },
			initData: function() {
				let arr = this.seatData
				//假数据说明：SeatCode座位编号，RowNum-行号，ColumnNum-纵号，YCoord-Y坐标，XCoord-X坐标，Status-状态
				let row = 0
				let col = 0
				let minCol = parseInt(arr[0].XCoord)
				let minRow = parseInt(arr[0].YCoord)
				for (let i of arr) {
					minRow = parseInt(i.YCoord) < minRow ? parseInt(i.YCoord) : minRow
					minCol = parseInt(i.XCoord) < minCol ? parseInt(i.XCoord) : minCol
					row = parseInt(i.YCoord) > row ? parseInt(i.YCoord) : row
					col = parseInt(i.XCoord) > col ? parseInt(i.XCoord) : col
				}				
				this.seatList = arr
				this.seatRow = row+1
				this.seatCol = col+1
				this.minRow = minRow
				this.minCol = minCol
				this.initSeatArray()
			},
			//初始座位数组
			initSeatArray: function() {
        var that = this;
        // type -3 为无 -2为空白处，-1为不可售，0为未购座位，1为已选座位
        if(that.areaSeatType == 1){
          var type = -3;
        }else{
          var type = -2;
        }
				var seatArray = Array(that.seatRow).fill(0).map(() => Array(that.seatCol).fill({
					type: type,
					SeatCode: '',
					RowNum: '',
					ColumnNum: ''
				}));
				that.seatArray = seatArray;

				that.initNonSeatPlace();
			},
			//初始化是座位的地方
			initNonSeatPlace: function() {
        
				let seat = this.seatList.slice();//源数据
				let arr = this.seatArray.slice();//根据X Y 排序好的空数组
				for (let num in seat) {
					//-1为不可售，0为可售未购座位，1为已选座位
					var status   = seat[num].Status;
          var areacolor= seat[num].areacolor;
          var SeatCode = seat[num].SeatCode;
          var RowNum   = seat[num].RowNum || 0;
          var ColumnNum= seat[num].ColumnNum || 0;
          var Price    = seat[num].Price || 0;
          var twoSeater= seat[num].twoSeater? seat[num].twoSeater: 0;
          var y = parseInt(seat[num].YCoord) - this.minRow;
          var x = parseInt(seat[num].XCoord) - this.minCol;
          if(arr[y] && arr[y][x]){
            arr[y][x] = {
            	type: status,
              areacolor: areacolor,
            	SeatCode: SeatCode,
            	RowNum: RowNum,
            	ColumnNum: ColumnNum,
            	Price: Price,
            	twoSeater:twoSeater,
            }
          }else{
            arr[y][x] = {
            	type: -3,
              areacolor: '',
            	SeatCode: '',
            	RowNum: 0,
            	ColumnNum: 0,
            	Price: -1,
            	twoSeater:0,
            }
          }
				}
				this.seatArray = arr.slice();
			},
			//放大缩小事件
			onScale: function(e) {
				this.showTis = false
				// this.moveX=-e.detail.x
				let w = this.boxWidth * 0.5
				let s = 1 - e.detail.scale
				this.moveX = w * s
				this.scale = e.detail.scale
				if (s > 0 || s === 0) {
					this.showTis = true
				}
			},
			//移动事件
			onMove: function(e) {
				this.showTis = false
				this.moveX = e.detail.x
			},
			//重置座位
			resetSeat: function() {
				this.SelectNum = 0
				this.optArr = []
				//将所有已选座位的值变为0
				let oldArray = this.seatArray.slice();
				for (let i = 0; i < this.seatRow; i++) {
					for (let j = 0; j < this.seatCol; j++) {
						if (oldArray[i][j].type === 1) {
							oldArray[i][j].type = 0
						}
					}
				}
				this.seatArray = oldArray;
			},
			//选定且购买座位
			buySeat: function() {
        var that = this;
				if (that.SelectNum <= 0 || !that.fids){
          app.alert('请选择座位');
          return;
        }
        
        var fids = that.fids.join(',');
        if(!that.type || that.type == 0){
          var fids = that.fids.join(',');
          app.goto('buy?id='+that.id+'&fids='+fids+'&areaid='+that.areaid);
        }else{
          //更换座位
          app.post('ApiZhiyoubao/modifyTheaterSeats', {type:that.type,orderid: that.orderid,ogid: that.ogid,fids:fids}, function (res) {
          	that.loading = false;
          	if (res.status == 1) {
                app.success(res.msg);
                setTimeout(function(){
                  app.goto('orderlist','redirect');
                },900)
          	}else{
              app.alert(res.msg)
              return;
            }
          });
        }
			},
			//处理座位选择逻辑
			handleChooseSeat: function(row, col) {
        
        var that = this;
        var seatArray = that.seatArray;
				let seatValue = seatArray[row][col].type;
        
				//如果是已购座位，直接返回
				if (seatValue != 0 &&  seatValue != 1) return
        
				//如果是已选座位点击后变未选
				if (seatValue === 1) {
					seatArray[row][col].type = 0
					that.SelectNum--
					that.getOptArr(seatArray[row][col], 0)
				} else if (seatValue === 0) {
          if (that.SelectNum >= that.max) {
            return uni.showToast({
              title: '一次最多选择' + that.max + '张',
              icon: 'none'
            })
          }
          seatArray[row][col].rowIndex = row
          seatArray[row][col].colIndex = col
					seatArray[row][col].type = 1
					that.SelectNum++
					that.getOptArr(seatArray[row][col], 1)
				}
				//必须整体更新二维数组，Vue无法检测到数组某一项更新,必须slice复制一个数组才行
				that.seatArray = seatArray.slice();
			},
			//处理已选座位数组
			getOptArr: function(item, type) {
        var that = this;
				var optArr = that.optArr;
        var fids   = that.fids;
				if (type === 1) {
					optArr.push(item)
          fids.push(item.SeatCode);
				} else if (type === 0) {
					let arr = [];
          var fids= []; 
					optArr.forEach(v => {
						if (v.SeatCode !== item.SeatCode) {
							arr.push(v);
              fids.push(v.SeatCode)
						}
					})
					optArr = arr
				}
				that.optArr = optArr.slice();
        that.fids = fids;
			},
			//推荐选座,参数是推荐座位数目，
			smartChoose: function(num) {
				// console.log('num===', num)
				// 先重置
				this.resetSeat()
				//找到影院座位水平垂直中间位置的后一排
				let rowStart = parseInt((this.seatRow - 1) / 2, 10) + 1;
				//先从中间排往后排搜索
				let backResult = this.searchSeatByDirection(rowStart, this.seatRow - 1, num);
				if (backResult.length > 0) {
					this.chooseSeat(backResult);
					this.SelectNum += num
					return
				}
				//再从中间排往前排搜索
				let forwardResult = this.searchSeatByDirection(rowStart - 1, 0, num);
				if (forwardResult.length > 0) {
					this.chooseSeat(forwardResult);
					this.SelectNum += num
					return
				}
				//提示用户无合法位置可选
        // #ifdef H5
				alert('无合法位置可选!')
        // #endif
			},
			
			//搜索函数,参数:fromRow起始行，toRow终止行,num推荐座位数
			searchSeatByDirection: function(fromRow, toRow, num) {
				/*
				 * 推荐座位规则
				 * (1)初始状态从座位行数的一半处的后一排的中间开始向左右分别搜索，取离中间最近的，如果满足条件，
				 *    记录下该结果离座位中轴线的距离，后排搜索完成后取距离最小的那个结果座位最终结果，优先向后排进行搜索，
				 *    后排都没有才往前排搜，前排逻辑同上
				 *
				 * (2)只考虑并排且连续的座位，不能不在一排或者一排中间有分隔
				 *
				 * */

				/*
				 * 保存当前方向搜索结果的数组,元素是对象,result是结果数组，offset代表与中轴线的偏移距离
				 * {
				 *   result:Array([x,y])
				 *   offset:Number
				 * }
				 *
				 */
				let currentDirectionSearchResult = [];

				let largeRow = fromRow > toRow ? fromRow : toRow,
					smallRow = fromRow > toRow ? toRow : fromRow;

				for (let i = smallRow; i <= largeRow; i++) {
					//每一排的搜索,找出该排里中轴线最近的一组座位
					let tempRowResult = [],
						minDistanceToMidLine = Infinity;
					for (let j = 0; j <= this.seatCol - num; j++) {
						//如果有合法位置
						if (this.checkRowSeatContinusAndEmpty(i, j, j + num - 1)) {
							//计算该组位置距离中轴线的距离:该组位置的中间位置到中轴线的距离
							let resultMidPos = parseInt((j + num / 2), 10);
							let distance = Math.abs(parseInt(this.seatCol / 2) - resultMidPos);
							//如果距离较短则更新
							if (distance < minDistanceToMidLine) {
								minDistanceToMidLine = distance;
								//该行的最终结果
								tempRowResult = this.generateRowResult(i, j, j + num - 1)
							}
						}
					}
					//保存该行的最终结果
					currentDirectionSearchResult.push({
						result: tempRowResult,
						offset: minDistanceToMidLine
					})
				}

				//处理后排的搜索结果:找到距离中轴线最短的一个
				//注意这里的逻辑需要区分前后排，对于后排是从前往后，前排则是从后往前找
				let isBackDir = fromRow < toRow;
				let finalReuslt = [],
					minDistanceToMid = Infinity;
				if (isBackDir) {
					//后排情况,从前往后
					currentDirectionSearchResult.forEach((item) => {
						if (item.offset < minDistanceToMid) {
							finalReuslt = item.result;
							minDistanceToMid = item.offset;
						}
					});
				} else {
					//前排情况，从后往前找
					currentDirectionSearchResult.reverse().forEach((item) => {
						if (item.offset < minDistanceToMid) {
							finalReuslt = item.result;
							minDistanceToMid = item.offset;
						}
					})
				}

				//直接返回结果
				return finalReuslt
			},

			/*辅助函数，判断每一行座位从i列到j列是否全部空余且连续
			 *
			 */
			checkRowSeatContinusAndEmpty: function(rowNum, startPos, endPos) {
				let isValid = true;
				for (let i = startPos; i <= endPos; i++) {
					if (this.seatArray[rowNum][i].type !== 0) {
						isValid = false;
						break;
					}
				}
				return isValid
			},
			//辅助函数：返回每一行的某个合理位置的座位数组
			generateRowResult: function(row, startPos, endPos) {
				let result = [];
				for (let i = startPos; i <= endPos; i++) {
					result.push([row, i])
				}
				return result
			},
			//辅助函数:智能推荐的选座操作
			chooseSeat: function(result) {
				let opt = this.optArr
				let oldArray = this.seatArray.slice();
				for (let i = 0; i < result.length; i++) {
					//选定座位
          oldArray[result[i][0]][result[i][1]].rowIndex = result[i][0]
          oldArray[result[i][0]][result[i][1]].colIndex = result[i][1]
					oldArray[result[i][0]][result[i][1]].type = 1
					this.optArr.push(oldArray[result[i][0]][result[i][1]])
				}
				this.seatArray = oldArray;
			},
		}
  }
</script>

<style>
	page {background: #eee; height: 100%;}
	.sel-seat{display: flex; flex-direction: column;width: 100%;height: 100%;}
  .seat-main{height: 100%;}
	.seat-main .vm-area{width: 750rpx;margin: 0 auto;}
	.seat-main .sm-title {background-color: #dddddd;width: 400rpx;height: 40rpx;transform: perspective(40rpx) rotateX(-10deg);
	  margin: 0 auto;display: flex;align-items: center;justify-content: center;position: relative;z-index: 2;}
	.seat-main .sm-title .text {font-size: 24rpx;color: #333333;}
	.seat-main .sm-screen {width: 100rpx;height: 30rpx;border: 1rpx solid #cccccc;display: flex;
	  align-items: center;justify-content: center; margin: 48rpx auto 0;border-radius: 4rpx;position: relative;z-index: 1;}
	.seat-main .sm-screen .text {font-size: 20rpx;color: #999999;}
	.seat-main .sm-line-center {height: 610rpx;width: 0;border: 1rpx dashed #e5e5e5;position: fixed;left: 50%;top: 0;display: block;z-index: 0;transform: translateX(-50%);}
	
  .seat-main .dp-ib{margin-right: 3px;position: relative;border-radius: 4rpx;overflow: hidden;}
  .seat-main .dp-item{width: 100%;height: 100%;border-radius: 4rpx;}
  .seat-main .dp-ib-suang{margin-right: 3px;position: relative;border-radius: 4rpx;overflow: hidden;}
	.seat-main .dp-ib-suang .sm-icon-s {width: 80%;height: 100%;margin-left: 8%;}
	.seat-main .sm-cell {display: flex;margin-top: 20rpx;align-items: center;position: relative;z-index: 2;padding: 0 10rpx;}
	.seat-main .sm-cell .sm-icon {width: 100%;height: 100%;}
	.seat-main .sm-line-index {position: fixed;top: 40rpx;left: 20rpx;border-radius: 14rpx;overflow: hidden;background-color: rgba(0,0,0,.2);z-index: 3;display: flex;flex-direction: column;align-items: center;justify-content: center;padding-bottom: 20rpx;width: 40rpx;}
	.seat-main .sm-line-index .text {font-size: 24rpx;color: #ffffff;width: 100%;text-align: center;margin-top: 20rpx;}
  .seat-main{width: 100%;position: relative;padding: 10rpx;}
	.seat-foot .sf-recommend {display: flex;align-items: center;justify-content: center;padding-bottom: 20rpx;}
	.seat-foot .text {font-size: 28rpx;color: #666666;}
	.seat-foot .sfr-tag {width: 110rpx;height: 60rpx;border-radius: 4rpx;border: 1rpx solid #ccc;display: flex;align-items: center;justify-content: center;margin-left: 20rpx;
	}  
	.seat-foot .sf-arselect .text {font-size: 26rpx;color: #666666;line-height: 60rpx;}
	.seat-foot .sf-arselect .price {font-size: 22rpx;color: red;}
	.seat-foot .sf-arselect .scr-wrap {display: flex;white-space: nowrap;}
	.seat-foot .sf-arselect .sfr-selt {min-width: 150rpx;min-height:90rpx;border-radius: 8rpx;border: 1rpx solid #ccc;display: flex;align-items: center;justify-content: space-between;position: relative;margin-right: 20rpx; padding:0 10rpx;}
	.seat-foot .sf-arselect	.sfr-selt .sfr-close {font-size: 22rpx;color: #999;margin:0 10rpx;}
	.seat-foot .f-btn {width: 100%;height: 90rpx;line-height: 90rpx;text-align: center;border-radius: 10rpx;overflow: hidden;}
	.seat-foot .f-btn .text {color: #ffffff;font-size: 36rpx;}
  .seat-foot {margin-top: auto;background-color: #ffffff;padding: 20rpx 30rpx;position: relative;z-index: 5;}
  
  .fix-tips {display: flex;align-items: center;justify-content: center;font-size: 24rpx;width: 100%;z-index: 1;flex-wrap: wrap;}
  .fix-tips .v-tips {display: flex;align-items: center;color: #999;margin: 0 10rpx;margin-bottom: 4rpx;}
  .fix-tips .v-tips .tip-areacolor{width: 40rpx;height: 40rpx;border-radius: 6rpx;overflow: hidden;}
  .fix-tips .v-tips .text {margin-left: 10rpx;}
</style>
