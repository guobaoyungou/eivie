<?php
/* Smarty version 3.1.33, created on 2024-06-07 11:00:37
  from '/www/wwwroot/19.71jc.cn/Modules/Lottery/templates/admin/snippets/designated.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_666277d570e062_91246009',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'daaeb0612220bdde02c21b5567e9bc5fc6bb4592' => 
    array (
      0 => '/www/wwwroot/19.71jc.cn/Modules/Lottery/templates/admin/snippets/designated.html',
      1 => 1712506480,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_666277d570e062_91246009 (Smarty_Internal_Template $_smarty_tpl) {
?><div class="row">
    <div class="col-sm-12">
        <div class="widget-box widget-color-blue" id="widget-box-designated">
            <div class="widget-header">
                <h5 class="widget-title bigger lighter">
                    <i class="ace-icon fa fa-users"></i>
                    内定名单
                </h5>
                <div class="widget-toolbar no-border">
                    <label>
                    <button class="btn btn-xs btn-warning btn-openmodaladddesignated">添加内定</button>
                    </label>
                </div>
            </div>
            <div class="widget-body">
                <div class="widget-main no-padding">
                    <style>
                    .prizestable tbody img{
                        width:60px;
                        height:60px;
                    }
                    .prizestable .userinfo div{
                        float:left;
                    }
                    </style>
                    <table class="table table-striped table-bordered table-hover prizestable">
                        <thead class="thin-border-bottom">
                            <tr >
                                <th>
                                    奖品
                                </th>
                                <th>
                                    获奖人
                                </th>
                                <th>
                                    内定状态
                                </th>
                                <th>
                                    状态
                                </th>
                                <th >操作</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['designated']->value, 'item');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['item']->value) {
?>
                            <tr id="designated_<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
">
                                <td >
                                    <?php echo $_smarty_tpl->tpl_vars['item']->value['prize']['formatedtext']['html'];?>

                                    <?php echo $_smarty_tpl->tpl_vars['item']->value['prize']['prizename'];?>

                                </td>
                                <td class="userinfo">
                                    <div style="width:60px;">
                                        <img src="<?php echo $_smarty_tpl->tpl_vars['item']->value['userinfo']['avatar'];?>
"/>
                                    </div>
                                    <div >
                                        昵称：<?php echo $_smarty_tpl->tpl_vars['item']->value['userinfo']['nickname'];?>
<br/>
                                        姓名：<?php echo $_smarty_tpl->tpl_vars['item']->value['userinfo']['signname'];?>
<br/>
                                        电话：<?php echo $_smarty_tpl->tpl_vars['item']->value['userinfo']['phone'];?>
<br/>
                                    </div>
                                </td>
                                <td ><?php echo $_smarty_tpl->tpl_vars['item']->value['designatedtext'];?>
</td>
                                <td ><?php echo $_smarty_tpl->tpl_vars['item']->value['statustext'];?>
</td>
                                <td >
                                    <button class="btn btn-primary btn-xs btn-cancel" data-id="<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
">取消内定</button>
                                    
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
                <div id="designatedmmodal" class="modal" tabindex="-1">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <button type="button" class="close" data-dismiss="modal">&times;</button>
                                <h4 class="blue bigger">内定设置</h4>
                            </div>
                            <div class="modal-body">
                                <div class="row">
                                    <div class="col-sm-12 columnitems">
                                        <div class="row">
                                            <div class="input-group">
                                                <span class="input-group-addon">
                                                    <i class="ace-icon fa fa-search"></i>
                                                    搜人：
                                                </span>
                                                <input type="text" class="form-control search-query" placeholder="输入昵称，姓名或者手机号" />
                                                <span class="input-group-btn">
                                                    <button type="button" class="btn btn-purple btn-sm btn-search">
                                                        开始搜
                                                    </button>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="space-4"></div>
                                        <div class="row">
                                            <div class="form-group">
                                                <label >搜索结果</label>
                                                <select class="form-control" name="userid">
                                                    <option value="0">无数据</option>
                                                </select>
                                                <div class="help-block" id="userid-help-block" ><i class="ace-icon fa fa-exclamation-triangle red"></i>只能搜索到最接近的20个人信息哦，所以输入的信息尽量输入的准确一些</div>
                                            </div>
                                        </div>
                                        <div class="space-4"></div>
                                        <div class="row">
                                            <div class="form-group">
                                                <label >奖品</label>
                                                <select class="form-control" name="prizeid">
                                                    <option value="0">请选择</option>
                                                    <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['prizes']->value, 'item');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['item']->value) {
?>
                                                    <?php if ($_smarty_tpl->tpl_vars['item']->value['leftnum'] > 0) {?>
                                                    <option value="<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
"><?php echo $_smarty_tpl->tpl_vars['item']->value['prizename'];?>
</option>
                                                    <?php }?>
                                                    <?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                                                </select>
                                            </div>
                                        </div>
                                        <div class="space-4"></div>
                                        <div class="row">
                                            <div class="form-group">
                                                <label >内定状态</label>
                                                <select class="form-control" name="designated">
                                                    <option value="2">必中</option>
                                                    <option value="3">不会中</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button class="btn btn-sm" data-dismiss="modal" >
                                    <i class="ace-icon fa fa-times"></i>
                                    取消
                                </button>
                                <button class="btn btn-sm btn-primary btn-save">
                                    <i class="ace-icon fa fa-check "></i>
                                    保存
                                </button>
                            </div>	
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php }
}
