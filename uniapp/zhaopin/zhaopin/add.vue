<template>
	<view class="content" v-if="isload">
		<form @submit="formSubmit">
			<view class="headpic">
				<view v-if="thumb" class="thumb" @tap="uploadthumb"><image :src="thumb" mode="widthFix"></image></view>
				<view @tap="uploadthumb" class="camera" v-if="thumb==''">
					<view><image :src="pre_url+'/static/img/camera2.png'"></image></view>
					<view>添加封面照片</view>
				</view>
			</view>
			<view class="box">
				<view class="form-item">
					<view class="form-label">标题<text style="color:red"> *</text></view>
					<view class="form-value">
						<input type="text" name="title" placeholder="请输入标题" :value="detail.title" placeholder-style="font-size:28rpx;color:#cccccc" />
					</view>
				</view>
			</view>
			<view class="box">
				<view class="form-item">
					<view class="form-label">招聘岗位<text style="color:red"> *</text></view>
					<view class="form-value picker">
						<view :class="cid>0?'':'hui'" class="flex-sb" @tap.stop="showCidSelect">
							<text>{{cname?cname:'请选择招聘岗位'}}</text>
							<image class="down" src="../../static/img/arrowright.png">
						</view>
					</view>
				</view>
				<view class="form-item">
					<view class="form-label">招聘人数<text style="color:red"> *</text></view>
					<view class="form-value flex-sb">
						<input type="number" name="num" placeholder="请输入招聘人数" :value="detail.num" placeholder-style="font-size:28rpx;color:#cccccc" />
						<view class="hui">人</view>
					</view>
				</view>
				<view class="form-item">
					<view class="form-label">薪资范围</view>
					<view class="form-value radio">
						<picker style="width: 100%;" name="salary" :value="salaryindex" mode="selector" :range="salarylist"  @change="selectChange" data-field="salary">
								<view :class="salaryindex>-1?'':'hui'" class="flex-sb">
									<text>{{salaryindex>-1?salarylist[salaryindex]:'请选择薪资范围'}}</text>
									<image class="down" src="../../static/img/arrowright.png">
								</view>
						</picker>
					</view>
				</view>
				<view class="form-item">
					<view class="form-label">性别要求</view>
					<view class="form-value radio">
						<radio-group name="sex">
							<label>
									<radio value="0" style="transform: scale(0.8);" :checked="detail.sex==0?true:false" />不限
							</label>
							<label>
									<radio value="1" style="transform: scale(0.8);" :checked="detail.sex==1?true:false"/>男
							</label>
							<label>
									<radio value="2" style="transform: scale(0.8);" :checked="detail.sex==2?true:false"/>女
							</label>
						</radio-group>
					</view>
				</view>
				<view class="form-item">
					<view class="form-label">年龄范围</view>
					<view class="form-value radio">
						<picker style="width: 100%;" name="age" :value="ageindex" mode="selector" :range="agelist"  @change="selectChange" data-field="age">
								<view :class="ageindex>-1?'':'hui'" class="flex-sb">
									<text>{{ageindex>-1?agelist[ageindex]:'请选择年龄范围'}}</text>
									<image class="down" src="../../static/img/arrowright.png">
								</view>
						</picker>
					</view>
				</view>
				<view class="form-item">
					<view class="form-label">工作经验</view>
					<view class="form-value radio">
						<picker style="width: 100%;" name="experience" :value="expindex" mode="selector" :range="explist"  @change="selectChange" data-field="exp">
								<view :class="expindex>-1?'':'hui'" class="flex-sb">
									<text>{{expindex>-1?explist[expindex]:'请选择工作经验'}}</text>
									<image class="down" src="../../static/img/arrowright.png">
								</view>
						</picker>
					</view>
				</view>
				<view class="form-item">
					<view class="form-label">福利待遇</view>
					<view class="form-value radio">
						<view :class="welfarenames?'':'hui'" class="flex-sb"  @tap.stop="showWelfareSelect">
							<text>{{welfarenames?welfarenames:'请选择福利待遇'}}</text>
							<image class="down" src="../../static/img/arrowright.png">
						</view>
					</view>
				</view>
				<view class="form-item">
					<view class="form-label">所在区域</view>
					<view class="form-value region">
						<uni-data-picker :localdata="items" popup-title="请选择工作地点" :placeholder="area || '请选择工作地点'" @change="areachange"></uni-data-picker>
						<input type="text" style="display:none" name="area" :value="area ? area : ''"/>
					</view>
				</view>
				<view class="form-item">
					<view class="form-label">详细地址<text style="color:red"> *</text></view>
					<view class="form-value">
						<view :class="address?'':'hui'" class="flex-sb"  @tap="selectzuobiao">
							<text>{{address?address:'详细地址'}}</text>
							<image class="location" src="../../static/img/address3.png">
						</view>
					</view>
				</view>
				
			</view>
			<view class="box">
				<view class="form-item-row">
					<view class="form-label">职位描述<text style="color:red"> *</text></view>
					<view class="form-value textarea">
						<textarea name="job_desc" placeholder="请填写职位描述" :value="detail.desc" placeholder-style="color:#cccccc;font-size:28rpx"></textarea>
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
						<input :type="(item.val4==1 || item.val4==2) ? 'digit' : 'text'" :name="'form'+idx" class="input" :placeholder="item.val2" placeholder-style="font-size:28rpx;color:#cccccc" :value="custom_formdata['form'+idx]" @input="setfield" :data-formidx="'form'+idx"/>
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
								<checkbox class="checkbox" style="transform: scale(0.8);" :value="item1" :checked="custom_formdata['form'+idx] && inArray(item1,custom_formdata['form'+idx].split(',')) ? true : false"/>{{item1}}
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
						<input type="text" style="display:none" :name="'form'+idx" :value="editorFormdata[idx].join(',')"/>
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
			<!-- 自定义表单End -->
			<view class="form-option">
				<!-- <button class="btn btn1" data-type="2" @tap="draft">保存草稿</button> -->
				<button class="btn" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)',color:'#ffffff'}" form-type="submit" data-type="1">确定发布</button>
			</view>
		</form>
		<!-- 岗位分类Start -->
		<view v-if="isshowcid" class="popup__container popup_cid">
			<view class="popup__overlay" @tap.stop="hideCidSelect"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<view class="popup_cancel"></view>
					<text class="popup__title-text">请选择岗位</text>
					<view class="popup_ok" @tap.stop="hideCidSelect">确定</view>
					<!-- <image src="/static/img/close.png" class="popup__close" @tap.stop="hideCidSelect" /> -->
				</view>
				<view class="popup__content flex-sb">
					<view class="cate-left">
						<block v-for="(item,index1) in categorylist" :key="index1">
							<view class="flex-s" @tap="changeCategory" :data-index="index1" :class="cindex==index1?'on':''"><text class="dot"></text><text>{{item.name}}</text></view>
						</block>
						<!-- <view class="item-cate"><image src="../../static/peiwan/cate.png"></image>{{item.name}}</view> -->
						<!-- <view class="item-flex">
							<view class="item-tag"  v-for="(item,index) in categorylist" :key="index" :class="item.checked?'on':''" :data-index2="index2" :data-index1="index1" @tap="choosecid">{{item.name}}</view>
						</view> -->
					</view>
					<view class="cate-right choose-box">
						<block v-for="(item,index2) in curcategory" :key="index2">
							<view class="choose-item" @tap="choosecid" :data-index="index2" :class="cid==item.id?'on':''">{{item.name}}</view>
						</block>
					</view>
				</view>
			</view>
		</view>
		<!-- 岗位分类End -->
		<!-- 福利待遇Start -->
		<view v-if="isshowwelfare" class="popup__container popup_welfare">
			<view class="popup__overlay" @tap.stop="hideWelfareSelect"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<view class="popup_cancel"></view>
					<text class="popup__title-text">请选择福利待遇</text>
					<view class="popup_ok" @tap.stop="hideWelfareSelect">确定</view>
				</view>
				<view class="popup__content flex-sb">
					<view class="cate-right choose-box">
						<block v-for="(item,index) in welfarelist" :key="index">
							<view class="choose-item" @tap="choosewelfare" :data-index="index" :class="item.checked?'on':''">{{item.name}}</view>
						</block>
					</view>
				</view>
			</view>
		</view>
		<!-- 福利待遇End -->
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
				thumb:'',
				pics:[],
				isshowcid:false,
				categorylist:[],
				cindex:-1,
				curcategory:[],
				cid:0,
				cname:'',
				//工作地点
				area:'',
				address:'',
				latitude:'',
				longitude:'',
				/* 薪资 */
				salarylist:[],
				salaryindex:-1,
				/* age */
				agelist:[],
				ageindex:-1,
				/* 工作经验 */
				explist:[],
				expindex:-1,
				/* 福利待遇 */
				welfarelist:[],
				welfareindex:[],
				welfarenames:'',
				isshowwelfare:false,
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
				tmplids: [],
				id:0,
				detail:''
			}
		},
		onLoad: function(opt) {
			this.opt = app.getopts(opt);
			this.id = this.opt.id || 0;
			this.getdata();
		},
		onPullDownRefresh: function(e) {
			
		},
		methods: {
			getdata: function() {
				var that = this;
				var opt = this.opt
				that.loading = true
				that.zhaopin()
				that.loaded()
			},
			getadd:function(){
				var that = this
				app.get('ApiZhaopin/zhaopinAdd', {id:that.id}, function (res) {
					if(res.status==2){
						app.alert(res.msg, function () {
						  app.goto('/pagesExt/business/apply');
						});
						return;
					}else if(res.status==1){
						that.loading = false;
						that.formfields = res.formfields
						that.formid = res.formid
						that.tmplids = res.tmplids;
						if(res.detail){
							that.detail = res.detail
							//工作经验
							if(res.detail.experience){
								for(let i in that.explist){
									if(that.explist[i]==res.detail.experience){
										that.expindex = i
									}
								}
							}
							if(res.detail.age){
								for(let i in that.agelist){
									if(that.agelist[i]==res.detail.age){
										that.ageindex = i
									}
								}
							}
							//期望薪资
							if(res.detail.salary){
								for(let i in that.salarylist){
									if(that.salarylist[i]==res.detail.salary){
										that.salaryindex = i
									}
								}
							}
							//求职岗位
							that.cname = res.detail.cname
							that.cid = res.detail.cid
							for(let cindex in that.categorylist){
								for(let ci in that.categorylist[cindex]['child']){
									var citem = that.categorylist[cindex]['child']
									if(citem.id==res.detail.cid){
										that.cindex = cindex;
									}
								}
							}
							that.thumb = res.detail.thumb
							that.address = res.detail.address
							that.latitude = res.detail.latitude
							that.longitude = res.detail.longitude
							that.area = res.detail.area
							that.welfarenames = res.detail.welfare
							if(that.welfarenames){
								var choosewelfare = that.welfarenames.split(',');
								for(let vk in that.welfarelist){
									if(app.inArray(that.welfarelist[vk].name,choosewelfare)){
										that.welfarelist[vk].checked = 1
									}
								}
							}
							
							//自定义字段
							if(res.detail.formdetail){
								that.custom_formdata = res.detail.formdetail
								var formfield = that.formfields
								for(let i in formfield){
									let item = formfield[i]
									that.formvaldata['form'+i] = res.detail.formdetail['form'+i]
									// console.log(item);
									if(item.key=='time' || item.key=='date' || item.key=='time' || item.key=='date'){
										that.editorFormdata[i] = res.detail.formdetail['form'+i]
									}else if(item.key=='region'){
										that.editorFormdata[i] = res.detail.formdetail['form'+i]
										that.regiondata = res.detail.formdetail['form'+i]
									}else if(item.key=='selector'){
										var chooseItems = item.val2;
										if(chooseItems){
											for(let k in chooseItems){
												if(chooseItems[k]==res.detail.formdetail['form'+i]){
													that.editorFormdata[i] = k
													that.formvaldata['form'+i] = k
												}
											}
										}
									}else if(item.key=='upload' || item.key=='checkbox'){
										that.editorFormdata[i] = res.detail.formdetail['form'+i].split(',')
									}
								}
								that.test = Math.random()
								console.log(that.formvaldata)
							}
						}
					}else{
						app.alert(res.msg,function(){
							app.goback()
						});
					}
				});
			},
			zhaopin:function(e){
				var that = this
				app.get('ApiZhaopin/zhaopinSet', {}, function (res) {
						that.loading = false;
						var set = res.zhaopinset
						that.categorylist = res.category;
						if(that.categorylist.length>0){
							that.curcategory = that.categorylist[0]['child']
							that.cindex = 0
						}
						if(set){
							that.salarylist = set.salary;
							that.agelist = res.zhaopinset.age
							that.salarylist = res.zhaopinset.salary
							that.explist = res.zhaopinset.experience
							that.edulist = res.zhaopinset.education
							var welfare = set.welfare
							var welfarelist = [];
							if(welfare.length>0){
								for(var i in welfare){
									var wl = {}
									wl.name = welfare[i]
									wl.checked = 0
									welfarelist.push(wl)
								}
							}
							that.welfarelist = welfarelist
						}
						that.getadd()
						
						//地区加载
						uni.request({
							url: app.globalData.pre_url+'/static/area.json',
							data: {},
							method: 'GET',
							header: { 'content-type': 'application/json' },
							success: function(res2) {
								that.items = res2.data
								that.provinceindex = 0
								that.citylist = that.items[0].children
							}
						});
					});
			},
			uploadthumb:function(e){
				var that = this;
				app.chooseImage(function(urls){
					for(var i=0;i<urls.length;i++){
						that.thumb = urls[i];
						break;
					}
				},1)
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
				},1)
			},
			removeimg:function(e){
				var that = this;
				var index= e.currentTarget.dataset.index
				var field= e.currentTarget.dataset.field
				var pics = that[field]
				pics.splice(index,1)
			},
			//岗位种类选择
			changeCategory:function(e){
				var cindex = e.currentTarget.dataset.index
				this.cindex = cindex
				this.curcategory = this.categorylist[cindex]['child']
			},
			showCidSelect:function(){
				this.isshowcid = true
			},
			hideCidSelect:function(){
				this.isshowcid = false
			},
			choosecid:function(e){
				var that = this
				var index1 = that.cindex
				var index2 = e.currentTarget.dataset.index
				that.cid = that.categorylist[index1]['child'][index2].id
				that.cname = that.categorylist[index1]['child'][index2].name
			},
			selectChange:function(e){
				var that = this 
				var index = e.detail.value
				var field = e.currentTarget.dataset.field
				that[field+'index'] = index
			},
			showWelfareSelect:function(){
				this.isshowwelfare = true
			},
			hideWelfareSelect:function(){
				this.isshowwelfare = false
			},
			choosewelfare:function(e){
				var that = this
				var welfarelist = that.welfarelist
				var index = e.currentTarget.dataset.index
				var checked = welfarelist[index].checked
				if(!checked){
					welfarelist[index].checked = 1;
				}else{
					welfarelist[index].checked = 0;
				}
				var welfareindex = [];
				var welfarename = [];
				for(var i in welfarelist){
					if(welfarelist[i].checked){
						welfareindex.push(i)
						welfarename.push(welfarelist[i].name)
					}
				}
				that.welfarenames = welfarename.join(',')
				that.welfareindex = welfareindex
			},
			areachange:function(e){
				const value = e.detail.value
				this.area = value[0].text + ',' + value[1].text + ',' + value[2].text;
			},
			selectzuobiao: function () {
			  var that = this;
			  uni.chooseLocation({
			    success: function (res) {
			      console.log(res);
			      // that.area = res.address;
			      that.address = res.address;
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
				//
				if (that.thumb == ''){
				  app.alert('请上传封面图片');
				  return;
				}
			  if (formdata.title == ''){
			    app.alert('请输入标题');
			    return;
			  }
				if (that.cid == '' || that.cid == 0) {
				  app.alert('请选择招聘岗位');
				  return;
				}
			  if (formdata.num == '' || formdata.num == 0) {
			    app.alert('请输入招聘人数');
			    return;
			  }
			  if (formdata.job_desc == '') {
			    app.alert('请输入职位描述');
			    return;
			  }
			 var postdata = {};
				var info = {
					id:that.id,
					thumb:that.thumb,
					title:formdata.title,
					cid:that.cid,
					cname:that.cname,
					num:formdata.num,
					sex:formdata.sex,
					welfare:that.welfarenames,
					desc:formdata.job_desc,
					area:formdata.area,
					address:that.address,
					latitude:that.latitude,
					longitude:that.longitude
				}
				if(that.salaryindex>-1){
					info['salary'] = that.salarylist[that.salaryindex]
				}
				if(that.ageindex>-1){
					info['age'] = that.agelist[that.ageindex]
				}
				if(that.expindex>-1){
					info['experience'] = that.explist[that.expindex]
				}
				//如果有自定义表单则验证表单内容
				if(that.formfields.length>0){
					var customformdata = {};
					var customData = that.checkCustomFormFields();
					if(!customData){
						return;
					}
					postdata['customformdata'] = customData
					postdata['customformid'] = that.formid
				}
				postdata['info'] = info
				
			  if (that.xystatus == 1 && !that.isagree) {
			    app.error('请先阅读并同意用户注册协议');
			    return false;
			  }
				app.showLoading('提交中');
			  app.post("ApiZhaopin/zhaopinSave", postdata, function (data) {
					app.showLoading(false);
			    if (data.status == 1) {
						//跳转协议页面
						that.subscribeMessage(function () {
						  setTimeout(function () {
						   app.goto('addxieyi?id='+data.id);
						  }, 1000);
						});
			    //   app.success(data.msg);
			    //   setTimeout(function () {
							// app.goto('/zhaopin/zhaopin/my?type=2','redirect');
			    //   }, 1000);
			    } else {
			      app.error(data.msg);
			    }
			  });
			},
	}
}
</script>
<style>
	@import "../common.css";
	.content{color:#222222}
	.box{background:#FFFFFF;padding: 0 30rpx;margin-bottom: 20rpx;}
	.hui{color: #CCCCCC;}
	.form-item{display: flex;justify-content: flex-start;align-items: center;border-bottom: 1rpx solid #f6f6f6; padding: 20rpx 0;}
	.form-value textarea{padding: 10rpx 0;}
	.form-label{flex-shrink: 0;width: 180rpx;flex-wrap: wrap;/* text-align: justify;text-align-last: justify; */padding-right: 30rpx;}
	.form-value{flex: 1;}
	.form-tips{color: #CCCCCC;font-size: 28rpx;padding: 20rpx 0;}
	.form-value .down{width: 32rpx;height: 32rpx;vertical-align:middle;flex-shrink: 0;}
	.form-value .location{width: 42rpx;height: 42rpx;vertical-align:middle;flex-shrink: 0;}
	.form-value.radio label{margin-right: 20rpx;}
	.form-value.upload{display: flex;align-items: center;flex-wrap: wrap;}
	/deep/.input-value-border{border: none;}
	/deep/.input-value{line-height: normal;padding: 0;}
	/* 行排列 */
	.form-item-row{border-bottom: 1rpx solid #f6f6f6; padding: 20rpx 0;}
	.form-item-row .form-label,.form-item-row .form-value{width: 100%;}
	.form-item-row .form-value textarea{width: 100%;height: 200rpx;}
	
	.form-option{display: flex;justify-content: center;padding: 30rpx;}
	.form-option .btn{text-align: center;padding:10rpx 20rpx;width: 96%;margin: 0 2%;border-radius: 16rpx;}
	.form-option .btn1{border: 1rpx solid #CCCCCC;margin-left: 0;}
	
	/* 图片 */
	.form-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
	.form-imgbox-close{position: absolute;display: block;width:32rpx;height:32rpx;right:-16rpx;top:-16rpx;color:#999;font-size:32rpx;background:#fff}
	.form-imgbox-close image{width:100%;height:100%;z-index: 9;}
	.form-imgbox-img{display: block;width:120rpx;height:120rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
	.form-imgbox-img>image{max-width:100%;}
	.form-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
	.form-uploadbtn{position:relative;height:116rpx;width:116rpx}
	
	.box .form-item:last-child{border: none;}
	.headpic{text-align: center;padding:0;background: #FFFFFF;margin-bottom: 20rpx;}
	.headpic .thumb{width: 100%;height: 400rpx;padding: 20rpx 0;}
	.headpic .thumb image{max-width: 100%;max-height: 100%}
	.headpic .camera{padding: 30rpx;}
	.headpic .camera image{width: 120rpx;height: 120rpx;}
	
	
	/* modal */
	.popup__content{padding:0 20rpx;}
	.popup__modal{border-radius: 0;max-height: 540rpx;min-height: 540rpx;}
	.popup__title{background: #f6f6f6;padding: 20rpx;display: flex;justify-content: space-between;align-items: center;}
	.popup__title .popup_cancel, .popup__title .popup_ok{flex-shrink: 0;color: #007aff;font-size: 32rpx;}
	.popup__title .popup__close{width: 24rpx;height: 24rpx;}
	
	.popup__content .choose-box{display: flex;justify-content: flex-start;flex-wrap: wrap;align-items: center;}
	.popup__content .choose-box .choose-item{width: 48%;flex-shrink: 0;overflow: hidden;background: #F6F6F6;text-align: center;padding:16rpx;margin-bottom: 16rpx;}
	.popup__content .choose-box .choose-item.on{color: #FE924A;background:#fe924a30;}
	
	.popup_cid .cate-left{height: 540rpx;width: 20%;flex-shrink: 0;line-height: 70rpx;border-right: 1rpx solid #f6f6f6;margin-right: 20rpx;padding-right: 10rpx;}
	.popup_cid .cate-left .dot{color: #FE924A;border: 3px solid #FE924A;border-radius: 50%;width: 10rpx;height: 10rpx;display: block;opacity: 0;margin-right: 10rpx;}
	.popup_cid .cate-left view.on{color: #FE924A;font-weight: bold;}
	.popup_cid .cate-left .flex-s{flex-wrap: nowrap;overflow: hidden;text-overflow: ellipsis;white-space: nowrap}
	.popup_cid .cate-left view.on .dot{opacity: 1;}
	.popup_cid .cate-right{flex: 1;align-self: flex-start;padding: 20rpx 0;}
	
	.popup_welfare .choose-box .choose-item{width: 165rpx;line-height: 36rpx;flex-wrap: nowrap;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;overflow: hidden;margin-top: 20rpx;margin-bottom: 0;margin-left: 10rpx;}
</style>
