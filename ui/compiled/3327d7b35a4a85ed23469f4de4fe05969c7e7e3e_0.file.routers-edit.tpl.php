<?php
/* Smarty version 4.5.3, created on 2026-09-19 15:09:58
  from '/var/www/html/subdomains/adwifi/ui/ui/routers-edit.tpl' */

/* @var Smarty_Internal_Template $_smarty_tpl */
if ($_smarty_tpl->_decodeProperties($_smarty_tpl, array (
  'version' => '4.5.3',
  'unifunc' => 'content_6aaea5c6eecff1_97706481',
  'has_nocache_code' => false,
  'file_dependency' => 
  array (
    '3327d7b35a4a85ed23469f4de4fe05969c7e7e3e' => 
    array (
      0 => '/var/www/html/subdomains/adwifi/ui/ui/routers-edit.tpl',
      1 => 1789600378,
      2 => 'file',
    ),
  ),
  'includes' => 
  array (
    'file:sections/header.tpl' => 1,
    'file:sections/footer.tpl' => 1,
  ),
),false)) {
function content_6aaea5c6eecff1_97706481 (Smarty_Internal_Template $_smarty_tpl) {
$_smarty_tpl->_subTemplateRender("file:sections/header.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
?>
<!-- routers-edit -->

<div class="row">
    <div class="col-sm-12 col-md-12">
        <div class="panel panel-primary panel-hovered panel-stacked mb30">
            <div class="panel-heading"><?php echo Lang::T('Edit Router');?>
</div>
            <div class="panel-body">
                <form class="form-horizontal" method="post" role="form" action="<?php echo $_smarty_tpl->tpl_vars['_url']->value;?>
routers/edit-post">
                    <input type="hidden" name="id" value="<?php echo $_smarty_tpl->tpl_vars['d']->value['id'];?>
">
                    <div class="form-group">
                        <label class="col-md-2 control-label"><?php echo Lang::T('Status');?>
</label>
                        <div class="col-md-10">
                            <label class="radio-inline warning">
                                <input type="radio" <?php if ($_smarty_tpl->tpl_vars['d']->value['enabled'] == 1) {?>checked<?php }?> name="enabled" value="1"> <?php echo Lang::T('Enable');?>

                            </label>
                            <label class="radio-inline">
                                <input type="radio" <?php if ($_smarty_tpl->tpl_vars['d']->value['enabled'] == 0) {?>checked<?php }?> name="enabled" value="0">
                                <?php echo Lang::T('Disable');?>

                            </label>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label"><?php echo Lang::T('Router Name / Location');?>
</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="name" name="name" maxlength="32"
                                value="<?php echo $_smarty_tpl->tpl_vars['d']->value['name'];?>
">
                            <p class="help-block"><?php echo Lang::T('Name of Area that router operated');?>
</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label"><?php echo Lang::T('IP Address');?>
</label>
                        <div class="col-md-6">
                            <input type="text" placeholder="192.168.88.1:8728" class="form-control" id="ip_address"
                                name="ip_address" value="<?php echo $_smarty_tpl->tpl_vars['d']->value['ip_address'];?>
">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label"><?php echo Lang::T('Username');?>
</label>
                        <div class="col-md-6">
                            <input type="text" class="form-control" id="username" name="username"
                                value="<?php echo $_smarty_tpl->tpl_vars['d']->value['username'];?>
">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label"><?php echo Lang::T('Router Secret');?>
</label>
                        <div class="col-md-6">
                            <input type="password" class="form-control" id="password" name="password"
                                placeholder="<?php echo Lang::T('Leave empty to keep current password');?>
">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label"><?php echo Lang::T('Description');?>
</label>
                        <div class="col-md-6">
                            <textarea class="form-control" id="description"
                                name="description"><?php echo $_smarty_tpl->tpl_vars['d']->value['description'];?>
</textarea>
                            <p class="help-block"><?php echo Lang::T('Explain Coverage of router');?>
</p>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label"><?php echo Lang::T('Coordinates');?>
</label>
                        <div class="col-md-6">
                            <input name="coordinates" id="coordinates" class="form-control" value="<?php echo $_smarty_tpl->tpl_vars['d']->value['coordinates'];?>
"
                                placeholder="6.465422, 3.406448">
                            <div id="map" style="width: '100%'; height: 200px; min-height: 150px;"></div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="col-md-2 control-label"><?php echo Lang::T('Coverage');?>
</label>
                        <div class="col-md-6">
                            <div class="input-group">
                                <input type="number" class="form-control" id="coverage" name="coverage" value="<?php echo $_smarty_tpl->tpl_vars['d']->value['coverage'];?>
"
                                onkeyup="updateCoverage()">
                                <span class="input-group-addon">meter(s)</span>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <div class="col-lg-offset-2 col-lg-10">
                            <button class="btn btn-primary" onclick="return ask(this, 'Continue the process of changing Routers?')" type="submit"><?php echo Lang::T('Save Changes');?>
</button>
                            Or <a href="<?php echo $_smarty_tpl->tpl_vars['_url']->value;?>
routers/list"><?php echo Lang::T('Cancel');?>
</a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</div>


    <?php echo '<script'; ?>
 src="https://unpkg.com/leaflet@1.9.3/dist/leaflet.js"><?php echo '</script'; ?>
>
    <?php echo '<script'; ?>
>
        var circle;
        function getLocation() {
            if (window.location.protocol == "https:" && navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(showPosition);
            } else {
                setupMap(51.505, -0.09);
            }
        }

        function showPosition(position) {
            setupMap(position.coords.latitude, position.coords.longitude);
        }

        function updateCoverage() {
            if(circle != undefined){
                circle.setRadius($("#coverage").val());
            }
        }


        function setupMap(lat, lon) {
            var map = L.map('map').setView([lat, lon], 13);
            L.tileLayer('https://{s}.basemaps.cartocdn.com/rastertiles/light_all/{z}/{x}/{y}.png', {
                attribution:
                    '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>',
                    subdomains: 'abcd',
                    maxZoom: 20
            }).addTo(map);
            circle = L.circle([lat, lon], 5, {
            color: 'blue',
            fillOpacity: 0.1
            }).addTo(map);
            var marker = L.marker([lat, lon]).addTo(map);
            map.on('click', function(e) {
                var coord = e.latlng;
                var lat = coord.lat;
                var lng = coord.lng;
                newLatLng = new L.LatLng(lat, lng);
                marker.setLatLng(newLatLng);
                circle.setLatLng(newLatLng);
                $('#coordinates').val(lat + ',' + lng);
                updateCoverage();
            });
            updateCoverage();
        }
        window.onload = function() {
        
        <?php if ($_smarty_tpl->tpl_vars['d']->value['coordinates']) {?>
            setupMap(<?php echo $_smarty_tpl->tpl_vars['d']->value['coordinates'];?>
);
        <?php } else { ?>
            getLocation();
        <?php }?>
        
        }
    <?php echo '</script'; ?>
>

<?php $_smarty_tpl->_subTemplateRender("file:sections/footer.tpl", $_smarty_tpl->cache_id, $_smarty_tpl->compile_id, 0, $_smarty_tpl->cache_lifetime, array(), 0, false);
}
}
