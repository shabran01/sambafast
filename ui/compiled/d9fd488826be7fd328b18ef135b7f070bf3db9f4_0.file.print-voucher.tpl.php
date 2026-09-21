<?php
/* Smarty version 4.5.3, created on 2026-09-19 15:52:43
  from '/var/www/html/subdomains/adwifi/ui/ui/print-voucher.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.5.3',
  'unifunc' => 'content_6aaeafcbca7809_21921018',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    'd9fd488826be7fd328b18ef135b7f070bf3db9f4' => 
    array (
      0 => '/var/www/html/subdomains/adwifi/ui/ui/print-voucher.tpl',
      1 => 1789600378,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
  ),
),false)) {
function content_6aaeafcbca7809_21921018 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_checkPlugins(array(0=>array('file'=>'/var/www/html/subdomains/adwifi/system/vendor/smarty/smarty/libs/plugins/modifier.count.php','function'=>'smarty_modifier_count',),));
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo $_smarty_tpl->tpl_vars['_title']->value;?>
</title>
  <link rel="shortcut icon" type="image/x-icon" href="ui/ui/images/favicon.ico">
  <link rel="stylesheet" href="ui/ui/styles/bootstrap.min.css">
  <link rel="stylesheet" href="ui/ui/fonts/font-awesome/css/font-awesome.min.css">
  <style>
    html, body {
      -webkit-print-color-adjust: exact;
      print-color-adjust: exact;
      color-adjust: exact;
    }
    * { box-sizing: border-box; }
    body { background: #f4f6f9; font-family: Arial, Helvetica, sans-serif; }

    .controls-panel { max-width: 900px; margin: 0 auto; padding: 16px; }
    .controls-panel h1 { font-size: 22px; font-weight: 700; color: #2b3d51; margin-bottom: 16px; }

    /* ===== A4 page container ===== */
    .page-a4 {
      background: white;
      width: 21cm;
      min-height: 29.7cm;
      margin: 0 auto 20px;
      padding: 0.8cm 0.6cm;
      box-shadow: 0 2px 12px rgba(0,0,0,.15);
      border-radius: 4px;
    }

    /* ===== Voucher grid ===== */
    .voucher-grid {
      display: flex;
      flex-wrap: wrap;
      gap: 8px;
      padding: 6px;
    }

    /* ===== Voucher card ===== */
    .voucher-card {
      flex: 0 0 calc(100% / <?php echo $_smarty_tpl->tpl_vars['vpl']->value;?>
 - 9px);
      border-radius: 8px;
      overflow: hidden;
      background: #fff;
      box-shadow: 0 1px 4px rgba(0,0,0,0.08);
      page-break-inside: avoid;
      break-inside: avoid;
    }
    .voucher-accent { display: none; } /* accent now handled inside Voucher.html */
    .voucher-num {
      position: absolute;
      display: none; /* hidden, numbering now in card header */
    }
    .voucher-card-inner {
      padding: 0;
      font-size: 10px;
      line-height: 1.4;
    }
    .voucher-card-inner table { width: 100%; border-collapse: collapse; }
    .voucher-card-inner img { max-width: 90%; height: auto; display: block; margin: 0 auto; }

    /* ===== Page break indicator ===== */
    .page-break-indicator {
      display: flex; align-items: center; gap: 10px;
      margin: 12px 0; color: #7c3aed; font-size: 11px; font-weight: 600;
      text-transform: uppercase; letter-spacing: 0.05em;
    }
    .page-break-indicator::after {
      content: ''; flex: 1; height: 2px;
      background: linear-gradient(90deg, #7c3aed 0%, transparent 100%);
    }

    /* ================================================================
       PRINT STYLES
       ================================================================ */
    @media print {
      html, body {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
        background: white !important;
        margin: 0; padding: 0;
      }
      .no-print { display: none !important; }
      .page-a4 {
        margin: 0; padding: 0.4cm; box-shadow: none; border-radius: 0;
        width: 100%; min-height: auto;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
      }
      .voucher-card, .voucher-card * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
        color-adjust: exact !important;
      }
    }

    @media screen and (max-width: 900px) { .page-a4 { width: 100%; padding: 8px; } }
  </style>
</head>
<body>

  <!-- ============================================================
       CONTROLS PANEL (hidden when printing)
       ============================================================ -->
  <div class="controls-panel no-print">
    <h1><i class="fa fa-ticket"></i> <?php echo Lang::T('Print Vouchers');?>
</h1>

    <form method="post" action="<?php echo $_smarty_tpl->tpl_vars['_url']->value;?>
plan/print-voucher/" id="controlsForm" class="panel panel-default">
      <div class="panel-body">
        <div class="row">
          <div class="col-sm-2">
            <div class="form-group">
              <label class="control-label"><?php echo Lang::T('From ID');?>
</label>
              <input type="text" name="from_id" value="<?php echo $_smarty_tpl->tpl_vars['from_id']->value;?>
" placeholder="0" class="form-control">
            </div>
          </div>
          <div class="col-sm-2">
            <div class="form-group">
              <label class="control-label"><?php echo Lang::T('Limit');?>
</label>
              <input type="text" name="limit" value="<?php echo $_smarty_tpl->tpl_vars['limit']->value;?>
" placeholder="40" class="form-control">
            </div>
          </div>
          <div class="col-sm-2">
            <div class="form-group">
              <label class="control-label"><?php echo Lang::T('Per Line');?>
</label>
              <input type="text" name="vpl" value="<?php echo $_smarty_tpl->tpl_vars['vpl']->value;?>
" placeholder="5" class="form-control">
            </div>
          </div>
          <div class="col-sm-2">
            <div class="form-group">
              <label class="control-label"><?php echo Lang::T('Break After');?>
</label>
              <input type="text" name="pagebreak" value="<?php echo $_smarty_tpl->tpl_vars['pagebreak']->value;?>
" placeholder="20" class="form-control">
            </div>
          </div>
          <div class="col-sm-2">
            <div class="form-group">
              <label class="control-label"><?php echo Lang::T('Plan');?>
</label>
              <select name="planid" class="form-control">
                <option value="0"><?php echo Lang::T('All Plans');?>
</option>
                <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['plans']->value, 'plan');
$_smarty_tpl->tpl_vars['plan']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['plan']->value) {
$_smarty_tpl->tpl_vars['plan']->do_else = false;
?>
                  <option value="<?php echo $_smarty_tpl->tpl_vars['plan']->value['id'];?>
" <?php if ($_smarty_tpl->tpl_vars['plan']->value['id'] == $_smarty_tpl->tpl_vars['planid']->value) {?>selected<?php }?>><?php echo $_smarty_tpl->tpl_vars['plan']->value['name_plan'];?>
</option>
                <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
              </select>
            </div>
          </div>
          <div class="col-sm-2">
            <div class="form-group">
              <label class="control-label">&nbsp;</label>
              <button type="submit" id="generateBtn" class="btn btn-primary btn-block">
                <i class="fa fa-refresh"></i> <?php echo Lang::T('Generate');?>

              </button>
            </div>
          </div>
        </div>
      </div>
    </form>

    <div class="panel panel-default" style="margin-bottom:10px;">
      <div class="panel-body" style="padding:8px 15px; display:flex; align-items:center; gap:16px; flex-wrap:wrap;">
        <button type="button" id="printBtn" class="btn btn-success">
          <i class="fa fa-print"></i> <?php echo Lang::T('Print Vouchers');?>

        </button>
        <span><?php echo Lang::T('Showing');?>
 <strong><?php echo smarty_modifier_count($_smarty_tpl->tpl_vars['voucher']->value);?>
</strong> <?php echo Lang::T('of');?>
 <strong><?php echo $_smarty_tpl->tpl_vars['vc']->value;?>
</strong> <?php echo Lang::T('vouchers');?>
</span>
        <?php if ($_smarty_tpl->tpl_vars['v']->value[0]['id']) {?>
          <span><?php echo Lang::T('From ID');?>
: <strong><?php echo $_smarty_tpl->tpl_vars['v']->value[0]['id'];?>
</strong></span>
        <?php }?>
      </div>
    </div>
  </div>

  <!-- ============================================================
       VOUCHER PAGE
       ============================================================ -->
  <div class="page-a4" id="voucherPage">
    <div class="voucher-grid" id="voucherGrid">
      <?php $_smarty_tpl->_assignInScope('n', 1);?>
      <?php
$_from = $_smarty_tpl->smarty->ext->_foreach->init($_smarty_tpl, $_smarty_tpl->tpl_vars['voucher']->value, 'vs');
$_smarty_tpl->tpl_vars['vs']->do_else = true;
if ($_from !== null) foreach ($_from as $_smarty_tpl->tpl_vars['vs']->value) {
$_smarty_tpl->tpl_vars['vs']->do_else = false;
?>
        <?php $_smarty_tpl->_assignInScope('jml', $_smarty_tpl->tpl_vars['jml']->value+1);?>

        <!-- Single voucher card -->
        <div class="voucher-card">
          <div class="voucher-accent"></div>
          <div class="voucher-card-inner">
            <?php echo $_smarty_tpl->tpl_vars['vs']->value;?>

          </div>
        </div>

        <?php if ($_smarty_tpl->tpl_vars['n']->value == $_smarty_tpl->tpl_vars['vpl']->value) {?>
          <?php $_smarty_tpl->_assignInScope('n', 1);?>
        <?php } else { ?>
          <?php $_smarty_tpl->_assignInScope('n', $_smarty_tpl->tpl_vars['n']->value+1);?>
        <?php }?>

                <?php if ($_smarty_tpl->tpl_vars['jml']->value == $_smarty_tpl->tpl_vars['pagebreak']->value) {?>
          <?php $_smarty_tpl->_assignInScope('jml', 0);?>
          </div><!-- close voucher-grid -->
          <div class="page-break-indicator no-print"><span><?php echo Lang::T('Page Break');?>
</span></div>
          <div class="voucher-grid">
        <?php }?>
      <?php
}
$_smarty_tpl->smarty->ext->_foreach->restore($_smarty_tpl, 1);?>
    </div>
  </div>

  <!-- ============================================================
       SCRIPTS
       ============================================================ -->
  <?php echo '<script'; ?>
 src="ui/ui/scripts/jquery.min.js"><?php echo '</script'; ?>
>
  <?php if ((isset($_smarty_tpl->tpl_vars['xfooter']->value))) {
echo $_smarty_tpl->tpl_vars['xfooter']->value;
}?>
  <?php echo '<script'; ?>
>
  document.getElementById('printBtn').addEventListener('click', function() {
    window.print();
  });
  <?php echo '</script'; ?>
>
</body>
</html><?php }
}
