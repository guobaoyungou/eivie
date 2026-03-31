<?php
/* Smarty version 3.1.33, created on 2024-06-07 11:01:43
  from '/www/wwwroot/19.71jc.cn/wall/themes/meepo/3dsign.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_66627817aa85a4_41530045',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '2b6b0ce9fea1098b71e8a7b9200679aa5c3e4fae' => 
    array (
      0 => '/www/wwwroot/19.71jc.cn/wall/themes/meepo/3dsign.html',
      1 => 1712506480,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:themes/meepo/top_panel.html' => 1,
  ),
),false)) {
function content_66627817aa85a4_41530045 (Smarty_Internal_Template $_smarty_tpl) {
?><link rel="stylesheet" type="text/css" href="themes/meepo/assets/css/screen_lottory_free.css">
<link rel="stylesheet" href="themes/meepo/assets/css/3d.css">
<link rel="stylesheet" href="themes/meepo/assets/css/3dstyle.css" media="screen" type="text/css">
<link rel="stylesheet" href="themes/meepo/assets/css/normalize.css">
<?php echo '<script'; ?>
 src="themes/meepo/assets/js/qdbase.js" type="text/javascript" charset="utf-8"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="themes/meepo/assets/js/screen_qd.js" type="text/javascript" charset="utf-8"><?php echo '</script'; ?>
>
<link rel="stylesheet" href="themes/meepo/assets/css/qd.css" type="text/css">
</head>
<body class="FUN WALL" >
<div class="ui" style="display:none">
	<input class="ui-input" type="text" />
	<span class="ui-return"></span> 
</div> 
<input type="hidden" id="max_id" name="max_id" value="<?php echo $_smarty_tpl->tpl_vars['qd_maxid']->value;?>
">
<?php $_smarty_tpl->_subTemplateRender("file:themes/meepo/top_panel.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
<div id="container" style="display:none;opacity: 0;"></div>
<div id="earth" style="display:none;"></div>
<canvas class="canvas" style="display:none;opacity: 0;"></canvas>
<div class="tuchu ">
  <div class="tuchug">
    <img src="themes/meepo/assets/images/icon/bbb.png"></div>
  <div class="tuchuk">
    <img class="tx" id="qdtx" src="http://wx.qlogo.cn/mmopen/ctSue0WnfibLR4ponCgXLv4G9Y43YFEXv5UboZuvHbvwkz6QXEYvw4RyuktMxhicq22efPTnwhPRhT67FIia2dmKDlIb4falNbN/132">
    <p class="pm">第
      <span class="red" id="qdnums">0</span>位签到</p>
    <p class="xm" id="qdname">官方签到女神</p></div>
</div>
<div class="Panel Lottery" style="display: none; opacity: 1;">
<div id="ddd"></div>
  <?php echo '<script'; ?>
 type="text/javascript">
    	var is_show_info = 1;
    	var hFont;
    /*** html节点字体大小随屏幕大小改变 ***/
    (function (doc, win) {
        var docEl = doc.documentElement,
            resizeEvt = 'orientationchange' in window ? 'orientationchange' : 'resize',
            recalc = function () {
                var clientWidth = docEl.clientWidth;
                if (!clientWidth) return;
                hFont = 20 * (clientWidth / 320);
                docEl.style.fontSize = hFont + 'px';
            };

        if (!doc.addEventListener) return;
        win.addEventListener(resizeEvt, recalc, false);
        doc.addEventListener('DOMContentLoaded', recalc, false);
    })(document, window);	   
	function senddh(){
		$('.canvas').show();
		$('.canvas,#container').css('opacity','1');
	}
	senddh();
	window.AvatarGap=<?php echo $_smarty_tpl->tpl_vars['threedimensional_config']->value['avatargap'];?>
;
	window.AvatarSize=<?php echo $_smarty_tpl->tpl_vars['threedimensional_config']->value['avatarsize'];?>
;
	window.AvatarNum=<?php echo $_smarty_tpl->tpl_vars['threedimensional_config']->value['avatarnum'];?>
;
<?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="themes/meepo/assets/js/2d.js?"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="themes/meepo/assets/plugs/hotkeys-master/dist/hotkeys.min.js"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="themes/meepo/assets/js/bindhotkeys.js"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 type="text/javascript">

	var new_sign_arr=[];
	function shownewsign(){
		if(new_sign_arr.length<=0){
			return false;
		}
		var person=new_sign_arr.shift();
		$("#qdtx").attr("src", person.avatar);
        $("#qdnums").html(person.signorder);
        $("#qdname").html(person.nickname);
		$('.tuchu').addClass('view');
		window.setTimeout(function(){$(".tuchu").removeClass('view');},3000);
	}
	
	$(function(){
		setInterval(function(){shownewsign()},5000);
	})
	
    S.Drawing.init('.canvas');
    /* 默认图片列表 */
    var placeholder_image_arr = ["themes\/meepo\/assets\/images\/noavatar.jpg"];
    var placeholder_image_cnt = placeholder_image_arr.length;
    var placeholder_image_index = 0;
    var default_placeholder_image = placeholder_image_arr[0];
/* 已经签到的用户列表 */
var personArray = <?php echo $_smarty_tpl->tpl_vars['personJson']->value;?>
;

var edit_personArray = new Array;
var placeholder_image = default_placeholder_image;
var table = new Array;

var l=personArray.length;
for (var i=0;i<160;i++){
	table[i]=new Object();
	if(l>0){
		table[i] = personArray[i%l];
		table[i].src=personArray[i%l].avatar;
	}else{
		if(placeholder_image_cnt>1){
			table[i].src=  placeholder_image_arr[i%placeholder_image_cnt];
		}else{
			table[i].src=  placeholder_image_arr[0];
		}
	}
}

//打乱数组顺序
table = table.sort(randsort);
function randsort(){
	return Math.random()>.5?-1:1;
}
function resetTable(){
	var num=160;
	var pal=personArray.length;
	if(pal<=0){
		return;
	}
	for(var i=0;i<num;i++){
		table[i] = personArray[i%pal];
		table[i].src=personArray[i%pal].avatar;
	}
	table = table.sort(randsort);	
}
/* 动画墙显示 屏 列表 */
var signwall_show_str = "<?php echo $_smarty_tpl->tpl_vars['threedimensional_config']->value['datastr'];?>
"; 
// var signwall_show_str = "#earth";
/* 动画墙 切换时间？做什么用？ */
var show_interval = "0"; // 间隔时间
var return_array = new Array();
function getArrayItems(arr, num) {
    //新建一个数组,将传入的数组复制过来,用于运算,而不要直接操作传入的数组;
    var temp_array = new Array();
    for (var index in arr) {
        temp_array.push(arr[index]);
    }
    //取出的数值项,保存在此数组
    for (var i = 0; i<num; i++) {
        //判断如果数组还有可以取出的元素,以防下标越界
        if (temp_array.length>0) {
            //在数组中产生一个随机索引
            var arrIndex = Math.floor(Math.random()*temp_array.length);
            //将此随机索引的对应的数组元素值复制出来
            return_array[i] = temp_array[arrIndex];
            //然后删掉此索引的数组元素,这时候temp_array变为新的数组
            temp_array.splice(arrIndex, 1);
        } else {
            //数组中数据项取完后,退出循环,比如数组本来只有10项,但要求取出20项.
            return false;
        }
    }
    return return_array;
}
getArrayItems(personArray,window.AvatarNum);

window.onload = function(){ 
	S.init();
	S.UI.simulate(signwall_show_str);
}
var signBox = $('.signBox'),
imgShow = $('.imgShow'),
newPic = new Array(),
signNo = 0,
className = " rightBottom ",
name,job,company,text,bowtie;
setInterval(get_new_sign_list, 2000);
function get_new_sign_list(){
  	var data = {};
  	data.mid =  $('#max_id').val();
  	$.ajax({
  		url : "ajax_act_get_new_sign.php",
  		dataType:"JSON",
  		type:"GET",
  		data:data,
  		success:function(result){  
  				if (result.mid){
  					$('#max_id').val(result.mid);
  				}  	
				if (result.omid < result.mid) { 
						new_sign_arr.push(result);
						personArray.push(result);
						resetTable();
						var elements=$('.element');
						var len=personArray.length;
						for(var i=0,l=elements.length;i<l;i++){
							if(i%len==0){
								$('.element').eq(i).find('img').attr('src',result.avatar);
							}
							
						}
  				}
  		}//success
  	});
};
setInterval(function() {
	getArrayItems(personArray,window.AvatarNum);
}, 10 * 1000);
  	<?php echo '</script'; ?>
>
	<?php echo '<script'; ?>
 src="/assets/js/three.min.js"><?php echo '</script'; ?>
>
	<?php echo '<script'; ?>
 src="/assets/js/CSS3DRenderer.min.js"><?php echo '</script'; ?>
>
 	<?php echo '<script'; ?>
 src="themes/meepo/assets/js/tween.min.js"><?php echo '</script'; ?>
>
  	<?php echo '<script'; ?>
 src="themes/meepo/assets/js/3d.js"><?php echo '</script'; ?>
>
  	<?php echo '<script'; ?>
 type="text/javascript">
		init();
		animate();
		transform( targets.table, 2000, 'table', 10);
		var signAlert = $("#signAlert");
	    /* 新增功能，头像弹窗的展示与关闭 */
		function addEventHandler(ele, event, hanlder) {
				if (ele.addEventListener) {
						ele.addEventListener(event, hanlder, false);
				} else if (ele.attachEvent) {
						ele.attachEvent("on"+event, hanlder);
				} else  {
						ele["on" + event] = hanlder;
				}
		 }
		function isNull(value) {
			if (typeof value === "undefined" || typeof value === "" || typeof value === "null" || value == "null" || value == null || value == "" || value == "undefined") {
				return true;
			} else {
				return false;
			}
		}
		
 <?php echo '</script'; ?>
>
</div><?php }
}
