<template>
<view>
	<block v-if="isload">
		<form @submit="subform">
			<view class="form-box">
				<view class="form-item">
					<view class="f1">商品名称<text style="color:red"> *</text></view>
					<view class="f2">{{info.name}}</view>
				</view>
				
			</view>
			<view class="form-box">
				<view class="form-item flex-col" style="border-bottom:0">
					<view class="f1">商品主图<text style="color:red"> *</text></view>
					<view class="f2">
						<view v-for="(item, index) in pic" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="pic"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
							<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
						</view>
						<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pic" data-pernum="1" v-if="pic.length==0"></view>
					</view>
					<input type="text" hidden="true" name="pic" :value="pic.join(',')" maxlength="-1"/>
				</view>
			</view>
	
			<!-- 规格列表 -->
			<view class="form-box" v-for="(item,index) in gglist" :key="index">
				<view class="form-item" style="height:80rpx;line-height:80rpx">
					<view class="f1">规格</view>
					<view class="f2" style="font-weight:bold;line-height: 40rpx;">{{item.name}}</view>
				</view>
				<view class="form-item" style="height:80rpx;line-height:80rpx">
					<view class="f1">市场价（元）</view>
					<view class="f2"><input type="text" @input="gglistInput" :data-index="index" :disabled="mendian_usercenter==1?true:false" data-field="market_price" :name="'market_price['+index+']'" :value="item.market_price" placeholder="请填写市场价" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item" style="height:80rpx;line-height:80rpx">
					<view class="f1">成本价（元）</view>
					<view class="f2"><input type="text" @input="gglistInput" :data-index="index" :disabled="mendian_usercenter==1?true:false" data-field="cost_price" :name="'cost_price['+index+']'" :value="item.cost_price" placeholder="请填写成本价" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item" style="height:80rpx;line-height:80rpx">
					<view class="f1">销售价（元）</view>
					<view class="f2"><input type="text" @input="gglistInput" :data-index="index" :disabled="mendian_usercenter==1?true:false" data-field="sell_price" :name="'sell_price['+index+']'" :value="item.sell_price" placeholder="请填写销售价" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item" style="height:80rpx;line-height:80rpx">
					<view class="f1">重量（克）</view>
					<view class="f2">{{item.weight}}</view>
				</view>
				<view class="form-item" style="height:80rpx;line-height:80rpx">
					<view class="f1" style="position:relative">库存 
						<block v-if="item.isstock_warning==1">
							<view class="stockwarning"><image :src="pre_url+'/static/img/workorder/ts.png'" style="width:30rpx;height:30rpx" >库存不足</view>
						</block>
					</view>
					<view class="f2">
						<input type="text" @input="gglistInput" :data-index="index" data-field="stock" :name="'stock['+index+']'" :value="item.stock" placeholder="请填写库存" placeholder-style="color:#888"></input></view>
				</view>
			
			</view>

			<button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit">提交</button>
			<view style="height:50rpx"></view>
		</form>
	</block>
	<view style="display:none">{{test}}</view>
	<loading v-if="loading"></loading>
	<popmsg ref="popmsg"></popmsg>
	<wxxieyi></wxxieyi>
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
      info:{},
			pagecontent:[],
			aglevellist:[],
			levellist:[],
			clist:[],
			clist2:[],
			cateArr:[],
			cateArr2:[],
			glist:[],
			groupArr:[],
			freighttypeArr:['全部模板','指定模板','自动发货','在线卡密'],
			freightindex:0,
			freightList:[],
			freightdata:[],
			freightIds:[],
			guigedata:[],
			pic:[],
			pics:[],
			cids:[],
			cids2:[],
			gids:[],
			cnames:'',
			cnames2:'',
			gnames:'',
			clistshow:false,
			clist2show:false,
			glistshow:false,
			ggname:'',
			ggindex:0,
			ggindex2:0,
			oldgglist:[],
			gglist:[],
			catche_detailtxt:'',
			start_time1:'',
			start_time2:'',
			end_time1:'',
			end_time2:'',
			start_hours:'',
			end_hours:'',
			gettjArr:['-1'],
			product_showset:1,
			commission_canset:1,
			bid:0,
			paramList:[],
			paramdata:[],
			resparamdata:{},
			editorFormdata:[],
			business_selfscore:0,
			test:'',
			showtjArr:['-1'],
			mendian_usercenter:0,//是否是门店中心
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		this.getdata();
  },
  methods: {
		getdata:function(){
			var that = this;
			var id = that.opt.id ? that.opt.id : '';
			that.loading = true;
			app.get('ApiAdminProduct/edit',{id:id}, function (res) {
				that.loading = false;
				that.info = res.info;
				if(that.info.start_time){
					var start_times = (that.info.start_time).split(' ');
					that.start_time1 = start_times[0];
					that.start_time2 = start_times[1];
				}
				if(that.info.end_time){
					var end_times = (that.info.end_time).split(' ');
					that.end_time1 = end_times[0];
					that.end_time2 = end_times[1];
				}
				if(that.info.start_hours){
					that.start_hours = that.info.start_hours;
				}
				if(that.info.end_hours){
					that.end_hours = that.info.end_hours;
				}
				that.product_showset = res.product_showset
				that.commission_canset = res.commission_canset
				that.gettjArr = that.info.gettj
				that.showtjArr = that.info.showtj
				that.pagecontent = res.pagecontent;
				that.aglevellist = res.aglevellist;
				that.levellist = res.levellist;
				that.oldgglist = res.newgglist;
				that.clist = res.clist;
				that.cateArr = res.cateArr;
				that.clist2 = res.clist2;
				that.cateArr2 = res.cateArr2;
				that.glist = res.glist;
				that.groupArr = res.groupArr;
				that.freightList = res.freightList;
				that.freightdata = res.freightdata;
				if(res.info.freighttype == 1) that.freightindex = 0;
				if(res.info.freighttype == 0){
					that.freightindex = 1;
					if(res.info.freightdata){
						that.freightIds = res.info.freightdata.split(',');
					}
				}
				if(res.info.freighttype == 3) that.freightindex = 2;
				if(res.info.freighttype == 4) that.freightindex = 3;
				that.pic = res.pic;
				that.pics = res.pics;
				that.cids = res.cids;
				that.cids2 = res.cids2;
				that.gids = res.gids;
				that.guigedata = res.guigedata;
				
				that.paramList = res.paramList;
				that.resparamdata = res.paramdata;
				var paramList = res.paramList;
				var editorFormdata = [];
				var paramdata = {};
				for(var i in paramList){
					var thisval = res.paramdata[paramList[i].name];
					if(!thisval){
						if(paramList[i].type ==2){
							thisval = [];
						}else{
							thisval = '';
						}
					}
					
					if (paramList[i].type == '1') {
						for(var j in paramList[i].params){
							if(paramList[i].params[j] == thisval){
								thisval = j;
							}
						}
					}
					editorFormdata.push(thisval);
					paramdata['form'+i] = thisval;
				}
				console.log(editorFormdata)
				console.log(paramdata)
				that.editorFormdata = editorFormdata;
				that.paramdata = paramdata;
				if(res.bset)
				that.bset = res.bset;
				that.bid = res.bid;
				that.business_selfscore = res.business_selfscore || 0;
				that.mendian_usercenter = res.mendian_usercenter || 0;
				that.getcnames();
				that.getcnames2();
				that.getgnames();
				that.getgglist();
				that.loaded();
			});
		},
		editorBindPickerChange:function(e){
			var idx = e.currentTarget.dataset.idx;
			var tplindex = e.currentTarget.dataset.tplindex;
			var val = e.detail.value;
			var editorFormdata = this.editorFormdata;
			if(!editorFormdata) editorFormdata = [];
			editorFormdata[idx] = val;
			console.log(editorFormdata)
			this.editorFormdata = editorFormdata;
			this.test = Math.random();

			var field = e.currentTarget.dataset.formidx;
			this.paramdata[field] = val;
		},
		setfield:function(e){
			var field = e.currentTarget.dataset.formidx;
			var value = e.detail.value;
			this.paramdata[field] = value;
		},
    subform: function (e) {
      var that = this;
      var formdata = e.detail.value;
      formdata.cid = that.cids.join(',');
			if(that.bid > 0){
				formdata.cid2 = that.cids2.join(',');
			}
      formdata.gid = that.gids;
			var guigedata = that.guigedata;
			if(guigedata.length == 0){
				app.alert('至少需要添加一个规格');
				return;
			}
			for(var i in guigedata){
				if(guigedata[i].items.length==0){
					app.alert('规格分组['+guigedata[i].title+']至少需要添加一个规格');
					return;
				}
			}
			var freightindex = this.freightindex;
			if(freightindex == 0){
				formdata.freighttype = 1;
			}else if(freightindex == 1){
				formdata.freighttype = 0;
				formdata.freightdata = this.freightIds.join(',');
			}else if(freightindex == 2){
				formdata.freighttype = 3;
			}else if(freightindex == 3){
				formdata.freighttype = 4;
			}
			if(formdata.status==2){
				if(!that.start_time1 || !that.start_time2 || !that.end_time1 || !that.end_time2) return app.error('请选择完整时间区间');
				formdata.start_time = that.start_time1 + ' '+that.start_time2;
				formdata.end_time = that.end_time1 + ' '+that.end_time2;
				let obj1 = new Date(formdata.start_time)
				let obj = new Date(formdata.end_time)
				if (obj1.getTime() > obj.getTime()) return app.error('开始时间不能大于结束时间');
			}
			if(formdata.status==3){
				if(!that.start_hours || !that.end_hours) return app.error('请选择完整时间区间');
				formdata.start_hours = that.start_hours;
				formdata.end_hours = that.end_hours;
				let NewDate = new Date();
				let year = NewDate.getFullYear();
				let month = NewDate.getMonth() + 1 < 10 ? "0" + (NewDate.getMonth() + 1) : NewDate.getMonth() + 1;
				let day = NewDate.getDate() < 10 ? "0" + NewDate.getDate() : NewDate.getDate();
				let obj1 = new Date(year + '-' + month + '-'+ day + ' ' + formdata.start_hours);
				let obj = new Date(year + '-' + month + '-'+ day + ' ' + formdata.end_hours);
				if (obj1.getTime() > obj.getTime()) return app.error('开始时间不能大于结束时间');
			}
			var paramdata = this.paramdata;
			var paramformdata = {};
			console.log(paramdata)
			var paramList = that.paramList;
			for (var i = 0; i < paramList.length;i++){
				var paramval = paramdata['form' + i]  === undefined ? '' : paramdata['form' + i];
				if (paramList[i].is_required == 1 && (paramdata['form' + i] === '' || paramdata['form' + i] === null || paramdata['form' + i] === undefined || paramdata['form' + i].length==0)){
						app.alert(paramList[i].name+' 必填');return;
				}
				if (paramList[i].type == '1'){
						paramval = paramList[i].params[paramdata['form' + i]];
				}
				if (paramList[i].type == '2' && paramval === ''){
					paramval = [];
				}
				paramformdata[paramList[i].name] = paramval;
			}

      var id = that.opt.id ? that.opt.id : '';
      app.post('ApiAdminProduct/savestock', {id:id,gglist:that.gglist}, function (res) {
        if (res.status == 0) {
          app.error(res.msg);
        } else {
          app.success(res.msg);
          setTimeout(function () {
            app.goto('index', 'redirect');
          }, 1000);
        }
      });
    },
		detailAddtxt:function(){
			this.$refs.dialogDetailtxt.open();
		},
		dialogDetailtxtClose:function(){
			this.$refs.dialogDetailtxt.close();
		},
		catcheDetailtxt:function(e){
			console.log(e)
			this.catche_detailtxt = e.detail.value;
		},
		dialogDetailtxtConfirm:function(e){
			var detailtxt = this.catche_detailtxt;
			console.log(detailtxt)
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
		changeFrieght:function(e){
			var id = e.currentTarget.dataset.id;
			var index = e.currentTarget.dataset.index;
			var freightIds = this.freightIds;
			var newfreightIds = [];
			var ischecked = false;
			for(var i in freightIds){
				if(freightIds[i] != id){
					newfreightIds.push(freightIds[i]);
				}else{
					ischecked = true;
				}
			}
			if(!ischecked) newfreightIds.push(id);
			this.freightIds = newfreightIds;
		},
		freighttypeChange:function(e){
			this.freightindex = e.detail.value;
		},
		bindStatusChange:function(e){
			this.info.status = e.detail.value;
		},
		bindStartTime1Change:function(e){
			this.start_time1 = e.target.value
		},
		bindStartTime2Change:function(e){
			this.start_time2 = e.target.value
		},
		bindEndTime1Change:function(e){
			this.end_time1 = e.target.value
		},
		bindEndTime2Change:function(e){
			this.end_time2 = e.target.value
		},
		bindStartHoursChange:function(e){
			this.start_hours = e.target.value
		},
		bindEndHoursChange:function(e){
			this.end_hours = e.target.value
		},
		gglistInput:function(e){
			var index = e.currentTarget.dataset.index;
			var field = e.currentTarget.dataset.field;
			var gglist = this.gglist;
			gglist[index][field] = e.detail.value;
			this.gglist = gglist;
			console.log(gglist)
		},
		getgglist:function(){
			var oldgglist = this.oldgglist;
			var guigedata = this.guigedata;
			var gglist = [];
			var len = guigedata.length;
			var newlen = 1; 
			var h = new Array(len);
			for(var i=0;i<len;i++){
				var itemlen = guigedata[i].items.length;
				if(itemlen<=0) { return; };
				newlen*=itemlen;
				h[i] = new Array(itemlen);
				for(var j=0;j<itemlen;j++){
					h[i][j] = { k:guigedata[i].items[j].k,title:guigedata[i].items[j].title};
				}
			}
			 
			//排列组合算法
			var arr = h;  //原二维数组
			var sarr = [[]];  //排列组合后的数组
			for (var i = 0; i < arr.length; i++) {
				var tarr = [];
				for (var j = 0; j < sarr.length; j++)
					for (var k = 0; k < arr[i].length; k++){
						tarr.push(sarr[j].concat(arr[i][k]));
					}
					sarr = tarr;
			}
			console.log(sarr);
			console.log(' ------ ');
		
			for(var i=0;i<sarr.length;i++){
				var ks = [];
				var titles = [];
				for(var j=0;j<sarr[i].length;j++){
					ks.push( sarr[i][j].k);
					titles.push( sarr[i][j].title);
				}
				ks =ks.join(',');
				titles =titles.join(',');
				//console.log(ks);
				//console.log(titles);
				if(typeof(oldgglist[ks])!='undefined'){
					var val = oldgglist[ks];
				}else{
					var val = { ks:ks,name:titles,market_price:'',cost_price:'',sell_price:'',weight:'100',stock:'1000',pic:'',givescore:'0',lvprice_data:null};
				}
				gglist.push(val);
			}
			this.gglist = gglist;
			console.log(gglist);
		},
		addgggroupname:function(e){
			this.ggname = '';
			this.ggindex = -1;
			this.$refs.dialogInput2.open();
		},
		delgggroupname:function(e){
			var that = this;
			var ggindex = e.currentTarget.dataset.index;
			var title = e.currentTarget.dataset.title;
			uni.showActionSheet({
        itemList: [ '修改','删除'],
        success: function (res) {
					if(res.tapIndex >= 0){
						if (res.tapIndex == 0) { //修改规格项
							that.ggname = title;
							that.ggindex = ggindex;
							that.$refs.dialogInput2.open();return;
						}else if (res.tapIndex == 1) { //删除规格项
							var guigedata = that.guigedata;
							var newguigedata = [];
							for(var i in guigedata){
								if(i != ggindex){
									newguigedata.push(guigedata[i]);
								}
							}
							that.guigedata = newguigedata;
							console.log(newguigedata);
							that.getgglist();
						}
					}
				}
			});
		},
		setgggroupname:function(done,val){
			var guigedata = this.guigedata;
			var ggindex = this.ggindex;
			if(ggindex == -1){ //新增规格分组
				ggindex = guigedata.length;
				guigedata.push({k:ggindex,title:val,items:[]});
				this.guigedata = guigedata;
			}else{ //修改规格分组名称
				guigedata[ggindex].title = val;
				this.guigedata = guigedata;
			}
			this.$refs.dialogInput2.close();
			this.getgglist();
		},
		addggname:function(e){
			var ggindex = e.currentTarget.dataset.index;
			this.ggname = '';
			this.ggindex = ggindex;
			this.ggindex2 = -1;
			this.$refs.dialogInput.open();
		},
		delggname:function(e){
			var that = this;
			var ggindex = e.currentTarget.dataset.index;
			var ggindex2 = e.currentTarget.dataset.index2;
			var k = e.currentTarget.dataset.k;
			var title = e.currentTarget.dataset.title;
			uni.showActionSheet({
        itemList: [ '修改','删除'],
        success: function (res) {
					if(res.tapIndex >= 0){
						if (res.tapIndex == 0) { //修改规格项
							that.ggname = title;
							that.ggindex = ggindex;
							that.ggindex2 = ggindex2;
							that.$refs.dialogInput.open();return;
						}else if (res.tapIndex == 1) { //删除规格项
							var guigedata = that.guigedata;
							var newguigedata = [];
							for(var i in guigedata){
								if(i == ggindex){
									var newitems = [];
									var index2 = 0;
									for(var j in guigedata[i].items){
										if(j!=ggindex2){
											newitems.push({k:index2,title:guigedata[i].items[j].title});
											index2++;
										}
									}
									guigedata[i].items = newitems;
								}
								newguigedata.push(guigedata[i]);
							}
							that.guigedata = newguigedata;
							console.log(newguigedata)
							that.getgglist();
						}
					}
				}
			});
		},
		setggname:function(done,val){
			var guigedata = this.guigedata;
			var ggindex = this.ggindex;
			var ggindex2 = this.ggindex2;
			if(ggindex2 == -1){ //新增规格名称
				var items = guigedata[ggindex].items;
				ggindex2 = items.length;
				items.push({k:ggindex2,title:val});
				guigedata[ggindex].items = items;
				this.guigedata = guigedata;
			}else{ //修改规格名称
				guigedata[ggindex].items[ggindex2].title = val;
				this.guigedata = guigedata;
			}
			this.$refs.dialogInput.close();
			this.getgglist();
		},
		cidsChange:function(e){
			var clist = this.clist;
			var cids = this.cids;
			var cid = e.currentTarget.dataset.id;
			var newcids = [];
			var ischecked = false;
			for(var i in cids){
				if(cids[i] != cid){
					newcids.push(cids[i]);
				}else{
					ischecked = true;
				}
			}
			if(ischecked==false){
				if(newcids.length >= 5){
					app.error('最多只能选择五个分类');return;
				}
				newcids.push(cid);
			}
			this.cids = newcids;
			this.getcnames();
			this.getparams();
		},
		getparams:function(){
			var that = this;
			app.post('ApiAdminProduct/getParam', {cid:(this.cids).join(',')}, function (res) {
				that.paramList = res.paramList;
				//that.paramdata = res.paramdata;
				var paramList = res.paramList;
				var editorFormdata = [];
				var paramdata = {};
					console.log(that.paramdata);
				for(var i in paramList){
					var thisval = that.resparamdata[paramList[i].name];
					console.log(thisval);
					if(!thisval){
						if(paramList[i].type ==2){
							thisval = [];
						}else{
							thisval = '';
						}
					}
					if (paramList[i].type == '1') {
						for(var j in paramList[i].params){
							if(paramList[i].params[j] == thisval){
								thisval = j;
							}
						}
					}
					editorFormdata.push(thisval);
					paramdata['form'+i] = thisval;
				}
				console.log(editorFormdata)
				console.log(paramdata)
				that.editorFormdata = editorFormdata;
				that.paramdata = paramdata;
				that.test = Math.random();
      });
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
		cids2Change:function(e){
			var clist = this.clist2;
			var cids = this.cids2;
			var cid = e.currentTarget.dataset.id;
			var newcids = [];
			var ischecked = false;
			for(var i in cids){
				if(cids[i] != cid){
					newcids.push(cids[i]);
				}else{
					ischecked = true;
				}
			}
			if(ischecked==false){
				if(newcids.length >= 5){
					app.error('最多只能选择五个分类');return;
				}
				newcids.push(cid);
			}
			this.cids2 = newcids;
			this.getcnames2();
		},
		getcnames2:function(){
			var cateArr = this.cateArr2;
			var cids = this.cids2;
			var cnames = [];
			for(var i in cids){
				cnames.push(cateArr[cids[i]]);
			}
			this.cnames2 = cnames.join(',');
		},
		gidsChange:function(e){
			var glist = this.glist;
			var gids = this.gids;
			var gid = e.currentTarget.dataset.id;
			var newgids = [];
			var ischecked = false;
			for(var i in gids){
				if(gids[i] != gid){
					newgids.push(gids[i]);
				}else{
					ischecked = true;
				}
			}
			if(ischecked==false){
				newgids.push(gid);
			}
			this.gids = newgids;
			this.getgnames();
		},
		getgnames:function(){
			var groupArr = this.groupArr;
			var gids = this.gids;
			var gnames = [];
			for(var i in gids){
				gnames.push(groupArr[gids[i]]);
			}
			this.gnames = gnames.join(',');
		},
		changeClistDialog:function(){
			this.clistshow = !this.clistshow
		},
		changeClist2Dialog:function(){
			this.clist2show = !this.clist2show
		},
		changeGlistDialog:function(){
			this.glistshow = !this.glistshow
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
		uploadimg2:function(e){
			var that = this;
			var index= e.currentTarget.dataset.index
			app.chooseImage(function(urls){
				that.gglist[index].pic = urls[0];
			},1);
		},
		removeimg2:function(e){
			var that = this;
			var index= e.currentTarget.dataset.index
			that.gglist[index].pic = '';
		},
		scanprocode: function (d) {
			var that = this;
			if(app.globalData.platform == 'mp'){
				var jweixin = require('jweixin-module');
				jweixin.ready(function () {   //需在用户可能点击分享按钮前就先调用
					jweixin.scanQRCode({
						needResult: 1, // 默认为0，扫描结果由微信处理，1则直接返回扫描结果，
						scanType: ["qrCode","barCode"], // 可以指定扫二维码还是一维码，默认二者都有
						success: function (res) {
							var content = res.resultStr; // 当needResult 为 1 时，扫码返回的结果
							if(content.indexOf(',') > 0){
								content = content.split(',')[1];
							}
							that.info.procode = content
							that.test = Math.random();
						},
						fail:function(err){
							app.error(err.errMsg);
						}
					});
				});
			}else{
				uni.scanCode({
					success: function (res) {
						console.log(res);
						var content = res.result;
						that.info.procode = content
						that.test = Math.random();
					},
					fail:function(err){
						app.error(err.errMsg);
					}
				});
			}
		},
  }
};
</script>
<style>
radio{transform: scale(0.6);}
checkbox{transform: scale(0.6);}
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

.ggtitle{height:60rpx;line-height:60rpx;color:#111;font-weight:bold;font-size:26rpx;display:flex;border-bottom:1px solid #f4f4f4}
.ggtitle .t1{width:200rpx;}
.ggcontent{line-height:60rpx;margin-top:10rpx;color:#111;font-size:26rpx;display:flex}
.ggcontent .t1{width:200rpx;display:flex;align-items:center;flex-shrink:0}
.ggcontent .t1 .edit{width:40rpx;height:40rpx}
.ggcontent .t2{display:flex;flex-wrap:wrap;align-items:center}
.ggcontent .ggname{background:#f55;color:#fff;height:40rpx;line-height:40rpx;padding:0 20rpx;border-radius:8rpx;margin-right:20rpx;margin-bottom:10rpx;font-size:24rpx;position:relative}
.ggcontent .ggname .close{position:absolute;top:-14rpx;right:-14rpx;background:#fff;height:28rpx;width:28rpx;border-radius:14rpx}
.ggcontent .ggnameadd{background:#ccc;font-size:36rpx;color:#fff;height:40rpx;line-height:40rpx;padding:0 20rpx;border-radius:8rpx;margin-right:20rpx;margin-left:10rpx;position:relative}
.ggcontent .ggadd{font-size:26rpx;color:#558}

.ggbox{line-height:50rpx;}


.layui-imgbox{margin-right:16rpx;margin-bottom:10rpx;font-size:24rpx;position: relative;}
.layui-imgbox-img{display: block;width:200rpx;height:200rpx;padding:2px;border: #d3d3d3 1px solid;background-color: #f6f6f6;overflow:hidden}
.layui-imgbox-img>image{max-width:100%;}
.layui-imgbox-repeat{position: absolute;display: block;width:32rpx;height:32rpx;line-height:28rpx;right: 2px;bottom:2px;color:#999;font-size:30rpx;background:#fff}
.uploadbtn{position:relative;height:200rpx;width:200rpx}


.clist-item{display:flex;border-bottom: 1px solid #f5f5f5;padding:20rpx 30rpx;}
.radio{flex-shrink:0;width: 32rpx;height: 32rpx;background: #FFFFFF;border: 2rpx solid #BFBFBF;border-radius: 50%;margin-right:30rpx}
.radio .radio-img{width:100%;height:100%;display:block}

.freightitem{width:100%;height:60rpx;display:flex;align-items:center;margin-left:40rpx}
.freightitem .f1{color:#666;flex:1}

.detailop{display:flex;line-height:60rpx}
.detailop .btn{border:1px solid #ccc;margin-right:10rpx;padding:0 16rpx;color:#222;border-radius:10rpx}
.detaildp{position:relative;line-height:50rpx}
.detaildp .op{width:100%;display:flex;justify-content:flex-end;font-size:24rpx;height:60rpx;line-height:60rpx;margin-top:10rpx}
.detaildp .op .btn{background:rgba(0,0,0,0.4);margin-right:10rpx;padding:0 10rpx;color:#fff}
.detaildp .detailbox{border:2px dashed #00a0e9}

.uni-popup-dialog {width: 300px;border-radius: 5px;background-color: #fff;}
.uni-dialog-title {display: flex;flex-direction: row;justify-content: center;padding-top: 15px;padding-bottom: 5px;}
.uni-dialog-title-text {font-size: 16px;font-weight: 500;}
.uni-dialog-content {display: flex;flex-direction: row;justify-content: center;align-items: center;padding: 5px 15px 15px 15px;}
.uni-dialog-content-text {font-size: 14px;color: #6e6e6e;}
.uni-dialog-button-group {display: flex;flex-direction: row;border-top-color: #f5f5f5;border-top-style: solid;border-top-width: 1px;}
.uni-dialog-button {display: flex;flex: 1;flex-direction: row;justify-content: center;align-items: center;height: 45px;}
.uni-border-left {border-left-color: #f0f0f0;border-left-style: solid;border-left-width: 1px;}
.uni-dialog-button-text {font-size: 14px;}
.uni-button-color {color: #007aff;}

.stockwarning{ position: absolute; right:0rpx; bottom:0;display:flex; align-items:center;font-size:24rpx;color:red;   }
.stockwarning image{  margin-right:10rpx}
</style>