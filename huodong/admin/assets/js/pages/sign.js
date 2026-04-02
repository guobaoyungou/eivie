/**
 * 签到管理页面模块
 * 包含：签到设置、签到名单、手机签到页、3D签到
 */
;(function(global) {
    'use strict';

    var SignPage = {
        // ========== 签到设置 ==========
        renderConfig: function() {
            SignPage._mapInstance = null;
            SignPage._mapMarker = null;
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title">签到设置</div>' +
                '<form id="sign-config-form" class="layui-form" lay-filter="signConfig">' +
                '<div class="sign-config-columns">' +
                // === 左侧：基本配置 ===
                '<div class="sign-config-left">' +
                '<div class="form-section"><div class="section-title">基本配置</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">功能开关</label><div class="layui-input-block"><input type="checkbox" name="enabled" lay-skin="switch" lay-text="开启|关闭" id="sign-enabled"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">签到时间段</label><div class="layui-input-inline" style="width:200px;"><input type="text" name="start_time" id="sign-start-time" class="layui-input" placeholder="开始时间"></div><div class="layui-form-mid">至</div><div class="layui-input-inline" style="width:200px;"><input type="text" name="end_time" id="sign-end-time" class="layui-input" placeholder="结束时间"></div></div>' +
                '</div>' +
                '<div class="form-section"><div class="section-title">必填信息</div>' +
                '<div class="layui-form-item"><div class="layui-input-block" style="margin-left:0;">' +
                '<input type="checkbox" name="require_name" title="姓名" lay-skin="primary">' +
                '<input type="checkbox" name="require_phone" title="电话" lay-skin="primary" lay-filter="requirePhone">' +
                '<input type="checkbox" name="require_company" title="公司" lay-skin="primary">' +
                '<input type="checkbox" name="require_position" title="职位" lay-skin="primary">' +
                '</div></div>' +
                '<div class="layui-form-item" id="phone-verify-row" style="display:none;"><label class="layui-form-label">短信验证码</label><div class="layui-input-block"><input type="checkbox" name="require_phone_verify" id="sign-require-phone-verify" lay-skin="switch" lay-text="开启|关闭"></div></div>' +
                '<div class="location-tip" id="phone-verify-tip" style="display:none;"><i class="fas fa-info-circle"></i> 开启后签到时需要输入短信验证码，确保手机号真实有效</div>' +
                '</div>' +
                '<div class="form-section"><div class="section-title">显示设置</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">使用微信头像</label><div class="layui-input-block"><input type="checkbox" name="use_wx_avatar" lay-skin="switch" lay-text="开启|关闭" checked></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">嘉宾显示方式</label><div class="layui-input-block">' +
                '<input type="radio" name="show_style" value="1" title="昵称" checked>' +
                '<input type="radio" name="show_style" value="2" title="姓名">' +
                '<input type="radio" name="show_style" value="3" title="手机号">' +
                '</div></div></div>' +

                // === 员工号设置 ===
                '<div class="form-section"><div class="section-title"><i class="fas fa-id-badge" style="color:#1976d2;margin-right:6px;"></i>员工号设置</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">员工号</label><div class="layui-input-block"><input type="checkbox" name="show_employee_no" id="sign-show-employee-no" lay-skin="switch" lay-text="开启|关闭" lay-filter="showEmployeeNo"></div></div>' +
                '<div class="layui-form-item" id="employee-no-require-row" style="display:none;"><label class="layui-form-label">员工号必填</label><div class="layui-input-block"><input type="checkbox" name="require_employee_no" id="sign-require-employee-no" lay-skin="switch" lay-text="必填|非必填"></div></div>' +
                '<div class="location-tip"><i class="fas fa-info-circle"></i> 开启后签到时需要填写员工编号</div>' +
                '</div>' +

                // === 上传照片设置 ===
                '<div class="form-section"><div class="section-title"><i class="fas fa-camera" style="color:#43a047;margin-right:6px;"></i>上传照片设置</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">上传照片</label><div class="layui-input-block"><input type="checkbox" name="show_photo" id="sign-show-photo" lay-skin="switch" lay-text="开启|关闭" lay-filter="showPhoto"></div></div>' +
                '<div class="layui-form-item" id="photo-require-row" style="display:none;"><label class="layui-form-label">照片必填</label><div class="layui-input-block"><input type="checkbox" name="require_photo" id="sign-require-photo" lay-skin="switch" lay-text="必填|非必填"></div></div>' +
                '<div class="location-tip"><i class="fas fa-info-circle"></i> 开启后签到时需要上传一张照片（信息采集用途）</div>' +
                '</div>' +

                // === 自定义字段设置 ===
                '<div class="form-section"><div class="section-title"><i class="fas fa-list-alt" style="color:#ff9800;margin-right:6px;"></i>自定义字段设置</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">自定义字段</label><div class="layui-input-block"><input type="checkbox" name="show_custom_fields" id="sign-show-custom-fields" lay-skin="switch" lay-text="开启|关闭" lay-filter="showCustomFields"></div></div>' +
                '<div class="location-tip"><i class="fas fa-info-circle"></i> 开启后可添加自定义信息采集字段</div>' +
                '<div id="custom-fields-panel" style="display:none;margin-top:10px;">' +
                '<table class="layui-table" id="custom-fields-table" style="margin:0;"><thead><tr>' +
                '<th style="width:150px;">字段名称</th><th style="width:100px;">字段类型</th><th style="width:180px;">选项值</th><th style="width:80px;">必填</th><th style="width:70px;">排序</th><th style="width:60px;">操作</th>' +
                '</tr></thead><tbody id="custom-fields-body"></tbody></table>' +
                '<button type="button" class="btn btn-default btn-sm" style="margin-top:8px;" onclick="SignPage._addCustomField()"><i class="fas fa-plus"></i> 添加字段</button>' +
                '</div>' +
                '</div>' +
                '</div>' +
                // === 右侧：地点限定 ===
                '<div class="sign-config-right">' +
                '<div class="form-section"><div class="section-title"><i class="fas fa-map-marker-alt" style="color:#ff5722;margin-right:6px;"></i>活动地点限定</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">地点限定</label><div class="layui-input-block"><input type="checkbox" name="sign_location_enabled" id="sign-location-enabled" lay-skin="switch" lay-text="开启|关闭" lay-filter="signLocationSwitch"></div></div>' +
                '<div class="location-tip"><i class="fas fa-info-circle"></i> 开启后，签到用户必须在指定活动地点的指定范围内才能完成签到</div>' +
                '<div id="location-config-panel" class="location-config-panel" style="display:none;">' +
                '<div class="layui-form-item"><label class="layui-form-label">签到范围</label><div class="layui-input-block"><select name="sign_radius" id="sign-radius" lay-filter="signRadius">' +
                '<option value="100">100米</option>' +
                '<option value="500">500米</option>' +
                '<option value="1000" selected>1公里</option>' +
                '<option value="5000">5公里</option>' +
                '<option value="10000">10公里</option>' +
                '</select></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">活动地址</label><div class="layui-input-block"><div class="location-search-wrapper" id="location-search-wrapper"><input type="text" name="sign_address" id="sign-address" class="layui-input" placeholder="搜索活动地址..." autocomplete="off"><i class="fas fa-search location-search-icon"></i><div id="location-suggestions" class="location-suggestions" style="display:none;"></div></div></div></div>' +
                '<div class="location-map-container" id="location-map-container"><div id="location-map" style="width:100%;height:300px;background:#f0f0f0;display:flex;align-items:center;justify-content:center;color:#999;"><i class="fas fa-map" style="font-size:24px;margin-right:8px;"></i>地图加载中...</div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">经度</label><div class="layui-input-inline" style="width:180px;"><input type="text" name="sign_longitude" id="sign-longitude" class="layui-input" placeholder="自动获取"></div>' +
                '<label class="layui-form-label" style="width:50px;">纬度</label><div class="layui-input-inline" style="width:180px;"><input type="text" name="sign_latitude" id="sign-latitude" class="layui-input" placeholder="自动获取"></div></div>' +
                '<div class="location-actions">' +
                '<button type="button" class="btn btn-default btn-sm" id="btn-get-location"><i class="fas fa-crosshairs"></i> 获取当前位置</button>' +
                '</div>' +
                '<div id="location-preview" class="location-preview" style="display:none;">' +
                '<div class="location-info">' +
                '<div class="location-coords" id="location-coords-display"></div>' +
                '<div class="location-radius-text" id="location-radius-display"></div>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>' +
                // === 底部保存按钮 ===
                '<div class="layui-form-item" style="margin-top:20px;"><div class="layui-input-block"><button type="button" class="btn btn-primary" id="btn-save-sign-config"><i class="fas fa-save"></i> 保存配置</button></div></div>' +
                '</form></div>';

            Layout.setContent(html);
            layui.form.render(null, 'signConfig');

            // 日期选择器
            layui.laydate.render({ elem: '#sign-start-time', type: 'datetime' });
            layui.laydate.render({ elem: '#sign-end-time', type: 'datetime' });

            // 地点限定开关监听
            layui.form.on('switch(signLocationSwitch)', function(data) {
                var panel = document.getElementById('location-config-panel');
                if (panel) panel.style.display = data.elem.checked ? 'block' : 'none';
                if (data.elem.checked) SignPage._initMapIfNeeded();
            });

            // 员工号开关监听
            layui.form.on('switch(showEmployeeNo)', function(data) {
                var row = document.getElementById('employee-no-require-row');
                if (row) row.style.display = data.elem.checked ? '' : 'none';
            });

            // 上传照片开关监听
            layui.form.on('switch(showPhoto)', function(data) {
                var row = document.getElementById('photo-require-row');
                if (row) row.style.display = data.elem.checked ? '' : 'none';
            });

            // 自定义字段开关监听
            layui.form.on('switch(showCustomFields)', function(data) {
                var panel = document.getElementById('custom-fields-panel');
                if (panel) panel.style.display = data.elem.checked ? 'block' : 'none';
            });

            // 手机号必填 → 短信验证码联动
            layui.form.on('checkbox(requirePhone)', function(data) {
                var verifyRow = document.getElementById('phone-verify-row');
                var verifyTip = document.getElementById('phone-verify-tip');
                if (verifyRow) verifyRow.style.display = data.elem.checked ? '' : 'none';
                if (verifyTip) verifyTip.style.display = data.elem.checked ? '' : 'none';
                if (!data.elem.checked) {
                    var verifyEl = document.getElementById('sign-require-phone-verify');
                    if (verifyEl) { verifyEl.checked = false; layui.form.render('checkbox', 'signConfig'); }
                }
            });

            // 自定义字段-字段类型变化监听（layui 接管 select 渲染，原生 onchange 不触发）
            layui.form.on('select(cfFieldType)', function(data) {
                SignPage._onCfTypeChange(data.elem);
            });

            // 半径选择变化 → 更新预览
            layui.form.on('select(signRadius)', function(data) {
                SignPage._updateLocationPreview();
            });

            // 获取当前位置按钮
            document.getElementById('btn-get-location').addEventListener('click', function() {
                SignPage._getCurrentLocation();
            });

            // 加载数据
            this._loadSignConfig(actId);

            // 保存
            document.getElementById('btn-save-sign-config').addEventListener('click', function() {
                SignPage._saveSignConfig(actId);
            });
        },

        _loadSignConfig: function(actId) {
            Api.getSignConfig(actId).then(function(res) {
                var data = (res.data && res.data.config) ? res.data.config : (res.data || {});
                // 填充基本配置
                if (data.enabled !== undefined) {
                    var el = document.querySelector('[name="enabled"]');
                    if (el) { el.checked = !!data.enabled; layui.form.render('checkbox', 'signConfig'); }
                }
                if (data.start_time) document.getElementById('sign-start-time').value = data.start_time;
                if (data.end_time) document.getElementById('sign-end-time').value = data.end_time;
                if (data.show_style || data.sign_show_style) {
                    var sv = data.show_style || data.sign_show_style;
                    var radio = document.querySelector('[name="show_style"][value="' + sv + '"]');
                    if (radio) { radio.checked = true; layui.form.render('radio', 'signConfig'); }
                }
                // 填充必填信息复选框
                var checkFields = ['require_name', 'require_phone', 'require_company', 'require_position'];
                for (var ci = 0; ci < checkFields.length; ci++) {
                    var cfName = checkFields[ci];
                    if (data[cfName] !== undefined) {
                        var cb = document.querySelector('[name="' + cfName + '"]');
                        if (cb) cb.checked = !!parseInt(data[cfName]);
                    }
                }
                // 填充微信头像设置
                if (data.use_wx_avatar !== undefined) {
                    var wxEl = document.querySelector('[name="use_wx_avatar"]');
                    if (wxEl) wxEl.checked = !!parseInt(data.use_wx_avatar);
                }
                // 填充短信验证码设置
                if (data.require_phone_verify !== undefined) {
                    var pvEl = document.getElementById('sign-require-phone-verify');
                    if (pvEl) pvEl.checked = !!parseInt(data.require_phone_verify);
                }
                // 联动：显示/隐藏短信验证码行
                var phoneChecked = !!parseInt(data.require_phone);
                var pvRow = document.getElementById('phone-verify-row');
                var pvTip = document.getElementById('phone-verify-tip');
                if (pvRow) pvRow.style.display = phoneChecked ? '' : 'none';
                if (pvTip) pvTip.style.display = phoneChecked ? '' : 'none';
                // 填充地点限定配置
                if (data.sign_location_enabled !== undefined) {
                    var locEl = document.getElementById('sign-location-enabled');
                    if (locEl) {
                        locEl.checked = !!parseInt(data.sign_location_enabled);
                        layui.form.render('checkbox', 'signConfig');
                    }
                    var panel = document.getElementById('location-config-panel');
                    if (panel) panel.style.display = parseInt(data.sign_location_enabled) ? 'block' : 'none';
                }
                if (data.sign_latitude) {
                    var latInput = document.getElementById('sign-latitude');
                    if (latInput) latInput.value = data.sign_latitude;
                }
                if (data.sign_longitude) {
                    var lngInput = document.getElementById('sign-longitude');
                    if (lngInput) lngInput.value = data.sign_longitude;
                }
                if (data.sign_radius) {
                    var radSelect = document.getElementById('sign-radius');
                    if (radSelect) {
                        radSelect.value = data.sign_radius;
                        layui.form.render('select', 'signConfig');
                    }
                }
                if (data.sign_address) {
                    var addrInput = document.getElementById('sign-address');
                    if (addrInput) addrInput.value = data.sign_address;
                }
                // 更新预览
                SignPage._updateLocationPreview();
                // 如果开启了地点限定，加载地图
                if (parseInt(data.sign_location_enabled)) {
                    SignPage._initMapIfNeeded();
                }

                // 加载员工号设置
                if (data.show_employee_no !== undefined) {
                    var empEl = document.getElementById('sign-show-employee-no');
                    if (empEl) { empEl.checked = !!parseInt(data.show_employee_no); }
                    var empRow = document.getElementById('employee-no-require-row');
                    if (empRow) empRow.style.display = parseInt(data.show_employee_no) ? '' : 'none';
                }
                if (data.require_employee_no !== undefined) {
                    var reqEmpEl = document.getElementById('sign-require-employee-no');
                    if (reqEmpEl) { reqEmpEl.checked = !!parseInt(data.require_employee_no); }
                }
                // 加载上传照片设置
                if (data.show_photo !== undefined) {
                    var photoEl = document.getElementById('sign-show-photo');
                    if (photoEl) { photoEl.checked = !!parseInt(data.show_photo); }
                    var photoRow = document.getElementById('photo-require-row');
                    if (photoRow) photoRow.style.display = parseInt(data.show_photo) ? '' : 'none';
                }
                if (data.require_photo !== undefined) {
                    var reqPhotoEl = document.getElementById('sign-require-photo');
                    if (reqPhotoEl) { reqPhotoEl.checked = !!parseInt(data.require_photo); }
                }
                // 加载自定义字段设置
                if (data.show_custom_fields !== undefined) {
                    var cfEl = document.getElementById('sign-show-custom-fields');
                    if (cfEl) { cfEl.checked = !!parseInt(data.show_custom_fields); }
                    var cfPanel = document.getElementById('custom-fields-panel');
                    if (cfPanel) cfPanel.style.display = parseInt(data.show_custom_fields) ? 'block' : 'none';
                }
                // 加载自定义字段列表
                SignPage._customFields = data.sign_custom_fields || [];
                SignPage._renderCustomFields();

                layui.form.render(null, 'signConfig');
            }).catch(function() {});
        },

        _saveSignConfig: function(actId) {
            var formData = {
                enabled: document.querySelector('[name="enabled"]').checked ? 1 : 0,
                start_time: document.getElementById('sign-start-time').value,
                end_time: document.getElementById('sign-end-time').value,
                require_name: document.querySelector('[name="require_name"]').checked ? 1 : 0,
                require_phone: document.querySelector('[name="require_phone"]').checked ? 1 : 0,
                require_phone_verify: document.getElementById('sign-require-phone-verify').checked ? 1 : 0,
                require_company: document.querySelector('[name="require_company"]').checked ? 1 : 0,
                require_position: document.querySelector('[name="require_position"]').checked ? 1 : 0,
                use_wx_avatar: document.querySelector('[name="use_wx_avatar"]').checked ? 1 : 0,
                show_style: document.querySelector('[name="show_style"]:checked').value,
                // 地点限定字段
                sign_location_enabled: document.getElementById('sign-location-enabled').checked ? 1 : 0,
                sign_latitude: parseFloat(document.getElementById('sign-latitude').value) || 0,
                sign_longitude: parseFloat(document.getElementById('sign-longitude').value) || 0,
                sign_radius: parseInt(document.getElementById('sign-radius').value) || 1000,
                sign_address: document.getElementById('sign-address').value,
                // 员工号设置
                show_employee_no: document.getElementById('sign-show-employee-no').checked ? 1 : 0,
                require_employee_no: document.getElementById('sign-require-employee-no').checked ? 1 : 0,
                // 上传照片设置
                show_photo: document.getElementById('sign-show-photo').checked ? 1 : 0,
                require_photo: document.getElementById('sign-require-photo').checked ? 1 : 0,
                // 自定义字段设置
                show_custom_fields: document.getElementById('sign-show-custom-fields').checked ? 1 : 0,
                sign_custom_fields: SignPage._collectCustomFields()
            };

            // 开启地点限定时校验必填
            if (formData.sign_location_enabled) {
                if (!formData.sign_latitude || !formData.sign_longitude) {
                    layui.layer.msg('请设置活动地点的经纬度', { icon: 2 });
                    return;
                }
            }

            Api.updateSignConfig(actId, formData).then(function() {
                layui.layer.msg('保存成功', { icon: 1 });
            });
        },

        /** 获取当前浏览器位置 */
        _getCurrentLocation: function() {
            if (!navigator.geolocation) {
                layui.layer.msg('您的浏览器不支持定位功能', { icon: 2 });
                return;
            }
            var loadIdx = layui.layer.load(2, { shade: [0.3, '#000'] });
            navigator.geolocation.getCurrentPosition(
                function(pos) {
                    layui.layer.close(loadIdx);
                    var lat = pos.coords.latitude;
                    var lng = pos.coords.longitude;
                    document.getElementById('sign-latitude').value = lat.toFixed(6);
                    document.getElementById('sign-longitude').value = lng.toFixed(6);
                    SignPage._updateLocationPreview();
                    layui.layer.msg('已获取当前位置', { icon: 1 });
                    // 更新地图
                    if (SignPage._mapInstance && window.qq && window.qq.maps) {
                        var latLng = new qq.maps.LatLng(lat, lng);
                        SignPage._mapInstance.setCenter(latLng);
                        SignPage._mapInstance.setZoom(16);
                        if (SignPage._mapMarker) {
                            SignPage._mapMarker.setPosition(latLng);
                            SignPage._mapMarker.setVisible(true);
                        }
                    }
                    // 通过后端API获取地址
                    Api.reverseGeo(lat.toFixed(6), lng.toFixed(6)).then(function(res) {
                        if (res.data && res.data.address) {
                            document.getElementById('sign-address').value = res.data.address;
                        }
                    }).catch(function(){});
                },
                function(err) {
                    layui.layer.close(loadIdx);
                    var msgs = {
                        1: '您拒绝了定位权限，请在浏览器设置中允许',
                        2: '无法获取位置信息，请检查网络或GPS',
                        3: '获取位置超时，请确保开启定位服务后重试'
                    };
                    layui.layer.msg(msgs[err.code] || '定位失败', { icon: 2 });
                },
                { enableHighAccuracy: false, timeout: 15000, maximumAge: 60000 }
            );
        },

        /** 更新地点预览信息 */
        _updateLocationPreview: function() {
            var lat = document.getElementById('sign-latitude').value;
            var lng = document.getElementById('sign-longitude').value;
            var radius = document.getElementById('sign-radius').value;
            var preview = document.getElementById('location-preview');
            var coordsDisplay = document.getElementById('location-coords-display');
            var radiusDisplay = document.getElementById('location-radius-display');

            if (lat && lng && parseFloat(lat) !== 0 && parseFloat(lng) !== 0) {
                if (preview) preview.style.display = 'block';
                if (coordsDisplay) coordsDisplay.innerHTML = '<i class="fas fa-map-pin"></i> 坐标：' + parseFloat(lat).toFixed(6) + ', ' + parseFloat(lng).toFixed(6);
                var radiusText = parseInt(radius) >= 1000 ? (parseInt(radius) / 1000) + '公里' : radius + '米';
                if (radiusDisplay) radiusDisplay.innerHTML = '<i class="fas fa-circle-notch"></i> 签到范围：方圆' + radiusText + '内';
            } else {
                if (preview) preview.style.display = 'none';
            }
        },

        /** 地图相关状态 */
        _mapInstance: null,
        _mapMarker: null,
        _mapKey: '',
        _mapSDKLoading: false,
        _searchTimer: null,

        /** 初始化地图（如果需要） */
        _initMapIfNeeded: function() {
            if (SignPage._mapInstance) return;
            if (!SignPage._mapKey) {
                Api.getMapKey().then(function(res) {
                    SignPage._mapKey = (res.data && res.data.map_key) || '';
                    if (SignPage._mapKey) {
                        SignPage._loadMapSDK(function() { SignPage._initMap(); });
                    } else {
                        var mapEl = document.getElementById('location-map');
                        if (mapEl) mapEl.innerHTML = '<i class="fas fa-exclamation-triangle" style="color:#e67e22;margin-right:8px;"></i>未配置地图Key，请在系统设置中配置';
                    }
                }).catch(function() {
                    layui.layer.msg('获取地图配置失败', { icon: 2 });
                });
            } else {
                SignPage._loadMapSDK(function() { SignPage._initMap(); });
            }
            // 初始化地址搜索（不依赖地图SDK）
            SignPage._initAddressSearch();
        },

        /** 加载腾讯地图SDK（仅用于地图渲染，不加载place库） */
        _loadMapSDK: function(callback) {
            if (window.qq && window.qq.maps) { callback(); return; }
            if (SignPage._mapSDKLoading) {
                var checkInterval = setInterval(function() {
                    if (window.qq && window.qq.maps) {
                        clearInterval(checkInterval);
                        callback();
                    }
                }, 200);
                return;
            }
            SignPage._mapSDKLoading = true;
            window._qqMapInit = function() {
                delete window._qqMapInit;
                SignPage._mapSDKLoading = false;
                callback();
            };
            var script = document.createElement('script');
            script.src = 'https://map.qq.com/api/js?v=2.exp&key=' + SignPage._mapKey + '&callback=_qqMapInit';
            script.onerror = function() {
                SignPage._mapSDKLoading = false;
                var mapEl = document.getElementById('location-map');
                if (mapEl) mapEl.innerHTML = '<i class="fas fa-exclamation-triangle" style="color:#e74c3c;margin-right:8px;"></i>地图加载失败，请检查网络';
            };
            document.head.appendChild(script);
        },

        /** 初始化地图实例 */
        _initMap: function() {
            var container = document.getElementById('location-map');
            if (!container) return;
            container.innerHTML = '';
            container.style.background = '';
            container.style.display = 'block';
            container.style.color = '';

            var latVal = document.getElementById('sign-latitude').value;
            var lngVal = document.getElementById('sign-longitude').value;
            var lat = parseFloat(latVal) || 26.647;
            var lng = parseFloat(lngVal) || 106.630;
            var hasCoords = !!(latVal && lngVal && parseFloat(latVal) !== 0 && parseFloat(lngVal) !== 0);

            var center = new qq.maps.LatLng(lat, lng);
            var map = new qq.maps.Map(container, {
                center: center,
                zoom: hasCoords ? 16 : 12,
                mapTypeControl: false
            });
            SignPage._mapInstance = map;

            var marker = new qq.maps.Marker({
                position: center,
                map: map,
                draggable: true
            });
            if (!hasCoords) marker.setVisible(false);
            SignPage._mapMarker = marker;

            // 点击地图选点
            qq.maps.event.addListener(map, 'click', function(event) {
                var latLng = event.latLng;
                marker.setPosition(latLng);
                marker.setVisible(true);
                document.getElementById('sign-latitude').value = latLng.getLat().toFixed(6);
                document.getElementById('sign-longitude').value = latLng.getLng().toFixed(6);
                SignPage._updateLocationPreview();
                // 通过后端API逆地理编码
                Api.reverseGeo(latLng.getLat().toFixed(6), latLng.getLng().toFixed(6)).then(function(res) {
                    if (res.data && res.data.address) {
                        document.getElementById('sign-address').value = res.data.address;
                    }
                }).catch(function(){});
            });

            // 拖动标记更新坐标
            qq.maps.event.addListener(marker, 'dragend', function(event) {
                var latLng = event.latLng;
                document.getElementById('sign-latitude').value = latLng.getLat().toFixed(6);
                document.getElementById('sign-longitude').value = latLng.getLng().toFixed(6);
                SignPage._updateLocationPreview();
                Api.reverseGeo(latLng.getLat().toFixed(6), latLng.getLng().toFixed(6)).then(function(res) {
                    if (res.data && res.data.address) {
                        document.getElementById('sign-address').value = res.data.address;
                    }
                }).catch(function(){});
            });
        },

        /** 初始化地址搜索（通过后端代理API） */
        _initAddressSearch: function() {
            var input = document.getElementById('sign-address');
            var sugBox = document.getElementById('location-suggestions');
            if (!input || !sugBox) return;

            // 防抖搜索
            input.addEventListener('input', function() {
                var keyword = input.value.trim();
                if (SignPage._searchTimer) clearTimeout(SignPage._searchTimer);
                if (keyword.length < 2) {
                    sugBox.style.display = 'none';
                    return;
                }
                SignPage._searchTimer = setTimeout(function() {
                    Api.searchPlace(keyword).then(function(res) {
                        var places = (res.data && Array.isArray(res.data)) ? res.data : [];
                        if (places.length === 0) {
                            sugBox.style.display = 'none';
                            return;
                        }
                        var html = '';
                        for (var i = 0; i < places.length && i < 8; i++) {
                            var p = places[i];
                            html += '<div class="sug-item" data-lat="' + p.lat + '" data-lng="' + p.lng + '" data-title="' + (p.title || '').replace(/"/g, '&quot;') + '" data-address="' + (p.address || '').replace(/"/g, '&quot;') + '">';
                            html += '<div class="sug-title"><i class="fas fa-map-marker-alt"></i> ' + (p.title || '') + '</div>';
                            html += '<div class="sug-addr">' + (p.address || '') + '</div>';
                            html += '</div>';
                        }
                        sugBox.innerHTML = html;
                        sugBox.style.display = 'block';

                        // 绑定点击事件
                        var items = sugBox.querySelectorAll('.sug-item');
                        for (var j = 0; j < items.length; j++) {
                            items[j].addEventListener('click', function() {
                                var lat = parseFloat(this.getAttribute('data-lat'));
                                var lng = parseFloat(this.getAttribute('data-lng'));
                                var title = this.getAttribute('data-title');
                                var addr = this.getAttribute('data-address');
                                input.value = addr || title;
                                document.getElementById('sign-latitude').value = lat.toFixed(6);
                                document.getElementById('sign-longitude').value = lng.toFixed(6);
                                sugBox.style.display = 'none';
                                SignPage._updateLocationPreview();
                                // 更新地图
                                if (SignPage._mapInstance && window.qq && window.qq.maps) {
                                    var latLng = new qq.maps.LatLng(lat, lng);
                                    SignPage._mapInstance.setCenter(latLng);
                                    SignPage._mapInstance.setZoom(16);
                                    if (SignPage._mapMarker) {
                                        SignPage._mapMarker.setPosition(latLng);
                                        SignPage._mapMarker.setVisible(true);
                                    }
                                }
                            });
                        }
                    }).catch(function() {
                        sugBox.style.display = 'none';
                    });
                }, 300);
            });

            // 点击其他地方关闭下拉
            document.addEventListener('click', function(e) {
                if (!input.contains(e.target) && !sugBox.contains(e.target)) {
                    sugBox.style.display = 'none';
                }
            });
        },

        // ========== 签到名单 ==========
        renderList: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title"><span>签到名单</span></div>' +
                '<div class="toolbar"><div class="toolbar-left">' +
                '<input type="text" id="sign-search" class="layui-input" style="width:220px;height:36px;" placeholder="搜索昵称/姓名/手机号">' +
                '<button class="btn btn-default" onclick="SignPage._searchList()"><i class="fas fa-search"></i> 搜索</button>' +
                '</div><div class="toolbar-right">' +
                '<button class="btn btn-primary" onclick="SignPage._exportList()"><i class="fas fa-download"></i> 导出</button>' +
                '<button class="btn btn-danger" onclick="SignPage._clearList()"><i class="fas fa-trash"></i> 清空</button>' +
                '</div></div>' +
                '<table id="sign-table" lay-filter="signTable"></table>' +
                '<div id="sign-pager" style="padding:10px 15px;"></div></div>';

            Layout.setContent(html);
            this._initTable(actId);
        },

        _initTable: function(actId) {
            var that = this;
            that._signActId = actId;
            that._signPage = 1;
            that._signLimit = 20;
            that._signKeyword = '';

            // 操作栏模板
            if (!document.getElementById('signTableBar')) {
                var script = document.createElement('script');
                script.type = 'text/html';
                script.id = 'signTableBar';
                script.innerHTML = '<a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="del"><i class="fas fa-trash"></i> 删除</a>';
                document.body.appendChild(script);
            }

            // 绑定表格行操作事件
            layui.table.on('tool(signTable)', function(obj) {
                if (obj.event === 'del') {
                    layui.layer.confirm('确定删除该签到记录？', { icon: 3 }, function(idx) {
                        Api.deleteParticipant(actId, obj.data.id).then(function() {
                            layui.layer.close(idx);
                            obj.del();
                            layui.layer.msg('删除成功', { icon: 1 });
                            // 重新加载当前页
                            that._loadSignTable();
                        });
                    });
                }
            });

            // 管理员开关
            layui.form.on('switch(toggleAdmin)', function(data) {
                var pid = data.elem.getAttribute('data-id');
                Api.http.post('/sign/' + actId + '/participant/' + pid + '/toggle-admin').then(function(res) {
                    layui.layer.msg(res.msg || '操作成功', { icon: 1 });
                }).catch(function() {
                    data.elem.checked = !data.elem.checked;
                    layui.form.render('checkbox');
                    layui.layer.msg('操作失败', { icon: 2 });
                });
            });

            // 核销员开关
            layui.form.on('switch(toggleVerifier)', function(data) {
                var pid = data.elem.getAttribute('data-id');
                Api.http.post('/sign/' + actId + '/participant/' + pid + '/toggle-verifier').then(function(res) {
                    layui.layer.msg(res.msg || '操作成功', { icon: 1 });
                }).catch(function() {
                    data.elem.checked = !data.elem.checked;
                    layui.form.render('checkbox');
                    layui.layer.msg('操作失败', { icon: 2 });
                });
            });

            // 首次加载数据
            that._loadSignTable();
        },

        // 通过 Axios 加载签到数据并渲染表格（确保 Hd-Token 认证）
        _loadSignTable: function(page) {
            var that = this;
            var actId = that._signActId;
            page = page || that._signPage || 1;
            var limit = that._signLimit || 20;
            var keyword = that._signKeyword || '';

            Api.getSignList(actId, { page: page, limit: limit, keyword: keyword }).then(function(res) {
                var list = res.data ? (res.data.list || []) : [];
                var count = res.data ? (res.data.count || 0) : 0;

                // 渲染表格（使用 data 参数，不依赖 url）
                layui.table.render({
                    elem: '#sign-table',
                    id: 'sign-table',
                    data: list,
                    cols: [[
                        { field: 'signorder', title: '序号', width: 80, sort: true },
                        { field: 'avatar', title: '头像', width: 70, templet: function(d) {
                            return d.avatar ? '<img class="avatar-preview" src="' + d.avatar + '">' : '<i class="fas fa-user-circle" style="font-size:36px;color:#ccc;"></i>';
                        }},
                        { field: 'nickname', title: '昵称', width: 120 },
                        { field: 'signname', title: '姓名', width: 100 },
                        { field: 'employee_no', title: '员工号', width: 110 },
                        { field: 'phone', title: '手机号', width: 130, templet: function(d) {
                            if (!d.phone) return '-';
                            return d.phone.replace(/(\d{3})\d{4}(\d{4})/, '$1****$2');
                        }},
                        { field: 'sign_photo', title: '签到照片', width: 90, templet: function(d) {
                            if (!d.sign_photo) return '-';
                            return '<img src="' + d.sign_photo + '" style="width:40px;height:40px;object-fit:cover;border-radius:4px;cursor:pointer;" onclick="layui.layer.photos({photos:{data:[{src:\x27' + d.sign_photo + '\x27}]}}); return false;">';
                        }},
                        { field: 'datetime', title: '签到时间', width: 170 },
                        { field: 'is_admin', title: '管理员', width: 90, align: 'center', templet: function(d) {
                            var checked = parseInt(d.is_admin) === 1 ? ' checked' : '';
                            return '<input type="checkbox" lay-skin="switch" lay-text="是|否" lay-filter="toggleAdmin" data-id="' + d.id + '"' + checked + '>';
                        }},
                        { field: 'is_verifier', title: '核销员', width: 90, align: 'center', templet: function(d) {
                            var checked = parseInt(d.is_verifier) === 1 ? ' checked' : '';
                            return '<input type="checkbox" lay-skin="switch" lay-text="是|否" lay-filter="toggleVerifier" data-id="' + d.id + '"' + checked + '>';
                        }},
                        { title: '操作', width: 100, align: 'center', toolbar: '#signTableBar' }
                    ]],
                    limit: limit,
                    text: { none: '暂无签到数据' }
                });

                // 渲染分页
                if (count > limit) {
                    layui.laypage.render({
                        elem: 'sign-pager',
                        count: count,
                        limit: limit,
                        curr: page,
                        layout: ['count', 'prev', 'page', 'next'],
                        jump: function(obj, first) {
                            if (!first) {
                                that._signPage = obj.curr;
                                that._loadSignTable(obj.curr);
                            }
                        }
                    });
                } else {
                    document.getElementById('sign-pager') && (document.getElementById('sign-pager').innerHTML = count > 0 ? '<span style="color:#999;font-size:13px;">共 ' + count + ' 条记录</span>' : '');
                }

                // 渲染 layui form 组件（开关等）
                layui.form.render();
            }).catch(function(err) {
                console.error('[SignPage] 加载签到名单失败:', err);
                layui.table.render({
                    elem: '#sign-table',
                    id: 'sign-table',
                    data: [],
                    cols: [[
                        { field: 'signorder', title: '序号', width: 80 },
                        { field: 'nickname', title: '昵称', width: 120 },
                        { field: 'signname', title: '姓名', width: 100 },
                        { field: 'phone', title: '手机号', width: 130 },
                        { field: 'datetime', title: '签到时间', width: 170 }
                    ]],
                    text: { none: '加载失败，请刷新重试' }
                });
            });
        },

        _searchList: function() {
            var that = this;
            that._signKeyword = document.getElementById('sign-search').value;
            that._signPage = 1;
            that._loadSignTable(1);
        },

        _exportList: function() {
            var actId = App.getCurrentActivityId();
            if (actId) Api.exportParticipants(actId);
        },

        _clearList: function() {
            var that = this;
            var actId = that._signActId || App.getCurrentActivityId();
            if (!actId) return;
            layui.layer.confirm('确定清空所有签到记录？此操作不可恢复！', { icon: 3, title: '警告' }, function(idx) {
                Api.clearSignList(actId).then(function() {
                    layui.layer.close(idx);
                    that._signPage = 1;
                    that._loadSignTable(1);
                    layui.layer.msg('已清空', { icon: 1 });
                });
            });
        },

        // ========== 手机签到页 ==========
        _mobileConfig: {},  // 缓存当前配置
        _signConfigCache: {},  // 缓存签到设置配置（用于预览联动）

        renderMobile: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title">手机签到页配置</div>' +
                '<div class="mobile-config-layout" style="display:flex;gap:30px;align-items:flex-start;flex-wrap:nowrap;">' +
                // === 左侧：配置表单区 ===
                '<div class="mobile-config-form" style="flex:1;min-width:0;overflow:hidden;">' +
                '<form class="layui-form" lay-filter="signMobile">' +

                // 签到页背景图
                '<div class="form-section"><div class="section-title">签到页背景图</div>' +
                '<div class="layui-form-item">' +
                '<div class="upload-area" id="mobile-bg-upload-area">' +
                '<div id="mobile-bg-preview" class="upload-preview" style="display:none;"><img id="mobile-bg-preview-img" src="" style="max-width:200px;max-height:120px;border-radius:4px;"><a href="javascript:;" class="upload-remove" onclick="SignPage._removeMobileImage(\'mobile_bg_image\')"><i class="fas fa-times"></i></a></div>' +
                '<div id="mobile-bg-upload-btn" class="upload-btn-area"><input type="file" id="mobile-bg-file" accept="image/jpeg,image/png" style="display:none;" onchange="SignPage._handleMobileUpload(this, \'mobile_bg_image\')">' +
                '<button type="button" class="btn btn-default" onclick="document.getElementById(\'mobile-bg-file\').click()"><i class="fas fa-cloud-upload-alt"></i> 选择图片</button></div>' +
                '<div class="upload-tip">推荐使用深色背景图，尺寸600×1138像素，图片大小请勿超过1M</div>' +
                '</div></div></div>' +

                // 活动信息图片
                '<div class="form-section"><div class="section-title">活动信息图片</div>' +
                '<div class="layui-form-item">' +
                '<div class="upload-area" id="mobile-activity-upload-area">' +
                '<div id="mobile-activity-preview" class="upload-preview" style="display:none;"><img id="mobile-activity-preview-img" src="" style="max-width:200px;max-height:120px;border-radius:4px;"><a href="javascript:;" class="upload-remove" onclick="SignPage._removeMobileImage(\'mobile_activity_image\')"><i class="fas fa-times"></i></a></div>' +
                '<div id="mobile-activity-upload-btn" class="upload-btn-area"><input type="file" id="mobile-activity-file" accept="image/jpeg,image/png" style="display:none;" onchange="SignPage._handleMobileUpload(this, \'mobile_activity_image\')">' +
                '<button type="button" class="btn btn-default" onclick="document.getElementById(\'mobile-activity-file\').click()"><i class="fas fa-cloud-upload-alt"></i> 选择图片</button></div>' +
                '<div class="upload-tip">建议将活动信息或活动流程制作成手机样式的图片然后上传，不上传则保持默认签到成功页面样式</div>' +
                '</div></div></div>' +

                // 其他设置
                '<div class="form-section"><div class="section-title">其他设置</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">隐藏头像</label><div class="layui-input-block"><input type="checkbox" name="mobile_hide_avatar" id="mobile-hide-avatar" lay-skin="switch" lay-text="是|否" lay-filter="mobileHideAvatar"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">快捷留言</label><div class="layui-input-block"><input type="checkbox" name="mobile_quick_message" id="mobile-quick-message" lay-skin="switch" lay-text="开启|关闭" lay-filter="mobileQuickMessage"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label" style="width:170px;">强制关注公众号授权登录</label><div class="layui-input-block" style="margin-left:170px;"><input type="checkbox" name="mobile_force_wx_auth" id="mobile-force-wx-auth" lay-skin="switch" lay-text="开启|关闭" lay-filter="mobileForceWxAuth"></div></div>' +
                '<div class="upload-tip" style="margin-top:-5px;margin-bottom:10px;padding-left:2px;">默认使用平台的微信服务号，若需使用自己的微信公众服务，需前往仪表盘进行公众号设置</div>' +
                '</div>' +

                // 签到后欢迎语
                '<div class="form-section"><div class="section-title">签到后欢迎语</div>' +
                '<div class="layui-form-item"><div class="layui-input-block" style="margin-left:0;max-width:400px;"><input type="text" name="mobile_welcome_text" id="mobile-welcome-text" class="layui-input" placeholder="欢迎参与本次活动" maxlength="100" oninput="SignPage._updateMobilePreview()"></div>' +
                '<div class="upload-tip">签到成功页面底部显示的文字，最多100个字符</div></div></div>' +

                // 签到按钮名称
                '<div class="form-section"><div class="section-title">签到按钮名称</div>' +
                '<div class="layui-form-item"><div class="layui-input-block" style="margin-left:0;max-width:260px;"><input type="text" name="mobile_btn_text" id="mobile-btn-text" class="layui-input" placeholder="参 与 活 动" maxlength="20" oninput="SignPage._updateMobilePreview()"></div></div></div>' +

                // 签到按钮图片
                '<div class="form-section"><div class="section-title">签到按钮图片</div>' +
                '<div class="layui-form-item">' +
                '<div class="upload-area" id="mobile-btn-upload-area">' +
                '<div id="mobile-btn-preview" class="upload-preview" style="display:none;"><img id="mobile-btn-preview-img" src="" style="max-width:200px;max-height:50px;border-radius:4px;"><a href="javascript:;" class="upload-remove" onclick="SignPage._removeMobileImage(\'mobile_btn_image\')"><i class="fas fa-times"></i></a></div>' +
                '<div id="mobile-btn-upload-btn" class="upload-btn-area"><input type="file" id="mobile-btn-file" accept="image/png,image/jpeg" style="display:none;" onchange="SignPage._handleMobileUpload(this, \'mobile_btn_image\')">' +
                '<button type="button" class="btn btn-default" onclick="document.getElementById(\'mobile-btn-file\').click()"><i class="fas fa-cloud-upload-alt"></i> 选择图片</button></div>' +
                '<div class="upload-tip">推荐尺寸310×45像素，默认为适配主题的动效按钮</div>' +
                '</div></div></div>' +

                // 保存按钮
                '<div class="layui-form-item" style="margin-top:20px;"><button type="button" class="btn btn-primary" id="btn-save-mobile-config"><i class="fas fa-save"></i> 保存配置</button></div>' +
                '</form></div>' +

                // === 右侧：手机预览区 ===
                '<div class="mobile-preview-wrapper" style="width:300px;flex-shrink:0;">' +
                '<div class="mobile-preview-title" style="text-align:center;margin-bottom:10px;font-weight:bold;color:#666;">手机预览</div>' +
                '<div class="mobile-preview-frame" id="mobile-preview-frame" style="width:270px;height:auto;min-height:480px;border:2px solid #333;border-radius:24px;overflow:hidden;position:relative;background:linear-gradient(135deg,#0c1445 0%,#1a237e 50%,#0d47a1 100%);margin:0 auto;">' +
                // 背景层
                '<div id="mp-bg-layer" style="position:absolute;top:0;left:0;width:100%;height:100%;background-size:cover;background-position:center;"></div>' +
                // 内容层
                '<div style="position:relative;z-index:1;display:flex;flex-direction:column;align-items:center;padding:30px 20px 40px;">' +
                // 头像区（含照片上传覆盖层）
                '<div id="mp-avatar-area" style="text-align:center;margin-bottom:12px;position:relative;">' +
                '<div style="width:70px;height:70px;border-radius:50%;background:#ccc;margin:0 auto 6px;border:2px solid rgba(255,255,255,0.8);overflow:hidden;position:relative;">' +
                '<i class="fas fa-user" style="font-size:40px;color:#fff;line-height:70px;"></i>' +
                '<div id="mp-photo-overlay" style="display:none;position:absolute;top:0;left:0;width:100%;height:100%;background:rgba(0,0,0,0.45);display:none;align-items:center;justify-content:center;flex-direction:column;">' +
                '<i class="fas fa-camera" style="font-size:18px;color:#fff;"></i>' +
                '<span style="font-size:8px;color:#fff;margin-top:2px;">上传照片</span>' +
                '</div></div>' +
                '<div style="color:#fff;font-size:11px;">用户昵称</div></div>' +
                // 表单字段区（动态渲染）
                '<div id="mp-fields-area" style="width:100%;margin-bottom:12px;"></div>' +
                // 按钮区
                '<div id="mp-btn-area" style="text-align:center;margin-bottom:12px;width:85%;"><div id="mp-btn-default" style="background:linear-gradient(90deg,#ff6b35,#ff4444);color:#fff;padding:8px 24px;border-radius:22px;font-size:13px;text-align:center;">参 与 活 动</div><div id="mp-btn-image" style="display:none;"><img id="mp-btn-img" src="" style="max-width:100%;height:auto;"></div></div>' +
                // 底部欢迎语
                '<div id="mp-welcome" style="color:rgba(255,255,255,0.8);font-size:11px;text-align:center;margin-top:8px;">欢迎参与本次活动</div>' +
                '</div></div>' +
                '<div id="mobile-sign-url-area" style="margin-top:14px;">' +
                '<div style="color:#666;font-size:12px;margin-bottom:6px;text-align:center;"><i class="fas fa-link" style="margin-right:4px;"></i>手机端签到页面地址</div>' +
                '<div style="display:flex;align-items:center;gap:6px;background:#f5f5f5;padding:8px 10px;border-radius:6px;border:1px solid #e8e8e8;">' +
                '<input type="text" id="mobile-sign-url-input" readonly style="flex:1;border:none;background:transparent;font-size:12px;color:#333;outline:none;overflow:hidden;text-overflow:ellipsis;" value="加载中...">' +
                '<button type="button" class="btn btn-default btn-xs" onclick="SignPage._copySignUrl()" style="white-space:nowrap;padding:4px 10px;font-size:12px;"><i class="fas fa-copy"></i> 复制</button>' +
                '</div></div>' +
                '</div>' +

                '</div></div>';

            Layout.setContent(html);
            layui.form.render(null, 'signMobile');

            // 监听开关变化
            layui.form.on('switch(mobileHideAvatar)', function() { SignPage._updateMobilePreview(); });
            layui.form.on('switch(mobileQuickMessage)', function() { SignPage._updateMobilePreview(); });
            layui.form.on('switch(mobileForceWxAuth)', function() { SignPage._updateMobilePreview(); });

            // 加载配置
            SignPage._loadMobileConfig(actId);
            // 加载手机端签到页地址
            SignPage._loadSignUrl(actId);

            // 保存
            document.getElementById('btn-save-mobile-config').addEventListener('click', function() {
                SignPage._saveMobileConfig(actId);
            });
        },

        /** 加载手机页配置 */
        _loadMobileConfig: function(actId) {
            // 同时加载手机页配置和签到设置配置
            var mobilePromise = Api.http.get('/sign/' + actId + '/mobile-config');
            var signPromise = Api.getSignConfig(actId);

            mobilePromise.then(function(res) {
                var d = res.data || {};
                SignPage._mobileConfig = d;

                // 回填背景图
                if (d.mobile_bg_image) {
                    document.getElementById('mobile-bg-preview-img').src = d.mobile_bg_image;
                    document.getElementById('mobile-bg-preview').style.display = 'inline-block';
                }
                // 回填活动信息图片
                if (d.mobile_activity_image) {
                    document.getElementById('mobile-activity-preview-img').src = d.mobile_activity_image;
                    document.getElementById('mobile-activity-preview').style.display = 'inline-block';
                }
                // 回填按钮图片
                if (d.mobile_btn_image) {
                    document.getElementById('mobile-btn-preview-img').src = d.mobile_btn_image;
                    document.getElementById('mobile-btn-preview').style.display = 'inline-block';
                }
                // 回填开关
                var hideEl = document.getElementById('mobile-hide-avatar');
                if (hideEl) { hideEl.checked = !!parseInt(d.mobile_hide_avatar); }
                var quickEl = document.getElementById('mobile-quick-message');
                if (quickEl) { quickEl.checked = !!parseInt(d.mobile_quick_message); }
                var wxAuthEl = document.getElementById('mobile-force-wx-auth');
                if (wxAuthEl) { wxAuthEl.checked = !!parseInt(d.mobile_force_wx_auth); }
                layui.form.render('checkbox', 'signMobile');

                // 回填文本
                document.getElementById('mobile-welcome-text').value = d.mobile_welcome_text || '欢迎参与本次活动';
                document.getElementById('mobile-btn-text').value = d.mobile_btn_text || '参 与 活 动';

                // 更新预览
                SignPage._updateMobilePreview();
            }).catch(function() {});

            // 加载签到设置配置（用于预览表单字段）
            signPromise.then(function(res) {
                var data = (res.data && res.data.config) ? res.data.config : (res.data || {});
                SignPage._signConfigCache = data;
                // 更新预览（签到配置加载完成后再次刷新）
                SignPage._updateMobilePreview();
            }).catch(function() {});
        },

        /** 图片上传处理 */
        _handleMobileUpload: function(input, fieldName) {
            if (!input.files || !input.files[0]) return;
            var file = input.files[0];

            // 大小校验
            var maxSize = fieldName === 'mobile_btn_image' ? 500 * 1024 : 1024 * 1024;
            if (file.size > maxSize) {
                layui.layer.msg('图片大小超出限制', { icon: 2 });
                input.value = '';
                return;
            }

            var formData = new FormData();
            formData.append('file', file);
            var loadIdx = layui.layer.load(2, { shade: [0.3, '#000'] });
            Api.uploadImage(formData).then(function(res) {
                layui.layer.close(loadIdx);
                var url = res.data ? (res.data.url || res.data) : '';
                if (!url) { layui.layer.msg('上传失败', { icon: 2 }); return; }
                SignPage._mobileConfig[fieldName] = url;

                // 更新预览
                if (fieldName === 'mobile_bg_image') {
                    document.getElementById('mobile-bg-preview-img').src = url;
                    document.getElementById('mobile-bg-preview').style.display = 'inline-block';
                } else if (fieldName === 'mobile_activity_image') {
                    document.getElementById('mobile-activity-preview-img').src = url;
                    document.getElementById('mobile-activity-preview').style.display = 'inline-block';
                } else if (fieldName === 'mobile_btn_image') {
                    document.getElementById('mobile-btn-preview-img').src = url;
                    document.getElementById('mobile-btn-preview').style.display = 'inline-block';
                }
                SignPage._updateMobilePreview();
            }).catch(function() {
                layui.layer.close(loadIdx);
                layui.layer.msg('上传失败', { icon: 2 });
            });
            input.value = '';
        },

        /** 移除已上传图片 */
        _removeMobileImage: function(fieldName) {
            SignPage._mobileConfig[fieldName] = '';
            if (fieldName === 'mobile_bg_image') {
                document.getElementById('mobile-bg-preview').style.display = 'none';
                document.getElementById('mobile-bg-preview-img').src = '';
            } else if (fieldName === 'mobile_activity_image') {
                document.getElementById('mobile-activity-preview').style.display = 'none';
                document.getElementById('mobile-activity-preview-img').src = '';
            } else if (fieldName === 'mobile_btn_image') {
                document.getElementById('mobile-btn-preview').style.display = 'none';
                document.getElementById('mobile-btn-preview-img').src = '';
            }
            SignPage._updateMobilePreview();
        },

        /** 更新手机预览区 */
        _updateMobilePreview: function() {
            var cfg = SignPage._mobileConfig || {};
            var signCfg = SignPage._signConfigCache || {};
            // 背景
            var bgLayer = document.getElementById('mp-bg-layer');
            if (bgLayer) {
                var bgImg = cfg.mobile_bg_image || (document.getElementById('mobile-bg-preview-img') || {}).src || '';
                bgLayer.style.backgroundImage = bgImg ? 'url(' + bgImg + ')' : 'none';
            }
            // 头像显隐
            var hideAvatar = document.getElementById('mobile-hide-avatar');
            var avatarArea = document.getElementById('mp-avatar-area');
            if (avatarArea) avatarArea.style.display = (hideAvatar && hideAvatar.checked) ? 'none' : 'block';
            // 照片上传覆盖层
            var photoOverlay = document.getElementById('mp-photo-overlay');
            if (photoOverlay) {
                var showPhoto = parseInt(signCfg.show_photo) || 0;
                photoOverlay.style.display = showPhoto ? 'flex' : 'none';
                // 照片必填时加红色边框提示
                var avatarCircle = photoOverlay.parentElement;
                if (avatarCircle) {
                    avatarCircle.style.borderColor = (showPhoto && parseInt(signCfg.require_photo)) ? '#ff4444' : 'rgba(255,255,255,0.8)';
                }
            }
            // 动态表单字段区
            var fieldsArea = document.getElementById('mp-fields-area');
            if (fieldsArea) {
                var fieldsHtml = '';
                var inputStyle = 'width:100%;height:26px;border:1px solid rgba(255,255,255,0.35);border-radius:4px;background:rgba(255,255,255,0.12);padding:0 8px;font-size:10px;color:rgba(255,255,255,0.6);margin-bottom:6px;box-sizing:border-box;outline:none;';
                var requiredMark = '<span style="color:#ff4444;margin-right:2px;">*</span>';
                // 必填信息字段
                if (parseInt(signCfg.require_name)) {
                    fieldsHtml += '<div style="' + inputStyle + 'display:flex;align-items:center;">' + requiredMark + '请输入姓名</div>';
                }
                if (parseInt(signCfg.require_phone)) {
                    fieldsHtml += '<div style="' + inputStyle + 'display:flex;align-items:center;">' + requiredMark + '请输入电话</div>';
                }
                if (parseInt(signCfg.require_company)) {
                    fieldsHtml += '<div style="' + inputStyle + 'display:flex;align-items:center;">' + requiredMark + '请输入公司</div>';
                }
                if (parseInt(signCfg.require_position)) {
                    fieldsHtml += '<div style="' + inputStyle + 'display:flex;align-items:center;">' + requiredMark + '请输入职位</div>';
                }
                // 员工号字段
                if (parseInt(signCfg.show_employee_no)) {
                    var empRequired = parseInt(signCfg.require_employee_no);
                    fieldsHtml += '<div style="' + inputStyle + 'display:flex;align-items:center;">' + (empRequired ? requiredMark : '') + '请输入员工号</div>';
                }
                // 自定义字段
                if (parseInt(signCfg.show_custom_fields) && signCfg.sign_custom_fields && signCfg.sign_custom_fields.length > 0) {
                    for (var fi = 0; fi < signCfg.sign_custom_fields.length; fi++) {
                        var cf = signCfg.sign_custom_fields[fi];
                        var cfName = cf.name || cf.field_name || ('自定义字段' + (fi + 1));
                        var cfRequired = parseInt(cf.required) || 0;
                        var cfType = cf.type || cf.field_type || 'text';
                        if (cfType === 'select') {
                            fieldsHtml += '<div style="' + inputStyle + 'display:flex;align-items:center;justify-content:space-between;">' + (cfRequired ? requiredMark : '') + '请选择' + cfName + '<i class="fas fa-chevron-down" style="font-size:8px;"></i></div>';
                        } else {
                            fieldsHtml += '<div style="' + inputStyle + 'display:flex;align-items:center;">' + (cfRequired ? requiredMark : '') + '请输入' + cfName + '</div>';
                        }
                    }
                }
                fieldsArea.innerHTML = fieldsHtml;
            }
            // 按钮
            var btnText = document.getElementById('mobile-btn-text');
            var btnImage = cfg.mobile_btn_image || '';
            var mpBtnDefault = document.getElementById('mp-btn-default');
            var mpBtnImage = document.getElementById('mp-btn-image');
            if (mpBtnDefault && mpBtnImage) {
                if (btnImage) {
                    mpBtnDefault.style.display = 'none';
                    mpBtnImage.style.display = 'block';
                    document.getElementById('mp-btn-img').src = btnImage;
                } else {
                    mpBtnDefault.style.display = 'block';
                    mpBtnImage.style.display = 'none';
                    if (btnText) mpBtnDefault.textContent = btnText.value || '参 与 活 动';
                }
            }
            // 欢迎语
            var welcomeText = document.getElementById('mobile-welcome-text');
            var mpWelcome = document.getElementById('mp-welcome');
            if (mpWelcome && welcomeText) mpWelcome.textContent = welcomeText.value || '欢迎参与本次活动';
        },

        /** 保存手机页配置 */
        _saveMobileConfig: function(actId) {
            if (!actId) actId = App.getCurrentActivityId();
            if (!actId) return;

            var data = {
                mobile_bg_image: SignPage._mobileConfig.mobile_bg_image || '',
                mobile_activity_image: SignPage._mobileConfig.mobile_activity_image || '',
                mobile_hide_avatar: document.getElementById('mobile-hide-avatar').checked ? 1 : 0,
                mobile_quick_message: document.getElementById('mobile-quick-message').checked ? 1 : 0,
                mobile_welcome_text: document.getElementById('mobile-welcome-text').value || '欢迎参与本次活动',
                mobile_btn_text: document.getElementById('mobile-btn-text').value || '参 与 活 动',
                mobile_btn_image: SignPage._mobileConfig.mobile_btn_image || '',
                mobile_force_wx_auth: document.getElementById('mobile-force-wx-auth').checked ? 1 : 0
            };

            Api.http.post('/sign/' + actId + '/mobile-config', data).then(function() {
                layui.layer.msg('保存成功', { icon: 1 });
            });
        },

        /** 加载手机端签到页地址 */
        _loadSignUrl: function(actId) {
            Api.getMobileUrls(actId).then(function(res) {
                var data = res.data || {};
                var signUrl = '';
                var urls = data.urls || [];
                for (var i = 0; i < urls.length; i++) {
                    if (urls[i].key === 'qiandao') {
                        signUrl = urls[i].url;
                        break;
                    }
                }
                if (!signUrl && data.qrcode_text) signUrl = data.qrcode_text;
                var urlInput = document.getElementById('mobile-sign-url-input');
                if (urlInput) urlInput.value = signUrl || '未获取到地址';
            }).catch(function() {
                var urlInput = document.getElementById('mobile-sign-url-input');
                if (urlInput) urlInput.value = '获取地址失败';
            });
        },

        /** 复制签到页地址 */
        _copySignUrl: function() {
            var urlInput = document.getElementById('mobile-sign-url-input');
            if (!urlInput || !urlInput.value || urlInput.value === '加载中...' || urlInput.value === '获取地址失败') {
                layui.layer.msg('暂无可复制的地址', { icon: 2 });
                return;
            }
            var url = urlInput.value;
            if (navigator.clipboard && navigator.clipboard.writeText) {
                navigator.clipboard.writeText(url).then(function() {
                    layui.layer.msg('地址已复制到剪贴板', { icon: 1 });
                }).catch(function() {
                    SignPage._fallbackCopy(url);
                });
            } else {
                SignPage._fallbackCopy(url);
            }
        },

        /** 降级复制方案 */
        _fallbackCopy: function(text) {
            var ta = document.createElement('textarea');
            ta.value = text;
            ta.style.cssText = 'position:fixed;left:-9999px;top:-9999px;';
            document.body.appendChild(ta);
            ta.select();
            try {
                document.execCommand('copy');
                layui.layer.msg('地址已复制到剪贴板', { icon: 1 });
            } catch (e) {
                layui.layer.msg('复制失败，请手动复制', { icon: 2 });
            }
            document.body.removeChild(ta);
        },

        // ========== 3D签到 ==========
        _3dEffects: [],
        _3dShapeNames: { sphere: '球形', torus: '隧道', grid: '方阵', helix: '螺旋', cylinder: '圆柱体', gene: '基因' },
        _3dTypeLabels: { preset_shape: '预设造型', image_logo: '图片Logo', text_logo: '文字Logo', countdown: '倒计时' },
        _3dTypeIcons: { preset_shape: 'fa-cube', image_logo: 'fa-image', text_logo: 'fa-font', countdown: 'fa-hourglass-half' },

        render3d: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title">3D签到设置</div>' +
                '<div style="display:flex;gap:24px;flex-wrap:wrap;">' +
                // === 左侧：全局配置 ===
                '<div style="flex:1;min-width:320px;">' +
                '<form class="layui-form" lay-filter="sign3d">' +
                '<div class="form-section"><div class="section-title">全局配置</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">播放模式</label><div class="layui-input-block">' +
                '<input type="radio" name="play_mode" value="sequential" title="顺序播放" checked lay-filter="sign3dPlayMode">' +
                '<input type="radio" name="play_mode" value="random" title="随机播放" lay-filter="sign3dPlayMode">' +
                '</div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">头像数量</label><div class="layui-input-inline" style="width:150px;"><input type="number" name="avatarnum" id="td-avatarnum" class="layui-input" value="30" min="1" max="200"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">头像大小</label><div class="layui-input-inline" style="width:150px;"><input type="number" name="avatarsize" id="td-avatarsize" class="layui-input" value="7" min="1" max="50"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">头像间距</label><div class="layui-input-inline" style="width:150px;"><input type="number" name="avatargap" id="td-avatargap" class="layui-input" value="15" min="1" max="50"></div></div>' +
                '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" id="btn-save-3d-config"><i class="fas fa-save"></i> 保存配置</button></div></div>' +
                '</div></form></div>' +
                // === 右侧：效果列表 ===
                '<div style="flex:1;min-width:360px;">' +
                '<div class="form-section"><div class="section-title" style="display:flex;justify-content:space-between;align-items:center;">' +
                '<span>效果列表</span>' +
                '<button type="button" class="btn btn-primary btn-sm" id="btn-add-3d-effect"><i class="fas fa-plus"></i> 添加效果</button>' +
                '</div>' +
                '<div class="upload-tip" style="margin-bottom:10px;"><i class="fas fa-info-circle"></i> 拖拽卡片可调整播放顺序，至少保留一个效果</div>' +
                '<div id="effects-list" style="min-height:80px;"></div>' +
                '</div></div>' +
                '</div></div>';

            Layout.setContent(html);
            layui.form.render(null, 'sign3d');

            SignPage._load3dConfig(actId);

            document.getElementById('btn-save-3d-config').addEventListener('click', function() {
                SignPage._save3dGlobalConfig(actId);
            });
            document.getElementById('btn-add-3d-effect').addEventListener('click', function() {
                SignPage._showAddEffectDialog(actId);
            });
        },

        /** 加载3D配置和效果列表 */
        _load3dConfig: function(actId) {
            Api.get3dConfig(actId).then(function(res) {
                var d = res.data || {};
                var cfg = d.config || {};
                // 回填全局配置
                document.getElementById('td-avatarnum').value = cfg.avatarnum || 30;
                document.getElementById('td-avatarsize').value = cfg.avatarsize || 7;
                document.getElementById('td-avatargap').value = cfg.avatargap || 15;
                var modeRadio = document.querySelector('[name="play_mode"][value="' + (cfg.play_mode || 'sequential') + '"]');
                if (modeRadio) { modeRadio.checked = true; layui.form.render('radio', 'sign3d'); }
                // 渲染效果列表
                SignPage._3dEffects = d.effects || [];
                SignPage._render3dEffectsList(actId);
            }).catch(function() {});
        },

        /** 保存全局配置 */
        _save3dGlobalConfig: function(actId) {
            var data = {
                avatarnum: parseInt(document.getElementById('td-avatarnum').value) || 30,
                avatarsize: parseInt(document.getElementById('td-avatarsize').value) || 7,
                avatargap: parseInt(document.getElementById('td-avatargap').value) || 15,
                play_mode: (document.querySelector('[name="play_mode"]:checked') || {}).value || 'sequential'
            };
            Api.save3dConfig(actId, data).then(function() {
                layui.layer.msg('配置已保存', { icon: 1 });
            });
        },

        /** 渲染效果列表 */
        _render3dEffectsList: function(actId) {
            var list = document.getElementById('effects-list');
            if (!list) return;
            var effects = SignPage._3dEffects;
            if (!effects || effects.length === 0) {
                list.innerHTML = '<div style="text-align:center;color:#999;padding:30px;">暂无效果，请添加</div>';
                return;
            }
            var html = '';
            for (var i = 0; i < effects.length; i++) {
                var e = effects[i];
                var iconClass = SignPage._3dTypeIcons[e.type] || 'fa-cube';
                var typeName = SignPage._3dTypeLabels[e.type] || e.type;
                var displayName = '';
                if (e.type === 'preset_shape') {
                    displayName = SignPage._3dShapeNames[e.content] || e.content;
                } else if (e.type === 'text_logo') {
                    displayName = '"' + e.content + '"';
                } else if (e.type === 'image_logo') {
                    displayName = 'Logo图片';
                } else if (e.type === 'countdown') {
                    displayName = '倒计时 ' + e.content + '秒';
                }
                var canDelete = effects.length > 1;
                html += '<div class="effect-card" draggable="true" data-id="' + e.id + '" style="display:flex;align-items:center;gap:10px;padding:10px 14px;margin-bottom:8px;background:#f8f9fa;border:1px solid #e8e8e8;border-radius:6px;cursor:grab;transition:all .2s;">' +
                    '<span style="color:#aaa;font-size:16px;cursor:grab;"><i class="fas fa-grip-vertical"></i></span>' +
                    '<span style="width:32px;height:32px;border-radius:6px;background:#e3f2fd;display:flex;align-items:center;justify-content:center;"><i class="fas ' + iconClass + '" style="color:#1976d2;font-size:14px;"></i></span>' +
                    '<div style="flex:1;min-width:0;">' +
                    '<div style="font-size:14px;font-weight:500;color:#333;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;">' + displayName + '</div>' +
                    '<div style="font-size:12px;color:#999;margin-top:2px;">' + typeName + '</div>' +
                    '</div>' +
                    (canDelete ? '<a href="javascript:;" class="effect-del-btn" data-eid="' + e.id + '" style="color:#ff4d4f;font-size:14px;padding:4px 8px;" title="删除"><i class="fas fa-trash-alt"></i></a>' : '<span style="color:#ccc;font-size:14px;padding:4px 8px;" title="至少保留一个"><i class="fas fa-trash-alt"></i></span>') +
                    '</div>';
            }
            list.innerHTML = html;

            // 绑定删除事件
            var delBtns = list.querySelectorAll('.effect-del-btn');
            for (var j = 0; j < delBtns.length; j++) {
                delBtns[j].addEventListener('click', function() {
                    var eid = parseInt(this.getAttribute('data-eid'));
                    SignPage._delete3dEffect(actId, eid);
                });
            }

            // 初始化拖拽排序
            SignPage._initEffectsDragSort(actId);
        },

        /** 初始化拖拽排序 */
        _initEffectsDragSort: function(actId) {
            var list = document.getElementById('effects-list');
            if (!list) return;
            var dragSrc = null;

            list.addEventListener('dragstart', function(ev) {
                dragSrc = ev.target.closest('.effect-card');
                if (!dragSrc) return;
                dragSrc.style.opacity = '0.4';
                ev.dataTransfer.effectAllowed = 'move';
                ev.dataTransfer.setData('text/plain', dragSrc.dataset.id);
            });

            list.addEventListener('dragover', function(ev) {
                ev.preventDefault();
                ev.dataTransfer.dropEffect = 'move';
                var target = ev.target.closest('.effect-card');
                if (target && target !== dragSrc) {
                    var rect = target.getBoundingClientRect();
                    var mid = rect.top + rect.height / 2;
                    if (ev.clientY > mid) {
                        target.parentNode.insertBefore(dragSrc, target.nextSibling);
                    } else {
                        target.parentNode.insertBefore(dragSrc, target);
                    }
                }
            });

            list.addEventListener('dragend', function() {
                if (dragSrc) dragSrc.style.opacity = '1';
                var cards = list.querySelectorAll('.effect-card');
                var ids = [];
                for (var k = 0; k < cards.length; k++) { ids.push(parseInt(cards[k].dataset.id)); }
                Api.reorder3dEffects(actId, ids).then(function(res) {
                    if (res.data && res.data.effects) { SignPage._3dEffects = res.data.effects; }
                    layui.layer.msg('排序已更新', { icon: 1, time: 1000 });
                });
            });
        },

        /** 删除效果 */
        _delete3dEffect: function(actId, effectId) {
            layui.layer.confirm('确定删除该效果？', { icon: 3 }, function(idx) {
                Api.delete3dEffect(actId, effectId).then(function(res) {
                    layui.layer.close(idx);
                    if (res.data && res.data.effects) { SignPage._3dEffects = res.data.effects; }
                    SignPage._render3dEffectsList(actId);
                    layui.layer.msg('已删除', { icon: 1 });
                });
            });
        },

        /** 显示添加效果弹窗 */
        _showAddEffectDialog: function(actId) {
            var content =
                '<div style="padding:15px;">' +
                '<ul class="layui-tab layui-tab-brief" lay-filter="addEffectTab" style="margin:0;">' +
                '<li class="layui-tab-title">' +
                '<li class="layui-this">预设3D造型</li>' +
                '<li>图片Logo</li>' +
                '<li>文字Logo</li>' +
                '<li>倒计时</li>' +
                '</li>' +
                '<div class="layui-tab-content" style="padding:15px 0;">' +
                // Tab 1: 预设3D造型
                '<div class="layui-tab-item layui-show">' +
                '<div class="layui-form-item"><label class="layui-form-label">选择造型</label><div class="layui-input-block">' +
                '<select id="add-effect-shape" class="layui-input">' +
                '<option value="sphere">球形</option><option value="torus">隧道</option><option value="grid">方阵</option>' +
                '<option value="helix">螺旋</option><option value="cylinder">圆柱体</option><option value="gene">基因</option>' +
                '</select></div></div>' +
                '</div>' +
                // Tab 2: 图片Logo
                '<div class="layui-tab-item">' +
                '<div class="layui-form-item"><div class="layui-input-block" style="margin-left:0;">' +
                '<input type="file" id="add-effect-logo-file" accept="image/png" style="display:none;">' +
                '<button type="button" class="btn btn-default" id="btn-select-logo"><i class="fas fa-cloud-upload-alt"></i> 选择PNG图片</button>' +
                '<span id="logo-file-name" style="margin-left:10px;color:#666;"></span>' +
                '<input type="hidden" id="add-effect-logo-url" value="">' +
                '</div>' +
                '<div class="upload-tip" style="margin-top:8px;"><i class="fas fa-info-circle"></i> 请上传png格式透明Logo，≤2MB</div>' +
                '<div class="upload-tip"><i class="fas fa-info-circle"></i> 去掉白色或其他背景色，简化Logo</div>' +
                '</div>' +
                '</div>' +
                // Tab 3: 文字Logo
                '<div class="layui-tab-item">' +
                '<div class="layui-form-item"><label class="layui-form-label">文字内容</label><div class="layui-input-block">' +
                '<input type="text" id="add-effect-text" class="layui-input" placeholder="如：欢迎莅临" maxlength="20">' +
                '</div></div>' +
                '<div class="upload-tip" style="margin-left:110px;"><i class="fas fa-info-circle"></i> 建议4字左右效果最佳</div>' +
                '</div>' +
                // Tab 4: 倒计时
                '<div class="layui-tab-item">' +
                '<div class="layui-form-item"><label class="layui-form-label">倒计时(秒)</label><div class="layui-input-inline" style="width:150px;">' +
                '<input type="number" id="add-effect-countdown" class="layui-input" value="10" min="3" max="300">' +
                '</div></div>' +
                '<div class="upload-tip" style="margin-left:110px;"><i class="fas fa-info-circle"></i> 大屏幕上按空格键可开启倒计时</div>' +
                '</div>' +
                '</div>' +
                '</ul>' +
                '</div>';

            var dialogIdx = layui.layer.open({
                type: 1,
                title: '添加效果',
                area: ['520px', '340px'],
                content: content,
                btn: ['确定添加', '取消'],
                yes: function(index) {
                    // 判断当前活跃的 tab
                    var activeTab = document.querySelector('.layui-tab-title .layui-this');
                    var tabText = activeTab ? activeTab.textContent.trim() : '';
                    var type = '', contentVal = '';

                    if (tabText === '预设3D造型') {
                        type = 'preset_shape';
                        contentVal = document.getElementById('add-effect-shape').value;
                    } else if (tabText === '图片Logo') {
                        type = 'image_logo';
                        contentVal = document.getElementById('add-effect-logo-url').value;
                        if (!contentVal) {
                            layui.layer.msg('请先上传Logo图片', { icon: 2 });
                            return;
                        }
                    } else if (tabText === '文字Logo') {
                        type = 'text_logo';
                        contentVal = (document.getElementById('add-effect-text').value || '').trim();
                        if (!contentVal) {
                            layui.layer.msg('请输入文字内容', { icon: 2 });
                            return;
                        }
                    } else if (tabText === '倒计时') {
                        type = 'countdown';
                        contentVal = document.getElementById('add-effect-countdown').value || '10';
                    }

                    Api.add3dEffect(actId, { type: type, content: contentVal }).then(function(res) {
                        layui.layer.close(index);
                        if (res.data && res.data.effects) { SignPage._3dEffects = res.data.effects; }
                        SignPage._render3dEffectsList(actId);
                        layui.layer.msg('效果已添加', { icon: 1 });
                    });
                }
            });

            // 渲染 layui tab 和 form
            layui.element.render('tab', 'addEffectTab');

            // 图片上传按钮绑定
            setTimeout(function() {
                var btnLogo = document.getElementById('btn-select-logo');
                var fileInput = document.getElementById('add-effect-logo-file');
                if (btnLogo) {
                    btnLogo.addEventListener('click', function() { fileInput.click(); });
                }
                if (fileInput) {
                    fileInput.addEventListener('change', function() {
                        if (!this.files || !this.files[0]) return;
                        var file = this.files[0];
                        if (file.size > 2 * 1024 * 1024) {
                            layui.layer.msg('文件大小超过2MB', { icon: 2 });
                            this.value = '';
                            return;
                        }
                        if (file.type !== 'image/png') {
                            layui.layer.msg('仅支持PNG格式', { icon: 2 });
                            this.value = '';
                            return;
                        }
                        var fd = new FormData();
                        fd.append('logo_file', file);
                        var loadIdx = layui.layer.load(2, { shade: [0.3, '#000'] });
                        Api.upload3dLogo(actId, fd).then(function(res) {
                            layui.layer.close(loadIdx);
                            var url = res.data ? res.data.url : '';
                            if (url) {
                                document.getElementById('add-effect-logo-url').value = url;
                                document.getElementById('logo-file-name').textContent = file.name;
                            } else {
                                layui.layer.msg('上传失败', { icon: 2 });
                            }
                        }).catch(function() {
                            layui.layer.close(loadIdx);
                        });
                        this.value = '';
                    });
                }
            }, 100);
        },

        // ========== 自定义字段管理 ==========
        _customFields: [],
        _cfIndex: 0,

        /** 渲染自定义字段列表 */
        _renderCustomFields: function() {
            var tbody = document.getElementById('custom-fields-body');
            if (!tbody) return;
            var fields = SignPage._customFields || [];
            var html = '';
            SignPage._cfIndex = fields.length;
            for (var i = 0; i < fields.length; i++) {
                html += SignPage._buildCustomFieldRow(i, fields[i]);
            }
            tbody.innerHTML = html;
            // 让 layui 重新渲染新插入的 select 元素
            layui.form.render('select', 'signConfig');
        },

        /** 构建一行自定义字段 HTML */
        _buildCustomFieldRow: function(idx, data) {
            data = data || {};
            var ft = data.field_type || 'text';
            var optDisabled = (ft === 'select' || ft === 'checkbox') ? '' : 'disabled';
            var optVal = data.field_options || '';
            if (Array.isArray(optVal)) optVal = optVal.join(',');
            return '<tr data-cf-idx="' + idx + '">' +
                '<td><input type="text" class="layui-input" style="width:140px;" value="' + (data.field_name || '') + '" data-cf="field_name"></td>' +
                '<td><select class="layui-input" style="width:90px;" data-cf="field_type" lay-filter="cfFieldType">' +
                '<option value="text"' + (ft==='text'?' selected':'') + '>文本</option>' +
                '<option value="select"' + (ft==='select'?' selected':'') + '>单选</option>' +
                '<option value="checkbox"' + (ft==='checkbox'?' selected':'') + '>多选</option>' +
                '<option value="image"' + (ft==='image'?' selected':'') + '>图片</option>' +
                '</select></td>' +
                '<td><input type="text" class="layui-input cf-options-input" style="width:170px;" value="' + optVal + '" data-cf="field_options" placeholder="英文逗号分隔" ' + optDisabled + '></td>' +
                '<td><select class="layui-input" style="width:70px;" data-cf="is_required">' +
                '<option value="0"' + (parseInt(data.is_required)!==1?' selected':'') + '>否</option>' +
                '<option value="1"' + (parseInt(data.is_required)===1?' selected':'') + '>是</option>' +
                '</select></td>' +
                '<td><input type="number" class="layui-input" style="width:60px;" value="' + (data.sort || 0) + '" data-cf="sort"></td>' +
                '<td><a href="javascript:;" class="layui-btn layui-btn-danger layui-btn-xs" onclick="SignPage._removeCustomField(this)"><i class="fas fa-trash"></i></a></td>' +
                '</tr>';
        },

        /** 添加一行自定义字段 */
        _addCustomField: function() {
            var tbody = document.getElementById('custom-fields-body');
            if (!tbody) return;
            var idx = SignPage._cfIndex;
            var tempContainer = document.createElement('tbody');
            tempContainer.innerHTML = SignPage._buildCustomFieldRow(idx, {});
            tbody.appendChild(tempContainer.firstChild);
            SignPage._cfIndex++;
            // 让 layui 重新渲染新插入的 select 元素
            layui.form.render('select', 'signConfig');
        },

        /** 删除一行自定义字段 */
        _removeCustomField: function(btn) {
            var tr = btn.closest('tr');
            if (tr) tr.remove();
        },

        /** 字段类型变化时切换选项输入框 */
        _onCfTypeChange: function(sel) {
            var tr = sel.closest('tr');
            var optInput = tr.querySelector('.cf-options-input');
            if (sel.value === 'select' || sel.value === 'checkbox') {
                optInput.disabled = false;
            } else {
                optInput.disabled = true;
                optInput.value = '';
            }
        },

        /** 收集自定义字段数据 */
        _collectCustomFields: function() {
            var rows = document.querySelectorAll('#custom-fields-body tr');
            var result = [];
            for (var i = 0; i < rows.length; i++) {
                var row = rows[i];
                var name = row.querySelector('[data-cf="field_name"]').value.trim();
                if (!name) continue;
                var ft = row.querySelector('[data-cf="field_type"]').value;
                var opts = row.querySelector('[data-cf="field_options"]').value.trim();
                var optArr = [];
                if ((ft === 'select' || ft === 'checkbox') && opts) {
                    optArr = opts.split(',').map(function(s){ return s.trim(); }).filter(function(s){ return s; });
                }
                result.push({
                    field_name: name,
                    field_type: ft,
                    field_options: optArr,
                    is_required: parseInt(row.querySelector('[data-cf="is_required"]').value) || 0,
                    sort: parseInt(row.querySelector('[data-cf="sort"]').value) || 0
                });
            }
            return result;
        }
    };

    global.SignPage = SignPage;
})(window);
