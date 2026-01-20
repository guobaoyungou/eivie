<template>
<view>
	<block v-if="isload">
		<form @submit="subform">
			<view class="form-box">
				<view class="form-item flex-col" style="border-bottom:0">
					<view class="f1" style="width:100%"><!-- <text style="color:red;padding-right:6rpx;">*</text> -->请选择{{mingpiantext}}背景</view>
					<block v-if="show_background_category ==1">	
						<dd-tab :itemdata="bgdata.category_name" :itemst="bgdata.category_key_arr" :st="bgcid"  @changetab="changebgcategory"></dd-tab>
						<scroll-view class="scroll-view" scroll-x="true" >
						<view class="bglist flex" v-if="bgcid !=-1">
							<block v-for="(item,index) in this_bglist" >
								<view class="bg" v-if="item.type ==0" :style="{backgroundColor:item.bgcolor}" @click="selectBg(item.id)">
									<view class="mengceng" v-if="item.id == bgid">
										<view class="mc"><view class="checked" :style="{color:t('color1')}">✔</view></view>
									</view>
								</view>
								<view class="bg" v-else @click="selectBg(item.id)">
									<view class="bgpic">
										<image  class="bgpic" :src="item.bgpic" mode="widthFix"></image>
									</view>
									<view class="mengceng" style="z-index: 10;" v-if="item.id == bgid">
										<view class="mc"><view class="checked" :style="{color:t('color1')}">✔</view></view>
									</view>
								</view>
							</block>
						</view>
						</scroll-view>	
					</block>
					<block v-if="iscustom==1">
						<view class="f2" style="flex-wrap: wrap;">
							<view v-for="(item, index) in bgpic" :key="index" class="layui-imgbox">
								<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="bgpic"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
								<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
							</view>
							<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadbgpic" data-field="bgpic" data-pernum="1" v-if="bgpic.length <= mingpian_banner_length"></view>
						</view>
						<input type="text" hidden="true" name="bgpic" :value="bgpic.join(',')" maxlength="-1"/>
					</block>
					
				</view>
			</view>
			<view class="form-box">
				<view class="form-item flex-col" style="border-bottom:0">
					<view class="f1" style="width:100%"><text style="color:red;padding-right:6rpx;">*</text>请上传个人照片（上传正方形照片）</view>
					<view class="f2">
						<view v-for="(item, index) in headimg" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="headimg"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
							<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
						</view>
						<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="headimg" data-pernum="1" v-if="headimg.length==0"></view>
					</view>
					<input type="text" hidden="true" name="headimg" :value="headimg.join(',')" maxlength="-1"/>
				</view>
			</view>
			<view class="form-box">
				<view class="form-item">
					<view class="f1"><text style="color:red;padding-right:6rpx;">*</text>请输入姓名</view>
					<view class="f2"><input type="text" name="realname" :value="info.realname" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item flex-col" style="border-bottom:0">
					<view class="f1" style="width:100%"><text style="color:red;padding-right:6rpx;">*</text>请添加头衔(最多添加3个)</view>
					<view class="f2">
						<input type="text" name="touxian1" :value="info.touxian1" placeholder="公司名称+职务，如：某科技有限公司|总经理" placeholder-style="color:#888" style="text-align:left"></input>
					</view>
					<view class="f2" style="margin-top:10rpx">
						<input type="text" name="touxian2" :value="info.touxian2" placeholder="公司名称+职务，如：某科技有限公司|总经理" placeholder-style="color:#888" style="text-align:left"></input>
					</view>
					<view class="f2" style="margin-top:10rpx">
						<input type="text" name="touxian3" :value="info.touxian3" placeholder="公司名称+职务，如：某科技有限公司|总经理" placeholder-style="color:#888" style="text-align:left"></input>
					</view>
				</view>
			</view>

			<view class="form-box">
				<view class="form-item flex-col" style="border-bottom:0">
					<view class="f1" style="width:100%">联系信息<text style="font-size:24rpx;color:#888">(前三项将显示在{{mingpiantext}}封面上)</text></view>
				</view>
				<block v-for="(item,index) in field_list" :key="index" v-if="item.isshow==1">
				<view class="form-item">
					<view class="f1"><text style="color:red;padding-right:6rpx;">{{item.required==1 ? '*' : ' '}}</text>请输入{{item.name}}</view>
					<view class="f2">
            <input v-if="item.isadd && item.isadd == '1'" type="text" :name="index" :value="addfields?addfields[index]:''" placeholder-style="color:#888"></input>
            <input v-else type="text" :name="index" :value="index=='address' ? address : info[index]" placeholder-style="color:#888"></input>
          </view>
					<view class="f3" v-if="index=='address'" @tap="selectzuobiao" style="color:#58e;font-size:24rpx;margin-left:6rpx">选择位置</view>
				</view>
				</block>
				<input name="latitude" :value="latitude" hidden="true"/>
				<input name="longitude" :value="longitude" hidden="true"/>
			</view>

			<view class="form-box">
				<view class="form-item flex-col">
					<text>个人简介</text>
					<view class="detailop"><view class="btn" @tap="detailAddtxt">+文本</view><view class="btn" @tap="detailAddpic">+图片</view><view class="btn" @tap="detailAddvideo">+视频</view></view>
					<view>
						<block v-for="(setData, index) in pagecontent" :key="index">
							<view class="detaildp">
							<view class="op">
								<view class="flex1"></view>
								<view class="btn" @tap="detailMoveup" :data-index="index">上移</view>
								<view class="btn" @tap="detailMovedown" :data-index="index">下移</view>
								<view class="btn" @tap="detailEdit" :data-index="index" v-if="setData.temp=='text'">编辑</view>
								<view class="btn" @tap="detailMovedel" :data-index="index">删除</view>
							</view>
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
			<view class="form-box">
				<view class="form-item flex-col" style="border-bottom:0">
					<view class="f1" style="width:100%">自定义分享标题(不填写则按框中内容显示)</view>
					<view class="f2">
						<input type="text" name="sharetitle" :value="info.sharetitle" :placeholder="'您好，这是我的'+mingpiantext+'，望惠存！'" placeholder-style="color:#888" style="text-align:left"></input>
					</view>
				</view>
			</view>
			<button class="savebtn" form-type="submit">提交</button>
			<view style="height:50rpx"></view>
		</form>


		<view class="popup__container" v-if="bglistshow">
			<view class="popup__overlay" @tap.stop="changeBglistDialog"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">请选择{{mingpiantext}}背景图</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="changeBglistDialog"/>
				</view>
				<view class="popup__content">
					<block v-for="(item, index) in bglist" :key="item">
						<view class="clist-item flex-y-center" @tap="bgChange" :data-pic="item">
							<view class="flex1"><img :src="item" class="clist-image" mode="widthFix"/></view>
              <view v-if="mingpian_banner" class="radio" :style="inArray(item,bgpic) ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
							<view v-else class="radio" :style="bgpic.join(',')==item ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
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
					<scroll-view class="uni-dialog-scroll" scroll-y="true">
						<view>
							<textarea :value="edit_text" placeholder="请输入文本内容" auto-height  @input="catcheDetailtxt"></textarea>
						</view>
					</scroll-view>
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
			field_list:[],
			pagecontent:[],
			bgpic:[],
			headimg:[],
			bglist:[],
			bglistshow:false,
			address:'',
      latitude:'',
      longitude:'',
			test:'',
      edit_text:'',
			edit_text_index:'',
      mingpiantext:'名片',
      addfields:{},
	  iscustom:1,//显示自定义，默认显示
	  bgdata:[],//背景图数据，分类
	  show_background_category:0,//显示背景图分类
	  bgid:0,//选中的背景id
	  bgcid:0,//选中的背景分类id
	  this_bglist:[],//当前分类下的背景列表
      mingpian_banner:false,
      mingpian_banner_length:0,
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
  methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			app.get('ApiMingpian/edit',{}, function (res) {
				that.loading = false;
        if (res.status == 1) {
          that.mingpiantext = that.t('名片');
          uni.setNavigationBarTitle({
          	title: that.mingpiantext+'编辑'
          });
          
          that.info = res.info || {};
          that.address = res.info.address || '';
          that.latitude = res.info.latitude || '';
          that.longitude = res.info.longitude || '';
          that.mingpian_banner = res.mingpian_banner || false;
          that.mingpian_banner_length = res.mingpian_banner_length || 0;
          if(that.info['bgpic']){
            that.bgpic = (that.info['bgpic']).split(',');
          }else{
            that.bgpic = [];
          }
          if(that.info['headimg']){
            that.headimg = (that.info['headimg']).split(',');
          }else{
            that.headimg = [];
          }
          that.field_list = res.field_list;
          that.pagecontent = res.pagecontent;
          that.bglist = res.bglist;
          if(res.addfields){
            that.addfields = res.addfields;
          }
		 
		  if( res.show_background_category ==1){
			that.show_background_category = res.show_background_category
			that.iscustom = 0;
			var bgdata = res.bgdata;
			var bglist = bgdata.bglist;
			var category_key_arr = bgdata.category_key_arr;
			var bgcid =that.info.bgcid ?that.info.bgcid:category_key_arr[0]?category_key_arr[0]:'-1';
			that.bgcid = bgcid;
			that.bgdata = res.bgdata;
			that.this_bglist = bgdata.bglist[bgcid];
			if(bgcid >0){
				that.bgid = that.info.bgid?that.info.bgid:that.this_bglist[0]?that.this_bglist[0].id:'';
			}else{
				that.iscustom = 1;
			}
		  }
          that.loaded();
        }else{
        		if(res.msg) {
        			app.alert(res.msg, function() {
        				if (res.url) app.goto(res.url);
        			});
        		} else if(res.url) {
        			app.goto(res.url);
        		} else {
        			app.alert('您无查看权限',function(){
                app.goback();
              });
        		}
        	}
			});
		},
    subform: function (e) {
      var that = this;
      var formdata = e.detail.value;
			console.log(formdata)
			if(formdata.headimg == ''){
				app.alert('请上传个人照片');return;
			}
			if(formdata.realname == ''){
				app.alert('请输入姓名');return;
			}
			if(formdata.touxian1 == '' && formdata.touxian2 == '' && formdata.touxian3 == ''){
				app.alert('请填写至少一个头衔');return;
			}
			for(var i in that.field_list){
				var thisfield = that.field_list[i];
				console.log(i)
				console.log(thisfield)
				if(thisfield.required == 1 && formdata[i] == ''){
					app.alert('请输入'+thisfield.name);return;
				}
			}
			if(that.opt.huomacode)formdata.huomacode = that.opt.huomacode
			if(that.show_background_category ==1){
				formdata.bgcid = that.bgcid;
				formdata.bgid = that.bgid;
			}
		
			app.showLoading('保存中');
      app.post('ApiMingpian/save', {info:formdata,pagecontent:that.pagecontent}, function (res) {
        if (res.status == 0) {
          app.error(res.msg);
        } else {
          app.success(res.msg);
          setTimeout(function () {
            app.goto('index', 'redirect');
          }, 1000);
        }
      });
    },
		uploadbgpic:function(){
      var that = this;
			var bglist = this.bglist;
			if(bglist.length > 0){
				uni.showActionSheet({
					itemList: ['选择系统背景','自己上传背景（700×480像素）'],
					success: function(res) {
						console.log(res.tapIndex)
						if (res.tapIndex == 0) {
							that.bglistshow = true;
						}else{
							that.uploadimg({currentTarget:{dataset:{field:"bgpic",pernum:"1"}}});
						}
					}
				});
			}else{
				that.uploadimg({currentTarget:{dataset:{field:"bgpic",pernum:"1"}}});
			}
		},
		bgChange:function(e){
      if(this.bgpic.length >= 9){
        app.alert('多上传9张');return;
      }
			var pic = e.currentTarget.dataset.pic;
      if(this.mingpian_banner){
        if (this.bgpic.includes(pic)) {
          let index = this.bgpic.indexOf(pic);
          if (index !== -1) {
            this.bgpic.splice(index, 1);
          }
        } else {
          this.bgpic.push(pic);
        }
      }else {
        this.bgpic = [pic];
        this.bglistshow = false;
      }
		},
		detailAddtxt:function(){
			this.edit_text = '';
			this.edit_text_index = '';
			this.$refs.dialogDetailtxt.open();
		},
		dialogDetailtxtClose:function(){
			console.log(11111);
			this.edit_text = '';
			this.$refs.dialogDetailtxt.close();
		},
		catcheDetailtxt:function(e){
			console.log(e)
			this.catche_detailtxt = e.detail.value;
		},
		dialogDetailtxtConfirm:function(e){
			var detailtxt = this.catche_detailtxt;
			console.log(detailtxt)
			//判断是否编辑
			let index = this.edit_text_index;
			if(index !=='' && index >= 0){
				let pageparams = this.pagecontent[index].params;
				pageparams.content = detailtxt;
				pageparams.showcontent = detailtxt;
				this.$refs.dialogDetailtxt.close();
				this.edit_text = '';
				this.edit_text_index = '';
				return;
			}
			var Mid = 'M' + new Date().getTime() + parseInt(Math.random() * 1000000);
			var pagecontent = this.pagecontent;
			pagecontent.push({"id":Mid,"temp":"text","params":{"content":detailtxt,"showcontent":detailtxt,"bgcolor":"#ffffff","fontsize":"14","lineheight":"20","letter_spacing":"0","bgpic":"","align":"left","color":"#000","margin_x":"0","margin_y":"0","padding_x":"5","padding_y":"5","quanxian":{"all":true},"platform":{"all":true}},"data":"","other":"","content":""});
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
				pagecontent.push({"id":Mid,"temp":"picture","params":{"bgcolor":"#FFFFFF","margin_x":"0","margin_y":"0","padding_x":"0","padding_y":"0","quanxian":{"all":true},"platform":{"all":true}},"data":pics,"other":"","content":""});
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
          console.log(res); //alert(res.errMsg);
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
		detailEdit:function(e){
      var index = e.currentTarget.dataset.index;
      var pagecontent = this.pagecontent;
      this.edit_text = pagecontent[index].params.showcontent;
			this.edit_text_index = index;
			this.$refs.dialogDetailtxt.open();
		},
		changeBglistDialog:function(){
			this.bglistshow = !this.bglistshow
		},
		uploadimg:function(e){
			var that = this;
      if(that.bgpic.length >= 9){
        app.alert('最多上传9张');return;
      }
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
			if(field == 'bgpic'){
				var bgpics = that.bgpic
				bgpics.splice(index,1);
				that.bgpic = bgpics;
			}else if(field == 'headimg'){
				var headimg = that.headimg
				headimg.splice(index,1);
				that.headimg = headimg;
			}
		},
    selectzuobiao: function () {
			console.log('selectzuobiao')
      var that = this;
      uni.chooseLocation({
        success: function (res) {
          console.log(res);
          that.address = res.address + res.name;
          that.latitude = res.latitude;
          that.longitude = res.longitude;
        },
        fail: function (res) {
					console.log(res)
          if (res.errMsg == 'chooseLocation:fail auth deny') {
            //$.error('获取位置失败，请在设置中开启位置信息');
            app.confirm('获取位置失败，请在设置中开启位置信息', function () {
              uni.openSetting({});
            });
          }
        }
      });
    },
	changebgcategory(index){
		this.bgcid = index;
		console.log(index,'index');
		if(index !=-1){
			this.this_bglist = this.bgdata.bglist[index];
			console.log(this.this_bglist);
			this.iscustom = 0;
		}else{
			this.iscustom = 1;
			this.bgid= '';
		}
	},
	selectBg(id){
		this.bgid = id;
	}
  }
};
</script>
<style>
radio{transform: scale(0.6);}
checkbox{transform: scale(0.6);}
.form-box{ padding:2rpx 24rpx 0 24rpx; background: #fff;margin: 24rpx;border-radius: 10rpx}
.form-item{ line-height: 100rpx; display: flex;justify-content: space-between;border-bottom:0px solid #eee }
.form-item .f1{color:#222;width:250rpx;flex-shrink:0;}
.form-item .f2{display:flex;align-items:center;flex:1}
.form-box .form-item:last-child{ border:none}
.form-box .flex-col{padding-bottom:20rpx}
.form-item input{ width: 100%; border: 1px solid #f1f1f1;color:#111;font-size:28rpx; /*text-align: right;*/height:70rpx;padding:0 10rpx;border-radius:6rpx}
.form-item textarea{ width:100%;min-height:200rpx;padding:20rpx 0;border: none;}
.form-item .upload_pic{ margin:50rpx 0;background: #F3F3F3;width:90rpx;height:90rpx; text-align: center  }
.form-item .upload_pic image{ width: 32rpx;height: 32rpx; }
.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none;background:#4A84FF}

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}

.clist-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.clist-image{width: 100%;display: block;}
.radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin: 0 30rpx 0 200rpx}
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
.uni-dialog-scroll{height: 300rpx;}
/* 背景分类 */
.bglist{width: 100%;}
.bglist .bg{
	min-width: 32%;
	height: 120rpx;
	margin-top: 20rpx;
	border-radius: 10rpx;
	overflow: hidden;
	margin-right: 2%;
	position: relative;
}
.scroll-view{width: 100%;overflow-x: auto;white-space: nowrap;}
.bglist .bgpic{
	position: absolute;
	left: 0;
	top: 0;
	width: 100%;
	height: 120rpx;
}
.bglist .bgpic image{
	width: 100%;
}
.mengceng{
	width: 100%;
	height: 120rpx;
	position: absolute;
	left: 0;
	left: 0;
}
.mengceng .mc{
	width: 100%;
	height: 120rpx;
	 background-color: rgba(0, 0, 0, 0.5);
	position: relative;
}

.mengceng .checked{
	width: 42rpx;
	height: 42rpx;
	position: absolute;
	left: 38%;
	top: 34%;
	background-color: #fff;
	border-radius: 50%;
	text-align: center;
	line-height: 42rpx;
	font-weight: 700;
}
.layui-imgbox-close{z-index: 10 !important;}
</style>
