<template>
	<view class="content" v-if="isload">
		<view class="xieyibox" v-if="showxieyi">
			<view class="xieyi">
				<rich-text :nodes="basicset.agent_xieyi"></rich-text>
			</view>
			<view class="bottom">
				<view class="button" :style="{background:lefttime>0?'#ccc':'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)',color:'#ffffff'}">
					<text v-if="lefttime>0" class="counter" >（{{lefttime}}）</text>
					<text v-if="lefttime>0">确定</text>
					<text v-else @tap="hidexieyi">确定</text>
				</view>
			</view>
		</view>
		
		
		<view v-if="!showxieyi">
			<form @submit="formSubmit">
				<view class="title"><image src="../../static/img/edit2.png"></image>代理入驻</view>
				<view class="box">
					<view class="form-item">
						<view class="form-label">姓名<text style="color:red"> *</text></view>
						<view class="form-value">
							<input type="text" name="name" placeholder="请输入姓名" :value="detail.name" placeholder-style="font-size:28rpx;color:#cccccc" />
						</view>
					</view>
					<view class="form-item">
						<view class="form-label">手机号<text style="color:red"> *</text></view>
						<view class="form-value">
							<input type="text" name="tel" placeholder="请输入联系电话" :value="detail.tel" placeholder-style="font-size:28rpx;color:#cccccc" />
						</view>
					</view>
					<view class="form-item">
						<view class="form-label">代理城市</view>
						<view class="form-value region">
							<uni-data-picker :localdata="arealist" popup-title="请选择代理城市" @change="areachange"  :placeholder="'请选择代理城市'"></uni-data-picker>
						</view>
					</view>
			</view>
				<!-- 自定义表单Start -->
				
				<view class="box customfields" v-if="formfields.length>0">
					<block  v-for="(item,idx) in formfields"  :key="idx">
						<view :class="(item.key=='textarea' || item.key=='upload')?'form-item-row':'form-item'">
							<view class="form-label">{{item.val1}}<text v-if="item.val3==1" style="color:red"> *</text></view>
							<view :class="'form-value '+item.key">
								<block v-if="item.key=='input'">
									<text v-if="item.val5" style="margin-right:10rpx">{{item.val5}}</text>
									<input :type="(item.val4==1 || item.val4==2) ? 'digit' : 'text'"  :name="'form'+idx" class="input" :placeholder="item.val2" placeholder-style="font-size:28rpx;color:#cccccc" :value="custom_formdata['form'+idx]" @input="setfield" :data-formidx="'form'+idx"/>
								</block>
								<block v-if="item.key=='textarea'">
									<textarea :name="'form'+idx" class='textarea' :placeholder="item.val2" placeholder-style="font-size:28rpx;color:#cccccc"  :value="custom_formdata['form'+idx]" @input="setfield" :data-formidx="'form'+idx"/>
								</block>
								<block v-if="item.key=='radio'">
									<radio-group class="flex" :name="'form'+idx" style="flex-wrap:wrap" @change="setfield" :data-formidx="'form'+idx">
										<label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center">
												<radio class="radio" :value="item1" style="transform: scale(0.8);" :checked="custom_formdata['form'+idx] && custom_formdata['form'+idx]==item1 ? true : false"/>{{item1}}
										</label>
									</radio-group>
								</block>
								<block v-if="item.key=='checkbox'">
									<checkbox-group :name="'form'+idx" class="flex" style="flex-wrap:wrap" @change="setfield" :data-formidx="'form'+idx">
										<label v-for="(item1,idx1) in item.val2" :key="item1.id" class="flex-y-center">
											<checkbox class="checkbox" style="transform: scale(0.8);" :value="item1" :checked="custom_formdata['form'+idx] && inArray(item1,custom_formdata['form'+idx]) ? true : false"/>{{item1}}
										</label>
									</checkbox-group>
								</block>
								<block v-if="item.key=='selector'">
									<picker class="picker" mode="selector" :name="'form'+idx" :value="editorFormdata[idx]" :range="item.val2" @change="editorBindPickerChange" :data-idx="idx" :data-formidx="'form'+idx">
										<view v-if="editorFormdata[idx] || editorFormdata[idx]===0"> {{item.val2[editorFormdata[idx]]}}</view>
										<view v-else style="color: #CCCCCC;">请选择</view>
									</picker>
								</block>
								<block v-if="item.key=='time'">
									<picker class="picker" mode="time" :name="'form'+idx" :value="custom_formdata['form'+idx]" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-idx="idx" :data-formidx="'form'+idx">
										<view v-if="editorFormdata[idx]">{{editorFormdata[idx]}}</view>
										<view v-else style="color: #CCCCCC;">请选择</view>
									</picker>
								</block>
								<block v-if="item.key=='date'">
									<picker class="picker" mode="date" :name="'form'+idx" :value="custom_formdata['form'+idx]" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-idx="idx" :data-formidx="'form'+idx">
										<view v-if="editorFormdata[idx]">{{editorFormdata[idx]}}</view>
										<view v-else style="color: #CCCCCC;">请选择</view>
									</picker>
								</block>
							
								<block v-if="item.key=='region'">
									<uni-data-picker :localdata="items" popup-title="请选择省市区" :placeholder="custom_formdata['form'+idx] || '请选择省市区'" @change="onchange" :data-formidx="'form'+idx"></uni-data-picker>
									<input type="text" style="display:none" :name="'form'+idx" :value="regiondata ? regiondata : custom_formdata['form'+idx]"/>
								</block>
								<block v-if="item.key=='upload'">
									<input type="text" style="display:none" :name="'form'+idx" :value="editorFormdata[idx]"/>
									<view class="flex" style="flex-wrap:wrap;padding-top:20rpx">
										<block v-if="editorFormdata[idx] && editorFormdata[idx].length>0">
											<view class="form-imgbox" v-for="(pic, pindex) in editorFormdata[idx]">
												<view class="form-imgbox-close" @tap="removeimgPics" :data-idx="idx" :data-imgidx="pindex" :data-formidx="'form'+idx">
													<image src="/static/img/ico-del.png"></image>
												</view>
												<view class="form-imgbox-img">
													<image :src="pic" @click="previewImage" :data-url="pic" mode="widthFix" :data-idx="idx"/>
												</view>
											</view>
										</block>
										<view class="form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 32rpx',backgroundSize:'50rpx 50rpx',backgroundColor:'#F3F3F3'}" @click="editorChooseImage" :data-idx="idx" :data-formidx="'form'+idx"></view>
									</view>
								</block>
							</view>
						</view>
					</block>
					<view style="display:none">{{test}}</view>
				</view>
			
				<view class="form-option">
					
					<button v-if="approve==-1 || approve==2" class="btn" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)',color:'#ffffff'}" form-type="submit" data-type="1">确定</button>
					<button v-if="approve==0" class="btn btn1">等待审核</button>
					<button v-if="approve==1" class="btn btn1">已入驻</button>
				</view>
			</form>
		</view>
		<!-- <view v-if="copyright!=''" class="copyright">{{copyright}}</view> -->
		<loading v-if="loading"></loading>
		<popmsg ref="popmsg"></popmsg>
	</view>
</template>
<script>
	var app = getApp();
	export default {
		data() {
			return {
				opt: {},
				loading: false,
				isload: false,
				pre_url: app.globalData.pre_url,
				//自定义表单Start
				show_custom_field:false,
				regiondata:'',
				editorFormdata:{},
				test:'',
				formfields:[],
				formid:'',
				custom_formdata:[],
				items: [],
				formvaldata:{},
				showxieyi:false,
				basicset:{},
				lefttime:15,
				showlevel:2,
				arealist: [],
				area:'',
				tmplids:[]
			}
		},
		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			this.getdata();
		},
		onPullDownRefresh: function(e) {
			
		},
		methods: {
			getdata: function() {
				var that = this;
				var opt = this.opt
				that.loading = true
				app.get('ApiZhaopin/agentApply', {}, function (res) {
					if(res.status==1){
						that.loading = false;
						that.formfields = res.formfields
						that.formid = res.formid
						that.basicset = res.basicset
						// that.info  = res.info
						that.approve  = res.approve
						that.custom_formdata = res.info
						if(res.detail){
							that.detail = res.detail
							that.area = res.detail.area
						}
						that.tmplids = res.tmplids
						that.initarea()
						that.loaded()
						if(that.showxieyi){
							setInterval(function () {
								if(that.lefttime>0){
									that.lefttime = that.lefttime - 1;
								}else{
									return;
								}
							}, 1000);
						}
					}else{
						app.alert(res.msg);
						setTimeout(function () {
							app.goback();
						}, 1000);
					}
				});
			},
			initarea: function() {
				//初始化当前城市
				var that = this
				//地区加载
				uni.request({
					url: app.globalData.pre_url + '/static/area.json',
					data: {},
					method: 'GET',
					header: {
						'content-type': 'application/json'
					},
					success: function(res2) {
						if (that.showlevel < 3) {
							var newlist = [];
							var arealist = res2.data
							for (var i in arealist) {
								var item1 = arealist[i]
								if (that.showlevel == 2) {
									var children = item1.children //市
									var newchildren = [];
									for (var j in children) {
										var item2 = children[j]
										item2.children = []; //去掉三级-县的数据
										newchildren.push(item2)
									}
									item1.children = newchildren
								} else {
									item1.children = []; ////去掉二级-市的数据
								}
								newlist.push(item1)
							}
							that.arealist = newlist
						} else {
							that.arealist = res2.data
						}
					}
				});
			},
			areachange:function(e){
				const value = e.detail.value
				this.area = value[0].text ;
				if(this.showlevel>1){
					this.area += ','+value[1].text
				}
				if(this.showlevel>2){
					this.area += ','+value[2].text
				}
			},
			hidexieyi:function(){
				this.showxieyi = false
			},
			download:function(){
				var file = this.basicset.qianyue_contract
				if(!file) return;
				app.showLoading('文件下载中');
				uni.downloadFile({
					url: file,
					success: (res) => {
					  var filePath = res.tempFilePath;
						if (res.statusCode === 200) {
							uni.openDocument({
								filePath: filePath,
								showMenu: true,
								success: function (res) {
									app.showLoading(false);
									console.log('打开文档成功');
								}
					    });
						}
					},
					fail:function(){
						app.showLoading(false);
						app.error('模板下载成功!');
					}
				});
			},
			//自定义表单
			onchange(e) {
			  const value = e.detail.value
				this.regiondata = value[0].text + ',' + value[1].text + ',' + value[2].text;
			},
			setfield:function(e){
				var field = e.currentTarget.dataset.formidx;
				var value = e.detail.value;
				this.formvaldata[field] = value;
			},
			editorBindPickerChange:function(e){
				var that = this;
				var idx = e.currentTarget.dataset.idx;
				var val = e.detail.value;
				var editorFormdata = this.editorFormdata;
				
				if(!editorFormdata) editorFormdata = {};
				editorFormdata[idx] = val;
				that.editorFormdata = editorFormdata
				this.test = Math.random();
				var field = e.currentTarget.dataset.formidx;
				this.formvaldata[field] = val;
			},
			checkCustomFormFields:function(e){
				var that = this;
				var subdata = this.formvaldata;
				var formcontent = that.formfields;
				var formid = that.formid;
				var formdata = new Array();
				for (var i = 0; i < formcontent.length;i++){
					//console.log(subdata['form' + i]);
					if (formcontent[i].key == 'region') {
							subdata['form' + i] = that.regiondata;
					}
					if (formcontent[i].val3 == 1 && (subdata['form' + i] === '' || subdata['form' + i] === null || subdata['form' + i] === undefined || subdata['form' + i].length==0)){
							app.alert(formcontent[i].val1+' 必填');return false;
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
							if (!/^1[3456789]\d{9}$/.test(subdata['form' + i])) {
								app.alert(formcontent[i].val1+' 格式错误');return false;
							}
						}
						if(formcontent[i].val4 == '3'){ //身份证号
							if (!/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/.test(subdata['form' + i])) {
								app.alert(formcontent[i].val1+' 格式错误');return false;
							}
						}
						if(formcontent[i].val4 == '4'){ //邮箱
							if (!/^(.+)@(.+)$/.test(subdata['form' + i])) {
								app.alert(formcontent[i].val1+' 格式错误');return false;
							}
						}
					}
					formdata.push(subdata['form' + i])
				}
				return subdata;
			},
			editorChooseImage: function (e) {
				var that = this;
				var idx = e.currentTarget.dataset.idx;
				var tplindex = e.currentTarget.dataset.tplindex;
				var editorFormdata = this.editorFormdata;
				if(!editorFormdata) editorFormdata = {};
				var pics = [];
				if(editorFormdata.hasOwnProperty(idx)){
					pics = editorFormdata[idx]
				}
				app.chooseImage(function(urls){
					for(var i=0;i<urls.length;i++){
						pics.push(urls[i]);
					}
					editorFormdata[idx] = pics
					that.editorFormdata = editorFormdata
					that.test = Math.random();
					that.formvaldata['form'+idx] = pics;
				},5)
			},
			removeimgPics:function(e){
				var that = this;
				var idx = e.currentTarget.dataset.idx;
				var imgindex = e.currentTarget.dataset.imgidx;
				var pics = that.editorFormdata[idx]
				var newpics = [];
				for(var i in pics){
					if(i!=imgindex){
						newpics.push(pics[i])
					}
				}
				that.editorFormdata[idx] = newpics
				that.formvaldata['form'+idx] = newpics
				that.test = Math.random();
			},
			formSubmit: function (e) {
				var that = this;
			  var formdata = e.detail.value;
				//如果有自定义表单则验证表单内容
				var postdata = {};
				postdata['area'] = that.area
				postdata['name'] = formdata.name
				postdata['tel'] = formdata.tel
				postdata['id'] = that.detail?that.detail.id:0
				if(that.formfields.length>0){
					var customformdata = {};
					var customData = that.checkCustomFormFields();
					if(!customData){
						return;
					}
					postdata['customformdata'] = customData
					postdata['customformid'] = that.formid
				}
				
			  // if (that.xystatus == 1 && !that.isagree) {
			  //   app.error('请先阅读并同意用户注册协议');
			  //   return false;
			  // }
				app.showLoading('提交中');
			  app.post("ApiZhaopin/agentApply", postdata, function (data) {
					app.showLoading(false);
			    if (data.status == 1) {
			      app.alert(data.msg,function(){
			      	that.subscribeMessage(function () {
			      	  setTimeout(function () {
			      			that.getdata()
			      	  }, 1000);
			      	});
			      });
			    } else {
			      app.alert(data.msg);
			    }
			  });
			},
	}
}
</script>
<style>
	@import "../common.css";
	.content{color:#222222}
	.title{background: #FFFFFF;padding: 30rpx; font-size: 36rpx;font-weight:bold;}
	.title image{width: 46rpx;height: 46rpx;vertical-align: text-bottom;margin-right: 10rpx;}
	.box{background:#FFFFFF;padding: 0 30rpx;margin-bottom: 20rpx;}
	.hui{color: #CCCCCC;}
	.form-item{display: flex;justify-content: flex-start;align-items: center;border-bottom: 1rpx solid #f6f6f6; padding: 20rpx 0;}
	.form-value textarea{padding: 10rpx 0;}
	.form-label{flex-shrink: 0;width: 200rpx;flex-wrap: wrap;/* text-align: justify;text-align-last: justify; */padding-right: 30rpx;}
	.form-value{flex: 1;}
	.form-tips{color: #CCCCCC;font-size: 28rpx;padding: 20rpx 0;}
	.form-value .down{width: 32rpx;height: 32rpx;vertical-align:middle;flex-shrink: 0;}
	.form-value.radio label{margin-right: 20rpx;}
	.form-value.upload{display: flex;align-items: center;flex-wrap: wrap;}
	/deep/.input-value-border{border: none;}
	/deep/.input-value{line-height: normal;padding: 0;}
	/* 行排列 */
	.form-item-row{border-bottom: 1rpx solid #f6f6f6; padding: 20rpx 0;}
	.form-item-row .form-label,.form-item-row .form-value{width: 100%;}
	.form-item-row .form-value textarea{width: 100%;height: 200rpx;}
	
	.form-option{display: flex;justify-content: center;padding: 30rpx;}
	.form-option .btn{text-align: center;padding:6rpx 30rpx;width: 96%;}
	
	/* 图片 */
	.form-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
	.form-imgbox-close{position: absolute;display: block;width:32rpx;height:32rpx;right:-16rpx;top:-16rpx;color:#999;font-size:32rpx;background:#fff}
	.form-imgbox-close image{width:100%;height:100%;z-index: 9;}
	.form-imgbox-img{display: block;width:120rpx;height:120rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
	.form-imgbox-img>image{max-width:100%;}
	.form-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
	.form-uploadbtn{position:relative;height:116rpx;width:116rpx}
	
	.box .form-item:last-child{border: none;}
	
	.btn1{background: #b7b7b7;color: #FFFFFF;}
	
	
	.xieyibox{color:#222222;padding: 30rpx;background: #FFFFFF;}
	.xieyibox .xieyi{line-height: 50rpx;overflow-y: scroll;min-height: 1000rpx;margin-bottom: 60rpx;}
	.xieyibox .bottom{position: fixed;bottom: 0;left: 0;width: 100%;background: #FFFFFF;}
	.xieyibox .bottom .button{text-align: center;width: 90%;height: 90rpx;line-height: 90rpx;font-size: 36rpx;font-weight: bold;margin: 10rpx 5%;border-radius: 60rpx;}
	.xieyibox .disabled{background: #ccc;}
	
	.down{padding: 30rpx;line-height: 50rpx;}
	.down .tips{color: #b4b4b4;}
	.down .file{color: #007AFF;}
</style>
