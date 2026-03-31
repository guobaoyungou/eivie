<?php
/* Smarty version 3.1.33, created on 2024-06-07 11:01:48
  from '/www/wwwroot/19.71jc.cn/wall/themes/meepo/vote.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_6662781c74f4b6_41352724',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '09b5621b361d47dcb9d04c18e8373f325f1b0b1c' => 
    array (
      0 => '/www/wwwroot/19.71jc.cn/wall/themes/meepo/vote.html',
      1 => 1712506480,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:themes/meepo/top_panel.html' => 1,
  ),
),false)) {
function content_6662781c74f4b6_41352724 (Smarty_Internal_Template $_smarty_tpl) {
?><link rel="stylesheet" type="text/css" href="themes/meepo/assets/css/screen_wall.css">
<link rel="stylesheet" type="text/css" href="themes/meepo/assets/css/main2.css" media="all">
<?php if ($_smarty_tpl->tpl_vars['wall_config']->value['voteshowway'] == 1) {?>
<link rel="stylesheet" type="text/css" href="themes/meepo/assets/css/votecss1.css" media="all">
<?php } else { ?>
<link rel="stylesheet" type="text/css" href="themes/meepo/assets/css/votecss2.css" media="all">
<?php }?>
<style>

.round-welcome {
 display:none;
position: absolute;
left: 0;
top: 0;
width: 100%;
height: 91%;
z-index: 99999;
background-color: rgba(0, 0, 0, 0.9);
}
.round-welcome .label .activity_key {
color: #f6ff27;
padding: 0 4px;
font-weight: bold;
}
.round-welcome img {
width: 430px;
height: 430px;
position: absolute;
left: 50%;
top: 50%;
margin-top: -215px;
margin-left: -215px;
border-radius: 15px;
box-shadow: 0 10px 10px #000;
}
.round-label {
position: absolute;
width: 128px;
height: 64px;
background: url(themes/meepo/assets/images/vote.png) top center no-repeat;
padding: 80px 0 0 0;
text-align: center;
font-size: 25px;
color: #fff;
top: 50%;
margin-top: -72px;
left: 50%;
margin-left: -420px;
cursor: default;
font-weight: bold;
}

.button-start {
position: absolute;
width: 128px;
height: 64px;
background: url(themes/meepo/assets/images/start.png) top center no-repeat;
padding: 80px 0 0 0;
text-align: center;
font-size: 25px;
color: #fff;
top: 50%;
margin-top: -72px;
right: 50%;
margin-right: -420px;
cursor: pointer;
}
.round-welcome .label {
position: absolute;
color: #fff;
line-height: 70px;
text-align: center;
font-size: 35px;
width: 100%;
}
.round-welcome .label.bottom {
bottom: 0;
}
.votebody h2{     font-size: 20px;
    padding: 15px 0;
    color: #FFF;
    text-align: center;
    font-weight: normal;
    position: absolute;
    width: 100%;}
.lunci{ display: block;
margin: 0 auto;
text-align: center;
position: absolute;
width: 100%;
bottom: 10px;}
.lunci a{display: inline-block;
padding: 5px 10px;
border: 1px solid rgba(255, 255, 255, 0.45);
font-size: 14px;
color: rgba(255, 255, 255, 0.65);
margin-right: 10px;
border-radius: 10px;}
.lunci a:hover{     border: 1px solid rgba(255, 255, 255, 0.7);
    background-color: rgba(255, 255, 255,0.5);
color:  #000;}
.lunci a.active{     border: 1px solid rgba(255, 255, 255, 0.7);
    background-color: rgb(255, 255, 255);
color:  #000;}

.lunci a.end{ border: 2px solid rgba(255, 255, 255, 0.5);
background-color: rgba(255, 255, 255, 0.5) ;
color:  #000;
font-size: 16px;}

</style>

<?php echo '<script'; ?>
 src="themes/meepo/assets/js/base3.js" type="text/javascript" charset="utf-8"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="themes/meepo/assets/js/jquery_flexslider.js" type="text/javascript"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="themes/meepo/assets/js/audio.js" type="text/javascript"><?php echo '</script'; ?>
>
<!-- <?php echo '<script'; ?>
 src="themes/meepo/assets/js/screen_photo.js" type="text/javascript" charset="utf-8"><?php echo '</script'; ?>
> -->
<?php echo '<script'; ?>
 src="themes/meepo/assets/js/jquery.nicescroll.min.js" type="text/javascript" charset="utf-8"><?php echo '</script'; ?>
>
</head>
<body class="FUN WALL" >
<?php $_smarty_tpl->_subTemplateRender("file:themes/meepo/top_panel.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
<div class="Panel MsgList"  >
 <div class="votebody" id="container"   >
   <h2><?php echo $_smarty_tpl->tpl_vars['vote_config']->value['votetitle'];?>
</h2>
   <div  class="votebox"><?php if ($_smarty_tpl->tpl_vars['vote_config']->value['status'] == 2) {?><div class="voteend btn_vote_status open" data="2"></div><?php } else { ?><div class="votestart btn_vote_status open" data="1"></div><?php }?>
   <div class="vote-wall-btns">
   	<?php if ($_smarty_tpl->tpl_vars['preid']->value != null) {?>
   	<span class="vote-btn"><a href="vote.php?id=<?php echo $_smarty_tpl->tpl_vars['preid']->value;?>
">上一轮</a></span>
   	<?php }?>
   	<?php if ($_smarty_tpl->tpl_vars['nextid']->value != null) {?>
   	<span class="vote-btn"><a href="vote.php?id=<?php echo $_smarty_tpl->tpl_vars['nextid']->value;?>
">下一轮</a></span>
   	<?php }?>
   </div>
</div>
<div class="vote-wall">
	<div class="vote-wall-side">
		<div id="scene_body_bar_rcd">

		</div>
	</div>
	<div class="maintaible">
		<?php if ($_smarty_tpl->tpl_vars['vote_config']->value['showtype'] == 1) {?>
		<div id="echart-table" class="echart-table"></div>
		<?php }?>
		<?php if ($_smarty_tpl->tpl_vars['vote_config']->value['showtype'] == 2) {?>
            <div class="vote-horizontal" ></div>

		<!-- <div class="vote-horizontal" ></div> -->
		<?php }?>
		<?php if ($_smarty_tpl->tpl_vars['vote_config']->value['showtype'] == 3) {?>
		<div class="vote-pics"></div>
		<?php }?>
	</div>
</div>
</div>
</div>
<?php echo '<script'; ?>
 src="themes/meepo/assets/js/echarts.min.js" type="text/javascript" charset="utf-8"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
 src="themes/meepo/assets/plugs/hotkeys-master/dist/hotkeys.min.js"><?php echo '</script'; ?>
>
        <?php echo '<script'; ?>
 src="themes/meepo/assets/js/bindhotkeys.js"><?php echo '</script'; ?>
>
<?php echo '<script'; ?>
>

var idd;
$(function(){
	getjson();
	
	<?php if ($_smarty_tpl->tpl_vars['wall_config']->value['voteopen'] != 2) {?>
		window.clearInterval(idd);
		idd=window.setInterval(getjson,2000);
	<?php }?>
	
	// alert(1);
	$("#scene_body_bar_rcd,.vote-horizontal").niceScroll();
	$('#scene_body_bar_head').click(function() {
		if ($('#scene_body_bar_head').hasClass('bar_expand')) {
			$('#scene_body_bar_head').removeClass('bar_expand').css('height',38+'px');
		}else{
			$('#scene_body_bar_head').addClass('bar_expand').css('height',$('.table-tyle-select').height()+38+'px');
		};
	});
	$('.btn_vote_status').bind('click',function(){
		var status=$('.btn_vote_status').attr('data');
		status=status==1?2:1;
		$.ajax({
			"url":"ajax_act_save_vote_status.php",
			"data":{"id":<?php echo $_smarty_tpl->tpl_vars['vote_config']->value['id'];?>
,"status":status},
			"type":"get",
			"dataType":"json",
			"success":function(json){
				if(json.code>0){
					if(status==1){
						$('.btn_vote_status').removeClass('voteend');
						$('.btn_vote_status').addClass('votestart');

					}else{
						$('.btn_vote_status').removeClass('votestart');
						$('.btn_vote_status').addClass('voteend');

					}
					$('.btn_vote_status').attr('data',status);
				}
			}
		});
	});
});

<?php if ($_smarty_tpl->tpl_vars['vote_config']->value['showtype'] == 1) {?>
// 基于准备好的dom，初始化echarts实例
var myChart = echarts.init(document.getElementById('echart-table'));
//myChart.style.width=$('.vote-wall').width();
// 指定图表的配置项和数据
<?php }?>


// 基于准备好的dom，初始化echarts实例

function getjson(){
	$.getJSON(PATH_ACTIVITY + Path_url('ajax_act_get_vote_record.php'), {
		"id":<?php echo $_smarty_tpl->tpl_vars['vote_config']->value['id'];?>
,
            },function(json){
            	if(json.type==1){
                    var left_item_template='<div class="btnItem" style="background-color: #00adcb;">[[pic]]<div><p>[[voteitem]]</p><div><b>[[cnt]]</b><span>[[unit]]，第[[num]]名</span></div></div></div>';
            		var option = {
					    xAxis: {
					        type: 'category',
					        data: [],
					        axisLine:{
					            lineStyle:{
					                color:'white',
					            },
					        },
					    },
					    yAxis: {
					        type: 'value',
					        axisLine:{
					            lineStyle:{
					                color:'white',
					            },
					        },
					    },
					    series: [{
					        data: [],
					        type: 'bar',
					        //配置样式
					        itemStyle: {
					            //通常情况下：
					            normal: {
					                color: function(params) {
					                    var colorList = ['rgb(164,205,238)', 'rgb(42,170,227)', 'rgb(25,46,94)', 'rgb(195,229,235)'];
					                    return colorList[params.dataIndex];
					                }
					            },
					            //鼠标悬停时：
					            //emphasis: {
					                //shadowBlur: 10,
					                //shadowOffsetX: 0,
					                //shadowColor: 'rgba(0, 0, 0, 0.5)'
					            //}
					        },
					        //设置柱子的宽度
					        // barWidth : 120,
					    }]
					};

            		for(var i=0,l=json.data.length;i<l;i++){
            			var index=i+1;
            			if(i<10){
            				option.xAxis.data[i]=json.data[i].voteitem;
            				var percent=json.total==0?0:Math.floor(json.data[i].cnt/json.total*100);
            				option.series[0].data[i]=percent;
            			}else{
            				var  itemhtml=left_item_template;
                            var img='';
                            itemhtml=itemhtml.replace('[[pic]]',img);
            				// itemhtml=itemhtml.replace('[[pic]]',json.data[i].imagepath);
            				itemhtml=itemhtml.replace('[[cnt]]',json.data[i].cnt);
            				itemhtml=itemhtml.replace('[[num]]',index);
							itemhtml=itemhtml.replace('[[voteitem]]',json.data[i].voteitem);
							itemhtml=itemhtml.replace('[[unit]]',"<?php echo $_smarty_tpl->tpl_vars['vote_config']->value['unit'];?>
");
							
            				left_html+=itemhtml;
            			}
            		}
                    if(json.data.length<=10){
                        $('.vote-wall').css({'padding-left':'0px'});
                    }
					// 指定图表的配置项和数据
					myChart.setOption(option);
					myChart.resize();
					$('#scene_body_bar_rcd').html(left_html);
            	}
            	if(json.type==2){
                    var colors=['mint','red','lila','orange','gray'];
                    var totalheight=$('.vote-horizontal').height();
                    var barheight=Math.floor((totalheight-150)/10);
                    var item_template='<div class="bar-item"><div class="num-rank">[[num]]</div><div class="bar active [[color]]" style="width:[[percent]]%;height:'+barheight+'px;"></div><div class="data-skill">[[voteitem]]<span>([[cnt]][[unit]])</span></div></div>';

            		var left_item_template='<div class="btnItem" style="background-color: #00adcb;">[[pic]]<div><p>[[voteitem]]</p><div><b>[[cnt]]</b><span>[[unit]]，第[[num]]名</span></div></div></div>';
            		var html='';
            		var left_html='';
            		for(var i=0,l=json.data.length;i<l;i++){
            			var index=i+1;
            			if(i<10){
            				var itemhtml=item_template;
            				itemhtml=itemhtml.replace('[[cnt]]',json.data[i].cnt);
                            itemhtml=itemhtml.replace('[[color]]',colors[i%5]);
            				var reg=/\[\[num\]\]/g;
            				itemhtml=itemhtml.replace(reg,index);
            				var percent=json.total==0?0:Math.floor(json.data[i].cnt/json.total*100);
            				itemhtml=itemhtml.replace('[[percent]]',percent);
							itemhtml=itemhtml.replace('[[voteitem]]',json.data[i].voteitem);
							itemhtml=itemhtml.replace('[[unit]]',"<?php echo $_smarty_tpl->tpl_vars['vote_config']->value['unit'];?>
");
							
            				html+=itemhtml;
            			}else{
            				var itemhtml=left_item_template;
                    // var img=json.data[i].imageid>0?'<img src="'+json.data[i].imagepath+'">':'';
                    var img='';
            				itemhtml=itemhtml.replace('[[pic]]',img);
            				itemhtml=itemhtml.replace('[[cnt]]',json.data[i].cnt);
            				itemhtml=itemhtml.replace('[[num]]',index);
							itemhtml=itemhtml.replace('[[voteitem]]',json.data[i].voteitem);
							itemhtml=itemhtml.replace('[[unit]]',"<?php echo $_smarty_tpl->tpl_vars['vote_config']->value['unit'];?>
");
            				left_html+=itemhtml;
            			}
            		}
            		// console.log(html);
                if(json.data.length<=10){
                  $('.vote-wall').css({'padding-left':'0px'});
                }
            		$('.vote-horizontal').html(html);
            		$('#scene_body_bar_rcd').html(left_html);
            	}
            	if(json.type==3){
            		//图片形式的
            		var item_template='<div class="vote-pics-item"><div class="vote-item-pic"><img src="[[pic]]"><p><b>[[cnt]]</b>[[unit]]</p><span class="rb-arrow"></span><span class="vote-rank">[[num]]</span></div><div class="vote-name">[[voteitem]]</div></div>';
            		var left_item_template='<div class="btnItem" style="background-color: #00adcb;"><img src="[[pic]]"><div><p>[[voteitem]]</p><div><b>[[cnt]]</b><span>[[unit]]，第[[num]]名</span></div></div></div>';
            		var html='';
            		var left_html='';
            		for(var i=0,l=json.data.length;i<l;i++){
            			var index=i+1;
            			if(i<10){
            				var itemhtml=item_template;
            				itemhtml=itemhtml.replace('[[pic]]',json.data[i].imagepath);
            				itemhtml=itemhtml.replace('[[cnt]]',json.data[i].cnt);
            				itemhtml=itemhtml.replace('[[num]]',index);
							itemhtml=itemhtml.replace('[[voteitem]]',json.data[i].voteitem);
							itemhtml=itemhtml.replace('[[unit]]',"<?php echo $_smarty_tpl->tpl_vars['vote_config']->value['unit'];?>
");
            				html+=itemhtml;
            			}else{
            				var  itemhtml=left_item_template;
            				itemhtml=itemhtml.replace('[[pic]]',json.data[i].imagepath);
            				itemhtml=itemhtml.replace('[[cnt]]',json.data[i].cnt);
            				itemhtml=itemhtml.replace('[[num]]',index);
							itemhtml=itemhtml.replace('[[voteitem]]',json.data[i].voteitem);
							itemhtml=itemhtml.replace('[[unit]]',"<?php echo $_smarty_tpl->tpl_vars['vote_config']->value['unit'];?>
");
            				left_html+=itemhtml;
            			}
            		}
                if(json.data.length<=10){
                  $('.vote-wall').css({'padding-left':'0px'});
                }
            		$('.vote-pics').html(html);
            		var maintaibleheight=$('.maintaible').innerHeight()-30;
            		var picwitdth=$('.vote-pics-item').width();
            		var picheight=picwitdth*9/16;
            		$('.vote-item-pic').height(picheight);
            		var vote_item_pic_width=$('.vote-item-pic').width();
            		$('.vote-item-pic').width(picwitdth)
            		$('.vote-item-pic').height(picheight)
                    $('.vote-pics-item').css('margin-bottom',(maintaibleheight-(picheight*3)-120)/3+'px');
            		$('#scene_body_bar_rcd').html(left_html);
            	}

	});
}

(function(k, e) {
    var start = function() {
        g = e(".Panel.MsgList"),
	    k.WBActivity.hideLoading();
	    g.css({
	        display: "block",
	        opacity: 1
	    });
	}
	start();
})(window, jQuery);

<?php echo '</script'; ?>
><?php }
}
