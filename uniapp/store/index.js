// #ifdef APP-PLUS
import Vue from 'vue'
import Vuex from 'vuex'
Vue.use(Vuex);
const store = new Vuex.Store({
	state:{
		WRITE_EXTERNAL_STORAGE: false,
		READ_EXTERNAL_STORAGE: false,
		CALL_PHONE: false,
		READ_PHONE_STATE:false,
		CAMERA:false,
		ACCESS_FINE_LOCATION:false,
		ACCESS_COARSE_LOCATION:false,
		ACCESS_COARSE_LOCATION:false,
		/* #ifdef APP-PLUS */
		isIos: plus.os.name == "iOS",
		/* #endif */
		mapping: {
		    'WRITE_EXTERNAL_STORAGE': {
		      title: "存储空间/照片权限说明",
		      content: "便于您使用该功能上传您的照片/图片/视频及用于更换头像、发布评论/分享、下载、与客服沟通等场景中读取和写入相册和文件内容。",
		      methods: 'SET_WRITE_EXTERNAL_STORAGE'
		    },
		    'READ_EXTERNAL_STORAGE': {
		      title: "存储空间/照片权限说明",
		      content: "便于您使用该功能上传您的照片/图片/视频及用于更换头像、发布评论/分享、下载、与客服沟通等场景中读取和写入相册和文件内容。",
		      methods: 'SET_READ_EXTERNAL_STORAGE'
		    },
		    'CALL_PHONE': {
		      title: "拨打/管理电话权限说明",
		      content: "便于您使用该功能联系商家或者商家与您联系等场景",
		      methods: 'SET_CALL_PHONE'
		    },
				'READ_PHONE_STATE':{
					title: "拨打/管理电话权限说明",
					content: "便于您使用该功能联系商家或者商家与您联系等场景",
					methods: 'SET_READ_PHONE_STATE'
				},
				'CAMERA':{
					title: "摄像头权限说明",
					content: "便于您使用该功能摄像头进行拍摄图片作为用户头像及商品评价等场景",
					methods: 'SET_CAMERA'
				},
				'ACCESS_FINE_LOCATION':{
					title: "GPS接收定位信息权限说明",
					content: "便于您使用该功能获取当前位置用于展示距离等场景",
					methods: 'SET_ACCESS_FINE_LOCATION'
				},
				'ACCESS_COARSE_LOCATION':{
					title: "用户经纬度信息权限说明",
					content: "便于您使用该功能获取当前位置用于展示距离等场景",
					methods: 'SET_ACCESS_COARSE_LOCATION'
				}
		}
	},
	mutations:{
		SET_WRITE_EXTERNAL_STORAGE(state, val) {
		    state.WRITE_EXTERNAL_STORAGE = val
		},
		SET_CALL_PHONE(state, val) {
		    state.CALL_PHONE = val
		},
		SET_READ_EXTERNAL_STORAGE(state, val) {
		    state.READ_EXTERNAL_STORAGE = val
		},
		SET_READ_PHONE_STATE(state, val) {
		    state.READ_PHONE_STATE = val
		},
		SET_CAMERA(state, val) {
		    state.CAMERA = val
		},
		SET_ACCESS_FINE_LOCATION(state, val) {
		    state.ACCESS_FINE_LOCATION = val
		},
		SET_ACCESS_COARSE_LOCATION(state, val) {
		    state.ACCESS_COARSE_LOCATION = val
		},
	},
	actions:{
		  //权限获取
		  async requestPermissions({
		      state,
		      dispatch,
		      commit
		  }, permissionID) {
		      try {
						let viewObj = null;
						viewObj = await dispatch('nativeObjView', permissionID);
						let kjj = plus.navigator.checkPermission('android.permission.' + permissionID)
		          if (kjj == 'undetermined' && !state[permissionID] && !state.isIos) {
		              viewObj.show();
		          }
		          console.log('android.permission.' + permissionID, '当前手机权限');
		          return new Promise(async (resolve, reject) => {
		               //苹果不需要这个
		              if(state.isIos){
		                  resolve(1);
		                  return
		              }
		              // Android权限查询
		              function requestAndroidPermission(permissionID_) {
		                  return new Promise((resolve, reject) => {
		                      plus.android.requestPermissions(
		                          [
		                              permissionID_
		                          ], // 理论上支持多个权限同时查询，但实际上本函数封装只处理了一个权限的情况。有需要的可自行扩展封装
		                          function (resultObj) {
		                              var result = 0;
		                              for (var i = 0; i < resultObj.granted.length; i++) {
		                                  var grantedPermission = resultObj.granted[i];
		                                  console.log('已获取的权限：' + grantedPermission);
		                                  result = 1;
																			if (viewObj) viewObj.close();
																			viewObj = null;
		                              }
		                              for (var i = 0; i < resultObj.deniedPresent
		                                  .length; i++) {
		                                  var deniedPresentPermission = resultObj
		                                      .deniedPresent[
		                                      i];
		                                  console.log('拒绝本次申请的权限：' + deniedPresentPermission);
		                                  result = 0;
																			if (viewObj) viewObj.close();
																			viewObj = null;
		                              }
		                              for (var i = 0; i < resultObj.deniedAlways
		                                  .length; i++) {
		                                  var deniedAlwaysPermission = resultObj.deniedAlways[
		                                      i];
		                                  console.log('永久拒绝申请的权限：' + deniedAlwaysPermission);
		                                  result = -1;
																			if (viewObj) viewObj.close();
																			viewObj = null;
		                              }
		                              resolve(result);
		                          },
		                          function (error) {
		                              console.log('申请权限错误：' + error.code + " = " + error
		                                  .message);
		                              resolve({
		                                  code: error.code,
		                                  message: error.message
		                              });
		                          }
		                      );
		                  });
		              }
		 
		              const result = await requestAndroidPermission(
		                  'android.permission.' + permissionID
		              );
		              if (result === 1) {
		                  //'已获得授权'
		                  commit(state.mapping[permissionID].methods, true)
		              } else if (result === 0) {
		                  //'未获得授权'
		                  commit(state.mapping[permissionID].methods, false)
		              } else {
		                  commit(state.mapping[permissionID].methods, true)
		                  uni.showModal({
		                      title: '提示',
		                      content: '操作权限已被拒绝，请手动前往设置',
		                      confirmText: "立即设置",
		                      success: (res) => {
		                          if (res.confirm) {
		                              dispatch('gotoAppPermissionSetting')
		                          }
		                      }
		                  })
		              }
		              resolve(result);
		          });
		      } catch (error) {
		          console.log(error);
		          reject(error);
		      }
		  },
		  //提示框
		  nativeObjView({
		      state
		  }, permissionID) {
		      const systemInfo = uni.getSystemInfoSync();
		      const statusBarHeight = systemInfo.statusBarHeight;
		      const navigationBarHeight = systemInfo.platform === 'android' ? 48 :
		          44; // Set the navigation bar height based on the platform
		      const totalHeight = statusBarHeight + navigationBarHeight;
		      let view = new plus.nativeObj.View('per-modal', {
		          top: '0px',
		          left: '0px',
		          width: '100%',
		          backgroundColor: '#444',
		          //opacity: .5;
		      })
		      view.drawRect({
		          color: '#fff',
		          radius: '5px'
		      }, {
		          top: totalHeight + 'px',
		          left: '5%',
		          width: '90%',
		          height: "100px",
		      })
		      view.drawText(state.mapping[permissionID].title, {
		          top: totalHeight + 5 + 'px',
		          left: "8%",
		          height: "30px"
		      }, {
		          align: "left",
		          color: "#000",
		      }, {
		          onClick: function (e) {
		              console.log(e);
		          }
		      })
		      view.drawText(state.mapping[permissionID].content, {
		          top: totalHeight + 35 + 'px',
		          height: "60px",
		          left: "8%",
		          width: "84%"
		      }, {
		          whiteSpace: 'normal',
		          size: "14px",
		          align: "left",
		          color: "#656563"
		      })
		 
		      function show() {
		          view = plus.nativeObj.View.getViewById('per-modal');
		          view.show()
		          view = null//展示的时候也得清空，不然影响下次的关闭，不知道为啥
		      }
		 
		      function close() {
		          view = plus.nativeObj.View.getViewById('per-modal');
		          view.close();
		          view = null
		      }
		      return {
		          show,
		          close
		      }
		  },
		 
		  // 跳转到**应用**的权限页面
		  gotoAppPermissionSetting({
		      state
		  }) {
		      if (state.isIos) {
		          var UIApplication = plus.ios.import("UIApplication");
		          var application2 = UIApplication.sharedApplication();
		          var NSURL2 = plus.ios.import("NSURL");
		          // var setting2 = NSURL2.URLWithString("prefs:root=LOCATION_SERVICES");		
		          var setting2 = NSURL2.URLWithString("app-settings:");
		          application2.openURL(setting2);
		 
		          plus.ios.deleteObject(setting2);
		          plus.ios.deleteObject(NSURL2);
		          plus.ios.deleteObject(application2);
		      } else {
		          var Intent = plus.android.importClass("android.content.Intent");
		          var Settings = plus.android.importClass("android.provider.Settings");
		          var Uri = plus.android.importClass("android.net.Uri");
		          var mainActivity = plus.android.runtimeMainActivity();
		          var intent = new Intent();
		          intent.setAction(Settings.ACTION_APPLICATION_DETAILS_SETTINGS);
		          var uri = Uri.fromParts("package", mainActivity.getPackageName(), null);
		          intent.setData(uri);
		          mainActivity.startActivity(intent);
		      }
		  }
	}
})
export default store
// #endif