<?php
/* Smarty version 3.1.33, created on 2024-06-07 11:00:56
  from '/www/wwwroot/19.71jc.cn/myadmin/templates/background.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_666277e81904f9_72531996',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '5cd81145143f75249d34e6533fa58893b89dd468' => 
    array (
      0 => '/www/wwwroot/19.71jc.cn/myadmin/templates/background.html',
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
function content_666277e81904f9_72531996 (Smarty_Internal_Template $_smarty_tpl) {
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
                        设置各个功能的背景图，如果没有设置会显示默认背景图。 <span class="alert-info">大屏幕长宽比一致即可，不要超过500kb&nbsp;<a href="https://pan.baidu.com/s/1N7PuQjr-g2iND-SEzDeseg" target="_blank">点此下载推荐素材</a></span>
                    </small>
                </h3>
                <div class="row">
                    <div class="col-xs-12">
                        <!-- PAGE CONTENT BEGINS -->
                        <?php echo $_smarty_tpl->tpl_vars['diyad']->value;?>

                        <div class="row">
                            <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['images']->value, 'item');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['item']->value) {
?>
                            <?php if ($_smarty_tpl->tpl_vars['item']->value['plugname'] != 'shuqian'&$_smarty_tpl->tpl_vars['item']->value['plugname'] != 'pashu') {?>
                            <div class="col-xs-6">
                                <div class="widget-box">
                                    <div class="widget-header">
                                        <h4 class="smaller">
                                            <?php echo $_smarty_tpl->tpl_vars['item']->value['name'];?>

                                        </h4>
                                    </div>
                                    <div class="widget-body">
                                        <div class="widget-main" style="height: 300px;">
                                            <div  class="bg-uploader">
                                                <!--用来存放文件信息-->
                                                <div  class="uploader-list">
                                                        <div  class="item">
                                                        <?php if ($_smarty_tpl->tpl_vars['item']->value['bgtype'] == 1) {?>
                                                        <img src="<?php echo $_smarty_tpl->tpl_vars['item']->value['attachmentpath'];?>
" style="max-width: 200px;max-height:150px;"/>
                                                        <?php } else { ?>
                                                        <video src="<?php echo $_smarty_tpl->tpl_vars['item']->value['attachmentpath'];?>
" style="object-fit:fill;max-width:200px;height:auto;max-height:150px;" autoplay="autoplay" autobuffer autoloop loop controls="controls"  ></video>
                                                        <?php }?>
                                                        <h4 class="info"></h4>
                                                        <p class="state"></p></div>
                                                </div>
                                                <div class="btns">
                                                    <div id="<?php echo $_smarty_tpl->tpl_vars['item']->value['plugname'];?>
_bg" class="selectfile">选择文件</div>
                                                    <input type="hidden" name="plugname" value="<?php echo $_smarty_tpl->tpl_vars['item']->value['plugname'];?>
"/>
                                                    <button  class="btn btn-primary upload-btn">开始上传</button>
                                                    <button  class="btn btn-default reset-btn">重置</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php }?>
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
 src="assets/ace_v1.4/assets/js/src/elements.aside.js"><?php echo '</script'; ?>
>
    <!--引入CSS-->
    <link rel="stylesheet" type="text/css" href="assets/webuploader-0.1.5/webuploader.css">
    <!--引入JS-->
    <?php echo '<script'; ?>
 type="text/javascript" src="assets/webuploader-0.1.5/webuploader.min.js"><?php echo '</script'; ?>
>
     
    <?php echo '<script'; ?>
 type="text/javascript">
        
        $(function(){
            var uploader=[];
            var thumbnailWidth=100;
            var thumbnailHeight=100;
            $('.bg-uploader').each(function(i){
                var self=$(this);
                uploader[i]=WebUploader.create({
                    swf:'assets/webuploader-0.1.5/Uploader.swf',
                    server:'dobackground.php?action=bg',
                    pick:'#'+self.find('.selectfile').attr('id'),
                    resize: false,
                    fileNumLimit:1,
                    formData:{"plugname":self.find('input[name=plugname]').val()},
                    duplicate:true,
                    accept:{
                        mimeTypes:'image/jpg,image/jpeg,image/png,video/mp4,video/ogg',
                    }
                });
                uploader[i].on( 'fileQueued', function( file ) {
                    var li=$('<div id="' + file.id + '" class="item">' +
                        '<img/>'+
                        '<h4 class="info">' + file.name + '</h4>' +
                        '<p class="state">等待上传...</p>' +'</div>');
                    var img=li.find('img');
                    self.find('.uploader-list').append(li);
                    this.makeThumb(file,function(error,src){
                        if ( error ) {
                            img.replaceWith('<span>不能预览</span>');
                            return;
                        }
                        img.attr( 'src', src );
                    },thumbnailWidth, thumbnailHeight);
                });
                //加入队列前 先清空队列中的内容
                uploader[i].on('beforeFileQueued',function(file){
                    this.reset();
                    self.find('.uploader-list').html('');
                });
                uploader[i].on('uploadProgress',function(){
                    self.find('.state').html('上传中，请耐心等待...');
                })
                uploader[i].on('uploadSuccess',function(file){
                    self.find('.state').html('上传完成，如果是视频文件，手动刷新页面后可查看');
                    alert('上传完成');
                });
                
                self.find('.upload-btn').on('click',function(){
                    uploader[i].upload();
                });
                
                self.find('.reset-btn').on('click',function(){
                    $.ajax({
                        "url":"dobackground.php?action=resetbg",
                        "type":"post",
                        "data":{"plugname":self.find('input[name=plugname]').val()},
                        "dataType":'json',
                        "success":function(json){
                            alert(json.message);
                            self.find('.item').children(':first').remove();
                            $('<img src="/wall/themes/meepo/assets/images/defaultbg.jpg" style="max-width: 200px;max-height:150px;"/>').prependTo(self.find('.item'));
                        }
                    })
                });
            });
            // Uploader.register({});
            // WebUploader.register({
            //     'after-send-file': 'checkuploaded'

            //     // xxxx
            // }, {

            //     addFiles: function( files ) {
            //         console.log('complete');
            //         // 遍历files中的文件, 过滤掉不满足规则的。
            //     }
            // });
        })
        
    <?php echo '</script'; ?>
>
    <?php $_smarty_tpl->_subTemplateRender("file:templates/html_footer.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
</div><?php }
}
