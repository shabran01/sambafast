<?php
/* Smarty version 4.5.3, created on 2026-09-19 15:52:16
  from '/var/www/html/subdomains/adwifi/ui/ui/autoload.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.5.3',
  'unifunc' => 'content_6aaeafb0c67171_33071038',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '6dde9444cb408f3100bc796ef05c8108a1db7bf1' => 
    array (
      0 => '/var/www/html/subdomains/adwifi/ui/ui/autoload.tpl',
      1 => 1789600378,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6aaeafb0c67171_33071038 (Smarty_Internal_Template $_smarty_tpl) {
?><option value="">Select Plans</option>
<?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['d']->value, 'ds');
$_smarty_tpl->tpl_vars['ds']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['ds']->value) {
$_smarty_tpl->tpl_vars['ds']->do_else = false;
?>
<option value="<?php echo $_smarty_tpl->tpl_vars['ds']->value['id'];?>
">
    <?php if ($_smarty_tpl->tpl_vars['ds']->value['enabled'] != 1) {?>DISABLED PLAN &bull; <?php }?>
    <?php echo $_smarty_tpl->tpl_vars['ds']->value['name_plan'];?>
 &bull;
    <?php echo Lang::moneyFormat($_smarty_tpl->tpl_vars['ds']->value['price']);?>

    <?php if ($_smarty_tpl->tpl_vars['ds']->value['prepaid'] != 'yes') {?> &bull; POSTPAID  <?php }?>
</option>
<?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);
}
}
