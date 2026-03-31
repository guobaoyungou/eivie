<template>
<view>
	<block v-if="isload">
		<form @submit="subform">
			<view class="form-box">
				<view class="form-item">
					<view class="f1">登录账号<text style="color:red"> *</text></view>
					<view class="f2"><input type="text" name="un" :value="info.un" placeholder="请填写登录账号" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item">
					<view class="f1">登录密码<text style="color:red"> *</text></view>
					<view class="f2"><input type="text" name="pwd" placeholder="请填写登录密码" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item" v-if="bid == 0">
					<view class="f1">会员ID</view>
					<view class="f2">
						<input type="text" name="mid" :value="info.mid" placeholder="请填写会员ID" placeholder-style="color:#888"></input>
					</view>
				</view>
				<view class="form-item">
					<view class="f1">备注</view>
					<view class="f2">
						<input type="text" name="remark" :value="info.remark" placeholder="请填写备注" placeholder-style="color:#888"></input>
					</view>
				</view>
				<view v-if="(!info.id || info.isadmin==0 || info.isadmin == 3) && info.id != userid">
					<view class="form-item">
						<view class="f1">权限组</view>
						<view class="f2">	
							<picker class="picker" mode="selector" name="groupid" :value="groupidIndex>0 ? groupidIndex:''" :range="groupList" @change="groupChange"  placeholder-style="color:#888">
								<view> {{ groupList[groupidIndex] ? groupList[groupidIndex] : '请选择' }}</view>
							</picker>
						</view>
					</view>
					<!-- 手动选择权限 -->
					<view v-if="groupid == 0">
						<view class="form-item">
							<view class="f1">门店</view>
							<view class="f2">
								<picker class="picker" mode="selector" name="mdid" :value="mendianIndex>0 ? mendianIndex:''" :range="mendianList" @change="mendianChange"  placeholder-style="color:#888">
									<view> {{ mendianList[mendianIndex] ? mendianList[mendianIndex] : '请选择' }}</view>
								</picker>
							</view>
						</view>
						<view class="form-item">
							<view class="f1">首页数据统计</view>
							<view class="f2">
								<radio-group class="radio-group" name="showtj">
									<view class="radio-group-view">
										<label><radio value="1" :checked="info.showtj == 1 ? true : false"></radio>显示</label>
										<label><radio value="0" :checked="isEmpty(info) || info.showtj == 0 ? true : false"></radio>不显示</label>
									</view>
								</radio-group>
							</view>
						</view>
						<view class="form-item">
							<view class="f1">权限设置</view>
							<view class="f2" @tap="showMenu">
								{{ authdata.length > 0 ? '已选中'+authdata.length+'个' : '请选择'}}
							</view>
						</view>
						<view class="form-item">
							<view class="f1">手机端权限</view>
							<view class="f2" @tap="showMobileMenu">
								{{ mobileselect > 0 ? '已选中'+mobileselect+'个' : '请选择'}}
							</view>
						</view>
					</view>
					<tkiTree ref="tkitree" :range="menudata" rangeKey="name" :multiple="true" @confirm="treeConfirm" :confirmColor="t('color1')" :cancelColor="t('color1')" :selectParent='true'></tkiTree>
					<tkiTree ref="mobiletree" :range="mobilemenu" rangeKey="name" :multiple="true" @confirm="mobileConfirm" :confirmColor="t('color1')" :cancelColor="t('color1')" :selectParent='true'></tkiTree>
				</view>
			</view>
			<button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit">提交</button>
			<view style="height:50rpx"></view>
		</form>
	</block>
	<loading v-if="loading"></loading>
	<popmsg ref="popmsg"></popmsg>
	<wxxieyi></wxxieyi>
</view>
</template>

<script>
import tkiTree from './tki-tree/tki-tree.vue'
var app = getApp();
export default {
	components: {
		tkiTree
	},
  data() {
    return {
			isload:false,
			loading:false,
			pre_url:app.globalData.pre_url,
      info:{},
			groupid:-1,
			groupAll:[],
			groupList:[],
			groupidIndex:-1,
			mendianAll:[],
			mendianList:[],
			mendianIndex:0,
			mendianid:0,
			menudata:[],
			authdata:[],
			mobilemenu:[],
			mobileauth:{},
			mobileselect:0,
			bid:0,
			userid:0,
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
			app.post('ApiAdminUser/getAddParams',{id:that.opt.id},function (res) {
				//权限组
				let groupList = res.grouplist.map(item => item.name);
				that.groupAll = res.grouplist;
				that.groupList = groupList;
				
				//门店
				let mendianList = res.mendianlist.map(item => item.name);
				that.mendianAll = res.mendianlist;
				that.mendianList = mendianList;
				
				that.menudata = res.menudata;
				that.mobilemenu = res.mobilemenu;
				that.info = res.info;
				that.bid = res.bid;
				that.userid = res.userid;
				if(res.info){
					if(app.isNull(res.info.groupid)){
						that.groupAll.forEach((val,k) => {
							if (val.id == res.info.groupid) {
								that.groupidIndex = k;
								that.groupid = val.id
							}
						});
					}
					if(res.info.mdid){
						that.mendianAll.forEach((value,key) => {
							if (value.id == res.info.mdid) {
								that.mendianIndex = key;
								that.mendianid = value.id
							}
						});
					}
					
					that.processMenuData(that.menudata);
					that.processMobileMenu(that.mobilemenu);
					uni.setNavigationBarTitle({
						title: '编辑账号'
					});
				}
				that.loading = false;
				that.loaded();
			});
		},

    subform: function (e) {
      var that = this;
      var formdata = e.detail.value;
			formdata['id'] = '';
			if(!app.isNull(that.info)){
				formdata['id'] = that.info.id;
			}
			
			if(!formdata.un) return app.error('请填写登录账号');
			if(!formdata.pwd && !formdata.id) return app.error('请填写登录密码');
			
			formdata['mdid'] = that.mendianid;
			formdata['groupid'] = that.groupid;
			formdata['auth_data'] = that.authdata;
			formdata['mobile_auth'] = that.mobileauth;
      app.post('ApiAdminUser/addUser', {formdata}, function (res) {
        if (res.status == 0) {
          app.error(res.msg);
        } else {
          app.success(res.msg);
          setTimeout(function () {
						app.goto('list','reLaunch');
          }, 500);
        }
      });
    },
		groupChange:function(e){
			var that = this; 
			let value = e.detail.value;
			that.groupidIndex = value;
			that.groupid = that.groupAll[value].id;
		},
		mendianChange:function(e){
			var that = this; 
			let value = e.detail.value;
			that.mendianIndex = value;
			that.mendianid = that.mendianAll[value].id;
		},
		showMenu:function(){
			this.$refs.tkitree._show();
		},
		treeConfirm:function(e){
			let data = [];
			e.forEach((k,v)=>{
				if(k.authdata){
					data.push(k.path+','+k.authdata);
				}
			})
			this.authdata = data;
		},
		mobileConfirm:function(e){
			var that = this;
			that.mobileselect = 0;
			that.mobileauth = {};
			let field = {};
			for (let i = 0; i < e.length; i++) {
				let item = e[i];
				if(item.id == 0){
					continue; //全选
				}
				if(item.field){
					field[item.id] = item.field;
					if(!that.mobileauth[item.field]){
						that.mobileauth[item.field] = [];
					}
					continue;
				}
				let pid = item.parents[item.parents.length - 1].id;
				let f = field[pid];
				if (item.authdata && !that.mobileauth[f].includes(item.authdata)) {
					that.mobileselect += 1;
					that.mobileauth[f].push(item.authdata);
				}
			}
		},
		showMobileMenu:function(){
			this.$refs.mobiletree._show();
		},
		processMenuData(menuData) {
			menuData.forEach(item => {
				if (item.checked && item.path && item.authdata) {
					this.authdata.push(item.path+','+item.authdata); 
				}
				if (item.children && item.children.length > 0) {
					this.processMenuData(item.children);
				}
			});
		},
		processMobileMenu(menuData,field=''){
			menuData.forEach(item => {
				if (item.checked && item.authdata) {
					this.mobileselect += 1;
					if(!this.mobileauth[field]){
						this.mobileauth[field] = [];
					}
					this.mobileauth[field].push(item.authdata);
				}
				if (item.children && item.children.length > 0) {
					this.processMobileMenu(item.children,item.field);
				}
			});
		}
  }
};
</script>
<style>
.picker-class{width: 300rpx;}
.picker-class .uni-input{text-align:right}
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
.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}
.menu-title{line-height: 100rpx;display: flex;justify-content: space-between;}
.checkbox-group{display: flex;flex-wrap: wrap}
.first-label{display: block;margin-top: 15rpx;}
.second-label,.third-label{width: 50%;padding:10rpx 0}
.second-item,.third-item{margin-left:50rpx;}
.radio-group-view{display: flex;align-items: center;flex-wrap: wrap;justify-content: flex-start;}
.radio-group-view label{white-space: nowrap;margin-left: 40rpx;}
</style>