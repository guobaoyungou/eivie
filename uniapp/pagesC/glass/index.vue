<template>
<view class="container">
	<block v-if="isload">
		<block v-if="datalist.length>0">
			<dd-tab :itemdata="namelist" :itemst="indexlist" :st="st" :isfixed="true" @changetab="changetab"></dd-tab>
			<view class="box mt">
				<view class="content">
					<view class="remark">
						备注：{{detail.remark}}
					</view>
				</view>
				<view class="content">
					<view class="table-body">
						<view class="row table-header">
							<view class="table-item lable" style="color: #222222;">定制参数</view>
							<view class="table-item">R</view>
							<view class="table-item">L</view>
						</view>
            <view class="row">
              <view class="table-item lable">球镜(SPH)</view>
              <view class="table-item">{{detail.sph_right}}</view>
              <view class="table-item">{{detail.sph_left}}</view>
            </view>
            <view class="row">
              <view class="table-item lable">柱镜(CYL)</view>
              <view class="table-item">{{detail.cyl_right}}</view>
              <view class="table-item">{{detail.cyl_left}}</view>
            </view>
            <view class="row">
              <view class="table-item lable">轴位(AX)</view>
              <view class="table-item">{{detail.ax_right}}</view>
              <view class="table-item">{{detail.ax_left}}</view>
            </view>
            <view class="row">
              <view class="table-item lable">下加光(ADD)</view>
              <view class="table-item">{{detail.add_right}}</view>
              <view class="table-item">{{detail.add_left}}</view>
            </view>
            <view class="row">
              <view class="table-item lable">数量(QTY)</view>
              <view class="table-item">{{detail.qty_right}}</view>
              <view class="table-item">{{detail.qty_left}}</view>
            </view>
            <view class="row">
              <block v-if="detail.double_ipd">
								<view class="table-item lable">瞳距(PD)</view>
								<view class="table-item">{{detail.ipd_right }}</view>
								<view class="table-item">{{detail.ipd_left}}</view>
              </block>
              <block v-else>
                <view class="table-item lable">瞳距(PD)</view>
                <view class="table-item"  style="flex: 1">{{detail.pd}}</view>
              </block>
            </view>
            <view class="row">
              <block v-if="detail.double_npd">
								<view class="table-item lable">近瞳距(NPD)</view>
								<view class="table-item">{{detail.npd_right }}</view>
								<view class="table-item">{{detail.npd_left}}</view>
              </block>
              <block v-else>
                <view class="table-item lable">近瞳距(NPD)</view>
                <view class="table-item"  style="flex: 1">{{detail.npd}}</view>
              </block>
            </view>
            <view class="row" v-if="detail.seg_right || detail.seg_left">
              <view class="table-item lable">瞳高</view>
              <view class="table-item">{{detail.seg_right}}</view>
              <view class="table-item">{{detail.seg_left}}</view>
            </view>

            <view class="row"  v-if="detail.corrlen_right || detail.corrlen_left">
              <view class="table-item lable">通道</view>
              <view class="table-item">{{detail.corrlen_right}}</view>
              <view class="table-item">{{detail.corrlen_left}}</view>
            </view>

            <view class="row" v-if="detail.frame_number_right || detail.frame_number_lef">
              <view class="table-item lable">镜框型号</view>
              <view class="table-item">{{detail.frame_number_right}}</view>
              <view class="table-item">{{detail.frame_number_left}}</view>
            </view>
						<view class="row" v-if="detail.frame_firm_right || detail.frame_firm_left">
							<view class="table-item lable">厂商</view>
							<view class="table-item">{{detail.frame_firm_right}}</view>
							<view class="table-item">{{detail.frame_firm_left}}</view>
						</view>
            <view class="row" v-if="detail.frame_color_right || detail.frame_color_left">
              <view class="table-item lable">颜色</view>
              <view class="table-item">{{detail.frame_color_right}}</view>
              <view class="table-item">{{detail.frame_color_left}}</view>
            </view>
            <view class="row" v-if="detail.frame_type_right || detail.frame_type_left">
              <view class="table-item lable">镜框类型</view>
              <view class="table-item">{{detail.frame_type_right}}</view>
              <view class="table-item">{{detail.frame_type_left}}</view>
            </view>
            <view class="row" v-if="detail.hbox_right || detail.hbox_left">
              <view class="table-item lable">镜高</view>
              <view class="table-item">{{detail.hbox_right}}</view>
              <view class="table-item">{{detail.hbox_left}}</view>
            </view>
            <view class="row" v-if="detail.vbox_right || detail.vbox_left">
              <view class="table-item lable">镜宽</view>
              <view class="table-item">{{detail.vbox_right}}</view>
              <view class="table-item">{{detail.vbox_left}}</view>
            </view>
            <view class="row" v-if="detail.dbl_right || detail.dbl_left">
              <view class="table-item lable">鼻梁距</view>
              <view class="table-item">{{detail.dbl_right}}</view>
              <view class="table-item">{{detail.dbl_left}}</view>
            </view>
            <view class="row" v-if="detail.fed_right || detail.fed_left">
              <view class="table-item lable">有效直径</view>
              <view class="table-item">{{detail.fed_right}}</view>
              <view class="table-item">{{detail.fed_left}}</view>
            </view>
            <view class="row" v-if="detail.fwd_right || detail.fwd_left">
              <view class="table-item lable">眼镜总宽</view>
              <view class="table-item">{{detail.fwd_right}}</view>
              <view class="table-item">{{detail.fwd_left}}</view>
            </view>
            <view class="row" v-if="detail.panto_right || detail.panto_left">
              <view class="table-item lable">前倾角</view>
              <view class="table-item">{{detail.panto_right}}</view>
              <view class="table-item">{{detail.panto_left}}</view>
            </view>
            <view class="row" v-if="detail.ztilt_right || detail.ztilt_left">
              <view class="table-item lable">镜面倾斜角</view>
              <view class="table-item">{{detail.ztilt_right}}</view>
              <view class="table-item">{{detail.ztilt_left}}</view>
            </view>
            <view class="row" v-if="detail.bvd_right || detail.bvd_left">
              <view class="table-item lable">镜眼距</view>
              <view class="table-item">{{detail.bvd_right}}</view>
              <view class="table-item">{{detail.bvd_left}}</view>
            </view>
            <view class="row" v-if="detail.prvm_x_right || detail.prvm_x_left">
              <view class="table-item lable">水平棱镜</view>
              <view class="table-item">{{detail.prvm_x_right}}</view>
              <view class="table-item">{{detail.prvm_x_left}}</view>
            </view>
            <view class="row" v-if="detail.prva_x_right || detail.prva_x_left">
              <view class="table-item lable">水平底向</view>
              <view class="table-item">{{detail.prva_x_right}}</view>
              <view class="table-item">{{detail.prva_x_left}}</view>
            </view>
            <view class="row" v-if="detail.prvm_y_right || detail.prvm_y_left">
              <view class="table-item lable">垂直棱镜</view>
              <view class="table-item">{{detail.prvm_y_right}}</view>
              <view class="table-item">{{detail.prvm_y_left}}</view>
            </view>
            <view class="row" v-if="detail.prva_y_right || detail.prva_y_left">
              <view class="table-item lable">垂直底向</view>
              <view class="table-item">{{detail.prva_y_right}}</view>
              <view class="table-item">{{detail.prva_y_left}}</view>
            </view>
            <view class="row" v-if="detail.fcoat_right || detail.fcoat_left">
              <view class="table-item lable">镀膜</view>
              <view class="table-item">{{detail.fcoat_right}}</view>
              <view class="table-item">{{detail.fcoat_left}}</view>
            </view>
            <view class="row" v-if="detail.tint_right || detail.tint_left">
              <view class="table-item lable">染色</view>
              <view class="table-item">{{detail.tint_right}}</view>
              <view class="table-item">{{detail.tint_left}}</view>
            </view>
						<view class="row" v-if="detail.colr_right || detail.colr_left">
						  <view class="table-item lable">颜色</view>
						  <view class="table-item">{{detail.colr_right}}</view>
						  <view class="table-item">{{detail.colr_left}}</view>
						</view>
            <view class="row" v-if="detail.crib_right || detail.crib_left">
              <view class="table-item lable">镜片直径</view>
              <view class="table-item">{{detail.crib_right}}</view>
              <view class="table-item">{{detail.crib_left}}</view>
            </view>
            <view class="row" v-if="detail.minedg_right || detail.minedg_left">
              <view class="table-item lable">镜片边缘厚度</view>
              <view class="table-item">{{detail.minedg_right}}</view>
              <view class="table-item">{{detail.minedg_left}}</view>
            </view>
            <view class="row" v-if="detail.minctr_right || detail.minctr_left">
              <view class="table-item lable">镜片中心厚度</view>
              <view class="table-item">{{detail.minctr_right}}</view>
              <view class="table-item">{{detail.minctr_left}}</view>
            </view>
            <view class="row" v-if="detail.mbase_right || detail.mbase_left">
              <view class="table-item lable">镜片基弯</view>
              <view class="table-item">{{detail.mbase_right}}</view>
              <view class="table-item">{{detail.mbase_left}}</view>
            </view>
            <view class="row" v-if="detail.inkmask_right || detail.inkmask_left">
              <view class="table-item lable">标记</view>
              <view class="table-item">{{detail.inkmask_right}}</view>
              <view class="table-item">{{detail.inkmask_left}}</view>
            </view>
            <view class="row" v-if="detail.bcerin_right || detail.bcerin_left">
              <view class="table-item lable">BCERIN</view>
              <view class="table-item">{{detail.bcerin_right}}</view>
              <view class="table-item">{{detail.bcerin_left}}</view>
            </view>
            <view class="row" v-if="detail.bcerup_right || detail.bcerup_left">
              <view class="table-item lable">BCERUP</view>
              <view class="table-item">{{detail.bcerup_right}}</view>
              <view class="table-item">{{detail.bcerup_left}}</view>
            </view>
					</view>
					<view class="bottom">
						<button class="btn" @tap="del" :data-id="detail.id">删除</button>
						<button class="btn" @tap="goto" :data-url="'add?id='+detail.id">编辑</button>
						<button class="btn" @tap="goto" data-url="add">添加</button>
						<button class="btn btn1" v-if="confirm==1" @tap="selectrecord" :data-id="detail.id" :data-qty="detail.qty_left + detail.qty_right" :style="{background:t('color1'),color:'#ffffff'}">选择</button>
					</view>
				</view>
			</view>
		</block>
		<block v-else>
			<nodata text="暂无定制记录"></nodata>
			<view class="goadd">
				<button class="btn" @tap="goto" data-url="add" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)',color:'#ffffff'}" form-type="submit" data-type="1">去定制</button>
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
			dkmid:0,
			prodata:[]
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.confirm = this.opt.c || 0;
		this.sid = this.opt.sid || 0;
		this.dkmid = this.opt.dkmid || 0;
		this.prodata = this.opt.prodata;
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
      app.post('ApiGlassCustom/myCustom', {pagenum:1,listrow:100,dkmid:that.dkmid}, function (res) {
				that.loading = false;
        var datalist = res.data;
				var namelist = [];
				var indexlist = [];
				if(datalist.length>0){
					var sid = app.getCache('_glass_custom_id');
					for(let i in datalist){
						if(sid>0){
							if(datalist[i].id==sid){
								that.detail = datalist[i]
								app.setCache('_glass_custom_id',sid);
								that.st = i
							}
						}else{
							if(i==0){
								that.detail = datalist[i]
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
			app.confirm('确定删除该定制吗？',function(){
				that.loading = true;
				app.post("ApiGlassCustom/del", {id:id}, function (data) {
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
			var qty = e.currentTarget.dataset.qty
			app.setCache('_glass_custom_id',id)
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
.table-item{width: 33%;flex-shrink: 0;text-align: center}
.table-body .row{color: #cdcdcd; font-size: 24rpx;display: flex;justify-content: space-around;text-align: right;}
.table-body .row.table-header{font-weight: bold;color: #222222;}
</style>