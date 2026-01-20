<template>
<view>
	<block v-if="isload">
		<view class="banner" :style="{background:'linear-gradient(180deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0) 100%)'}">
		</view>
		<view class="user">
			<image :src="userinfo.headimg" background-size="cover"/>
			<view class="info" v-if="set && set.parent_show == 1">
				<view>
					<view class="nickname">{{userinfo.nickname}}</view>
					<view>{{t('推荐人')}}：{{userinfo.pid > 0 ? userinfo.pnickname : '无'}}</view>
				</view>
			</view>
			<view class="info" v-else>
				 <text class="nickname">{{userinfo.nickname}}</text>
			</view>
		</view>
		<view class="contentdata">
			<view class="data">
				<view class="data_title flex-y-center"><image class="data_icon" :src="pre_url+'/static/imgsrc/commission_m1.png'"/>我的{{t('佣金')}}</view>
				<view class="data_text">
					{{comwithdraw==1?'可提现':'剩余'}}{{t('佣金')}}<text v-if="showunit==1">({{getunit('佣金单位')}})</text>
				</view>
				<view class="data_price flex-y-center flex-bt">
					<text @tap="goto" data-url="../order/shoporder?st=0">{{userinfo.commission}}</text>
					<view @tap="goto" data-url="withdraw" v-if="comwithdraw==1" class="data_btn flex-xy-center" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">去提现<image :src="pre_url+'/static/imgsrc/commission_dw.png'"/></view>
					<view @tap="tomoney" v-else-if="commission2money=='1'" class="data_btn flex-xy-center" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">转到{{t('余额')}}账户<image :src="pre_url+'/static/imgsrc/commission_dw.png'"/></view>
				</view>
				<view class="flex-y-center flex-bt" v-if="set.yx_commission_to_lingqiantong && set.yx_commission_to_lingqiantong == 1">
					<text></text>					
					<view @tap="goto" data-url="/pagesD/commissionlingqiantong/index" class="data_btn flex-xy-center" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">转入{{t('零钱通')}}<image :src="pre_url+'/static/imgsrc/commission_dw.png'"/></view>
				</view>
				<view class="data_module flex flex-wp">
					<view @tap="goto" data-url="../order/shoporder?st=0" class="data_module_view">
						<view class="data_lable">已提现{{t('佣金')}}<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="show_now==1">{{count3}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('count3')">
							<block v-if="isNull(pagedata.count3)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.count3}}
							</block>
						</view>
					</view>
					<view @tap="goto" data-url="../order/shoporder?st=0" class="data_module_view">
						<view class="data_lable">待结算<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="show_now==1">{{is_end==1?userinfo.commission_yj:'计算中'}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('commission_yj')">
							<block v-if="isNull(pagedata.commission_yj)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.commission_yj}}
							</block>
						</view>
					</view>
					<view v-if="userinfo.show_totalcommission && userinfo.totalcommission > 0" @tap="goto" data-url="../order/shoporder?st=0" class="flex1">
						<view class="data_lable">累计总佣金</view>
						<view class="data_value">{{userinfo.totalcommission}}</view>
					</view>
					<view v-if="userinfo.show_teamfenhongyeji > 0" class="flex1">
						<view class="data_lable">团队业绩</view>
						<view class="data_value" v-if="show_now==1">{{userinfo.team_fenhong_yeji}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('team_fenhong_yeji')">
							<block v-if="isNull(pagedata.team_fenhong_yeji)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.team_fenhong_yeji}}
							</block>
						</view>
					</view>
          <view v-if="userinfo.forzengxcommissionshow" @tap="goto" data-url="/pagesC/my/forzengxcommissionlog" class="flex1">
          	<view class="data_lable">冻结{{t('佣金')}}</view>
          	<view class="data_value">{{userinfo.forzengxcommission}}</view>
          </view>
					<block v-if="!isNull(userinfo.show_commission_count) && userinfo.show_commission_count==1">
						<view class="flex1">
							<view class="data_lable">今日{{t('佣金')}}</view>
							<view class="data_value" v-if="show_now==1">{{is_end==1?userinfo.today_commission:'计算中'}}</view>
							<view class="data_value" v-else @tap.stop="qury_data('today_commission')">
								<block v-if="isNull(pagedata.today_commission)" >
									<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
								</block>
								<block v-else>
									{{pagedata.today_commission}}
								</block>
							</view>
						</view>
						<view class="flex1">
							<view class="data_lable">昨日{{t('佣金')}}</view>
							<view class="data_value" v-if="show_now==1">{{is_end==1?userinfo.yesterday_commission:'计算中'}}</view>
							<view class="data_value" v-else @tap.stop="qury_data('yesterday_commission')">
								<block v-if="isNull(pagedata.yesterday_commission)" >
									<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
								</block>
								<block v-else>
									{{pagedata.yesterday_commission}}
								</block>
							</view>
						</view>
						<view class="flex1">
							<view class="data_lable">本月{{t('佣金')}}</view>
							<view class="data_value"  v-if="show_now==1">{{is_end==1?userinfo.month_commission : '计算中'}}</view>
							<view class="data_value" v-else @tap.stop="qury_data('month_commission')">
								<block v-if="isNull(pagedata.month_commission)" >
									<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
								</block>
								<block v-else>
									{{pagedata.month_commission}}
								</block>
							</view>
						</view>
						<view class="flex1">
							<view class="data_lable">上月{{t('佣金')}}</view>
							<view class="data_value" v-if="show_now==1">{{is_end==1?userinfo.last_month_commission : '计算中'}}</view>
							<view class="data_value" v-else @tap.stop="qury_data('last_month_commission')">
								<block v-if="isNull(pagedata.last_month_commission)" >
									<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
								</block>
								<block v-else>
									{{pagedata.last_month_commission}}
								</block>
							</view>
						</view>
					</block>
				</view>
				<view  class="data_module flex" v-if="(userinfo.baodan_freeze && userinfo.baodan_freeze > 0) || (userinfo.zt_commission && userinfo.zt_commission > 0)">
					<view v-if="userinfo.baodan_freeze && userinfo.baodan_freeze > 0" @tap="goto" data-url="/pagesA/commission/baodanfreezelog" class="flex1">
						<view class="data_lable">报单冻结</view>
						<view class="data_value">{{userinfo.baodan_freeze}}</view>
					</view>
					<view v-if="userinfo.zt_commission && userinfo.zt_commission > 0" @tap="goto" data-url="/activity/commission/commissionlog?st=1" class="flex1">
						<view class="data_lable">直推奖</view>
						<view class="data_value">{{userinfo.zt_commission}}</view>
					</view>
				</view>
				
			</view>
			<view class="data" v-if="hasfenhong">
				<view class="data_title flex-y-center flex-bt">
					<view class="flex-y-center">
						<image class="data_icon" :src="pre_url+'/static/imgsrc/commission_m2.png'"/>
						<text>{{t('股东分红')}}</text>
					</view>
					<view @tap="goto" data-url="fenhong" class="data_detail flex-y-center">
						查看详情<image :src="pre_url+'/static/imgsrc/commission_db.png'"/>
					</view>
				</view>
				<view class="data_module flex">
					<view class="flex1">
						<view class="data_lable">累计分红<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value"  v-if="show_now==1">{{userinfo.fenhong}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('fenhong')">
							<block v-if="isNull(pagedata.fenhong)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.fenhong}}
							</block>
						</view>
					</view>
					<view class="flex1">
						<view class="data_lable">待结算<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="show_now==1">{{is_end==1?userinfo.fenhong_yj:'计算中'}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('fenhong_yj')">
							<block v-if="isNull(pagedata.fenhong_yj)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.fenhong_yj}}
							</block>
						</view>
					</view>
				</view>
				<view class="data_module flex" v-if="userinfo.fenhong_max_show">
					<view class="flex1">
						<view class="data_lable">已发分红<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="show_now==1">{{userinfo.gudong_total}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('gudong_total')">
							<block v-if="isNull(pagedata.gudong_total)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.gudong_total}}
							</block>
						</view>
					</view>
					<view class="flex1">
						<view class="data_lable">待分红<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="show_now==1">{{userinfo.gudong_remain}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('gudong_remain')">
							<block v-if="isNull(pagedata.gudong_remain)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.gudong_remain}}
							</block>
						</view>
					</view>
				</view>
				<view class="data_module flex" v-if="gongxianfenhong_show==1">
					<view class="flex1">
						<view class="data_lable">预计{{userinfo.gongxianfenhong_txt || '股东贡献量分红'}}<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="show_now==1">{{userinfo.gongxianfenhong}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('gongxianfenhong')">
							<block v-if="isNull(pagedata.gongxianfenhong)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.gongxianfenhong}}
							</block>
						</view>
					</view>
				</view>
				<view class="data_module flex" v-if="!isNull(userinfo.fenhong_max_show) && userinfo.fenhong_max_show==1">
					<view class="flex1">
						<view class="data_lable">总额度</view>
						<view class="data_value">{{userinfo.fenhong_max_add}}</view>
					</view>
					<view class="flex1">
						<view class="data_value" v-if="!isNull(userinfo.fenhongmax_to_score) && userinfo.fenhongmax_to_score==1">
							<view @tap="goto" style="width: 200rpx;" data-url="/pagesC/my/fenhongmaxToScore" class="data_btn flex-xy-center" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">
								转{{t('积分')}}<image :src="pre_url+'/static/imgsrc/commission_dw.png'"/>
							</view>
						</view>
						<view class="data_value" v-if="!isNull(userinfo.fenhongmax_to_gupiao) && userinfo.fenhongmax_to_gupiao==1">
							<view @tap="goto" style="width: 200rpx;" :data-url="userinfo.fenhongmax_to_gupiao_url" class="data_btn flex-xy-center" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}">
								兑换股票<image :src="pre_url+'/static/imgsrc/commission_dw.png'"/>
							</view>
						</view>
					</view>
				</view>
			</view>

      <view class="data" v-if="userinfo.fenhong_max_status">
        <view class="data_title flex-y-center flex-bt">
          <view class="flex-y-center">
            <image class="data_icon" :src="pre_url+'/static/imgsrc/commission_m2.png'"/>
            <text>{{t('股东分红')}}额度</text>
          </view>
        </view>
        <view class="data_module flex">
          <view class="flex1">
            <view class="data_lable">最大{{t('股东分红')}}额度</view>
            <view class="data_value" v-if="show_now==1">{{userinfo.fenhong_max}}</view>
			<view class="data_value" v-else @tap.stop="qury_data('fenhong_max')">
				<block v-if="isNull(pagedata.fenhong_max)" >
					<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
				</block>
				<block v-else>
					{{pagedata.fenhong_max}}
				</block>
			</view>
          </view>
          <view class="flex1">
            <view class="data_lable">已发{{t('股东分红')}}额度</view>
            <view class="data_value" v-if="show_now==1">{{userinfo.gudong_total}}</view>
			<view class="data_value" v-else @tap.stop="qury_data('gudong_total')">
				<block v-if="isNull(pagedata.gudong_total)" >
					<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
				</block>
				<block v-else>
					{{pagedata.gudong_total}}
				</block>
			</view>
          </view>
        </view>
      </view>

			<view class="data" v-if="hasfenhong_huiben">
				<view class="data_title flex-y-center flex-bt">
					<view class="flex-y-center">
						<image class="data_icon" :src="pre_url+'/static/imgsrc/commission_m2.png'"/>
						<text>{{t('回本股东分红')}}</text>
					</view>
					<view @tap="goto" data-url="/pagesA/commission/fenhong_huiben" class="data_detail flex-y-center">
						查看详情<image :src="pre_url+'/static/imgsrc/commission_db.png'"/>
					</view>
				</view>
				<view class="data_module flex">
					<view class="flex1">
						<view class="data_lable">累计分红<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="show_now==1">{{userinfo.fenhong_huiben}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('fenhong_huiben')">
							<block v-if="isNull(pagedata.fenhong_huiben)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.fenhong_huiben}}
							</block>
						</view>
					</view>
					<view class="flex1">
						<view class="data_lable">待结算<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" @tap="goto" data-url="/pagesA/commission/fenhong_huiben">查看</view>
					</view>
				</view>
			</view>
			
			<view class="data" v-if="hasteamfenhong && set.teamfenhong_show">
				<view class="data_title flex-y-center flex-bt">
					<view class="flex-y-center">
						<image class="data_icon" :src="pre_url+'/static/imgsrc/commission_m2.png'"/>
						<text>{{t('团队分红')}}</text>
					</view>
					<view @tap="goto" data-url="teamfenhong" class="data_detail flex-y-center">
						查看详情<image :src="pre_url+'/static/imgsrc/commission_db.png'"/>
					</view>
				</view>
				<view class="data_module flex">
					<view class="flex1">
						<view class="data_lable">累计分红<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="show_now==1">{{userinfo.teamfenhong}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('teamfenhong')">
							<block v-if="isNull(pagedata.teamfenhong)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.teamfenhong}}
							</block>
						</view>
					</view>
					<view class="flex1">
						<view class="data_lable">待结算<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="show_now==1">{{is_end==1?userinfo.teamfenhong_yj:'计算中'}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('teamfenhong_yj')">
							<block v-if="isNull(pagedata.teamfenhong_yj)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.teamfenhong_yj}}
							</block>
						</view>
					</view>
				</view>
			</view>
			<view class="data" v-if="hasbusinessteamfenhong && set.business_teamfenhong_show">
				<view class="data_title flex-y-center flex-bt">
					<view class="flex-y-center">
						<image class="data_icon" :src="pre_url+'/static/imgsrc/commission_m2.png'"/>
						<text>{{t('商家团队分红')}}</text>
					</view>
					<view @tap="goto" data-url="/pagesA/commission/business_teamfenhong" class="data_detail flex-y-center">
						查看详情<image :src="pre_url+'/static/imgsrc/commission_db.png'"/>
					</view>
				</view>
				<view class="data_module flex">
					<view class="flex1">
						<view class="data_lable">累计分红<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="show_now==1">{{userinfo.business_teamfenhong}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('business_teamfenhong')">
							<block v-if="isNull(pagedata.business_teamfenhong)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.business_teamfenhong}}
							</block>
						</view>
					</view>
					<view class="flex1">
						<view class="data_lable">待结算<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="show_now==1">{{is_end==1?userinfo.business_teamfenhong_yj:'计算中'}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('business_teamfenhong_yj')">
							<block v-if="isNull(pagedata.business_teamfenhong_yj)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.business_teamfenhong_yj}}
							</block>
						</view>
					</view>
				</view>
			</view>
			<view class="data" v-if="hasteamshouyi && set.teamshouyi_show">
				<view class="data_title flex-y-center flex-bt">
					<view class="flex-y-center">
						<image class="data_icon" :src="pre_url+'/static/imgsrc/commission_m2.png'"/>
						<text>{{t('团队收益')}}</text>
					</view>
					<view @tap="goto" data-url="/pagesA/commission/teamshouyi" class="data_detail flex-y-center">
						查看详情<image :src="pre_url+'/static/imgsrc/commission_db.png'"/>
					</view>
				</view>
				<view class="data_module flex">
					<view class="flex1">
						<view class="data_lable">累计收益<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="show_now==1">{{userinfo.teamshouyi}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('teamshouyi')">
							<block v-if="isNull(pagedata.teamshouyi)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.teamshouyi}}
							</block>
						</view>
					</view>
				</view>
			</view>
			
			<view class="data" v-if="hasareafenhong">
				<view class="data_title flex-y-center flex-bt">
					<view class="flex-y-center">
						<image class="data_icon" :src="pre_url+'/static/imgsrc/commission_m2.png'"/>
						<text>{{t('区域代理分红')}}</text>
					</view>
					<view @tap="goto" data-url="areafenhong" class="data_detail flex-y-center">
						查看详情<image :src="pre_url+'/static/imgsrc/commission_db.png'"/>
					</view>
				</view>
				<view class="data_module flex">
					<view class="flex1">
						<view class="data_lable">累计分红<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="show_now==1">{{userinfo.areafenhong}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('areafenhong')">
							<block v-if="isNull(pagedata.areafenhong)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.areafenhong}}
							</block>
						</view>
					</view>
					<view class="flex1">
						<view class="data_lable">待结算<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="show_now==1">{{is_end==1?userinfo.areafenhong_yj:'计算中'}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('areafenhong_yj')">
							<block v-if="isNull(pagedata.areafenhong_yj)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.areafenhong_yj}}
							</block>
						</view>
					</view>
				</view>
			</view>
			
			<view class="data" v-if="set.show_myyeji == 1">
				<view class="data_title flex-y-center flex-bt">
					<view class="flex-y-center">
						<image class="data_icon" :src="pre_url+'/static/imgsrc/commission_m2.png'"/>
						<text>我的业绩</text>
					</view>
				</view>
				<view class="data_module flex" @tap="goto" data-url="/pagesExt/commission/myyeji">
					<view class="flex1">
						<view class="data_lable">累计<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value">{{userinfo.buymoney}}</view>
					</view>
					<view class="flex1">
						<view class="data_lable">本月<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="show_now==1">{{userinfo.buymoney_thismonth}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('buymoney_thismonth')">
							<block v-if="isNull(pagedata.buymoney_thismonth)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.buymoney_thismonth}}
							</block>
						</view>
					</view>
					<view class="flex1" v-if="userinfo.showyejicommission && userinfo.yeji_commission > 0">
						<view class="data_lable">业绩奖<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="show_now==1">{{userinfo.yeji_commission}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('yeji_commission')">
							<block v-if="isNull(pagedata.yeji_commission)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.yeji_commission}}
							</block>
						</view>
					</view>
				</view>
			</view>
			<view class="data" v-if="userinfo.showxiaoshouyeji ">
				<view class="data_title flex-y-center flex-bt">
					<view class="flex-y-center">
						<image class="data_icon" :src="pre_url+'/static/imgsrc/commission_m2.png'"/>
						<text>销售业绩</text>
					</view>
				</view>
				<view class="data_module flex" >
					<view class="flex1">
						<view class="data_lable">总业绩<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="show_now==1" style="font-size: 38rpx;">{{userinfo.totalyeji}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('totalyeji')">
							<block v-if="isNull(pagedata.totalyeji)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.totalyeji}}
							</block>
						</view>
					</view>
					<view class="flex1">
						<view class="data_lable">销售额<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="show_now==1" style="font-size: 38rpx;">{{userinfo.coupon_yeji}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('coupon_yeji')">
							<block v-if="isNull(pagedata.coupon_yeji)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.coupon_yeji}}
							</block>
						</view>
					</view>
					<view class="flex1">
						<view class="data_lable">收益<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="show_now==1" style="font-size: 38rpx;">{{userinfo.shouyi}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('shouyi')">
							<block v-if="isNull(pagedata.shouyi)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.shouyi}}
							</block>
						</view>
					</view>
					<view class="flex1" v-if="userinfo.yeji_commission > 0">
						<view class="data_lable">业绩奖<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="show_now==1" style="font-size: 38rpx;">{{userinfo.yeji_commission}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('yeji_commission')">
							<block v-if="isNull(pagedata.yeji_commission)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.yeji_commission}}
							</block>
						</view>
					</view>
				</view>
			</view>
			
			<view class="data" v-if="hasteamfenhong && (teamnum_show==1 || teamyeji_show==1)">
				<view class="data_title flex-y-center flex-bt">
					<view class="flex-y-center">
						<image class="data_icon" :src="pre_url+'/static/imgsrc/commission_m3.png'"></image>
						<text>{{t('我的团队')}}</text>
					</view>
					<view @tap="goto" data-url="myteam" class="data_detail flex-y-center">
						查看详情<image :src="pre_url+'/static/imgsrc/commission_db.png'"/>
					</view>
				</view>
				<view class="data_module flex flex-wp">
					<view class="flex1" v-if="teamnum_show==1">
						<view class="data_lable">团队人数</view>
						<view class="data_value">{{userinfo.teamnum}}</view>
					</view>
					<view class="flex1" v-if="teamyeji_show==1">
						<view class="data_lable">团队单量</view>
						<view class="data_value">{{userinfo.teamOrderCount}}</view>
					</view>
					<view class="flex1" v-if="teamyeji_show==1 && userinfo.hasOwnProperty('teamyeji_prosum')">
						<view class="data_lable">团队件数</view>
						<view class="data_value">{{userinfo.teamyeji_prosum}}</view>
					</view>
					<view class="flex1" v-if="teamyeji_show==1">
						<view class="data_lable">团队业绩<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="set.show_teamyeji_search == 1" @tap="goto" data-url="/pagesExt/commission/teamyeji">{{userinfo.teamyeji}}</view>
						<view class="data_value" v-else>{{userinfo.teamyeji}}</view>
					</view>
					<view class="flex1" v-if="set.show_teamyeji_search==1">
						<view class="data_lable">本月<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" @tap="goto" data-url="/pagesExt/commission/teamyeji">{{userinfo.teamyeji_thismonth}}</view>
					</view>
				</view>
			</view>
			<view class="data" v-if="hastouzifenhong">
				<view class="data_title flex-y-center flex-bt">
					<view class="flex-y-center">
						<image class="data_icon" :src="pre_url+'/static/imgsrc/commission_m2.png'"/>
						<text>{{t('投资分红')}}</text>
					</view>
					<view @tap="goto" data-url="touzifenhong" class="data_detail flex-y-center">
						查看详情<image :src="pre_url+'/static/imgsrc/commission_db.png'"/>
					</view>
				</view>
				<view class="data_module flex">
					<view class="flex1">
						<view class="data_lable">投资金额<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value">{{userinfo.touzimoney}}</view>
					</view>
					<view class="flex1">
						<view class="data_lable">累计分红<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="show_now==1">{{userinfo.touzifenhong}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('touzifenhong')">
							<block v-if="isNull(pagedata.touzifenhong)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.touzifenhong}}
							</block>
						</view>
					</view>
					
					<view class="flex1">
						<view class="data_lable">待结算<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="show_now==1">{{userinfo.touzifenhong_yj}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('touzifenhong_yj')">
							<block v-if="isNull(pagedata.touzifenhong_yj)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.touzifenhong_yj}}
							</block>
						</view>
					</view>
				</view>
			</view>
			<view class="data" v-if="commission_butie">
				<view class="data_title flex-y-center flex-bt">
					<view class="flex-y-center">
						<image class="data_icon" :src="pre_url+'/static/imgsrc/commission_m2.png'"/>
						<text>{{t('分销补贴')}}</text>
					</view>
					<view @tap="goto" data-url="/pagesA/commission/commissionbutie" class="data_detail flex-y-center">
						查看详情<image :src="pre_url+'/static/imgsrc/commission_db.png'"/>
					</view>
				</view>
				<view class="data_module flex">
					<view class="flex1">
						<view class="data_lable">待发放<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="show_now==1">{{userinfo.butie_total}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('butie_total')">
							<block v-if="isNull(pagedata.butie_total)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.butie_total}}
							</block>
						</view>
					</view>
					<view class="flex1">
						<view class="data_lable">已发放<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="show_now==1">{{userinfo.butie_send}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('butie_send')">
							<block v-if="isNull(pagedata.butie_send)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.butie_send}}
							</block>
						</view>
					</view>
				</view>
			</view>
			
			<view class="data" v-if="userinfo.show_team_yeji_fenhong">
				<view class="data_title flex-y-center flex-bt">
					<view class="flex-y-center">
						<image class="data_icon" :src="pre_url+'/static/imgsrc/commission_m2.png'"/>
						<text>{{t('团队业绩阶梯奖')}}</text>
					</view>
					<view @tap="goto" data-url="/pagesB/teamsaleyeji/fhorderlist" class="data_detail flex-y-center">
						查看详情<image :src="pre_url+'/static/imgsrc/commission_db.png'"/>
					</view>
				</view>
				<view class="data_module flex">
					<view class="flex1">
						<view class="data_lable">累计分红<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value">{{userinfo.total_team_yeji_fenhong}}</view>
					</view>
					<view class="flex1" v-if="!isNull(userinfo.show_team_yeji_fenhong_yj) && userinfo.show_team_yeji_fenhong_yj==1">
						<view class="data_lable">待结算<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="show_now==1">{{userinfo.team_yeji_fenhong_yj}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('team_yeji_fenhong_yj')">
							<block v-if="isNull(pagedata.team_yeji_fenhong_yj)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.team_yeji_fenhong_yj}}
							</block>
						</view>
					</view>
				</view>
				<view class="data_module flex" v-if="!isNull(userinfo.show_team_yeji_fenhong_jc) && userinfo.show_team_yeji_fenhong_jc==1">
					<view class="flex1">
						<view class="data_lable">当月新增业绩<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="show_now==1">{{userinfo.team_yeji_month_addyeji}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('team_yeji_month_addyeji')">
							<block v-if="isNull(pagedata.team_yeji_month_addyeji)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.team_yeji_month_addyeji}}
							</block>
						</view>
					</view>
					<view class="flex1">
						<view class="data_lable">总业绩<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="show_now==1">{{userinfo.team_yeji_total_addyeji}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('team_yeji_total_addyeji')">
							<block v-if="isNull(pagedata.team_yeji_total_addyeji)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.team_yeji_total_addyeji}}
							</block>
						</view>
					</view>
				</view>
			</view>
			<view class="data" v-if="userinfo.show_teamfenhong_freight">
				<view class="data_title flex-y-center flex-bt">
					<view class="flex-y-center">
						<image class="data_icon" :src="pre_url+'/static/imgsrc/commission_m2.png'"/>
						<text>运费补贴</text>
					</view>
					<view @tap="goto" data-url="/pagesC/commission/teamfenhongfreight" class="data_detail flex-y-center">
						查看详情<image :src="pre_url+'/static/imgsrc/commission_db.png'"/>
					</view>
				</view>
				<view class="data_module flex">
					<view class="flex1">
						<view class="data_lable">累计分红<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="show_now==1">{{userinfo.teamfenhong_freight}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('teamfenhong_freight')">
							<block v-if="isNull(pagedata.teamfenhong_freight)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.teamfenhong_freight}}
							</block>
						</view>
					</view>
					<view class="flex1">
						<view class="data_lable">待结算<text v-if="showunit==1">({{getunit('佣金单位')}})</text></view>
						<view class="data_value" v-if="show_now==1">{{userinfo.teamfenhong_freight_yj}}</view>
						<view class="data_value" v-else @tap.stop="qury_data('teamfenhong_freight_yj')">
							<block v-if="isNull(pagedata.teamfenhong_freight_yj)" >
								<image class="query-img" :src="pre_url+'/static/img/icon-refresh.png'" mo />
							</block>
							<block v-else>
								{{pagedata.teamfenhong_freight_yj}}
							</block>
						</view>
					</view>
				</view>
			</view>
			<view class="list">
				<view class="item" @tap="goto" data-url="poster">
					<image :src="pre_url+'/static/imgsrc/commission_i4.png'"></image>
					<view class="flex1">
						<view class="title">
							分享海报
						</view>
						<view class="text">
							邀请好友享收益
						</view>
					</view>
				</view>
				<view class="item" @tap="tomoney" v-if="comwithdraw==1 && commission2money=='1'">
					<image :src="pre_url+'/static/imgsrc/commission_i1.png'"></image>
					<view class="flex1">
						<view class="title">
							{{t('佣金')}}转{{t('余额')}}
						</view>
						<view class="text">
							直接到账
						</view>
					</view>
				</view>
				<view class="item" @tap="goto" data-url="downorder" v-if="set.fxorder_show == 1">
					<image :src="pre_url+'/static/imgsrc/commission_i3.png'"></image>
					<view class="flex1">
						<view class="title">
							{{t('分销订单')}}
						</view>
						<view class="text">
							查看订单
						</view>
					</view>
				</view>
				<view class="item" @tap="goto" data-url="commissionlog" v-if="set.commissionlog_show">
					<image :src="pre_url+'/static/imgsrc/commission_i3.png'"></image>
					<view class="flex1">
						<view class="title">
							{{t('佣金')}}明细
						</view>
						<view class="text">
							查看明细
						</view>
					</view>
				</view>
				<view class="item" @tap="goto" data-url="commissionrecord" v-if="set.commissionrecord_show">
					<image :src="pre_url+'/static/imgsrc/commission_i3.png'"></image>
					<view class="flex1">
						<view class="title">
							{{t('佣金')}}记录
						</view>
						<view class="text">
							查看记录
						</view>
					</view>
				</view>
				<view class="item" @tap="goto" data-url="fhorder" v-if="showfenhong && set.fhorder_show">
					<image :src="pre_url+'/static/imgsrc/commission_i3.png'"></image>
					<view class="flex1">
						<view class="title">
							分红订单
						</view>
						<view class="text">
							查看订单
						</view>
					</view>
				</view>
				<view class="item" @tap="goto" data-url="fhlog" v-if="showfenhong && set.fhlog_show">
					<image :src="pre_url+'/static/imgsrc/commission_i3.png'"></image>
					<view class="flex1">
						<view class="title">
							分红记录
						</view>
						<view class="text">
							查看记录
						</view>
					</view>
				</view>
				<view class="item" @tap="goto" data-url="/pagesExt/commission/tjbusinessList" v-if="set.tjbusiness_show">
					<image :src="pre_url+'/static/imgsrc/commission_i3.png'"></image>
					<view class="flex1">
						<view class="title">
							推荐商家
						</view>
						<view class="text">
							查看记录
						</view>
					</view>
				</view>
				<view class="item" @tap="goto" data-url="orderMendian" v-if="showMendianOrder">
					<image :src="pre_url+'/static/imgsrc/commission_i3.png'"></image>
					<view class="flex1">
						<view class="title">
							服务订单
						</view>
						<view class="text">
							查看订单
						</view>
					</view>
				</view>
				<view class="item" @tap="goto" data-url="commissionlogMendian" v-if="showMendianOrder">
					<image :src="pre_url+'/static/imgsrc/commission_i4.png'"></image>
					<view class="flex1">
						<view class="title">
							服务{{t('佣金')}}
						</view>
						<view class="text">
							查看记录
						</view>
					</view>
				</view>
				<view class="item" @tap="goto" data-url="orderYeji" v-if="showYeji">
					<image :src="pre_url+'/static/imgsrc/commission_i3.png'"></image>
					<view class="flex1">
						<view class="title">
							业绩统计
						</view>
						<view class="text">
							查看业绩
						</view>
					</view>
				</view>
				<view class="item" @tap="goto" data-url="/pagesExt/agent/cardEdit" v-if="set && set.agent_card == 1">
					<image :src="pre_url+'/static/imgsrc/commission_i10.png'"></image>
					<view class="flex1">
						<view class="title">
							代理卡片
						</view>
						<view class="text">
							查看代理信息
						</view>
					</view>
				</view>
				<view class="item" @tap="goto" data-url="/pagesA/agent/priceRate" v-if="set && set.product_price_rate == 1">
					<image :src="pre_url+'/static/imgsrc/commission_i10.png'"></image>
					<view class="flex1">
						<view class="title">
							价格倍率
						</view>
						<view class="text">
							修改商品价格倍率
						</view>
					</view>
				</view>
				<view class="item" @tap="goto" data-url="/pagesA/agent/memberPriceRate" v-if="set && set.member_level_price_rate == 1">
					<image :src="pre_url+'/static/imgsrc/commission_i10.png'"></image>
					<view class="flex1">
						<view class="title">
							价格倍率
						</view>
						<view class="text">
							修改会员等级倍率
						</view>
					</view>
				</view>
				<view class="item" @tap="goto" data-url="/pagesC/commission/tobusinessmoney" v-if="set && set.tobusinessmoney">
					<image :src="pre_url+'/static/imgsrc/commission_i1.png'"></image>
					<view class="flex1">
						<view class="title">
							{{t('佣金')}}转商户
						</view>
						<view class="text">
							直接到账
						</view>
					</view>
				</view>
        
				<view class="item" @tap="goto" data-url="/pagesD/businessShareholder/orderlog" v-if="userinfo && userinfo.showShareholder">
					<image :src="pre_url+'/static/imgsrc/commission_i1.png'"></image>
					<view class="flex1">
						<view class="title">
							共享股东金额
						</view>
						<view class="text">
							查看订单明细
						</view>
					</view>
				</view>
				<view class="item" @tap="goto" data-url="/pagesD/commissionlingqiantong/order" v-if="set.yx_commission_to_lingqiantong && set.yx_commission_to_lingqiantong == 1">
					<image :src="pre_url+'/static/imgsrc/commission_i10.png'"></image>
					<view class="flex1">
						<view class="title">
							{{t('零钱通')}}
						</view>
						<view class="text">
							查看收益明细
						</view>
					</view>
				</view>
			</view>
		</view>
		<view style="width:100%;height:20rpx"></view>
		
		<uni-popup id="dialogInput" ref="dialogInput" type="dialog">
			<uni-popup-dialog mode="input" :title="t('佣金') + '转' + t('余额')+commission_to_money_rate" value="" placeholder="请输入转入金额" @confirm="tomonenyconfirm"></uni-popup-dialog>
		</uni-popup>

		<view v-if="showxieyi" class="xieyibox">
			<view class="xieyibox-content">
				<view style="overflow:scroll;height:100%;">
					<parse :content="xycontent" @navigate="navigate"></parse>
				</view>
				<view style="position:absolute;z-index:9999;bottom:10px;left:0;right:0;margin:0 auto;text-align:center; width: 50%;height: 60rpx; line-height: 60rpx; color: #fff; border-radius: 8rpx;" :style="{background:'linear-gradient(90deg,'+t('color1')+' 0%,rgba('+t('color1rgb')+',0.8) 100%)'}"  @tap="hidexieyi">已阅读并同意</view>
			</view>
		</view>

	</block>
	<loading v-if="loading"></loading>
	<dp-tabbar :opt="opt"></dp-tabbar>
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
			
      hiddenmodalput: true,
      userinfo: [],
      count3: 0,
      comwithdraw: 0,
      canwithdraw: true,
      money: 0,
      commission2money: "",
			showfenhong:false,
			showMendianOrder:false,
			hastouzifenhong:false,
			hasfenhong:false,
			hasareafenhong:false,
			hasteamfenhong:false,
			showYeji:false,
			fxjiesuantime:0,
			teamyeji_show:0,
			teamnum_show:0,
			gongxianfenhong_show:0,
			set:{},
			hasteamshouyi:false,
			commission_money_exchange_num:0,
			hasfenhong_huiben:false,
			hasbusinessteamfenhong:false,
			commission_butie:false,
			showxieyi:false,
			xycontent:'',
			fhtype_arr:[],
			is_end:0,
      commission_to_money_rate:'',
	  showunit:1,//是否显示佣金单位
	  show_now:1,//0需要点击每个数据才加载 1进入页面就加载
	  pagedata:{}
    };
  },

  onLoad: function (opt) {
		this.opt = app.getopts(opt);
		var that = this;
		this.getdata();
  },
	onPullDownRefresh: function () {
		this.getdata();
	},
  methods: {
		getdata: function () {
			var that = this;
			that.loading = true;
			app.get('ApiAgent/commissionSurvey', {}, function (res) {
				that.loading = false;
				uni.setNavigationBarTitle({
					title: '我的' + that.t('佣金')
				});
				uni.setNavigationBarColor({
					frontColor: '#ffffff', 
					backgroundColor: that.t('color1') 
				});
				that.userinfo = res.userinfo;
				that.set = res.set;
				that.count3 = res.count3;
				that.comwithdraw = res.comwithdraw;
				that.commission2money = res.commission2money;
				that.showfenhong = res.showfenhong;
				that.showMendianOrder = res.showMendianOrder;
				that.hastouzifenhong = res.hastouzifenhong;
				that.hasfenhong = res.hasfenhong;
				that.hasfenhong_huiben = res.hasfenhong_huiben;
				that.hasareafenhong = res.hasareafenhong;
				that.hasteamfenhong = res.hasteamfenhong;
				that.showYeji = res.hasYeji;
				that.fxjiesuantime = res.fxjiesuantime;
				that.teamyeji_show = res.teamyeji_show;
				that.teamnum_show = res.teamnum_show;
				that.gongxianfenhong_show = res.gongxianfenhong_show;
				that.hasteamshouyi = res.hasteamshouyi;
				that.commission_money_exchange_num = res.commission_money_exchange_num;
				that.hasbusinessteamfenhong = res.hasbusinessteamfenhong;
				that.commission_butie = res.commission_butie;
				that.fhtype_arr = res.fhtype_arr;
				that.commission_to_money_rate = res.commission_to_money_rate;
				that.showunit = res.showunit;
				that.show_now = res.show_now;
				that.loaded();
				//异步请求接口获取分红预收益数据
				that.sequentialRequests();
				if(res.uplv_agree == 1){
					that.showxieyi = true;
					that.xycontent = res.agree_content;
				}else{
					that.showxieyi = false;
				}

			});
		},
		// 异步函数
		sequentialRequests:async function() {
			var that = this;
			var fhtype_arr = that.fhtype_arr;
			for (let i = 0; i < fhtype_arr.length; i++) {
			  console.log(fhtype_arr[i]);
			  await that.getdata2(fhtype_arr[i]);
			}
			var show_commission_count = that.userinfo.show_commission_count || 0;
			if(show_commission_count){
				await that.getCommissionCount();
			}
			that.is_end = 1;
			console.log('请求完成'); // 处理响应
		},
		getdata2: function (fhtype) {
			var that = this;
			console.log('请求'+fhtype);
			return new Promise((resolve, reject) => {
			   app.get('ApiAgent/get_fenhong', {fhtype:fhtype}, function (res) {
					var data = res.data
					var commission_yj = parseFloat(that.userinfo.commission_yj);
					if(fhtype=='gudong' && data){
						//股东分红
						that.userinfo.fenhong_yj = data.fenhong_yj;
					}
					if(fhtype=='huiben' && data){
						//股东回本分红
						that.userinfo.fenhong_yj_huiben = data.fenhong_yj;
					}
					if(fhtype=='team' && data){
						//团队分红
						that.userinfo.teamfenhong_yj = data.fenhong_yj;
					}
					if(fhtype=='area' && data){
						//区域分红
						that.userinfo.areafenhong_yj = data.fenhong_yj;
					}
					if(fhtype=='touzi' && data){
						//投资分红
						that.userinfo.touzifenhong_yj = data.fenhong_yj;
					}
					if(fhtype=='business_teamfenhong' && data){
						//投资分红
						that.userinfo.business_teamfenhong_yj = data.fenhong_yj;
					}
					if(data){
						that.userinfo.commission_yj = (parseFloat(commission_yj )+ parseFloat(data.fenhong_yj)).toFixed(2);
					}
					resolve(data);
				});
			   
			});
		},
		getCommissionCount: function () {
			var that = this;
			console.log('请求佣金统计');
			return new Promise((resolve, reject) => {
			   app.get('ApiAgent/getCommissionCount', {}, function (res) {
					that.userinfo.today_commission = res.today_commission;
					that.userinfo.yesterday_commission = res.yesterday_commission;
					that.userinfo.month_commission = res.month_commission;
					that.userinfo.last_month_commission = res.last_month_commission;
					resolve(res);
				});
			   
			});
		},
    cancel: function () {
      this.hiddenmodalput = true;
    },
    tomoney: function () {
      this.$refs.dialogInput.open()
    },
    tomonenyconfirm: function (done, val) {
			console.log(val)
      var that = this;
      var money = val;
      if (money == '' || parseFloat(money) <= 0) {
        app.alert('请输入转入金额');
        return;
      }
      if (parseFloat(money) > this.userinfo.commission) {
        app.alert('可转入' + that.t('佣金') + '不足');
        return;
      }
			if(that.commission_money_exchange_num>0){
				var need_score = that.commission_money_exchange_num*money
				if(need_score>0){
					app.confirm('本次操作需要消耗' +need_score+ that.t('积分'),function(){
						done();
						that.exchangeSubmit(money)
					});
				}
			}else{
				done();
				that.exchangeSubmit(money)
			}
    },
		exchangeSubmit:function(money){
			var that = this;
			app.showLoading('提交中');
			app.post('ApiAgent/commission2money', {money: money}, function (data) {
				app.showLoading(false);
			  if (data.status == 0) {
			    app.error(data.msg);
			  } else {
			    that.hiddenmodalput = true;
			    app.success(data.msg);
			    setTimeout(function () {
			      that.getdata();
			    }, 1000);
			  }
			});
		},
		hidexieyi: function () {
			var that = this;
			app.goto('signature');
		},
		getunit:function(text=''){
			var rtext = this.t(text);
			if(rtext =='佣金单位' || rtext =='余额单位'){
				rtext = '元';
			}
			return rtext;
		},
		qury_data:function(field_name){
			var that = this;
			that.loading = true;
			app.get('ApiAgent/getCommissionPageData', {field_name:field_name}, function (res) {
				that.loading = false;
				var data = res.data;
				that.pagedata[field_name] = data[field_name] || '0.00';
			});
		}
  }
};
</script>
<style>
.banner{position: absolute;width: 100%;height: 900rpx;}
.user{ display:flex;width:100%;padding:40rpx 45rpx 0 45rpx;color:#fff;position:relative}
.user image{ width:80rpx;height:80rpx;border-radius:50%;margin-right:20rpx}
.user .info{display:flex;align-items: center;}
.user .info .nickname{font-size:32rpx;font-weight:bold;}
.user .set{ width:70rpx;height:100rpx;line-height:100rpx;font-size:40rpx;text-align:center}
.user .set image{width:50rpx;height:50rpx;border-radius:0}

.contentdata{display:flex;flex-direction:column;width:100%;padding:0 30rpx;position:relative;margin-bottom:20rpx}

.data{background:#fff;padding:30rpx;margin-top:30rpx;border-radius:16rpx}
.data_title{font-size: 28rpx;color: #333;font-weight: bold;}
.data_detail{font-size: 24rpx;font-family: Source Han Sans CN;font-weight: 400;color: #999999;font-weight: normal;}
.data_detail image{height: 24rpx;width: 24rpx;margin-left: 10rpx;}
.data_icon{height: 35rpx;width: 35rpx;margin-right: 15rpx;}
.data_text{font-size: 26;color: #999;margin-top: 60rpx;}
.data_price{font-size: 64rpx;color: #333;font-weight: bold;margin-top: 10rpx;}
.data_btn{height: 56rpx;padding: 0 30rpx;font-size: 24rpx;color: #fff;font-weight: normal;border-radius: 100rpx;}
.data_btn image{height: 24rpx;width: 24rpx;margin-left: 6rpx;}
.data_module{margin-top: 60rpx;width: 100%;}
.data_module .data_module_view{min-width: 50%;margin-bottom: 20rpx;}
.data_lable{font-size: 26;color: #999;}
.data_value{font-size: 44rpx;font-weight: bold;color: #333;margin-top: 10rpx;}

.list{ background: #fff;margin-top:30rpx;padding:30rpx;border-radius:16rpx;display: grid;grid-template-columns: repeat(2, 1fr);grid-column-gap: 10rpx;grid-row-gap: 50rpx;}
.list .item{ display:flex;align-items:center;}
.list image{ height: 72rpx;width: 72rpx;margin-right: 20rpx; }
.list .title{font-size: 28rpx;font-family: Source Han Sans CN;font-weight: 500;color: #121212;}
.list .text{font-size: 24rpx;font-family: Source Han Sans CN;font-weight: 400;color: #999999;margin-top: 10rpx;}

.xieyibox{width:100%;height:100%;position:fixed;top:0;left:0;z-index:99;background:rgba(0,0,0,0.7)}
.xieyibox-content{width:90%;margin:0 auto;height:80%;margin-top:10%;background:#fff;color:#333;padding:5px 10px 50px 10px;position:relative;border-radius:2px}

.query-img {width: 32rpx;height: 32rpx;}
.query-text{font-size: 28rpx;color: #007aff;}
</style>