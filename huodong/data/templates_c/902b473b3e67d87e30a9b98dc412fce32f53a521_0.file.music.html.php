<?php
/* Smarty version 3.1.33, created on 2024-06-07 10:58:45
  from '/www/wwwroot/19.71jc.cn/myadmin/templates/music.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_66627765a3a342_85670500',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '902b473b3e67d87e30a9b98dc412fce32f53a521' => 
    array (
      0 => '/www/wwwroot/19.71jc.cn/myadmin/templates/music.html',
      1 => 1712506480,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:templates/html_header.html' => 1,
    'file:templates/html_sidebar.html' => 1,
    'file:templates/html_footercontent.html' => 1,
    'file:templates/html_footer.html' => 1,
  ),
),false)) {
function content_66627765a3a342_85670500 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_subTemplateRender("file:templates/html_header.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
<!-- /section:basics/navbar.layout -->
<div class="main-container ace-save-state" id="main-container">
    <?php echo '<script'; ?>
 type="text/javascript">
        
				try{ace.settings.loadState('main-container')}catch(e){}
			
    <?php echo '</script'; ?>
>
    <!-- #section:basics/sidebar -->
    <?php $_smarty_tpl->_subTemplateRender("file:templates/html_sidebar.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
    <!-- /section:basics/sidebar -->
    <div class="main-content">
        <div class="main-content-inner">
            <!-- #section:basics/content.breadcrumbs -->
            <div class="breadcrumbs ace-save-state" id="breadcrumbs">
                <ul class="breadcrumb">
                    <li>
                        <i class="ace-icon fa fa-home home-icon">
                        </i>
                        <a href="index.php">
                            首页
                        </a>
                    </li>
                    <li class="active">
                        <?php echo $_smarty_tpl->tpl_vars['title']->value;?>

                    </li>
                </ul>
                <!-- /.breadcrumb -->
                <!-- /section:basics/content.searchbox -->
            </div>
            <!-- /section:basics/content.breadcrumbs -->
            <div class="page-content">
                <h3 class="header smaller lighter blue">
                    <?php echo $_smarty_tpl->tpl_vars['title']->value;?>

                    <small>
                        系统的配乐,默认音乐是《拉德斯基进行曲》，不需要音乐的页面可以关闭
                    </small>
                </h3>
                <div class="row">
                    <div class="col-xs-12">
                        <!-- PAGE CONTENT BEGINS -->
                        <div class="row">
                            <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['bgmusics']->value, 'item');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['item']->value) {
?>
                            <div class="col-xs-6">
                                <div class="widget-box">
                                    <div class="widget-header">
                                        <h4 class="smaller">
                                            <?php echo $_smarty_tpl->tpl_vars['item']->value['name'];?>

                                        </h4>
                                    </div>
                                    <div class="widget-body">
                                        <div class="widget-main" style="height:200px;">
                                            <form action="domusic.php?action=setbgmusic" class="form-horizontal" enctype="multipart/form-data" method="post" role="form">
                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right" for="bgmusic">
                                                        默认状态：
                                                    </label>
                                                    <div class="col-sm-9">
                                                        <label>
                                                            <input name="bgmusicstatus" class="ace ace-switch ace-switch-3" type="checkbox" <?php if ($_smarty_tpl->tpl_vars['item']->value['bgmusicstatus'] == 1) {?>checked<?php }?> />
                                                            <span class="lbl" onclick=""></span>
                                                            
                                                        </label>
                                                    </div>
                                                </div>
                                                <div class="form-group">
                                                    <label class="col-sm-3 control-label no-padding-right" for="bgmusic">
                                                        音乐：
                                                    </label>
                                                    <div class="col-sm-9">
                                                        <input class="audiouploader" multiple="" name="bgmusicpath" type="file" value=""/>
                                                        <div class="hr hr-12 dotted">
                                                        </div>
                                                        <input type="hidden" name="plugname" value="<?php echo $_smarty_tpl->tpl_vars['item']->value['plugname'];?>
"/>
                                                        <input type="hidden" name="bgmusic" value="<?php echo $_smarty_tpl->tpl_vars['item']->value['bgmusic'];?>
">
                                                        <button class="btn btn-sm btn-primary" type="submit">
                                                            保存
                                                        </button>
                                                        <span class="alert-info">
                                                            不要超过<?php echo $_smarty_tpl->tpl_vars['maxfilesize']->value;?>

                                                        </span>
                                                    </div>
                                                </div>
                                            </form>
                                            <?php if ($_smarty_tpl->tpl_vars['item']->value['bgmusicpath'] != '') {?>
                                            <audio controls="controls">
                                                <source src="<?php echo $_smarty_tpl->tpl_vars['item']->value['bgmusicpath'];?>
" type="audio/mpeg">
                                                </source>
                                                您的浏览器不支持音乐播放，您可以下载这个谷歌浏览器， https://pan.baidu.com/s/1bp0MHFx，效果和性能会好一点哦
                                            </audio>
                                            <?php }?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                           
                        </div>
                        <!-- PAGE CONTENT ENDS -->
                    </div>
                    <!-- /.col -->
                </div>
                <!-- /.row -->
            </div>
            <!-- /.page-content -->
        </div>
    </div>
    <!-- /.main-content -->
    <?php $_smarty_tpl->_subTemplateRender("file:templates/html_footercontent.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
    <!-- 写每个页面自定的js -->
    <?php echo '<script'; ?>
 type="text/javascript">
        $(function(){
        	// $('.audiouploader').ace_file_input({
        	// 	no_file:'No File ...',
        	// 	btn_choose:'点击此处选择音乐',
        	// 	btn_change:'点击此处选择音乐',
        	// 	droppable:false,
        	// 	onchange:null,
        	// 	thumbnail:false, //| true | large
        	// 	allowExt:['mp3'],
        	// });
        })
    <?php echo '</script'; ?>
>
    <?php $_smarty_tpl->_subTemplateRender("file:templates/html_footer.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
</div><?php }
}
