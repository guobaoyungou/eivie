<?php
/* Smarty version 3.1.33, created on 2026-04-02 14:56:11
  from '/home/www/ai.eivie.cn/huodong/wall/themes/meepo/login.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_69ce130ba0f1c9_69873789',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'f31078eccea72b0a28c7b4f6a3be484542db3ae8' => 
    array (
      0 => '/home/www/ai.eivie.cn/huodong/wall/themes/meepo/login.html',
      1 => 1712506480,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:themes/meepo/top_panel.html' => 1,
  ),
),false)) {
function content_69ce130ba0f1c9_69873789 (Smarty_Internal_Template $_smarty_tpl) {
?><link rel="stylesheet" type="text/css" href="themes/meepo/assets/css/screen_lottory_free.css">
    <style>

	::-webkit-scrollbar {
 		display: none;
	} 
	
	.member-list {
	height: 70%;
	overflow: scroll;
	} 
	.qiandao{
	background-color: rgba(0, 0, 0, 0.3);
	height: 100%;
	width: 100%;
	overflow: auto; 
	position:relative;
	} 
	.tuchu{
	position: absolute; 
	top: 0; 
	left: 0; 
	bottom:0;
	right:0;
	background-color:rgba(0,0,0,0.75); 
	z-index:2;
	display:none
	} 
		.tuchug {
	border-radius:100%;
	top:50%;
	left:50%;
	margin:-350px 0 0 -350px;
	width:700px;
	height:700px;
	position:absolute;
	z-index:4;
}
.tuchuk {
	background-color:#FFF;
	border-radius:10px;
	top:50%;
	left:50%;
	margin:-200px 0 0 -150px;
	width:300px;
	height:400px;
	position:absolute;
	z-index:4;
}
.tx {
	width:226px;
	height:226px;
	border-radius:100%;
	overflow:hidden;
	margin:40px 37px 15px 37px;
}
.xm {
	font-size:20px;
	color:#000;
	overflow:hidden;
	white-space:nowrap;
	text-overflow:ellipsis;
	padding:12px 0;
	text-align:center;
	position:absolute;
	bottom:0;
	margin:0;
	width:100%;
	background-color:rgba(245,245,245,0.57);
	border-radius:0 0 10px 10px;
}
.pm {
	font-size:20px;
	text-align:center;
	background-color:rgba(255,255,255,1);
	color:#000;
	margin:15px auto;
	padding:0;
	border-radius:100px;
	width:75%;
}
.pm .red {
	color:#ff7575;
}
.tuchu.view {
	display:block;
	-webkit-animation:vp 0.5s 8s ease forwards;
	-moz-animation:0.5s 8s ease forwards;
}
.view .tuchuk {
	-webkit-animation:vv 8s 0s ease forwards;
	-moz-animation:8s 0s ease forwards;
}
.view .tuchug {
	-webkit-animation:vg 6.5s 1s ease forwards;
	-moz-animation:6.5s 1s ease forwards;
	opacity:0;
}
@-webkit-keyframes vv {
	0% {
	-webkit-transform:scale(0.1);
	opacity:0;
}
10% {
	-webkit-transform:scale(1.5);
	opacity:1;
}
20% {
	-webkit-transform:scale(1);
	opacity:1;
}
90% {
	-webkit-transform:scale(1);
	opacity:1;
}
100% {
	-webkit-transform:scale(0);
	opacity:0;
}
}@-webkit-keyframes vp {
	0% {
	-webkit-transform:scale(1);
	opacity:1;
}
100% {
	-webkit-transform:scale(1);
	opacity:0;
}
}@-webkit-keyframes vg {
	0% {
	-webkit-transform:rotate(0);
	opacity:0;
}
10% {
	-webkit-transform:rotate(0);
	opacity:1;
}
90% {
	-webkit-transform:rotate(60deg);
	opacity:1;
}
100% {
	-webkit-transform:rotate(70deg);
	opacity:0;
}

}.qiandao ul {
	list-style:none;
	margin:0;
	padding:20px;
	box-sizing:border-box;
	overflow:hidden;
}
.qiandao ul li {
	float:left;
	width:144px;
	height:144px;
	text-align:center;
	padding:0;
	opacity:0;
	display:none;
	margin: 15px 15px 0 0
}
.qiaodaotx {
	width:108px;
	height:108px;
	margin:0 auto;
	border-radius:100%;
	overflow:hidden;
	opacity:0;
	display:none
}
.qiaodaoxm {
	font-size:16px;
	color:#FFF;
	overflow:hidden;
	white-space:nowrap;
	text-overflow:ellipsis;
	margin:0 auto;
	padding:5px 0;
	opacity:0;
	display:none;
	text-align: center;
	width: 100%;
}
.qiaodaopm {
	position:absolute;
	font-size:16px;
	top:105px;
	left:50%;
	background-color:rgba(255,255,255,1);
	color:#000;
	padding:0;
	border-radius:100px;
	width:60px;
	margin:0;
	margin-left:-30px;
	/*box-shadow:0px 1px 5px rgba(0,0,0,0.44);
	*/opacity:0;
	display:none
}
.qiandao ul li.qiaodaosf {
	display:block;
	opacity:1;
	position: relative;
}
.qiandao ul li.qiaodaoxs {
	display:block;
	opacity:1;
	-webkit-animation:xs 1s 0s ease forwards;
	-moz-animation:1s 0s ease forwards;
}
.qiaodaosf .qiaodaotx {
	-webkit-animation:f1 1.2s 0.5s ease forwards;
	-moz-animation:2s 0.5s ease forwards;
	display:block
}
.qiaodaosf .qiaodaopm {
	-webkit-animation:mc 1.2s 1.5s ease forwards;
	-moz-animation:2s 1.5s ease forwards;
	display:block
}
.qiaodaosf .qiaodaoxm {
	-webkit-animation:mc 1.2s 2s ease forwards;
	-moz-animation:2s 2s ease forwards;
	display:block
}
.qiandao ul li.showqiaodao {
	display:block;
	opacity:1;
}
.showqiaodao .qiaodaotx {
	opacity:1;
	display:block
}
.showqiaodao .qiaodaopm {
	opacity:1;
	display:block
}
.showqiaodao .qiaodaoxm {
	opacity:1;
	display:block
}
@-webkit-keyframes f1 {
	0% {
	-webkit-transform:scale(0.1);
	opacity:0;
}
70% {
	-webkit-transform:scale(1.5);
	opacity:1;
}
100% {
	-webkit-transform:scale(1);
	opacity:1;
}
}@-webkit-keyframes mc {
	0% {
	opacity:0;
}
100% {
	opacity:1;
}
}@-webkit-keyframes xs {
	0% {
	opacity:1;
	-webkit-transform:scale(1);
}
100% {
	opacity:0;
	-webkit-transform:scale(0);
}
}
/* #ddd { */
	/* display:none; */
/* } */
</style>
<?php echo '<script'; ?>
 src="themes/meepo/assets/js/qdbase.js?20154223" type="text/javascript" charset="utf-8"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="themes/meepo/assets/js/screen_qd.js" type="text/javascript" charset="utf-8"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="themes/meepo/assets/plugs/jquery.scrollTo-2.1.2/jquery.scrollTo.min.js" type="text/javascript" charset="utf-8"><?php echo '</script'; ?>
>
</head>
<body class="FUN WALL" >
<?php $_smarty_tpl->_subTemplateRender("file:themes/meepo/top_panel.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
<div class="Panel Lottery" style="display: block; opacity: 1;">
      <div class="tuchu ">
        <div class="tuchug">
          <img src="themes/meepo/assets/images/icon/bbb.png"></div>
        <div class="tuchuk">
          <img class="tx" id="qdtx" src="">
          <p class="xm" id="qdname">官方签到女神</p></div>
      </div>
      <div class="qiandao" id="ddd" >
      <ul id="divDetail"></ul>
      </div>
      <?php echo '<script'; ?>
 src="themes/meepo/assets/plugs/hotkeys-master/dist/hotkeys.min.js"><?php echo '</script'; ?>
>
		<?php echo '<script'; ?>
 src="themes/meepo/assets/js/bindhotkeys.js"><?php echo '</script'; ?>
>
      <?php echo '<script'; ?>
>
      //签到人数
      var qiandaonum=<?php echo $_smarty_tpl->tpl_vars['qiandaonum']->value;?>
;
      var showcount=0;
    

   	function showuser(){
   		if(notshowusers.length<=0){
   			setTimeout(function(){
	   			showuser();
	   		},3000);
   			return;
   		}
   		showcount++;
   		var user=notshowusers.shift();
   		var delay=1500;
   		var offsetdelay=0;
   		if(showcount>qiandaonum){
   			//需要放大显示
   			$('#qdtx').attr('src',user.avatar);
   			$('#qdname').text(user.nickname)
   			$(".tuchu").addClass('view');
   			delay=7000;
   			offsetdelay=3000;
   		}
   		setTimeout(function(){
   			$(".tuchu").removeClass('view');
   			var item = '<li id="dh' + showcount + '" class="qiaodaosf" ><img class="qiaodaotx"  id="hd' + showcount + '" src="' + user.avatar + '"><p id="nc' + showcount + '" class="qiaodaoxm">' + user.nickname + '</p></li>';
	   		$('#divDetail').append(item);
	      $('#ddd').scrollTo($('#divDetail').height(),500);
   		},delay);
   		//添加一个头像
   		setTimeout(function(){
   			showuser();
   		},delay+offsetdelay);
   	}
   	//没有显示的人
    var notshowusers=[];
    //获取数据
		function getjson(lastsignorder) {
			
			$.getJSON(Path_url('ajax_act_get_sign.php'), {
				mid: lastsignorder,
				num:50,
			},
			function(json) {
				if(json.code<0){
					return false;
				}
				if(json.data.users.length>0){
					notshowusers=notshowusers.concat(json.data.users);
				}
				setTimeout("getjson(" + json.data.mid + ")", 5000);
			});
		}

		$(function(){
			getjson(0);
			setTimeout(function(){showuser()},3000);
		});
		
		<?php echo '</script'; ?>
>
</div><?php }
}
