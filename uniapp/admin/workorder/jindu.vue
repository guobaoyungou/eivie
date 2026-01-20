<template>
	<view>
		<view class="banner" :style="'background: linear-gradient(180deg, '+t('color1')+' 0%, rgba('+t('color1rgb')+',0) 100%);'"></view>
		<view class="page">
			<view class="header fa">
				<view class="f1">
					<view class="header_title" v-if="detail.status==0">
						待处理
					</view>
					<view class="header_title" v-if="detail.status==1">
						{{detail.clname}}
					</view>
					<view class="clusername" style="color: #fff;margin-top: 10rpx;" v-if="detail.clusername"> <text>处理人：{{detail.clusername}}</text></view>
					<view class="header_text fa" @tap="goto" :data-url="'formdetail?id='+id">
						查看工单详情<image class="header_icon" :src="pre_url+'/static/imgsrc/work_detail.png'" mode="widthFix"></image>
					</view>
				</view>
				<image class="header_tag" :src="pre_url+'/static/imgsrc/work_tag.png'" mode="widthFix"></image>
			</view>
			
			
			<view class="desc">	<parse :content="form.desc"></parse></view>
			
			


			<view class="body">
				<view class="body_title">
					<text>工单进度</text>
					<image class="body_icon" :src="pre_url+'/static/imgsrc/work_title.png'" mode="widthFix"></image>
				</view>
				
				<view class="content">
					<block v-if="data.length>0">
					
						<view class="module"  v-for="(item,index) in data" :key="index">
							<image class="module_tag" :src="pre_url+'/static/imgsrc/'+(index>0?'work_dot':'work_dotA')+'.jpg'" mode="widthFix"></image>
							
							<view :class="'module_title '+(index>0?'module_null':'')">
								{{item.desc}}
							</view>
							
							<view class="module_text">
								{{item.remark}}
							</view>
							<view class="module_img" v-if="item.content_pic">
								<view v-for="(pic,picindex) in item.content_pic">
									<image :src="pic" mode="widthFix" style="width: 100rpx;" @tap="previewImage" :data-url="pic">
								</view>
							</view>
							
							<view v-for="(formitem, formindex) in form.contentuser"  class="hfitem" :style="(dataindex!=index && formindex>1)?'display:none':''">
								<text class="t1" :class="formitem.key=='separate'?'title':''">{{formitem.val1}}</text>
								<text class="t2" v-if="formitem.key!='upload' && formitem.key!='upload_file' && formitem.key!='upload_video'" >{{item['form'+formindex]}}</text>
								<view class="t2" style="display: flex; justify-content: flex-end;"  v-if="formitem.key=='upload'">
									<view v-for="(sub, indx) in item['form'+formindex]" :key="indx">
										<image :src="sub" style="width:50px; margin-left: 10rpx;" mode="widthFix" @tap="previewImage" :data-url="sub"></image>
									</view>
								</view>
								
								<!-- #ifdef !H5 && !MP-WEIXIN -->
								<view class="t2" v-if="formitem.key=='upload_file' && item['form'+formindex]" style="overflow: hidden;white-space: pre-wrap;word-wrap: break-word;color: #4786BC;">
										{{hf['form'+formindex]}}
								</view>
								<!-- #endif -->
								<!-- #ifdef H5 || MP-WEIXIN -->
								<view class="t2" v-if="formitem.key=='upload_file' && item['form'+formindex]"  @tap="download" :data-file="item['form'+formindex]" style="overflow: hidden;white-space: pre-wrap;word-wrap: break-word;color: #4786BC;">
										点击下载查看
								</view>
								<!-- #endif -->
								
								<view class="t2"  v-if="formitem.key=='upload_video' && item['form'+formindex]">
										<video  :src="item['form'+formindex]" style="width: 100%;"/></video>
								</view>
							</view>
							
							<view v-if="dataindex==formindex" class="zktext" @tap="zhankai1" :data-dataindex="index" data-type="zhedie">收起
								<image :src="pre_url+'/static/img/workorder/shoqi.png'" />
							</view>
							<view v-else class="zktext" @tap="zhankai1" :data-dataindex="index"  data-type="zhankai">展开
								<image :src="pre_url+'/static/img/workorder/more.png' "/>
							</view>
							
							
							<view class="module_time">
								{{item.time}}
							</view>
							<!--<view class="module_opt fx">
								<view class="module_btn module_r">同意</view>
								<view class="module_btn">拒绝</view>
								<view class="module_btn">协商</view>
							</view>-->
							<view class="module_opt fx">
								<view v-for="(hf,hfindex) in item.hflist" :key="hfindex">
									<view class="t3" v-if="hf.hfremark" ><text class="t3_1">我的回复：</text>{{hf.hfremark}} </view>
									<view class="t4" v-if="hf.hftime" ><text class="t4_1">回复时间：</text>{{hf.hftime}} </view>
									
									<view v-for="(item, index) in form.contentuser" :key="index" class="hfitem" :style="(curindex!=hfindex && index>1)?'display:none':''">
										<text class="t1" :class="item.key=='separate'?'title':''">{{item.val1}}</text>
										<text class="t2" v-if="item.key!='upload' && item.key!='upload_file' && item.key!='upload_video'" >{{hf['form'+index]}}</text>
										<view class="t2" style="display: flex; justify-content: flex-end;"  v-if="item.key=='upload'">
											<view v-for="(sub, indx) in hf['form'+index]" :key="indx">
												<image :src="sub" style="width:50px; margin-left: 10rpx;" mode="widthFix" @tap="previewImage" :data-url="sub"></image>
											</view>
										</view>
										
										<!-- #ifdef !H5 && !MP-WEIXIN -->
										<view class="t2" v-if="item.key=='upload_file' && hf['form'+index]" style="overflow: hidden;white-space: pre-wrap;word-wrap: break-word;color: #4786BC;">
												{{hf['form'+index]}}
										</view>
										<!-- #endif -->
										<!-- #ifdef H5 || MP-WEIXIN -->
										<view class="t2" v-if="item.key=='upload_file' && hf['form'+index]"  @tap="download" :data-file="hf['form'+index]" style="overflow: hidden;white-space: pre-wrap;word-wrap: break-word;color: #4786BC;">
												点击下载查看
										</view>
										<!-- #endif -->
										
										<view class="t2"  v-if="item.key=='upload_video' && hf['form'+index]">
												<video  :src="hf['form'+index]" style="width: 100%;"/></video>
										</view>
										
									</view>
									<view v-if="curindex==hfindex" class="zktext" @tap="zhankai" :data-curindex="hfindex" data-type="zhedie">收起
										<image :src="pre_url+'/static/img/workorder/shoqi.png'" />
									</view>
									<view v-else class="zktext" @tap="zhankai" :data-curindex="hfindex"  data-type="zhankai">展开
										<image :src="pre_url+'/static/img/workorder/more.png' "/>
									</view>
									
									
									
								</view>
							</view>
							
							
						</view>
		
						
						</block>
						<block v-else>
							<view class="module">
								<image class="module_tag" :src="pre_url+'/static/imgsrc/work_dot.jpg'" mode="widthFix"></image>
								<view class="module_title module_null">
									等待处理
								</view>
							</view>
						</block>
						
					</view>
			</view>
			
			
			
			<view class="item">
					<view class="modal"  v-if="detail.status!=2" >
						<view class="modal_jindu">
							<form   @submit="formsubmit" :data-formcontent="form.contentuser" >
									<view class="title">选择流程</view>
									<view class="uni-list">
											<radio-group name="liucheng">
													<label class="uni-list-cell uni-list-cell-pd" v-for="(item, index) in lclist">
															<view>
																<block v-if="data.length>0">
																	<radio :value="''+item.id" style="transform:scale(0.7)" :checked="data[0].lcid==item.id?true:false"/>
																</block>
																<block v-else>
																	<radio :value="''+item.id" style="transform:scale(0.7)" />
																</block>
															</view>
															<view>{{item.name}}</view>
													</label>
											</radio-group>
											
												<block v-for="(item,index) in form.contentuser" >
													<view class="form-item">
														<view  class="label">{{item.val1}}<text v-if="item.val3==1" style="color:red"> *</text></view>
														<block v-if="item.key=='input'">
															<input type="text" :name="'form'+index" class="input"  :placeholder="item.val2" :data-formidx="'form'+index" placeholder-style="font-size:28rpx"/>
														</block>
														<block v-if="item.key=='upload'">
															<input type="text" style="display:none" :name="'form'+index" :value="editorFormdata['content_pics'+index]"/>
															<view class="flex" style="flex-wrap:wrap;padding-top:20rpx">
																<view class="form-imgbox" v-if="editorFormdata['content_pic'+index]" v-for="(item1, index1) in editorFormdata['content_pic'+index]" :key="index">
																	<view class="form-imgbox-close" @tap="removeimg" :data-pindex="index1"  :data-field="'content_pic'+index"  :data-idx="index"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
																	<view class="form-imgbox-img"><image  :src="item1" @click="previewImage" :data-url="item1" mode="widthFix" /></view>
																</view>
																<view class="form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 50rpx',backgroundSize:'80rpx 80rpx',backgroundColor:'#F3F3F3',backgroundPosition: 'center'}" @click="editorChooseImage" :data-field="'content_pic'+index"  :data-idx="index" :data-formidx="'form'+index"></view>
															</view>
														</block>
														
														<!-- #ifdef H5 || MP-WEIXIN -->
														<block v-if="item.key=='upload_file'">
															<input type="text" style="display:none" :name="'form'+index" :value="editorFormdata[index]"/>
															<view class="flex-y-center" style="flex-wrap:wrap;padding-top:20rpx;">
																<view class="dp-form-imgbox" v-if="editorFormdata[index]">
																	<view class="dp-form-imgbox-close" @tap="removeimg2" :data-idx="index" :data-formidx="'form'+index">
																		<image :src="pre_url+'/static/img/ico-del.png'" class="image"></image>
																	</view>
																	<view  style="overflow: hidden;white-space: pre-wrap;word-wrap: break-word;color: #4786BC;" @tap="download" :data-file="editorFormdata[index]" >
																		文件已上传成功
																	</view>
																</view>
																<block v-else>
																	<view  class="dp-form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 30rpx',backgroundSize:'50rpx 50rpx',backgroundColor:'#F3F3F3'}" @click="chooseFile" :data-idx="index" :data-id="index" :data-formidx="'form'+index" style="margin-right:20rpx;"></view>
																	<view v-if="item.val2" style="color:#999">{{item.val2}}</view>
																</block>
															</view>
														</block>
														<!-- #endif -->
														
														<block v-if="item.key=='upload_video'">
															<input type="text" style="display:none" :name="'form'+index" :value="editorFormdata[index]"/>
															<view class="flex-y-center" style="flex-wrap:wrap;padding-top:20rpx">
																<view class="dp-form-imgbox" v-if="editorFormdata[index]">
																	<view class="dp-form-imgbox-close" @tap="removeimg2" :data-idx="index" :data-formidx="'form'+index">
																			<image :src="pre_url+'/static/img/ico-del.png'" class="image"></image>
																	</view>
																	<view  style="overflow: hidden;white-space: pre-wrap;word-wrap: break-word;color: #4786BC;width: 230rpx;">
																			<video  :src="editorFormdata[index]" style="width: 100%;"/></video>
																	</view>
																</view>
																<block v-else>
																	<view class="dp-form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 30rpx',backgroundSize:'50rpx 50rpx',backgroundColor:'#F3F3F3'}" @tap="upVideo" :data-idx="index"  :data-id="index" :data-formidx="'form'+index" style="margin-right:20rpx;"></view>
																	<view v-if="item.val2" style="color:#999">{{item.val2}}</view>
																</block>
															</view>
														</block>	
													</view>		
												</block>
														
											<!--<view class="form-item4 flex-col">
												<view class="label">上传图片</view>
												<view id="content_picpreview" class="flex" style="flex-wrap:wrap;padding-top:20rpx">
													<view v-for="(item, index) in content_pic" :key="index" class="layui-imgbox">
														<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="content_pic" ><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
														<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
													</view>
													<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="content_pic" v-if="content_pic.length<5"></view>
												</view>
											</view>
											<view class="beizhu flex">
													<label>备注:</label><textarea placeholder="输入内容"  name="content" maxlength="-1" style="height: 200rpx;"></textarea>
											</view>-->		
									</view>
									<button class="btn" form-type="submit" >提交</button>
							</form>
						</view>
					</view>
				
			</view>
			
		</view>
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
				nodata:false,
				data:[],
				detail:[],
				content_pic: [],
				lclist:[],
				jdlist:[],
				form:[],
				showmore:false,
				curindex:0,
				cid:0,
				editorFormdata:{},
				formdata:{},
				formvaldata:{},
				curindex:-1,
				dataindex:-1,
				formindex:0,
				id:0
			}
		},
		onLoad(opt) {
			var that=this
			this.opt = app.getopts(opt);
			this.id =  that.opt.id;
			this.getdata();
			this.getliucheng();
		},
		methods: {
			getdata: function () {
				var that = this;
				that.id = that.opt.id;
				that.loading = true;
				app.post('ApiWorkorder/selectjindu', { id:that.id }, function (res) {
					that.loading = false;
					var data = res.data;
					that.data = data;
					that.detail = res.detail
					that.form = res.form
					that.loaded();
				});
			},

			getliucheng:function(e){
				var that=this
				app.post('ApiAdminWorkorder/getliucheng', {cid: that.opt.cid}, function (res) {
						var lclist = res.datalist;
						that.lclist = lclist;
						
				});
			},
			/*uploadimg:function(e){
				var that = this;
				var field= e.currentTarget.dataset.field
				var pics = that[field]
				if(!pics) pics = [];
			
				app.chooseImage(function(urls){
					for(var i=0;i<urls.length;i++){
						pics.push(urls[i]);
					}
					console.log(pics);
				},5)
			},
			removeimg:function(e){
				var that = this;
				var index= e.currentTarget.dataset.index
				var field= e.currentTarget.dataset.field
				var pics = that[field]
				pics.splice(index,1)
			},*/
			formsubmit: function (e) {
			  var that = this;		
			  var formdata = e.detail.value;
			  var content = formdata.content;
			  var liucheng = formdata.liucheng;
			  if (liucheng == '') {
			    app.error('请选择流程');
			    return false;
			  }
				var content_pic = that.content_pic;
				var formcontent = e.currentTarget.dataset.formcontent;
				var formid = e.currentTarget.dataset.formid;
				var formdataval = e.detail.value;
				var newformdata = {};
				for (var i = 0; i < formcontent.length;i++){
					//console.log(subdata['form' + i]);
					if (formcontent[i].val3 == 1 && (formdataval['form' + i ] === '' || formdataval['form' + i] === null || formdataval['form' + i] === undefined || formdataval['form' + i].length==0)){
							app.alert(formcontent[i].val1+' 必填');return;
					}
					if (formcontent[i].key == 'input' && formcontent[i].val4 && formdataval['form' + i]!==''){
						if(formcontent[i].val4 == '2'){ //手机号
							if (!app.isPhone(formdataval['form' + i])) {
								app.alert(formcontent[i].val1+' 格式错误');return;
							}
						}
						if(formcontent[i].val4 == '3'){ //身份证号
							if (!/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/.test(formdataval['form' + i])) {
								app.alert(formcontent[i].val1+' 格式错误');return;
							}
						}
						if(formcontent[i].val4 == '4'){ //邮箱
							if (!/^(.+)@(.+)$/.test(formdataval['form' + i])) {
								app.alert(formcontent[i].val1+' 格式错误');return;
							}
						}
					}
					newformdata['form'+i] = formdataval['form' + i];
				}
					
				console.log(newformdata);
				
				app.showLoading();
			  app.post('ApiAdminWorkorder/addjindu', {logid:that.opt.id,lcid:liucheng,formdata:newformdata}, function (res) {
					var res  = res
			  	app.showLoading(false);
			    if (res.status == 1) {
			      app.alert('处理成功');
						setTimeout(function () {
						  //that.getdata();
								app.goto('formdetail?id='+that.opt.id);
						}, 1000);
			    } else {
			      app.alert(res.msg);
			    }
			
			  });
			},
			zhankai:function(e){
				var that=this
			  var type = e.currentTarget.dataset.type;
				var curindex = e.currentTarget.dataset.curindex;
				if(type=='zhedie'){
					var curindex = -1
					that.curindex=curindex
				}
				if(type=='zhankai'){
					var showmore = that.showmore
					that.showmore = !showmore
					that.curindex = curindex
				}
			},
			zhankai1:function(e){
				var that=this
				var type = e.currentTarget.dataset.type;
				var dataindex = e.currentTarget.dataset.dataindex;
				if(type=='zhedie'){
					var dataindex = -1
					that.dataindex=dataindex
				}
				if(type=='zhankai'){
					var showmore1 = that.showmore1
					that.showmore1 = !showmore1
					that.dataindex = dataindex
				}
			},
			editorChooseImage: function (e) {
				var that = this;
				var idx = e.currentTarget.dataset.idx;
				var field = e.currentTarget.dataset.field;
				var pics = that.editorFormdata[field]
				console.log(field);
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
			download:function(e){
			    var that = this;
			    var file = e.currentTarget.dataset.file;
			    // #ifdef H5
			        window.location.href= file;
			    // #endif
			    
			    // #ifdef MP-WEIXIN
			    uni.downloadFile({
			    	url: file, 
			    	success: (res) => {
			            var filePath = res.tempFilePath;
			    		if (res.statusCode === 200) {
			    			uni.openDocument({
			                  filePath: filePath,
			                  showMenu: true,
			                  success: function (res) {
			                    console.log('打开文档成功');
			                  }
			                });
			    		}
			    	}
			    });
			    // #endif
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
										var maxsize = that.form.content[idx].val6;
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
										var maxsize = that.formdetail.content[idx].val11;
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
			                            
			                            editorFormdata[idx] = data.url;
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
								var maxsize =  that.form.content[idx].val6;
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
		}
	}
</script>

<style>
	page{	background: #f6f6f6;}
	.banner{	position: absolute;	width: 100%;height: 700rpx;}
	.page{	position: relative;	padding: 30rpx;}
	.header{	padding: 30rpx;display: flex;justify-content: space-between;}
	.header_title{	font-size: 40rpx;	color: #fff;	font-weight: bold;}
	.header_text{	color: rgba(255, 255, 255, 0.8);	font-size: 24rpx;margin-top: 20rpx;		display: flex;align-items: center;}
	.header_icon{		height: 30rpx;width: 30rpx;	margin: 0 0 0 10rpx;}
	.header_tag{	width: 80rpx;	}
	.body{position: relative;	padding: 30rpx 50rpx;	background: #fff;	border-radius: 20rpx;	margin-top: 20rpx;}
	.body_title{	position: relative;font-size: 35rpx;color: #333;	text-align: center;	font-weight: bold;}
	.body_title text{position: relative;}
	.body_icon{position: absolute;	width: 120rpx;	left: 0;	right: 0;	bottom: 0;	margin: 0 auto;}
	.content{	margin-top: 50rpx;}
	.module{		position: relative;	padding: 0 0 0 50rpx;	min-height: 200rpx;	border-left: 2px dashed #e4e5e7;}
	.module_title{	font-size: 28rpx;	color: #333;	font-weight: bold;}
	.module_time{	font-size: 24rpx;	color: #999;margin-top: 10rpx;}
	.module_text{		font-size: 26rpx;	color: #666;	margin-top: 10rpx;}
	.module_opt{	padding: 30rpx 0 50rpx 0;}
	.module_btn{	font-size: 24rpx;color: #333;border: 1px solid #f0f0f0;	padding: 15rpx 30rpx;	border-radius: 100rpx;	margin: 0 10rpx 0 0;}
	.module_r{background: #fd3b60;color: #fff;}
	.module_tag{	position: absolute;	height: 26px;width: 26px;left: -14px;	top: 0;	}
	.module_active{	border-color: #fd3b60;}
	.module:last-child{	border-color: #fff;}
	.module_null{		color: #999;}
	
	.module_opt .t3{ margin-bottom: 10rpx; font-size:26rpx; color:#666}
	.module_opt .t3_1{  color: #999;}
	.module_opt .t4{ margin-bottom: 10rpx; font-size:26rpx ;color: #999;}
	
	
	
	
	.modal .modal_jindu{ background: #fff;align-items: center; margin: auto; width: 100%;  border-radius: 10rpx; padding: 40rpx;margin-top: 30rpx;}
	.modal_jindu .close image { width: 20rpx; height: 20rpx; position: absolute; top:10rpx; right: 20rpx;}
	.modal_jindu .title{ font-size: 32rpx; font-weight: bold;}
	.uni-list{ margin-top: 30rpx;}
	.uni-list-cell{ display: flex; height: 80rpx;}
	.beizhu label{ width: 100rpx;}
	.modal_jindu .btn{  background: #1658c6; border-radius: 3px;line-height: 24px; border: none; padding: 0 10px;color: #fff;font-size: 20px; text-align: center; width: 300px;  display: flex; height: 40px; justify-content: center;align-items: center;}
	
	
	.modal_jindu .item .f1{ width:60rpx;position:relative}
	/*.logistics img{width: 15px; height: 15px; position: absolute; left: -8px; top:11px;}*/
	.modal_jindu .item .f1 image{width: 30rpx; height: 100%; position: absolute; left: -16rpx; top: 0rpx;}
	.modal_jindu .item .f2{display:flex;flex-direction:column;flex:auto;padding:10rpx 0}
	.modal_jindu .item .f2 .t1{font-size: 30rpx;}
	.modal_jindu .item .f2 .t1{font-size: 26rpx;}
	
	
	
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
	
	

	.form-item4{width:100%;background: #fff; padding: 20rpx 0rpx;margin-top:1px}
	.form-item4 .label{ width:150rpx;}
	.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
	.layui-imgbox-img{display: block;width:150rpx;height:150rpx;padding:2px;background-color: #f6f6f6;overflow:hidden ;margin-top: 20rpx;}
	.layui-imgbox-img>image{max-width:100%;}
	.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
	.uploadbtn{position:relative;height:200rpx;width:200rpx}
	
	.hfitem{display:flex;width:100%;padding:20rpx 0;border-bottom:1px dashed #ededed;}
	.hfitem:last-child{ border-bottom: 0;}
	.hfitem .t1{width:200rpx;color: #999;}
	.hfitem .t1.title{font-size: 36rpx;font-weight: 600;line-height: 80rpx;width:100%}
	.hfitem .t2{flex:1;text-align:right}
	.hfitem .red{color:red}
	.zktext{ text-align: right;margin-top: 20rpx;  color: #1296db;	font-size: 28rpx; display:flex; align-items: center; justify-content: end; }
	.zktext image{ width:30rpx; height:30rpx}
	
</style>
