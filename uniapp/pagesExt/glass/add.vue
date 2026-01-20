<template>
<view class="container">
	<block v-if="isload">
	<view class="top" :style="{background:'linear-gradient('+t('color1')+' 0%,rgba('+t('color1rgb')+',0) 100%)'}"></view>
	<view class="content">
		<form @submit="formSubmit">
			<view class="tips-top">创建视力档案，加速加工眼镜</view>
			<!-- 档案名称 -->
			<view class="box">
				<view class="form-item dangan">
					<view class="form-label">档案名称</view>
					<view class="form-value name-item bb">
						<input type="text" name="name" placeholder="请输入档案名称" :value="detail.name" placeholder-style="font-size:28rpx;color:#cccccc" />
					</view>
				</view>
			</view>
			<!-- 档案名称 -->
			
			<!-- 视力信息 -->
			<view class="box">
				<view class="tips-info">视力信息</view>
				<view class="tips-desc flex">
					<view class="tips-ref">可参考验光单</view>
					<view @tap="goto" data-url="set?field=desc">
						<text class="red">填写说明</text>
						<image :src="pre_url+'/static/img/arrowright2.png'"></image>
					</view>
				</view>
				<view class="form-item">
					<view class="form-label">视力</view>
					<view class="form-value radio">
						<radio-group name="type" @change="typechange">
							<label>
									<radio value="1" style="transform: scale(0.7);" :color="t('color1')" :checked="type==1?true:false" />近视
							</label>
							<label>
									<radio value="2" style="transform: scale(0.7);" :color="t('color1')" :checked="type==2?true:false" />远视
							</label>
							<label>
									<radio value="3" style="transform: scale(0.7);" :color="t('color1')" :checked="type==3?true:false" />远近两用
							</label>
						</radio-group>
					</view>
				</view>
				<view class="form-item bb">
					<view class="form-label">散光</view>
					<view class="form-value radio">
						<radio-group name="is_ats" @change="atsChange">
							<label>
									<radio value="0" style="transform: scale(0.7);" :color="t('color1')" :checked="isAts==0?true:false" />无
							</label>
							<label>
									<radio value="1" style="transform: scale(0.7);" :color="t('color1')" :checked="isAts==1?true:false" />有
							</label>
							
						</radio-group>
					</view>
				</view>
				<view class="form-item table-header">
					<view class="form-label">验光数据</view>
					<view class="form-value">
						<view class="table-item">右眼</view>
						<view class="table-item">左眼</view>
					</view>
				</view>
				<view class="table-body">
					<view class="form-item">
						<view class="form-label">球镜(Sph)</view>
						<view class="form-value">
							<view class="dushu">
								<view class="unit-item">
									<picker style="width: 100%;" name="degress_right" :value="degress_right_index" mode="selector" :range="degresslist"  @change="selectChange" data-field="degress_right">
											<view :class="degress_right_index>-1?'':'hui'" class="flex-e">
												<text>{{degress_right_index>-1?degresslist[degress_right_index]:'右眼'}}</text>
												<image class="down" :src="pre_url+'/static/img/arrowright.png'">
											</view>
									</picker>
								<!-- <input type="number" name="degress_right" placeholder="-18~+10" :value="detail.degress_right" placeholder-style="font-size:24rpx;color:#cccccc" /> -->
								</view>
								<view class="unit-item mgL10">
									<picker style="width: 100%;" name="degress_left" :value="degress_left_index" mode="selector" :range="degresslist"  @change="selectChange" data-field="degress_left">
											<view :class="degress_left_index>-1?'':'hui'" class="flex-e">
												<text>{{degress_left_index>-1?degresslist[degress_left_index]:'左眼'}}</text>
												<image class="down" :src="pre_url+'/static/img/arrowright.png'">
											</view>
									</picker>
								</view>
							</view>
							<!-- <view class="tips-dushu">球镜范围值(-18.00 ~ +10.00)，步长0.25</view> -->
						</view>
					</view>
					<block v-if="isAts==1">
						<view class="form-item">
							<view class="form-label">柱镜(Cyl)</view>
							<view class="form-value">
								<view class="dushu">
									<view class="unit-item">
										<picker style="width: 100%;" name="ats_right" :value="ats_right_index" mode="selector" :range="atslist"  @change="selectChange" data-field="ats_right">
												<view :class="ats_right_index>-1?'':'hui'" class="flex-e">
													<text>{{ats_right_index>-1?atslist[ats_right_index]:'右眼'}}</text>
													<image class="down" :src="pre_url+'/static/img/arrowright.png'">
												</view>
										</picker>
									<!-- <input type="number" name="ats_right" placeholder="-6~+6" :value="detail.ats_right" placeholder-style="font-size:24rpx;color:#cccccc" /> -->
									</view>
									<view class="unit-item mgL10">
										<picker style="width: 100%;" name="ats_left" :value="ats_left_index" mode="selector" :range="atslist"  @change="selectChange" data-field="ats_left">
												<view :class="ats_left_index>-1?'':'hui'" class="flex-e">
													<text>{{ats_left_index>-1?atslist[ats_left_index]:'左眼'}}</text>
													<image class="down" :src="pre_url+'/static/img/arrowright.png'">
												</view>
										</picker>
									</view>
								</view>
								<!-- <view class="tips-dushu">柱镜范围值(-6.00 ~ +6.00)，步长0.25</view> -->
							</view>
						</view>
						<view class="form-item">
							<view class="form-label">轴位(Axis)</view>
							<view class="form-value">
								<view class="dushu">
									<view class="unit-item">
										<picker style="width: 100%;" name="ats_zright" :value="ats_zright_index" mode="selector" :range="atszlist"  @change="selectChange" data-field="ats_zright">
												<view :class="ats_zright_index>-1?'':'hui'" class="flex-e">
													<text>{{ats_zright_index>-1?atszlist[ats_zright_index]:'右眼'}}</text>
													<image class="down" :src="pre_url+'/static/img/arrowright.png'">
												</view>
										</picker>
										<!-- <input type="number" name="ats_zright" placeholder="0-180" :value="detail.ats_zright" placeholder-style="font-size:24rpx;color:#cccccc" /> -->
									</view>
									<view class="unit-item mgL10">
										<picker style="width: 100%;" name="ats_zleft" :value="ats_zleft_index" mode="selector" :range="atszlist"  @change="selectChange" data-field="ats_zleft">
												<view :class="ats_zleft_index>-1?'':'hui'" class="flex-e">
													<text>{{ats_zleft_index>-1?atszlist[ats_zleft_index]:'左眼'}}</text>
													<image class="down" :src="pre_url+'/static/img/arrowright.png'">
												</view>
										</picker>
										<!-- <input type="number" name="ats_zleft" placeholder="0-180" :value="detail.ats_zleft" placeholder-style="font-size:24rpx;color:#cccccc" /> -->
									</view>
								</view>
								<!-- <view class="tips-dushu">轴位范围值(0 ~ +180)，步长1</view> -->
							</view>
						</view>
					</block>
					<view class="form-item" v-if="type==3">
						<view class="form-label">下加光(ADD)</view>
						<view class="form-value">
							<view class="dushu">
								<view class="unit-item">
								<picker style="width: 100%;" name="add_right" :value="add_right_index" mode="selector" :range="addlist"  @change="selectChange" data-field="add_right">
										<view :class="add_right_index>-1?'':'hui'" class="flex-e">
											<text>{{add_right_index>-1?addlist[add_right_index]:'右眼'}}</text>
											<image class="down" :src="pre_url+'/static/img/arrowright.png'">
										</view>
								</picker>
								</view>
								<view class="unit-item mgL10">
								<picker style="width: 100%;" name="add_left" :value="add_left_index" mode="selector" :range="addlist"  @change="selectChange" data-field="add_left">
										<view :class="add_left_index>-1?'':'hui'" class="flex-e">
											<text>{{add_left_index>-1?addlist[add_left_index]:'左眼'}}</text>
											<image class="down" :src="pre_url+'/static/img/arrowright.png'">
										</view>
								</picker>
								</view>
							</view>
							<!-- <view class="tips-dushu">柱镜范围值(0.50~4.00)，步长0.25</view> -->
						</view>
					</view>
					<view class="form-item">
						<view class="form-label">
							<view>瞳距(PD)</view>
							<view style="font-size: 24rpx;">双瞳距<checkbox style="transform: scale(0.6);" @click="pdchange" :checked="doublepd"></checkbox></view>
						</view>
						<view class="form-value">
							<view class="flex-s" v-if="doublepd">
								<view class="unit-item">
									<picker style="width: 100%;" name="ipd_right" :value="pd_right_index" mode="selector" :range="dpdlist"  @change="selectChange" data-field="pd_right">
											<view :class="pd_right_index>-1?'':'hui'" class="flex-e">
												<text>{{pd_right_index>-1?dpdlist[pd_right_index]:'右眼'}}</text>
												<image class="down" :src="pre_url+'/static/img/arrowright.png'">
											</view>
									</picker>
								</view>
								<view class="unit-item">
									<picker style="width: 100%;" name="ipd_left" :value="pd_left_index" mode="selector" :range="dpdlist"  @change="selectChange" data-field="pd_left">
											<view :class="pd_left_index>-1?'':'hui'" class="flex-e">
												<text>{{pd_left_index>-1?dpdlist[pd_left_index]:'左眼'}}</text>
												<image class="down" :src="pre_url+'/static/img/arrowright.png'">
											</view>
									</picker>
								</view>
							</view>
							<view class="unit-item" v-if="!doublepd">
								<picker style="width: 100%;" name="ipd" :value="pd_index" mode="selector" :range="pdlist"  @change="selectChange" data-field="pd">
										<view :class="pd_index>-1?'':'hui'" class="flex-e">
											<text>{{pd_index>-1?pdlist[pd_index]:'瞳距'}}</text>
											<image class="down" :src="pre_url+'/static/img/arrowright.png'">
										</view>
								</picker>
							</view>
							<!-- <view>双瞳距<checkbox style="transform: scale(0.6);" @click="pdchange" :checked="doublepd"></checkbox></view> -->
						</view>
					</view>
					<view class="form-item jz-item">
						<view class="form-label flex-y-center"><text>矫正视力</text><image @tap="showJzTips" class="tips-jz" :src="pre_url+'/static/img/tanhao.png'"></view>
						<view class="form-value dushu">
							<view class="unit-item">
								<picker style="width: 100%;" name="correction_right" :value="correction_right_index" mode="selector" :range="visionlist"  @change="selectChange" data-field="correction_right">
										<view :class="correction_right_index>-1?'':'hui'" class="flex-e">
											<text>{{correction_right_index>-1?visionlist[correction_right_index]:'右眼'}}</text>
											<image class="down" :src="pre_url+'/static/img/arrowright.png'">
										</view>
								</picker>
							</view>
							<view class="unit-item">
							<picker style="width: 100%;" name="correction_left" :value="correction_left_index" mode="selector" :range="visionlist"  @change="selectChange" data-field="correction_left">
									<view :class="correction_left_index>-1?'':'hui'" class="flex-e">
										<text>{{correction_left_index>-1?visionlist[correction_left_index]:'左眼'}}</text>
										<image class="down" :src="pre_url+'/static/img/arrowright.png'">
									</view>
							</picker>
							</view>
						</view>
					</view>
				</view>
			</view>
			<!-- 视力信息 -->
			<!-- 备注信息 -->
			<view class="box">
				<view class="form-item textarea-item">
					<view class="form-label" style="width: 120rpx;">备注</view>
					<view class="form-value">
						<textarea class="textarea"  name="remark" :value="detail.remark" placeholder="请输入备注信息" placeholder-style="font-size:28rpx;color:#cccccc" ></textarea>
					</view>
				</view>
			</view>
			<!-- 备注信息 -->
			<!-- 客户信息 -->
			<view class="box">
				<view class="tips-info flex-bt show-box" @click="toggleBoxContent">
          <view>
            个人信息<text class="tips">（建议填写）</text>
          </view>
          <view>
            <text :class="iconClass" style="color:#999;font-weight:normal"></text>
          </view>
        </view>
				<view class="box-content" :style="{ height: boxContentHeight }">
          <view class="form-item">
            <view class="form-label">姓名</view>
            <view class="form-value unit-item">
              <input type="text" name="nickname" placeholder="请输入姓名" :value="detail.nickname" placeholder-style="font-size:28rpx;color:#cccccc" />
            </view>
          </view>
          <view class="form-item">
            <view class="form-label">年龄</view>
            <view class="form-value unit-item">
              <input type="number" name="age" placeholder="请输入年龄" :value="detail.age" placeholder-style="font-size:28rpx;color:#cccccc" />
              <text class="unit">岁</text>
            </view>
          </view>
          <view class="form-item">
            <view class="form-label">性别</view>
            <view class="form-value radio">
              <radio-group name="sex">
                <label>
                    <radio value="1" style="transform: scale(0.8);" :color="t('color1')" :checked="detail.sex==1?true:false" />男
                </label>
                <label>
                    <radio value="2" style="transform: scale(0.8);" :color="t('color1')" :checked="detail.sex==2?true:false" />女
                </label>
              </radio-group>
            </view>
          </view>
          <view class="form-item">
            <view class="form-label">手机号码</view>
            <view class="form-value unit-item">
              <input type="text" name="tel" placeholder="请输入手机号码" :value="detail.tel" placeholder-style="font-size:28rpx;color:#cccccc" />
            </view>
          </view>
          <view class="form-item">
            <view class="form-label">验光时间</view>
            <view class="form-value unit-item">
              <picker style="width: 100%;" name="check_time" :value="check_time" mode="date" @change="bindDateChange">
                  <view :class="check_time?'':'hui'" class="flex-sb">
                    <text>{{check_time?check_time:'请选择验光时间'}}</text>
                    <image class="down" :src="pre_url+'/static/img/arrowright.png'">
                  </view>
              </picker>
            </view>
          </view>
          <view class="form-item">
            <view class="form-label">验光单位</view>
            <view class="form-value unit-item">
              <input type="text" name="optometry_company" placeholder="请输入验光单位" :value="detail.optometry_company" placeholder-style="font-size:28rpx;color:#cccccc" />
            </view>
          </view>
          <view class="form-item">
            <view class="form-label">验光师</view>
            <view class="form-value unit-item">
              <input type="text" name="optometry_name" placeholder="请输入验光师" :value="detail.optometry_name" placeholder-style="font-size:28rpx;color:#cccccc" />
            </view>
          </view>
        </view>
      </view>
			<!-- 客户信息 -->
			<view class="bottom">
				<view class="flex-x-center">
					<radio-group @click="ruleChange">
						<label>
								<radio style="transform: scale(0.8);" :color="t('color1')" :checked="isrule" />
						</label>
					</radio-group>
					<view>我已阅读并同意<text class="red" @tap="goto" data-url="set?field=xieyi">《用户信息协议授权协议》</text>中的全部内容</view>
				</view>
				<view class="form-option">
					<button class="btn" :style="{background:'linear-gradient(-90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)',color:'#ffffff'}" form-type="submit" data-type="1">保存</button>
				</view>
			</view>
		</form>
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
			opt:{},
			loading:false,
      isload: false,
			menuindex:-1,
			
			pre_url:app.globalData.pre_url,
			type:1,
			detail:{},
			isrule:false,
			isAts:0,
			id:0,
			set:{},
			visionlist:[],
			correction_right_index:-1,
			correction_left_index:-1,
			
			degress_right_index:-1,
			degress_left_index:-1,
			ats_right_index:-1,
			ats_left_index:-1,
			ats_zright_index:-1,
			ats_zleft_index:-1,
			add_right_index:-1,
			add_left_index:-1,
			
			doublepd:false,
			pd_index:-1,
			pd_left_index:-1,
			pd_right_index:-1,
			
			degresslist:[],//-18  10 0.25
			atslist:[], //柱镜 -6 0 0.25
			atszlist:[], //轴位 0 180 1
			dpdlist:[], //15 40 0.5
			pdlist:[],//45 80 1
			addlist:[] ,//0 4 0.25
			check_time:'',
      boxContentHeight: '0', // 客户信息 初始高度为0 默认隐藏
      iconClass: 'iconfont icondaoxu', // 初始图标类名
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.id = this.opt.id || 0;
		this.getdata();
  },
	onPullDownRefresh: function () {
	},
  onReachBottom: function () {
  },
  methods: {
    getdata: function (loadmore) {
      var that = this;
			that.loading = true;
      app.post('ApiGlass/add', {id:that.id}, function (res) {
				that.loading = false;
				that.set = res.set;
				that.initlist('degress',-18,10,0.25,2)
				that.initlist('ats',-6,0,0.25,2)
				that.initlist('atsz',0,180,1,0)
				that.initlist('dpd',15,40,0.5,2)
				that.initlist('pd',45,80,0.5,2)
				that.initlist('add',0,4.00,0.25,2)
				that.detail = res.detail
				if(that.set && that.set.vision){
					that.visionlist = that.set.vision
				}
				if(that.detail.id){
					that.isrule = true
					that.check_time = that.detail.check_time
					that.type = that.detail.type
					that.isAts = that.detail.is_ats
					that.doublepd = that.detail.double_ipd?true:false;
					if(that.detail.correction_left || that.detail.correction_right){
						for(let i in that.visionlist){
							 let vision = that.visionlist[i];
							 if(vision==that.detail.correction_right){
								 that.correction_right_index = i;
							 }
							 if(vision==that.detail.correction_left){
							 		that.correction_left_index = i;
							 }
						}
					}
					if(that.detail.degress_right || that.detail.degress_left){
						for(let i in that.degresslist){
							 let vision = that.degresslist[i];
							 if(vision==that.detail.degress_right){
								 that.degress_right_index = i;
							 }
							 if(vision==that.detail.degress_left){
							 		that.degress_left_index = i;
							 }
						}
					}
					if(that.doublepd){
						for(let i in that.dpdlist){
							 let vision = that.dpdlist[i];
							 if(vision==that.detail.ipd_left){
								 that.pd_left_index = i;
							 }
							 if(vision==that.detail.ipd_right){
							 		that.pd_right_index = i;
							 }
						}
					}else{
						for(let i in that.pdlist){
							 let vision = that.pdlist[i];
							 var ipd = that.detail.ipd
							 if(vision==ipd){
								 that.pd_index = i;
							 }
						}
					}
					if(that.detail.is_ats){
						if(that.detail.ats_left || that.detail.ats_right){
							for(let i in that.atslist){
								 let vision = that.atslist[i];
								 if(vision==that.detail.ats_right){
									 that.ats_right_index = i;
								 }
								 if(vision==that.detail.ats_left){
								 		that.ats_left_index = i;
								 }
							}
						}
						if(that.detail.ats_zleft || that.detail.ats_zright){
							for(let i in that.atszlist){
								 let vision = that.atszlist[i];
								 if(vision==that.detail.ats_zright){
									 that.ats_zright_index = i;
								 }
								 if(vision==that.detail.ats_zleft){
								 		that.ats_zleft_index = i;
								 }
							}
						}
					}
					if(that.detail.type==3){
						if(that.detail.add_left || that.detail.add_right){
							for(let i in that.addlist){
								 let vision = that.addlist[i];
								 if(vision==that.detail.add_right){
									 that.add_right_index = i;
								 }
								 if(vision==that.detail.add_left){
								 		that.add_left_index = i;
								 }
							}
						}
					}
					
				}
				that.loaded()
			});
    },
		bindDateChange:function(e){
			this.check_time = e.detail.value
		},
		initlist:function(field,min,max,step,point=2){
			var that = this;
			var min = parseFloat(min);
			var max = parseFloat(max);
			var step = parseFloat(step);
			var listarr = [];
			var index = 0;
			for(var i=min; i<=max;i=i+step){
				var v = '';
				if(i<0){
					v = i.toFixed(point);
				}else if(i==0){
					console.log('i++++:'+i)
					v = i.toFixed(point);
					that[field+'_right_index'] = index
					that[field+'_left_index'] = index
					that[field+'_index'] = index
					console.log(that.degress_left_index)
				}else{
					if(field=='degress'){
						var v = '+' +(i.toFixed(point))
					}else{
						if(min<0){
							var v = '+' +(i.toFixed(point))
						}else{
							v = i.toFixed(point);
						}
					}
				}
        
				//双眼瞳距 默认值30
				if (field === 'dpd' && v === '30.00') {
					that.pd_left_index = index;
					that.pd_right_index = index;
				}
				//单眼瞳距 默认值60
				if (field === 'pd' && v === '60.00') {
					that.pd_index = index;
				}
				listarr.push(v)
				index++
			}
			that[field+'list'] = listarr
		},
		pdchange:function(e){
			this.doublepd = this.doublepd?false:true
		},
		selectChange:function(e){
			var that = this 
			var index = e.detail.value
			var field = e.currentTarget.dataset.field
			that[field+'_index'] = index
		},
		atsChange:function(e){
			this.isAts = e.detail.value
			console.log(e.detail.value)
		},
		ruleChange:function(e){
			this.isrule = (this.isrule?false:true)
		},
		typechange:function(e){
			var that = this
			that.type = e.detail.value
			if(that.type==1){
				that.initlist('degress',-18,0,0.25,2)
			}else if(that.type==2){
				that.initlist('degress',0,10,0.25,2)
			}else if(that.type==3){
				that.initlist('degress',-18,10,0.25,2)
			}
		},
		stepCheck:function(value,min,max,step){
			var value = parseFloat(value);
			if(value<min){
				return false;
			}
			if(value>max){
				return false;
			}
			var remain = value % step;
			if(remain>0){
				return false;
			}
			return true;
		},
		formSubmit: function (e) {
			var that = this;
		  var formdata = e.detail.value;
			//
			
			if(that.correction_left_index>-1){
				formdata['correction_left'] = that.visionlist[that.correction_left_index]
			}
			if(that.correction_right_index>-1){
				formdata['correction_right'] = that.visionlist[that.correction_right_index]
			}
			if(that.degress_left_index>-1){
				formdata['degress_left'] = that.degresslist[that.degress_left_index]
			}
			if(that.degress_right_index>-1){
				formdata['degress_right'] = that.degresslist[that.degress_right_index]
			}
			if(that.ats_left_index>-1){
				formdata['ats_left'] = that.atslist[that.ats_left_index]
			}
			if(that.ats_right_index>-1){
				formdata['ats_right'] = that.atslist[that.ats_right_index]
			}
			if(that.ats_zleft_index>-1){
				formdata['ats_zleft'] = that.atszlist[that.ats_zleft_index]
			}
			if(that.ats_zright_index>-1){
				formdata['ats_zright'] = that.atszlist[that.ats_zright_index]
			}
			if(formdata.type==3){
				formdata['add_left'] = that.addlist[that.add_left_index]
				formdata['add_right'] = that.addlist[that.add_right_index]
			}
			if(that.doublepd){
				formdata['ipd_left'] = that.dpdlist[that.pd_left_index]
				formdata['ipd_right'] = that.dpdlist[that.pd_right_index]
			}else{
				formdata['ipd'] = that.pdlist[that.pd_index]
			}
			
			if (formdata.name == ''){
			  app.error('请输入档案名称');
			  return;
			}
			if (!formdata.type){
			  app.error('请选择近视或者远视');
			  return;
			}
			if(formdata.degress_right_index<0){
				app.error('请选择右眼球镜');
				return;
			}
			if(formdata.degress_left_index<-1){
				app.error('请选择左眼球镜');
				return;
			}
			if(formdata.tel!='' && !app.isPhone(formdata.tel)){
				app.error('手机号码格式错误');
				return;
			}
			formdata['degress_right'] = that.degresslist[that.degress_right_index]
			formdata['degress_left'] = that.degresslist[that.degress_left_index]
			if(that.isAts){
				if(that.ats_right_index>-1){
					formdata['ats_right'] = that.atslist[that.ats_right_index]
				}
				if(that.ats_left_index>-1){
					formdata['ats_left'] = that.atslist[that.ats_left_index]
				}
				if(that.ats_zright_index>-1){
					formdata['ats_zright'] = that.atszlist[that.ats_zright_index]
				}
				if(that.ats_zleft_index>-1){
					formdata['ats_zleft'] = that.atszlist[that.ats_zleft_index]
				}
			}
			if(that.doublepd){
				if(that.pd_right_index>-1){
					formdata['ipd_right'] = that.dpdlist[that.pd_right_index]
				}
				if(that.pd_left_index>-1){
					formdata['ipd_left'] = that.dpdlist[that.pd_left_index]
				}
			}else{
				if(that.pd_index>-1){
					formdata['ipd'] = that.pdlist[that.pd_index]
				}
			}
			if(formdata.type==3){
				if(that.add_right_index>-1){
					formdata['add_right'] = that.addlist[that.add_right_index]
				}
				if(that.add_left_index>-1){
					formdata['add_left'] = that.addlist[that.add_left_index]
				}
			}
			
		  if (!that.isrule) {
		    app.error('请先阅读并同意用户信息授权协议');
		    return false;
		  }
			formdata['double_ipd'] = that.doublepd?1:0
			formdata['check_time'] = that.check_time
			formdata['id'] = that.id
			app.showLoading('提交中');
		  app.post("ApiGlass/save", formdata, function (data) {
				app.showLoading(false);
		    if (data.status == 1) {
					//缓存最新的档案信息
					app.setCache('_glass_record_id',data.id)
		      app.success(data.msg);
					setTimeout(function () {
					  app.goback(true)
					}, 1000);
		    } else {
		      app.error(data.msg);
		    }
		  });
		},
		showJzTips:function(){
			app.alert('矫正视力是指用眼镜来矫正屈光不正之后得出的视力，是戴眼镜后的视力')
		},
    toggleBoxContent() {
      const animation = uni.createAnimation({
        duration: 300, // 动画持续时间，单位ms
        timingFunction: 'ease', // 动画效果
      });

      if (this.boxContentHeight === '0') {
        // 展开
        animation.height('auto').step();
        this.boxContentHeight = 'auto';
        this.iconClass = 'iconfont iconshangla';
      } else {
        // 收起
        animation.height(0).step();
        this.boxContentHeight = '0';
        this.iconClass = 'iconfont icondaoxu';
      }

      this.animation = animation.export();
    },
  }
};
</script>
<style>
	.flex{display: flex;align-items: center;}
	.flex-s{display: flex;justify-content: flex-start;align-items: center;}
	.flex-e{display: flex;justify-content: flex-end;align-items: center;}
	.flex-sb{display: flex;justify-content: space-between;align-items: center;}
	.flex-c{display: flex;justify-content: center;align-items: center;}
	.hui{color: #CCCCCC;}
	.top{height: 400rpx;}
	.content{position: relative;top: -400rpx;padding: 20rpx;}
	.box{width: 100%;background: #FFFFFF; color: #222222;padding: 20rpx;border-radius: 20rpx;margin-bottom: 20rpx;}
	.tips-top{width: 100%;text-align: center;font-size: 36rpx;color: #FFFFFF;padding: 30rpx 0;}
	.tips-info{font-size: 16px; font-weight: bold;line-height: 45px; }
	.tips-info .tips{font-size: 30rpx;color: #cdcdcd;font-weight: normal;}
	.tips-desc{color: #9d9d9d;font-size: 28rpx;}
	.tips-ref{margin-right: 20rpx;}
	.tips-desc image{width: 30rpx;height: 30rpx;padding-top: 8rpx;}
	.tips-dushu{font-size: 24rpx;color:#9d9d9d;padding-top: 6rpx;}
	.red{color: #ff0000;}
	.bottom{margin: 40rpx 0;}
	.dangan .form-label{font-size: 32rpx;font-weight: bold;}

.form-item{display: flex;justify-content: flex-start;align-items: center;/* border-bottom: 1rpx solid #f6f6f6; */ padding: 30rpx 20rpx;}
/* .box .form-item:last-child{border-bottom: none;} */
.form-value textarea{padding: 10rpx 0;}
.form-label{flex-shrink: 0;width: 200rpx;flex-wrap: wrap;padding-right: 30rpx;font-size: 28rpx;}
.form-value{flex: 1;}
.form-tips{color: #CCCCCC;font-size: 28rpx;padding: 20rpx 0;}
.form-value .down{width: 28rpx;height: 28rpx;vertical-align:middle;flex-shrink: 0;}
.form-value.radio label{margin-right: 10rpx;}
.form-value.upload{display: flex;align-items: center;flex-wrap: wrap;}
.textarea-item .textarea{border: 1rpx solid #e5e5e5;max-width: 480rpx;border-radius: 4rpx;padding: 16rpx;height: 160rpx;font-size: 28rpx;}

.name-item input{text-align: right}
/deep/.input-value-border{border: none;}
/deep/.input-value{line-height: normal;padding: 0;}
/* 行排列 */
.form-item-row{border-bottom: 1rpx solid #f6f6f6; padding: 20rpx 0;}
.form-item-row .form-label,.form-item-row .form-value{width: 100%;}
.form-item-row .form-value textarea{width: 100%;height: 200rpx;}
.form-option{display: flex;justify-content: center;padding: 30rpx;}
.form-option .btn{text-align: center;width: 100%;border-radius: 80rpx;line-height: 84rpx;}

.dushu{display: flex;justify-content: space-between;}
.unit-item{display: flex;justify-content: space-between;margin:0 6rpx;border-bottom: 1rpx solid #ededed;flex:1;font-size: 28rpx;}
.unit-item .unit{color: #8d8d8d;line-height: 44rpx;padding-left: 10rpx;}
.bb{border-bottom: 1rpx solid #e5e5e5;}
.mgL10{margin-left: 30rpx;}
.tips-jz{width: 40rpx;height: 40rpx;}
.jz-item .unit-item{width: 45%;}

	.table-header{font-weight: bold;}
	.table-header .form-value{display: flex;justify-content: flex-end;align-items: center;text-align: right;}
	.table-header .table-item{width: 50%;flex-shrink: 0;}
	.table-body{color: #cdcdcd; font-size: 24rpx;}
	.table-body .form-item{padding: 20rpx 20rpx;color: #8d8d8d;}
	.table-body .form-value{text-align: right;font-size: 24rpx;}
	.table-body .form-item .uni-input-input{font-size: 24rpx;}
  
  /* 初始状态为隐藏，高度为0 */
  .box-content {
    overflow: hidden; /* 防止内容溢出 */
    height: 0; /* 初始高度为0 */
    transition: height 0.3s ease; /* 添加高度变化的过渡效果 */
  }
  
</style>