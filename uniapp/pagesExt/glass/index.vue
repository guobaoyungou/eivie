<template>
<view class="container">
	<block v-if="isload">
		<block v-if="datalist.length>0">
			<dd-tab :itemdata="namelist" :itemst="indexlist" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
			<view class="box mt">
				<view class="content" v-if="detail.nickname || detail.check_time">
					<view class="base">
						<view class="fl-l">
							<view class="name">{{detail.nickname}}</view>
							<view class=""><text v-if="detail.sex">{{detail.sex==1?'男':'女'}} </text> {{detail.age?detail.age+'岁':''}}</view>
						</view>
						<view class="fl-r" style="text-align: right;" v-if="detail.check_time">
							<view class="t">验光时间</view>
							<view class="t">{{detail.check_time}}</view>
						</view>
					</view>
					<view class="remark">
						备注：{{detail.remark}}
					</view>
				</view>
				<view class="content">
					<view class="row-title">
						<view class="l">{{detail.typetxt}}</view>
						<view class="r">瞳距 
							<text v-if="detail.double_ipd==0">{{detail.ipd}}</text>
							<text v-else>R{{detail.ipd_right}} L{{detail.ipd_left}}</text>
						</view>
					</view>
					<view class="table-body">
						<view class="row table-header">
							<view class="table-item lable" style="color: #222222;">验光数据</view>
							<view class="table-item">右眼</view>
							<view class="table-item">左眼</view>
						</view>
						<view class="row">
							<view class="table-item lable">球镜(Sph)</view>
							<view class="table-item">{{detail.degress_right}}</view>
							<view class="table-item">{{detail.degress_left}}</view>
						</view>
						<!-- <block v-if="detail.is_ats==1"> -->
							<view class="row">
								<view class="table-item lable">柱镜(Cyl)</view>
								<view class="table-item">{{detail.ats_right}}</view>
								<view class="table-item">{{detail.ats_left}}</view>
							</view>
							<view class="row">
								<view class="table-item lable">轴位(Axis)</view>
								<view class="table-item">{{detail.ats_zright}}</view>
								<view class="table-item">{{detail.ats_zleft}}</view>
							</view>
						<!-- </block> -->
						<view class="row" v-if="detail.type==3">
							<view class="table-item lable">下加光(ADD)</view>
							<view class="table-item">{{detail.add_right}}</view>
							<view class="table-item">{{detail.add_left}}</view>
						</view>
						<view class="row">
							<view class="table-item lable">矫正视力</view>
							<view class="table-item">{{detail.correction_right}}</view>
							<view class="table-item">{{detail.correction_left}}</view>
						</view>
					</view>
				
					<view class="bottom">
						<button class="btn" @tap="del" :data-id="detail.id">删除</button>
						<button class="btn" @tap="goto" :data-url="'add?id='+detail.id">编辑</button>
						<button class="btn" @tap="goto" data-url="add">添加</button>
						<button class="btn btn1" v-if="confirm==1" @tap="selectrecord" :data-id="detail.id" :style="{background:t('color1'),color:'#ffffff'}">选择</button>
					</view>
				</view>
			</view>
		</block>
		<block v-else>
			<!-- <nomore text="没有更多信息了" v-if="nomore"></nomore> -->
			<nodata text="还没有你的视力档案哟~"></nodata>
			<view class="goadd">
				<button class="btn" @tap="goto" data-url="add" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)',color:'#ffffff'}" form-type="submit" data-type="1">完善视力档案</button>
			</view>
		</block>
	</block>
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
			
			pre_url:app.globalData.pre_url,
      st: 0,
      datalist: [],
      pagenum: 1,
      myscore: 0,
      myscore2: 0,
      nodata: false,
      nomore: false,
			datalist:[],
			namelist:[],
			indexlist:[],
			detail:{},
			st:0,
			confirm:0,
			sid:0,
			dkmid:0
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.confirm = this.opt.c || 0;
		this.sid = this.opt.sid || 0;
		this.dkmid = this.opt.dkmid || 0;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  onReachBottom: function () {
  },
  methods: {
    getdata: function (loadmore) {
			if(!loadmore){
				this.pagenum = 1;
				this.datalist = [];
			}
      var that = this;
      var pagenum = that.pagenum;
			that.nodata = false;
			that.nomore = false;
			that.loading = true;
      app.post('ApiGlass/myrecord', {pagenum:1,listrow:100,dkmid:that.dkmid}, function (res) {
				that.loading = false;
        var datalist = res.data;
				var namelist = [];
				var indexlist = [];
				if(datalist.length>0){
					var sid = app.getCache('_glass_record_id');
					for(let i in datalist){
						if(sid>0){
							if(datalist[i].id==sid){
								that.detail = datalist[i]
								app.setCache('_glass_record_id',sid);
								that.st = i
							}
						}else{
							if(i==0){
								that.detail = datalist[i]
								// app.setCache('_glass_record_id',that.detail.id);
								that.st = i
							}
						}
						namelist.push(datalist[i].name)
						indexlist.push(i)
					}
				}
				that.datalist = datalist
				that.namelist = namelist
				that.indexlist = indexlist
				that.loaded();
      });
    },
		changetab:function(e){
			var index = e
			var that = this;
			that.st = index
			that.detail = this.datalist[index]
		},
		del:function(e){
			var that = this;
			var id = e.currentTarget.dataset.id
			app.confirm('确定删除该档案吗？',function(){
				that.loading = true;
				app.post("ApiGlass/del", {id:id}, function (data) {
				  if (data.status == 1) {
						that.detail = {}
				    app.success(data.msg);
						setTimeout(function () {
						  that.getdata()
						}, 1000);
				  } else {
						that.loading = false;
				    app.error(data.msg);
				  }
				});
			})
		},
		selectrecord:function(e){
			var id = e.currentTarget.dataset.id
			app.setCache('_glass_record_id',id)
			app.goback(true);
		}
  }
};
</script>
<style>
.content{background: #FFFFFF;padding: 30rpx;border-radius: 10rpx;color: #222222;width: 94%;margin: 20rpx 3% 0 3%;}
.row{padding: 20rpx 10rpx;display: flex;justify-content: flex-start;align-items: center;}
.mt{margin-top: 110rpx;}

.row .value{flex: 1;flex-wrap: wrap;}
.row .value-multi{flex:1;display: flex;justify-content: flex-start;align-items: center;}
.row-title{font-size: 30rpx;border-bottom: 1px solid #e2e2e2;padding-bottom: 30rpx;display: flex;justify-content: space-between;align-items: center;}
.row-title .r{font-size: 28rpx;color: #bbb;}
.value-multi .value-item{width: 45%;}
.bottom{border-top: 1rpx solid #e2e2e2;display: flex;justify-content: flex-end;padding-top: 30rpx;}
.bottom .btn{width: 150rpx; border-radius: 8rpx;color: #222222;border: 1px solid #e2e2e2;}
.bottom .btn1{background: #009688;color: #FFFFFF;}
.goadd{width: 90%;margin: 0 5%;}
.goadd .btn{border-radius: 60rpx;line-height: 90rpx;}

.remark{color: #bbb;}
.base{display: flex;justify-content: space-between;align-items: center;line-height: 46rpx;color: #bbb;font-size: 24rpx;}
.base .name{font-size: 32rpx;font-weight: bold;color: #222222;}

.lable{flex-shrink: 0;width: 160rpx;color:#bbb;}
.table-item{width: 33%;flex-shrink: 0;}
.table-body .row{color: #cdcdcd; font-size: 24rpx;display: flex;justify-content: space-around;text-align: right;}
.table-body .row.table-header{font-weight: bold;color: #222222;}
</style>