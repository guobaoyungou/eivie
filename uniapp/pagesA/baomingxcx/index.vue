<template>
<view class="container" >
	<block v-if="isload">	
		
		<view v-if="order && order.status==0">
			<view class="step1" >
					<view class="content">
						<view class="f1"><image :src="pre_url+'/static/imgsrc/baomingxcx/shenhezhong.png'" /></view>
						<view class="f2">您的资料正在审核中</view>
					</view>
			</view>
		</view>

		<view v-else-if="order && order.status==1">
			<view class="step1" >
					<view class="content">
						<view class="f1"><image :src="pre_url+'/static/imgsrc/baomingxcx/tongguo.png'"  style="width: 150rpx; height: 150rpx;"/></view>
						<view class="f2" style="margin-top: 20rpx;">您的资料已审核通过</view>
						<block v-if="order.paystatus==1">
							<view class="f2" style="margin-top: 20rpx;" v-if="order.paystatus==1">已支付</view>
							<view v-if="order.poster" class="paybtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'"   @tap="dengji" data-feild='zhunkaozheng'  :data-poster="order.poster" >下载准考证</view>
							<view v-else class="tips" style="text-align: center;">等待准考证生成</view>
							<view class="paybtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" @tap="dengji"  data-field="dengji"  :data-poster="order.dengjipic" >查看登记表</view>
						</block>
						<block v-else>
							<view class="f2" style="margin-top: 20rpx;">需支付金额：<text style="color: red;">￥{{info.price}}</text></view>
							<view class="paybtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit"  @tap.stop="goto" :data-url="'/pagesExt/pay/pay?id=' + order.payorderid">立即支付</view>
						</block>
					</view>
				
			</view>
		</view>	
		<view v-else>
						<view class="tips" v-if="order.status==2">审核未通过：{{order.checkreason}}</view>
						<form @submit="subform">
							<view class="step1" >
									<view class="title">基本信息</view>
									<view class="form-box">
										<view class="form-item flex border">
											<view class="f1">姓名<text style="color:red"> *</text></view>
											<view class="f2" >
												<input name="realname" :value="order.realname" placeholder="请输入真实姓名" > </input>
											</view>
										</view>
										<view class="form-item flex border">
											<view class="f1">身份证号<text style="color:red"> *</text></view>
											<view class="f2" >
												<input name="icode" :value="order.icode" placeholder="请输入身份证号" > </input>
											</view>
										</view>
										<view class="form-item flex border">
											<view class="f1">性别<text style="color:red"> *</text></view>
											<view class="f2" >
												<text data-sex='1' @tap="sexChage" :class="'radio1 ' +(sex==1?'checked':'')" > 男 </text> 
												<text data-sex='2'  @tap="sexChage" :class="'radio1 ' +(sex==2?'checked':'')"> 女</text>
											</view>
										</view>
										<view class="form-item flex border">
											<view class="f1">出生日期<text style="color:red"> *</text></view>
											<view class="f2" >
												<picker class="picker" mode="date" value="" start="1900-01-01" data-field="birthday" @change="bindDateChange">
													<view v-if="birthday">{{birthday}}</view>
													<view v-else>请选择出生日期</view>
												</picker>
												<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
										</view>
										
										<view class="form-item flex border">
											<view class="f1">民族<text style="color:red"> *</text></view>
											<view class="f2" >
												<input type="text" style="display: none;"  name="minzu" :value="index1">
												<picker class="picker" mode="selector" data-field="index1"  data-id="1"  @change="BindPickerChange"  :value='index1'  :range="info.minzu"  >
													<view  v-if="info.minzu[index1]">{{info.minzu[index1]}}</view>
													<view  v-else>请选择民族</view>
												</picker>
												<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
										</view>
									
										<view class="form-item flex border">
											<view class="f1">婚姻状况<text style="color:red"> *</text></view>
											<view class="f2" >
												<input type="text"  style="display: none;"  name="hunyin" :value="index2">
												<picker class="picker" mode="selector" data-field="index2"  @change="BindPickerChange" :value='index2' :range="info.hunyin"  >
													<view  v-if="info.hunyin[index2]">{{info.hunyin[index2]}}</view>
													<view  v-else>请选择</view>
												</picker>
												<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
										</view>
										<view class="form-item flex border">
											<view class="f1">政治面貌<text style="color:red"> *</text></view>
											<view class="f2" >
													<input type="text"  style="display: none;"  name="zhengzhimianmao" :value="index3">
												<picker class="picker" mode="selector" data-field="index3"  @change="BindPickerChange" :value='index3'  :range="info.zhengzhimianmao">
												<view  v-if="info.zhengzhimianmao[index3]">{{info.zhengzhimianmao[index3]}}</view>
												<view  v-else>请选择</view>
												</picker>
												
											
												<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
										</view>
										
										<view class="form-item flex border">
											<view class="f1">籍贯<text style="color:red"> *</text></view>
											<view class="f2" >
												<input name="jiguan" :value="order.jiguan" placeholder="请输入籍贯" > </input>
											</view>
										</view>
										
										<view class="form-item flex border">
											<view class="f1">户籍地<text style="color:red"> *</text></view>
											<view class="f2" >
												<input name="hujidi" :value="order.hujidi" placeholder="请输入户籍地" > </input>
											</view>
										</view>
										
										<view class="form-item flex border">
											<view class="f1">生源地</view>
											<view class="f2" >
												<input name="sydi" :value="order.sydi" placeholder="请输入生源地" > </input>
											</view>
										</view>
								</view>	
								<view class="title">证件照片 <text style="color: #999;font-size: 24rpx"> (请上传一寸照片) </text><text style="color:red">*</text></view>
								<view class="form-box">
									<view class="apply_box">
								
										<view class="flex" style="flex-wrap:wrap;padding-bottom:20rpx;">
											<view v-for="(item, index) in zhengjian" :key="index" class="layui-imgbox">
												<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="zhengjian"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
												<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix" ></image></view>
											</view>
											<view class="uploadbtn" v-if="zhengjian.length<1" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="zhengjian"></view>
										</view>
										<view class="shili"><image :src="pre_url+'/static/imgsrc/baomingxcx/shili.png'" /></view>
										<input type="text" hidden="true" name="zhengjian" :value="zhengjian.join(',')" maxlength="-1"></input>
									</view>
									<view class="pictips">
										<text class="t1">要求近期彩色正面免冠1寸证件照片;格式支持：JPG、JPEG,最大支持50KB</text>
										<text class="t2">证件照片参考模板</text>
									</view>
								</view>
								<view class="title">联系方式</view>
								<view class="form-box">
									<view class="form-item flex border">
										<view class="f1">联系电话<text style="color:red"> *</text></view>
										<view class="f2" >
											<input name="tel" :value="order.tel" placeholder="请输入联系电话" > </input>
										</view>
									</view>

									<view class="form-item flex border">
										<view class="f1">紧急电话<text style="color:red"> *</text></view>
										<view class="f2" >
											<input name="jjtel" :value="order.jjtel" placeholder="请输入紧急电话" > </input>
										</view>
									</view>


									<view class="form-item flex border">
										<view class="f1">通讯地址<text style="color:red"> *</text></view>
										<view class="f2" >
											<input name="txaddress" :value="order.txaddress" placeholder="请输入通讯地址" > </input>
										</view>
									</view>

			
								</view>
								
								<view class="title">教育信息</view>
								<view class="form-box">
									<view class="form-item flex border">
										<view class="f1">学历<text style="color:red"> *</text></view>
										<view class="f2" >
											<input type="text" style="display: none;"   name="xueli" :value="index4">
											<picker class="picker" mode="selector" data-field="index4"  @change="BindPickerChange" :value='index4'  :range="info.xueli"  >
												<view  v-if="info.xueli[index4]">{{info.xueli[index4]}}</view>
												<view  v-else>请选择</view>
											</picker>
										</view>
									</view>
					

									<view class="form-item flex border">
										<view class="f1">学位<text style="color:red"> *</text></view>
										<view class="f2" >
											<input type="text" style="display: none;"   name="xuewei" :value="index5">
											<picker class="picker" mode="selector" data-field="index5"  @change="BindPickerChange" :value='index5'  :range="info.xuewei"  >
												<view  v-if="info.xuewei[index5]">{{info.xuewei[index5]}}</view>
												<view  v-else>请选择</view>
											</picker>
										</view>
									</view>


									<view class="form-item flex border">
										<view class="f1">毕业院校<text style="color:red"> *</text></view>
										<view class="f2" >
											<input name="biyeschool" :value="order.biyeschool" placeholder="请输入毕业院校" > </input>
										</view>
									</view>


									<view class="form-item flex border">
										<view class="f1">毕业时间<text style="color:red"> *</text></view>
										<view class="f2" >
											<picker class="picker" mode="date" value="" start="1900-01-01"  @change="bindDateChange" data-field="biyedate">
												<view v-if="biyedate">{{biyedate}}</view>
												<view v-else>请选择</view>
											</picker>
										</view>
									</view>
					
					
									<view class="form-item flex border">
										<view class="f1">所属专业<text style="color:red"> *</text></view>
										<view class="f2" >
											<input name="zhuanye" :value="order.zhuanye" placeholder="请输入所属专业" > </input>
										</view>
									</view>

					
									<view class="form-item flex border">
										<view class="f1">教育形式<text style="color:red"> *</text></view>
										<view class="f2" >
											<input type="text" style="display: none;"   name="jyxingshi" :value="index6">
											<picker class="picker" mode="selector" data-field="index6"  @change="BindPickerChange" :value='index6'  :range="info.jyxingshi"  >
												<view  v-if="info.jyxingshi[index6]">{{info.jyxingshi[index6]}}</view>
												<view  v-else>请选择</view>
											</picker>
										</view>
									</view>

					
									<view class="form-item flex border">
										<view class="f1">外语水平</view>
										<view class="f2" >
											<input name="en_level" :value="order.en_level" placeholder="请输入外语水平" > </input>
										</view>
									</view>


									<view class="form-item flex border">
										<view class="f1">计算机水平</view>
										<view class="f2" >
											<input name="pc_level" :value="order.pc_level" placeholder="请输入计算机水平" > </input>
										</view>
									</view>
								</view>
								
								<view class="title">健康信息</view>
								<view class="form-box">
										<view class="form-item flex border">
											<view class="f1">身高<text style="color:red"> *</text></view>
											<view class="f2" >
												<input name="shengao" :value="order.shengao" placeholder="请输入身高" > </input>
											</view>
										</view>
										<view class="form-item flex border">
											<view class="f1">体重<text style="color:red"> *</text></view>
											<view class="f2" >
												<input name="weight" :value="order.weight" placeholder="请输入体重" > </input>
											</view>
										</view>
										<view class="form-item flex border">
											<view class="f1">辨色力</view>
											<view class="f2" >
												<input type="text" style="display: none;"   name="bianseli" :value="index7">
												<picker class="picker" mode="selector" data-field="index7"  @change="BindPickerChange" :value='index7'  :range="info.bianseli"  >
													<view  v-if="info.bianseli[index7]">{{info.bianseli[index7]}}</view>
													<view  v-else>请选择</view>
												</picker>
											</view>
										</view>
								</view>
								<view class="title">其他信息</view>
								<view class="form-box">
										<view class="form-item flex border">
											<view class="f1">本人身份<text style="color:red"> *</text></view>
											<view class="f2" >
												<checkbox-group name="benrenshenfen" class="checkbox-group" >
													<label v-for="(item1,idx1) in info.benrenshenfen" class="flex-y-center">
														<checkbox class="checkbox" :checked="inArray(item1,order.benrenshenfen)?true:false" :value="item1"/>{{item1}}
													</label>
												</checkbox-group>
											</view>
										</view>
										<view class="form-item flex border">
											<view class="f1">是否满足加分条件</view>
											<view class="f2" >
													<input type="text" style="display: none;"   name="addtiaojian" :value="index8">
												<picker class="picker" mode="selector" data-field="index8"  @change="BindPickerChange" :value='index8'  :range="info.addtiaojian"  >
													<view  v-if="info.addtiaojian[index8]">{{info.addtiaojian[index8]}}</view>
													<view  v-else>请选择</view>
												</picker>
											</view>
										</view>
										
										<view class="form-item  border">
											<view class="f1" style="height: 80rpx;">奖惩情况</view>
											<view class="f3" style="border: 1rpx solid #f5f5f5; border-radius: 10rpx;">
												<textarea name="jiangchengqk" class='textarea'  :value="order.jiangchengqk"  placeholder-style="font-size:28rpx"/>
											</view>
										</view>
										
								
								</view>
								<view class="title" style="display: flex; justify-content: space-between;">
									<view><text style="color:red"> *</text><text>家庭情况</text></view>
									<text style="color: #999; font-size: 24rpx;" @tap="addjiating">添加家庭情况</text>
								</view>
								<view class="form-box" v-if="jiatinglist" v-for="(item,index) in jiatinglist">
									<view class="form-item flex border">
										<view class="f1">姓名<text style="color:red"> *</text></view>
										<view class="f2" >
											<input  @input="jiatinglistInput" :data-index="index" data-field="name"  :value="item.name" placeholder="请填写姓名" > </input>
										</view>
									</view>
									<view class="form-item flex border">
										<view class="f1">关系<text style="color:red"> *</text></view>
										<view class="f2" >
											<input @input="jiatinglistInput" :data-index="index" data-field="guanxi"  :value="item.guanxi" placeholder="请填写关系" > </input>
										</view>
									</view>
								
									<view class="form-item flex border">
										<view class="f1">单位<text style="color:red"> *</text></view>
										<view class="f2" >
											<input  @input="jiatinglistInput" :data-index="index" data-field="danwei"  :value="item.danwei" placeholder="请填写单位" > </input>
										</view>
									</view>
									
									<view class="form-item flex border">
										<view class="f1">紧急联系电话<text style="color:red"> *</text></view>
										<view class="f2" >
											<input  @input="jiatinglistInput" :data-index="index" data-field="tel" :value="item.tel" placeholder="紧急联系电话" > </input>
										</view>
									</view>
									<view class="del" :data-index="index"  @tap="removejiating">删除</view>
								</view>
								<view v-else>
									
								</view>
								
								<view class="title" style="display: flex; justify-content: space-between;">
									<view><text style="color:red"> *</text><text>学习经历</text></view>
									<text style="color: #999; font-size: 24rpx;" @tap="addxuexi">添加学习经历</text></view>
								<view class="form-box" v-if="xuexilist" v-for="(item,index) in xuexilist">
									
									<view class="form-item flex border">
										<view class="f1">起止日期<text style="color:red"> *</text></view>
										<view class="f2" >
											<picker class="picker" mode="date" :value="jddate[index]" start="1900-01-01"  :data-index='index' @change="bindDateChange" :data-field="'jddate'">
												<block v-if="item.jddate && !jddate[index]">
													<view v-if="item.jddate">{{item.jddate}}</view>
												</block>
												<block v-else-if="jddate[index]">	
													<view v-if="jddate[index]">{{jddate[index]}}</view>
												</block>
												<view v-else  style="color: #999;">请选择开始日期</view>
											</picker>
											 - 
											<picker class="picker" mode="date" :value="bydate[index]"  start="1900-01-01"  :data-index="index" @change="bindDateChange" data-field="bydate">
												<block v-if="item.bydate && !bydate[index]">
													<view v-if="item.bydate">{{item.bydate}}</view>
												</block>
												<block v-else-if="bydate[index]">	
														<view v-if="bydate[index]">{{bydate[index]}}</view>
												</block>
												<view v-else style="color: #999;">请选择毕业日期</view>
											</picker>
										</view>
									</view>
				
									<view class="form-item flex border">
										<view class="f1">学历/学位<text style="color:red"> *</text></view>
										<view class="f2" >
											<input  @input="xuexilistInput" :data-index="index" data-field="xueli"  :value="item.xueli" placeholder="请填写学历/学位" > </input>
										</view>
									</view>
									<view class="form-item flex border">
										<view class="f1">毕业院校及系别<text style="color:red"> *</text></view>
										<view class="f2" >
											<input @input="xuexilistInput" :data-index="index" data-field="school" :value="item.school" placeholder="请填写毕业院校" > </input>
										</view>
									</view>
									<view class="form-item flex border">
										<view class="f1">所学专业<text style="color:red"> *</text></view>
										<view class="f2" >
											<input  @input="xuexilistInput" :data-index="index" data-field="zhuanye"  :value="item.zhuanye" placeholder="请填写专业" > </input>
										</view>
									</view>
									<view class="form-item flex border">
										<view class="f1">学习形式<text style="color:red"> *</text></view>
										<view class="f2" >
											<input  @input="xuexilistInput" :data-index="index" data-field="xuexixingshi"  :value="item.xuexixingshi" placeholder="请填写学习形式" > </input>
										</view>
									</view>
									<view class="del" :data-index="index"  @tap="removexuexi">删除</view>
								</view>
								<view v-else>
									
								</view>
								
			
								<view class="title">报名附件</view>
								<view class="form-box">
	
									
									<view class="flex-y-center" style="flex-wrap:wrap;padding:20rpx 0;">
										<view class="dp-form-imgbox" v-if="shenfenzheng.length>0">
											<view v-for="(item, index) in shenfenzheng" :key="index" class="layui-imgbox">
												<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="shenfenzheng"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
												<view class="layui-imgbox-img2"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
											</view>
												<input type="text" hidden="true" name="shenfenzheng" :value="shenfenzheng.join(',')" maxlength="-1"/>
										</view>
										<block >
											<view  class="dp-form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 30rpx',backgroundSize:'50rpx 50rpx',backgroundColor:'#F3F3F3'}"  @tap="uploadimg" data-field="shenfenzheng" style="margin-right:20rpx;"></view>
											<view  style="color:#999"><text style="color:red"> *</text>身份证正反面必传</view>
										</block>
									</view>
									
									<view class="flex-y-center" style="flex-wrap:wrap;padding:20rpx 0;">
										<view class="dp-form-imgbox" v-if="biyezheng.length>0">
											<view v-for="(item, index) in biyezheng" :key="index" class="layui-imgbox">
												<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="biyezheng"><image :src="pre_url+'/static/img/ico-del.png'"></image></view>
												<view class="layui-imgbox-img2"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
											</view>
												<input type="text" hidden="true" name="biyezheng" :value="biyezheng.join(',')" maxlength="-1"/>
										</view>
										<block >
											<view  class="dp-form-uploadbtn" :style="{background:'url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 30rpx',backgroundSize:'50rpx 50rpx',backgroundColor:'#F3F3F3'}"  @tap="uploadimg" data-field="biyezheng" style="margin-right:20rpx;"></view>
											<view  style="color:#999"><text style="color:red"> *</text>毕业证必传</view>
										</block>
									</view>
									

								</view>		
							</view>		
							
							
							
							<view class="step" >
									
								<view class="agree flex"> 
									<checkbox-group @change="isagreeChange"><label class="flex-y-center"><checkbox class="checkbox" value="1" :checked="isagree"/>我已阅读并同意</label></checkbox-group>
									<text :style="{color:t('color1')}"  @tap="showxieyiFun">《承诺书》</text>
								</view>
								<view class="flex">
									<button class="savebtn2" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit">立即提交</button>
								</view>	
							</view>	
							<view style="height:50rpx"></view>
						</form>
				</view>
				
				<view class="posterDialog" v-if="showdengji">
					<view class="main">
						<view class="close" @tap="posterDialogClose"><image class="img" :src="pre_url+'/static/img/close.png'"/></view>
						<view class="content">
							<image class="img" :src="dengjipic" mode="widthFix" @tap="previewImage" :data-url="dengjipic"></image>
						</view>
						<view  @tap="saveposter(dengjipic)"  :data-poster="dengjipic" class="upload" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" > {{field=='dengji'?'下载登记表':'下载准考证'}}</view>
					</view>
				</view>
				

		<view v-if="showxieyi" class="xieyibox">
			<view class="xieyibox-content">
				<view style="overflow:scroll;height:100%;">
					<parse :content="info.content" @navigate="navigate"></parse>
				</view>
				<view style="position:absolute;z-index:9999;bottom:10px;left:0;right:0;margin:0 auto;text-align:center; width: 50%;height: 60rpx; line-height: 60rpx; color: #fff; border-radius: 8rpx;" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  @tap="hidexieyi">已阅读并同意</view>
			</view>
		</view>
	</block>
	<loading v-if="loading"></loading>
	<popmsg ref="popmsg"></popmsg>
	<view style="display:none">{{test}}</view>
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
      info:{un:'',tel:'',realname:''},
			showxieyi:false,
			zhengjian:[],
			sex:1,
			birthday:'',
			fujian01:'',
			hukoubo:[],
			index1:0,
			index2:0,
			index3:0,
			index4:0,
			index5:0,
			index6:0,
			index7:0,
			index8:0,
			info:[],
			picker:[],
			field:'',
			isagree:false,
			biyedate:'',
			jiatinglist:[],
			xuexilist:[],
			worklist:[],
			shenfenzheng:[],
			biyezheng:[],
			tuiwuzheng:[],
			xuejibaogao:[],
			other:[],
			order:[],
			jddate:{},
			bydate:{},
			workdatestart:{},
			workdateend:{},
			showdengji:false,
			dengjipic:'',
			test:'',
			tmplids: [],
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
		var that = this;
		var url = app.globalData.pre_url+'/static/area.json?time=v1';
		console.log(url);
		uni.request({
			url: url,
			data: {},
			method: 'GET',
			header: { 'content-type': 'application/json' },
			success: function(res2) {
				console.log(res2.data);
				that.items = res2.data
			}
		});
  },
  methods: {
		getdata:function(){
			var that = this;
			that.loading = true;
			var id = this.opt.id
			app.get('ApiBaomingxcx/getsysset',{ id:id}, function (res) {
				that.loading = false;
				if(res.status==1){
					if(res.data){
						that.info = res.data
					}
					that.tmplids = res.tmplids;
					if(res.order){
							that.order = res.order
							that.birthday = res.order.birthday
							that.index1 = res.order.minzu
							that.index2 = res.order.hunyin
							that.index3 = res.order.zhengzhimianmao
							that.index4 = res.order.zhengzhimianmao
							that.index5 = res.order.zhengzhimianmao
							that.index6 = res.order.zhengzhimianmao
							that.index7 = res.order.zhengzhimianmao
							that.index8 = res.order.zhengzhimianmao
							that.zhengjian = res.order.zhengjian
							that.biyedate = res.order.biyedate
							that.hukoubo = res.order.hukoubo
							that.shenfenzheng = res.order.shenfenzheng
							that.biyezheng = res.order.biyezheng
							that.tuiwuzheng = res.order.tuiwuzheng
							that.xuejibaogao = res.order.xuejibaogao
							that.other = res.order.other
							that.jiatinglist =  res.order.jiatinglist
							that.xuexilist =  res.order.xuexilist
							that.worklist =  res.order.worklist
							that.sex =  res.order.sex
					}
					that.loaded();
				}else{
					app.alert('参数缺失');
				}
			
			});
		},
		
    subform: function (e) {
      var that = this;
			//var tags = JSON.stringify(that.tags);
      var formdata = e.detail.value;

			formdata.sex  = that.sex
			formdata.birthday  = that.birthday
			formdata.biyedate = that.biyedate
	

			formdata.jiatinglist = that.jiatinglist
			formdata.xuexilist = that.xuexilist
			formdata.worklist = that.worklist
			//console.log(formdata);
			
			if(formdata.realname==''){
				app.error('请填写姓名');return;	
			}
			if(formdata.icode==''){
				app.error('请填写身份证号');return;	
			}
			if (!/(^\d{15}$)|(^\d{18}$)|(^\d{17}(\d|X|x)$)/.test(formdata.icode)) {
				app.alert('身份证号格式错误');return;
			}
			if(formdata.sex==''){
				app.error('请选择性别');return;	
			}
			if(formdata.birthday==''){
				app.error('请填写生日');return;	
			}
			if(formdata.minzu==''){
				app.error('请选择民族');return;	
			}
			if(formdata.hunyin==''){
				app.error('请选择婚姻');return;	
			}
			if(formdata.zhengzhimianmao==''){
				app.error('请选择政治面貌');return;	
			}
			if(formdata.jiguan==''){
				app.error('请填写籍贯');return;	
			}
			if(formdata.hujidi==''){
				app.error('请填写户籍地');return;	
			}
			if(formdata.zhengjian==''){
				app.error('请上传证件照');return;	
			}
			if(formdata.tel==''){
				app.error('请填写联系方式');return;	
			}
			if (!app.isPhone(formdata.tel)) {
				app.alert('联系方式格式错误');return;
			}
			if(formdata.jjtel==''){
				app.error('请填写紧急联系方式');return;	
			}
			if(formdata.txaddress==''){
				app.error('请填写通讯地址');return;	
			}
			if(formdata.xueli==''){
				app.error('请选择学历');return;	
			}
			if(formdata.xuewei==''){
				app.error('请选择学位');return;	
			}
			if(formdata.biyedate==''){
				app.error('请选择毕业时间');return;	
			}
			if(formdata.biyeschool==''){
				app.error('请填写毕业院校');return;	
			}
			if(formdata.zhuanye==''){
				app.error('请填写专业');return;	
			}
			if(formdata.jyxingshi==''){
				app.error('请选择教育形式');return;	
			}
			if(formdata.shengao==''){
				app.error('请填写身高');return;	
			}
			if(formdata.weight==''){
				app.error('请填写体重');return;	
			}
			if(formdata.benrenshenfen==''){
				app.error('请选择本人身份');return;	
			}

		
			if(formdata.shenfenzheng=='' || formdata.shenfenzheng==undefined){
				app.error('请上传身份证');return;	
			}                                             
			
			if(formdata.biyezheng==''  || formdata.biyezheng==undefined){
				app.error('请上传毕业证');return;	
			}
			if(formdata.jiatinglist=='' ){
				app.error('请将填写家庭情况');return;
			}
			if(formdata.xuexilist=='' ){
				app.error('请将填学习经历');return;
			}
	
			var id = that.opt.id ? that.opt.id : '';
			var orderid = that.order.id ? that.order.id : '';
			if(!that.isagree){
				app.error('请先阅读并同意服务协议');return;	
			}
			app.showLoading('提交中');
			//console.log(formdata);return;
      app.post('ApiBaomingxcx/formsubmit', {id:id,info:formdata,orderid:orderid}, function (res) {
				app.showLoading(false);
        if (res.status == 1) {
					app.success(res.msg);
					that.subscribeMessage(function () {
						setTimeout(function () {
							that.getdata();
						}, 1000)
					})
        } else {
          app.error(res.msg);
        }
      });
    },
		sexChage:function(e){
				this.sex = e.currentTarget.dataset.sex;
		},
		BindPickerChange:function(e){
			var that = this;
			var index = e.detail.value;
			var field = e.currentTarget.dataset.field;
			that[field] = index
		},
		saveposter:function(pic){
			var that = this;
			app.showLoading('图片保存中');
			uni.downloadFile({
				url: pic,
				success (res) {
					if (res.statusCode === 200) {
						uni.saveImageToPhotosAlbum({
							filePath: res.tempFilePath,
							success:function () {
								app.success('保存成功');
							},
							fail:function(){
								app.showLoading(false);
								app.error('保存失败');
							}
						})
					}
				},
				fail:function(){
					app.showLoading(false);
					app.error('下载失败');
				}
			});
		},
		dengji: function (e) {
			var that = this;
			that.showdengji = true;
			var field = e.currentTarget.dataset.field;
			that.field = field;
			if(field=='dengji'){
				that.sharetypevisible = false;
				//app.showLoading('生成中');
				var poster = e.currentTarget.dataset.poster;
				that.dengjipic = poster;
				/*app.post('ApiBaomingxcx/poster', { id: that.order.id}, function (data) {
					app.showLoading(false);
					if (data.status == 0) {
						app.alert(data.msg);
					} else {
						that.dengjipic = data.poster;
					}
				});*/
			}else{
					var poster = e.currentTarget.dataset.poster;
					console.log(poster);
					that.dengjipic = poster;
			}
	
		},
		posterDialogClose: function () {
			this.showdengji = false;
		},
		addjiating:function(e){
			var that=this;
			var jiatinglist = that.jiatinglist;
			var length= jiatinglist.length
			var val = { name:'',guanxi:'',danwei:'',tel:''}
			jiatinglist.push(val);
			this.jiatinglist = jiatinglist;
		},
		jiatinglistInput:function(e){
			var index = e.currentTarget.dataset.index;
			var field = e.currentTarget.dataset.field;
			var jiatinglist = this.jiatinglist;
			jiatinglist[index][field] = e.detail.value;
			this.jiatinglist = jiatinglist;
		},
		removejiating:function(e){
			var that=this
			var index = e.currentTarget.dataset.index;
			var jiatinglist = that.jiatinglist;
			jiatinglist.splice(index,1);
			this.jiatinglist = jiatinglist;
		},

		addxuexi:function(e){
			var that=this;
			var xuexilist = that.xuexilist;
			var length= xuexilist.length
			var val = { jddate:'',bydate:'',xueli:'',school:'',zhuanye:'',xuexixingshi:''}
			xuexilist.push(val);
			this.xuexilist = xuexilist;
		},
		xuexilistInput:function(e){
			var index = e.currentTarget.dataset.index;
			var field = e.currentTarget.dataset.field;
			var xuexilist = this.xuexilist;
			xuexilist[index][field] = e.detail.value;
			this.xuexilist = xuexilist;
		},
		
		
		
		removexuexi:function(e){
			var that=this
			var index = e.currentTarget.dataset.index;
			var xuexilist = that.xuexilist;
			xuexilist.splice(index,1);
			this.xuexilist = xuexilist;
		},
		
		addwork:function(e){
			var that=this;
			var worklist = that.worklist;
			var length= worklist.length
			var val = { workdatestart:'',workdateend:'', danwei:'',city:'',bumen:'',zhiwu:''}
			worklist.push(val);
			this.worklist = worklist;
		},
		worklistInput:function(e){
			var index = e.currentTarget.dataset.index;
			var field = e.currentTarget.dataset.field;
			var worklist = this.worklist;
			worklist[index][field] = e.detail.value;
			this.worklist = worklist;
			console.log(worklist);
		},
		removework:function(e){
			var that=this
			var index = e.currentTarget.dataset.index;
			var worklist = that.worklist;
			worklist.splice(index,1);
			this.worklist = worklist;
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
				console.log(field);
				that[field] = pics;
				console.log(that[field])
			},1)
		},
		removeimg:function(e){
			var that = this;
			var index= e.currentTarget.dataset.index
			var field= e.currentTarget.dataset.field
			var pics = that[field];
			pics.splice(index,1);
			that[field] = pics;
		
		},
		isagreeChange: function (e) {
		  var val = e.detail.value;
		  if (val.length > 0) {
		    this.isagree = true;
		  } else {
		    this.isagree = false;
		  }
		},
		showxieyiFun: function () {
		  this.showxieyi = true;
		},
		hidexieyi: function () {
		  this.showxieyi = false;
				this.isagree = true;
				if(this.wxloginclick){
					this.weixinlogin();
				}
		},
		bindDateChange: function(e) {
			var that=this
			var field= e.currentTarget.dataset.field
console.log(field);
			//this.birthday = e.detail.value
			if(field=='jddate' || field=='bydate'){
					var index= e.currentTarget.dataset.index
					//that.jdindex = index;
					that[field][index] = e.detail.value
									console.log(that[field][index]);
					var xuexilist = that.xuexilist;
					xuexilist[index][field] = e.detail.value;
					this.xuexilist = xuexilist;
			}else if(field=='workdatestart' || field=='workdateend'){
					var index= e.currentTarget.dataset.index
					//that.jdindex = index;
					that[field][index] = e.detail.value
					var worklist = that.worklist;
					worklist[index][field] = e.detail.value;
					this.worklist = worklist;
			}else{
					that[field] =  e.detail.value
			}
			that.test = Math.random();
		},


  }
};
</script>
<style>
	
radio{transform: scale(0.6);}
checkbox{transform: scale(0.6);}

.form-box{ padding:2rpx 24rpx 0 24rpx; background: #fff;margin: 24rpx;border-radius: 10rpx;  z-index: 1000;}
.form-item{ justify-content: space-between;border-bottom:1px solid #eee }
.form-item .jdtype{ line-height: 50rpx;}
.form-item .f1{color:#333;flex-shrink:0; font-size: 30rpx; display: flex; align-items: center;}
.form-item .f2{display:flex;align-items:center;  margin: 10rpx 0;justify-content: flex-end; width: 80%; }
.form-item .f2 input{ height: 80rpx;}
.form-item .f2 .picker{ height: 80rpx; line-height: 80rpx;}
.form-item .f2 .checkbox-group{ margin:20rpx}
.form-item .tbox { display: flex; padding: 30rpx 0;}
.form-item .tbox .pname{color:#7C7F8E;align-items:center; font-size: 30rpx; margin-right: 20rpx;}
.form-item .tbox .subname{color:#222222; margin-right: 15rpx; align-items:center; font-size: 24rpx;  height: 50rpx; background:#fff ; border: 1rpx solid #E5E5E5;border-radius: 24rpx; padding: 0rpx 20rpx;}
.form-item .tbox .subname.on{ color:#FF3A69; border: 1rpx solid #FFA1A1; background: ;font-size: 24rpx; background: #FFE9E9; }
	
.form-box .form-item:last-child{ border:none}
.form-box .flex-col{padding-bottom:20rpx}
.form-item input{ width: 100%; border: none;color:#111;font-size:28rpx; text-align: right; line-height: 80rpx;}
.form-item textarea{ width:100%;height:150rpx;padding:20rpx;border: none;}
.form-item .upload_pic{ margin:50rpx 0;background: #F3F3F3;width:90rpx;height:90rpx; text-align: center  }
.form-item .tishi{  line-height: 30rpx; color:#949798 ; margin-bottom: 30rpx;font-size: 26rpx;}
.form-item .tishi.t2{  background: #F4F4F4; padding:0rpx 20rpx; border-radius: 10rpx;}
.form-item .upload_pic image{ width: 32rpx;height: 32rpx; }
.subitem{ display: flex; align-items: center; margin-right: 20rpx;}


.step1{}
.step1 .title{ font-size:32rpx ;color:#333333; padding:20rpx  30rpx; font-weight: bold; border-bottom: 1rpx solid #EEEEEE;}
.step2 .title{ font-size:32rpx ;color:#333333; padding: 20rpx 0; border-bottom: 1rpx solid #EEEEEE;}
.step2 .border{ border-bottom: 1rpx solid #EEEEEE;}
.form-item .radio1{ font-size: 20rpx; display: flex; padding: 0rpx 20rpx;  background: #EEEEEE; color:#778899; border-radius: 24rpx; 
line-height: 50rpx;height: 50rpx; width: 120rpx; text-align: center; align-items: center; justify-content: center; margin-left: 20rpx;}
.form-item .radio1.checked{ background:#FF3A69;color: #fff; }

.form-item2{ line-height: 100rpx;justify-content: space-between;border-bottom:1px solid #eee }
.form-item2 input{ width: 100%; border: none;color:#111;font-size:28rpx; text-align: right; }
.form-item2 .f1{ margin-top: 10rpx;}
.form-item2 .f2 label{ margin-top: 11rpx; margin-left: 10rpx;}

.step .title{ font-size:32rpx ;color:#333333; padding: 20rpx 0; border-bottom: 1rpx solid #EEEEEE;}
.step .border{ border-bottom: 1rpx solid #EEEEEE;}
.step .t3{  margin: 30rpx 0 ; color: #C66121; font-size: 28rpx; width: 60%; }
.step .agree{ margin: 30rpx ;  color: #999; font-size: 28rpx; line-height: 50rpx;}
.step .agree .t4{  color: #FF3A69; }


.savebtn{ width: 90%; height:80rpx; line-height: 80rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none; }
.savebtn2{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none; }

.autoitem{padding:10rpx 0;flex:1;display:flex;justify-content: flex-end;}
.autoitem image{ width:50rpx;height:50rpx}
switch{transform:scale(.7);}
.shili{ margin-left: 20rpx; width: 200rpx; height: 200rpx;}
.shili image{ width: 100%; height: 100%;}

.dp-form-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative; display: flex;}
.dp-form-imgbox-close{position: absolute;display: block;width:32rpx;height:32rpx;right:-10rpx;top:-26rpx;color:#999;font-size:32rpx;background:#999;z-index:9;border-radius:50%}
.dp-form-imgbox-close .image{width:100%;height:100%}
.dp-form-imgbox-img{display: block;width:100rpx;height:100rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.dp-form-imgbox-img>.image{max-width:100%;}
.dp-form-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}
.layui-imgbox{ margin-right: 10rpx; position: relative;}


.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}

.layui-imgbox-img2{display: block;width:100rpx;height:100rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img2>image{max-width:100%;}

.clist-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:10rpx}
.radio .radio-img{width:100%;height:100%;display:block}

.freightitem{width:100%;height:60rpx;display:flex;align-items:center;margin-left:40rpx}
.freightitem .f1{color:#666;flex:1}


.xieyibox{width:100%;height:100%;position:fixed;top:0;left:0;z-index:99;background:rgba(0,0,0,0.7)}
.xieyibox-content{width:90%;margin:0 auto;height:80%;margin-top:20%;background:#fff;color:#333;padding:5px 10px 50px 10px;position:relative;border-radius:2px}

.apply_box{ margin-top: 20rpx; display: flex; justify-content:space-between}
.apply_box image{}

.dp-form-uploadbtn{position:relative;height:100rpx;width:100rpx}
	
.step1 .content{ width: 100%; min-height: 750rpx; color: #868686; font-size: 30rpx; position: fixed; top: 20%; }
.step1 .content .f1{ display: flex; flex-wrap: wrap; align-items: center; justify-content: center;}
.step1 .content .f2{ display: flex; flex-wrap: wrap; align-items: center; justify-content: center;}

.step1 .content image{ width:400rpx; height:400rpx}
.step1 .content .paybtn{ width: 80%; height:80rpx; line-height: 80rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 10%; margin-top:60rpx; border: none; } 

.tips{ color: red; padding:20rpx}
.upload{ text-align: center; height: 80rpx; width: 50%; line-height: 80rpx; color: #fff; border-radius: 50rpx; margin: 20rpx auto;}
.del{ text-align: right; height: 80rpx; line-height: 80rpx; color: red;}

.pictips{ display: flex; font-size:20rpx ; color: #999; padding-bottom:20rpx} 
.pictips .t1{display: flex; width: 65%; margin-right:20rpx}
</style>