<template>
<view>
	<block v-if="isload">
		<form @submit="subform">
			<view class="form-box">
				<view class="form-item">
					<view class="f1">商品名称<text style="color:red"> *</text></view>
					<view class="f2"><input type="text" name="name" :value="info.name" placeholder="请填写商品名称" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item">
					<view class="f1">商品分类<text style="color:red"> *</text></view>
					<view class="f2" @tap="changeClistDialog"><text v-if="cids.length>0">{{cnames}}</text><text v-else style="color:#888">请选择</text><image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
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
				<view class="form-item flex-col">
					<view class="f1">商品图片</view>
					<view class="f2" style="flex-wrap:wrap">
						<view v-for="(item, index) in pics" :key="index" class="layui-imgbox">
							<view class="layui-imgbox-close" @tap="removeimg" :data-index="index" data-field="pics"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
							<view class="layui-imgbox-img"><image :src="item" @tap="previewImage" :data-url="item" mode="widthFix"></image></view>
						</view>
						<view class="uploadbtn" :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg" data-field="pics" data-pernum="9" v-if="pics.length<5"></view>
					</view>
					<input type="text" hidden="true" name="pics" :value="pics.join(',')" maxlength="-1"></input>
				</view>
			</view>
			<view class="form-box" v-show="scoreshop_guige">
				<view class="form-item">
					<text>多规格</text>
					<view>
						<radio-group class="radio-group" name="guigeset" @change="bindguigesetChange">
							<label><radio value="0" :checked="!info || info.guigeset==0?true:false"></radio> 关闭</label>
							<label><radio value="1" :checked="info.guigeset==1?true:false"></radio> 开启</label> 
						</radio-group>
					</view>
				</view>

				<view class="form-item flex-col" v-show="info.guigeset==1">
					<view class="f1">设置规格</view>
					<view class="flex-col">
						<view class="ggtitle">
							<view class="t1">规格分组</view>
							<view class="t2">规格名称</view>
						</view>
						<view class="ggcontent" v-for="(gg,index) in guigedata" :key="index">
							<view class="t1" @tap="delgggroupname" :data-index="index" :data-title="gg.title">{{gg.title}}<image class="edit" :src="pre_url+'/static/img/edit2.png'"/></view>
							<view class="t2">
								<view class="ggname" v-for="(ggitem,index2) in gg.items" :key="index2" @tap="delggname" :data-index="index" :data-index2="index2" :data-title="ggitem.title" :data-k="ggitem.k">{{ggitem.title}}<image class="close" :src="pre_url+'/static/img/ico-del.png'"/></view>
								<view class="ggnameadd" @tap="addggname" :data-index="index">+</view>
							</view>
						</view>
						<view class="ggcontent">
							<view class="ggadd" @tap="addgggroupname">添加分组</view>
						</view>
					</view>
				</view>
			</view>


			<!-- 规格列表 -->
			<view class="form-box" v-for="(item,index) in gglist" :key="index">
				<view class="form-item" style="height:80rpx;line-height:80rpx" v-show="info.guigeset==1">
					<view class="f1">规格</view>
					<view class="f2" style="font-weight:bold">{{item.name}}</view>
				</view>
				<view class="form-item" style="height:80rpx;line-height:80rpx">
					<view class="f1">市场价（元）</view>
					<view class="f2"><input type="text" @input="gglistInput" :data-index="index" data-field="market_price" :name="'market_price['+index+']'" :value="item.market_price" placeholder="请填写市场价" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item" style="height:80rpx;line-height:80rpx">
					<view class="f1">成本价（元）</view>
					<view class="f2"><input type="text" @input="gglistInput" :data-index="index" data-field="cost_price" :name="'cost_price['+index+']'" :value="item.cost_price" placeholder="请填写成本价" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item" style="height:80rpx;line-height:80rpx">
					<view class="f1">所需金额（元）</view>
					<view class="f2"><input type="text" @input="gglistInput" :data-index="index" data-field="money_price" :name="'money_price['+index+']'" :value="item.money_price" placeholder="请填写所需金额" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item" style="height:80rpx;line-height:80rpx">
					<view class="f1">所需{{t('积分')}}（个）</view>
					<view class="f2"><input type="text" @input="gglistInput" :data-index="index" data-field="score_price" :name="'score_price['+index+']'" :value="item.score_price" :placeholder="'请填写所需'+t('积分')" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item" style="height:80rpx;line-height:80rpx">
					<view class="f1">重量（克）</view>
					<view class="f2"><input type="text" @input="gglistInput" :data-index="index" data-field="weight" :name="'weight['+index+']'" :value="item.weight" placeholder="请填写重量" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item" style="height:80rpx;line-height:80rpx">
					<view class="f1">库存</view>
					<view class="f2"><input type="text" @input="gglistInput" :data-index="index" data-field="stock" :name="'stock['+index+']'" :value="item.stock" placeholder="请填写库存" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item" v-show="info.guigeset==1">
					<view class="f1">规格图片</view>
					<view class="f2" style="flex-wrap:wrap;margin-top:20rpx;margin-bottom:20rpx">
						<view class="layui-imgbox" v-if="item.pic!=''">
							<view class="layui-imgbox-close" @tap="removeimg2" :data-index="index"><image style="display:block" :src="pre_url+'/static/img/ico-del.png'"></image></view>
							<view class="layui-imgbox-img"><image :src="item.pic" @tap="previewImage" :data-url="item.pic" mode="widthFix"></image></view>
						</view>
						<view class="uploadbtn" v-else :style="'background:url('+pre_url+'/static/img/shaitu_icon.png) no-repeat 60rpx;background-size:80rpx 80rpx;background-color:#F3F3F3;'" @tap="uploadimg2" :data-index="index"></view>
					</view>
				</view>
			</view>
			<view class="form-box">
				<view class="form-item">
					<view class="f1">配送模板</view>
					<view class="f2">
						<picker @change="freighttypeChange" :value="freightindex" :range="freighttypeArr">
							<view class="picker">{{freighttypeArr[freightindex]}}</view>
						</picker>
						<image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/>
					</view>
				</view>
				<view class="form-item flex-col" v-if="freightindex==1">
					<view class="f1">选择模板</view>
					<view class="f2 flex-col">
						<view class="freightitem" style="width:100%" v-for="(item,index) in freightList" :key="index" @tap="changeFrieght" :data-index="index" :data-id="item.id">
							<view class="f1">{{item.name}}</view>
							<view class="radio" :style="inArray(item.id,freightIds) ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
					</view>
				</view>
				<view class="form-item flex-col" v-if="freightindex==2">
					<view class="f1">发货信息</view>
					<view class="f2 flex-col"><textarea name="freightdata" placeholder="请输入发货信息" placeholder-style="color:#888;font-size:28rpx" style="height:160rpx;min-height:160rpx;font-size:28rpx" maxlength="-1" :value="info.freightdata"></textarea></view>
				</view>
				<view class="form-item flex-col" v-if="freightindex==3" style="color:#999">请在电脑端后台上传卡密信息</view>
			</view>

			<view class="form-box">
				<view class="form-item">
					<view class="f1">销量</view>
					<view class="f2"><input type="text" name="sales" :value="info.sales" placeholder="请填写销量" placeholder-style="color:#888"></input></view>
					<input type="text" hidden="true" name="oldsales" :value="info.id?info.sales:'0'">
				</view>
				<view class="form-item">
					<view class="f1">每人限购</view>
					<view class="f2"><input type="text" name="buymax" :value="info.buymax" placeholder="0表示不限购" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item">
					<view class="f1">序号</view>
					<view class="f2"><input type="text" name="sort" :value="info.sort" placeholder="用于排序,越大越靠前" placeholder-style="color:#888"></input></view>
				</view>
			</view>

			<view class="form-box">
				<view class="form-item">
					<view>状态<text style="color:red"> *</text></view>
					<view>
						<radio-group class="radio-group" name="status" @change="bindStatusChange">
							<label><radio value="1" :checked="info.status==1?true:false"></radio> 上架</label> 
							<label><radio value="0" :checked="!info || info.status==0?true:false"></radio> 下架</label>
						</radio-group>
					</view>
				</view>
			</view>
			

			<view class="form-box">
				<view class="form-item flex-col">
					<text>商品详情</text>
					<view class="detailop"><view class="btn" @tap="detailAddtxt">+文本</view><view class="btn" @tap="detailAddpic">+图片</view><view class="btn" @tap="detailAddvideo">+视频</view></view>
					<view>
						<block v-for="(setData, index) in pagecontent" :key="index">
							<view class="detaildp">
							<view class="op"><view class="flex1"></view><view class="btn" @tap="detailMoveup" :data-index="index">上移</view><view class="btn" @tap="detailMovedown" :data-index="index">下移</view><view class="btn" @tap="detailMovedel" :data-index="index">删除</view></view>
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


			<button class="savebtn" :style="'background:linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'" form-type="submit">提交</button>
			<view style="height:50rpx"></view>
		</form>

		
		<view class="popup__container" v-if="clistshow">
			<view class="popup__overlay" @tap.stop="changeClistDialog"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">请选择商品分类</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="changeClistDialog"/>
				</view>
				<view class="popup__content">
					<block v-for="(item, index) in clist" :key="item.id">
						<view class="clist-item" @tap="cidsChange" :data-id="item.id">
							<view class="flex1">{{item.name}}</view>
							<view class="radio" :style="inArray(item.id,cids) ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
						<block v-for="(item2, index2) in item.child" :key="item2.id">
							<view class="clist-item" style="padding-left:80rpx" @tap="cidsChange" :data-id="item2.id">
								<view class="flex1" v-if="item.child.length-1==index2">└ {{item2.name}}</view>
								<view class="flex1" v-else>├ {{item2.name}}</view>
								<view class="radio" :style="inArray(item2.id,cids) ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
							</view>
							<block v-for="(item3, index3) in item2.child" :key="item3.id">
							<view class="clist-item" style="padding-left:160rpx" @tap="cidsChange" :data-id="item3.id">
								<view class="flex1" v-if="item2.child.length-1==index3">└ {{item3.name}}</view>
								<view class="flex1" v-else>├ {{item3.name}}</view>
								<view class="radio" :style="inArray(item3.id,cids) ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
							</view>
							</block>
						</block>
					</block>
				</view>
			</view>
		</view>
		
		
		<uni-popup id="dialogInput" ref="dialogInput" type="dialog">
			<uni-popup-dialog mode="input" title="输入规格名称" :value="ggname" placeholder="请输入规格名称" @confirm="setggname"></uni-popup-dialog>
		</uni-popup>
		<uni-popup id="dialogInput2" ref="dialogInput2" type="dialog">
			<uni-popup-dialog mode="input" title="输入规格分组" :value="ggname" placeholder="请输入规格分组" @confirm="setgggroupname"></uni-popup-dialog>
		</uni-popup>
		<uni-popup id="dialogDetailtxt" ref="dialogDetailtxt" type="dialog">
			<view class="uni-popup-dialog">
				<view class="uni-dialog-title">
					<text class="uni-dialog-title-text">请输入文本内容</text>
				</view>
				<view class="uni-dialog-content">
					<textarea value="" placeholder="请输入文本内容" @input="catcheDetailtxt"></textarea>
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
			cateArr:[],
			freighttypeArr:['全部模板','指定模板','自动发货','在线卡密'],
			freightindex:0,
			freightList:[],
			freightdata:[],
			freightIds:[],
			guigedata:[],
			pic:[],
			pics:[],
			cids:[],
			cnames:'',
			clistshow:false,
			ggname:'',
			ggindex:0,
			ggindex2:0,
			oldgglist:[],
			gglist:[],
			catche_detailtxt:'',
			product_showset:1,
			commission_canset:1,
			bid:0,
			paramList:[],
			paramdata:[],
			resparamdata:{},
			editorFormdata:[],
			test:'',
			scoreshop_guige:false,
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
			app.get('ApiAdminScoreshopProduct/edit',{id:id}, function (res) {
				that.loading = false;
				that.info = res.info;
				that.scoreshop_guige = res.scoreshop_guige;
				that.product_showset = res.product_showset
				that.commission_canset = res.commission_canset
				that.pagecontent = res.pagecontent;
				that.aglevellist = res.aglevellist;
				that.levellist = res.levellist;
				that.oldgglist = res.newgglist;
				that.clist = res.clist;
				that.cateArr = res.cateArr;
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
				if(res.info.cid) that.cids = [res.info.cid];
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
				that.getcnames();
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
      formdata.cid = (that.cids).join(',');
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
      var id = that.opt.id ? that.opt.id : '';
      app.post('ApiAdminScoreshopProduct/save', {id:id,info:formdata,guigedata:guigedata,gglist:that.gglist,pagecontent:that.pagecontent}, function (res) {
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
		bindguigesetChange:function(e){
			console.log(e.detail.value)
			this.info.guigeset = e.detail.value;
			if(this.info.guigeset == 0){
				this.guigedata = [{"k":0,"title":"规格","items":[{"k":0,"title":"默认规格"}]}];
				for(var k in this.gglist){
					this.gglist = [this.gglist[k]];break;
				}
			}
			this.test = Math.random();
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
			this.cids = [cid];
			this.getcnames();
		},
		getcnames:function(){
			var cateArr = this.cateArr;
			var cids = this.cids;
			console.log(cids)
			var cnames = [];
			for(var i in cids){
				cnames.push(cateArr[cids[i]]);
			}
			this.cnames = cnames.join(',');
		},
		changeClistDialog:function(){
			this.clistshow = !this.clistshow
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
</style>