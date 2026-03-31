<?php
/* Smarty version 3.1.33, created on 2024-06-07 11:00:36
  from '/www/wwwroot/19.71jc.cn/Modules/Lottery/templates/admin/snippets/winners.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_666277d492aea2_83004503',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'adf0eec608462298b0c4d1427b8d5e5bb1d97897' => 
    array (
      0 => '/www/wwwroot/19.71jc.cn/Modules/Lottery/templates/admin/snippets/winners.html',
      1 => 1712506480,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_666277d492aea2_83004503 (Smarty_Internal_Template $_smarty_tpl) {
?><div class="row">
    <div class="col-sm-12">
        <div class="widget-box widget-color-blue" id="widget-box-winners">
            <div class="widget-header">
                <h5 class="widget-title bigger lighter">
                    <i class="ace-icon fa fa-gift"></i>
                    中奖名单-按抽取出来的时间顺序排列
                </h5>
                <div class="widget-toolbar no-border">
                    <label>
                        <button class="btn btn-xs btn-warning btn-clear" >清空名单</button>
                    </label>
                    <label>
                        <button class="btn btn-xs btn-warning btn-exportexcel" >导出中奖记录</button>
                    </label>
                </div>
            </div>
            <div class="widget-body">
                <div class="widget-main no-padding">
                    <table class="table table-striped table-bordered table-hover">
                        <thead class="thin-border-bottom">
                            <tr>
                                <th>
                                    奖品
                                </th>
                                <th>
                                    中奖人
                                </th>
                                <th>
                                    状态
                                </th>
                                <th >操作</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['winners']->value, 'item');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['item']->value) {
?>
                            <tr id="item_<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
">
                                <tr >
                                    <td class="prize-img">
                                        <?php echo $_smarty_tpl->tpl_vars['item']->value['prize']['prizename'];
if ($_smarty_tpl->tpl_vars['item']->value['prize']['isdel'] == 2) {?><font style="color:red;">(已删)</font><?php }?><br/>
                                        <?php echo $_smarty_tpl->tpl_vars['item']->value['prize']['formatedtext']['html'];?>

                                    </td>
                                    <td >
                                        <div class="row">
                                        <div class="col-xs-3">
                                            <img src="<?php echo $_smarty_tpl->tpl_vars['item']->value['avatar'];?>
" style="width:60px;height:auto;" />
                                        </div>
                                        <div class="col-xs-7">
                                            昵称：<?php echo $_smarty_tpl->tpl_vars['item']->value['nickname'];?>
<br/>
                                            <?php if ($_smarty_tpl->tpl_vars['item']->value['signname'] != '') {?>姓名：<?php echo $_smarty_tpl->tpl_vars['item']->value['signname'];?>
<br/><?php }?>
                                            <?php if ($_smarty_tpl->tpl_vars['item']->value['phone'] != '') {?>手机号：<?php echo $_smarty_tpl->tpl_vars['item']->value['phone'];?>
<br/><?php }?>
                                        </div>
                                    </div>
                                    </td>
                                    <td >
                                        <?php echo $_smarty_tpl->tpl_vars['item']->value['statustext'];?>

                                    </td>
                                    <td >
                                        <?php if ($_smarty_tpl->tpl_vars['item']->value['status'] == 2) {?>
                                        <button class="btn btn-primary btn-xs btn-give" data-id="<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
">发奖</button>
                                        <?php }?>
                                        <?php if ($_smarty_tpl->tpl_vars['item']->value['status'] == 3) {?>
                                        <button class="btn btn-warning btn-xs btn-cancel" data-id="<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
">改成未发奖</button>
                                        <?php }?>
                                        <button class="btn btn-danger btn-xs btn-delete" data-id="<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
">删除</button>
                                    </td>
                                </tr>
                            </tr>
                        <?php
}
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div id="editprizemodal" class="modal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <form action="module.php?m=prize&c=admin&a=ajax_act_save_prize" method="POST" id="editform" enctype="multipart/form-data">
                        <div class="modal-header">
                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                            <h4 class="blue bigger">奖品信息</h4>
                        </div>
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-sm-12 columnitems"></div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <input name="id" type="hidden" value="0"/>
                            <input name="activityid" type="hidden" value=""/>
                            <input name="plugname" type="hidden" value="lottery"/>
                            <input name="rate" type="hidden" value="100"/>
                            <button class="btn btn-sm" data-dismiss="modal" >
                                <i class="ace-icon fa fa-times"></i>
                                取消
                            </button>
                            <button class="btn btn-sm btn-primary btn-save">
                                <i class="ace-icon fa fa-check"></i>
                                保存
                            </button>
                        </div>	
                    </form>	
                </div>
            </div>
        </div>
    </div>
</div><?php }
}
