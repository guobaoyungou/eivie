<template>
	<view class="content" v-if="isload">
		<view :class="showxieyi?'hide':'show'">
		<form @submit="formSubmit">
			<view class="headpic">
				<view v-if="thumb" class="thumb" @tap="uploadthumb"><image :src="thumb" mode="widthFix"></image></view>
				<view @tap="uploadthumb" class="camera" v-if="thumb==''">
					<view><image :src="pre_url+'/static/img/camera2.png'"></image></view>
					<view>添加头像</view>
				</view>
			</view>
			<!-- <view class="box">
				<view class="form-item">
					<view class="form-label">标题<text style="color:red"> *</text></view>
					<view class="form-value">
						<input type="text" name="title" placeholder="请输入标题" placeholder-style="font-size:28rpx;color:#cccccc" />
					</view>
				</view>
			</view> -->
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
						<input type="text" name="tel" placeholder="请输入手机号" :value="detail.tel" placeholder-style="font-size:28rpx;color:#cccccc" />
					</view>
				</view>
				<view class="form-item">
					<view class="form-label">年龄</view>
					<view class="form-value flex-sb">
						<input type="number" name="age" placeholder="请输入年龄" :value="detail.age" placeholder-style="font-size:28rpx;color:#cccccc" />
						<text class="hui">岁</text>
					</view>
				</view>
				<view class="form-item">
					<view class="form-label">性别</view>
					<view class="form-value radio">
						<radio-group name="sex">
							<!-- <label>
									<radio value="保密" style="transform: scale(0.8);" />保密
							</label> -->
							<label>
									<radio value="1" style="transform: scale(0.8);" :checked="detail.sex==1?true:false" />男
							</label>
							<label>
									<radio value="2" style="transform: scale(0.8);" :checked="detail.sex==2?true:false" />女
							</label>
						</radio-group>
					</view>
				</view>
				<!-- <view class="form-item">
					<view class="form-label">生日<text style="color:red"> *</text></view>
					<view class="form-value">
						<picker class="picker" mode="date" name="birthday" @change="birthdayChange" >
							<view :class="birthday?'':'hui'">{{birthday?birthday:'请选择生日'}}</view>
						</picker>
					</view>
				</view> -->
			</view>
				
			<view class="box">
				<view class="form-item">
					<view class="form-label">是否在职<text style="color:red"> *</text></view>
					<view class="form-value radio">
						<radio-group name="has_job">
							<label>
									<radio value="1" style="transform: scale(0.8);" :checked="detail.has_job==1?true:false" />在职
							</label>
							<label>
									<radio value="2" style="transform: scale(0.8);" :checked="detail.has_job==2?true:false" />离职
							</label>
						</radio-group>
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
					<view class="form-label">求职岗位</view>
					<view class="form-value radio">
						<view :class="cnames?'':'hui'" class="flex-sb"  @tap.stop="showCidSelect">
							<text>{{cnames?cnames:'请选择求职岗位'}}</text>
							<image class="down" src="../../static/img/arrowright.png">
						</view>
					</view>
				</view>
				<view class="form-item">
					<view class="form-label">期望薪资</view>
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
					<view class="form-label">个人优势</view>
					<view class="form-value region">
						<view :class="tags.length>0?'':'hui'" class="flex-sb" @tap="showTags">
							<text>{{tags.length>0?tags.join(','):'请选择个人优势'}}</text>
							<image class="down" src="../../static/img/arrowright.png">
						</view>
					</view>
				</view>
				<view class="form-item">
					<view class="form-label">期望省市</view>
					<view class="form-value region">
						<view :class="basearea?'':'hui'" class="flex-sb" @tap="showBasearea">
							<text>{{basearea?basearea:'请选择期望省市'}}</text>
							<image class="down" src="../../static/img/arrowright.png">
						</view>
					</view>
				</view>
			</view>
			<view class="box">
				<view class="form-item-row">
					<view class="form-label">自我介绍</view>
					<view class="form-value textarea">
						<textarea name="desc" placeholder="请填写自我介绍" :value="detail.desc" placeholder-style="color:#cccccc;font-size:28rpx"></textarea>
					</view>
				</view>
			</view>
			<!-- 自定义表单Start -->
			<view class="box customfields" v-if="formfields.length>0">
				<block  v-for="(item,idx) in formfields"  :key="idx">
					<view :class="(item.key=='textarea' || item.key=='upload')?'form-item-row':'form-item'">
						<view class="form-label">{{item.val1}}{{idx}}<text v-if="item.val3==1" style="color:red"> *</text></view>
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
								<picker class="picker" mode="time" :name="'form'+idx" value="" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-idx="idx" :data-formidx="'form'+idx">
									<view v-if="editorFormdata[idx]">{{editorFormdata[idx]}}</view>
									<view v-else style="color: #CCCCCC;">请选择</view>
								</picker>
							</block>
							<block v-if="item.key=='date'">
								<picker class="picker" mode="date" :name="'form'+idx" value="" :start="item.val2[0]" :end="item.val2[1]" :range="item.val2" @change="editorBindPickerChange" :data-idx="idx" :data-formidx="'form'+idx">
									<view v-if="editorFormdata[idx]">{{editorFormdata[idx]}}</view>
									<view v-else style="color: #CCCCCC;">请选择</view>
								</picker>
							</block>
						
							<block v-if="item.key=='region'">
								<uni-data-picker :localdata="items_custom" popup-title="请选择省市区" :placeholder="custom_formdata['form'+idx] || '请选择省市区'" @change="onchange" :data-formidx="'form'+idx"></uni-data-picker>
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
			<view class="xieyi" @tap="showxieyiContent">
				<image :src="qiuzhiset.xieyi_pic" mode="widthFix">
			</view>
			<!-- 保密协议 -->
			<view class="box yinsi">
				<radio-group name="secret_type">
					<view class="flex-sb yinsi-item">
						<view class="yinsi-title">手机号隐私保护</view>
						<view><text class="yinsi-tip">加密手机号</text><radio style="transform: scale(0.7);" value="1" :checked="(detail && detail.secret_type==0)?true:false"></radio></view>
					</view>
					<view class="flex-sb yinsi-item">
						<view class="yinsi-title">其他隐私保护</view>
						<view><text class="yinsi-tip">头像，姓名，照片等</text><radio style="transform: scale(0.7);" value="2" :checked="(detail && detail.secret_type==2)?true:false"></radio></view>
					</view>
					<view class="flex-sb yinsi-item">
						<view class="yinsi-title">全部隐私保护</view>
						<view><text class="yinsi-tip">全部信息加密</text><radio style="transform: scale(0.7);" value="3" :checked="(detail && detail.secret_type==3)?true:false"></radio></view>
					</view>
					<view class="flex-sb yinsi-item">
						<view class="yinsi-title">取消选择</view>
						<view><text class="yinsi-tip">公开个人信息</text><radio style="transform: scale(0.7);" value="0"></radio></view>
					</view>
				</radio-group>
			</view>
			<!-- 保密协议 -->
			<view class="form-option">
				<!-- <button class="btn btn1" data-type="2" @tap="draft">保存草稿</button> -->
				<button class="btn" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)',color:'#ffffff'}" form-type="submit" data-type="1">确定发布</button>
			</view>
		</form>
		</view>
		<view class="xieyi-content" :class="showxieyi?'show':'hide'">
			<rich-text :nodes="qiuzhiset.xieyi"></rich-text>
			<view class="xieyi-opt"><button @tap="showxieyiContent" class="btn" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)',color:'#ffffff'}">确定</button></view>
		</view>
		<!-- 岗位分类Start -->
		<view v-if="isshowcid" class="popup__container popup_cid">
			<view class="popup__overlay" @tap.stop="hideCidSelect"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<view class="popup_cancel"></view>
					<text class="popup__title-text">请选择岗位</text>
					<view class="popup_ok" @tap.stop="hideCidSelect">确定</view>
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
						<block v-for="(item1,index2) in curcategory" :key="index2">
							<view class="choose-item" @tap="choosecid" :data-index="index2" :class="item1.checked==1?'on':''">{{item1.name}}</view>
						</block>
					</view>
				</view>
			</view>
		</view>
		<!-- 岗位分类End -->
		<!-- 期望省份Start -->
		<!-- <view v-if="isbasearea" class="popup__container popup_area">
			<view class="popup__overlay" @tap.stop="hideBasearea"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">请选择省份</text>
					<image src="/static/img/close.png" class="popup__close" @tap.stop="hideBasearea" />
				</view>
				<view class="popup__content flex-sb">
					<view class="cate-right choose-box">
						<block v-for="(item,index) in items" :key="index">
							<view class="choose-item" @tap="chooseBasearea" :data-index="index" :class="item.checked?'on':''">{{item.text}}</view>
						</block>
					</view>
				</view>
			</view>
		</view> -->
		<!-- 期望省份End -->
		<!-- 个人特长Start -->
		<view v-if="isshowtags" class="popup__container popup_area">
			<view class="popup__overlay" @tap.stop="hideTags"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<view class="popup_cancel"></view>
					<text class="popup__title-text">请选择个人优势</text>
					<view class="popup_ok" @tap.stop="hideTags">确定</view>
				</view>
				<view class="popup__content flex-sb">
					<view class="cate-right choose-box">
						<block v-for="(item,index) in tagslist" :key="index">
							<view class="choose-item" @tap="choosetags" :data-index="index" :class="item.checked?'on':''">{{item.name}}</view>
						</block>
					</view>
				</view>
			</view>
		</view>
		<view v-if="isbasearea" class="alert">
			<view @click="isbasearea=false;provinceIndex=3" class="alert_hide"></view>
			<view class="alert_module">
				<view class="alert_head">
					<view @click="isbasearea=false;provinceIndex=3" class="alert_cancel flex-xy-center">取消</view>
					<view @click="areaClick" class="alert_confirm flex-xy-center">确定</view>
				</view>
				<view class="alert_data">
					<view class="alert_content">
						<swiper class="alert_content" vertical duration="100" display-multiple-items='7' @change="provinceChange($event)">
							<swiper-item v-for="(item,index) in items" :key="index" class="alert_item flex-xy-center" @click="provinceClick(index)">
								<view v-if="textState" :class="item.active?'alert_active':''">{{item.text}}</view>
							</swiper-item>
						</swiper>
					</view>
					<view class="alert_content">
						<swiper class="alert_content" v-if="areaState" vertical duration="100" display-multiple-items='7'>
							<swiper-item v-for="(item,index) in items[provinceIndex].children" :key="index" class="alert_item flex-xy-center" @click="cityClick(index)">
								<view class="area_item" v-if="textState" :class="item.active?'alert_active':''">
									<view>{{item.text}}</view><view v-if="item.active" class="duihao"></view>
								</view>
							</swiper-item>
						</swiper>
					</view>
					<view>
						<view class="alert_shade"></view>
						<view class="alert_shade"></view>
					</view>
				</view>
			</view>
		</view>
		<!-- 个人特长End -->
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
				cids:[],
				cnames:'',
				birthday:'',
				area:'',//工作地点
				secret_type:0,
				/* 薪资 */
				salarylist:[],
				salaryindex:-1,
				/* age */
				agelist:[],
				ageindex:-1,
				/* 工作经验 */
				explist:[],
				expindex:-1,
				//地区选择
				isbasearea:false,
				areaindex:[],
				basearea:'',
				areaAry:[],
				/* 个人优势 */
				tagslist:[],
				tags:[],
				isshowtags:false,
				//自定义表单Start
				show_custom_field:false,
				regiondata:'',
				editorFormdata:{},
				test:'',
				formfields:[],
				formid:'',
				custom_formdata:[],
				items: [],
				items_custom:[],
				formvaldata:{},
				qiuzhiset:{},
				showxieyi:false,
				
				provinceIndex:0,
				areaState:true,
				textState:true,
				tmplids:[],
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
				app.get('ApiZhaopin/qiuzhiAdd', {id:that.id}, function (res) {
					if(res.status==2){
						app.alert(res.msg, function () {
						  app.goto('/zhaopin/qiuzhi/apply');
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
							//期望薪资
							if(res.detail.salary){
								for(let i in that.salarylist){
									if(that.salarylist[i]==res.detail.salary){
										that.salaryindex = i
									}
								}
							}
							//求职岗位
							that.cnames = res.detail.cnames
							that.tags = res.detail.tags.split(',');
							that.basearea = res.detail.area;
							that.thumb = res.detail.thumb
							that.secret_type = res.detail.secret_type
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
			areaClick(){
				let areaAry = [];
				var basearea = '';
				for(let i=0;i<this.items.length;i++){
					var baseareaI = ''
					if(this.items[i].active){
						baseareaI = this.items[i].text
						let item={
							text:this.items[i].text,
							value:this.items[i].value,
							children:[]
						}
						
						//四个直辖市 不要二级
						if(
							this.items[i].text!='北京市' 
							&& this.items[i].text!='上海市' 
							&& this.items[i].text!='天津市' 
							&& this.items[i].text!='重庆市'
						){
							var baseareaC = [];
							for(let j=0;j<this.items[i].children.length;j++){
								if(this.items[i].children[j].active){
									baseareaC.push(this.items[i].children[j].text);
									let itemS={
										text:this.items[i].children[j].text,
										value:this.items[i].children[j].value
									}
									item.children.push(itemS)
								}
							}
							if(baseareaC.length>0){
								baseareaI = baseareaI+'-'+baseareaC.join(',')
							}
						}
						if(basearea){
							basearea = basearea+'/'+baseareaI
						}else{
							basearea = baseareaI
						}
						areaAry.push(item)
					}
				}
				if(areaAry.length){
					this.isbasearea = false;
					this.basearea = basearea;
					this.areaAry = areaAry
				}else{
					app.error('请选择地区');
				}
			},
			provinceChange(e){
				this.areaState = false
				this.provinceIndex = e.target.current + 3;
				if(this.items[this.provinceIndex].text=='全国'){
					this.items[this.provinceIndex].children = []
				}else{
					if(this.items[this.provinceIndex].addState!=1){
						this.items[this.provinceIndex].addState=1
						this.items[this.provinceIndex].children = [{},{},{},...this.items[this.provinceIndex].children,{},{},{}]
					}
				}
				setTimeout(()=>{
					this.areaState = true
				});
			},
			provinceClick(index){
				let ary = [];
				if(this.items[index].text=='全国'){
					if(this.items[index].active==true){
						this.items[index].active = false
					}else{
						this.items[index].active = true
						//下面所有的省份选中置空
						for(var i=0;i<this.items.length;i++){
							if(i!=index && this.items[i].active==true){
								this.items[i].active = false;
								// console.log(this.items[i])
								if(this.items[i].children){
									for(var j=0;j<this.items[i].children.length;j++){
										this.items[i].children[j].active = false
									}
								}
							}
						}
					}
				}else{
					if(this.items[index].active==true){
						this.items[index].active = false
					}else{
						//是不是选中了全国
						var hasAll = false;
						for(var i=0;i<this.items.length;i++){
							if(this.items[i].text=='全国' && this.items[i].active==true){
								hasAll = true;
								break;
							}
						}
						if(hasAll){
							app.error('您已经选择了全国');
							return;
						}
						for(let i=0;i<this.items[index].children.length;i++){
							if(this.items[index].children[i].active){
								ary.push(i);
							}
						}
						if(ary.length){
							this.items[index].active = true;
						}else{
							if(this.items[index].active){
								this.items[index].active = false;
							}else{
								this.items[index].active = true;
							}
						}
					}
				}
				this.textState = false
				setTimeout(()=>{
					this.textState = true
				});
			},
			cityClick(index){
				if(!this.items[this.provinceIndex].children[index].text){
					return;
				}
				if(this.items[this.provinceIndex].children[index].active){
					this.items[this.provinceIndex].children[index].active = false
				}else{
					//是不是有全国
					if(this.items[this.provinceIndex].text!='全国'){
						var hasAll = false;
						var areaAry = this.areaAry;
						for(var i in this.items){
							if(this.items[i].text=='全国' && this.items[i].active==true){
								hasAll = true;
								break;
							}
						}
						console.log('q:'+hasAll)
						if(hasAll && !this.items[this.provinceIndex].children[index].active){
							app.error('您已经选择了全国');
							return;
						}else{
							this.items[this.provinceIndex].active = true
							this.items[this.provinceIndex].children[index].active = true
						}
					}
				}
				this.textState = false
				setTimeout(()=>{
					this.textState = true
				});
			},
			showxieyiContent:function(e){
				this.showxieyi = this.showxieyi?false:true
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
							var qiuzhi = set.qiuzhi
							that.qiuzhiset = qiuzhi
							var tagslist = [];
							if(qiuzhi.hasOwnProperty('tags')){
								var tagsArr = qiuzhi.tags?qiuzhi.tags:[];
								for(var i in tagsArr){
									var wl = {}
									wl.name = tagsArr[i]
									wl.checked = 0
									tagslist.push(wl)
								}
							}
							that.tagslist = tagslist
						}
						//获取详情
						that.getadd();
						//地区加载
						uni.request({
							url: app.globalData.pre_url+'/static/area.json',
							data: {},
							method: 'GET',
							header: { 'content-type': 'application/json' },
							success: function(res2) {
								that.items_custom = res2.data
								res2.data = [{},{},{},{text: "全国", value: "0",children:[]},...res2.data,{},{},{}]
								that.provinceIndex = that.provinceIndex + 3
								that.items = res2.data;
								that.items[that.provinceIndex].addState = 1;
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
				var categorylist = that.categorylist;
				var category = categorylist[index1].child
			
				if(category[index2].checked){
					category[index2].checked = 0;
				}else{
					category[index2].checked = 1;
				}
				categorylist[index1].child = category
				var cids = [];
				var cnames = [];
				for(var i in categorylist){
					for(var j in categorylist[i].child){
						if(categorylist[i]['child'][j].checked){
							cids.push(categorylist[i]['child'][j].id)
							cnames.push(categorylist[i]['child'][j].name)
						}
					}
				}
				that.categorylist = categorylist;
				that.cnames = cnames.join(',')
				that.cids = cids
			},
			selectChange:function(e){
				var that = this 
				var index = e.detail.value
				var field = e.currentTarget.dataset.field
				that[field+'index'] = index
			},
			showBasearea:function(e){
				this.isbasearea = true
				// console.log(this.items)
			},
			hideBasearea:function(e){
				this.isbasearea = false
			},
			chooseBasearea:function(e){
				var that = this
				var index = e.currentTarget.dataset.index
				var items = that.items
				var checked = items[index].checked
				if(!checked || checked==undefined){
					if(that.areaindex.length>=5){
						app.error('最多可选择5个省市哦');
						return;
					}
					items[index].checked = 1;
				}else{
					items[index].checked = 0;
				}
				var areaindex = [];
				var basearea = [];
				for(var i in items){
					if(items[i].checked){
						areaindex.push(i)
						basearea.push(items[i].text)
					}
				}
				that.areaindex = areaindex
				that.basearea = basearea.join(',')
			},
			//个人优势
			showTags:function(){
				this.isshowtags = true
			},
			hideTags:function(){
				this.isshowtags = false
			},
			choosetags:function(e){
				var that = this
				var list = that.tagslist
				var index = e.currentTarget.dataset.index
				var checked = list[index].checked
				if(!checked){
					if(that.tags.length>=5){
						app.error('最多可选择5个个人标签哦');
						return;
					}
					list[index].checked = 1;
				}else{
					list[index].checked = 0;
				}
				var tags = [];
				for(var i in list){
					if(list[i].checked){
						tags.push(list[i].name)
					}
				}
				that.tags = tags
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
				
				console.log('eee');
				console.log(subdata);
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
				console.log(formdata)
				if (that.thumb == ''){
				  app.alert('请上传头像');
				  return;
				}
			  /* if (formdata.title == ''){
			    app.alert('请输入标题');
			    return;
			  } */
				if (formdata.tel == ''){
				  app.alert('请输入手机号');
				  return;
				}
				if (!/^1[3456789]\d{9}$/.test(formdata.tel)) {
					app.alert('手机号格式错误');return false;
				}
				if (formdata.has_job == ''){
				  app.alert('请选择是否在职');
				  return;
				}
			  // if (formdata.desc == '') {
			  //   app.alert('请输入自我介绍');
			  //   return;
			  // }
			 var postdata = {};
				var info = {
					id:that.id,
					thumb:that.thumb,
					title:formdata.title,
					name:formdata.name,
					tel:formdata.tel,
					age:formdata.age,
					cids:that.cids.join(','),
					cnames:that.cnames,
					sex:formdata.sex,
					desc:formdata.desc,
					area:that.basearea,
					secret_type:formdata.secret_type,
					tags:that.tags.join(',')
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
			  app.post("ApiZhaopin/qiuzhiSave", postdata, function (data) {
					app.showLoading(false);
			    if (data.status == 1) {
			      app.success(data.msg);
						if(!data.needApply){
							var url = '/zhaopin/zhaopin/my?type=1'
						}else{
							var url = '/zhaopin/qiuzhi/apply'
						}
						that.subscribeMessage(function () {
						  setTimeout(function () {
						    app.goto(url,'redirect')
						  }, 1000);
						});
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
	.headpic .thumb{width: 100%;height: 220rpx;display: flex;justify-content: center;align-items: center;}
	.headpic .thumb image{height: 180rpx;width: 180rpx;border-radius: 50%;}
	.headpic .camera{padding: 30rpx;}
	.headpic .camera image{width: 120rpx;height: 120rpx;}
	
	
	/* modal */
	.popup__content{padding:0 20rpx;overflow-y: scroll;}
	.popup__modal{border-radius: 0;max-height: 540rpx;min-height: 540rpx;overflow-y: scroll;}
	.popup__title{background: #f6f6f6;padding: 20rpx;display: flex;justify-content: space-between;align-items: center;}
	.popup__title .popup_cancel, .popup__title .popup_ok{flex-shrink: 0;color: #007aff;font-size: 32rpx;}
	.popup__title .popup__close{width: 24rpx;height: 24rpx;}
	
	.popup__content .choose-box{display: flex;justify-content: flex-start;flex-wrap: wrap;align-items: center;}
	.popup__content .choose-box .choose-item{width: 47%;flex-shrink: 0;overflow: hidden;background: #F6F6F6;text-align: center;padding:16rpx;margin-bottom: 16rpx;margin: 6rpx 4rpx;}
	.popup__content .choose-box .choose-item.on{color: #FE924A;background:#fe924a30;}
	
	.popup_cid .cate-left{height: 540rpx;width: 20%;flex-shrink: 0;line-height: 70rpx;border-right: 1rpx solid #f6f6f6;margin-right: 20rpx;padding-right: 10rpx;}
	.popup_cid .cate-left .dot{color: #FE924A;border: 3px solid #FE924A;border-radius: 50%;width: 10rpx;height: 10rpx;display: block;opacity: 0;margin-right: 10rpx;}
	.popup_cid .cate-left view.on{color: #FE924A;font-weight: bold;}
	.popup_cid .cate-left .flex-s{flex-wrap: nowrap;overflow: hidden;text-overflow: ellipsis;white-space: nowrap}
	.popup_cid .cate-left view.on .dot{opacity: 1;}
	.popup_cid .cate-right{flex: 1;align-self: flex-start;padding: 20rpx 0;}
	
	.popup_area .choose-box .choose-item{width: 160rpx;line-height: 36rpx;flex-wrap: nowrap;white-space: nowrap;overflow: hidden;text-overflow: ellipsis;overflow: hidden;margin-top: 20rpx;margin-bottom: 0;margin-left: 10rpx;}
	
	.yinsi{padding: 20rpx 30rpx;}
	.yinsi-item{border-bottom: 1rpx solid #f6f6f6;padding: 10rpx 0rpx;}
	.yinsi-item:last-child{border: none;}
	.yinsi .yinsi-title{font-weight: bold;}
	.yinsi .yinsi-tip{color: #999;font-size: 20rpx;}
	
	.xieyi{display: flex;justify-content: center;margin-bottom: 20rpx;padding: 0 20rpx;}
	.xieyi image{width: 100%;}
	.show{/* opacity: 1; */display: block;}
	.hide{/* opacity: 0; */display: none;}
	.xieyi-content{padding: 20rpx 30rpx;background: #FFFFFF;line-height: 50rpx;}
	.xieyi-opt{margin:30rpx;}
	.xieyi-opt button{width: 300rpx;margin: 0 auto;}
	
	.alert{position: fixed;height: 100%;width: 100%;top: 0;left: 0;background: rgba(0, 0, 0, 0.5);z-index: 999999;}
	.alert_hide{position: absolute;height: 100%;width: 100%;top: 0;left: 0;}
	.alert_module{position: absolute;height: 285px;width: 100%;bottom: 0;background: #fff;opacity: 1;}
	.alert_head{position: relative;height: 45px;width: 100%;display: flex;justify-content: space-between;border-bottom: 1px solid #f0f0f0;}
	.alert_cancel{height: 100%;padding: 0 14px;font-size: 17px;overflow: hidden;cursor: pointer;color: #888;}
	.alert_confirm{height: 100%;padding: 0 14px;font-size: 17px;overflow: hidden;cursor: pointer;color: #007aff;}
	.alert_data{position: relative;height: 240px;display: flex;}
	.alert_content{flex: 1;height: 100%;background: #fff;opacity: 1;}
	.alert_item{color: #000;}
	.alert_active{color: #007aff;}
	.alert_shade{position: absolute;height: 102.5px;width: 100%;box-sizing: border-box;pointer-events:none;}
	.alert_shade:nth-child(1){top: 0;left: 0;border-bottom: 1px solid #f0f0f0;background: rgba(255, 255, 255, 0.5);}
	.alert_shade:nth-child(2){bottom: 0;left: 0;border-top: 1px solid #f0f0f0;background: rgba(255, 255, 255, 0.5);}
	
	.alert_content .area_item{display: flex;}
	.alert_content .duihao{ width:12rpx;height:26rpx;border-color:#007aff;border-style:solid;
	border-width:0 3rpx 3rpx 0;align-items: flex-end;text-align: right;margin-left: 20rpx;
	transform: rotate(45deg);}
</style>
