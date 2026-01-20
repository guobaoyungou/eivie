<template>
<view>
	<block v-if="isload">
		<form @submit="subform">
			<view class="form-box">
				<view class="form-item">
					<view class="f1">工单分类<text style="color:red"> *</text></view>
					<view class="f2" @tap="changeClistDialog"><text v-if="cids.length>0">{{cnames}}</text><text v-else style="color:#888">请选择</text><image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
				</view>
			</view>
			<button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit">提交</button>
		</form>

		<view class="popup__container" v-if="clistshow">
			<view class="popup__overlay" @tap.stop="changeClistDialog"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">请选择工单分类</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="changeClistDialog"/>
				</view>
				<view class="popup__content">
					<block v-for="(item, index) in clist" :key="item.id">
						<view class="clist-item" :data-id="item.id">
							<view class="flex1">{{item.name}}</view>
						</view>
						<block v-for="(item2, index2) in item.child" :key="item2.id">
							<view class="clist-item" style="padding-left:80rpx" @tap="cidsChange" :data-id="item2.id">
								<view class="flex1" v-if="item.child.length-1==index2">└ {{item2.name}}</view>
								<view class="flex1" v-else>├ {{item2.name}}</view>
								<view class="radio" :style="inArray(item2.id,cids) ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
							</view>
							
						</block>
					</block>
				</view>
			</view>
		</view>
		

	</block>
	<loading v-if="loading"></loading>
	<popmsg ref="popmsg"></popmsg>
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
			clist:[],
			cids:[],
			cnames:'',
			clistshow:false,
			bid:0
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
  methods: {
		getdata:function(){
			var that = this;
			var id = that.opt.id ? that.opt.id : '';
			that.loading = true;
			app.get('ApiAdminWorkorder/updatecate',{id:id}, function (res) {
				that.loading = false;
				that.info = res.info;
				that.clist = res.clist;
				that.cateArr = res.cateArr;
				that.loaded();
			});
		},

    subform: function (e) {
      var that = this;
      var formdata = e.detail.value;
      formdata.cid = that.cids.join(',');
      var id = that.opt.id ? that.opt.id : '';
      app.post('ApiAdminWorkorder/updatecate', {id:id,info:formdata}, function (res) {
        if (res.status == 0) {
          app.error(res.msg);
        } else {
          app.success(res.msg);
          setTimeout(function () {
            app.goback(true);
          }, 1000);
        }
      });
    },
		changeClistDialog:function(){
			this.clistshow = !this.clistshow
		},
		cidsChange:function(e){
			var clist = this.clist;
			var cids = this.cids;
			var cid = e.currentTarget.dataset.id;
			var newcids = [];
			var ischecked = false;
			for(var i in cids){
				if(cids[i] != cid){
					//newcids.push(cids[i]);
				}else{
					ischecked = true;
				}
			}
			if(ischecked==false){
				if(newcids.length >= 1){
					//app.error('最多只能选择1个分类');return;
				}
				newcids.push(cid);
			}
			this.cids = newcids;
			this.getcnames();
		},
		getcnames:function(){
			var cateArr = this.cateArr;
			var cids = this.cids;
			var cnames = [];
			for(var i in cids){
				cnames.push(cateArr[cids[i]]);
			}
			this.cnames = cnames.join(',');
			this.clistshow  = false
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

.ggtitle{height:60rpx;line-height:60rpx;color:#111;font-weight:bold;font-size:26rpx;display:flex;border-bottom:1px solid #f4f4f4}
.ggtitle .t1{width:200rpx;}
.ggcontent{line-height:60rpx;margin-top:10rpx;color:#111;font-size:26rpx;display:flex}
.ggcontent .t1{width:200rpx;display:flex;align-items:center;flex-shrink:0}
.ggcontent .t1 .edit{width:40rpx;height:40rpx}
.ggcontent .t2{display:flex;flex-wrap:wrap;align-items:center}
.ggcontent .ggname{background:#f55;color:#fff;height:40rpx;line-height:40rpx;padding:0 20rpx;border-radius:8rpx;margin-right:20rpx;margin-bottom:10rpx;font-size:24rpx;position:relative}
.ggcontent .ggname .close{position:absolute;top:-14rpx;right:-14rpx;background:#fff;height:28rpx;width:28rpx;border-radius:14rpx}
.ggcontent .ggnameadd{background:#ccc;font-size:36rpx;color:#fff;height:40rpx;line-height:40rpx;padding:0 20rpx;border-radius:8rpx;margin-right:20rpx;margin-left:10rpx;position:relative}
.ggcontent .ggadd{font-size:26rpx;color:#558}

.ggbox{line-height:50rpx;}


.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}


.clist-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.radio .radio-img{width:100%;height:100%;display:block}

.freightitem{width:100%;height:60rpx;display:flex;align-items:center;margin-left:40rpx}
.freightitem .f1{color:#666;flex:1}

.detailop{display:flex;line-height:60rpx}
.detailop .btn{border:1px solid #ccc;margin-right:10rpx;padding:0 16rpx;color:#222;border-radius:10rpx}
.detaildp{position:relative;line-height:50rpx}
.detaildp .op{width:100%;display:flex;justify-content:flex-end;font-size:24rpx;height:60rpx;line-height:60rpx;margin-top:10rpx}
.detaildp .op .btn{background:rgba(0,0,0,0.4);margin-right:10rpx;padding:0 10rpx;color:#fff}
.detaildp .detailbox{border:2px dashed #00a0e9}

.uni-popup-dialog {width: 300px;border-radius: 5px;background-color: #fff;}
.uni-dialog-title {display: flex;flex-direction: row;justify-content: center;padding-top: 15px;padding-bottom: 5px;}
.uni-dialog-title-text {font-size: 16px;font-weight: 500;}
.uni-dialog-content {display: flex;flex-direction: row;justify-content: center;align-items: center;padding: 5px 15px 15px 15px;}
.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;}
.uni-dialog-button-group {display: flex;flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
.uni-dialog-button {display: flex;flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;}
.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
.uni-dialog-button-text {font-size: 14px;}
.uni-button-color {color: #007aff;}

.stockwarning{ position: absolute; right:0rpx; bottom:0;display:flex; align-items:center;font-size:24rpx;color:red;   }
.stockwarning image{  margin-right:10rpx}
</style>