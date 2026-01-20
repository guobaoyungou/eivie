<template>
<view>
	<block v-if="isload">
		<form @submit="subform">
			<view class="form-box">
				<view class="form-item">
					<view class="f1">课程名称<text style="color:red"> *</text></view>
					<view class="f2"><input type="text" name="name" :value="info.name" placeholder="请填写课程名称" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item">
					<view class="f1">课程分类<text style="color:red"> *</text></view>
					<view class="f2" @tap="changeClistDialog">
            <text v-if="cids && cids.length>0" style="line-height: normal;">{{cnames}}</text>
            <text v-else style="color:#888">请选择</text><image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/>
          </view>
				</view>
			</view>
			<view class="form-box">
				<view class="form-item flex-col" style="border-bottom:0">
					<view class="f1">课程主图<text style="color:red"> *</text></view>
					<view class="f2">
						<view v-for="(item, index) in pic" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="pic"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
							<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
						</view>
						<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pic" data-pernum="1" v-if="pic.length==0"></view>
					</view>
					<input type="text" hidden="true" name="pic" :value="pic.join(',')" maxlength="-1"/>
				</view>
				<view class="form-item flex-col">
					<view class="f1">课程图片</view>
					<view class="f2" style="flex-wrap:wrap">
						<view v-for="(item, index) in pics" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="pics"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
							<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
						</view>
						<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pics" data-pernum="9" v-if="pics.length<5"></view>
					</view>
					<input type="text" hidden="true" name="pics" :value="pics.join(',')" maxlength="-1"></input>
				</view>
			</view>

			<view class="form-box">
        <view v-if="canlvprice" class="form-item">
          <view class="f1">会员价</view>
          <view class="f2">
            <radio-group class="radio-group" name="lvprice" @change="bindLvpriceChange">
              <label><radio value="1" :checked="info.lvprice==1?true:false"></radio> 开启</label> 
              <label><radio value="0" :checked="!info || info.lvprice==0?true:false"></radio> 关闭</label>
            </radio-group>
          </view>
        </view>
        <block v-if="!info || !info.lvprice || info.lvprice==0">
          <view class="form-item" style="height:80rpx;line-height:80rpx">
            <view class="f1">销售价（元）</view>
            <view class="f2"><input type="text" name="price" :value="info.price" placeholder="请填写销售价" placeholder-style="color:#888"></input></view>
          </view>
          <view class="form-item" style="height:80rpx;line-height:80rpx">
            <view class="f1">划线价（元）</view>
            <view class="f2"><input type="text" name="market_price" :value="info.market_price" placeholder="请填写划线价" placeholder-style="color:#888"></input></view>
          </view>
        </block>
        <block v-else>
          <block v-if="levellist">
            <view v-for="(level,index) in levellist" class="form-item" style="height:80rpx;line-height:80rpx">
              <view class="f1">{{level.name}}</view>
              <view class="f2"><input type="text" :name="'lvprice_data'+level.id" :value="info['lvprice_data'][level.id]['money_price']" placeholder="请填写价格" placeholder-style="color:#888"></input></view>
            </view>
          </block>
          <view class="form-item" style="height:80rpx;line-height:80rpx">
            <view class="f1">划线价（元）</view>
            <view class="f2"><input type="text" name="market_price" :value="info.market_price" placeholder="请填写划线价" placeholder-style="color:#888"></input></view>
          </view>
        </block>
			</view>
      
      <view class="form-box">
        <view class="form-item">
          <view class="f1">课程类型</view>
          <view class="f2">
            <radio-group class="radio-group" name="kctype" @change="bindKctypeChange">
              <label><radio value="1" :checked="!info || info.kctype==1?true:false"></radio>图文</label> 
              <label><radio value="3" :checked="info.kctype==3?true:false"></radio>视频</label>
            </radio-group>
          </view>
        </view>
        <view class="form-item" style="border: 0;">
          <view class="f1">免费内容</view>
          <view class="f2" style="color: #999;font-size: 24rpx;">免费内容在收费内容前面展示</view>
        </view>
        <view class="form-item" style="background-color: #f8f8f8;border-radius: 8rpx;">
          <view style="width: 100%;white-space: pre-wrap;line-height: 40rpx;">
            <textarea name="freecontent" :value="info.freecontent" placeholder="请输入免费内容"></textarea>
          </view>
        </view>
        <block v-if="info.kctype ==3">
          <view class="form-item flex-col" style="border-bottom:0">
          	<view class="f1">上传视频<text style="color:red"> *</text></view>
            <view style="color: #999;font-size: 24rpx;line-height: 40rpx;">视频链接必须为mp4格式的源链接，视频大小须在{{video_size}}以内</view>
          	<view class="f2">
          		<image :src="pre_url+'/static/img/uploadvideo.png'" style="width:200rpx;height:200rpx;background:#eee;" @tap="uploadvideo"></image><text v-if="video_url" style="padding-left:20rpx;color:#333">已上传短视频</text>
          		<input type="text" hidden="true" name="video_url" :value="video_url" maxlength="-1"/>
          	</view>
          </view>
          <view class="form-item">
            <view class="f1">视频时长</view>
            <view class="f2"><input type="text" name="video_duration" :value="info.video_duration" placeholder="请填写视频时长" placeholder-style="color:#888"></input></view>
          </view>
          <view v-if="custom.video_speed" class="form-item">
            <view class="f1">视频倍速</view>
            <view class="f2">
              <radio-group class="radio-group" name="isspeed" >
              	<label><radio value="1" :checked="info.isspeed==1?true:false"></radio> 开启</label> 
              	<label><radio value="0" :checked="!info || !info.isspeed || info.isspeed==0?true:false"></radio> 关闭</label>
              </radio-group>
            </view>
          </view>
          <view v-if="info.kctype != 1" class="form-item">
            <view class="f1">禁止快进</view>
            <view class="f2">
              <radio-group class="radio-group" name="isjinzhi" >
              	<label><radio value="1" :checked="info.isjinzhi==1?true:false"></radio> 开启</label> 
              	<label><radio value="0" :checked="!info || !info.isjinzhi || info.isjinzhi==0?true:false"></radio> 关闭</label>
              </radio-group>
            </view>
          </view>
        </block>
      </view>
			<view class="form-box">
				<view class="form-item">
					<view>状态<text style="color:red"> *</text></view>
					<view>
						<radio-group class="radio-group" name="status" @change="bindStatusChange">
							<label><radio value="1" :checked="info.status==1?true:false"></radio> 开启</label> 
							<label><radio value="0" :checked="!info || info.status==0?true:false"></radio> 关闭</label>
						</radio-group>
					</view>
				</view>
			</view>
			<view class="form-box">
				<view class="form-item flex-col">
					<text>课程详情</text>
					<view class="detailop"><view class="btn" @tap="detailAddtxt">+文本</view><view class="btn" @tap="detailAddpic">+图片</view><!-- <view class="btn" @tap="detailAddvideo">+视频</view> --></view>
					<view>
						<block v-for="(setData, index) in pagecontent" :key="index">
							<view class="detaildp">
							<view class="op"><view class="flex1"></view><view class="btn" @tap="detailMoveup" :data-index="index">上移</view><view class="btn" @tap="detailMovedown" :data-index="index">下移</view><view class="btn" @tap="detailMovedel" :data-index="index">删除</view></view>
							<view class="detailbox">
								<block v-if="setData.temp=='notice'">
									<dp-notice :params="setData.params" :data="setData.data"></dp-notice>
								</block>
								<block v-if="setData.temp=='banner'">
									<dp-banner :params="setData.params" :data="setData.data"></dp-banner> 
								</block>
								<block v-if="setData.temp=='search'">
									<dp-search :params="setData.params" :data="setData.data"></dp-search>
								</block>
								<block v-if="setData.temp=='text'">
									<dp-text :params="setData.params" :data="setData.data"></dp-text>
								</block>
								<block v-if="setData.temp=='title'">
									<dp-title :params="setData.params" :data="setData.data"></dp-title>
								</block>
								<block v-if="setData.temp=='dhlist'">
									<dp-dhlist :params="setData.params" :data="setData.data"></dp-dhlist>
								</block>
								<block v-if="setData.temp=='line'">
									<dp-line :params="setData.params" :data="setData.data"></dp-line>
								</block>
								<block v-if="setData.temp=='blank'">
									<dp-blank :params="setData.params" :data="setData.data"></dp-blank>
								</block>
								<block v-if="setData.temp=='menu'">
									<dp-menu :params="setData.params" :data="setData.data"></dp-menu> 
								</block>
								<block v-if="setData.temp=='map'">
									<dp-map :params="setData.params" :data="setData.data"></dp-map> 
								</block>
								<block v-if="setData.temp=='cube'">
									<dp-cube :params="setData.params" :data="setData.data"></dp-cube> 
								</block>
								<block v-if="setData.temp=='picture'">
									<dp-picture :params="setData.params" :data="setData.data"></dp-picture> 
								</block>
								<block v-if="setData.temp=='pictures'"> 
									<dp-pictures :params="setData.params" :data="setData.data"></dp-pictures> 
								</block>
								<block v-if="setData.temp=='video'">
									<dp-video :params="setData.params" :data="setData.data"></dp-video> 
								</block>
								<block v-if="setData.temp=='shop'">
									<dp-shop :params="setData.params" :data="setData.data" :shopinfo="setData.shopinfo"></dp-shop> 
								</block>
								<block v-if="setData.temp=='product'">
									<dp-product :params="setData.params" :data="setData.data" :menuindex="menuindex"></dp-product> 
								</block>
								<block v-if="setData.temp=='collage'">
									<dp-collage :params="setData.params" :data="setData.data"></dp-collage> 
								</block>
								<block v-if="setData.temp=='kanjia'">
									<dp-kanjia :params="setData.params" :data="setData.data"></dp-kanjia> 
								</block>
								<block v-if="setData.temp=='seckill'">
									<dp-seckill :params="setData.params" :data="setData.data"></dp-seckill> 
								</block>
								<block v-if="setData.temp=='scoreshop'">
									<dp-scoreshop :params="setData.params" :data="setData.data"></dp-scoreshop> 
								</block>
								<block v-if="setData.temp=='coupon'">
									<dp-coupon :params="setData.params" :data="setData.data"></dp-coupon> 
								</block>
								<block v-if="setData.temp=='article'">
									<dp-article :params="setData.params" :data="setData.data"></dp-article> 
								</block>
								<block v-if="setData.temp=='business'">
									<dp-business :params="setData.params" :data="setData.data"></dp-business> 
								</block>
								<block v-if="setData.temp=='liveroom'">
									<dp-liveroom :params="setData.params" :data="setData.data"></dp-liveroom> 
								</block>
								<block v-if="setData.temp=='button'">
									<dp-button :params="setData.params" :data="setData.data"></dp-button> 
								</block>
								<block v-if="setData.temp=='hotspot'">
									<dp-hotspot :params="setData.params" :data="setData.data"></dp-hotspot> 
								</block>
								<block v-if="setData.temp=='cover'">
									<dp-cover :params="setData.params" :data="setData.data"></dp-cover> 
								</block>
								<block v-if="setData.temp=='richtext'">
									<dp-richtext :params="setData.params" :data="setData.data" :content="setData.content"></dp-richtext> 
								</block>
								<block v-if="setData.temp=='form'">
									<dp-form :params="setData.params" :data="setData.data" :content="setData.content"></dp-form> 
								</block>
								<block v-if="setData.temp=='userinfo'">
									<dp-userinfo :params="setData.params" :data="setData.data" :content="setData.content"></dp-userinfo> 
								</block>
							</view>
							</view>
						</block>
					</view>
				</view>
			</view>
			<button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit">提交</button>
			<view style="height:50rpx"></view>
		</form>

		<view class="popup__container" v-if="clistshow">
			<view class="popup__overlay" @tap.stop="changeClistDialog"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">请选择课程分类</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="changeClistDialog"/>
				</view>
				<view class="popup__content">
					<block v-for="(item, index) in clist" :key="item.id">
						<view class="clist-item" @tap="cidsChange" :data-id="item.id">
							<view class="flex1">{{item.name}}</view>
							<view class="radio" :style="inArray(item.id,cids) ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
						<block v-for="(item2, index2) in item.child" :key="item2.id">
							<view class="clist-item" style="padding-left:80rpx" @tap="cidsChange" :data-id="item2.id">
								<view class="flex1" v-if="item.child.length-1==index2">└ {{item2.name}}</view>
								<view class="flex1" v-else>├ {{item2.name}}</view>
								<view class="radio" :style="inArray(item2.id,cids) ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
							</view>
							<block v-for="(item3, index3) in item2.child" :key="item3.id">
							<view class="clist-item" style="padding-left:160rpx" @tap="cidsChange" :data-id="item3.id">
								<view class="flex1" v-if="item2.child.length-1==index3">└ {{item3.name}}</view>
								<view class="flex1" v-else>├ {{item3.name}}</view>
								<view class="radio" :style="inArray(item3.id,cids) ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
							</view>
							</block>
						</block>
					</block>
				</view>
			</view>
		</view>
		<uni-popup id="dialogDetailtxt" ref="dialogDetailtxt" type="dialog">
			<view class="uni-popup-dialog">
				<view class="uni-dialog-title">
					<text class="uni-dialog-title-text">请输入文本内容</text>
				</view>
				<view class="uni-dialog-content">
					<textarea value="" placeholder="请输入文本内容" @input="catcheDetailtxt"></textarea>
				</view>
				<view class="uni-dialog-button-group">
					<view class="uni-dialog-button" @click="dialogDetailtxtClose">
						<text class="uni-dialog-button-text">取消</text>
					</view>
					<view class="uni-dialog-button uni-border-left" @click="dialogDetailtxtConfirm">
						<text class="uni-dialog-button-text uni-button-color">确定</text>
					</view>
				</view>
				<view class="uni-popup-dialog__close" @click="dialogDetailtxtClose">
					<span class="uni-popup-dialog__close-icon "></span>
				</view>
			</view>
		</uni-popup>
	</block>
	<view style="display:none">{{test}}</view>
	<loading v-if="loading"></loading>
	<popmsg ref="popmsg"></popmsg>
	<wxxieyi></wxxieyi>
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
			levellist:[],
			clist:[],
			cateArr:[],

			pic:[],
			pics:[],
			cids:[],
			cnames:'',
			clistshow:false,
			catche_detailtxt:'',
      custom:[],
      video_url:'',
      video_size:'50MB',
      canlvprice:false,
      test:''
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
			app.get('ApiKecheng/lecturerEditkecheng',{id:id}, function (res) {
				that.loading = false;
        if(res.status == 1){
          that.info = res.info;
          
          that.pagecontent = res.pagecontent;
          that.levellist   = res.levellist;
          that.clist       = res.clist;
          that.cateArr     = res.cateArr;
          that.pic         = res.pic;
          that.pics        = res.pics;
          that.cids        = res.cids;
          that.video_url   = res.video_url
          if(res.custom){
            that.custom = res.custom
          }
          if(res.video_size){
            that.video_size = res.video_size
          }
          if(res.canlvprice){
            that.canlvprice = res.canlvprice
          }
          that.getcnames();
          that.loaded();
        } else {
          if (res.msg) {
            app.alert(res.msg, function() {
              if (res.url) app.goto(res.url);
            });
          } else if (res.url) {
            app.goto(res.url);
          } else {
            app.alert('您无查看权限');
          }
        }
				
			});
		},
		setfield:function(e){
			var field = e.currentTarget.dataset.formidx;
			var value = e.detail.value;
			this.paramdata[field] = value;
		},
    subform: function (e) {
      var that = this;
      var formdata = e.detail.value;
      formdata.cid = that.cids.join(',');
      var id = that.opt.id ? that.opt.id : '';
      app.post('ApiKecheng/lecturerEditkecheng', {id:id,info:formdata,pagecontent:that.pagecontent}, function (res) {
        if (res.status == 1) {
          app.success(res.msg);
          setTimeout(function () {
            if(res.tourl){
              app.goto(res.tourl, 'redirect');
            }else{
              app.goback();
            }
          }, 800);
        } else {
          if (res.msg) {
            app.alert(res.msg, function() {
              if (res.url) app.goto(res.url);
            });
          } else if (res.url) {
            app.goto(res.url);
          } else {
            app.alert('您无查看权限');
          }
        }
      });
    },
		detailAddtxt:function(){
			this.$refs.dialogDetailtxt.open();
		},
		dialogDetailtxtClose:function(){
			this.$refs.dialogDetailtxt.close();
		},
		catcheDetailtxt:function(e){
			console.log(e)
			this.catche_detailtxt = e.detail.value;
		},
		dialogDetailtxtConfirm:function(e){
			var detailtxt = this.catche_detailtxt;
			console.log(detailtxt)
			var Mid = 'M' + new Date().getTime() + parseInt(Math.random() * 1000000);
			var pagecontent = this.pagecontent;
			pagecontent.push({"id":Mid,"temp":"text","params":{"content":detailtxt,"showcontent":detailtxt,"bgcolor":"#ffffff","fontsize":"14","lineheight":"20","letter_spacing":"0","bgpic":"","align":"left","color":"#000","margin_x":"0","margin_y":"0","padding_x":"10","padding_y":"5","quanxian":{"all":true},"platform":{"all":true}},"data":"","other":"","content":""});
			this.pagecontent = pagecontent;
			this.$refs.dialogDetailtxt.close();
		},
		detailAddpic:function(){
			var that = this;
			app.chooseImage(function(urls){
				var Mid = 'M' + new Date().getTime() + parseInt(Math.random() * 1000000);
				var pics = [];
				for(var i in urls){
					var picid = 'p' + new Date().getTime() + parseInt(Math.random() * 1000000);
					pics.push({"id":picid,"imgurl":urls[i],"hrefurl":"","option":"0"})
				}
				var pagecontent = that.pagecontent;
				pagecontent.push({"id":Mid,"temp":"picture","params":{"bgcolor":"#FFFFFF","margin_x":"0","margin_y":"0","padding_x":"10","padding_y":"0","quanxian":{"all":true},"platform":{"all":true}},"data":pics,"other":"","content":""});
				that.pagecontent = pagecontent;
			},9);
		},
		detailAddvideo:function(){
			var that = this;
			uni.chooseVideo({
        sourceType: ['album', 'camera'],
        success: function (res) {
          var tempFilePath = res.tempFilePath;
          app.showLoading('上传中');
          uni.uploadFile({
            url: app.globalData.baseurl + 'ApiImageupload/uploadImg/aid/' + app.globalData.aid + '/platform/' + app.globalData.platform + '/session_id/' + app.globalData.session_id,
            filePath: tempFilePath,
            name: 'file',
            success: function (res) {
              app.showLoading(false);
              var data = JSON.parse(res.data);
              if (data.status == 1) {
                that.video = data.url;
								var pagecontent = that.pagecontent;
								var Mid = 'M' + new Date().getTime() + parseInt(Math.random() * 1000000);
								pagecontent.push({"id":Mid,"temp":"video","params":{"bgcolor":"#FFFFFF","margin_x":"0","margin_y":"0","padding_x":"0","padding_y":"0","src":data.url,"quanxian":{"all":true},"platform":{"all":true}},"data":"","other":"","content":""});
								that.pagecontent = pagecontent;
              } else {
                app.alert(data.msg);
              }
            },
            fail: function (res) {
              app.showLoading(false);
              app.alert(res.errMsg);
            }
          });
        },
        fail: function (res) {
          console.log(res);
        }
      });
		},
		detailMoveup:function(e){
			var index = e.currentTarget.dataset.index;
			var pagecontent = this.pagecontent;
			if(index > 0)
				pagecontent[index] = pagecontent.splice(index-1, 1, pagecontent[index])[0];
		},
		detailMovedown:function(e){
			var index = e.currentTarget.dataset.index;
			var pagecontent = this.pagecontent;
			if(index < pagecontent.length-1)
				pagecontent[index] = pagecontent.splice(index+1, 1, pagecontent[index])[0];
		},
		detailMovedel:function(e){
			var index = e.currentTarget.dataset.index;
			var pagecontent = this.pagecontent;
			pagecontent.splice(index,1);
		},
		bindStatusChange:function(e){
			this.info.status = e.detail.value;
		},
    bindLvpriceChange:function(e){
    	this.info.lvprice = e.detail.value;
    },
    bindKctypeChange:function(e){
    	this.info.kctype = e.detail.value;
    },
		cidsChange:function(e){
			var clist = this.clist;
			var cids = this.cids;
			var cid = e.currentTarget.dataset.id;
			var newcids = [];
			var ischecked = false;
			for(var i in cids){
				if(cids[i] != cid){
					newcids= [cids[i]];
				}else{
					ischecked = true;
				}
			}
			if(ischecked==false){
				if(newcids.length >= 5){
					app.error('最多只能选择五个分类');return;
				}
				newcids = [cid];
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
    },
		changeClistDialog:function(){
			this.clistshow = !this.clistshow
		},
		uploadimg:function(e){
			var that = this;
			var pernum = parseInt(e.currentTarget.dataset.pernum);
			if(!pernum) pernum = 1;
			var field= e.currentTarget.dataset.field
			var pics = that[field]
			if(!pics) pics = [];
			app.chooseImage(function(urls){
				for(var i=0;i<urls.length;i++){
					pics.push(urls[i]);
				}
				if(field == 'pic') that.pic = pics;
				if(field == 'pics') that.pics = pics;
			},pernum);
		},
		removeimg:function(e){
			var that = this;
			var index= e.currentTarget.dataset.index
			var field= e.currentTarget.dataset.field
			if(field == 'pic'){
				var pics = that.pic
				pics.splice(index,1);
				that.pic = pics;
			}else if(field == 'pics'){
				var pics = that.pics
				pics.splice(index,1);
				that.pics = pics;
			}
		},
		scanprocode: function (d) {
			var that = this;
			if(app.globalData.platform == 'mp'){
				var jweixin = require('jweixin-module');
				jweixin.ready(function () {   //需在用户可能点击分享按钮前就先调用
					jweixin.scanQRCode({
						needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
						scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
						success: function (res) {
							var content = res.resultStr; // 当needResult 为 1 时，扫码返回的结果
							if(content.indexOf(',') > 0){
								content = content.split(',')[1];
							}
							that.info.procode = content
							that.test = Math.random();
						},
						fail:function(err){
							app.error(err.errMsg);
						}
					});
				});
			}else{
				uni.scanCode({
					success: function (res) {
						console.log(res);
						var content = res.result;
						that.info.procode = content
						that.test = Math.random();
					},
					fail:function(err){
						app.error(err.errMsg);
					}
				});
			}
		},
    uploadvideo: function () {
      var that = this;
      uni.chooseVideo({
        sourceType: ['album', 'camera'],
        maxDuration: 60,
        success: function (res) {
          var tempFilePath = res.tempFilePath;
          app.showLoading('上传中');
          uni.uploadFile({
            url: app.globalData.baseurl + 'ApiImageupload/uploadImg/aid/' + app.globalData.aid + '/platform/' + app.globalData.platform + '/session_id/' + app.globalData.session_id,
            filePath: tempFilePath,
            name: 'file',
            success: function (res) {
              app.showLoading(false);
              var data = JSON.parse(res.data);
              if (data.status == 1) {
                that.video_url = data.url;
              } else {
                app.alert(data.msg);
              }
            },
            fail: function (res) {
              app.showLoading(false);
              app.alert(res.errMsg);
            }
          });
        },
        fail: function (res) {
          console.log(res); //alert(res.errMsg);
        }
      });
    },
    removevideo:function(e){
    	var that = this;
    	var index= e.currentTarget.dataset.index
    	var field= e.currentTarget.dataset.field
    	if(field == 'video_url'){
    		var video_urls = that.video_url
    		video_urls.splice(index,1);
    		that.video_url = video_urls;
    	}
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
.class-text-limit{line-height: normal;max-width: 445rpx;width:auto;word-break: break-all;}
.ggtitle{height:60rpx;line-height:60rpx;color:#111;font-weight:bold;font-size:26rpx;display:flex;border-bottom:1px solid #f4f4f4}
.ggtitle .t1{width:200rpx;}
.ggcontent{line-height:60rpx;margin-top:10rpx;color:#111;font-size:26rpx;display:flex}
.ggcontent .t1{width:200rpx;display:flex;align-items:center;flex-shrink:0}
.ggcontent .t1 .edit{width:40rpx;height:40rpx}
.ggcontent .t2{display:flex;flex-wrap:wrap;align-items:center}

.ggbox{line-height:50rpx;}

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}

.clist-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.radio .radio-img{width:100%;height:100%;display:block}

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