<template>
<view class="container">
		<view class="detail">
			<parse :content="formdata.desc"></parse>
		</view>
			<form @submit="editorFormSubmit" :data-formcontent="formdata.content" :data-formid="cateid"  >
			<view class="content">
				<view class="form-item flex">
					<view class="title"> 工单类型:</view>
					<view class="inputbox flex">
						<picker class="picker" mode="selector" @change="BindPickerChange2" :range="newcate" :value="index" range-key="name">
								<view class="uni-input" >{{newcatename}}</view>
						</picker>
						<text class="iconfont iconjiantou" style="color:#ccc;font-weight:normal"></text>
					</view>
				</view>
				
				<view class="form-item flex">
					<view class="title"> 工单名称:</view>
					<view class="inputbox flex">
						<picker class="picker" mode="selector" @change="BindPickerChange" :range="cate" :value="index" range-key="name">
								<view class="uni-input" >{{catename}}</view>
						</picker>
						<text class="iconfont iconjiantou" style="color:#ccc;font-weight:normal"></text>
					</view>
				</view>
				<block v-for="(fitem,findex) in formdata.content" >
					<view class="dp-form-separate" @tap.stop="formChange" :data-index="findex"><text>{{fitem.name}}</text><image :src="pre_url+'/static/img/workorder/'+(currentindex==findex?'down':'up')+'.png'"></view>
					<view class="parentitem" :style="(currentindex!=findex?'display:none':'')">
						
						<view class="form-item" v-for="(item,idx) in fitem.list" >
							<view  class="label">{{item.val1}}<text v-if="item.val3==1" style="color:red"> *</text></view>
							<block v-if="item.key=='input'">
								<input type="text" :name="'form'+item.id" class="input"  :placeholder="item.val2" :data-formidx="'form'+item.id" placeholder-style="font-size:28rpx"/>
							</block>
							<block v-if="item.key=='textarea'">
								<textarea :name="'form'+item.id" class='textarea' :placeholder="item.val2" :data-formidx="'form'+item.id" placeholder-style="font-size:28rpx"/>
							</block>
							<block v-if="item.key=='radio'">
								<radio-group class="radio-group" :name="'form'+item.id" :data-formidx="'form'+item.id">
									<label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center">
										<radio class="radio" :value="item1"/>{{item1}}
									</label>
								</radio-group>
							</block>
							<block v-if="item.key=='checkbox'">
								<checkbox-group :name="'form'+item.id" class="checkbox-group" :data-formidx="'form'+item.id">
									<label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center">
										<checkbox class="checkbox" :value="item1"/>{{item1}}
									</label>
								</checkbox-group>
							</block>
							<block v-if="item.key=='selector'">
								<picker class="picker" mode="selector"  :name="'form'+item.id"  :range="item.val2" @change="editorBindPickerChange" :data-idx="item.id" :data-formidx="'form'+item.id">		
									<view v-if="editorFormdata[item.id] || editorFormdata[item.id]===0"> {{item.val2[editorFormdata[item.id]]}}</view>
									<view v-else>请选择</view>
								</picker>
								<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
							</block>
							<block v-if="item.key=='time'">
								<picker class="picker" mode="time" :name="'form'+item.id" value="" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-bid="formdata" :data-idx="item.id"  :data-formidx="'form'+item.id" >
									<view v-if="editorFormdata[item.id]">{{editorFormdata[item.id]}}</view>
									<view v-else>请选择</view>
								</picker>
								<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
							</block>
							<block v-if="item.key=='date'">
								<picker class="picker" mode="date" :name="'form'+item.id" value="" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-bid="formdata" :data-idx="item.id" :data-formidx="'form'+item.id">
									<view v-if="editorFormdata[item.id]">{{editorFormdata[item.id]}}</view>
									<view v-else>请选择</view>
								</picker>
								<text class="iconfont iconjiantou" style="color:#999;font-weight:normal"></text>
							</block>
							
							<block v-if="item.key=='region'">
								<uni-data-picker :localdata="items"  popup-title="请选择省市区" :placeholder="formdata['form'+item.id] || '请选择省市区'" @change="onchange" :data-formidx="'form'+item.id"></uni-data-picker>
								<!-- <picker class="picker" mode="region" :name="'form'+idx" value="" @change="editorBindPickerChange" :data-idx="idx">
									<view v-if="editorFormdata[idx]">{{editorFormdata[idx]}}</view> 
									<view v-else>请选择省市区</view>
								</picker> -->
								<input type="text" style="display:none" :name="'form'+item.id" :value="regiondata ? regiondata : formdata['form'+item.id]"/>
							</block>
							
							<block v-if="item.key=='upload'">
								<input type="text" style="display:none" :name="'form'+item.id" :value="editorFormdata['content_pics'+item.id]"/>
								<view class="flex" style="flex-wrap:wrap;padding-top:20rpx">
									<view class="form-imgbox" v-if="editorFormdata['content_pic'+item.id]" v-for="(item1, index1) in editorFormdata['content_pic'+item.id]" :key="index">
										<view class="form-imgbox-close" @tap="removeimg" :data-pindex="index1"  :data-field="'content_pic'+item.id"  :data-idx="item.id"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
										<view class="form-imgbox-img"><image  :src="item1" @click="previewImage" :data-url="item1" mode="widthFix" /></view>
									</view>
									<view class="form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 50rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3',backgroundPosition: 'center'}" @click="editorChooseImage" :data-field="'content_pic'+item.id"  :data-idx="item.id" :data-formidx="'form'+item.id"></view>
								</view>
							</block>
							<!-- #ifdef H5 || MP-WEIXIN -->
							<block v-if="item.key=='upload_file'">
								<input type="text" style="display:none" :name="'form'+item.id" :value="editorFormdata[item.id]"/>
								<view class="flex-y-center" style="flex-wrap:wrap;padding-top:20rpx;">
									<view class="dp-form-imgbox" v-if="editorFormdata[item.id]">
										<view class="dp-form-imgbox-close" @tap="removeimg2" :data-idx="item.id" :data-formidx="'form'+item.id">
											<image :src="pre_url+'/static/img/ico-del.png'" class="image"></image>
										</view>
										<view  style="overflow: hidden;white-space: pre-wrap;word-wrap: break-word;color: #4786BC;" @tap="download" :data-file="editorFormdata[idx]" >
											文件已上传成功
										</view>
									</view>
									<block v-else>
										<view  class="dp-form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 30rpx',backgroundSize:'50rpx 50rpx',backgroundColor:'#F3F3F3'}" @click="chooseFile" :data-idx="idx" :data-id="item.id" :data-formidx="'form'+item.id" style="margin-right:20rpx;"></view>
										<view v-if="item.val2" style="color:#999">{{item.val2}}</view>
									</block>
								</view>
							</block>
							<!-- #endif -->
							
							<block v-if="item.key=='upload_video'">
								<input type="text" style="display:none" :name="'form'+item.id" :value="editorFormdata[item.id]"/>
								<view class="flex-y-center" style="flex-wrap:wrap;padding-top:20rpx">
									<view class="dp-form-imgbox" v-if="editorFormdata[item.id]">
										<view class="dp-form-imgbox-close" @tap="removeimg" :data-idx="item.id" :data-formidx="'form'+item.id">
												<image :src="pre_url+'/static/img/ico-del.png'" class="image"></image>
										</view>
										<view  style="overflow: hidden;white-space: pre-wrap;word-wrap: break-word;color: #4786BC;width: 230rpx;">
												<video  :src="editorFormdata[item.id]" style="width: 100%;"/></video>
										</view>
									</view>
									<block v-else>
										<view class="dp-form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 30rpx',backgroundSize:'50rpx 50rpx',backgroundColor:'#F3F3F3'}" @tap="upVideo" :data-idx="idx"  :data-id="item.id" :data-formidx="'form'+item.id" style="margin-right:20rpx;"></view>
										<view v-if="item.val2" style="color:#999">{{item.val2}}</view>
									</block>
								</view>
							</block>
						</view>
					</view>
				</block>
				<view class="form-item" v-if="formdata.payset==1">
					<text class="label">支付金额：</text>
					<input type="text" class="input" name="price" :value='formdata.price' v-if="formdata.priceedit==1"  data-formidx="price"/>
					<text v-if="formdata.priceedit==0">{{formdata.price}}</text>
					<text style="padding-left:10rpx">元</text>
				</view>
			</view>
			<view style="height: 100rpx;"></view>
			<view class="btnbox">	<button class="dp-form-btn" form-type="submit"  :data-formcontent="formdata.content"  :data-formid="cateid"  :style="{background:t('color1')}" >提交</button>	</view>
		</form>
		
		<!--悬浮按钮-->
		<view class="myworkorder"  @tap="goto" data-url="record">我的工单</view>
		
	<nodata v-if="nodata"></nodata>
	<nomore v-if="nomore"></nomore>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
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
			cate:[],
      nomore: false,
			nodata:false,
			index:0,
			catename:'请选择',
			pre_url:app.globalData.pre_url,
			editorFormdata:{},
			formdata:{},
			formvaldata:{},
			regiondata:'',
			cateid:'',
			items:[],
			newcatename:'请选择类型',
			currentindex:0,
			newcate:[],
			newindex:-1,
			newcateid:0
			
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.st = this.opt.st || 'all';
		this.type = this.opt.type || '';
		this.orderid = this.opt.id || '';
		this.cateid = this.opt.cateid || '';
		this.getdata();
  },
  methods: {
    changetab: function (st) {
      this.st = st;
      uni.pageScrollTo({
        scrollTop: 0,
        duration: 0
      });
      this.getdata();
    },
		onchange(e) {
		  const value = e.detail.value
			console.log(value[0].text + ',' + value[1].text + ',' + value[2].text)
			this.regiondata = value[0].text + ',' + value[1].text + ',' + value[2].text;
		},
    getdata: function () {
			var that = this;
			app.get('ApiIndex/getCustom',{}, function (customs) {
				var url = app.globalData.pre_url+'/static/area.json';
				uni.request({
					url: app.globalData.pre_url+'/static/area.json',
					data: {},
					method: 'GET',
					header: { 'content-type': 'application/json' },
					success: function(res2) {
						that.items = res2.data
					}
				});
			});
      app.post('ApiWorkorder/getcategory', { type:that.type ,cateid:that.cateid,newcateid:that.newcateid}, function (res) {
					that.isload = true;
					var cate = res.data;
					that.cate = cate;
					//console.log(that.cateid)
					that.newcate = res.newcate
					if(that.cateid){
						that.BindPickerChanges();
					}
			})
			console.log(that.editorFormdata);
    },
		BindPickerChange:function(e){
			var that = this;
			var index = e.detail.value;
			that.index = index
			that.catename = that.cate[index].name
			var cateid = that.cate[index].id
			that.cateid = cateid
			/*app.post('ApiWorkorder/getform', {id:cateid }, function (res) {
					that.isload = true;
					that.loading=false
					if(res && res.status == 1 && res.data){
						var formcontent = res.data;
						that.formdata = formcontent
						console.log(formdata);
					}
			})*/
	
			that.formdata = that.cate[index]
			that.newcatename = that.cate[index].newcatename		
		},
		
		BindPickerChanges:function(){
			var that = this;
			var index = that.index;
			that.catename = that.cate[index].name
			var cateid = that.cate[index].id
			that.cateid = cateid
			that.formdata = that.cate[index]
			that.newcatename = that.cate[index].newcatename		
		},
		BindPickerChange2:function(e){
			var that = this;
			var index = e.detail.value;
			that.newindex = index;
			that.newcateid = that.newcate[index].id
			that.newcatename = that.newcate[index].name
			that.getdata();
		},
		
		editorChooseImage: function (e) {
			var that = this;
			var idx = e.currentTarget.dataset.idx;
		  var field = e.currentTarget.dataset.field;
			var pics = that.editorFormdata[field]
			//console.log(field);
			if(!pics) pics = [];
			app.chooseImage(function(urls){
				for(var i=0;i<urls.length;i++){
					pics.push(urls[i]);
				}
				that.editorFormdata[field] = pics;
				that.editorFormdata['content_pics'+idx] = that.editorFormdata[field].join(',');
				that.formvaldata[field] = pics;
				console.log(that.editorFormdata);
				that.getdata();
			},5)
		},
		removeimg:function(e){
			var that = this;
			var idx = e.currentTarget.dataset.idx;
			var pindex= e.currentTarget.dataset.pindex
			var field= e.currentTarget.dataset.field
			var pics = that.editorFormdata[field]
			that.editorFormdata[field].splice(pindex,1)
			that.editorFormdata['content_pics'+idx] = that.editorFormdata[field].join(',');
			//console.log(that.editorFormdata[field]);
			that.getdata();
		},
		
		chooseFile:function(e){
		    var that = this;
		    var idx = e.currentTarget.dataset.idx;
			  var id = e.currentTarget.dataset.id;
		    var field = e.currentTarget.dataset.formidx;
		    var editorFormdata = this.editorFormdata;
		    if(!editorFormdata) editorFormdata = [];
				var currentindex= that.currentindex
		    //console.log( that.formdata.content);
		    var up_url = app.globalData.baseurl + 'ApiImageupload/uploadImg/aid/' + app.globalData.aid + '/platform/' + app.globalData.platform +'/session_id/' +app.globalData.session_id;
		    // #ifdef H5
		    uni.chooseFile({
		        count: 1, //默认100
		        success: function (res) {
		            const tempFilePaths = res.tempFiles;
								if(tempFilePaths[0].size > 0){
									var maxsize = that.formdata.content[currentindex].list[idx].val6;
									console.log(maxsize);
									if(maxsize){
										maxsize = parseFloat(maxsize);
										if(maxsize > 0 && maxsize * 1024 * 1024 < tempFilePaths[0].size){
											app.alert('文件过大');return;
										}
									}
								}
		            //for (var i = 0; i < tempFilePaths.length; i++) {
		            	app.showLoading('上传中');
		            	uni.uploadFile({
		            		url: up_url,
		            		filePath: tempFilePaths[0]['path'],
		            		name: 'file',
		            		success: function(res) {
		            			app.showLoading(false);
		            			var data = JSON.parse(res.data);
		            			if (data.status == 1) {
													that.formvaldata[field] = data.url;
													editorFormdata[id] = data.url;
													that.editorFormdata = editorFormdata;
													that.$set(that.editorFormdata, idx,data.url)
													that.getdata();
		            			} else {
		            				app.alert(data.msg);
		            			}
		            		},
		            		fail: function(res) {
		            			app.showLoading(false);
		            			app.alert(res.errMsg);
		            		}
		            	});
		            //}
		        }
		    });
		    // #endif
		    // #ifdef MP-WEIXIN
		        wx.chooseMessageFile({
		          count: 1,
		          //type: 'file',
		          success (res) {
		            // tempFilePath可以作为 img 标签的 src 属性显示图片
		            const tempFilePaths = res.tempFiles
		            console.log(tempFilePaths);
		            
								if(tempFilePaths[0].size > 0){
									var maxsize = that.formdata.content[currentindex].list[idx].val6;
									if(maxsize){
										maxsize = parseFloat(maxsize);
										if(maxsize > 0 && maxsize * 1024 * 1024 < tempFilePaths[0].size){
											app.alert('文件过大');return;
										}
									}
								}
		           
		            //for (var i = 0; i < tempFilePaths.length; i++) {
		            	app.showLoading('上传中');
		                console.log(tempFilePaths[0]);
		            	uni.uploadFile({
		            		url: up_url,
		            		filePath: tempFilePaths[0]['path'],
		            		name: 'file',
		            		success: function(res) {
		            			app.showLoading(false);
		            			var data = JSON.parse(res.data);
		            			if (data.status == 1) {
												that.formvaldata[field] = data.url;
												
												editorFormdata[id] = data.url;
												that.editorFormdata = editorFormdata;
												that.$set(that.editorFormdata, idx,data.url)
												that.getdata();
		            			} else {
		            				app.alert(data.msg);
		            			}
		            		},
		            		fail: function(res) {
		            			app.showLoading(false);
		            			app.alert(res.errMsg);
		            		}
		            	});
		            //}
		          },
		          complete(res){
		              console.log(res)
		          }
		        })
		    // #endif
		},
		removeimg2:function(e){
			var that = this;
			var idx = e.currentTarget.dataset.idx;
			console.log(idx);
			var field = e.currentTarget.dataset.formidx;
			var editorFormdata = this.editorFormdata;
			if(!editorFormdata) editorFormdata = [];
			editorFormdata[idx] = '';
			that.editorFormdata = editorFormdata
			that.test = Math.random();
			that.formvaldata[field] = '';
			that.getdata()
		},
		upVideo:function(e){
		    var that = this;
		    var that = this;
		    var idx = e.currentTarget.dataset.idx;
		    var field = e.currentTarget.dataset.formidx;
				var currentindex = that.currentindex;
		    var editorFormdata = this.editorFormdata;
		    if(!editorFormdata) editorFormdata = [];
				var id = e.currentTarget.dataset.id;
		    var up_url = app.globalData.baseurl + 'ApiImageupload/uploadImg/aid/' + app.globalData.aid + '/platform/' + app.globalData.platform +'/session_id/' +app.globalData.session_id;
		    uni.chooseVideo({
		      sourceType: ['camera', 'album'],
		      success: function (res) {
		        var path = res.tempFilePath;
						if(res.size > 0){
							var maxsize = that.formdata.content[currentindex].list[idx].val6;
							if(maxsize){
								maxsize = parseFloat(maxsize);
								if(maxsize > 0 && maxsize * 1024 * 1024 < res.size){
									app.alert('视频文件过大');return;
								}
							}
						}
		
		        app.showLoading('上传中');
		        console.log(path );
		        uni.uploadFile({
		          url: up_url,
		          filePath: path,
		          name: 'file',
		          success: function(res) {
		            app.showLoading(false);
		            var data = JSON.parse(res.data);
		            if (data.status == 1) {
		              that.formvaldata[field] = data.url;
	
		              editorFormdata[id] = data.url;
		              that.editorFormdata = editorFormdata;
									that.getdata();
		              that.$set(that.editorFormdata, idx,data.url)
		            } else {
		              app.alert(data.msg);
		            }
		          },
		          fail: function(res) {
		            app.showLoading(false);
		            app.alert(res.errMsg);
		          }
		        });
		      }
		    });
		},
		editorBindPickerChange:function(e){
			var that=this
			var idx = e.currentTarget.dataset.idx;
			var val = e.detail.value;
			var editorFormdata = that.editorFormdata;
			if(!editorFormdata) editorFormdata = [];
			editorFormdata[idx] = val;
			that.editorFormdata = editorFormdata
			//console.log(this.editorFormdata);
			this.test = Math.random();			
			var field = e.currentTarget.dataset.formidx;
			that.formvaldata[field] = val;
			that.getdata();
		},
		editorFormSubmit:function(e){
			var that = this;
			if(this.submitDisabled) return ;
			//console.log('form发生了submit事件，携带数据为：', e.detail.value)
			//var subdata = e.detail.value;
			var formcontent = e.currentTarget.dataset.formcontent;
			console.log(formcontent);
			var formid = e.currentTarget.dataset.formid;
			var formdataval = e.detail.value;
			var newformdata = {};
			for (var i = 0; i < formcontent.length;i++){
				//console.log(subdata['form' + i]);
				var forms = formcontent[i].list;
				for(var j=0;j<forms.length;j++){
					if (forms[j].key == 'region') {
							formdataval['form' + forms[j].id] = that.regiondata;
					}
					if (forms[j].key!='separate' && forms[j].val3 == 1 && (formdataval['form' +forms[j].id ] === '' || formdataval['form' + forms[j].id] === null || formdataval['form' + forms[j].id] === undefined || formdataval['form' + forms[j].id].length==0)){
							app.alert(forms[j].val1+' 必填');return;
					}
					if (forms[j].key =='switch'){
							if (subdata['form' + forms[j].id]==false){
									subdata['form' + forms[j].id] = '否'
							}else{
									subdata['form' + forms[j].id] = '是'
							}
					}
					if (forms[j].key == 'input' && forms[j].val4 && formdataval['form' + forms[j].id]!==''){
						if(forms[j].val4 == '2'){ //手机号
							if (!app.isPhone(formdataval['form' + forms[j].id])) {
								app.alert(forms[j].val1+' 格式错误');return;
							}
						}
						if(forms[j].val4 == '3'){ //身份证号
							if (!/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/.test(formdataval['form' + forms[j].id])) {
								app.alert(forms[j].val1+' 格式错误');return;
							}
						}
						if(forms[j].val4 == '4'){ //邮箱
							if (!/^(.+)@(.+)$/.test(formdataval['form' + forms[j].id])) {
								app.alert(forms[j].val1+' 格式错误');return;
							}
						}
					}
					newformdata['form'+forms[j].id] = formdataval['form' + forms[j].id];
				}
		
			}
			console.log(newformdata);
			that.submitDisabled = true;
			app.showLoading('提交中');
		
			var pages = getCurrentPages(); //获取加载的页面
			var currentPage = pages[pages.length - 1]; //获取当前页面的对象
			var thispath = '/' + (currentPage.route ? currentPage.route : currentPage.__route__); //当前页面url 
			var opts = currentPage.$vm.opt;
			app.post('ApiWorkorder/formsubmit', {formid:formid,formdata:newformdata,price:formdataval.price,type:that.type,orderid:that.orderid},function(data){
				app.showLoading(false);
				if (data.status == 0) {
					setTimeout(function () {
						app.error(data.msg);
					}, 100)
					that.submitDisabled = false;
					return;
				}else if(data.status == 1) { //无需付款
					that.subscribeMessage(function () {
						setTimeout(function () {
							app.success(data.msg);
						}, 100)
						setTimeout(function () {
							app.goto('record');
						}, 1000)
					});
					return;
				}else if(data.status==2){
					that.subscribeMessage(function () {
						setTimeout(function () {
							app.goto('/pagesExt/pay/pay?id='+data.payorderid+'&tourl=record');
						}, 100);
					});
				}
				that.submitDisabled = false;
			});
		},
		formChange:function(e){
			var that = this;
			var index = e.currentTarget.dataset.index;
			that.currentindex = index
		},
  }
}
</script>
<style>
	.detail{  background: #fff; padding: 30rpx;}
	.content{ width:90%;background: #fff; margin: 40rpx; border-radius:10rpx; padding: 20rpx;}
	.dp-form-btn{color:#fff;width:90%;height:66rpx;display:flex;align-items:center;justify-content:center;border-radius:50rpx}
	.btnbox{ background: #fff; position: fixed; bottom: 0; width: 100%; padding: 20rpx 0;}
	.form-item .inputbox{ align-items: center; color: #ccc}
	.content .item .uni-input{ color: #ccc}
	
	.form-item {width: 100%;padding: 16rpx 0;background: #fff;display: flex;align-items: center;justify-content:space-between;border-bottom: 1px #ededed solid;}
	.form-item .label {color: #333;width: 150rpx;flex-shrink:0}
	.form-item .radio{transform:scale(.7);}
	.form-item .checkbox{transform:scale(.7);}
	.form-item .input {height: 70rpx;padding-left: 10rpx;text-align: right;flex:1;border:1px solid #eee;padding:0 8rpx;border-radius:2px;}
	.form-item .textarea{height:140rpx;line-height:40rpx;overflow: hidden;flex:1;border:1px solid #eee;border-radius:2px;padding:8rpx}
	.form-item .radio-group{display:flex;flex-wrap:wrap;justify-content:flex-end}
	.form-item .radio{height: 70rpx;line-height: 70rpx;display:flex;align-items:center}
	.form-item .radio2{display:flex;align-items:center;}
	.form-item .radio .myradio{margin-right:10rpx;display:inline-block;border:1px solid #aaa;background:#fff;height:32rpx;width:32rpx;border-radius:50%}
	.form-item .checkbox-group{display:flex;flex-wrap:wrap;justify-content:flex-end}
	.form-item .checkbox{height: 70rpx;line-height: 70rpx;display:flex;align-items:center}
	.form-item .checkbox2{display:flex;align-items:center;height: 40rpx;line-height: 40rpx;}
	.form-item .checkbox .mycheckbox{margin-right:10rpx;display:inline-block;border:1px solid #aaa;background:#fff;height:32rpx;width:32rpx;border-radius:2px}
	.form-item .picker{height: 70rpx;line-height:70rpx;flex:1;text-align:right}
	
	
	.form-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
	.form-imgbox-close{position: absolute;display: block;width:32rpx;height:32rpx;right:-16rpx;top:-16rpx;color:#999;font-size:32rpx;background:#fff;z-index: 2;}
	.form-imgbox-close image{width:100%;height:100%}
	.form-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
	.form-imgbox-img>image{max-width:100%;}
	.form-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
	.form-uploadbtn{position:relative;height:200rpx;width:200rpx}
	
	.dp-form-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
	.dp-form-imgbox-close{position: absolute;display: block;width:32rpx;height:32rpx;right:-10rpx;top:-26rpx;color:#999;font-size:32rpx;background:#999;z-index:9;border-radius:50%}
	.dp-form-imgbox-close .image{width:100%;height:100%}
	.dp-form-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
	.dp-form-imgbox-img>.image{max-width:100%;}
	.dp-form-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
	.dp-form-uploadbtn{position:relative;height:100rpx;width:100rpx}
	
	.dp-form-separate{width: 100%;padding: 20rpx 0;font-size: 30rpx;font-weight: bold;color: #454545; display:flex; justify-content: space-between;align-items: center;}
	.dp-form-separate image{ width:40rpx; height:40rpx }
	
	.myworkorder{ background: #fff; width: 150rpx; height: 70rpx; position: fixed; right: 0%; bottom: 20%; display: flex; align-items: center; border-top-left-radius: 30rpx; border-bottom-left-radius: 30rpx; justify-content: center;}
</style>