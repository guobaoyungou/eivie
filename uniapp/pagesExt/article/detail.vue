<template>
<view>
  <block v-if="!is_gather">
    <block v-if="isload">
    	<view class="container">
    		<view class="header">
    			<text class="title" v-if="detail.showname==1">{{detail.name}}</text>
					<text class="subtitle" v-if="subtitle_status">{{detail.subtitle}}</text>
    			<view class="artinfo" v-if="detail.showsendtime==1 || detail.showauthor==1 || detail.showreadcount==1">
    				<text class="t1" v-if="detail.showsendtime==1">{{detail.createtime}}</text>
    				<text class="t2" v-if="detail.showauthor==1">{{detail.author}}</text>
    				<text class="t3" v-if="detail.showreadcount==1">阅读：{{detail.readcount}}</text>
    			</view>
    			<view style="padding:8rpx 0">
    				<dp :pagecontent="pagecontent" :richtype="richtype" :richurl="richurl"></dp>
    			</view>
          <block v-if="reward">
              <view @tap='changeReward' style="width: 360rpx;background-color: #FA5151;border-radius: 12rpx;color: #fff;margin: 40rpx auto;line-height: 80rpx;">
                  <view style="display: flex;width: 160rpx;margin: 0 auto;">
                      <image :src='pre_url+"/static/img/dianzan.png"' style='width: 32rpx;height: 32rpx;margin-top: 20rpx;margin-right: 10rpx;'></image>
                      打赏作者
                  </view>
              </view>
              <view style="width: 360rpx;margin: 0 auto;overflow: hidden;text-align:center;line-height: 80rpx;">
                  <view style="width:80rpx ;float: left;border-top: 2rpx solid #E5E5E5;margin-top: 40rpx;"></view>
                  <view style="width: 200rpx;font-size: 24rpx;float: left;color:#B2B2B2;">
                      <text  v-if="detail.reward_num>1000" style="color: #536084;">1000+</text>
                      <text v-else style="color: #536084;">{{detail.reward_num}}</text>
                      <text>人打赏</text>
                  </view>
                  <view style="width:80rpx ;float: right;border-top: 2rpx solid #E5E5E5;margin-top: 40rpx;"></view>
              </view>
              <view v-if="detail.reward_log" style="width: 640rpx;margin:0rpx auto;overflow: hidden;margin-bottom: 40rpx;">
                  <block v-for="(item,index) in detail.reward_log">
                      <image :src="item" style="width: 70rpx;height: 70rpx;margin-top: 20rpx;margin-right: 10rpx;"></image>
                  </block>
              </view>
          </block>
    		</view>
    		<!--资源-->
    		<view class="zybox" v-if="detail.fujian_list && detail.fujian_list.length > 0">
    			<view class="zy_title">资源明细<text class="zy_tip">获取后可预览</text></view>
    			<view class="zy_list" v-for="(item,index) in detail.fujian_list" :data-type="item.type" :data-url = "item.url" @click="openFile">
    				<view class="zy_left  flex-y-center ">
    					<image v-if="item.type =='pdf'" class="image zy_image" :src="pre_url + '/static/img/article/pdf.png'" />
    					<image v-else-if="item.type =='xlsx' ||item.type =='xls' " class="image zy_image" :src="pre_url + '/static/img/article/excel.png'" />
    					<image v-else-if="item.type =='doc' ||item.type =='docx' " class="image zy_image" :src="pre_url + '/static/img/article/word.png'" />
    					<image v-else-if="item.type =='ppt' ||item.type =='pptx' " class="image zy_image" :src="pre_url + '/static/img/article/ppt.png'" />
    					<image v-else-if="item.type =='mp4' " class="image zy_image" :src="pre_url + '/static/img/article/mp4.png'" />
    					<image v-else-if="item.type =='mp3' " class="image zy_image" :src="pre_url + '/static/img/article/mp3.png'" />
    					<image v-else-if="item.type =='zip' ||item.type =='rar' ||item.type =='7z' " class="image zy_image" :src="pre_url + '/static/img/article/zip.png'" />
    					<image v-else class="image zy_image" :src="pre_url + '/static/img/article/png.png'" />		
    				</view>
    				<view class="zy_content flex-y-center">
    					{{item.name}}
    				</view>
    				<view class="flex-y-center f1" style="justify-content: center; padding: 0 10rpx;">
    					<image v-if="detail.is_look_resource=='0' || detail.is_have=='0'"  class="image suo_image" :src="pre_url + '/static/img/article/suo.png'" />
    					<button v-else class="btn_yl"><text v-if="item.type =='zip' ||item.type =='rar' ||item.type =='7z'">下载</text><text v-else>预览</text></button>
    				</view>
    			</view>
    		</view>
    		<!--评论-->
    		<block v-if="detail.canpl==1">
    		<view class="plbox">
    			<view class="plbox_title"><text class="t1">评论</text><text>({{plcount}})</text></view>
    			<view class="plbox_content">
    				<block v-for="(item, idx) in datalist" :key="item.id">
    				<view class="item1 flex">
    					<view class="f1 flex0"><image :src="item.headimg"></image></view>
    					<view class="f2 flex-col">
    						<text class="t1">{{item.nickname}}</text>
    						<view class="t2 plcontent"><parse :content="item.content" /></view>
    						<block v-if="item.replylist.length>0">
    						<view class="relist">
    							<block v-for="(hfitem, index) in item.replylist" :key="index">
    							<view class="item2">
    								<view>{{hfitem.nickname}}：</view>
    								<view class="f2 plcontent"><parse :content="hfitem.content" /></view>
    							</view>
    							 </block>
    						</view>
    						</block>
    						<view class="t3 flex">
    							<text>{{item.createtime}}</text>
    							<view class="flex1"><text v-if="detail.canplrp==1" class="phuifu" style="cursor:pointer" @tap="goto" :data-url="'pinglun?type=1&id=' + detail.id + '&hfid=' + item.id">回复</text></view>
    							<view class="flex-y-center pzan" @tap="pzan" :data-id="item.id" :data-index="idx"><image :src="pre_url+'/static/img/zan-' + (item.iszan==1?'2':'1') + '.png'"></image>{{item.zan}}</view>
    						</view>
    					</view>
    				</view>
    				</block>
    			</view>
    			<!-- <nodata v-if="nodata"></nodata> -->
    			<!-- <nomore v-if="nomore"></nomore> -->
    			<loading v-if="loading"></loading>
    		</view>
    		<view style="height:160rpx"></view>
    		<view class="pinglun" :class="menuindex>-1?'tabbarbot-scoped':'notabbarbot'">
    			<view class="pinput" @tap="goto" :data-url="'pinglun?type=0&id=' + detail.id">发表评论</view>
    			<view class="zan flex-y-center" @tap="zan" :data-id="detail.id">
    				<image :src="pre_url+'/static/img/zan-' + (iszan?'2':'1') + '.png'"/><text style="padding-left:2px">{{detail.zan}}</text>
    			</view>
    			<block v-if="detail.btntxt && detail.btnurl">
    				<view class="buybtn" style="cursor:pointer" :onclick="'location.href=' + detail.btnurl">{{detail.btntxt}}</view>
    			</block>
    			<view v-if="detail.fujian_list && detail.fujian_list.length > 0">
    				<view v-if="detail.is_have =='0'"  class="tobuy flex-x-center flex-y-center" :style="{background:t('color1')}" @tap="getResource">
    				    获取资源
    				</view>
    				<view v-else  class="tobuy flex-x-center flex-y-center" :style="{background:t('color2')}"  @tap="toRecord" >
    				    已获取·去下载
    				</view>
    			</view>
    			
    		</view>
    		</block>
    	</view>
    </block>
    <block v-if="reward && openreward">
        <view @tap="changeReward" style="background-color: #000;opacity: 0.4;width: 100%;height: 100%;position: fixed;top: 0;z-index: 996;"></view>
        <view style="position: fixed;width: 690rpx;left: 30rpx;top:30%;background-color: #fff;z-index: 997;border-radius: 8rpx;">
            <view style="overflow: hidden;padding: 30rpx 30rpx 0;">
                <text>打赏作者</text>
                <image @tap="changeReward" :src="pre_url+'/static/img/close.png'" style="float: right;width: 30rpx;height: 30rpx;"></image>
            </view>
            <view v-if="reward_num_type == 1" style="padding: 0 30rpx 30rpx;">
                <view style="width: 300rpx;text-align: center;margin: 0 auto;overflow: hidden;line-height: 70rpx;margin-top: 20rpx;">
                    <view @tap="changeRewardtype" data-type="1" class="reward_typel" :style="reward_type == 1?'background-color: #FA5151;color: #fff;':'background-color: #f3f3f3;color: #000;'">
                        金额
                    </view>
                    <view @tap="changeRewardtype" data-type="2" class="reward_typer" :style="reward_type == 2?'background-color: #FA5151;color: #fff;':'background-color: #f3f3f3;color: #000;'">
                        {{t('积分')}}
                    </view>
                </view>
                <view v-if="reward_data" style="width: 580rpx;overflow: hidden;text-align: center;margin: 0 auto;line-height: 80rpx;color: #FA5151;font-size: 30rpx;">
                    <block v-if="reward_type == 1 && reward_data.money_data">
                        <view  v-for="(item,index) in reward_data.money_data" :class="reward_num == item?'reward_content reward_num':'reward_content'" :style="(index+1)%3 ==0?'':'margin-right: 20rpx;'" >
                            <view @tap="selRewardnum" :data-num="item">
                                ￥{{item}}
                            </view>
                        </view>
                    </block>
                    <block v-if="reward_type == 2 && reward_data.score_data">
                        <view  v-for="(item,index) in reward_data.score_data" :class="reward_num == item?'reward_content reward_num':'reward_content'" :style="(index+1)%3 ==0?'':'margin-right: 20rpx;'">
                            <view  @tap="selRewardnum" :data-num="item">
                                {{item}}{{t('积分')}}
                            </view>
                        </view>
                    </block>
                </view>
                <view @tap="changeRewardNumType" data-type="1" v-if="reward_type == 1" class="reward_num_type">其他金额</view>
                <view @tap="changeRewardNumType" data-type="1" v-if="reward_type == 2" class="reward_num_type">其他{{t('积分')}}</view>
            </view>
            <view v-if="reward_num_type == 2">
                <view>
                    <view style="width: 250rpx;margin: 0 auto;font-size: 40rpx;font-weight: bold;line-height: 80rpx;display: flex;text-align: center;margin: 40rpx auto;">
                        <text v-if="reward_type == 1">￥</text>
                        <input v-model="reward_num" @input="inputRewardnum"  placeholder="0(整数)" placeholder-style="line-height: 80rpx;" style="height: 80rpx;line-height:80rpx;display: inline-block;width: 170rpx;border-bottom: 2rpx solid #eee;"/>
                        <text v-if="reward_type == 2" style="font-size: 36rpx;">{{t('积分')}}</text>
                    </view>
                    <view @tap="changeRewardNumType" data-type="2" v-if="reward_type == 1" class="reward_num_type">固定金额</view>
                    <view @tap="changeRewardNumType" data-type="2" v-if="reward_type == 2" class="reward_num_type">固定{{t('积分')}}</view>
                </view>
                
                <view @tap="postReward" style="width: 100%;line-height: 80rpx;text-align: center;border-top: 2rpx solid #eee;font-size: 30rpx;color: #FA5151;">
                    确定
                </view>
            </view>
        </view>
      </block>
    <loading v-if="loading"></loading>
  </block>
  <block v-else>
    <web-view :src="pre_url +'/h5/'+aid+'.html#/pagesExt/article/detail?id='+opt.id+'&pid=' + mid"></web-view>
  </block>
  <view class="countdown-content" v-if="countdownShow && countdownTime">
    <view class="count-main">
      <view class="bg-view"></view>
      <progress active :duration="countdownTime" percent="100" stroke-width="6" activeColor="#feea17" backgroundColor="#792908" @activeend="endCountdown" />
      <view class="text-view">
        <text>浏览{{readTime}}秒</text>
        <text>获得奖励</text>
      </view>
    </view>
  </view>
  <dp-tabbar :opt="opt" @getmenuindex="getmenuindex"></dp-tabbar>
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
      aid:app.globalData.aid,
      mid:app.globalData.mid,
      detail:[],
      datalist: [],
      pagenum: 1,
      id: 0,
      pagecontent: "",
      title: "",
      sharepic: "",
      nodata:false,
      nomore:false,
      iszan: "",
      plcount:0,
      
      reward:false,
      reward_data:'',
      openreward:false,
      reward_type:1,
      reward_num:0,
      reward_num_type:1,
      
      richtype:0,
      richurl:'',
      is_gather:false,
      countdownShow:false,
      readTime: 0, //观看时间
      countdownTime: 0, //进度条倒计时
      timeout:null,//定时器
			subtitle_status:false
    };
  },
  onLoad: function (opt) {
		this.opt = app.getopts(opt);
    this.readTime = 0;
		this.getdata();
  },
	  onShow:function() {
			this.setSeo();
	  },
	onPullDownRefresh: function () {
		this.getdata();
	},
	onShareAppMessage:function(){
		var that = this;
		return this._sharewx({title:this.detail.name,desc:this.detail.subname,pic:this.detail.pic,callback: function() {
					that.sharecallback();
				}});
	},
	onShareTimeline:function(){
		var that = this;
		var sharewxdata = this._sharewx({title:this.detail.name,desc:this.detail.subname,pic:this.detail.pic,
			callback: function() {
					that.sharecallback();
				}});
		var query = (sharewxdata.path).split('?')[1]+'&seetype=circle';
		return {
			title: sharewxdata.title,
			imageUrl: sharewxdata.imageUrl,
			query: query
		}
	},
  onReachBottom: function () {
    if (!this.nodata && !this.nomore && this.detail.canpl==1) {
      this.pagenum = this.pagenum + 1
      this.getpllist();
    }
  },
  beforeDestroy() {
    this.clearCountdown();
  },
    onPageScroll: function (e) {
        uni.$emit('onPageScroll',e);
    },
  methods: {
	sharecallback: function() {
	app.post("ApiArticle/giveScorenum",{}, function(res) {
	});	
	},
	getdata:function(){
			var that = this;
			var id = that.opt.id;
			that.loading = true;
			app.get('ApiArticle/detail', {id: id}, function (res) {
				that.loading = false;
				if (res.status == 1){
					that.detail = res.detail;
					if(res.detail.is_gather){
					//#ifdef MP-WEIXIN
					that.is_gather = res.detail.is_gather;
					// #endif
					}
					that.pagecontent = res.pagecontent;
					that.plcount = res.plcount;
					that.iszan = res.iszan;
					that.title = res.detail.name;
					that.sharepic = res.detail.pic;
					if(res.reward){
					  that.reward      = res.reward;
					  that.reward_data = res.reward_data;
					}
					if(res.richtype){
						that.richtype = res.richtype;
					}
					if(res.richurl){
						that.richurl  = res.richurl
					}
					that.subtitle_status = res.subtitle_status
					
					if(res.detail && res.detail.read_time){
						that.readTime = res.detail.read_time;
						that.startCountdown();
					}
					uni.setNavigationBarTitle({
						title: res.detail.name
					});
					// #ifdef MP-BAIDU
					if(that.detail.keywords){
						that.setSeo();
					}
					// #endif
				} else {
					app.alert(res.msg);
				}
				that.pagenum = 1;
				that.datalist = [];
				that.getpllist();
				that.loaded({title:res.detail.name,desc:res.detail.subname,pic:res.detail.pic,callback: function() {
					that.sharecallback();
				}});
			});
		},
		
	getpllist: function () {
        var that = this;
        var pagenum = that.pagenum;
			that.loading = true;
			that.nodata = false;
			that.nomore = false;
        app.post('ApiArticle/getpllist', {pagenum: pagenum,id: that.detail.id}, function (res) {
				that.loading = false;
            var data = res.data;
            if (data.length == 0) {
                if(pagenum == 1){
                    that.nodata = true;
                }else{
                    that.nomore = true;
                }
            }
            var datalist = that.datalist;
            var newdata = datalist.concat(data);
            that.datalist = newdata;
        });
    },
    zan: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      app.post("ApiArticle/zan", {id: id}, function (res) {
        if (res.type == 0) {
          //取消点赞
          var iszan = 0;
        } else {
          var iszan = 1;
        }
        that.iszan = iszan;
        that.detail.zan = res.zancount;
      });
    },
    pzan: function (e) {
      var that = this;
      var id = e.currentTarget.dataset.id;
      var index = e.currentTarget.dataset.index;
      var datalist = that.datalist;
      app.post("ApiArticle/pzan", {id: id}, function (res) {
        if (res.type == 0) {
          //取消点赞
          var iszan = 0;
        } else {
          var iszan = 1;
        }

        datalist[index].iszan = iszan;
        datalist[index].zan = res.zancount;
        that.datalist = datalist;
      });
    },
    changeReward:function(){
        var that = this;
        that.openreward      = !that.openreward;
        that.reward_type     = 1;
        that.reward_num_type = 1;
        that.reward_num      = '';
    },
    changeRewardtype:function(e){
        var that = this;
        var type         = e.currentTarget.dataset.type;
        that.reward_type = type;
        that.reward_num  = '';
    },
    changeRewardNumType:function(e){
        var that = this;
        var reward_num_type = that.reward_num_type;
        if(reward_num_type == 1){
            that.reward_num_type = 2;
        }else{
            that.reward_num_type = 1;
        }
        that.reward_num  = '';
    },
    inputRewardnum:function(e){
        var that = this;
        var reward_type = that.reward_type;
        var num  = e.detail.value;
        var index = num.indexOf('.');
        if(reward_type == 2){
            if(index>=0){
                // app.alert(that.t('积分')+'必须为整数');
                // return;
                num = parseInt(num);
            }
        }else{
            if(index>=0){
                var slice_index = index+1;
                var afternum = num.slice(slice_index);
                var len = afternum.length;
                if(len>2){
                    num = parseInt(num*100)/100;
                }
            }
        }
        setTimeout(function(){
            console.log(num);
            that.reward_num = num;
        },0)
        //that.$set(that, 'reward_num', num);
    },
    selRewardnum:function(e){
        var that = this;
        var reward_type = that.reward_type;
        var num  = e.currentTarget.dataset.num;
        that.reward_num = num;
        that.postReward();
    },
    postReward:function(){
        var that = this;
        var reward_type = that.reward_type;
        var reward_num  = that.reward_num;
        if(reward_type == 1){
            var msg = '确定打赏'+reward_num+'元吗？';
        }else{
            var msg = '确定打赏'+reward_num+that.t('积分')+'吗？';
        }
        app.confirm(msg,function(){
            var data = {
                id          : that.opt.id,
                reward_type : reward_type,
                reward_num  : reward_num
            }
            app.post('ApiArticle/reward',data,function(res){
                if(res.status == 1){
                    if(reward_type == 2){
                        app.alert(res.msg);
                        setTimeout(function(){
                            that.getdata();
                            that.changeReward();
                        },800)
                    }else{
                        app.goto('/pagesExt/pay/pay?id=' + res.payorderid);
                    }
                }else{
                    app.alert(res.msg);
                }
            })
        })
    },
	openFile(e){
		var that = this;
		var file   = e.currentTarget.dataset.url;
		var type  = e.currentTarget.dataset.type;
		if(that.detail.is_look_resource =='0'){
			app.alert('请升级会员后查看');
			return;
		}
		if(that.detail.is_have =='0'){
			app.alert('请先获取资源');
			return;
		}
		if(file ==''){
			app.alert('打开文件失败');
			return;
		}
		that.loading = true;
		var pngtype = ['png','jpg','gif','jepg','webp'];
		if(pngtype.indexOf(type) !== -1 || type =='mp4' || type =='mp3'){
			var showtype= type =='mp4'?'mp4':type =='mp3' ?'mp3':'png';
			that.loading = false;
			console.log(that.detail.is_download_resource,'------');
			app.goto('/pagesExt/article/show?type='+showtype+'&url='+encodeURIComponent(file)+'&auth='+that.detail.is_download_resource);
		}
		const filetype = ['pptx', 'ppt', 'docx', 'doc', 'xlsx', 'xls', 'pdf']
		if(filetype.indexOf(type) !== -1){
			// #ifdef H5
			    window.location.href= file;
			// #endif
			
			// #ifdef MP-WEIXIN
			uni.downloadFile({
				url: file, 
				success: (res) => {
					that.loading = false;
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
		}
		const ziptype = ['zip', 'rar','7z']
		if(ziptype.indexOf(type) !== -1){
			// #ifdef H5
			    window.location.href= file;
			// #endif
			
			// #ifdef MP-WEIXIN
			const downloadTask = uni.downloadFile({
				url: file, 
				success: (res) => {
					that.loading = false;
			        var filePath = res.tempFilePath;
					if (res.statusCode === 200) {
						 
					}
					uni.saveFile({
						tempFilePath: res.tempFilePath,
						success: function(red) {
							 app.success('文件已保存：'+ red.savedFilePath);
						}
					});

				}
			});
			// #endif
		}
		
	},
	getResource(){
		var that = this;
		if(that.detail.is_look_resource =='0'){
			app.alert('请升级会员后获取');
			return;
		}
		that.loading = true;
		app.post('ApiArticle/getResourceSave',{id:that.detail.id},function(res){
		    if(res.status == 1){
		        that.loading = false;
				app.success('成功获取');
				setTimeout(function () {
					app.goto('/pagesExt/article/record');
				}, 1000);
		    }else{
		        app.alert(res.msg);
		    }
		})
	},
	toRecord(){
		var url = '/pagesExt/article/record';
		app.goto(url);
	},
	setSeo(){
		var that =this;
		// #ifdef MP-BAIDU
		if(that.detail.keywords){
			swan.setPageInfo({
				title: that.detail.name,
				keywords: that.detail.keywords,
				description: that.detail.subname
			});
		}
		// #endif
	},
  startCountdown() {
    this.countdownShow = true;
    // 进度条为100%，duration 设置动画的快慢，进度增加 百分之一 需要的毫秒数，
    // 1秒是1000毫秒 
    this.countdownTime = this.readTime * 10; // 使用时间
    // #ifdef H5 || APP-PLUS
    // 清除上一个计时器
    if (this.timeout) {
        clearTimeout(this.timeout);
    }
    
    this.timeout = setTimeout(() => {
      this.endCountdown();
    }, this.readTime*1000)
    // #endif
  },
  endCountdown: function() {
    var that = this;
    app.post('ApiArticle/countdownEnd', {id: that.opt.id}, function (res) {
      if(res.status == 1){
        uni.showToast({
          title: res.msg
        });
      }else{
        app.alert(res.msg);
      }
    });
    that.countdownShow = false;
    that.clearCountdown();
  }, 
  clearCountdown() {
      if (this.timeout) {
        clearTimeout(this.timeout);
        this.timeout = null;
      }
    }
  }
};
</script>
<style>
.tabbarbot-scoped{bottom:calc(110rpx +  env(safe-area-inset-bottom)) !important;}
.header{ background-color: #fff;padding: 10rpx 20rpx 0 20rpx;position: relative;display:flex;flex-direction:column;}
.header .title{width:100%;font-size: 36rpx;color:#333;line-height: 1.4;margin:10rpx 0;margin-top:20rpx;font-weight:bold}
.header .subtitle{font-size: 30rpx;}
.header .artinfo{width:100%;font-size:28rpx;color: #8c8c8c;font-style: normal;overflow: hidden;display:flex;margin:10rpx 0;}
.header .artinfo .t1{padding-right:8rpx}
.header .artinfo .t2{color:#777;padding-right:8rpx}
.header .artinfo .t3{text-align:right;flex:1;}
.header .subname{width:100%;font-size:28rpx;color: #888;border:1px dotted #ddd;border-radius:10rpx;margin:10rpx 0;padding:10rpx}


.pinglun{ width:96%;max-width:750px;margin:0 auto;position:fixed;display:flex;align-items:center;bottom:0;left:0;right:0;height:100rpx;background:#fff;z-index:10;border-top:1px solid #f7f7f7;padding:0 2%;box-sizing:content-box}
.pinglun .pinput{flex:1;color:#a5adb5;font-size:32rpx;padding:0;line-height:100rpx}
.pinglun .zan{padding:0 12rpx;line-height:100rpx}
.pinglun .zan image{width:48rpx;height:48rpx}
.pinglun .zan span{height:40rpx;line-height:50rpx;font-size:32rpx}
.pinglun .buybtn{margin-left:0.08rpx;background:#31C88E;height:72rpx;line-height:72rpx;padding:0 20rpx;color:#fff;border-radius:6rpx}

.plbox{width:100%;padding:40rpx 20rpx;background:#fff;margin-top:10px}
.plbox_title{font-size:28rpx;height:60rpx;line-height:60rpx;margin-bottom:20rpx}
.plbox_title .t1{color:#000;font-weight:bold}
.plbox_content .plcontent{vertical-align: middle;color:#111}
.plbox_content .plcontent image{ width:44rpx;height:44rpx;vertical-align: inherit;}
.plbox_content .item1{width:100%;margin-bottom:20rpx}
.plbox_content .item1 .f1{width:80rpx;}
.plbox_content .item1 .f1 image{width:60rpx;height:60rpx;border-radius:50%}
.plbox_content .item1 .f2{flex:1}
.plbox_content .item1 .f2 .t1{}
.plbox_content .item1 .f2 .t2{color:#000;margin:10rpx 0;line-height:60rpx;}
.plbox_content .item1 .f2 .t3{color:#999;font-size:20rpx}
.plbox_content .item1 .f2 .pzan image{width:32rpx;height:32rpx;margin-right:2px}
.plbox_content .item1 .f2 .phuifu{margin-left:6px;color:#507DAF}
.plbox_content .relist{width:100%;background:#f5f5f5;padding:4rpx 20rpx;margin-bottom:20rpx}
.plbox_content .relist .item2{font-size:24rpx;margin-bottom:10rpx}
.copyright{display:none}
.reward_typel{width: 150rpx;display: inline-block;border-radius: 8rpx 0 0 8rpx;}
.reward_typer{width: 150rpx;display: inline-block;border-radius: 0rpx 8rpx 8rpx 0rpx;}
.reward_content{width: 180rpx;border-radius: 8rpx;border: 2rpx solid #FA5151;float: left;margin-top: 20rpx;overflow: hidden;white-space: nowrap;}
.reward_num{background-color:#FA5151;color:#fff}
.reward_num_type{width:200rpx;margin:20rpx auto;height:60rpx;line-height: 60rpx;text-align: center;color: #536084;overflow: hidden;}
.zybox{background: #fff;width: 100%;padding: 0rpx 20rpx 20rpx 20rpx;margin-top: 25rpx;}
.zy_title{font-size: 34rpx;padding: 30rpx 0;}
.zy_title .zy_tip{font-size: 28rpx; color: #C0C0C0;margin-left: 20rpx;}
.zy_list{display: flex;height: 100rpx;border-bottom: 1px solid #EEEEEE;}
.zy_left{flex: 1;}
.zy_list .zy_image { width: 60rpx;height: 60rpx;}
.zy_list .zy_content{font-size: 26rpx;flex: 7;word-break: break-all}
.tobuy{height: 60rpx;line-height: 60rpx;color: #FFFFFF;border-radius: 32rpx;margin-left: 20rpx;flex-shrink: 0;padding: 0 50rpx;font-size: 24rpx; font-weight: bold;}
.btn_yl{height: 37rpx;line-height: 33rpx;color: #03a9f4;border: 1px solid #03a9f4;border-radius: 32rpx;padding: 0 15rpx;font-size: 24rpx;}
.suo_image{ width: 40rpx; height: 40rpx;flex: 1;}
.countdown-content .count-main{position:fixed;right:50rpx;bottom:300rpx;z-index:9999;width:100rpx;height:140rpx}
.countdown-content .text-view{width:100%;height:60rpx;color:#fff;font-size:24rpx;display:flex;flex-direction:column;align-items:center;justify-content:center;background:rgba(0,0,0,.6);border-radius:0rpx 0rpx 8rpx 8rpx}
.countdown-content .text-view text{transform:scale(0.7);white-space:nowrap;line-height:1}
</style>