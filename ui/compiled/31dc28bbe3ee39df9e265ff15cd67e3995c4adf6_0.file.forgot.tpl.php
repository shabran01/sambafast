<?php
/* Smarty version 4.5.3, created on 2026-09-19 15:36:52
  from '/var/www/html/subdomains/adwifi/ui/ui/customer/forgot.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.5.3',
  'unifunc' => 'content_6aaeac1460eaf3_65154367',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '31dc28bbe3ee39df9e265ff15cd67e3995c4adf6' => 
    array (
      0 => '/var/www/html/subdomains/adwifi/ui/ui/customer/forgot.tpl',
      1 => 1789600378,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:customer/header-public.tpl' => 1,
    'file:customer/footer-public.tpl' => 1,
  ),
),false)) {
function content_6aaeac1460eaf3_65154367 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_subTemplateRender("file:customer/header-public.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>

<style>

/* Reset body background */
body.app.off-canvas.body-full {
    background: linear-gradient(135deg, #0f0c29 0%, #1a1040 40%, #0d1b3e 100%) !important;
    min-height: 100vh;
}

.container {
    background: transparent !important;
}

.form-head {
    display: none !important;
}

*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

.login-wrapper {
    position: relative;
    z-index: 1;
    width: 100%;
    max-width: 420px;
    margin: 40px auto;
}

/* ─── LOGIN CARD ─── */
.login-card {
    width: 100%;
    background: rgba(20,20,50,0.8);
    backdrop-filter: blur(20px);
    -webkit-backdrop-filter: blur(20px);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 20px;
    padding: 28px 24px;
    box-shadow:
        0 20px 40px rgba(0,0,0,0.4),
        0 0 0 1px rgba(255,255,255,0.1) inset;
    animation: cardIn 0.5s cubic-bezier(0.22, 1, 0.36, 1) both;
}

@keyframes cardIn {
    from { opacity: 0; transform: translateY(20px) scale(0.96); }
    to   { opacity: 1; transform: translateY(0) scale(1); }
}

/* ─── LOGO ─── */
.logo-box {
    width: 50px; height: 50px;
    border-radius: 14px;
    background: linear-gradient(135deg, #6366f1, #06b6d4);
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 14px;
    font-size: 18px;
    color: #fff;
    box-shadow: 0 8px 20px rgba(99,102,241,0.3);
}

.card-title {
    text-align: center;
    font-size: 1.4rem;
    font-weight: 700;
    color: #ffffff;
    margin-bottom: 8px;
    text-shadow: 0 1px 2px rgba(0,0,0,0.3);
}

.card-sub {
    text-align: center;
    font-size: 0.95rem;
    color: rgba(255,255,255,0.85);
    margin-bottom: 24px;
    font-weight: 500;
}

/* ─── FORM FIELDS ─── */
.field-group { margin-bottom: 14px; }

.field-label {
    display: block;
    font-size: 0.8rem;
    font-weight: 600;
    color: rgba(255,255,255,0.9);
    margin-bottom: 6px;
    text-shadow: 0 1px 2px rgba(0,0,0,0.2);
}

.field-wrap {
    position: relative;
    display: flex;
    align-items: center;
}

.field-icon {
    position: absolute;
    left: 12px;
    color: #64748b;
    font-size: 14px;
    pointer-events: none;
}

.field-input {
    width: 100%;
    padding: 12px 12px 12px 40px;
    background: rgba(255,255,255,0.9);
    border: 1px solid rgba(255,255,255,0.4);
    border-radius: 10px;
    font-size: 0.95rem;
    font-family: inherit;
    color: #1e1b4b;
    outline: none;
    transition: all 0.2s ease;
    font-weight: 500;
}

.field-input::placeholder { 
    color: #64748b;
    font-weight: 400;
}

.field-input:focus {
    background: #ffffff;
    border-color: #6366f1;
    box-shadow: 0 0 0 3px rgba(99,102,241,0.2);
}

.field-input:read-only {
    background: rgba(255,255,255,0.7);
    color: #475569;
}

/* ─── ALERT ─── */
.alert-box {
    border-radius: 10px;
    padding: 10px 14px;
    margin-bottom: 16px;
    display: flex;
    align-items: flex-start;
    gap: 8px;
    font-size: 0.8rem;
    font-weight: 500;
    animation: alertIn 0.3s ease;
}
@keyframes alertIn {
    from { opacity: 0; transform: translateY(-6px); }
    to   { opacity: 1; transform: translateY(0); }
}
.alert-error {
    background: rgba(239,68,68,0.15);
    border: 1px solid rgba(239,68,68,0.3);
    color: #fca5a5;
}
.alert-success {
    background: rgba(16,185,129,0.15);
    border: 1px solid rgba(16,185,129,0.3);
    color: #6ee7b7;
}

.help-block {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.8);
    margin-top: 10px;
    margin-bottom: 16px;
    line-height: 1.5;
}

/* ─── BUTTONS ─── */
.btn-group {
    display: flex;
    flex-direction: column;
    gap: 10px;
    margin-top: 18px;
}

.btn-group .btn {
    padding: 12px;
    border: none;
    border-radius: 10px;
    font-size: 0.9rem;
    font-weight: 700;
    cursor: pointer;
    transition: all 0.2s ease;
    text-decoration: none;
    text-align: center;
}

.btn-primary {
    background: linear-gradient(135deg, #6366f1 0%, #4f46e5 50%, #06b6d4 100%);
    background-size: 200% 200%;
    background-position: 0% 50%;
    color: #fff;
    box-shadow: 0 4px 15px rgba(99,102,241,0.3);
}

.btn-primary:hover {
    background-position: 100% 50%;
    transform: translateY(-1px);
    box-shadow: 0 8px 20px rgba(99,102,241,0.4);
}

.btn-link {
    background: transparent;
    color: rgba(255,255,255,0.75);
    font-weight: 500;
}

.btn-link:hover {
    color: #818cf8;
}

/* ─── RESPONSIVE DESIGN ─── */
@media (max-width: 480px) {
    body.customer-login { padding: 12px; }
    
    .login-card {
        padding: 24px 20px;
        border-radius: 16px;
    }
    
    .logo-box {
        width: 45px; height: 45px;
        font-size: 16px;
        margin-bottom: 12px;
    }
    
    .card-title { font-size: 1.1rem; }
    .card-sub { font-size: 0.8rem; margin-bottom: 16px; }
    
    .btn-group .btn { padding: 10px; font-size: 0.85rem; }
}

</style>

<div class="login-wrapper">
    <div class="login-card">
        <!-- Logo -->
        <div class="logo-box">
            <i class="fa fa-key"></i>
        </div>
        
        <h2 class="card-title"><?php echo $_smarty_tpl->tpl_vars['_c']->value['CompanyName'];?>
</h2>
        <p class="card-sub">
            <?php if ($_smarty_tpl->tpl_vars['step']->value == 1) {?>
                <?php echo Lang::T('Verification Code');?>

            <?php } elseif ($_smarty_tpl->tpl_vars['step']->value == 2) {?>
                <?php echo Lang::T('Success');?>

            <?php } elseif ($_smarty_tpl->tpl_vars['step']->value == 6) {?>
                <?php echo Lang::T('Forgot Username');?>

            <?php } else { ?>
                <?php echo Lang::T('Forgot Password');?>

            <?php }?>
        </p>

        <!-- Alert Messages -->
        <?php if ((isset($_smarty_tpl->tpl_vars['msg']->value))) {?>
        <div class="alert-box alert-error">
            <i class="fa fa-circle-exclamation"></i>
            <span><?php echo $_smarty_tpl->tpl_vars['msg']->value;?>
</span>
        </div>
        <?php }?>

        <!-- Forgot Form -->
        <form action="<?php echo $_smarty_tpl->tpl_vars['_url']->value;?>
forgot&step=<?php echo $_smarty_tpl->tpl_vars['step']->value+1;?>
" method="post">
            <?php if ($_smarty_tpl->tpl_vars['step']->value == 1) {?>
                <!-- Step 1: Verification Code -->
                <div class="field-group">
                    <label class="field-label">
                        <?php if ($_smarty_tpl->tpl_vars['_c']->value['country_code_phone'] != '') {
echo Lang::T('Phone Number');
} else {
echo Lang::T('Usernames');
}?>
                    </label>
                    <div class="field-wrap">
                        <i class="fa fa-<?php if ($_smarty_tpl->tpl_vars['_c']->value['country_code_phone'] != '') {?>phone<?php } else { ?>user<?php }?> field-icon"></i>
                        <input type="text" readonly class="field-input" name="username" value="<?php echo $_smarty_tpl->tpl_vars['username']->value;?>
"
                            placeholder="<?php if ($_smarty_tpl->tpl_vars['_c']->value['country_code_phone'] != '') {
echo $_smarty_tpl->tpl_vars['_c']->value['country_code_phone'];?>
 <?php echo Lang::T('Phone Number');
} else {
echo Lang::T('Usernames');
}?>">
                    </div>
                </div>
                <div class="field-group">
                    <label class="field-label"><?php echo Lang::T('Verification Code');?>
</label>
                    <div class="field-wrap">
                        <i class="fa fa-asterisk field-icon"></i>
                        <input type="text" required class="field-input" id="otp_code"
                            placeholder="<?php echo Lang::T('Verification Code');?>
" name="otp_code">
                    </div>
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary"><?php echo Lang::T('Validate');?>
</button>
                    <a href="<?php echo $_smarty_tpl->tpl_vars['_url']->value;?>
forgot&step=-1" class="btn btn-link"><?php echo Lang::T('Cancel');?>
</a>
                </div>
            <?php } elseif ($_smarty_tpl->tpl_vars['step']->value == 2) {?>
                <!-- Step 2: Success -->
                <div class="field-group">
                    <label class="field-label"><?php if ($_smarty_tpl->tpl_vars['_c']->value['country_code_phone'] != '') {
echo Lang::T('Phone Number');
} else {
echo Lang::T('Usernames');
}?></label>
                    <div class="field-wrap">
                        <i class="fa fa-<?php if ($_smarty_tpl->tpl_vars['_c']->value['country_code_phone'] != '') {?>phone<?php } else { ?>user<?php }?> field-icon"></i>
                        <input type="text" readonly class="field-input" name="username" value="<?php echo $_smarty_tpl->tpl_vars['username']->value;?>
">
                    </div>
                </div>
                <div class="field-group">
                    <label class="field-label"><?php echo Lang::T('Your Password has been change to');?>
</label>
                    <div class="field-wrap">
                        <i class="fa fa-lock field-icon"></i>
                        <input type="text" readonly class="field-input" value="<?php echo $_smarty_tpl->tpl_vars['passsword']->value;?>
" onclick="this.select()">
                    </div>
                </div>
                <p class="help-block">
                    <?php echo Lang::T('Use the password to login, and change the password from password change page');?>

                </p>
                <div class="btn-group">
                    <a href="<?php echo $_smarty_tpl->tpl_vars['_url']->value;?>
login" class="btn btn-primary"><?php echo Lang::T('Back to Login');?>
</a>
                </div>
            <?php } elseif ($_smarty_tpl->tpl_vars['step']->value == 6) {?>
                <!-- Step 6: Forgot Username -->
                <div class="field-group">
                    <label class="field-label"><?php echo Lang::T('Please input your Email or Phone number');?>
</label>
                    <div class="field-wrap">
                        <i class="fa fa-search field-icon"></i>
                        <input type="text" name="find" class="field-input" required value="" placeholder="<?php echo Lang::T('Email or Phone number');?>
">
                    </div>
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary"><?php echo Lang::T('Validate');?>
</button>
                    <a href="<?php echo $_smarty_tpl->tpl_vars['_url']->value;?>
forgot" class="btn btn-link"><?php echo Lang::T('Back');?>
</a>
                </div>
            <?php } else { ?>
                <!-- Default: Forgot Password -->
                <div class="field-group">
                    <label class="field-label">
                        <?php if ($_smarty_tpl->tpl_vars['_c']->value['country_code_phone'] != '') {
echo Lang::T('Phone Number');
} else {
echo Lang::T('Usernames');
}?>
                    </label>
                    <div class="field-wrap">
                        <i class="fa fa-<?php if ($_smarty_tpl->tpl_vars['_c']->value['country_code_phone'] != '') {?>phone<?php } else { ?>user<?php }?> field-icon"></i>
                        <input type="text" class="field-input" name="username" required
                            placeholder="<?php if ($_smarty_tpl->tpl_vars['_c']->value['country_code_phone'] != '') {
echo $_smarty_tpl->tpl_vars['_c']->value['country_code_phone'];?>
 <?php echo Lang::T('Phone Number');
} else {
echo Lang::T('Usernames');
}?>">
                    </div>
                </div>
                <div class="btn-group">
                    <button type="submit" class="btn btn-primary"><?php echo Lang::T('Validate');?>
</button>
                    <a href="<?php echo $_smarty_tpl->tpl_vars['_url']->value;?>
forgot&step=6" class="btn btn-link"><?php echo Lang::T('Forgot Usernames');?>
</a>
                    <a href="<?php echo $_smarty_tpl->tpl_vars['_url']->value;?>
login" class="btn btn-link"><?php echo Lang::T('Back');?>
</a>
                </div>
            <?php }?>
        </form>
    </div>
</div>

<?php $_smarty_tpl->_subTemplateRender("file:customer/footer-public.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
}
}
