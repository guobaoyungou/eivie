<?php
/* Smarty version 3.1.33, created on 2024-06-07 11:00:35
  from '/www/wwwroot/19.71jc.cn/Modules/Lottery/templates/admin/snippets/prizes.html' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '3.1.33',
  'unifunc' => 'content_666277d3aec440_74920370',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '5a6bb7f607a2cc7bf497c7ad839fa2dffe852485' => 
    array (
      0 => '/www/wwwroot/19.71jc.cn/Modules/Lottery/templates/admin/snippets/prizes.html',
      1 => 1712506480,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_666277d3aec440_74920370 (Smarty_Internal_Template $_smarty_tpl) {
?><div class="row">
    <div class="col-sm-12">
        <div class="widget-box widget-color-blue" id="widget-box-prizes">
            <div class="widget-header">
                <h5 class="widget-title bigger lighter">
                    <i class="ace-icon fa fa-gift"></i>
                    奖品列表
                </h5>
                <div class="widget-toolbar no-border">
                    <label>
                        <button class="btn btn-xs btn-warning btn-openaddprizemodal" data-id="0">添加</button>
                    </label>
                </div>
            </div>
            <div class="widget-body">
                <div class="widget-main no-padding">
                    <table class="table table-striped table-bordered table-hover">
                        <thead class="thin-border-bottom">
                            <tr>
                                <th>
                                    奖品图
                                </th>
                                <th>
                                    奖品名称和数据量
                                </th>
                                <th >操作</th>
                            </tr>
                        </thead>

                        <tbody>
                        <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['prizes']->value, 'item');
if ($_from !== null) {
foreach ($_from as $_smarty_tpl->tpl_vars['item']->value) {
?>
                            <tr id="item_<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
">
                                <td class="prize-img"><?php echo $_smarty_tpl->tpl_vars['item']->value['formatedtext']['html'];?>
</td>
                                <td >
                                    名称：<?php echo $_smarty_tpl->tpl_vars['item']->value['prizename'];?>
<br/>
                                    总数：<?php echo $_smarty_tpl->tpl_vars['item']->value['num'];?>
<br/>
                                    冻结：<?php echo $_smarty_tpl->tpl_vars['item']->value['freezenum'];?>
<br/>
                                    剩余：<?php echo $_smarty_tpl->tpl_vars['item']->value['leftnum'];?>

                                </td>
                                <td >
                                    <button class="btn btn-primary btn-xs btn-openaddprizemodal"  data-id="<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
">修改</button>
                                    <button class="btn btn-danger btn-xs btn-delete" data-id="<?php echo $_smarty_tpl->tpl_vars['item']->value['id'];?>
">删除</button>
                                </td>
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
