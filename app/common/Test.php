<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <title>系统设置</title>
  <meta name="renderer" content="webkit">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, minimum-scale=1.0, maximum-scale=1.0, user-scalable=0">
  {include file="public/css"/}
</head>

<body>
	{if getcustom('loc_business')}
	<div class="layui-form-item">
		<label class="layui-form-label" style="width:130px">系统模式：</label>
		<div class="layui-input-inline" style="width:360px">
			<input type="radio" name="info[mode]" value="0" {if $info['mode']==0}checked{/if} title="商城模式" lay-skin="primary" lay-filter="">
			<input type="radio" name="info[mode]" value="1" {if $info['mode']==1}checked{/if} title="商户门店模式" lay-skin="primary" lay-filter="">
		</div>
		<div class="layui-form-mid layui-word-aux" style="clear:left;margin-left:160px">商城模式：传统在线商城；<br>商户门店模式：根据定位推荐最近商户；<br></div>
	</div>
	{/if}
  <div class="layui-fluid">
    <div class="layui-row layui-col-space15">
      <div class="layui-card layui-col-md12">
				<div class="layui-card-header"><i class="fa fa-cog"></i> 系统设置</div>
				<div class="layui-card-body" pad15>
					<div class="layui-form" lay-filter="">
						<div class="layui-tab layui-tab-brief" lay-filter="mytab">
							<ul class="layui-tab-title">
								<li class="layui-this" lay-id="1">基础设置</li>
								<li lay-id="4">财务设置</li>
								<li lay-id="5">积分设置</li>
								<li lay-id="6">分销分红</li>
								<li lay-id="7">文本自定义</li>
								<li lay-id="8">登录设置</li>
								<li lay-id="9">注册协议</li>
								<li lay-id="10">附件设置</li>
							</ul>
							<div class="layui-tab-content">
								<div class="layui-tab-item layui-show">
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">商家名称：</label>
										<div class="layui-input-inline">
											<input type="text" name="info[name]" class="layui-input" value="{$info['name']}">
										</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">商家LOGO：</label>
										<input type="hidden" name="info[logo]" id="logo" class="layui-input" value="{$info['logo']}">
										<button style="float:left;" type="button" class="layui-btn layui-btn-primary" onclick="uploader(this)" upload-input="logo" upload-preview="logoPreview">上传图片</button>
										<div class="layui-form-mid layui-word-aux" style="margin-left:10px;">建议尺寸：200×200像素</div>
										<div id="logoPreview" style="float:left;padding-top:10px;margin-left:160px;clear: both;">
											<div class="layui-imgbox" style="width:100px;"><div class="layui-imgbox-img"><img src="{$info['logo']}"/></div></div>
										</div>
									</div>
									{if getcustom('loc_business')}
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">系统模式：</label>
										<div class="layui-input-inline" style="width:360px">
											<input type="radio" name="info[mode]" value="0" {if $info['mode']==0}checked{/if} title="商城模式" lay-skin="primary" lay-filter="">
											<input type="radio" name="info[mode]" value="1" {if $info['mode']==1}checked{/if} title="商户门店模式" lay-skin="primary" lay-filter="">
										</div>
										<div class="layui-form-mid layui-word-aux" style="clear:left;margin-left:160px">商城模式：传统在线商城；<br>商户门店模式：根据定位推荐最近商户；<br></div>
									</div>
									{/if}
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">商家简介：</label>
										<div class="layui-input-inline" style="width:600px">
											<input type="text" name="info[desc]" value="{$info['desc']}" class="layui-input">
										</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">商家服务电话：</label>
										<div class="layui-input-inline">
											<input type="text" name="info[tel]" value="{$info['tel']}" class="layui-input">
										</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">商家地址：</label>
										<div class="layui-input-inline">
											<input type="text" name="info[address]" value="{$info['address']}" class="layui-input">
										</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">经纬度：</label>
										<div class="layui-input-inline" style="width:150px">
											<input type="text" name="info[longitude]" value="{$info['longitude']}" class="layui-input">
										</div>
										<div class="layui-form-mid">-</div>
										<div class="layui-input-inline" style="width:150px">
											<input type="text" name="info[latitude]" value="{$info['latitude']}" class="layui-input">
										</div>
										<button class="layui-btn layui-btn-primary" onclick="choosezuobiao()" style="float:left">选择坐标</button>
									</div>
									<!-- <div class="layui-form-item" {if !in_array('mp',$platform)}style="display:none"{/if}>
										<label class="layui-form-label" style="width:130px">客服系统链接：</label>
										<div class="layui-input-inline" style="width:300px">
											<input type="text" name="info[kfurl]" value="{$info['kfurl']}" class="layui-input">
										</div>
										<div class="layui-form-mid layui-word-aux" style="margin-left:10px;">商品详情页客服链接，不填写则使用商城内部客服系统</div>
									</div> -->

									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">客服系统链接：</label>
										<div class="layui-input-inline" style="width:300px">
											<input type="text" name="info[kfurl]" value="{$info.kfurl}" class="layui-input"/>
										</div>
										<div class="layui-form-mid layui-word-aux" style="clear:left;margin-left:160px">商品详情页客服链接，不填写则使用商城内部客服系统</div>
									</div>

									<div class="layui-form-item" {if !in_array('wx',$platform)}style="display:none"{/if}>
										<label class="layui-form-label" style="width:130px">微信小程序客服：</label>
										<div class="layui-input-inline" style="width:360px">
											<input type="radio" name="info[wxkf]" value="0" {if $info['wxkf']==0}checked{/if}  title="客服链接" lay-skin="primary" lay-filter="wxkfset">
											<input type="radio" name="info[wxkf]" value="1" {if $info['wxkf']==1}checked{/if} title="小程序客服" lay-skin="primary" lay-filter="wxkfset">
											<input type="radio" name="info[wxkf]" value="2" {if $info['wxkf']==2}checked{/if} title="微信客服" lay-skin="primary" lay-filter="wxkfset">
										</div>
										<div {if $info['wxkf']==1}style="display:none"{/if} id="wxkfurl">
										<div class="layui-form-mid">客服链接：</div>
										<div class="layui-input-inline" style="width:220px">
											<input type="text" name="info[wxkfurl]" value="{$info['wxkfurl']}" class="layui-input">
										</div>
										</div>
										<div {if $info['wxkf']!=2}style="display:none"{/if} id="wxkfcorpid">
										<div class="layui-form-mid">企业ID：</div>
										<div class="layui-input-inline" style="width:170px">
											<input type="text" name="info[corpid]" value="{$info['corpid']}" class="layui-input">
										</div>
										</div>
										<div class="layui-form-mid layui-word-aux" style="clear:left;margin-left:160px">客服链接：使用填写的客服链接，不填写则使用商城内部客服系统；<br>小程序客服：在小程序后台-功能-客服-小程序客服，配置客服人员；<br>微信客服：在<a href="https://work.weixin.qq.com/kf" target="_blank">微信客服</a>系统注册账号并绑定，<a href="https://work.weixin.qq.com/nl/act/p/229e9eb2c10f417a" target="_blank">查看绑定流程</a>，填写企业ID并复制客服链接填写到客服链接处</div>
									</div>
						{if getcustom('agent_card') && $ainfo['agent_card'] == 1}
						<div class="layui-form-item">
							<label class="layui-form-label" style="width:130px">代理卡片：</label>
							<div class="layui-input-inline">
								<input type="radio" name="info[agent_card]" value="1" title="开启" {if $info['agent_card']==1}checked{/if}>
								<input type="radio" name="info[agent_card]" value="0" title="关闭" {if $info['agent_card']==0}checked{/if}>
							</div>
						</div>
						{/if}
									<div class="layui-form-item" {if !in_array('mp',$platform)}style="display:none"{/if}>
										<label class="layui-form-label" style="width:130px">关注提示：</label>
										<div class="layui-input-inline" style="width:300px">
											<input type="checkbox" name="gzts[]" value="1" {if in_array('1',explode(',',$info['gzts']))}checked{/if} title="首页" lay-skin="primary">
											<input type="checkbox" name="gzts[]" value="2" {if in_array('2',explode(',',$info['gzts']))}checked{/if}  title="商品详情页" lay-skin="primary">
										</div>
										<div class="layui-form-mid layui-word-aux" style="margin-left:10px;">仅公众号端有效</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">订单播报：</label>
										<div class="layui-input-inline" style="width:300px">
											<input type="checkbox" name="ddbb[]" value="1" {if in_array('1',explode(',',$info['ddbb']))}checked{/if} title="首页" lay-skin="primary">
											<input type="checkbox" name="ddbb[]" value="2" {if in_array('2',explode(',',$info['ddbb']))}checked{/if}  title="商品详情页" lay-skin="primary">
										</div>
										<div class="layui-form-mid layui-word-aux" style="margin-left:10px;">首页或商品详情页滚动显示最近购买信息</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">主色调：</label>
										<div class="layui-input-inline">
											<input type="text" name="info[color1]" value="{$info['color1']}" class="layui-input">
										</div>
										<div class="_colorpicker" style="float:left"></div>
										<div class="layui-form-mid layui-word-aux" style="margin-left:10px;">商城主色调，如：#FD4A46</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">辅色调：</label>
										<div class="layui-input-inline">
											<input type="text" name="info[color2]" value="{$info['color2']}" class="layui-input">
										</div>
										<div class="_colorpicker" style="float:left"></div>
										<div class="layui-form-mid layui-word-aux" style="margin-left:10px;">商城辅色调，如：#7E71F6</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">进入条件：</label>
										<div class="layui-input-inline" style="width:800px">
											<input type="checkbox" name="info[gettj][]" value="-1" title="所有人" lay-skin="primary" {if in_array('-1',$info['gettj'])}checked{/if} lay-filter="gettjset"/>
											{foreach $levellist as $v}
											<input type="checkbox" name="info[gettj][]" value="{$v.id}" title="{$v.name}" lay-skin="primary" {if in_array($v['id'],$info['gettj'])}checked{/if}/>
											{/foreach}
										</div>
									</div>
									<div id="gettjset" {if !$info['id'] || in_array('-1',$info['gettj'])}style="display:none"{/if}>
										<div class="layui-form-item">
											<div class="layui-form-label" style="width:130px">无权限提示：</div>
											<div class="layui-input-inline">
												<input class="layui-input" name="info[gettjtip]" value="{if !$info['id']}您没有权限进入{else}{$info.gettjtip}{/if}"/>
											</div>
											<div class="layui-form-mid"></div>
										</div>
									</div>

								</div>

								<div class="layui-tab-item">

									<fieldset class="layui-elem-field layui-field-title"  style="margin-top: 30px;">
										<legend>余额</legend>
									</fieldset>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">{:t('余额')}支付：</label>
										<div class="layui-input-inline">
											<input type="radio" name="info[moneypay]" title="开启" value="1" {if $info['moneypay']==1}checked{/if}/>
											<input type="radio" name="info[moneypay]" title="关闭" value="0" {if $info['moneypay']==0}checked{/if}/>
										</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">{:t('余额')}充值：</label>
										<div class="layui-input-inline">
											<input type="radio" name="info[recharge]" title="开启" value="1" {if $info['recharge']==1}checked{/if}/>
											<input type="radio" name="info[recharge]" title="关闭" value="0" {if $info['recharge']==0}checked{/if}/>
										</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">{:t('佣金')}转{:t('余额')}：</label>
										<div class="layui-input-inline">
											<input type="radio" name="info[commission2money]" title="开启" value="1" {if $info['commission2money']==1}checked{/if}/>
											<input type="radio" name="info[commission2money]" title="关闭" value="0" {if $info['commission2money']==0}checked{/if}/>
										</div>
										<div class="layui-form-mid layui-word-aux" style="clear:left;margin-left:160px">开启后{:t('会员')}可以将{:t('佣金')}直接转入{:t('余额')}中用于消费</div>
									</div>

									{if getcustom('plug_zhiming') || getcustom('money_transfer')}
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">{:t('余额')}转账：</label>
										<div class="layui-input-inline">
											<input type="radio" name="info[money_transfer]" title="开启" value="1" {if $info['money_transfer']==1}checked{/if}/>
											<input type="radio" name="info[money_transfer]" title="关闭" value="0" {if $info['money_transfer']==0}checked{/if}/>
										</div>
									</div>
									{/if}

									{if getcustom('plug_yuebao')}
										<fieldset class="layui-elem-field layui-field-title"  style="margin-top: 30px;">
											<legend>余额宝</legend>
										</fieldset>
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">是否开启：</label>
											<div class="layui-input-inline">
												<input type="radio" name="info[open_yuebao]" title="开启" value="1" {if $info['open_yuebao']==1}checked{/if} lay-filter="set_yuebao"/>
												<input type="radio" name="info[open_yuebao]" title="关闭" value="0" {if $info['open_yuebao']==0}checked{/if} lay-filter="set_yuebao"/>
											</div>
										</div>
										<div id="yuebaoset" {if !$info || $info['open_yuebao']==0}style="display:none"{/if}>
											<div class="layui-form-item">
												<label class="layui-form-label" style="width:130px">余额宝收益利率：</label>
												<div class="layui-input-inline" style="width:120px">
													<input type="text" name="info[yuebao_rate]" value="{$info['yuebao_rate']?$info['yuebao_rate']:0}" class="layui-input"/>
												</div>
												<div class="layui-form-mid">%</div>
											</div>
											<div class="layui-form-item">
												<label class="layui-form-label" style="width:130px">收益提现天数：</label>
												<div class="layui-input-inline" style="width:120px">
													<input type="text" name="info[yuebao_withdraw_time]" value="{$info['yuebao_withdraw_time']?$info['yuebao_withdraw_time']:0}" class="layui-input"/>
												</div>
												<div class="layui-form-mid">天 注意：1、必须为整数 2、填0为不限制，填其他数字，如填写10，为每10天可提现一次 3、会员单独设置后此设置失效</div>
											</div>
											<div class="layui-form-item">
												<label class="layui-form-label" style="width:130px">收益转余额：</label>
												<div class="layui-input-inline">
													<input type="radio" name="info[yuebao_turn_yue]" title="开启" value="1" {if $info['yuebao_turn_yue']==1}checked{/if}/>
													<input type="radio" name="info[yuebao_turn_yue]" title="关闭" value="0" {if $info['yuebao_turn_yue']==0}checked{/if}/>
												</div>
											</div>
										</div>
									{/if}

									<fieldset class="layui-elem-field layui-field-title" style="margin-top: 30px;">
										<legend>提现</legend>
									</fieldset>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">提现方式：</label>
										<div class="layui-input-inline" style="width:400px">
											<input type="checkbox" name="info[withdraw_weixin]" title="微信钱包" value="1" {if $info['withdraw_weixin']==1}checked{/if}>
											<input type="checkbox" name="info[withdraw_aliaccount]" title="支付宝" value="1" {if $info['withdraw_aliaccount']==1}checked{/if}/>
											<input type="checkbox" name="info[withdraw_bankcard]" title="银行卡" value="1" {if $info['withdraw_bankcard']==1}checked{/if}/>
										</div>
										<div class="layui-form-mid layui-word-aux" style="clear:left;margin-left:160px">提现到微信钱包需开通企业付款到零钱功能</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">自动打款：</label>
										<div class="layui-input-inline" style="width:200px">
											<input type="radio" name="info[withdraw_autotransfer]" value="0" title="关闭" {if $info['withdraw_autotransfer']==0}checked{/if}/>
											<input type="radio" name="info[withdraw_autotransfer]" value="1" title="开启" {if $info['withdraw_autotransfer']==1}checked{/if}/>
										</div>
										<div class="layui-form-mid layui-word-aux">开启后提现到微信钱包时不需要审核，自动用企业付款打款，需开通企业付款到零钱功能</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">{:t('余额')}提现：</label>
										<div class="layui-input-inline">
											<input type="radio" name="info[withdraw]" title="开启" value="1" {if $info['withdraw']==1}checked{/if} lay-filter="withdraw"/>
											<input type="radio" name="info[withdraw]" title="关闭" value="0" {if $info['withdraw']==0}checked{/if} lay-filter="withdraw"/>
										</div>
									</div>
									<div id="withdrawotherset" {if $info['withdraw']==0}style="display:none"{/if}>
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">提现最小金额：</label>
											<div class="layui-input-inline" style="width:120px">
												<input type="text" name="info[withdrawmin]" value="{$info['withdrawmin']}" class="layui-input"/>
											</div>
											<div class="layui-form-mid">元</div>
										</div>
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">提现手续费：</label>
											<div class="layui-input-inline" style="width:120px">
												<input type="text" name="info[withdrawfee]" value="{$info['withdrawfee']}" class="layui-input"/>
											</div>
											<div class="layui-form-mid">%</div>
										</div>
									</div>
									{if getcustom('plug_yuebao')}
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">{:t('余额宝')}收益提现：</label>
											<div class="layui-input-inline">
												<input type="radio" name="info[yuebao_withdraw]" title="开启" value="1" {if $info['yuebao_withdraw']==1}checked{/if} lay-filter="yuebao_withdraw"/>
												<input type="radio" name="info[yuebao_withdraw]" title="关闭" value="0" {if $info['yuebao_withdraw']==0}checked{/if} lay-filter="yuebao_withdraw"/>
											</div>
										</div>
										<div id="yuebaowithdrawotherset" {if $info['yuebao_withdraw']==0}style="display:none"{/if}>
											<div class="layui-form-item">
												<label class="layui-form-label" style="width:130px">提现最小金额：</label>
												<div class="layui-input-inline" style="width:120px">
													<input type="text" name="info[yuebao_withdrawmin]" value="{$info['yuebao_withdrawmin']}" class="layui-input"/>
												</div>
												<div class="layui-form-mid">元</div>
											</div>
											<div class="layui-form-item">
												<label class="layui-form-label" style="width:130px">提现手续费：</label>
												<div class="layui-input-inline" style="width:120px">
													<input type="text" name="info[yuebao_withdrawfee]" value="{$info['yuebao_withdrawfee']}" class="layui-input"/>
												</div>
												<div class="layui-form-mid">%</div>
											</div>
										</div>
									{/if}
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">{:t('佣金')}提现：</label>
										<div class="layui-input-inline">
											<input type="radio" name="info[comwithdraw]" title="开启" value="1" {if $info['comwithdraw']==1}checked{/if} lay-filter="comwithdraw"/>
											<input type="radio" name="info[comwithdraw]" title="关闭" value="0" {if $info['comwithdraw']==0}checked{/if} lay-filter="comwithdraw"/>
										</div>
									</div>
									<div id="comwithdrawotherset" {if $info['comwithdraw']==0}style="display:none"{/if}>
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">提现最小金额：</label>
											<div class="layui-input-inline" style="width:120px">
												<input type="text" name="info[comwithdrawmin]" value="{$info['comwithdrawmin']}" class="layui-input"/>
											</div>
											<div class="layui-form-mid">元</div>
										</div>
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">提现手续费：</label>
											<div class="layui-input-inline" style="width:120px">
												<input type="text" name="info[comwithdrawfee]" value="{$info['comwithdrawfee']}" class="layui-input"/>
											</div>
											<div class="layui-form-mid">%</div>
										</div>
										{if getcustom('fengdanjiangli')}
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">可提现比例：</label>
											<div class="layui-input-inline" style="width:120px">
												<input type="text" name="info[comwithdrawbl]" value="{$info['comwithdrawbl']}" class="layui-input"/>
											</div>
											<div class="layui-form-mid">%</div>
										<div class="layui-form-mid layui-word-aux">可提现比例不是100%时,不可提现部分在提现时直接转换为余额</div>
										</div>
										{/if}
										{if getcustom('commission2scorepercent')}
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">{:t('佣金')}到{:t('积分')}账户比例：</label>
											<div class="layui-input-inline" style="width:120px">
												<input type="text" name="info[commission2scorepercent]" value="{$info['commission2scorepercent']}" class="layui-input"/>
											</div>
											<div class="layui-form-mid">%</div>
											<div class="layui-form-mid layui-word-aux">设置后产生佣金时就有一部分比例自动转成积分</div>
										</div>
										{/if}
										{if getcustom('commission2moneypercent')}
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">{:t('佣金')}到{:t('余额')}账户比例：</label>
											<div class="layui-form-mid" style="margin-right:0;background:#e6e6e6;padding: 9px 9px !important">首单</div>
											<div class="layui-input-inline" style="width: 100px;">
												<input style="border-radius:0 2px 2px 0" type="text" name="info[commission2moneypercent1]" class="layui-input" value="{$info.commission2moneypercent1}">
											</div>
											<div class="layui-form-mid">%</div>
											<div class="layui-form-mid" style="margin-right:0;background:#e6e6e6;padding: 9px 9px !important">复购</div>
											<div class="layui-input-inline" style="width: 100px;">
												<input style="border-radius:0 2px 2px 0" type="text" name="info[commission2moneypercent2]" class="layui-input" value="{$info.commission2moneypercent2}">
											</div>
											<div class="layui-form-mid">%</div>

											<div class="layui-form-mid layui-word-aux">设置后商城订单产生{:t('佣金')}时就有一部分比例自动转成{:t('余额')}</div>
										</div>
										{/if}
										{if getcustom('commission_autowithdraw')}
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">{:t('佣金')}自动提现：</label>
											<div class="layui-input-inline">
												<input type="radio" name="info[commission_autowithdraw]" title="开启" value="1" {if $info['commission_autowithdraw']==1}checked{/if}/>
												<input type="radio" name="info[commission_autowithdraw]" title="关闭" value="0" {if $info['commission_autowithdraw']==0}checked{/if}/>
											</div>
											<div class="layui-form-mid layui-word-aux">开启后{:t('佣金')}结算后将自动通过企业付款到零钱，需开通企业付款到零钱功能</div>
										</div>
										{/if}
									</div>

									<fieldset class="layui-elem-field layui-field-title">
										<legend>发票</legend>
									</fieldset>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">发票开关：</label>
										<div class="layui-input-inline">
											<input type="radio" name="info[invoice]" value="1" {if $info['invoice']==1}checked{/if} title="开启" lay-filter="invoice"/>
											<input type="radio" name="info[invoice]" value="0" {if $info['invoice']==0}checked{/if} title="关闭" lay-filter="invoice"/>
										</div>
									</div>
  									<div id="invoice-set" {if $info['invoice']==0}style="display:none"{/if}>
									  <div class="layui-form-item">
										  <label class="layui-form-label" style="width:130px">发票类型：</label>
										  <div class="layui-input-inline" style="width:300px">
											  <input type="checkbox" name="info[invoice_type][]" value="1" title="普通发票" lay-skin="primary" {if in_array('1',$info['invoice_type'])}checked{/if}/>
											  <input type="checkbox" name="info[invoice_type][]" value="2" title="增值税专用发票" lay-skin="primary" {if in_array('2',$info['invoice_type'])}checked{/if}/>
										  </div>
									  </div>
									{if getcustom('invoice_rate')}
									  <div class="layui-form-item">
										  <label class="layui-form-label" style="width:130px">发票税点：</label>
										  <div class="layui-input-inline" style="width:120px">
											  <input type="text" name="info[invoice_rate]" value="{$info['invoice_rate']}" class="layui-input"/>
										  </div>
										  <div class="layui-form-mid">%</div>
									  </div>
  									{/if}
									</div>
									{if $pay_transfer}
									<fieldset class="layui-elem-field layui-field-title" style="margin-top: 30px;">
										<legend>转账汇款</legend>
									</fieldset>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">转账汇款支付：</label>
										<div class="layui-input-inline">
											<input type="radio" name="info[pay_transfer]" title="开启" value="1" {if $info['pay_transfer']==1}checked{/if} lay-filter="pay_transfer"/>
											<input type="radio" name="info[pay_transfer]" title="关闭" value="0" {if $info['pay_transfer']==0}checked{/if} lay-filter="pay_transfer"/>
										</div>
										<div class="layui-form-mid layui-word-aux" style="clear:left;margin-left:160px">开启后{:t('会员')}下单时可使用此支付方式，后台需审核并处理订单，仅平台支持</div>
									</div>
									<div id="pay_transfer_set" {if $info['pay_transfer']==0}style="display:none"{/if}>
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">户名：</label>
											<div class="layui-input-inline" style="width:300px">
												<input type="text" name="info[pay_transfer_account_name]" value="{$info['pay_transfer_account_name']}" class="layui-input"/>
											</div>
										</div>
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">账号：</label>
											<div class="layui-input-inline" style="width:300px">
												<input type="text" name="info[pay_transfer_account]" value="{$info['pay_transfer_account']}" class="layui-input"/>
											</div>
										</div>
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">开户行：</label>
											<div class="layui-input-inline" style="width:300px">
												<input type="text" name="info[pay_transfer_bank]" value="{$info['pay_transfer_bank']}" class="layui-input"/>
											</div>
										</div>
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">提示信息：</label>
											<div class="layui-input-inline" style="width:400px">
												<textarea type="text" name="info[pay_transfer_desc]" class="layui-textarea" value="">{$info.pay_transfer_desc}</textarea>
											</div>
											<div class="layui-form-mid layui-word-aux" style="clear:left;margin-left:160px">展示在转账信息下面的提示说明，如：汇款时请将订单号填写在附言，备注等栏位</div>
										</div>
									</div>
									{/if}

									{if $w7moneyscore}
									<fieldset class="layui-elem-field layui-field-title" style="margin-top: 30px;">
										<legend>微擎同步</legend>
									</fieldset>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">使用微擎平台余额积分：</label>
										<div class="layui-input-inline">
											<input type="radio" name="info[w7moneyscore]" title="关闭" value="0" {if $info['w7moneyscore']==0}checked{/if}/>
											<input type="radio" name="info[w7moneyscore]" title="开启" value="1" {if $info['w7moneyscore']==1}checked{/if}/>
										</div>
										<div class="layui-form-mid layui-word-aux" style="margin-left:10px;">开启后余额和积分将使用微擎平台的会员余额积分，即和微擎平台的会员余额和积分同步，请谨慎操作</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">微擎平台或应用uniacid：</label>
										<div class="layui-input-inline">
											<input type="text" name="info[w7uniacid]" value="{$info['w7uniacid']}" class="layui-input"/>
										</div>
										<div class="layui-form-mid layui-word-aux" style="margin-left:10px;">根据uniacid同步信息，请谨慎操作</div>
									</div>
									{/if}
								</div>
								<div class="layui-tab-item">
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">消费赠{:t('积分')}：</label>
										<div class="layui-form-mid" style="margin-right:0;background:#e6e6e6;padding: 9px 9px !important">消费每满</div>
										<div class="layui-input-inline" style="width: 100px;">
											<input style="border-radius:0 2px 2px 0" type="text" name="info[scorein_money]" class="layui-input" value="{$info.scorein_money}">
										</div>
										<div class="layui-form-mid">元</div>
										<div class="layui-form-mid" style="margin-right:0;background:#e6e6e6;padding: 9px 9px !important">赠送</div>
										<div class="layui-input-inline" style="width: 100px;">
											<input style="border-radius:0 2px 2px 0" type="text" name="info[scorein_score]" class="layui-input" value="{$info.scorein_score}">
										</div>
										<div class="layui-form-mid">{:t('积分')}</div>
										<div class="layui-form-mid layui-word-aux" style="margin-left:10px;">使用{:t('余额')}支付时不赠送，支付后积分即到账，发生退款时不会扣除</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">充值赠积分：</label>
										<div class="layui-form-mid" style="margin-right:0;background:#e6e6e6;padding: 9px 9px !important">充值每满</div>
										<div class="layui-input-inline" style="width: 100px;">
											<input style="border-radius:0 2px 2px 0" type="text" name="info[scorecz_money]" class="layui-input" value="{$info.scorecz_money}">
										</div>
										<div class="layui-form-mid">元</div>
										<div class="layui-form-mid" style="margin-right:0;background:#e6e6e6;padding: 9px 9px !important">赠送</div>
										<div class="layui-input-inline" style="width: 100px;">
											<input style="border-radius:0 2px 2px 0" type="text" name="info[scorecz_score]" class="layui-input" value="{$info.scorecz_score}">
										</div>
										<div class="layui-form-mid">{:t('积分')}</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">{:t('积分')}抵扣：</label>
										<div class="layui-form-mid" style="margin-right:0;background:#e6e6e6;padding: 9px 9px !important">每{:t('积分')}抵扣</div>
										<div class="layui-input-inline" style="width: 100px;">
											<input style="border-radius:0 2px 2px 0" type="text" name="info[score2money]" class="layui-input" value="{$info.score2money}">
										</div>
										<div class="layui-form-mid">元</div>
										<div class="layui-form-mid layui-word-aux" style="margin-left:10px;">付款时一个{:t('积分')}可抵扣多少元，0表示不开启{:t('积分')}抵扣</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px"></label>
										<div class="layui-form-mid" style="margin-right:0;background:#e6e6e6;padding: 9px 9px !important">最多抵扣百分比</div>
										<div class="layui-input-inline" style="width: 100px;">
											<input style="border-radius:0 2px 2px 0" type="text" name="info[scoredkmaxpercent]" class="layui-input" value="{$info.scoredkmaxpercent}">
										</div>
										<div class="layui-form-mid">%</div>
										<div class="layui-form-mid layui-word-aux" style="margin-left:10px;">选择使用{:t('积分')}抵扣时，最多可抵扣订单额的百分之多少</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">{:t('积分')}不抵扣运费：</label>
										<div class="layui-input-inline" style="width:70px">
											<input type="checkbox" name="info[scorebdkyf]" value="1" lay-text="开启|关闭" lay-skin="switch" {if $info['scorebdkyf']==1}checked{/if}>
										</div>
										<div class="layui-form-mid layui-word-aux" style="margin-left:10px;">开启后{:t('积分')}不能抵扣运费</div>
									</div>
									{if getcustom('plug_zhiming') || getcustom('score_transfer')}
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">{:t('积分')}转赠：</label>
										<div class="layui-input-inline">
											<input type="radio" name="info[score_transfer]" title="开启" value="1" {if $info['score_transfer']==1}checked{/if}/>
											<input type="radio" name="info[score_transfer]" title="关闭" value="0" {if $info['score_transfer']==0}checked{/if}/>
										</div>
									</div>
									{/if}
									{if getcustom('score_withdraw') || getcustom('plug_luckycollage')}
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">{:t('积分')}提现到{:t('余额')}：</label>
										<div class="layui-input-inline">
											<input type="radio" name="info[score_withdraw]" title="开启" value="1" {if $info['score_withdraw']==1}checked{/if} lay-filter="score_withdraw"/>
											<input type="radio" name="info[score_withdraw]" title="关闭" value="0" {if $info['score_withdraw']==0}checked{/if} lay-filter="score_withdraw"/>
										</div>
									</div>
									<div id="score_withdraw_div" {if $info['score_withdraw']==0}style="display:none"{/if}>
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">每日转允提{:t('积分')}比例：</label>
											<div class="layui-input-inline" style="width:120px">
												<input type="number" min="0" max="100" name="info[score_withdraw_percent_day]" value="{$info['score_withdraw_percent_day']}" class="layui-input"/>
											</div>
											<div class="layui-form-mid">%</div>
											<div class="layui-form-mid layui-word-aux" style="margin-left:10px;">每日普通{:t('积分')}转为可提现{:t('积分')}的百分比，不足1{:t('积分')}按1{:t('积分')}，大于1积分时，对小数位四舍五入取整数</div>
										</div>
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">{:t('积分')}转{:t('余额')}换算比例：</label>
											<div class="layui-input-inline" style="width:120px">
												<input type="number" min="0" name="info[score_to_money_percent]" value="{$info['score_to_money_percent']}" class="layui-input"/>
											</div>
											<div class="layui-form-mid layui-word-aux" style="margin-left:10px;">如设置0.5，表示1{:t('积分')}换0.5元{:t('余额')}</div>
										</div>
									</div>
									{/if}
								</div>
								<div class="layui-tab-item">
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">分销结算方式：</label>
										<div class="layui-input-inline" style="width:400px">
											<input type="radio" name="info[fxjiesuantype]" title="按商品价格" value="0" {if $info['fxjiesuantype']==0}checked{/if}/>
											<input type="radio" name="info[fxjiesuantype]" title="按成交价格" value="1" {if $info['fxjiesuantype']==1}checked{/if}/>
											<input type="radio" name="info[fxjiesuantype]" title="按销售利润" value="2" {if $info['fxjiesuantype']==2}checked{/if}/>
										</div>
										<div class="layui-form-mid layui-word-aux" style="clear:left;margin-left:160px">按商品价格结算：商品价格×提成百分比；按成交价格结算：成交价格×提成百分比,即扣除{:t('会员')}折扣、满减优惠、{:t('优惠券')}抵扣及{:t('积分')}抵扣后计算分销；按销售利润结算：（成交价格-商品成本）×提成百分比</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">分销结算时间：</label>
										<div class="layui-input-inline" style="width:250px">
											<input type="radio" name="info[fxjiesuantime]" title="确认收货后" value="0" {if $info['fxjiesuantime']==0}checked{/if}/>
											<input type="radio" name="info[fxjiesuantime]" title="付款后" value="1" {if $info['fxjiesuantime']==1}checked{/if}/>
										</div>
										{if getcustom('fxjiesuantime_perweek')}
										<div class="layui-form-mid">周</div>
										<div class="layui-input-inline" style="width:100px">
											<input type="text" name="info[fxjiesuantime_delaydays]" class="layui-input" value="{$info.fxjiesuantime_delaydays}">
										</div>
										<div class="layui-form-mid">结算</div>
										{else}
										<div class="layui-input-inline" style="width:100px">
											<input type="text" name="info[fxjiesuantime_delaydays]" class="layui-input" value="{$info.fxjiesuantime_delaydays}">
										</div>
										<div class="layui-form-mid">天结算</div>
										{/if}
										<div class="layui-form-mid layui-word-aux" style="clear:left;margin-left:160px">注意：0代表付款或确认收货后立即结算，如果产生退款，已发放的佣金不会扣除</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">买单付款分销：</label>
										<div class="layui-input-inline" style="width:300px">
											<input type="radio" name="info[maidanfenxiao]" title="否" value="0" {if $info['maidanfenxiao']==0}checked{/if}/>
											<input type="radio" name="info[maidanfenxiao]" title="是" value="1" {if $info['maidanfenxiao']==1}checked{/if}/>
										</div>
										<div class="layui-form-mid layui-word-aux" style="clear:left;margin-left:160px">买单付款功能是否参与分销</div>
									</div>
									
									{if $auth_data=='all' || in_array('gdfenhong',$auth_data) || in_array('teamfenhong',$auth_data) || in_array('areafenhong',$auth_data)}
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">分红结算方式：</label>
										<div class="layui-input-inline" style="width:300px">
											<input type="radio" name="info[fhjiesuantype]" title="按销售金额" value="0" {if $info['fhjiesuantype']==0}checked{/if}/>
											<input type="radio" name="info[fhjiesuantype]" title="按销售利润" value="1" {if $info['fhjiesuantype']==1}checked{/if}/>
										</div>
										<div class="layui-form-mid layui-word-aux" style="clear:left;margin-left:160px">按销售金额结算即：销售价格×提成百分比，按销售利润即：（销售价格-商品成本）×提成百分比</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">分红结算时间：</label>
										<div class="layui-input-inline" style="width:600px">
											<input type="radio" name="info[fhjiesuantime_type]" title="确认收货后结算" value="0" {if $info['fhjiesuantime_type']==0}checked{/if} lay-filter="fhjiesuantime_type"/>
											<input type="radio" name="info[fhjiesuantime_type]" title="付款后结算" value="1" {if $info['fhjiesuantime_type']==1}checked{/if} lay-filter="fhjiesuantime_type"/>
										</div>
										<div class="layui-form-mid layui-word-aux" style="clear:left;margin-left:160px">确认收货后结算：可选计算周期，付款后结算：每分钟计算一次</div>
										<div class="layui-form-mid " style="clear:left;margin-left:160px;color:red;">注意：选择付款后结算退款时无法退回分红</div>
									</div>
									<div class="layui-form-item" id="fhjiesuantime_type-set"{if $info['fhjiesuantime_type']==1}style="display:none"{/if}>
										<label class="layui-form-label" style="width:130px">分红结算周期：</label>
										<div class="layui-input-inline" style="width:700px">
											<input type="radio" name="info[fhjiesuantime]" title="每天结算" value="0" {if $info['fhjiesuantime']==0}checked{/if}/>
											<input type="radio" name="info[fhjiesuantime]" title="每小时结算" value="2" {if $info['fhjiesuantime']==2}checked{/if}/>
											<input type="radio" name="info[fhjiesuantime]" title="每分钟结算" value="3" {if $info['fhjiesuantime']==3}checked{/if}/>
											<input type="radio" name="info[fhjiesuantime]" title="月初结算" value="1" {if $info['fhjiesuantime']==1}checked{/if}/>
											<input type="radio" name="info[fhjiesuantime]" title="月底结算" value="4" {if $info['fhjiesuantime']==4}checked{/if}/>
											<input type="radio" name="info[fhjiesuantime]" title="年底结算" value="5" {if $info['fhjiesuantime']==5}checked{/if}/>
										</div>
										<div class="layui-form-mid layui-word-aux" style="clear:left;margin-left:160px">每天结算则第二天计算前一天的分红并发放，月初结算则每月一号计算上一个月的分红并发放（确认收货的订单）</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">分红合并结算：</label>
										<div class="layui-input-inline" style="width:300px">
											<input type="radio" name="info[fhjiesuanhb]" title="是" value="1" {if $info['fhjiesuanhb']==1}checked{/if}/>
											<input type="radio" name="info[fhjiesuanhb]" title="否" value="0" {if $info['fhjiesuanhb']==0}checked{/if}/>
										</div>
										<div class="layui-form-mid layui-word-aux" style="clear:left;margin-left:160px">分红合并结算即结算时所有需要分红的订单合并为一条,产生一条结算记录</div>
									</div>
									{if $auth_data=='all' || in_array('teamfenhong',$auth_data)}
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">团队分红级差：</label>
										<div class="layui-input-inline" style="width:300px">
											<input type="radio" name="info[teamfenhong_differential]" title="是" value="1" {if $info['teamfenhong_differential']==1}checked{/if}/>
											<input type="radio" name="info[teamfenhong_differential]" title="否" value="0" {if $info['teamfenhong_differential']==0}checked{/if}/>
										</div>
										<div class="layui-form-mid layui-word-aux" style="clear:left;margin-left:160px">开启后团队分红将按照级差进行分红</div>
									</div>
									{/if}
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">多商户商品分红：</label>
										<div class="layui-input-inline" style="width:300px">
											<input type="radio" name="info[fhjiesuanbusiness]" title="是" value="1" {if $info['fhjiesuanbusiness']==1}checked{/if}/>
											<input type="radio" name="info[fhjiesuanbusiness]" title="否" value="0" {if $info['fhjiesuanbusiness']==0}checked{/if}/>
										</div>
										<div class="layui-form-mid layui-word-aux" style="clear:left;margin-left:160px">开启后多商户的商品也参与分红</div>
									</div>
									{/if}
									{if getcustom('partner_jiaquan') && $partner_jiaquan}
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">股东加权分红：</label>
										<div class="layui-input-inline" style="width:300px">
											<input type="radio" name="info[partner_jiaquan]" title="是" value="1" {if $info['partner_jiaquan']==1}checked{/if}/>
											<input type="radio" name="info[partner_jiaquan]" title="否" value="0" {if $info['partner_jiaquan']==0}checked{/if}/>
										</div>
										<div class="layui-form-mid layui-word-aux" style="clear:left;margin-left:160px">开启后高等级的股东也会参与低等级的股东分红</div>
									</div>
									{/if}
									{if getcustom('areafenhong_jiaquan')}
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">区域代理加权分红：</label>
										<div class="layui-input-inline" style="width:300px">
											<input type="radio" name="info[areafenhong_jiaquan]" title="是" value="1" {if $info['areafenhong_jiaquan']==1}checked{/if}/>
											<input type="radio" name="info[areafenhong_jiaquan]" title="否" value="0" {if $info['areafenhong_jiaquan']==0}checked{/if}/>
										</div>
										<div class="layui-form-mid layui-word-aux" style="clear:left;margin-left:160px">开启后上级区域的区域代理也会参与下级区域的区域分红</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">区域判断方式：</label>
										<div class="layui-input-inline" style="width:300px">
											<input type="radio" name="info[areafenhong_checktype]" title="收货地址" value="0" {if $info['areafenhong_checktype']==0}checked{/if}/>
											<input type="radio" name="info[areafenhong_checktype]" title="手机号归属地" value="1" {if $info['areafenhong_checktype']==1}checked{/if}/>
										</div>
										<div class="layui-form-mid layui-word-aux" style="clear:left;margin-left:160px"></div>
									</div>
									{/if}
									{if getcustom('partner_gongxian') && $partner_gongxian}
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">股东贡献量分红：</label>
										<div class="layui-input-inline" style="width:300px">
											<input type="radio" name="info[partner_gongxian]" title="是" value="1" {if $info['partner_gongxian']==1}checked{/if} lay-filter="partner_gongxian"/>
											<input type="radio" name="info[partner_gongxian]" title="否" value="0" {if $info['partner_gongxian']==0}checked{/if} lay-filter="partner_gongxian"/>
										</div>
										<div class="layui-form-mid layui-word-aux" style="clear:left;margin-left:160px">开启后可设置一定比例的分红金额按照股东的团队业绩量分红</div>
									</div>
									<div id="partner_gongxian_div" {if $info['partner_gongxian']==0}style="display:none"{/if}>
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">预计股东贡献量分红：</label>
											<div class="layui-input-inline" style="width:200px">
												<input type="radio" name="info[gongxianfenhong_show]" title="显示" value="1" {if $info['gongxianfenhong_show']==1}checked{/if}/>
												<input type="radio" name="info[gongxianfenhong_show]" title="不显示" value="0" {if $info['gongxianfenhong_show']==0}checked{/if}/>
											</div>
											<div class="layui-form-mid">自定义名称</div>
											<div class="layui-input-inline" style="width:170px">
												<input type="text" name="info[gongxianfenhong_txt]" class="layui-input" value="{$info.gongxianfenhong_txt}">
											</div>
										</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">团队业绩：</label>
										<div class="layui-input-inline" style="width:300px">
											<input type="radio" name="info[teamyeji_show]" title="显示" value="1" {if $info['teamyeji_show']==1}checked{/if}/>
											<input type="radio" name="info[teamyeji_show]" title="不显示" value="0" {if $info['teamyeji_show']==0}checked{/if}/>
										</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">团队总人数：</label>
										<div class="layui-input-inline" style="width:300px">
											<input type="radio" name="info[teamnum_show]" title="显示" value="1" {if $info['teamnum_show']==1}checked{/if}/>
											<input type="radio" name="info[teamnum_show]" title="不显示" value="0" {if $info['teamnum_show']==0}checked{/if}/>
										</div>
									</div>
									{/if}
								</div>
								<div class="layui-tab-item">
									{foreach $textset as $k=>$v}
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">{$k}：</label>
										<div class="layui-input-inline">
											<input type="text" name="textset[{$k}]" class="layui-input" value="{$v}">
										</div>
									</div>
									{/foreach}
								</div>
								<div class="layui-tab-item">
									{foreach $platform as $pl}
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">{:getplatformname($pl)}：</label>
										<div class="layui-input-inline" style="width:500px">
											<input type="checkbox" name="info[logintype_{$pl}][]" title="注册登录" value="1" {if in_array('1',$info['logintype_'.$pl])}checked{/if}/>
											<input type="checkbox" name="info[logintype_{$pl}][]" title="手机号登录" value="2" {if in_array('2',$info['logintype_'.$pl])}checked{/if}/>
											{if $pl!='h5'}<input type="checkbox" name="info[logintype_{$pl}][]" title="授权登录" value="3" {if in_array('3',$info['logintype_'.$pl])}checked{/if}/>{/if}
										</div>
										<div class="layui-form-mid layui-word-aux" style="clear:left;margin-left:160px;"></div>
									</div>
									{/foreach}
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">绑定手机号：</label>
										<div class="layui-input-inline" style="width:350px">
											<input type="radio" name="info[login_bind]" title="不绑定" value="0" {if $info['login_bind']==0}checked{/if}/>
											<input type="radio" name="info[login_bind]" title="可选绑定" value="1" {if $info['login_bind']==1}checked{/if}/>
											<input type="radio" name="info[login_bind]" title="强制绑定" value="2" {if $info['login_bind']==2}checked{/if}/>
										</div>
										<div class="layui-form-mid layui-word-aux">授权登录时是否提示绑定手机号</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">强制登录：</label>
										<div class="layui-input-inline" style="width:500px">
											{foreach $platform as $pl}
											<input type="checkbox" name="login_mast[]" title="{:getplatformname($pl)}" value="{$pl}" {if in_array($pl,explode(',',$info['login_mast']))}checked{/if} lay-skin="primary"/>
											{/foreach}
										</div>
										<div class="layui-form-mid layui-word-aux" style="clear:left;margin-left:160px;">开启后进入系统必须先登录</div>
									</div>
									{if getcustom('reg_invite_code') || getcustom('plug_zhangyuan')}
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">注册邀请码：</label>
										<div class="layui-input-inline" style="width:300px">
											<input type="radio" name="info[reg_invite_code]" title="开启" value="1" {if $info['reg_invite_code']==1}checked{/if}/>
											<input type="radio" name="info[reg_invite_code]" title="关闭" value="0" {if $info['reg_invite_code']==0}checked{/if}/>
											<input type="radio" name="info[reg_invite_code]" title="强制邀请" value="2" {if $info['reg_invite_code']==2}checked{/if}/>
										</div>
										<div class="layui-form-mid layui-word-aux" style="clear:left;margin-left:160px;">通过邀请链接注册时展示邀请人信息，不能输入邀请码，强制邀请时必须邀请才能注册</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">邀请码类型：</label>
										<div class="layui-input-inline" style="width:300px">
											<input type="radio" name="info[reg_invite_code_type]" title="手机号" value="0" {if $info['reg_invite_code_type']==0}checked{/if}/>
											<input type="radio" name="info[reg_invite_code_type]" title="邀请码" value="1" {if $info['reg_invite_code_type']==1}checked{/if}/>
										</div>
										<div class="layui-form-mid layui-word-aux"></div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">注册审核：</label>
										<div class="layui-input-inline" style="width:300px">
											<input type="radio" name="info[reg_check]" title="关闭" value="0" {if $info['reg_check']==0}checked{/if}/>
											<input type="radio" name="info[reg_check]" title="开启" value="1" {if $info['reg_check']==1}checked{/if}/>
										</div>
										<div class="layui-form-mid layui-word-aux"></div>
									</div>
									{/if}
								</div>

								<div class="layui-tab-item">

									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">用户注册协议：</label>
										<div class="layui-input-inline" style="width:300px">
											<input type="radio" name="xyinfo[status]" title="开启" value="1" {if $xyinfo['status']==1}checked{/if}/>
											<input type="radio" name="xyinfo[status]" title="关闭" value="0" {if $xyinfo['status']==0}checked{/if}/>
										</div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">注册协议名称：</label>
										<div class="layui-input-inline" style="width:300px">
											<input type="text" name="xyinfo[name]" value="{$xyinfo['name']}" class="layui-input">
										</div>
										<div class="layui-form-mid layui-word-aux"></div>
									</div>
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">注册协议内容：</label>
										<div class="layui-input-inline" style="width:500px">
											<script id="xycontent" name="xyinfo[content]" type="text/plain" style="width:100%;height:400px">{$xyinfo.content}</script>
										</div>
									</div>
								</div>

								<div class="layui-tab-item">
									<div class="layui-form-item">
										<label class="layui-form-label" style="width:130px">附件存储类型：</label>
										<div class="layui-input-inline" style="width:300px">
											<select name="rinfo[type]" lay-filter="changetype">
												<option value="0" {if $rinfo['type']==0}selected{/if}>跟随平台附件设置</option>
												{if $rinfo['type']==1}<option value="1" {if $rinfo['type']==1}selected{/if}>本地存储</option>{/if}
												<option value="2" {if $rinfo['type']==2}selected{/if}>阿里云</option>
												<option value="3" {if $rinfo['type']==3}selected{/if}>七牛云</option>
												<option value="4" {if $rinfo['type']==4}selected{/if}>腾讯云</option>
											</select>
										</div>
									</div>
									<div id="aliossset" {if $rinfo['type']!=2}style="display:none"{/if}>
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">Access Key ID：</label>
											<div class="layui-input-inline" style="width:300px">
												<input type="text" name="rinfo[alioss][key]" value="{$rinfo['alioss']['key']}" class="layui-input">
											</div>
										</div>
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">Access Key Secret：</label>
											<div class="layui-input-inline" style="width:300px">
												<input type="text" name="rinfo[alioss][secret]" value="{$rinfo['alioss']['secret']}" class="layui-input">
											</div>
										</div>
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">Bucket名称：</label>
											<div class="layui-input-inline" style="width:300px">
												<input type="text" name="rinfo[alioss][bucket]" value="{$rinfo['alioss']['bucket']}" class="layui-input">
											</div>
											<div class="layui-form-mid layui-word-aux">空间名称</div>
										</div>
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">EndPoint（地域节点）</label>
											<div class="layui-input-inline" style="width:300px">
												<input type="text" name="rinfo[alioss][ossurl]" value="{$rinfo['alioss']['ossurl']}" class="layui-input">
											</div>
											<div class="layui-form-mid layui-word-aux">如：oss-cn-qingdao.aliyuncs.com</div>
										</div>
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">Bucket域名：</label>
											<div class="layui-input-inline" style="width:300px">
												<input type="text" name="rinfo[alioss][url]" value="{$rinfo['alioss']['url']}" class="layui-input">
											</div>
											<div class="layui-form-mid layui-word-aux">开头须加https://</div>
										</div>
									</div>
									<div id="qiniuset" {if $rinfo['type']!=3}style="display:none"{/if}>
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">Accesskey：</label>
											<div class="layui-input-inline" style="width:300px">
												<input type="text" name="rinfo[qiniu][accesskey]" value="{$rinfo['qiniu']['accesskey']}" class="layui-input">
											</div>
											<div class="layui-form-mid layui-word-aux">在密钥管理中查找</div>
										</div>
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">Secretkey：</label>
											<div class="layui-input-inline" style="width:300px">
												<input type="text" name="rinfo[qiniu][secretkey]" value="{$rinfo['qiniu']['secretkey']}" class="layui-input">
											</div>
											<div class="layui-form-mid layui-word-aux">在密钥管理中查找</div>
										</div>
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">Bucket：</label>
											<div class="layui-input-inline" style="width:300px">
												<input type="text" name="rinfo[qiniu][bucket]" value="{$rinfo['qiniu']['bucket']}" class="layui-input">
											</div>
											<div class="layui-form-mid layui-word-aux">空间名称</div>
										</div>
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">Url：</label>
											<div class="layui-input-inline" style="width:300px">
												<input type="text" name="rinfo[qiniu][url]" value="{$rinfo['qiniu']['url']}" class="layui-input">
											</div>
											<div class="layui-form-mid layui-word-aux">开头须加https://</div>
										</div>
									</div>
									<div id="cosset" {if $rinfo['type']!=4}style="display:none"{/if}>
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">APPID：</label>
											<div class="layui-input-inline" style="width:300px">
												<input type="text" name="rinfo[cos][appid]" value="{$rinfo['cos']['appid']}" class="layui-input">
											</div>
										</div>
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">SecretID：</label>
											<div class="layui-input-inline" style="width:300px">
												<input type="text" name="rinfo[cos][secretid]" value="{$rinfo['cos']['secretid']}" class="layui-input">
											</div>
										</div>
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">SecretKEY：</label>
											<div class="layui-input-inline" style="width:300px">
												<input type="text" name="rinfo[cos][secretkey]" value="{$rinfo['cos']['secretkey']}" class="layui-input">
											</div>
										</div>
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">Bucket：</label>
											<div class="layui-input-inline" style="width:300px">
												<input type="text" name="rinfo[cos][bucket]" value="{$rinfo['cos']['bucket']}" class="layui-input">
											</div>
											<div class="layui-form-mid layui-word-aux">空间名称</div>
										</div>
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">bucket所属地域：</label>
											<div class="layui-input-inline" style="width:300px">
												<input type="text" name="rinfo[cos][local]" value="{$rinfo['cos']['local']}" class="layui-input">
											</div>
											<div class="layui-form-mid layui-word-aux">地域代码，如：ap-beijing</div>
										</div>
										<div class="layui-form-item">
											<label class="layui-form-label" style="width:130px">Url：</label>
											<div class="layui-input-inline" style="width:300px">
												<input type="text" name="rinfo[cos][url]" value="{$rinfo['cos']['url']}" class="layui-input">
											</div>
											<div class="layui-form-mid layui-word-aux">开头须加https://</div>
										</div>
									</div>
								</div>


							</div>
						</div>

						<div class="layui-form-item">
							<div class="layui-input-block" style="margin-left:175px;">
								<button class="layui-btn" lay-submit lay-filter="formsubmit">提 交</button>
							</div>
						</div>
					</div>
				</div>
      </div>
    </div>
  </div>
	{include file="public/js"/}
	<script>
	var ueditor = UE.getEditor('xycontent',{imageScaleEnabled:false});

	function choosezuobiao(){
		var address = $("input[name='info[address]']").val();
		var longitude = $("input[name='info[longitude]']").val();
		var latitude = $("input[name='info[latitude]']").val();
		var choosezblayer = layer.open({type:2,shadeClose: true,area: ['800px', '560px'],'title': '选择坐标',content: "{:url('DesignerPage/choosezuobiao')}&address="+(address?address:"")+"&jd="+(longitude?longitude:"")+"&wd="+(latitude?latitude:""),btn:['确定','取消'],yes:function(index, layero){
			var longitude = layero.find('iframe').contents().find('#mapjd').val();
			var latitude = layero.find('iframe').contents().find('#mapwd').val();
			$("input[name='info[longitude]']").val(longitude);
			$("input[name='info[latitude]']").val(latitude);
			layer.close(choosezblayer);
		}});
	}

	layui.form.on('radio(set_yuebao)', function(data){
		if(data.value == '1'){
			$('#yuebaoset').show();
		}else{
			$('#yuebaoset').hide();
		}
	})

	layui.form.on('radio(invoice)', function(data){
		if(data.value == '1'){
			$('#invoice-set').show();
		}else{
			$('#invoice-set').hide();
		}
	})
	layui.form.on('radio(fhjiesuantime_type)', function(data){
		if(data.value == '1'){
			$('#fhjiesuantime_type-set').hide();
		}else{
			$('#fhjiesuantime_type-set').show();
		}
	})
	layui.form.on('radio(pay_transfer)', function(data){
		if(data.value == '0'){
			$('#pay_transfer_set').hide();
		}else{
			$('#pay_transfer_set').show();
		}
	})
	layui.form.on('radio(alipayset)', function(data){
		if(data.value == '0'){
			$('#alipayset').hide();
		}else{
			$('#alipayset').show();
		}
	})
	layui.form.on('radio(withdraw)', function(data){
		if(data.value == '1'){
			$('#withdrawotherset').show();
		}else{
			$('#withdrawotherset').hide();
		}
	})
	layui.form.on('radio(yuebao_withdraw)', function(data){
		if(data.value == '1'){
			$('#yuebaowithdrawotherset').show();
		}else{
			$('#yuebaowithdrawotherset').hide();
		}
	})
	layui.form.on('radio(comwithdraw)', function(data){
		if(data.value == '1'){
			$('#comwithdrawotherset').show();
		}else{
			$('#comwithdrawotherset').hide();
		}
	})
	layui.form.on('radio(score_withdraw)', function(data){
		if(data.value == '1'){
			$('#score_withdraw_div').show();
		}else{
			$('#score_withdraw_div').hide();
		}
	})
	layui.form.on('radio(wxkfset)', function(data){
		if(data.value == '2'){
			$('#wxkfcorpid').show();
		}else{
			$('#wxkfcorpid').hide();
		}
		if(data.value == '1'){
			$('#wxkfurl').hide();
		}else{
			$('#wxkfurl').show();
		}
	})
	layui.form.on('radio(partner_gongxian)', function(data){
		if(data.value == '1'){
			$('#partner_gongxian_div').show();
		}else{
			$('#partner_gongxian_div').hide();
		}
	})



	layui.form.on('submit(formsubmit)', function(obj){
		var field = obj.field
		if(field['info[logo]'] == ''){
			dialog('请上传商家LOGO',0);return;
		}
		if(!field['info[withdraw_weixin]'])  field['info[withdraw_weixin]'] = 0
		if(!field['info[withdraw_bankcard]'])  field['info[withdraw_bankcard]'] = 0
		if(!field['info[withdraw_aliaccount]'])  field['info[withdraw_aliaccount]'] = 0
		if(!field['info[scorebdkyf]'])  field['info[scorebdkyf]'] = 0
		if(!field['info[wxkf]'])  field['info[wxkf]'] = 0

		field['xyinfo[content]'] = ueditor.getContent();

		//哪个选项卡 用于刷新后保持
		var tabindex = 0;
		$('.layui-tab-title>li').each(function(i,v){
			if($(this).hasClass('layui-this')){
				tabindex = i;
				console.log(i)
				return false;
			}
		})
		field.tabindex = tabindex;
		var index = layer.load();
		$.post('',field,function(data){
			layer.close(index);
			dialog(data.msg,data.status,data.url);
		})
	})

	layui.form.on('checkbox(gettjset)', function(data){
		console.log(data)
		if(data.elem.checked){
			$('#gettjset').hide();
		}else{
			$('#gettjset').show();
		}
	})
	layui.form.on('select(changetype)',function(data){
		$('#aliossset').hide()
		$('#qiniuset').hide()
		$('#cosset').hide()
		if(data.value==2){
			$('#aliossset').show()
		}
		if(data.value==3){
			$('#qiniuset').show()
		}
		if(data.value==4){
			$('#cosset').show()
		}
	})
  </script>


	{include file="public/copyright"/}
</body>
</html>