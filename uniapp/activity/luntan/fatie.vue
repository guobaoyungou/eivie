<template>
<view>
	<block v-if="isload">
		<form @submit="formsubmit">
		<view class="st_box">
			<view class="st_title flex-y-center">
				<view @tap="goback" style="width:100rpx"><image :src="pre_url+'/static/img/goback.jpg'"></image></view>
				<view style="margin-right:40%">我要发帖</view>

			</view>
			<view class="st_form">
				<view class="flex flex-y-center select-cate" v-if="cateArr">
					<picker @change="cateChange" :value="cindex" :range="cateArr" style="height:80rpx;line-height:80rpx;font-size:18px">
						<view class="picker">{{cindex==-1? '请选择发帖类型' : cateArr[cindex]}}</view>
					</picker>
					<view class="jiantou-icon">
						<image :src="pre_url+'/static/img/lt_right_jiantou.png'"></image>
					</view>
				</view>
                <view v-if="cate2 && cateArr2 && cateArr2.length>0">
                	<picker @change="cateChange2" :value="cindex2" :range="cateArr2" style="height:80rpx;line-height:80rpx;border-bottom:1px solid #EEEEEE;font-size:18px">
                		<view class="picker">{{cindex2==-1? '请选择二级分类' : cateArr2[cindex2]}}</view>
                	</picker>
                </view>
								<view v-if='formcontent.length>0'>
										<view class="dp-form-item2" v-for="(item,idx) in formcontent" :key="item.id">
											<view class="label"><text v-if="item.val3==1" style="color:red"> *</text>{{item.val1}}</view>
											<block v-if="item.key=='input'">
													<text v-if="item.val5" style="margin-right:10rpx">{{item.val5}}</text>
													<!-- #ifdef MP-WEIXIN -->
													<block v-if="item.val4==2 && item.val6==1">
														<input @focus="inputFocus" @blur="inputBlur" :type="(item.val4==1 || item.val4==2) ? 'digit' : 'text'" disabled="true" :name="'form'+idx" class="input disabled" :placeholder="item.val2" placeholder-style="font-size:28rpx" :style="{'background-color':'#efefef'}" value="" @input="setfield" :data-formidx="'form'+idx"/>
														<button class="authtel" open-type="getPhoneNumber" type="primary" @getphonenumber="getPhoneNumber" :data-idx="idx">一键填写</button>
													</block>
													<block v-else>
														<input :adjust-position="false"	@focus="inputFocus" @blur="inputBlur"	:type="(item.val4==1 || item.val4==2) ? 'digit' : 'text'"	readonly	:name="'form'+idx" 	class="input" :class="'form'+idx"	:placeholder="item.val2" 	placeholder-style="font-size:28rpx" value="" @input="setfield" :data-formidx="'form'+idx"/>
													</block>
													<!-- #endif -->
													<!-- #ifndef MP-WEIXIN -->
													<block>
														<input :type="(item.val4==1 || item.val4==2) ? 'digit' : 'text'" readonly :name="'form'+idx" class="input" :placeholder="item.val2" placeholder-style="font-size:28rpx" value="" @input="setfield" :data-formidx="'form'+idx"/>
													</block>
													<!-- #endif -->
											</block>
											<block v-if="item.key=='textarea'">
												<textarea :adjust-position="false" @focus="inputFocus"	 @blur="inputBlur" :name="'form'+idx" class='textarea' :class="'form'+idx" :placeholder="item.val2" placeholder-style="font-size:28rpx" value="" @input="setfield" :data-formidx="'form'+idx"/>
											</block>
											<block v-if="item.key=='radio'">
												<radio-group :name="'form'+idx" :class="item.val10=='1'?'rowalone':'flex'" style="flex-wrap:wrap" @change="setfield" :data-formidx="'form'+idx">
													<label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center" :class="[item.val11=='1'?'checkborder':'',item.val10=='1'?'':'rowmore']" :style="{padding:'0 10rpx',marginTop:'10rpx',borderRadius: '10rpx'}" :data-idx="idx" :data-index="idx1" :data-value="item1">
															<radio class="radio" :value="item1" />{{item1}}
													</label>
												</radio-group>
											</block>
											<block v-if="item.key=='checkbox'">
												<checkbox-group :name="'form'+idx" :class="item.val4=='1'?'rowalone':'flex'" style="flex-wrap:wrap" @change="setfield" :data-formidx="'form'+idx">
													<label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center" :class="[item.val9=='1'?'checkborder':'',item.val4=='1'?'':'rowmore']" :style="{padding:'0 10rpx',marginTop:'10rpx',borderRadius: '10rpx'}">
														<checkbox class="checkbox" :value="item1" />{{item1}}
													</label>
												</checkbox-group>
											</block>
											<block v-if="item.key=='selector'">
												<picker class="picker" mode="selector" :name="'form'+idx" :value="editorFormdata[idx]" :range="item.val2" @change="editorBindPickerChange" :data-idx="idx" :data-formidx="'form'+idx">
													<view class="flex-y-center flex-bt" v-if="editorFormdata[idx] || editorFormdata[idx]===0">
														<text>{{item.val2[editorFormdata[idx]]}}</text>
														<view class="arrow-area">
															<view class="input-arrow"></view>
														</view>
													</view>
													<view class="dp-form-normal flex-y-center flex-bt" v-else>
														<text>请选择</text>
														<view class="arrow-area">
															<view class="input-arrow"></view>
														</view>
													</view>
												</picker>
											</block>
											<block v-if="item.key=='time'">
												<picker class="picker" mode="time" :name="'form'+idx" value="" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-idx="idx" :data-formidx="'form'+idx">
													<view class="flex-y-center flex-bt" v-if="editorFormdata[idx]">
														<text>{{editorFormdata[idx]}}</text>
														<view class="arrow-area">
															<view class="input-arrow"></view>
														</view>
													</view>
													<view class="dp-form-normal flex-y-center flex-bt" v-else>
														<text>请选择</text>
														<view class="arrow-area">
															<view class="input-arrow"></view>
														</view>
													</view>
												</picker>
											</block>
											<block v-if="item.key=='date'">
												<picker class="picker" mode="date" :name="'form'+idx" value="" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-idx="idx" :data-formidx="'form'+idx">
													<view class="flex-y-center flex-bt" v-if="editorFormdata[idx]">
														<text>{{editorFormdata[idx]}}</text>
														<view class="arrow-area">
															<view class="input-arrow"></view>
														</view>
													</view>
													<view class="dp-form-normal flex-y-center flex-bt" v-else>
														<text>请选择</text>
														<view class="arrow-area">
															<view class="input-arrow"></view>
														</view>
													</view>
												</picker>
											</block>
											<block v-if="item.key=='year'">
												<picker class="picker" :name="'form'+idx" value="" @change="yearChange" :data-idx="idx" :range="yearList" :data-formidx="'form'+idx">
													<view class="flex-y-center flex-bt" v-if="editorFormdata[idx]">
														<text>{{editorFormdata[idx]}}</text>
														<view class="arrow-area">
															<view class="input-arrow"></view>
														</view>
													</view>
													<view class="dp-form-normal flex-y-center flex-bt" v-else>
														<text>请选择</text>
														<view class="arrow-area">
															<view class="input-arrow"></view>
														</view>
													</view>
												</picker>
											</block>
									
											<block v-if="item.key=='region'">
												<uni-data-picker style="flex: 1;width: 100%;" :localdata="items" popup-title="请选择省市区" placeholder="请选择省市区" @change="onchange" 
												:data-formidx="'form'+idx"></uni-data-picker>
												<input type="text" style="display:none" :name="'form'+idx" value=""/>
											</block>
											<block v-if="item.key=='upload'">
												<input type="text" style="display:none" :name="'form'+idx" value=""/>
												<view class="flex" style="flex-wrap:wrap;padding-top:20rpx">
													<view class="dp-form-imgbox" v-if="editorFormdata[idx]">
														<view class="dp-form-imgbox-close" @tap="removeimgForm" :data-idx="idx" :data-formidx="'form'+idx"><image :src="pre_url+'/static/img/ico-del.png'" class="image"></image></view>
														<view class="dp-form-imgbox-img"><image class="image" :src="editorFormdata[idx]" @click="previewImage" :data-url="editorFormdata[idx]" mode="aspectFit" :data-idx="idx"/></view>
													</view>
													<view v-else class="dp-form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3'}" @click="editorChooseImage" :data-idx="idx" :data-formidx="'form'+idx"></view>
												</view>
											</block>
									    <block v-if="item.key=='upload_pics'">
									    	<input type="text" style="display:none" :name="'form'+idx" value="" maxlength="-1"/>
									    	<view class="flex" style="flex-wrap:wrap;padding-top:20rpx;">
									    		<view v-for="(item2, index2) in editorFormdata[idx]" :key="index2" class="dp-form-imgbox">
									    			<view class="dp-form-imgbox-close" @tap="removeimgForm" :data-index="index2" data-type="pics" :data-idx="idx" :data-formidx="'form'+idx"><image :src="pre_url+'/static/img/ico-del.png'" class="image"></image></view>
									    			<view class="dp-form-imgbox-img" style="margin-bottom: 10rpx;"><image class="image" :src="item2" @click="previewImage" :data-url="item2" mode="aspectFit" :data-idx="idx"/></view>
									    		</view>
									    		<view class="dp-form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3',marginBottom: '10rpx'}" @click="editorChooseImage" :data-idx="idx" :data-formidx="'form'+idx" data-type="pics"></view>
									    	</view>
									    </block>
										</view>

								</view>
				<!-- <view v-if="cateArr">
					<picker @change="cateChange" :value="cindex" :range="cateArr" style="height:80rpx;line-height:80rpx;border-bottom:1px solid #EEEEEE;font-size:18px">
						<view class="picker">{{cindex==-1? '请选择曝光标签' : cateArr[cindex]}}</view>
					</picker>
				</view> -->
				<view><textarea :placeholder="inputtips" name="content" maxlength="-1"></textarea></view>
				<view v-if="need_call">
						<input type="text" placeholder="输入联系电话" name="mobile"/>
				</view>
				<block v-if="isphone">
					<view>
							<input type="text" placeholder="请输入姓名" name="name"/>
					</view>
					<view>
							<input type="text" placeholder="请输入手机号" name="mobile"/>
					</view>
					<block v-if="isphoneother">
						<view>
							<input type="text" placeholder="请输入QQ" name="qq"/>
						</view>
						<view>
							<input type="text" placeholder="请输入微信号" name="wechat"/>
						</view>
					</block>
				</block>
				
				
				
				<view class="uploadbtn_ziti1">
					插入图片
				</view>
				<view class="flex" style="flex-wrap:wrap;padding-top:20rpx;">
					<view v-for="(item, index) in pics" :key="index" class="layui-imgbox">
						<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="pics"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
						<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
						<!-- <view class="layui-imgbox-repeat" @tap="xuanzhuan" :data-index="index" data-field="pics"><text class="fa fa-repeat"></text></view> -->
					</view>
					<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pics" v-if="pics.length<9">
						
					</view>
					
				</view>
				
				<input type="text" hidden="true" name="pics" :value="pics.join(',')" maxlength="-1"></input>
			
			<view class="uploadbtn_ziti2">
				插入视频
			</view>
				<view class="flex-y-center" style="width:100%;padding:20rpx 0;margin-top:20rpx;">
					<image :src="pre_url+'/static/img/uploadvideo.png'" style="width:200rpx;height:200rpx;background:#eee;" @tap="uploadvideo"></image><text v-if="video" style="padding-left:20rpx;color:#333">已上传短视频</text></view>
				<input type="text" hidden="true" name="video" :value="video" maxlength="-1"></input>
				
				<!-- 付费置顶 -->
				<block v-if="paytop == 1 && topoptions">
				<view class="uploadbtn_ziti2">帖子置顶展示</view>
				<scroll-view class="top-scroll" scroll-x="true" show-scrollbar="false" :scroll-with-animation="true" style="padding: 20rpx 0;" >
					<view class="top-options flex">
						<view  v-for="(item, index) in topoptions"  :key="index" class="top-option" :style="{background: topselected == index ? 'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)' : ''}" :class="{ 'selected': topselected === index }" @tap="selectTopTime(index)" >
							<view class="time">置顶{{ item.hour }}小时</view>
							<view class="price">{{ item.price }}元</view>
						</view>
					</view>
				</scroll-view>
				</block>
			</view>
		</view>
		<view class="st_title flex-y-center">
			<button form-type="submit" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" v-if="paytop == 1 && topselected >= 0">支付{{topprice}}元 发表</button>
			<button form-type="submit" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}" v-else>发表</button>
		</view>
		<view style="width:100%;margin-top:20rpx;text-align:center;color:#999;display:flex;align-items:center;justify-content:center" @tap="goto" data-url="fatielog">我的发帖记录<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
		<view style="width:100%;height:50rpx"></view>
		</form>
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
      datalist: [],
      content_pic: [],
      pagenum: 1,
      cateArr: [],
      cindex: -1,
			pics:[],
      video: '',
      need_call:false,
      clist:[],
      clist2:[],
      cateArr2: [],
      cindex2: -1,
      cate2:false,
      displaytype:-1,
			isphone:false,
			iscatephone:false,
			editorFormdata:[],
			formdata:{},
			formvaldata:{},
			items:[],
			regiondata:'',
			yearList:[],
			isform:false,
			formcontent:[],
			formid:0,
			paytop:0,
			topoptions: [],
			topselected:-1,
			topprice:0,
			isphoneother:0,//其他联系方式
			inputtips:"输入内容"
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.displaytype = this.opt.displaytype || -1;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiLuntan/fatie', {display_type:that.displaytype}, function (res) {
				that.loading = false;
				if (res.status == 0) {
					app.alert(res.msg);
					return;
				}
				that.clist = res.clist;
				that.iscatephone = res.iscatephone
				that.isphoneother = res.isphoneother || 0;
				var clist = res.clist;
				if (clist.length > 0) {
					var cateArr = [];
					for (var i in clist) {
						if (that.opt && that.opt.cid == clist[i].id) {
							
							that.cindex = i;
							that.getform(that.opt.cid)
							
							if(that.iscatephone){
									that.isphone = clist[that.cindex].isphone;
							}
							if(clist[that.cindex].pay_top){
								let catedata = clist[that.cindex]
								that.paytop = catedata.pay_top;
								if(catedata.top_options){
								  that.topoptions = JSON.parse(catedata.top_options);
								}
							}
							if(res.cate2){
									that.getCate2(that.opt.cid);
							}
						}
						cateArr.push(clist[i].name);
					}
				} else {
					cateArr = false;
				}
				that.cateArr = cateArr
				if(res.need_call){
						that.need_call = true;
				}
				if(res.cate2){
						that.cate2    = true;
				}
				if(res.isform){
					that.isform  = true;
				}
				if(res.input_tips){
					that.inputtips = res.res.input_tips;
				}
				that.loaded();
			});
		},
    cateChange: function (e) {
        var that = this;
        that.cindex = e.detail.value;

        var clist = that.clist;
        if (clist &&clist.length > 0) {
            var cid = clist[that.cindex].id;
						if(that.iscatephone){
								that.isphone = clist[that.cindex].isphone;
						}
            let catedata = clist[that.cindex];
            if(catedata){
              if(catedata.pay_top) {
                that.topoptions = [];
                that.topselected = -1;
                that.topprice = 0;
                that.paytop = catedata.pay_top;
                if(catedata.top_options){
                  that.topoptions = JSON.parse(catedata.top_options);
                }
              }else{
                that.paytop = 0;
                that.topoptions = [];
                that.topselected = -1;
                that.topprice = 0;
              }
            }
        } else {
            var cid = 0;
        }
				// 分类表单
				if(cid && that.isform){
					that.getform(cid)
				}
			
        if(that.cate2){
            that.getCate2(cid);
        }
    },
		getform:function(cid){
			var that = this;
			app.get('ApiLuntan/getform', {cid:cid}, function (res) {
				// console.log(res)
				if(res.status == 1 && res.formid && res.formcontent){
					that.formid = res.formid || 0
					that.formcontent = res.formcontent || []
					uni.request({
						url: app.globalData.pre_url+'/static/area.json',
						data: {},
						method: 'GET',
						header: { 'content-type': 'application/json' },
						success: function(res2) {
							that.items = res2.data
						}
					});
					let year = [];
					for(let i=0;i<that.formcontent.length;i++){
						if(that.formcontent[i].key=='year'){
							for(let j=that.formcontent[i].val2[0];j<=that.formcontent[i].val2[1];j++){
								year.push(j);
							}
						}
					}
					that.yearList = year.reverse();
				}else{
					that.formcontent = [];
					that.formid = 0;
				}
			})
		},
    cateChange2: function (e) {
      this.cindex2 = e.detail.value;
    },
    formsubmit: function (e) {
      var that = this;
      
      var clist = that.clist;

      if (clist.length > 0) {
        if (that.cindex == -1) {
          app.error('请选择分类');
          return false;
        }
        var cid = clist[that.cindex].id;
		
      } else {
        var cid = 0;
      }
      var cid2 =0;
      if(that.cate2){
          var clist2 = that.clist2;
          if (clist2.length > 0) {
            if (that.cindex2 == -1) {
              app.error('请选择二级分类');
              return false;
            }
            var cid2 = clist2[that.cindex2].id;
          } else {
            var cid2 = 0;
          }
      }
      var formdata = e.detail.value;
      var content = formdata.content;
      var pics = formdata.pics;
      var video = formdata.video;
      var mobile = formdata.mobile;
      if (content == '' && pics == '') {
        app.error('请输入内容');
        return false;
      }
			var name = formdata.name;
			if(that.isphone){
				if(!name){
					app.error('请输入姓名');return false;
				}
				if(that.isphoneother == 1){
					//手机号 QQ 微信填写一个即可
					if (!mobile && !formdata.qq && !formdata.wechat){
						app.error('请输入手机号、QQ号或微信账号');
						return false;
					}
					if(mobile && !app.isPhone(mobile)){
						app.alert('手机号格式错误');return;
					}
				}else{
					if(!mobile){
						app.error('请输入手机号');	return false;
					}
					if (!app.isPhone(mobile)) {
						app.alert('手机号格式错误');return;
					}
				}
			}
			
			var subdata = JSON.parse(JSON.stringify(this.formvaldata));
			
			if(that.formcontent.length > 0){
				var formcontent = that.formcontent;
				for (var i = 0; i < formcontent.length;i++){
					//console.log(subdata['form' + i]);
					if (formcontent[i].key == 'region') {
							subdata['form' + i] = that.regiondata;
					}
					if (formcontent[i].key!='separate' && formcontent[i].val3 == 1 && (subdata['form' + i] === '' || subdata['form' + i] === null || subdata['form' + i] === undefined || subdata['form' + i].length==0)){
						if(formcontent[i].linkitem == '' || formcontent[i].linkshow){
							console.log(subdata['form' + i])
							app.alert(formcontent[i].val1+' 必填');return;
						}
					}
					if (formcontent[i].key =='switch'){
							if (subdata['form' + i]==false){
									subdata['form' + i] = '否'
							}else{
									subdata['form' + i] = '是'
							}
					}
					if (formcontent[i].key == 'selector') {
							subdata['form' + i] = formcontent[i].val2[subdata['form' + i]]
					}
					if (formcontent[i].key == 'input' && formcontent[i].val4 && subdata['form' + i]!==''){
						if(formcontent[i].val4 == '2'){ //手机号
							if (!app.isPhone(subdata['form' + i])) {
								app.alert(formcontent[i].val1+' 格式错误');return;
							}
						}
						if(formcontent[i].val4 == '3'){ //身份证号
							if (!/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/.test(subdata['form' + i])) {
								app.alert(formcontent[i].val1+' 格式错误');return;
							}
						}
						if(formcontent[i].val4 == '4'){ //邮箱
							if (!/^(.+)@(.+)$/.test(subdata['form' + i])) {
								app.alert(formcontent[i].val1+' 格式错误');return;
							}
						}
					}
				}
			}
      
      app.post('ApiLuntan/fatie', {cid: cid,cid2:cid2,pics: pics,content: content,video: video,mobile: mobile,name:name,formdata:subdata,formid:that.formid,qq:formdata.qq,wechat:formdata.wechat,topselected:that.topselected}, function (res) {
        app.showLoading(false);
        if (res.status == 1) {
          app.success(res.msg);
          setTimeout(function () {
            app.goback(true);
          }, 1000);
        }else if(res.status == 2){
          app.goto('/pagesExt/pay/pay?id=' + res.payorderid,'redirectTo');
          return;
        } else {
          app.error(res.msg);
        }
      });
    },
		uploadimg:function(e){
			var that = this;
			var field= e.currentTarget.dataset.field
			var pics = that[field]
			if(!pics) pics = [];
			app.chooseImage(function(urls){
				for(var i=0;i<urls.length;i++){
					pics.push(urls[i]);
				}
				if(field == 'pic') that.pic = pics;
				if(field == 'pics') that.pics = pics;
				if(field == 'zhengming') that.zhengming = pics;
			},9)
		},
    uploadvideo: function () {
      var that = this;
      console.log(11);
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
                that.video = data.url;
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
		removeimg:function(e){
			var that = this;
			var index= e.currentTarget.dataset.index
			var field= e.currentTarget.dataset.field
			var pics = that[field]
			pics.splice(index,1)
		},
        getCate2: function (pid) {
        	var that = this;
        	app.post('ApiLuntan/getCate2', {pid:pid}, function (res) {
                if(res.status == 1){
                    var clist2 = res.data;
                    that.clist2 = clist2;
                    if (clist2.length > 0) {
                    	var cateArr2 = [];
                    	for (var i in clist2) {
                    		if (that.opt && that.opt.cid == clist2[i].id) {
                    			that.cindex = i;
                                if(res.cate2){
                                    that.getCate2(that.opt.cid);
                                }
                    		}
                    		cateArr2.push(clist2[i].name);
                    	}
                    } else {
                    	cateArr2 = false;
                    }
                    that.cateArr2 = cateArr2
                }else{
                    that.cateArr2 = [];
                    app.alert(res.msg)
                }
        	});
        },
				// 失去焦点
				inputBlur(){
					this.$set(this,'keyboardHeight',0)
				},
				// 获取焦点
				inputFocus(event){
					if (this.timer) {
						clearTimeout(this.timer)
					}
					this.timer = setTimeout(() => {
						this.timer = null
						const height = event.detail.height; //键盘高度
						const formidx = event.target.dataset.formidx
						if(height === 0){
							this.scrollToInput(0);
							return;
						}
					try{
					const query = uni.createSelectorQuery().in(this);
						query.select(`.${formidx}`).boundingClientRect((res) => {
							const windowHeight = uni.getSystemInfoSync().windowHeight;
							// 除去键盘的剩余高度
							let restHeight = windowHeight - height;
							// 元素左下角坐标
							let bottom = res.bottom;
							// 只有当元素被软键盘覆盖的时候才上推页面
							if (bottom <= restHeight) return;
							// 现阶段需要滚动的大小
							let scrollTop = bottom - restHeight;
							this.scrollToInput(height, scrollTop);
						}).exec();
					} catch(err){console.log(err)}
					},300)
				},
				// 监听页面键盘弹起推动页面
				scrollToInput(height,scrollTop){
					this.$set(this,'keyboardHeight',height)
					 if (scrollTop) {
					    try {
					      this.getScrollOffset().then((lastScrollTop) => {
					        uni.pageScrollTo({
					          // 如果已经存在滚动，在此基础上继续滚
					          scrollTop: lastScrollTop ? lastScrollTop + scrollTop : scrollTop,
					          duration: 0,
					        });
					      });
					    } catch (error) {}
					  }
				},
				// 获取页面滚动条位置
				getScrollOffset() {
				  return new Promise((resolve) => {
				    try {
				     const query = uni.createSelectorQuery().in(this);
				        query.selectViewport().scrollOffset((res) => {
				          resolve(res.scrollTop);
				        }).exec();
				    } catch (error) {
				      resolve(0);
				    }
				  });
				},
				setfield:function(e){
				  var that = this;
					var field = e.currentTarget.dataset.formidx;
					var value = e.detail.value;
					that.formvaldata[field] = value;
				},
				editorBindPickerChange:function(e){
					var idx = e.currentTarget.dataset.idx;
					var val = e.detail.value;
					var editorFormdata = this.editorFormdata;
					if(!editorFormdata) editorFormdata = [];
					this.$set(this.editorFormdata,idx,val)
					this.test = Math.random();
					var field = e.currentTarget.dataset.formidx;
					this.formvaldata[field] = val;
					var idx = field.replace('form','');
					var thiscontent = this.editorFormdata[idx]
					if(thiscontent.key == 'radio' || thiscontent.key=='selector'){
						var linkitem = thiscontent.val1 + '|' + thiscontent.val2[val];
						for(var i in this.editorFormdata){
							var thislinkitem = this.editorFormdata[i].linkitem;
							if(thislinkitem == linkitem){
								this.editorFormdata[i].linkshow = true;
								this.test = Math.random();
							}else if(thislinkitem && thislinkitem.split('|')[0] == thiscontent.val1){
								this.editorFormdata[i].linkshow = false;
								this.test = Math.random();
							}
						}
					}
				},
				editorChooseImage: function (e) {
					var that = this;
					var idx = e.currentTarget.dataset.idx;
					var editorFormdata = this.editorFormdata;
					if(!editorFormdata) editorFormdata = [];
				  var type = e.currentTarget.dataset.type;
					app.chooseImage(function(data){
				    if(type == 'pics'){
				      var pics = editorFormdata[idx];
				      if(!pics){
				        pics = [];
				      }
				      for(var i=0;i<data.length;i++){
				      	pics.push(data[i]);
				      }
							that.$set(that.editorFormdata,idx,pics)
				      that.test = Math.random();
				      var field = e.currentTarget.dataset.formidx;
				      that.formvaldata[field] = pics;
				    }else{
							that.$set(that.editorFormdata,idx,data[0])
				      that.test = Math.random();
				      var field = e.currentTarget.dataset.formidx;
				      that.formvaldata[field] = data[0];
				    }
					})
				},
				removeimgForm:function(e){
					var that = this;
					var idx = e.currentTarget.dataset.idx;
					var field = e.currentTarget.dataset.formidx;
					var editorFormdata = this.editorFormdata;
				  if(!editorFormdata) editorFormdata = [];
				  var type  = e.currentTarget.dataset.type;
				  var index = e.currentTarget.dataset.index;
				  if(type == 'pics'){
				    var pics = editorFormdata[idx]
				    pics.splice(index,1);
				    editorFormdata[idx] = pics;
				    that.editorFormdata = editorFormdata
				    that.test = Math.random();
				    that.formvaldata[field] = pics;
				  }else{
				    editorFormdata = '';
						that.$set(that.editorFormdata,idx,editorFormdata)
				    that.test = Math.random();
				    that.formvaldata[field] = '';
				  }
				},
				onchange(e) {
				  const value = e.detail.value
					this.regiondata = value[0].text + ',' + value[1].text + ',' + value[2].text;
				},
				yearChange:function(e){
					var idx = e.currentTarget.dataset.idx;
					var val = this.yearList[e.detail.value];
					var editorFormdata = this.editorFormdata;
					if(!editorFormdata) editorFormdata = [];
					this.$set(this.editorFormdata,idx,val);
					this.test = Math.random();
					var field = e.currentTarget.dataset.formidx;
					this.formvaldata[field] = val;
				},
				selectTopTime(index) {
					//已选中则取消选中
					if (this.topselected === index) {
						this.topselected = -1;
						this.topprice = 0;
					} else {
						this.topselected = index;
						this.topprice = this.topoptions[index].price;
					}
				},
  }
};
</script>
<style>
page{background:#f7f7f7}
.st_box{ padding: 20rpx 0 }
.st_title{ display: flex; justify-content: space-between;padding:24rpx;  }
.st_title1{ display: flex; justify-content: space-between;padding:24rpx;border-bottom: 1px solid #D0D0D0  }
.st_title image{width: 18rpx;height:32rpx}
.st_title text{ color:#242424; font-size: 36rpx}
/* .st_title button{ background: #31C88E; border-radius:6rpx; line-height: 48rpx;border: none; padding:0 20rpx ;color:#fff;margin:0} */


.st_title button{
    background: #1658c6;
    border-radius: 3px;
    line-height: 48rpx;
    border: none;
    padding: 0 20rpx;
    color: #fff;
    font-size: 20px;
    text-align: center;
    /* margin: 0; */
    width: 90%;
    display: flex;
    height: 100rpx;
    justify-content: center;
    align-items: center;}



.st_form{ padding: 24rpx;background: #ffffff;margin: 10px;border-radius: 15px;}
.st_form input{ width: 100%;height: 120rpx; border: none;border-bottom:1px solid #EEEEEE;}
.st_form input::-webkit-input-placeholder { /* WebKit browsers */ color:    #BBBBBB; font-size: 24rpx}
.st_form textarea{ width:100%;min-height:200rpx;padding:20rpx 0;border: none;border-bottom:1px solid #EEEEEE;}
.st_form .select-cate{border-bottom: 1px solid #EEEEEE;}
.jiantou-icon{width: 30rpx;height: 30rpx;margin-left: 10rpx;}
.jiantou-icon image{width: 100%;height: 100%;}

.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}
.uploadbtn_ziti1{height:30rpx; line-height: 30rpx;font-size:30rpx; margin-top: 20rpx;}
.uploadbtn_ziti2{height:30rpx; line-height: 30rpx;font-size:30rpx; padding-top: 20rpx; margin-top: 20rpx;border-top:1px solid #EEEEEE;}
/*  */
.radio{transform:scale(.7);}
.checkbox{transform:scale(.7);}
.dp-form-item2{width: 100%;border-bottom: 1px #ededed solid;padding:10rpx 10rpx;display:flex;flex-direction:column;align-items: flex-start;}
.dp-form-item2:last-child{border:0}
.dp-form-item2 .label{line-height: 85rpx;width:100%;margin-right: 10px;}
.dp-form-item2 .value{display: flex;justify-content: flex-start;width: 100%;flex: 1;}
.dp-form-item2 .input{height: 70rpx;line-height: 70rpx;overflow: hidden;width:100%;border: 1px solid #e5e5e5;border-radius: 5px;padding: 0 15rpx;background:#fff;}
.dp-form-item2 .textarea{height:180rpx;line-height:40rpx;overflow: hidden;width:100%;border:1px solid #eee;border-radius:5px;padding:15rpx}
.dp-form-item2 .radio{height: 70rpx;line-height: 70rpx;display:flex;align-items:center;}
.dp-form-item2 .radio2{display:flex;align-items:center;}
.dp-form-item2 .radio .myradio{margin-right:10rpx;display:inline-block;border:1px solid #aaa;background:#fff;height:32rpx;width:32rpx;border-radius:50%}
.dp-form-item2 .checkbox{height: 70rpx;line-height: 70rpx;display:flex;align-items:center;}
.dp-form-item2 .checkbox2{display:flex;align-items:center;height: 40rpx;line-height: 40rpx;}
.dp-form-item2 .checkbox .mycheckbox{margin-right:10rpx;display:inline-block;border:1px solid #aaa;background:#fff;height:32rpx;width:32rpx;border-radius:2px}
.dp-form-item2 .layui-form-switch{}
.dp-form-item2 .picker{min-height: 70rpx;line-height:70rpx;flex:1;width:100%;border: 1px solid #e5e5e5;border-radius: 5px;padding: 0 5px;}
.dp-form-imgbox{margin-right:16rpx;font-size:24rpx;position: relative;}
.dp-form-imgbox-close{position: absolute;display: block;width:32rpx;height:32rpx;right:-16rpx;top:-16rpx;color:#999;font-size:32rpx;background:#fff;z-index:9;border-radius:50%}
.dp-form-imgbox-close .image{width:100%;height:100%}
.dp-form-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden;}
.dp-form-imgbox-img>.image{width:100%;height:100%}
.dp-form-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.dp-form-uploadbtn{position:relative;height:200rpx;width:200rpx}
.authtel{border-radius: 10rpx; line-height: 68rpx;margin-left: 20rpx;padding: 0 20rpx;}
.input.disabled{background: #EFEFEF;}
.dp-form-normal{color: grey;}
.arrow-area {
	position: relative;
	width: 20px;
	/* #ifndef APP-NVUE */
	display: flex;
	margin-left: auto;
	/* #endif */
	justify-content: center;
	transform: rotate(-45deg);
	transform-origin: center;
}
.input-arrow {width: 7px;height: 7px;border-left: 1px solid #999;border-bottom: 1px solid #999;}
.checkborder{border: 1px solid #dcdfe6;border-radius: 5px;margin-top: 15rpx;min-width: 300rpx;padding: 0 10rpx;}
.rowalone{width: 100%;}
.rowmore{margin-right: 20rpx;}
.top-scroll{width:100%;white-space:nowrap;padding:0 20rpx;margin-top:50rpx}
.top-scroll .top-options{display:flex;align-items:center;padding:10rpx 0}
.top-scroll .top-options .top-option{width:185rpx;height:120rpx;border-radius:16rpx;background-color:#f5f5f5;color:#333;text-align:center;margin-right:20rpx;padding:20rpx;box-sizing:border-box;cursor:pointer;transition:all 0.2s;flex-shrink:0;display:flex;flex-direction:column;justify-content:center}
.top-scroll .top-options .top-option.selected{color:#fff;transform:scale(1.05);}
.top-scroll .top-options .top-option .time{font-weight:bold;line-height:1.4}
.top-scroll .top-options .top-option .price{opacity:0.9;margin-top:10rpx}
</style>