<template>
<view>
	<block v-if="isload">
		<form @submit="subform">
			<view class="form-box">
				<view class="form-item">
					<view class="f1">菜品名称<text style="color:red"> *</text></view>
					<view class="f2"><input type="text" name="name" :value="info.name" placeholder="请填写菜品名称" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item">
					<view class="f1">菜品分类<text style="color:red"> *</text></view>
					<view class="f2" @tap="changeClistDialog"><text v-if="cids.length>0">{{cnames}}</text><text v-else style="color:#888">请选择</text><image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
				</view>
				<view class="form-item" v-if="show_area_list">
					<view class="f1">出餐区域<text style="color:red"> *</text></view>
					<view class="f2" @tap="changeArealistDialog"><text v-if="area_name">{{area_name}}</text><text v-else style="color:#888">请选择</text><image :src="pre_url+'/static/img/arrowright.png'" style="width:30rpx;height:30rpx"/></view>
				</view>
			</view>
			<view class="form-box">
				<view class="form-item flex-col" style="border-bottom:0">
					<view class="f1">菜品主图<text style="color:red"> *</text></view>
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
					<view class="f1">菜品图片</view>
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
			<view class="form-box">
				<view class="form-item flex-col">
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
				<view class="form-item" style="height:80rpx;line-height:80rpx">
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
					<view class="f1">销售价（元）</view>
					<view class="f2"><input type="text" @input="gglistInput" :data-index="index" data-field="sell_price" :name="'sell_price['+index+']'" :value="item.sell_price" placeholder="请填写销售价" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item" style="height:80rpx;line-height:80rpx">
					<view class="f1">每日库存</view>
					<view class="f2"><input type="text" @input="gglistInput" :data-index="index" data-field="stock_daily" :name="'stock_daily['+index+']'" :value="item.stock_daily" placeholder="请填写每日库存" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item" style="height:80rpx;line-height:80rpx">
					<view class="f1">总库存</view>
					<view class="f2"><input type="text" @input="gglistInput" :data-index="index" data-field="stock" :name="'stock['+index+']'" :value="item.stock" placeholder="请填写库存" placeholder-style="color:#888"></input></view>
				</view>
			<!-- 	<view class="form-item" style="height:80rpx;line-height:80rpx">
					<view class="f1">赠{{t('积分')}}(个)</view>
					<view class="f2"><input type="text" @input="gglistInput" :data-index="index" data-field="givescore" :name="'givescore['+index+']'" :value="item.givescore" placeholder="请填写赠送数量" placeholder-style="color:#888"></input></view>
				</view> -->
				<view class="form-item">
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
					<view class="f1">销量</view>
					<view class="f2"><input type="text" name="sales" :value="info.sales" placeholder="请填写销量" placeholder-style="color:#888"></input></view>
					<input type="text" hidden="true" name="oldsales" :value="info.id?info.sales:'0'">
				</view>
				<view class="form-item">
					<view class="f1">每人限购</view>
					<view class="f2"><input type="text" name="limit_per" :value="info.limit_per" placeholder="0表示不限购" placeholder-style="color:#888"></input></view>
				</view>
				<view class="form-item">
					<view class="f1">序号</view>
					<view class="f2"><input type="text" name="sort" :value="info.sort" placeholder="用于排序,越大越靠前" placeholder-style="color:#888"></input></view>
				</view>
			</view>
			<view class="form-box" v-if="product_showset==1">
				<view class="form-item flex-col">
					<view class="f1">显示条件</view>
					<view class="f2" style="line-height:30px">
						<checkbox-group class="radio-group" name="showtj" >
							<label><checkbox value="-1" :checked="inArray('-1',showtjArr)?true:false"></checkbox> 所有人</label> 
							<label><checkbox value="-2" :checked="inArray('-2',showtjArr)?true:false"></checkbox> 未登录用户</label> 
							<label><checkbox value="0" :checked="inArray('0',showtjArr)?true:false"></checkbox> 关注用户</label>
							<label v-for="item in levellist" :key="item.id"><checkbox :value="''+item.id" :checked="inArray(item.id,showtjArr)?true:false"></checkbox> {{item.name}}</label>
						</checkbox-group>
					</view>
				</view>
			</view>
			<view class="form-box">
				<view class="form-item">
					<view>状态<text style="color:red"> *</text></view>
					<view>
						<radio-group class="radio-group" name="status" @change="bindStatusChange">
							<label><radio value="1" :checked="info.status==1?true:false"></radio> 上架</label> 
							<label><radio value="0" :checked="!info || info.status==0?true:false"></radio> 下架</label>
							<label><radio value="2" :checked="info.status==2?true:false"></radio> 上架时间</label>
							<label><radio value="3" :checked="info.status==3?true:false"></radio> 上架周期</label>
						</radio-group>
					</view>
				</view>
				<view class="form-item flex-col" v-if="info.status==2">
					<view class="f1">上架时间</view>
					<view class="f2" style="line-height:30px">
						 <picker mode="date" :value="start_time1" @change="bindStartTime1Change">
               <view class="picker">{{start_time1}}</view>
             </picker>
						 <picker mode="time" :value="start_time2" @change="bindStartTime2Change">
               <view class="picker" style="padding-left:10rpx">{{start_time2}}</view>
             </picker>
						 <view style="padding:0 10rpx;color:#222;font-weight:bold">到</view>
						 <picker mode="date" :value="end_time1" @change="bindEndTime1Change">
               <view class="picker">{{end_time1}}</view>
             </picker>
						 <picker mode="time" :value="end_time2" @change="bindEndTime2Change">
               <view class="picker" style="padding-left:10rpx">{{end_time2}}</view>
             </picker>
					</view>
				</view>
				<view class="form-item flex-col" v-if="info.status==3">
					<view class="f1">上架周期</view>
					<view class="f2" style="line-height:30px">
						 <picker mode="time" :value="start_hours" @change="bindStartHoursChange">
               <view class="picker">{{start_hours}}</view>
             </picker>
						 <view style="padding:0 10rpx;color:#222;font-weight:bold">到</view>
						 <picker mode="time" :value="end_hours" @change="bindEndHoursChange">
               <view class="picker">{{end_hours}}</view>
             </picker>
					</view>
				</view>
			</view>
			<view class="form-box">
				<view class="form-item flex-col">
					<text>菜品详情</text>
					<view class="detailop"><view class="btn" @tap="detailAddtxt">+文本</view><view class="btn" @tap="detailAddpic">+图片</view></view>
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
					<text class="popup__title-text">请选择菜品分类</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="changeClistDialog"/>
				</view>
				<view class="popup__content">
					<block v-for="(item, index) in clist" :key="item.id">
						<view class="clist-item" @tap="cidsChange" :data-id="item.id">
							<view class="flex1">{{item.name}}</view>
							<view class="radio" :style="inArray(item.id,cids) ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
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
		<view class="popup__container" v-if="arealistshow">
			<view class="popup__overlay" @tap.stop="changeArealistDialog"></view>
			<view class="popup__modal">
				<view class="popup__title">
					<text class="popup__title-text">请选择出餐区域</text>
					<image :src="pre_url+'/static/img/close.png'" class="popup__close" style="width:36rpx;height:36rpx" @tap.stop="changeArealistDialog"/>
				</view>
				<view class="popup__content">
					<block v-for="(item, index) in arealist" :key="item.id">
						<view class="clist-item" @tap="areaChange" :data-id="item.id" :data-name="item.name">
							<view class="flex1">{{item.name}}</view>
							<view class="radio" :style="item.id == area_id ? 'background:'+t('color1')+';border:0' : ''"><image class="radio-img" :src="pre_url+'/static/img/checkd.png'"/></view>
						</view>
					</block>
				</view>
			</view>
		</view>
	</block>
	<loading v-if="loading"></loading>
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
			groupArr:[],
			freighttypeArr:['全部模板','指定模板','自动发货','在线卡密'],
			freightindex:0,
			freightdata:[],
			freightIds:[],
			guigedata:[],
			pic:[],
			pics:[],
			cids:[],
			gids:[],
			cnames:'',
			gnames:'',
			clistshow:false,
			glistshow:false,
			ggname:'',
			ggindex:0,
			ggindex2:0,
			oldgglist:[],
			gglist:[],
			catche_detailtxt:'',
			start_time1:'-选择日期-',
			start_time2:'-选择时间-',
			end_time1:'-选择日期-',
			end_time2:'-选择时间-',
			start_hours:'-开始时间-',
			end_hours:'-结束时间-',
			gettjArr:['-1'],
			product_showset:0,
			showtjArr:['-1'],
			arealistshow:false,
			arealist:[],
			area_id:0,
			area_name:'',
			show_area_list:false
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
			app.get('ApiAdminRestaurantProduct/edit',{id:id}, function (res) {
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
				that.pagecontent = res.pagecontent;
				that.aglevellist = res.aglevellist;
				that.levellist = res.levellist;
				that.oldgglist = res.newgglist;
				that.clist = res.clist;
				that.cateArr = res.cateArr;
				that.groupArr = res.groupArr;
				that.pic = res.pic;
				that.pics = res.pics;
				that.cids = res.cids;
				that.gids = res.gids;
				that.guigedata = res.guigedata;
				that.product_showset = res.product_showset;
				that.showtjArr = that.info.showtj
				that.arealist = res.arealist;
				that.show_area_list = res.show_area_list;
				that.getcnames();
				that.getgnames();
				that.getgglist();
				that.loaded();
			});
		},
    subform: function (e) {
      var that = this;
      var formdata = e.detail.value;
      formdata.cid = that.cids.join(',');
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
			
			if(formdata.status==2){
				formdata.start_time = that.start_time1 + ' '+that.start_time2;
				formdata.end_time = that.end_time1 + ' '+that.end_time2;
			}
			if(formdata.status==3){
				formdata.start_hours = that.start_hours;
				formdata.end_hours = that.end_hours;
			}
			 formdata.area_id = that.area_id;	
      var id = that.opt.id ? that.opt.id : '';
      app.post('ApiAdminRestaurantProduct/save', {id:id,info:formdata,guigedata:guigedata,gglist:that.gglist,pagecontent:that.pagecontent}, function (res) {
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
		detailMoveup:function(e){
			var index = e.currentTarget.dataset.index;
			var pagecontent = this.pagecontent;
			pagecontent[index] = pagecontent.splice(index-1, 1, pagecontent[index])[0];
		},
		detailMovedown:function(e){
			var index = e.currentTarget.dataset.index;
			var pagecontent = this.pagecontent;
			pagecontent[index] = pagecontent.splice(index+1, 1, pagecontent[index])[0];
		},
		detailMovedel:function(e){
			var index = e.currentTarget.dataset.index;
			var pagecontent = this.pagecontent;
			pagecontent.splice(index,1);
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
			var rowspans = new Array(len);
			for(var i=0;i<len;i++){
				var itemlen = guigedata[i].items.length;
				if(itemlen<=0) { itemlen = 1 };
				newlen*=itemlen;
				h[i] = new Array(newlen);
				for(var j=0;j<newlen;j++){
					h[i][j] = new Array();
				}
        var l = guigedata[i].items.length;
        rowspans[i] = 1;
        for(j=i+1;j<len;j++){
          rowspans[i]*= guigedata[j].items.length;
        }
			}
			for(var m=0;m<len;m++){
				var k = 0,kid = 0,n=0;
				for(var j=0;j<newlen;j++){
          var rowspan = rowspans[m];
          
					h[m][j]={ k:guigedata[m].items[kid].k,title:guigedata[m].items[kid].title,id: guigedata[m].items[kid].id};
					
          n++;
          if(n==rowspan){
            kid++; if(kid>guigedata[m].items.length-1) { kid=0; }
            n=0;
          }
				}
			}
			for(var i=0;i<newlen;i++){
				var ks = [];
				var titles = [];
				for(var j=0;j<len;j++){
					ks.push( h[j][i].k);
					titles.push( h[j][i].title);
				}
				var ks2 =ks.join('_');
				ks =ks.join(',');
				titles =titles.join(',');
				if(typeof(oldgglist[ks])!='undefined'){
          oldgglist[ks].name = titles;
					var val = oldgglist[ks];
				}else{
					var val = { ks:ks,name:titles,market_price:'',cost_price:'',sell_price:'',stock:'1000',pic:'',givescore:'0',lvprice_data:null};
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
		gidsChange:function(e){
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
		changeArealistDialog:function(){
			this.arealistshow = !this.arealistshow
		},
		areaChange(e){
			var id= e.currentTarget.dataset.id;
			var name= e.currentTarget.dataset.name;
			this.area_id = id;
			this.area_name = name;
			this.arealistshow = false;
		}
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