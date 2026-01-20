<template>
<view>
	<block v-if="isload">
		<form @submit="subform">
			<view class="form-box">
				<view class="form-item">
					<view class="f1" @click="showExplain('1')"><text style="color:red"> *</text>出水口编号<image class="title-icon" :src="`${pre_url}/static/img/admin/jieshiicon.png`"></image></view>
					<view class="f2"><input type="number" name="outlet_num" :value="outlet_num" placeholder="出水口编号" placeholder-style="color:#888"></input></view>
				</view>
			</view>
      <view class="form-box dkdiv-item flex-y-center" >
        <view class="form-item">
          <view class="f1"><span style="color: red">*</span>积分抵扣设置</view>
          <view class="f2">
            <radio-group class="radio-group" name="scoredkmaxpercent_type" @change="scoredkmaxpercentTtypeChange">
              <label><radio value="0" :checked="scoredkmaxpercent_type==0?true:false"></radio> 跟随系统</label>
              <label style="margin-left: 20rpx;"><radio value="1" :checked="scoredkmaxpercent_type==1?true:false"></radio> 独立设置</label>
            </radio-group>
          </view>
        </view>
      </view>
      <view class="form-box" v-if="scoredkmaxpercent_type ==1">
        <view class="form-item">
          <view class="f1" @click="showExplain('4')"><text style="color:red"> *</text>积分抵扣%<image class="title-icon" :src="`${pre_url}/static/img/admin/jieshiicon.png`"></image></view>
          <view class="f2"><input type="text" name="scoredkmaxpercent" v-model="scoredkmaxpercent" placeholder="积分抵扣百分比" placeholder-style="color:#888" @input="changsetggname"></input></view>
        </view>
      </view>
      <view class="form-box">
        <view class="form-item">
          <view class="f1" @click="showExplain('2')"><text style="color:red"> *</text>出水口单价<image class="title-icon" :src="`${pre_url}/static/img/admin/jieshiicon.png`"></image></view>
          <view class="f2"><input type="text" name="outlet_price" v-model="outlet_price" placeholder="出水口单价（元/升）" placeholder-style="color:#888" @input="changsetggname"></input></view>
        </view>
      </view>
			<view class="form-box">
				<view class="form-item flex-col">
					<view class="f1" @click="showExplain('3')"><text style="color:red"> *</text>打水套餐<image class="title-icon" :src="`${pre_url}/static/img/admin/jieshiicon.png`"></image></view>
					<view class="flex-col">
						<view class="ggcontent">
							<view class="t2">
								<view class="ggname" v-for="(ggitem,index2) in price_combo" :key="index2" :data-index2="index2" :data-dashui_amount="ggitem.dashui_amount" @tap.stop="addggname" :data-title="ggitem.dashui_title">{{ggitem.dashui_title}}<image class="close" @tap.stop="delggname" :data-index2="index2" :src="pre_url+'/static/img/ico-del.png'"/></view>
                <view class="ggnameadd" @tap.stop="addggname" data-index2="-1">+</view>
							</view>
						</view>

					</view>
				</view>
			</view>

			<button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit">提交</button>
			<view style="height:50rpx"></view>
		</form>
		
		<uni-popup id="dialogInput" ref="dialogInput" type="dialog">
			<uni-popup-dialog mode="input" title="请输入套餐价格（元）" v-model="dashui_amount" placeholder="请输入套餐价格（元）" @confirm="setggname"></uni-popup-dialog>
		</uni-popup>
	</block>
	<loading v-if="loading"></loading>
</view>
</template>

<script>
var app = getApp();

export default {
  data() {
    return {
			isload:false,
			loading:false,
			pre_url:app.globalData.pre_url,
      info:{},
			pagecontent:[],
      water_happyti_device_id:0,
      id:0,
      price_combo:[],
      dashui_amount:'',
      outlet_num:'',
      outlet_price:'',
      scoredkmaxpercent:0,
      scoredkmaxpercent_type:0,
      ggindex2:-1,
      index2:-1,
      cftj:false
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
    this.water_happyti_device_id = this.opt.water_happyti_device_id || 0;
    this.id = this.opt.id || 0;
		this.getdata();
  },
  methods: {
		getdata:function(){
			var that = this;
			var id = that.opt.id ? that.opt.id : '';
			that.loading = true;
			app.get('ApiAdminWaterHappyti/outletEdit',{id:id,water_happyti_device_id:that.water_happyti_device_id}, function (res) {
				that.loading = false;
				that.info = res.info;
				that.outlet_num = res.info.outlet_num;
				that.outlet_price = res.info.outlet_price || '';
				that.scoredkmaxpercent = res.info.scoredkmaxpercent || 0;
				that.scoredkmaxpercent_type = res.info.scoredkmaxpercent_type || 0;
				that.price_combo = res.info.price_combo || [];
				that.water_happyti_device_id = res.info.water_happyti_device_id;

				that.loaded();
			});
		},
    subform: function (e) {
      var that = this;
      var formdata = e.detail.value;
      formdata['water_happyti_device_id'] = that.water_happyti_device_id;

      if(that.cftj){
        return;
      }

      if(!formdata.outlet_num){
        app.alert('请设置出水口编号');
        return;
      }

      if(!that.price_combo || that.price_combo == ''){
        app.alert('请设置打水套餐');
        return;
      }

      that.cftj = true;
      var id = that.opt.id ? that.opt.id : '';
      app.post('ApiAdminWaterHappyti/outletSave', {id:id,info:formdata,price_combo:that.price_combo}, function (res) {
        that.cftj = false;
        if (res.status == 0) {
          app.error(res.msg);
        } else {
          app.success(res.msg);
          setTimeout(function () {
            app.goto('outletList?id='+that.water_happyti_device_id, 'redirect');
          }, 1000);
        }
      });
    },

		addggname:function(e){
			var dashui_amount = e.currentTarget.dataset.dashui_amount;
      var index2 = e.currentTarget.dataset.index2;
			this.dashui_amount = dashui_amount;
			this.index2 = index2;
			this.$refs.dialogInput.open();
		},
		delggname:function(e){
			var that = this;
      var index2 = e.currentTarget.dataset.index2;
      var price_combo = this.price_combo;
      price_combo.splice(index2, 1);
      this.price_combo = price_combo;

		},
		setggname:function(done,val){
      if(val < 0.1){
        app.alert('价格不能小于0.1元');
        return;
      }

      var index2 = this.index2;
			var price_combo = this.price_combo;
			var outlet_price = this.outlet_price;
      if(!outlet_price || outlet_price == '' || outlet_price <=0){
        app.alert('请先设置出水口单价');
        return;
      }
      var dashui_sheng = (val / outlet_price).toFixed(2);
      var dashui_title = val+'元'+dashui_sheng+'升';;
			if(index2 == -1){ //新增规格名称
				var items = price_combo;
				items.push({dashui_title:dashui_title,dashui_amount:val,dashui_sheng});
				this.price_combo = items;
			}else{ //修改规格名称
        var dashui_sheng = (val / outlet_price).toFixed(2);
        price_combo[index2].dashui_amount = val;
        price_combo[index2].dashui_sheng = dashui_sheng;
        price_combo[index2].dashui_title = dashui_title;
				this.price_combo = price_combo;
			}
      this.dashui_amount= '';
			this.$refs.dialogInput.close();

			//this.getgglist();
		},

    changsetggname:function(){

      var price_combo = this.price_combo;
      var outlet_price = this.outlet_price;

      for (let i = 0;i<price_combo.length;i++){
        var dashui_amount = price_combo[i].dashui_amount;
        var dashui_sheng = (dashui_amount / outlet_price).toFixed(2);
        var dashui_title = dashui_amount+'元'+dashui_sheng+'升';
        price_combo[i].dashui_sheng = dashui_sheng;
        price_combo[i].dashui_title = dashui_title;
      }
      if(!outlet_price || outlet_price == '' || outlet_price <=0){
        this.price_combo = [];
      }else {
        this.price_combo = price_combo;
      }
    },

    showExplain(n){
      var text = '';
      if(n == 1){
        text = this.info.text1;
      }else if(n == 2){
        text = this.info.text2;
      }else if(n == 3){
        text = this.info.text3;
      }

      uni.showModal({
        title: '解释说明',
        content: text,
        showCancel:false
      });
    },
    scoredkmaxpercentTtypeChange: function(e) {
      var that = this;
      that.scoredkmaxpercent_type = e.detail.value;
    },
  }
};
</script>
<style>
radio{transform: scale(0.6);}
checkbox{transform: scale(0.6);}
.form-box{ padding:2rpx 24rpx 0 24rpx; background: #fff;margin: 24rpx;border-radius: 10rpx}
.form-item{ line-height: 100rpx; display: flex;justify-content: space-between;border-bottom:1px solid #eee }
.form-item .f1{color:#222;width:200rpx;flex-shrink:0}
.form-item .f2{display:flex;align-items:center}
.form-box .form-item:last-child{ border:none}
.form-box .flex-col{padding-bottom:20rpx}
.form-item input{ width: 100%; border: none;color:#111;font-size:28rpx; text-align: right}
.form-item textarea{ width:100%;min-height:200rpx;padding:20rpx 0;border: none;}
.form-item .upload_pic{ margin:50rpx 0;background: #F3F3F3;width:90rpx;height:90rpx; text-align: center  }
.form-item .upload_pic image{ width: 32rpx;height: 32rpx; }
.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none; }

.ggcontent{line-height:60rpx;margin-top:10rpx;color:#111;font-size:26rpx;display:flex}
.ggcontent .t1{width:200rpx;display:flex;align-items:center;flex-shrink:0}
.ggcontent .t1 .edit{width:40rpx;height:40rpx}
.ggcontent .t2{display:flex;flex-wrap:wrap;align-items:center}
.ggcontent .ggname{background:#f55;color:#fff;height:40rpx;line-height:40rpx;padding:0 20rpx;border-radius:8rpx;margin-right:20rpx;margin-bottom:10rpx;font-size:24rpx;position:relative}
.ggcontent .ggname .close{position:absolute;top:-14rpx;right:-14rpx;background:#fff;height:28rpx;width:28rpx;border-radius:14rpx}
.ggnameadd{text-align: center;background:#ccc;font-size:36rpx;color:#fff;height:45rpx;line-height:40rpx;padding:0 20rpx;border-radius:8rpx;margin-right:20rpx;margin-left:10rpx;position:relative}
.layui-imgbox-img>image{max-width:100%;}
.title-icon{width: 30rpx;height: 30rpx;margin-left: 10rpx;}
</style>