<template>
	<view>
		<form @submit="subform">
		<view class="form-view flex flex-col">
			<view class="form-options flex flex-bt flex-y-center">
				<view class="options-title">标题</view>
				<view class="options-input">
					<input name="name" :value="info.name" type="text" placeholder-style="font-size:28rpx;color: #BFBFCB;" placeholder="请输入文章标题"/>
				</view>
			</view>
			<view class="form-options flex flex-bt flex-y-center">
				<view class="options-title">描述</view>
				<view class="options-input">
					<input name="subname" :value="info.subname" type="text" placeholder-style="font-size:28rpx;color: #BFBFCB;" placeholder="请输入文章描述"/>
				</view>
			</view>
			<view class="form-options flex flex-bt flex-y-center">
				<view class="options-title">作者</view>
				<view class="options-input">
					<input name="author" :value="info.author" type="text" placeholder-style="font-size:28rpx;color: #BFBFCB;" placeholder="请输入文章作者"/>
				</view>
			</view>
			<view class="form-options flex flex-bt flex-y-center">
				<view class="options-title">分类</view>
				<view class=""  @tap="changeClistDialog"><text v-if="cids.length>0" style="line-height: normal;">{{cnames}}</text><text v-else style="font-size:28rpx;color: #BFBFCB;">请选择</text></view>
			</view>
			<view class="form-options flex flex-bt flex-y-center">
				<view class="options-title">阅读量</view>
				<view class="options-input">
					<input name="readcount" :value="info.readcount" placeholder-style="font-size:28rpx;color: #BFBFCB;" placeholder="请输入文章阅读量"/>
				</view>
			</view>
			<view class="form-options flex flex-bt flex-y-center">
				<view class="options-title">序号</view>
				<view class="options-input">
					<input name="sort" :value="info.sort" placeholder-style="font-size:28rpx;color: #BFBFCB;" placeholder="请输入序号"/>
				</view>
			</view>
			<view class="form-options flex flex-bt flex-y-center">
				<view class="options-title">发布时间</view>
				<view class="options-input flex flex-bt flex-y-center">
					<view class="flex" style="flex:1;align-items: center;justify-content: flex-end;">
						<picker mode="date" @change="startTimeChange" class="picker-class flex" style="margin-right: 20rpx;">
								<view :class="startTime?'':'picker-view-class flex'">{{startTime ? startTime:'请选择日期'}}</view>
						</picker>
						<picker mode="time" @change="startTime2Change" class="picker-class flex">
								<view :class="startTime2?'':'picker-view-class flex'">{{startTime2 ? startTime2:'请选择时间'}}</view>
						</picker>
					</view>
					<image :src="`${pre_url}/static/img/arrowright.png`" class="options-icon"></image>
				</view>
			</view>
			<view class="form-options flex-col">
				<view class="options-title">详情页展示</view>
				<view class="options-sex-view flex flex-y-center flex-bt">
					<view @click='setzhanshi(0)' class='sex-optinos' :style="{background:(sexIndexArr.includes(0) ? (t('color1') || '#FD4A46'):''),color:(sexIndexArr.includes(0) ? '#fff':'')}">标题</view>
					<view @click='setzhanshi(1)' class='sex-optinos' :style="{background:(sexIndexArr.includes(1) ? (t('color1') || '#FD4A46'):''),color:(sexIndexArr.includes(1) ? '#fff':'')}">阅读量</view>
					<view @click='setzhanshi(2)' class='sex-optinos' :style="{background:(sexIndexArr.includes(2) ? (t('color1') || '#FD4A46'):''),color:(sexIndexArr.includes(2) ? '#fff':'')}">发布时间</view>
					<view @click='setzhanshi(3)' class='sex-optinos' :style="{background:(sexIndexArr.includes(3) ? (t('color1') || '#FD4A46'):''),color:(sexIndexArr.includes(3) ? '#fff':'')}">作者</view>
				</view>
			</view>
		</view>
		<view class="form-view flex flex-col" style="padding: 0rpx;">
			<view class="form-options flex flex-bt flex-y-center">
				<view class="options-title">文章评论</view>
				<view>
					<switch color="#3d5bf6" style="transform:scale(0.7)" value="1" :checked="canpl==1?true:false" @change="switchChange" />
				</view>
			</view> 
			<block v-if="canpl">
			<view class="form-options flex flex-bt flex-y-center">
				<view class="options-title">评论回复</view>
				<view>
					<switch color="#3d5bf6" style="transform:scale(0.7)" value="1" :checked="canplrp==1?true:false" @change="switchChange2" />
				</view>
			</view> 
			<view class="form-options flex flex-bt flex-y-center">
				<view class="options-title">评论审核</view>
				<view>
					<switch color="#3d5bf6" style="transform:scale(0.7)" value="1" :checked="pinglun_check==1?true:false" @change="switchChange3" />
				</view>
			</view> 
			</block>
			<view class="form-options flex flex-bt flex-y-center">
				<view class="options-title">状态</view>
				<view>
					<switch color="#3d5bf6" style="transform:scale(0.7)" value="1" :checked="status==1?true:false" @change="switchChange4" />
				</view>
			</view> 
		</view>
		<view class="form-view flex flex-col">
			<view class="form-options flex flex-col">
				<view class="options-title">图片</view>
				<view class="upload-pic-view flex flex-y-center">
					<view class="up-fun-view" @tap="uploadimg" data-field="pics" data-pernum="9" v-if="pics.length<1">
						<image :src="`${pre_url}/static/img/uploadimage.png`"></image>
					</view>
					<block v-for="(item,index) in pics" :key="index">
					<view class="up-fun-view">
						<image :src="item" @tap="previewImage" :data-url="item"></image>
						<view class="close-but" @tap="removeimg" :data-index="index" data-field="pics">
							<image :src="`${pre_url}/static/img/uploadimgdelete.png`"></image>
						</view>
					</view>
					</block>
				</view>
			</view> 
		</view>
		<view class="form-view flex flex-col" style="padding: 0rpx;">
		<view class="form-box">
			<view class="form-item flex-col">
				<text>文章详情</text>
				<view class="detailop"><view class="btn" @tap="detailAddtxt">+文本</view><view class="btn" @tap="detailAddpic">+图片</view></view>
				<view>
					<block v-for="(setData, index) in pagecontent" :key="index">
						<view class="detaildp">
						<view class="op">
							<view class="flex1"></view>
							<view class="btn" @tap="detailMoveup" :data-index="index">上移</view>
							<view class="btn" @tap="detailMovedown" :data-index="index">下移</view>
							<view class="btn" @tap="detailEdit" :data-index="index" v-if="setData.temp=='text'">编辑</view>
							<view class="btn" @tap="detailMovedel" :data-index="index">删除</view>
						</view>
						<view class="detailbox">
							<block v-if="setData.temp=='notice'">
								<dp-notice :params="setData.params" :data="setData.data"></dp-notice>
							</block>
							<block v-if="setData.temp=='banner'">
								<dp-banner :params="setData.params" :data="setData.data"></dp-banner>
							</block>
							<block v-if="setData.temp=='search'">
								<dp-search :params="setData.params" :data="setData.data"></dp-search>
							</block>
							<block v-if="setData.temp=='text'">
								<dp-text :params="setData.params" :data="setData.data"></dp-text>
							</block>
							<block v-if="setData.temp=='title'">
								<dp-title :params="setData.params" :data="setData.data"></dp-title>
							</block>
							<block v-if="setData.temp=='dhlist'">
								<dp-dhlist :params="setData.params" :data="setData.data"></dp-dhlist>
							</block>
							<block v-if="setData.temp=='line'">
								<dp-line :params="setData.params" :data="setData.data"></dp-line>
							</block>
							<block v-if="setData.temp=='blank'">
								<dp-blank :params="setData.params" :data="setData.data"></dp-blank>
							</block>
							<block v-if="setData.temp=='menu'">
								<dp-menu :params="setData.params" :data="setData.data"></dp-menu>
							</block>
							<block v-if="setData.temp=='map'">
								<dp-map :params="setData.params" :data="setData.data"></dp-map>
							</block>
							<block v-if="setData.temp=='cube'">
								<dp-cube :params="setData.params" :data="setData.data"></dp-cube>
							</block>
							<block v-if="setData.temp=='picture'">
								<dp-picture :params="setData.params" :data="setData.data"></dp-picture>
							</block>
							<block v-if="setData.temp=='pictures'">
								<dp-pictures :params="setData.params" :data="setData.data"></dp-pictures>
							</block>
							<block v-if="setData.temp=='video'">
								<dp-video :params="setData.params" :data="setData.data"></dp-video>
							</block>
							<block v-if="setData.temp=='shop'">
								<dp-shop :params="setData.params" :data="setData.data" :shopinfo="setData.shopinfo"></dp-shop>
							</block>
							<block v-if="setData.temp=='product'">
								<dp-product :params="setData.params" :data="setData.data" :menuindex="menuindex"></dp-product>
							</block>
							<block v-if="setData.temp=='collage'">
								<dp-collage :params="setData.params" :data="setData.data"></dp-collage>
							</block>
							<block v-if="setData.temp=='kanjia'">
								<dp-kanjia :params="setData.params" :data="setData.data"></dp-kanjia>
							</block>
							<block v-if="setData.temp=='seckill'">
								<dp-seckill :params="setData.params" :data="setData.data"></dp-seckill>
							</block>
							<block v-if="setData.temp=='scoreshop'">
								<dp-scoreshop :params="setData.params" :data="setData.data"></dp-scoreshop>
							</block>
							<block v-if="setData.temp=='coupon'">
								<dp-coupon :params="setData.params" :data="setData.data"></dp-coupon>
							</block>
							<block v-if="setData.temp=='article'">
								<dp-article :params="setData.params" :data="setData.data"></dp-article>
							</block>
							<block v-if="setData.temp=='business'">
								<dp-business :params="setData.params" :data="setData.data"></dp-business>
							</block>
							<block v-if="setData.temp=='liveroom'">
								<dp-liveroom :params="setData.params" :data="setData.data"></dp-liveroom>
							</block>
							<block v-if="setData.temp=='button'">
								<dp-button :params="setData.params" :data="setData.data"></dp-button>
							</block>
							<block v-if="setData.temp=='hotspot'">
								<dp-hotspot :params="setData.params" :data="setData.data"></dp-hotspot>
							</block>
							<block v-if="setData.temp=='cover'">
								<dp-cover :params="setData.params" :data="setData.data"></dp-cover>
							</block>
							<block v-if="setData.temp=='richtext'">
								<dp-richtext :params="setData.params" :data="setData.data" :content="setData.content"></dp-richtext>
							</block>
							<block v-if="setData.temp=='form'">
								<dp-form :params="setData.params" :data="setData.data" :content="setData.content"></dp-form>
							</block>
							<block v-if="setData.temp=='userinfo'">
								<dp-userinfo :params="setData.params" :data="setData.data" :content="setData.content"></dp-userinfo>
							</block>
						</view>
						</view>
					</block>
				</view>
			</view>
		</view>
		</view>
		<!--  -->
		<button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit">提交</button>
</form>
		<view class="popup__container" v-if="clistshow">
			<view class="popup__overlay" @tap.stop="changeClistDialog"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">请选择文章分类</text>
					<image :src="`${pre_url}/static/img/close.png`" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="changeClistDialog"/>
				</view>
				<view class="popup__content">
					<block v-for="(item, index) in clist" :key="item.id">
						<view class="clist-item" @tap="cidsChange" :data-id="item.id">
							<view class="flex1">{{item.name}}</view>
							<view class="radio" :style="inArray(item.id,cids) ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" src="/static/img/checkd.png"/></view>
						</view>
						<block v-for="(item2, index2) in item.child" :key="item2.id">
							<view class="clist-item" style="padding-left:80rpx" @tap="cidsChange" :data-id="item2.id">
								<view class="flex1" v-if="item.child.length-1==index2">└ {{item2.name}}</view>
								<view class="flex1" v-else>├ {{item2.name}}</view>
								<view class="radio" :style="inArray(item2.id,cids) ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" src="/static/img/checkd.png"/></view>
							</view>
							<block v-for="(item3, index3) in item2.child" :key="item3.id">
							<view class="clist-item" style="padding-left:160rpx" @tap="cidsChange" :data-id="item3.id">
								<view class="flex1" v-if="item2.child.length-1==index3">└ {{item3.name}}</view>
								<view class="flex1" v-else>├ {{item3.name}}</view>
								<view class="radio" :style="inArray(item3.id,cids) ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" src="/static/img/checkd.png"/></view>
							</view>
							</block>
						</block>
					</block>
				</view>
			</view>
		</view>
		
		<uni-popup id="dialogDetailtxt" ref="dialogDetailtxt" type="dialog">
			<view class="uni-popup-dialog">
				<view class="uni-dialog-title">
					<text class="uni-dialog-title-text">请输入文本内容</text>
				</view>
				<view class="uni-dialog-content">
					<scroll-view class="uni-dialog-scroll" scroll-y="true">
						<view style="width: 100%;">
							<textarea :value="edit_text" placeholder="请输入文本内容" auto-height  @input="catcheDetailtxt" :maxlength='-1'></textarea>
						</view>
					</scroll-view>
				</view>
				<view class="uni-dialog-button-group">
					<view class="uni-dialog-button" @click="dialogDetailtxtClose">
						<text class="uni-dialog-button-text">取消</text>
					</view>
					<view class="uni-dialog-button uni-border-left" @click="dialogDetailtxtConfirm">
						<text class="uni-dialog-button-text uni-button-color">确定</text>
					</view>
				</view>
				<view class="uni-popup-dialog__close" @click="dialogDetailtxtClose">
					<span class="uni-popup-dialog__close-icon "></span>
				</view>
			</view>
		</uni-popup>
		<wxxieyi></wxxieyi>
		<dp-tabbar :opt="opt"></dp-tabbar>
	</view>
</template>
<script>
	var app = getApp();
	export default{
		data(){
			return{
				navigationMenu:{},
				platform: app.globalData.platform,
				pre_url: app.globalData.pre_url,
				clist:{},
				clistshow:false,
				quanshow:false,
				cnames:'',
				cids:[],
				cateArr:[],
				pics:[],
				pagecontent:[],
				edit_text:'',
				edit_text_index:'',
				pid:'',
				info:{},
				opt:{},
				circles:[],
				qids:[],
				qname:[],
				keyword:'',
				video:'',
				catche_detailtxt:'',
				startTime:'',
				startTime2:'',
				sexIndexArr:[0,1,2,3],
				canpl:1,
				canplrp:1,
				pinglun_check:0,
				status:1
			}
		},
		
		onLoad: function (opt) {
			this.opt = app.getopts(opt);
			this.pid = this.opt.pid || '';
			this.getdata();
		},
		methods: {
			switchChange:function(){
				this.canpl = !this.canpl
			},
			switchChange2:function(){
				this.canplrp = !this.canplrp
			},
			switchChange3:function(){
				this.pinglun_check = !this.pinglun_check
			},
			switchChange4:function(){
				this.status = !this.status
			},
			// 页面信息
			getdata:function(){
				var that = this;
				that.loading = true;
				app.get('ApiAdminArticle/edit', {}, function (res) {
					that.loading = false;
					console.log(res)
					that.clist = res.data
					that.cateArr = res.cateArr
					that.info = res.info
					that.pics = res.info.pics || []
					that.startTime = res.info.startTime || ''
					that.startTime2 = res.info.startTime2 || ''
				})
			},
			setzhanshi:function(index){
				if(this.sexIndexArr.includes(index)){
					let index_ = this.sexIndexArr.indexOf(index);
					this.sexIndexArr.splice(index_,1)
				}else{
					this.sexIndexArr.push(index);
				}
			},
			startTimeChange: function(e) {
				this.startTime = e.detail.value;
			},
			startTime2Change: function(e) {
				this.startTime2 = e.detail.value;
			},
			subform: function (e) {
			  var that = this;
			  var formdata = e.detail.value;
				if(formdata.name == ''){
					app.alert('标题不能为空');return;
				}

				formdata.cids = that.cids;
				formdata.pics = that.pics;
				// formdata.qids = that.qids;
				formdata.createtime=that.startTime+ ' ' +that.startTime2
				formdata.zhanshi=that.sexIndexArr
				formdata.canpl=that.canpl
				formdata.canplrp=that.canplrp
				formdata.pinglun_check=that.pinglun_check
				formdata.status=that.status
				console.log(formdata)
				
				app.showLoading('保存中');
			  app.post('ApiAdminArticle/save', {info:formdata,pagecontent:that.pagecontent}, function (res) {
			    if (res.status == 0) {
			      app.error(res.msg);
			    } else {
			      app.success(res.msg);
			      setTimeout(function () {
			        app.goto('/admin/index/index', 'redirect');
			      }, 1000);
			    }
			  });
			},
			changeClistDialog:function(){
				this.clistshow = !this.clistshow
			},
			selectCircle:function(pid,name){
				var qids = this.qids;
				if(!qids) qids = [];
				qids.push(pid);
				this.qids = qids;
				
				var qname = this.qname;
				if(!qname) qname = [];
				qname.push(name);
				this.qname = qname;
				
			},
			
			cidsChange:function(e){
				console.log(e)
				var clist = this.clist;
				var cids = this.cids;
				var cid = e.currentTarget.dataset.id;
				var newcids = [];
				newcids.push(cid);
				this.cids = newcids;
				this.getcnames();
				
				// var newcids = [];
				// var ischecked = false;
				// for(var i in cids){
				// 	if(cids[i] != cid){
				// 		newcids.push(cids[i]);
				// 	}else{
				// 		ischecked = true;
				// 	}
				// }
				// if(ischecked==false){
				// 	if(newcids.length >= 5){
				// 		app.error('最多只能选择五个分类');return;
				// 	}
				// 	newcids.push(cid);
				// }
				// this.cids = newcids;
				// this.getcnames();
			},
			getcnames:function(){
				var cateArr = this.cateArr;
				var cids = this.cids;
				var cnames = [];
				for(var i in cids){
					cnames.push(cateArr[cids[i]]);
				}
				this.cnames = cnames.join(',');
			},
			uploadimg:function(e){
				var that = this;
				var pernum = parseInt(e.currentTarget.dataset.pernum);
				if(!pernum) pernum = 1;
				var field= e.currentTarget.dataset.field
				var pics = that[field]
				if(!pics) pics = [];
				app.chooseImage(function(urls){
					for(var i=0;i<urls.length;i++){
						pics.push(urls[i]);
					}
					if(field == 'pic') that.pic = pics;
					if(field == 'pics') that.pics = pics;
				},pernum);
			},
			removeimg:function(e){
				var that = this;
				var index= e.currentTarget.dataset.index
				var field= e.currentTarget.dataset.field
				if(field == 'pic'){
					var pics = that.pic
					pics.splice(index,1);
					that.pic = pics;
				}else if(field == 'pics'){
					var pics = that.pics
					pics.splice(index,1);
					that.pics = pics;
				}
			},
			detailAddtxt:function(){
				this.edit_text = '';
				this.edit_text_index = '';
				this.catche_detailtxt = '';
				this.$refs.dialogDetailtxt.open();
			},
			dialogDetailtxtClose:function(){
				this.edit_text = '';
				this.$refs.dialogDetailtxt.close();
			},
			catcheDetailtxt:function(e){
				console.log(e)
				this.catche_detailtxt = e.detail.value;
			},
			dialogDetailtxtConfirm:function(e){
				var detailtxt = this.catche_detailtxt;
				console.log(detailtxt)
				//判断是否编辑
				let index = this.edit_text_index;
				if(index !=='' && index >= 0){
					let pageparams = this.pagecontent[index].params;
					pageparams.content = detailtxt;
					pageparams.showcontent = detailtxt;
					this.$refs.dialogDetailtxt.close();
					this.edit_text = '';
					this.edit_text_index = '';
					return;
				}
				var Mid = 'M' + new Date().getTime() + parseInt(Math.random() * 1000000);
				var pagecontent = this.pagecontent;
				pagecontent.push({"id":Mid,"temp":"text","params":{"content":detailtxt,"showcontent":detailtxt,"bgcolor":"#ffffff","fontsize":"14","lineheight":"20","letter_spacing":"0","bgpic":"","align":"left","color":"#000","margin_x":"0","margin_y":"0","padding_x":"5","padding_y":"5","quanxian":{"all":true},"platform":{"all":true}},"data":"","other":"","content":""});
				this.pagecontent = pagecontent;
				this.$refs.dialogDetailtxt.close();
			},
			detailAddpic:function(){
				var that = this;
				app.chooseImage(function(urls){
					var Mid = 'M' + new Date().getTime() + parseInt(Math.random() * 1000000);
					var pics = [];
					for(var i in urls){
						var picid = 'p' + new Date().getTime() + parseInt(Math.random() * 1000000);
						pics.push({"id":picid,"imgurl":urls[i],"hrefurl":"","option":"0"})
					}
					var pagecontent = that.pagecontent;
					pagecontent.push({"id":Mid,"temp":"picture","params":{"bgcolor":"#FFFFFF","margin_x":"0","margin_y":"0","padding_x":"0","padding_y":"0","quanxian":{"all":true},"platform":{"all":true}},"data":pics,"other":"","content":""});
					that.pagecontent = pagecontent;
				},9);
			},
			
			detailAddvideo:function(){
				var that = this;
				uni.chooseVideo({
			    sourceType: ['album', 'camera'],
			    success: function (res) {
			      var tempFilePath = res.tempFilePath;
			      app.showLoading('上传中');
			      uni.uploadFile({
			        url: app.globalData.baseurl + 'ApiImageupload/uploadImg/aid/' + app.globalData.aid + '/platform/' + app.globalData.platform + '/session_id/' + app.globalData.session_id,
			        filePath: tempFilePath,
			        name: 'file',
			        success: function (res) {
			          app.showLoading(false);
			          var data = JSON.parse(res.data);
			          if (data.status == 1) {
			            that.video = data.url;
									var pagecontent = that.pagecontent;
									var Mid = 'M' + new Date().getTime() + parseInt(Math.random() * 1000000);
									pagecontent.push({"id":Mid,"temp":"video","params":{"bgcolor":"#FFFFFF","margin_x":"0","margin_y":"0","padding_x":"0","padding_y":"0","src":data.url,"quanxian":{"all":true},"platform":{"all":true}},"data":"","other":"","content":""});
									that.pagecontent = pagecontent;
			          } else {
			            app.alert(data.msg);
			          }
			        },
			        fail: function (res) {
			          app.showLoading(false);
			          app.alert(res.errMsg);
			        }
			      });
			    },
			    fail: function (res) {
			      console.log(res); //alert(res.errMsg);
			    }
			  });
			},
			detailMoveup:function(e){
				var index = e.currentTarget.dataset.index;
				var pagecontent = this.pagecontent;
				if(index > 0)
					pagecontent[index] = pagecontent.splice(index-1, 1, pagecontent[index])[0];
			},
			detailMovedown:function(e){
				var index = e.currentTarget.dataset.index;
				var pagecontent = this.pagecontent;
				if(index < pagecontent.length-1)
					pagecontent[index] = pagecontent.splice(index+1, 1, pagecontent[index])[0];
			},
			detailMovedel:function(e){
				var index = e.currentTarget.dataset.index;
				var pagecontent = this.pagecontent;
				pagecontent.splice(index,1);
			},
			detailEdit:function(e){
			  var index = e.currentTarget.dataset.index;
			  var pagecontent = this.pagecontent;
			  this.edit_text = pagecontent[index].params.showcontent;
				this.edit_text_index = index;
				this.$refs.dialogDetailtxt.open();
			},
		},
			
	}
</script>

<style>
	.form-view{width: 94%;background: #fff;padding: 20rpx 0rpx;margin: 20rpx auto 0rpx;border-radius: 10rpx;}
	.form-options{width: 92%;margin: 0 auto;border-bottom: 1px #f8f8f8 solid;padding: 32rpx 0rpx;}
	.form-options .options-title{font-size: 28rpx;color: #242424;font-weight: 500;}
	.form-options .options-title .up-view{}
	.options-title .up-view .up-view-options{border: 1px solid rgba(93, 102, 123, 0.3);box-sizing: border-box;border-radius: 8rpx;padding: 10rpx 16rpx;color: #5D667B;
	font-size: 22rpx;}
	.options-title .up-view .up-view-options image{width: 32rpx;height: 32rpx;margin-right: 10rpx;}
	.form-options .options-input{width: 366rpx;text-align: right;}
	.form-options .textarea-view{width: 100%;line-height: 40rpx;min-height: 260rpx;height: auto;max-height: 500rpx;overflow-y: scroll;margin-top: 32rpx;}
	.textarea-view textarea{width: 100%;}
	.upload-pic-view{width: 100%;justify-content: flex-start;flex-wrap: wrap;margin-top: 32rpx;}
	.upload-pic-view .up-fun-view{width: 210rpx;height: 210rpx;border-radius:16rpx ;overflow: hidden;position: relative;margin-bottom: 20rpx;margin-right: 20rpx}
	.upload-pic-view .up-fun-view .close-but{width: 40rpx;height: 40rpx;position: absolute;top: 0rpx;right: 0rpx;border-radius: 50%;}
	.upload-pic-view .up-fun-view .close-but image{width: 100%;height: 100%;}
	.upload-pic-view .up-fun-view image{width: 100%;height: 100%;}
	.form-options .tips-text{font-size: 28rpx;color: #9D9DAB;flex: 1;padding-left: 30rpx;}
	.form-options .tips-text image{width: 32rpx;height: 32rpx;}
	.pageBottom-class{background: #fff;width: 100%;position: fixed;bottom:0;left: 50%;transform: translateX(-50%);padding-top: 20rpx;padding-bottom: calc(20rpx + env(safe-area-inset-bottom));}
	.pageBottom-but{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; border: none; }

	.clist-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
	.radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
	.radio .radio-img{width:100%;height:100%;display:block}

	.dynamic-circle-view{align-items: center;flex-wrap: wrap;margin: 20rpx auto;width: 92%;}
	.dynamic-circle-view .dynamic-circle-options{white-space: nowrap;background: rgba(61, 91, 246, 0.05);padding: 5px 6px;border-radius: 4px;text-align: center;
	color: #3D5BF6;font-size: 24rpx;margin-right: 18rpx;display: flex;align-items: center;justify-content: center;margin-bottom: 18rpx;}
	.dynamic-circle-view .dynamic-circle-options image{width: 28rpx;height: 28rpx;margin-left: 16rpx;}

.form-box{ padding:2rpx 24rpx 0 24rpx; background: #fff;border-radius: 10rpx}
	.form-item{ line-height: 100rpx; display: flex;justify-content: space-between;border-bottom:0px solid #eee }
	.form-item .f1{color:#222;width:250rpx;flex-shrink:0;}
	.form-item .f2{display:flex;align-items:center;flex:1}
	.form-box .form-item:last-child{ border:none}
	.form-box .flex-col{padding-bottom:20rpx}
	.form-item input{ width: 100%; border: 1px solid #f1f1f1;color:#111;font-size:28rpx; /*text-align: right;*/height:70rpx;padding:0 10rpx;border-radius:6rpx}
	.form-item textarea{ width:100%;min-height:200rpx;padding:20rpx 0;border: none;}
	.form-item .upload_pic{ margin:50rpx 0;background: #F3F3F3;width:90rpx;height:90rpx; text-align: center  }
	.form-item .upload_pic image{ width: 32rpx;height: 32rpx; }
	.savebtn{ width: 90%; height:96rpx; line-height: 96rpx; text-align:center;border-radius:48rpx; color: #fff;font-weight:bold;margin: 0 5%; margin-top:60rpx; border: none;}
	.detailop{display:flex;line-height:60rpx}
	.detailop .btn{border:1px solid #ccc;margin-right:10rpx;padding:0 16rpx;color:#222;border-radius:10rpx}
	.detaildp{position:relative;line-height:50rpx}
	.detaildp .op{width:100%;display:flex;justify-content:flex-end;font-size:24rpx;height:60rpx;line-height:60rpx;margin-top:10rpx}
	.detaildp .op .btn{background:rgba(0,0,0,0.4);margin-right:10rpx;padding:0 10rpx;color:#fff}
	.detaildp .detailbox{border:2px dashed #00a0e9}
	.uni-popup-dialog {width: 300px;border-radius: 5px;background-color: #fff;}
	.uni-dialog-title {display: flex;flex-direction: row;justify-content: center;padding-top: 15px;padding-bottom: 5px;}
	.uni-dialog-title-text {font-size: 16px;font-weight: 500;}
	.uni-dialog-content {display: flex;flex-direction: row;justify-content: center;align-items: center;padding: 5px 15px 15px 15px;width: 100%;}
	.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;width: 100%;}
	.uni-dialog-button-group {display: flex;flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
	.uni-dialog-button {display: flex;flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;}
	.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
	.uni-dialog-button-text {font-size: 14px;}
	.uni-button-color {color: #007aff;}
	.uni-dialog-scroll{height: 300rpx;}
	.options-icon{width: 30rpx;height: 30rpx;margin-left: 20rpx;}
	.picker-view-class{font-size:28rpx;color: #BFBFCB;}
	.form-options .options-sex-view{margin-top: 20rpx;}
	.form-options .options-sex-view .sex-optinos-active{color: #3D5BF6 !important;border: 1px solid rgba(61, 91, 246, 0.6);
	background: rgba(61, 91, 246, 0.1) !important;}
	.form-options .options-sex-view .sex-optinos{border-radius: 4px;background: #F0F0F0;padding: 13rpx 38rpx;font-size: 28rpx;color: #9D9DB0;font-weight: 500;box-sizing: border-box;}
</style>