<?php
/* Smarty version 3.1.33, created on 2024-06-05 16:55:02
  from '/www/wwwroot/19.71jc.cn/myadmin/templates/danye.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_666027e65f4911_34549193',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'b91fabbcda3e3df644b4b36775068b8ee25c2b6d' => 
    array (
      0 => '/www/wwwroot/19.71jc.cn/myadmin/templates/danye.html',
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
function content_666027e65f4911_34549193 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_subTemplateRender("file:templates/html_header.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
<!-- /section:basics/navbar.layout -->
<div class="main-container ace-save-state" id="main-container">
    <?php echo '<script'; ?>
 type="text/javascript">
        
        try{ace.settings.loadState('main-container')} catch (e){}
        
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
                        <i class="ace-icon fa fa-home home-icon"></i>
                        <a href="index.php">首页</a>
                    </li>
                    <li class="active"><?php echo $_smarty_tpl->tpl_vars['title']->value;?>
</li>
                </ul><!-- /.breadcrumb -->
                <!-- /section:basics/content.searchbox -->
            </div>

            <!-- /section:basics/content.breadcrumbs -->
            <div class="page-content">
                <h3 class="header smaller lighter blue">
                    <?php echo $_smarty_tpl->tpl_vars['title']->value;?>

                    <small>单页列表</small>
                </h3>
                <div class="row">
                    <div class="col-xs-12">
                        <div class="widget-box widget-color-blue" id="widget-box-2">
                            <div class="widget-header">
                                <h5 class="widget-title bigger lighter">
                                    <i class="ace-icon fa fa-users"></i>
                                    单页列表
                                </h5>
                                <div class="widget-toolbar no-border">
                                    <label>
                                        <button class="btn btn-xs btn-warning btn_add">添加</button>
                                    </label>

                                </div>
                            </div>
                            <div class="widget-body">
                                <div class="widget-main no-padding">
                                    <!-- PAGE CONTENT BEGINS -->
                                    <table class="table table-striped table-bordered table-hover">
                                        <thead class="thin-border-bottom">
                                            <tr>
                                                <th>序号</th>
                                                <th>title</th>
                                                <th>图片</th>
                                                <th>
                                                    排序
                                                </th>
                                                <th>
                                                    创建时间
                                                </th>

                                                <th >操作</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['danye']->value, 'item');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['item']->value) {
?>
                                            <tr id="item<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
">
                                                <td ><?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
</td>
                                                <td ><?php echo $_smarty_tpl->tpl_vars['item']->value['title'];?>
</td>
                                                <td ><img src="<?php echo $_smarty_tpl->tpl_vars['item']->value['img'];?>
" style="width: 100px; height: 100px;"></td>
                                                <td ><?php echo $_smarty_tpl->tpl_vars['item']->value['sort'];?>
</td>
                                                <td ><?php echo $_smarty_tpl->tpl_vars['item']->value['createtime'];?>
</td>
                                                <td >
                                                    <a href="###" onclick="del(<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
)"><span class="label label-danger">删除</span></a>
                                                </td>
                                            </tr>
                                            <?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                                        </tbody>
                                    </table>
                                    <?php echo $_smarty_tpl->tpl_vars['pagehtml']->value;?>

                                </div>
                            </div>
                        </div>

                        <div id="danyemodal" class="modal" tabindex="-1">
                            <div class="modal-dialog">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <button type="button" class="close" data-dismiss="modal">&times;</button>
                                        <h4 class="blue bigger">单页设置</h4>
                                    </div>
                                    <form id="savedanye" action="dodanye.php?action=savedanye" method="post" enctype="multipart/form-data" >
                                        <div class="modal-body">
                                            <div class="row">
                                                <div class="col-sm-12">
                                                    <div class="row">
                                                        <div class="form-group">
                                                            <label for="form-field-select-3" class="col-xs-2">title：</label>
                                                            <div>
                                                                <input type="text" id="form-field-1" name="danyetitle" placeholder="title" class="col-xs-8" value="" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="space-4"></div>
                                                    <div class="row">
                                                        <div class="form-group">
                                                            <label for="form-field-select-3" class="col-xs-2">排序 ：</label>
                                                            <div>
                                                                <input type="number" id="form-field-1" name="sort" placeholder="排序" class="col-xs-8" value="" />
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="space-4"></div>
                                                    <div class="row">

                                                        <input type="file" class="imageuploader" name="photo"/>

                                                    </div>
                                                    <div class="space-4"></div>

                                                </div>
                                            </div>
                                        </div>
                                    </form>

                                    <div class="modal-footer">
                                        <input name="id" type="hidden" value="0"/>
                                        <button class="btn btn-sm" data-dismiss="modal" >
                                            <i class="ace-icon fa fa-times"></i>
                                            取消
                                        </button>
                                        <button class="btn btn-sm btn-primary" id="btn-save-item">
                                            <i class="ace-icon fa fa-check"></i>
                                            保存
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <!-- PAGE CONTENT ENDS -->
                    </div><!-- /.col -->
                </div><!-- /.row -->
            </div><!-- /.page-content -->
        </div>
    </div><!-- /.main-content -->
    <?php $_smarty_tpl->_subTemplateRender("file:templates/html_footercontent.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
    <?php echo '<script'; ?>
 type="text/javascript" src="assets/js/jquery.form.js"><?php echo '</script'; ?>
>
    <!-- 写每个页面自定的js -->
    <?php echo '<script'; ?>
 type="text/javascript">
        
        $('.btn_add').bind('click', function(){
            $('#danyemodal').modal('show');
        });
        $('.imageuploader').ace_file_input({
        style: 'well',
                btn_choose: '点击此处选择图片',
                btn_change: null,
                no_icon: 'ace-icon fa fa-cloud-upload',
                droppable: true,
                maxSize: 1550000,
                //previewHeight:200,
                allowExt: ["jpeg", "jpg", "png", "gif"],
                allowMime: ["image/jpg", "image/jpeg", "image/png", "image/gif"],
                thumbnail: 'large'//large | fit
                //,icon_remove:null//set null, to hide remove/reset button
                /**,before_change:function(files, dropped) {
                 //Check an example below
                 //or examples/file-upload.html
                 return true;
                 }*/
                /**,before_remove : function() {
                 return true;
                 }*/
                ,
                //previewSize:400,
                previewHeight:200,
                preview_error : function(filename, error_code) {
                //name of the file that failed
                //error_code values
                //1 = 'FILE_LOAD_FAILED',
                //2 = 'IMAGE_LOAD_FAILED',
                //3 = 'THUMBNAIL_FAILED'
                //alert(error_code);
                }

        }).on('change', function(){
        //console.log($(this));
        //console.log($(this).data('ace_input_files'));
        //console.log($(this).data('ace_input_method'));
        });
     
        $('#btn-save-item').bind('click',function(){
            $('#savedanye').ajaxSubmit({
			dataType: 'json',
			success:function(json){
                            console.log(json);
//				$('input[name=pjlogopath]').val(json.filepath);
				alert(json.message);
				if(json.code>0){
					window.location.reload();
				}
			}
		});
		return false;	
	});
        
        function del(id){
            if(!confirm('确认要删除吗？'))return false;
                jQuery.ajax({
                    "url":"dodanye.php?action=deldanye",
                    "data":{'id':id},
                    "type":"post",
                    "dataType":"json",
                    "success":function(json){
                            if(json.code>0){
                                    jQuery('#item'+id).remove();
                            }
                    }
                });
        }
        
        
    <?php echo '</script'; ?>
>
    <?php $_smarty_tpl->_subTemplateRender("file:templates/html_footer.html", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
}
}
