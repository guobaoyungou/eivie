/**
 * 签到管理页面模块
 * 包含：签到设置、签到名单、白名单、手机签到页、3D签到
 */
;(function(global) {
    'use strict';

    // API路径配置
    var API_CONFIG = {
        basePath: '/api/hd/',
        get sign() {
            return {
                import: this.basePath + 'sign/import',
                doimport: this.basePath + 'sign/doimport',
                whitelist: this.basePath + 'sign/whitelist', // 保留兼容性
                permissions: this.basePath + 'sign/permissions' // 保留兼容性
            };
        },
        // 新的API端点
        get hdSign() {
            var self = this;
            return {
                toggleAdmin: function(activityId, userId) {
                    return self.basePath + 'sign/' + activityId + '/participant/' + userId + '/toggle-admin';
                },
                toggleVerifier: function(activityId, userId) {
                    return self.basePath + 'sign/' + activityId + '/participant/' + userId + '/toggle-verifier';
                },
                // 白名单API
                whitelist: function(activityId) {
                    return self.basePath + 'sign/' + activityId + '/whitelist';
                },
                whitelistDelete: function(activityId, whitelistId) {
                    return self.basePath + 'sign/' + activityId + '/whitelist/' + whitelistId;
                },
                whitelistClear: function(activityId) {
                    return self.basePath + 'sign/' + activityId + '/whitelist/clear';
                }
            };
        }
    };

    var SignPage = {
        // ========== 签到设置 ==========
        renderConfig: function() {
            SignPage._mapInstance = null;
            SignPage._mapMarker = null;
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<style>' +
                '.sign-config-container { display: grid; grid-template-columns: 1.6fr 1fr; gap: 32px; max-width: 1400px; margin: 0 auto; }' +
                '.sign-config-left { display: grid; gap: 24px; align-content: start; }' +
                '.sign-config-right { display: grid; gap: 24px; align-content: start; }' +
                '.form-panel { background: #fff; border-radius: 12px; padding: 28px; box-shadow: 0 2px 12px rgba(0,0,0,0.05); border: 1px solid #f0f0f0; transition: box-shadow 0.3s ease; }' +
                '.form-panel:hover { box-shadow: 0 4px 16px rgba(0,0,0,0.08); }' +
                '.panel-title { font-size: 18px; font-weight: 600; color: #1a1a1a; margin-bottom: 24px; padding-bottom: 16px; border-bottom: 2px solid #1890ff; display: flex; align-items: center; gap: 10px; }' +
                '.panel-title i { font-size: 20px; color: #1890ff; }' +
                '.form-group { margin-bottom: 20px; }' +
                '.form-label { font-size: 14px; font-weight: 500; color: #333; margin-bottom: 8px; display: flex; align-items: center; gap: 6px; }' +
                '.form-label i { color: #666; font-size: 14px; }' +
                '.form-row { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }' +
                '.form-info { font-size: 13px; color: #666; margin-top: 6px; display: flex; align-items: center; gap: 6px; padding-left: 24px; }' +
                '.form-info i { color: #1890ff; }' +
                '.checkbox-group { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; padding: 8px 0; }' +
                '.checkbox-item { display: flex; align-items: center; gap: 8px; }' +
                '.checkbox-item label { font-size: 14px; color: #333; margin: 0; }' +
                '.feature-highlight { position: relative; border-left: 4px solid #1890ff; padding-left: 16px; background: linear-gradient(135deg, #f8fafc 0%, #ffffff 100%); }' +
                '.feature-icon { width: 36px; height: 36px; border-radius: 8px; background: linear-gradient(135deg, #1890ff 0%, #52c41a 100%); display: flex; align-items: center; justify-content: center; margin-right: 12px; }' +
                '.feature-icon i { color: white; font-size: 18px; }' +
                '.section-header { display: flex; align-items: center; margin-bottom: 20px; }' +
                '.map-container { border-radius: 8px; overflow: hidden; border: 1px solid #e8e8e8; }' +
                '.action-bar { position: sticky; bottom: 0; background: #fff; border-top: 1px solid #e8e8e8; padding: 24px; margin-top: 40px; box-shadow: 0 -2px 12px rgba(0,0,0,0.05); z-index: 100; }' +
                '.action-content { max-width: 1200px; margin: 0 auto; display: flex; justify-content: space-between; align-items: center; }' +
                '.action-info { display: flex; align-items: center; gap: 12px; color: #666; font-size: 14px; }' +
                '.action-info i { color: #1890ff; font-size: 16px; }' +
                '.save-button { background: linear-gradient(135deg, #1890ff 0%, #096dd9 100%); border: none; padding: 12px 36px; font-size: 16px; font-weight: 500; border-radius: 8px; color: white; cursor: pointer; display: flex; align-items: center; gap: 10px; transition: all 0.3s ease; box-shadow: 0 4px 12px rgba(24, 144, 255, 0.3); }' +
                '.save-button:hover { background: linear-gradient(135deg, #096dd9 0%, #0050b3 100%); transform: translateY(-2px); box-shadow: 0 6px 16px rgba(24, 144, 255, 0.4); }' +
                '.save-button:active { transform: translateY(0); }' +
                '.save-button i { font-size: 18px; }' +
                '.time-inputs { display: grid; grid-template-columns: 1fr auto 1fr; gap: 12px; align-items: center; }' +
                '.time-divider { color: #999; font-size: 14px; text-align: center; padding: 0 8px; }' +
                '.radio-group { display: flex; gap: 20px; }' +
                '.radio-item { display: flex; align-items: center; gap: 6px; }' +
                '.radio-item label { margin: 0; }' +
                '.badge { background: #1890ff; color: white; font-size: 12px; padding: 2px 8px; border-radius: 10px; margin-left: 8px; vertical-align: middle; }' +
                '.location-card { width: 100%; max-height: none; overflow: visible; }' +
                '.location-config-panel { animation: fadeIn 0.3s ease; }' +
                '@keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }' +
                '</style>' +

                '<div class="sign-config-container">' +
                // === 左侧：主要配置 ===
                '<div class="sign-config-left">' +
                
                // 基本配置
                '<div class="form-panel">' +
                '<div class="panel-title"><i class="fas fa-cogs"></i>基本配置</div>' +
                '<div class="form-group">' +
                '<div class="form-label"><i class="fas fa-power-off"></i>功能开关</div>' +
                '<div class="layui-input-block" style="margin-left:0;">' +
                '<input type="checkbox" name="enabled" lay-skin="switch" lay-text="ON|OFF" id="sign-enabled">' +
                '</div>' +
                '<div class="form-info"><i class="fas fa-info-circle"></i>开启后用户可以在活动页进行签到</div>' +
                '</div>' +
                '<div class="form-group">' +
                '<div class="form-label"><i class="fas fa-clock"></i>签到时间段</div>' +
                '<div class="time-inputs">' +
                '<input type="text" name="start_time" id="sign-start-time" class="layui-input" placeholder="开始时间" style="width:100%;height:36px;border-radius:6px;">' +
                '<div class="time-divider">至</div>' +
                '<input type="text" name="end_time" id="sign-end-time" class="layui-input" placeholder="结束时间" style="width:100%;height:36px;border-radius:6px;">' +
                '</div>' +
                '<div class="form-info"><i class="fas fa-info-circle"></i>设置签到开始和结束的时间范围</div>' +
                '</div>' +
                '</div>' +
                
                // 必填信息
                '<div class="form-panel feature-highlight">' +
                '<div class="panel-title"><i class="fas fa-id-card"></i>必填信息 <span class="badge">重要</span></div>' +
                '<div class="form-group">' +
                '<div class="form-label">选择需要用户填写的信息</div>' +
                '<div class="checkbox-group">' +
                '<div class="checkbox-item"><input type="checkbox" name="require_name" title="姓名" lay-skin="primary"> <label>姓名</label></div>' +
                '<div class="checkbox-item"><input type="checkbox" name="require_phone" title="电话" lay-skin="primary" lay-filter="requirePhone"> <label>电话</label></div>' +
                '<div class="checkbox-item"><input type="checkbox" name="require_company" title="公司" lay-skin="primary"> <label>公司</label></div>' +
                '<div class="checkbox-item"><input type="checkbox" name="require_position" title="职位" lay-skin="primary"> <label>职位</label></div>' +
                '</div>' +
                '<div id="phone-verify-row" class="form-group" style="display:none;margin-top:16px;">' +
                '<div class="form-label"><i class="fas fa-sms"></i>短信验证码</div>' +
                '<div class="layui-input-block" style="margin-left:0;">' +
                '<input type="checkbox" name="require_phone_verify" id="sign-require-phone-verify" lay-skin="switch" lay-text="开启|关闭">' +
                '</div>' +
                '<div id="phone-verify-tip" class="form-info" style="display:none;"><i class="fas fa-shield-alt"></i>开启后签到时需要输入短信验证码，确保手机号真实有效</div>' +
                '</div>' +
                '</div>' +
                '</div>' +
                
                // 显示设置和员工号设置（并排）
                '<div class="form-row">' +
                '<div class="form-panel">' +
                '<div class="panel-title"><i class="fas fa-eye"></i>显示设置</div>' +
                '<div class="form-group">' +
                '<div class="form-label">头像来源</div>' +
                '<div class="radio-group">' +
                '<div class="radio-item"><input type="radio" name="avatar_source" value="1" title="微信头像" checked lay-filter="avatarSource"> <label>微信头像</label></div>' +
                '<div class="radio-item"><input type="radio" name="avatar_source" value="0" title="显示照片" lay-filter="avatarSource" id="avatar-source-photo"> <label>显示照片</label></div>' +
                '</div>' +
                '<div id="avatar-photo-tip" class="form-info" style="display:none;color:#faad14;"><i class="fas fa-exclamation-triangle"></i>需先在照片设置中开启功能才可选择</div>' +
                '</div>' +
                '<div class="form-group">' +
                '<div class="form-label">嘉宾显示方式</div>' +
                '<div class="radio-group">' +
                '<div class="radio-item"><input type="radio" name="show_style" value="1" title="昵称" checked> <label>昵称</label></div>' +
                '<div class="radio-item"><input type="radio" name="show_style" value="2" title="姓名"> <label>姓名</label></div>' +
                '<div class="radio-item"><input type="radio" name="show_style" value="3" title="手机号"> <label>手机号</label></div>' +
                '</div>' +
                '</div>' +
                '</div>' +
                
                '<div class="form-panel">' +
                '<div class="panel-title"><i class="fas fa-id-badge"></i>员工号设置</div>' +
                '<div class="form-group">' +
                '<div class="form-label">启用员工号</div>' +
                '<div class="layui-input-block" style="margin-left:0;">' +
                '<input type="checkbox" name="show_employee_no" id="sign-show-employee-no" lay-skin="switch" lay-text="开启|关闭" lay-filter="showEmployeeNo">' +
                '</div>' +
                '</div>' +
                '<div id="employee-no-require-row" class="form-group" style="display:none;">' +
                '<div class="form-label">员工号必填</div>' +
                '<div class="layui-input-block" style="margin-left:0;">' +
                '<input type="checkbox" name="require_employee_no" id="sign-require-employee-no" lay-skin="switch" lay-text="必填|非必填">' +
                '</div>' +
                '</div>' +
                '<div class="form-info"><i class="fas fa-info-circle"></i>用于企业内部员工识别和管理</div>' +
                '</div>' +
                '</div>' +
                
                // 照片和自定义字段设置（并排）
                '<div class="form-row">' +
                '<div class="form-panel">' +
                '<div class="panel-title"><i class="fas fa-camera"></i>照片设置</div>' +
                '<div class="form-group">' +
                '<div class="form-label">启用照片功能</div>' +
                '<div class="layui-input-block" style="margin-left:0;">' +
                '<input type="checkbox" name="show_photo" id="sign-show-photo" lay-skin="switch" lay-text="开启|关闭" lay-filter="showPhoto">' +
                '</div>' +
                '</div>' +
                '<div id="photo-require-row" class="form-group" style="display:none;">' +
                '<div class="form-label">照片必填</div>' +
                '<div class="layui-input-block" style="margin-left:0;">' +
                '<input type="checkbox" name="require_photo" id="sign-require-photo" lay-skin="switch" lay-text="必填|非必填" lay-filter="requirePhoto">' +
                '</div>' +
                '</div>' +
                '<div class="form-info"><i class="fas fa-info-circle"></i>用于收集用户照片信息</div>' +
                '</div>' +
                
                '<div class="form-panel">' +
                '<div class="panel-title"><i class="fas fa-edit"></i>自定义字段</div>' +
                '<div class="form-group">' +
                '<div class="form-label">启用自定义字段</div>' +
                '<div class="layui-input-block" style="margin-left:0;">' +
                '<input type="checkbox" name="show_custom_fields" id="sign-show-custom-fields" lay-skin="switch" lay-text="开启|关闭" lay-filter="showCustomFields">' +
                '</div>' +
                '</div>' +
                '<div id="custom-fields-panel" style="display:none;margin-top:16px;">' +
                '<div class="form-info" style="margin-bottom:12px;"><i class="fas fa-list"></i>自定义信息采集字段</div>' +
                '<table class="layui-table" id="custom-fields-table" style="margin:0;border-radius:8px;overflow:hidden;">' +
                '<thead><tr>' +
                '<th style="width:150px;">字段名称</th>' +
                '<th style="width:100px;">字段类型</th>' +
                '<th style="width:180px;">选项值</th>' +
                '<th style="width:80px;">必填</th>' +
                '<th style="width:70px;">排序</th>' +
                '<th style="width:60px;">操作</th>' +
                '</tr></thead><tbody id="custom-fields-body"></tbody></table>' +
                '<button type="button" class="btn btn-default btn-sm" style="margin-top:12px;border-radius:6px;" onclick="SignPage._addCustomField()"><i class="fas fa-plus"></i> 添加字段</button>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>' +
                
                // === 右侧：地点限定 ===
                '<div class="sign-config-right">' +
                '<div class="form-panel location-card">' +
                '<div class="panel-title"><i class="fas fa-map-marker-alt"></i>地点限定 <span class="badge">高级</span></div>' +
                '<div class="form-group">' +
                '<div class="form-label">启用地点限定</div>' +
                '<div class="layui-input-block" style="margin-left:0;">' +
                '<input type="checkbox" name="sign_location_enabled" id="sign-location-enabled" lay-skin="switch" lay-text="开启|关闭" lay-filter="signLocationSwitch">' +
                '</div>' +
                '<div class="form-info"><i class="fas fa-location-arrow"></i>开启后用户必须在指定范围内签到</div>' +
                '</div>' +
                '<div id="location-config-panel" class="location-config-panel" style="display:none;margin-top:20px;">' +
                '<div class="form-group">' +
                '<div class="form-label">签到范围</div>' +
                '<select name="sign_radius" id="sign-radius" class="layui-select" lay-filter="signRadius" style="width:100%;height:36px;border-radius:6px;">' +
                '<option value="100">100米</option>' +
                '<option value="500">500米</option>' +
                '<option value="1000" selected>1公里</option>' +
                '<option value="5000">5公里</option>' +
                '<option value="10000">10公里</option>' +
                '</select>' +
                '</div>' +
                '<div class="form-group">' +
                '<div class="form-label">活动地址</div>' +
                '<div class="location-search-wrapper" id="location-search-wrapper">' +
                '<input type="text" name="sign_address" id="sign-address" class="layui-input" placeholder="🔍 搜索活动地址..." style="width:100%;height:36px;border-radius:6px;padding-right:40px;" autocomplete="off">' +
                '<i class="fas fa-search location-search-icon" style="right:12px;top:10px;"></i>' +
                '<div id="location-suggestions" class="location-suggestions" style="display:none;"></div>' +
                '</div>' +
                '</div>' +
                '<div class="form-group">' +
                '<div class="map-container">' +
                '<div id="location-map" style="width:100%;height:280px;background:#f7f9fc;display:flex;align-items:center;justify-content:center;color:#999;">' +
                '<div style="text-align:center;"><i class="fas fa-map" style="font-size:32px;margin-bottom:10px;color:#1890ff;"></i><br><span>地图加载中...</span></div>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '<div class="form-row">' +
                '<div class="form-group">' +
                '<div class="form-label">经度</div>' +
                '<input type="text" name="sign_longitude" id="sign-longitude" class="layui-input" placeholder="自动获取" style="width:100%;height:36px;border-radius:6px;">' +
                '</div>' +
                '<div class="form-group">' +
                '<div class="form-label">纬度</div>' +
                '<input type="text" name="sign_latitude" id="sign-latitude" class="layui-input" placeholder="自动获取" style="width:100%;height:36px;border-radius:6px;">' +
                '</div>' +
                '</div>' +
                '<div style="margin-top:20px;">' +
                '<button type="button" class="btn btn-default" id="btn-get-location" style="width:100%;height:40px;border-radius:6px;border:1px solid #d9d9d9;background:#fafafa;">' +
                '<i class="fas fa-crosshairs"></i> 获取当前位置' +
                '</button>' +
                '</div>' +
                '<div id="location-preview" class="location-preview" style="display:none;margin-top:16px;padding:16px;background:#f7f9fc;border-radius:8px;">' +
                '<div class="form-label" style="margin-bottom:8px;">位置信息</div>' +
                '<div class="form-info" id="location-coords-display" style="margin:0;">坐标: 未设置</div>' +
                '<div class="form-info" id="location-radius-display" style="margin-top:4px;">签到范围: 1000米</div>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>' +
                // === 底部操作栏 ===
                '<div class="action-bar">' +
                '<div class="action-content">' +
                '<div class="action-info">' +
                '<i class="fas fa-shield-alt"></i>配置已自动保存草稿' +
                '</div>' +
                '<button type="button" class="save-button" onclick="SignPage._saveConfig()">' +
                '<i class="fas fa-cloud-upload-alt"></i>保存所有设置' +
                '</button>' +
                '</div>' +
                '</div>';

            Layout.setContent(html);
            
            // 渲染Layui表单
            layui.form.render(null, 'signConfig');
            
            // 监听表单switch状态变化 - 实时响应开关点击
            layui.form.on('switch(requirePhone)', function(data){
                var phoneVerifyRow = document.getElementById('phone-verify-row');
                var phoneVerifyTip = document.getElementById('phone-verify-tip');
                if (data.elem.checked) {
                    if (phoneVerifyRow) phoneVerifyRow.style.display = 'block';
                    if (phoneVerifyTip) phoneVerifyTip.style.display = 'block';
                } else {
                    if (phoneVerifyRow) phoneVerifyRow.style.display = 'none';
                    if (phoneVerifyTip) phoneVerifyTip.style.display = 'none';
                }
                // 同时关闭短信验证码
                var phoneVerifySwitch = document.querySelector('[name="require_phone_verify"]');
                if (!data.elem.checked && phoneVerifySwitch) {
                    phoneVerifySwitch.checked = false;
                    layui.form.render('checkbox', 'signConfig');
                }
            });
            
            layui.form.on('switch(avatarSource)', function(data){
                var avatarPhotoTip = document.getElementById('avatar-photo-tip');
                var avatarSourcePhoto = document.getElementById('avatar-source-photo');
                if (data.elem.checked && data.elem.id === 'avatar-source-photo') {
                    if (avatarPhotoTip) avatarPhotoTip.style.display = 'block';
                } else if (avatarPhotoTip) {
                    avatarPhotoTip.style.display = 'none';
                }
            });
            
            // 监听员工号设置开关
            layui.form.on('switch(showEmployeeNo)', function(data){
                var employeeNoRequireRow = document.getElementById('employee-no-require-row');
                if (employeeNoRequireRow) {
                    if (data.elem.checked) {
                        employeeNoRequireRow.style.display = 'block';
                    } else {
                        employeeNoRequireRow.style.display = 'none';
                        // 如果关闭员工号开关，则关闭必填选项
                        var requireEmployeeNo = document.querySelector('[name="require_employee_no"]');
                        if (requireEmployeeNo) {
                            requireEmployeeNo.checked = false;
                            layui.form.render('checkbox', 'signConfig');
                        }
                    }
                }
            });
            
            // 监听上传照片开关
            layui.form.on('switch(showPhoto)', function(data){
                var photoRequireRow = document.getElementById('photo-require-row');
                if (photoRequireRow) {
                    if (data.elem.checked) {
                        photoRequireRow.style.display = 'block';
                    } else {
                        photoRequireRow.style.display = 'none';
                        // 如果关闭上传照片开关，则关闭必填选项
                        var requirePhoto = document.querySelector('[name="require_photo"]');
                        if (requirePhoto) {
                            requirePhoto.checked = false;
                            layui.form.render('checkbox', 'signConfig');
                        }
                    }
                }
            });
            
            // 监听照片必填开关 - 用于切换"显示照片"选项状态
            layui.form.on('switch(requirePhoto)', function(data){
                var avatarSourcePhoto = document.getElementById('avatar-source-photo');
                if (data.elem.checked && avatarSourcePhoto) {
                    // 如果开启照片必填，则启用显示照片选项
                    avatarSourcePhoto.disabled = false;
                } else if (avatarSourcePhoto) {
                    // 如果关闭照片必填，可能需要检查显示照片是否被选中
                    var avatarPhotoTip = document.getElementById('avatar-photo-tip');
                    if (avatarSourcePhoto.checked) {
                        // 如果"显示照片"被选中，但照片必填关闭，可能需要提示
                        // 这里可以根据需求决定是否禁用
                    }
                }
            });
            
            // 监听自定义字段开关
            layui.form.on('switch(showCustomFields)', function(data){
                var customFieldsPanel = document.getElementById('custom-fields-panel');
                if (customFieldsPanel) {
                    if (data.elem.checked) {
                        customFieldsPanel.style.display = 'block';
                    } else {
                        // 关闭时检查是否有自定义字段数据
                        var hasCustomFields = false;
                        if (typeof SignPage._getCustomFieldsData === 'function') {
                            var customFields = SignPage._getCustomFieldsData();
                            hasCustomFields = customFields.length > 0;
                        }
                        
                        if (hasCustomFields) {
                            // 有自定义字段数据，提示用户
                            layui.layer.confirm('关闭自定义字段会隐藏现有字段，但不会删除数据。保存后字段将不生效。是否继续？', {
                                btn: ['继续', '取消'],
                                icon: 3
                            }, function(index) {
                                customFieldsPanel.style.display = 'none';
                                layui.layer.close(index);
                            }, function() {
                                // 取消操作，恢复开关状态
                                data.elem.checked = true;
                                layui.form.render('checkbox', 'signConfig');
                            });
                        } else {
                            // 没有自定义字段数据，直接隐藏
                            customFieldsPanel.style.display = 'none';
                        }
                    }
                }
            });
            
            // 监听地点限定开关
            layui.form.on('switch(signLocationSwitch)', function(data){
                var locationConfigPanel = document.getElementById('location-config-panel');
                if (locationConfigPanel) {
                    if (data.elem.checked) {
                        locationConfigPanel.style.display = 'block';
                    } else {
                        locationConfigPanel.style.display = 'none';
                    }
                }
            });
            
            // 监听签到范围选择变化（更新预览）
            layui.form.on('select(signRadius)', function(data){
                // 触发预览更新（如果有预览显示功能）
                updateLocationPreview();
            });
            
            // 加载时间选择器
            layui.laydate.render({
                elem: '#sign-start-time',
                type: 'datetime',
                format: 'yyyy-MM-dd HH:mm:ss'
            });
            layui.laydate.render({
                elem: '#sign-end-time',
                type: 'datetime',
                format: 'yyyy-MM-dd HH:mm:ss'
            });
            
            // 初始化地图功能
            setTimeout(() => {
                this._initMap();
            }, 100);
            
            // 初始检查电话复选框状态
            setTimeout(() => {
                var requirePhoneCheckbox = document.querySelector('[name="require_phone"]');
                var phoneVerifyRow = document.getElementById('phone-verify-row');
                var phoneVerifyTip = document.getElementById('phone-verify-tip');
                
                if (requirePhoneCheckbox) {
                    if (requirePhoneCheckbox.checked) {
                        if (phoneVerifyRow) phoneVerifyRow.style.display = 'block';
                        if (phoneVerifyTip) phoneVerifyTip.style.display = 'block';
                    } else {
                        if (phoneVerifyRow) phoneVerifyRow.style.display = 'none';
                        if (phoneVerifyTip) phoneVerifyTip.style.display = 'none';
                        // 如果电话未开启，强制关闭短信验证码
                        var phoneVerifySwitch = document.querySelector('[name="require_phone_verify"]');
                        if (phoneVerifySwitch) {
                            phoneVerifySwitch.checked = false;
                            layui.form.render('checkbox', 'signConfig');
                        }
                    }
                }
            }, 200);
            
            // 加载配置数据
            this._loadSignConfig(actId);
        },
        
        // ========== 加载签到配置 ==========
        _loadSignConfig: function(actId) {
            if (!actId) return;
            
            layui.layer.load(2);
            
            Api.getSignConfig(actId).then(function(res) {
                layui.layer.closeAll('loading');
                var data = res.data || {};
                var config = data.config || {};
                
                // 填充表单数据
                if (config.enabled !== undefined) {
                    document.querySelector('[name="enabled"]').checked = !!config.enabled;
                    layui.form.render('checkbox', 'signConfig');
                }
                
                if (config.start_time) document.getElementById('sign-start-time').value = config.start_time;
                if (config.end_time) document.getElementById('sign-end-time').value = config.end_time;
                
                // 必填信息复选框
                if (config.require_name) document.querySelector('[name="require_name"]').checked = true;
                if (config.require_phone) {
                    document.querySelector('[name="require_phone"]').checked = true;
                    var phoneVerifyRow = document.getElementById('phone-verify-row');
                    var phoneVerifyTip = document.getElementById('phone-verify-tip');
                    if (phoneVerifyRow) phoneVerifyRow.style.display = 'block';
                    if (phoneVerifyTip) phoneVerifyTip.style.display = 'block';
                }
                if (config.require_company) document.querySelector('[name="require_company"]').checked = true;
                if (config.require_position) document.querySelector('[name="require_position"]').checked = true;
                
                // 短信验证码开关 - 必须依赖电话开关
                var requirePhoneChecked = config.require_phone;
                if (config.require_phone_verify && requirePhoneChecked) {
                    // 只有在电话开启的情况下才允许短信验证码开启
                    document.querySelector('[name="require_phone_verify"]').checked = true;
                } else {
                    // 如果电话未开启，强制关闭短信验证码
                    document.querySelector('[name="require_phone_verify"]').checked = false;
                }
                
                // 头像来源
                var avatarSource = config.use_wx_avatar !== undefined ? config.use_wx_avatar : config.avatar_source;
                if (avatarSource !== undefined && avatarSource !== null) {
                    var radioEls = document.querySelectorAll('[name="avatar_source"]');
                    for (var i = 0; i < radioEls.length; i++) {
                        if (radioEls[i].value == avatarSource) radioEls[i].checked = true;
                    }
                    if (avatarSource == 0) {
                        var avatarPhotoTip = document.getElementById('avatar-photo-tip');
                        if (avatarPhotoTip) avatarPhotoTip.style.display = 'block';
                    }
                }
                
                // 嘉宾显示方式
                var showStyle = config.sign_show_style || config.show_style;
                if (showStyle) {
                    var styleRadios = document.querySelectorAll('[name="show_style"]');
                    for (var i = 0; i < styleRadios.length; i++) {
                        if (styleRadios[i].value == showStyle) styleRadios[i].checked = true;
                    }
                }
                
                // 员工号设置
                if (config.show_employee_no !== undefined) {
                    document.querySelector('[name="show_employee_no"]').checked = !!config.show_employee_no;
                    if (config.show_employee_no) {
                        var employeeNoRequireRow = document.getElementById('employee-no-require-row');
                        if (employeeNoRequireRow) employeeNoRequireRow.style.display = 'block';
                    }
                }
                if (config.require_employee_no) {
                    document.querySelector('[name="require_employee_no"]').checked = true;
                }
                
                // 上传照片设置
                if (config.show_photo !== undefined) {
                    document.querySelector('[name="show_photo"]').checked = !!config.show_photo;
                    if (config.show_photo) {
                        var photoRequireRow = document.getElementById('photo-require-row');
                        if (photoRequireRow) photoRequireRow.style.display = 'block';
                    }
                }
                if (config.require_photo) {
                    document.querySelector('[name="require_photo"]').checked = true;
                }
                
                // 自定义字段设置
                if (config.show_custom_fields !== undefined) {
                    document.querySelector('[name="show_custom_fields"]').checked = !!config.show_custom_fields;
                    if (config.show_custom_fields) {
                        var customFieldsPanel = document.getElementById('custom-fields-panel');
                        if (customFieldsPanel) customFieldsPanel.style.display = 'block';
                        
                        // 加载自定义字段数据
                        if (config.sign_custom_fields && Array.isArray(config.sign_custom_fields) && config.sign_custom_fields.length > 0) {
                            if (typeof SignPage._loadCustomFields === 'function') {
                                SignPage._loadCustomFields(config.sign_custom_fields);
                            }
                        }
                    }
                }
                
                // 地点限定
                if (config.sign_location_enabled !== undefined) {
                    document.querySelector('[name="sign_location_enabled"]').checked = !!config.sign_location_enabled;
                    if (config.sign_location_enabled) {
                        var locationConfigPanel = document.getElementById('location-config-panel');
                        if (locationConfigPanel) locationConfigPanel.style.display = 'block';
                    }
                }
                
                if (config.sign_radius) document.querySelector('[name="sign_radius"]').value = config.sign_radius;
                if (config.sign_address) document.getElementById('sign-address').value = config.sign_address;
                if (config.sign_longitude) document.getElementById('sign-longitude').value = config.sign_longitude;
                if (config.sign_latitude) document.getElementById('sign-latitude').value = config.sign_latitude;
                
                // 重新渲染表单
                layui.form.render(null, 'signConfig');
                
                // 初始化地点预览
                if (typeof SignPage.updateLocationPreview === 'function') {
                    SignPage.updateLocationPreview();
                }
                
                // 为地址和坐标输入框添加实时更新事件
                setTimeout(function() {
                    var signAddress = document.getElementById('sign-address');
                    var signLongitude = document.getElementById('sign-longitude');
                    var signLatitude = document.getElementById('sign-latitude');
                    
                    if (signAddress) {
                        signAddress.addEventListener('input', function() {
                            if (typeof SignPage.updateLocationPreview === 'function') {
                                SignPage.updateLocationPreview();
                            }
                        });
                    }
                    
                    if (signLongitude) {
                        signLongitude.addEventListener('input', function() {
                            if (typeof SignPage.updateLocationPreview === 'function') {
                                SignPage.updateLocationPreview();
                            }
                        });
                    }
                    
                    if (signLatitude) {
                        signLatitude.addEventListener('input', function() {
                            if (typeof SignPage.updateLocationPreview === 'function') {
                                SignPage.updateLocationPreview();
                            }
                        });
                    }
                    
                    // 为签到范围数字输入框添加实时验证和预览更新
                    var signRadius = document.querySelector('[name="sign_radius"]');
                    if (signRadius) {
                        signRadius.addEventListener('input', function() {
                            var value = parseInt(this.value) || 1000;
                            if (value < 100) value = 100;
                            if (value > 10000) value = 10000;
                            this.value = value;
                            
                            if (typeof SignPage.updateLocationPreview === 'function') {
                                SignPage.updateLocationPreview();
                            }
                        });
                    }
                    
                    // 为时间输入框添加实时格式化
                    var startTime = document.getElementById('sign-start-time');
                    var endTime = document.getElementById('sign-end-time');
                    
                    if (startTime) {
                        startTime.addEventListener('focus', function() {
                            if (!this.value) {
                                var now = new Date();
                                var year = now.getFullYear();
                                var month = String(now.getMonth() + 1).padStart(2, '0');
                                var day = String(now.getDate()).padStart(2, '0');
                                this.value = year + '-' + month + '-' + day + ' 09:00:00';
                            }
                        });
                    }
                    
                    if (endTime) {
                        endTime.addEventListener('focus', function() {
                            if (!this.value) {
                                var now = new Date();
                                var year = now.getFullYear();
                                var month = String(now.getMonth() + 1).padStart(2, '0');
                                var day = String(now.getDate()).padStart(2, '0');
                                this.value = year + '-' + month + '-' + day + ' 18:00:00';
                            }
                        });
                    }
                }, 300);
                
            }).catch(function(err) {
                layui.layer.closeAll('loading');
                console.error('加载签到配置失败:', err);
            });
        },
        
        // ========== 更新地点预览 ==========
        updateLocationPreview: function() {
            var radius = document.querySelector('[name="sign_radius"]').value || '1000';
            var address = document.getElementById('sign-address').value || '未设置地址';
            var longitude = document.getElementById('sign-longitude').value || '0';
            var latitude = document.getElementById('sign-latitude').value || '0';
            
            var locationCoordsDisplay = document.getElementById('location-coords-display');
            var locationRadiusDisplay = document.getElementById('location-radius-display');
            
            if (locationCoordsDisplay) {
                if (longitude !== '0' && latitude !== '0') {
                    locationCoordsDisplay.textContent = '坐标: ' + longitude + ', ' + latitude;
                } else {
                    locationCoordsDisplay.textContent = '坐标: 未设置';
                }
            }
            
            if (locationRadiusDisplay) {
                locationRadiusDisplay.textContent = '签到范围: ' + radius + '米';
            }
            
            var locationPreview = document.getElementById('location-preview');
            if (locationPreview && (longitude !== '0' || address !== '未设置地址')) {
                locationPreview.style.display = 'block';
            } else if (locationPreview) {
                locationPreview.style.display = 'none';
            }
        },
        
        // ========== 保存签到配置 ==========
        _saveConfig: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) {
                layui.layer.msg('请先选择活动', { icon: 2 });
                return;
            }
            
            var form = document.getElementById('sign-config-form');
            if (!form) return;
            
// 收集表单数据
        var formData = {
            enabled: document.querySelector('[name="enabled"]').checked ? 1 : 0,
            start_time: document.getElementById('sign-start-time').value || '',
            end_time: document.getElementById('sign-end-time').value || '',
            require_name: document.querySelector('[name="require_name"]').checked ? 1 : 0,
            require_phone: document.querySelector('[name="require_phone"]').checked ? 1 : 0,
            require_company: document.querySelector('[name="require_company"]').checked ? 1 : 0,
            require_position: document.querySelector('[name="require_position"]').checked ? 1 : 0,
            require_phone_verify: document.querySelector('[name="require_phone_verify"]') && document.querySelector('[name="require_phone_verify"]').checked ? 1 : 0,
            use_wx_avatar: document.querySelector('[name="avatar_source"]:checked') ? document.querySelector('[name="avatar_source"]:checked').value : 1,
            show_style: document.querySelector('[name="show_style"]:checked') ? document.querySelector('[name="show_style"]:checked').value : 1,
            show_employee_no: document.querySelector('[name="show_employee_no"]').checked ? 1 : 0,
            require_employee_no: document.querySelector('[name="require_employee_no"]') && document.querySelector('[name="require_employee_no"]').checked ? 1 : 0,
            show_photo: document.querySelector('[name="show_photo"]').checked ? 1 : 0,
            require_photo: document.querySelector('[name="require_photo"]') && document.querySelector('[name="require_photo"]').checked ? 1 : 0,
            show_custom_fields: document.querySelector('[name="show_custom_fields"]').checked ? 1 : 0,
            sign_location_enabled: document.querySelector('[name="sign_location_enabled"]').checked ? 1 : 0,
            sign_radius: document.querySelector('[name="sign_radius"]').value || '1000',
            sign_address: document.getElementById('sign-address').value || '',
            sign_longitude: document.getElementById('sign-longitude').value || '0',
            sign_latitude: document.getElementById('sign-latitude').value || '0',
            
            // 默认的大屏配置字段（前端表单中可能没有这些字段）
            sign_match_mode: 1,
            sign_verify_mode: 1,
            sign_bg_image: '',
            sign_music: ''
        };
            
            // 获取自定义字段数据（如果开启了自定义字段）
            if (formData.show_custom_fields && typeof SignPage._getCustomFieldsData === 'function') {
                var customFields = SignPage._getCustomFieldsData();
                if (customFields.length > 0) {
                    formData.sign_custom_fields = customFields;
                }
            }
            
            layui.layer.load(2);
            
            Api.updateSignConfig(actId, formData).then(function(res) {
                layui.layer.closeAll('loading');
                if (res.code === 0) {
                    layui.layer.msg('签到设置已保存', { icon: 1 });
                } else {
                    layui.layer.msg(res.msg || '保存失败', { icon: 2 });
                }
            }).catch(function(err) {
                layui.layer.closeAll('loading');
                layui.layer.msg(err.msg || '保存失败', { icon: 2 });
            });
        },

        // ========== 签到名单 ==========
        renderList: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<style>' +
                '.tab-container { display: flex; border-bottom: 1px solid #e8e8e8; margin-bottom: 20px; }' +
                '.tab-item { padding: 10px 20px; cursor: pointer; font-size: 14px; color: #666; border-bottom: 2px solid transparent; transition: all 0.3s ease; }' +
                '.tab-item:hover { color: #1890ff; }' +
                '.tab-item.active { color: #1890ff; border-bottom-color: #1890ff; font-weight: 500; }' +
                '.content-card { background: #fff; border-radius: 4px; box-shadow: 0 1px 4px rgba(0,0,0,0.1); padding: 20px; }' +
                '.toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }' +
                '.toolbar-left { display: flex; align-items: center; gap: 10px; }' +
                '.toolbar-right { display: flex; align-items: center; gap: 10px; }' +
                '</style>' +
                '<div class="content-card">' +
                '<div class="card-title" style="margin-bottom: 0;">' +
                '<div class="tab-container">' +
                '<div class="tab-item active" onclick="SignPage._switchTab(\'sign-list\')">签到名单</div>' +
                '<div class="tab-item" onclick="SignPage._switchTab(\'whitelist\')">白名单</div>' +
                '</div>' +
                '</div>' +
                '<div class="tab-content" id="sign-list-content">' +
                '<div class="toolbar"><div class="toolbar-left">' +
                '<input type="text" id="sign-search" class="layui-input" style="width:220px;height:36px;" placeholder="搜索昵称/姓名/手机号">' +
                '<button class="btn btn-default" onclick="SignPage._searchList()"><i class="fas fa-search"></i> 搜索</button>' +
                '</div><div class="toolbar-right">' +
                '<button class="btn btn-success" onclick="SignPage._importList()"><i class="fas fa-upload"></i> 导入</button>' +
                '<button class="btn btn-primary" onclick="SignPage._exportList()"><i class="fas fa-download"></i> 导出</button>' +
                '<button class="btn btn-info" onclick="SignPage._batchManagePermissions()"><i class="fas fa-user-lock"></i> 权限管理</button>' +
                '<button class="btn btn-danger" onclick="SignPage._clearList()"><i class="fas fa-trash"></i> 清空</button>' +
                '</div></div>' +
                '<table id="sign-table" lay-filter="signTable"></table>' +
                '<div id="sign-pager" style="padding:10px 15px;"></div>' +
                '</div>' +
                '<div class="tab-content" id="whitelist-content" style="display:none;">' +
                '<div class="toolbar"><div class="toolbar-left">' +
                '<input type="text" id="whitelist-search" class="layui-input" style="width:220px;height:36px;" placeholder="搜索姓名/手机号">' +
                '<button class="btn btn-default" onclick="SignPage._searchWhitelist()"><i class="fas fa-search"></i> 搜索</button>' +
                '</div><div class="toolbar-right">' +
                '<button class="btn btn-success" onclick="SignPage._addWhitelistItem()"><i class="fas fa-plus"></i> 添加</button>' +
                '<button class="btn btn-danger" onclick="SignPage._clearWhitelist()"><i class="fas fa-trash"></i> 清空</button>' +
                '</div></div>' +
                '<table id="whitelist-table" lay-filter="whitelistTable"></table>' +
                '<div id="whitelist-pager" style="padding:10px 15px;"></div>' +
                '</div>' +
                '</div>';

            Layout.setContent(html);
            this._loadSignTable(1);
        },

        _loadSignTable: function(page) {
            var actId = App.getCurrentActivityId();
            if (!actId) return;

            var searchKeyword = document.getElementById('sign-search').value;
            var limit = 20;
            var offset = (page - 1) * limit;

            // 显示加载状态
            layui.layer.load(2);

            // 调用后端API获取数据
            var xhr = new XMLHttpRequest();
            var url = '/api/hd/sign/' + actId + '/list?page=' + page + '&limit=' + limit;
            if (searchKeyword) {
                url += '&keyword=' + encodeURIComponent(searchKeyword);
            }

            xhr.open('GET', url, true);
            xhr.setRequestHeader('Hd-Token', Api.getToken() || '');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    layui.layer.closeAll('loading');
                    if (xhr.status === 200) {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.code === 0) {
                                var data = res.data.list || [];
                                var total = res.data.total || 0;

                                // 渲染表格
                                layui.table.render({
                                    elem: '#sign-table',
                                    data: data,
                                    cols: [[
                                        {type: 'checkbox', fixed: 'left'},
                                        {field: 'id', title: 'ID', width: 80, fixed: 'left'},
                                        {field: 'nickname', title: '昵称', width: 120},
                                        {field: 'signname', title: '姓名', width: 100},
                                        {field: 'phone', title: '手机号', width: 150},
                                        {field: 'company', title: '公司', width: 150},
                                        {field: 'position', title: '职位', width: 100},
                                        {field: 'employee_no', title: '员工号', width: 100},
                                        {field: 'create_time', title: '签到时间', width: 180},
                                        {title: '权限状态', width: 140, templet: function(d) {
                                            var roles = [];
                                            if (d.is_admin == 1) roles.push('<span class="badge badge-success" style="margin-right:3px;">管理员</span>');
                                            if (d.is_verifier == 1) roles.push('<span class="badge badge-info" style="margin-right:3px;">核销员</span>');
                                            return roles.join(' ') || '<span class="text-muted">无权限</span>';
                                        }},
                                        {title: '权限管理', width: 220, fixed: 'right', templet: function(d) {
                                            var buttons = '';
                                            buttons += '<button class="btn btn-xs ' + (d.is_admin == 1 ? 'btn-success' : 'btn-outline-secondary') + '" onclick="SignPage._toggleAdmin(' + d.id + ', ' + (d.is_admin == 1 ? 0 : 1) + ')" style="margin-right:3px;">';
                                            buttons += (d.is_admin == 1 ? '<i class="fas fa-user-times"></i>' : '<i class="fas fa-user-tie"></i>') + ' 管理员</button>';
                                            
                                            buttons += '<button class="btn btn-xs ' + (d.is_verifier == 1 ? 'btn-info' : 'btn-outline-secondary') + '" onclick="SignPage._toggleVerifier(' + d.id + ', ' + (d.is_verifier == 1 ? 0 : 1) + ')" style="margin-right:3px;">';
                                            buttons += (d.is_verifier == 1 ? '<i class="fas fa-user-times"></i>' : '<i class="fas fa-user-check"></i>') + ' 核销员</button>';
                                            
                                            buttons += '<button class="btn btn-default btn-xs" onclick="SignPage._addToWhitelist(' + d.id + ', \'' + (d.signname || d.nickname) + '\', \'' + d.phone + '\')"><i class="fas fa-user-slash"></i> 加入白名单</button>';
                                            return buttons;
                                        }}
                                    ]],
                                    page: false,
                                    loading: false
                                });

                                // 渲染分页
                                layui.laypage.render({
                                    elem: 'sign-pager',
                                    count: total,
                                    limit: limit,
                                    curr: page,
                                    jump: function(obj, first) {
                                        if (!first) {
                                            SignPage._loadSignTable(obj.curr);
                                        }
                                    }
                                });
                            } else {
                                layui.layer.msg(res.msg || '获取数据失败', {icon: 2});
                            }
                        } catch (e) {
                            layui.layer.msg('数据解析失败', {icon: 2});
                        }
                    } else {
                        layui.layer.msg('请求失败', {icon: 2});
                    }
                }
            };
            xhr.send();
        },

        _searchList: function() {
            this._loadSignTable(1);
        },

        _importList: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) {
                layui.layer.msg('请先选择活动', { icon: 2 });
                return;
            }

            SignPage._importActId = actId;
            
            // 导入弹窗HTML
            var importHtml = '<style>' +
                '.import-modal-content { padding: 0 20px; }' +
                '.import-steps { display: flex; justify-content: space-between; margin-bottom: 30px; }' +
                '.step-item { flex: 1; text-align: center; color: #999; position: relative; }' +
                '.step-item.active { color: #1890ff; }' +
                '.step-item.active .step-number { background-color: #1890ff; color: white; border-color: #1890ff; }' +
                '.step-number { width: 32px; height: 32px; line-height: 32px; border-radius: 50%; border: 2px solid #e8e8e8; background-color: #f5f5f5; margin: 0 auto 10px; font-weight: bold; }' +
                '.step-title { font-weight: 500; margin-bottom: 5px; font-size: 14px; }' +
                '.step-desc { font-size: 12px; color: #999; }' +
                '.import-content { min-height: 200px; }' +
                '.step-content { display: none; }' +
                '.step-content.active { display: block; }' +
                '.template-info { border: 1px solid #f0f0f0; border-radius: 4px; padding: 20px; background-color: #fafafa; }' +
                '.template-info h4 { margin-top: 0; margin-bottom: 15px; color: #333; }' +
                '.template-info ul { margin-bottom: 20px; padding-left: 20px; }' +
                '.template-info li { margin-bottom: 5px; color: #666; }' +
                '.template-actions { display: flex; justify-content: space-between; }' +
                '.upload-area { text-align: center; padding: 30px; border: 2px dashed #e8e8e8; border-radius: 4px; background-color: #fafafa; }' +
                '.upload-btn-area { margin-bottom: 15px; }' +
                '.file-info { background-color: #f0f8ff; border: 1px solid #d9ecff; border-radius: 4px; padding: 12px; margin-bottom: 15px; text-align: left; color: #1890ff; }' +
                '.upload-tip { color: #999; font-size: 12px; margin-bottom: 20px; }' +
                '.step-actions { display: flex; justify-content: space-between; }' +
                '.import-progress { text-align: center; }' +
                '.progress-header { font-weight: 500; margin-bottom: 15px; color: #333; }' +
                '.progress-bar-container { height: 8px; background-color: #f5f5f5; border-radius: 4px; overflow: hidden; margin: 20px 0; }' +
                '.progress-bar { height: 100%; background-color: #1890ff; width: 30%; border-radius: 4px; transition: width 0.3s; }' +
                '.progress-info { color: #666; margin-bottom: 20px; font-size: 14px; }' +
                '.import-result { border: 1px solid #f0f0f0; border-radius: 4px; padding: 20px; background-color: #fafafa; }' +
                '.result-header { font-weight: 500; margin-bottom: 15px; color: #333; border-bottom: 1px solid #e8e8e8; padding-bottom: 10px; }' +
                '.result-item { display: flex; justify-content: space-between; margin-bottom: 10px; }' +
                '.result-label { color: #666; }' +
                '.result-value { font-weight: bold; color: #1890ff; }' +
                '.result-actions { text-align: right; }' +
                '</style>' +
                '<div class="import-modal-content">' +
                '<div class="import-steps">' +
                '<div class="step-item active">' +
                '<div class="step-number">1</div>' +
                '<div class="step-title">下载导入模板</div>' +
                '<div class="step-desc">根据模板格式填写签到数据</div>' +
                '</div>' +
                '<div class="step-item">' +
                '<div class="step-number">2</div>' +
                '<div class="step-title">上传Excel文件</div>' +
                '<div class="step-desc">选择填写好的Excel文件</div>' +
                '</div>' +
                '<div class="step-item">' +
                '<div class="step-number">3</div>' +
                '<div class="step-title">导入数据</div>' +
                '<div class="step-desc">系统处理导入的签到数据</div>' +
                '</div>' +
                '</div>' +
                '<div class="import-content">' +
                '<div class="step-content active" id="step1-content">' +
                '<div class="template-info">' +
                '<h4>模板说明</h4>' +
                '<ul>' +
                '<li>请按照模板格式填写签到数据</li>' +
                '<li>支持导入的字段：姓名、手机号、公司、职位、员工号</li>' +
                '<li>手机号为必填字段，用于唯一标识用户</li>' +
                '<li>导入数据会自动去重，相同手机号的记录会被覆盖</li>' +
                '</ul>' +
                '<div class="template-actions">' +
                '<button class="btn btn-primary" onclick="SignPage._downloadTemplate()"><i class="fas fa-file-excel"></i> 下载Excel模板</button>' +
                '<button class="btn btn-default" onclick="SignPage._nextStep()">下一步</button>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '<div class="step-content" id="step2-content">' +
                '<div class="upload-area">' +
                '<input type="file" id="import-file" accept=".xlsx,.xls" style="display:none;" onchange="SignPage._handleImportFile(this)">' +
                '<div class="upload-btn-area">' +
                '<button type="button" class="btn btn-default" onclick="document.getElementById(\'import-file\').click()"><i class="fas fa-cloud-upload-alt"></i> 选择Excel文件</button>' +
                '</div>' +
                '<div id="file-preview" style="margin-top:15px;display:none;">' +
                '<div class="file-info"><i class="fas fa-file-excel"></i> <span id="file-name"></span></div>' +
                '</div>' +
                '<div class="upload-tip">支持 .xlsx 和 .xls 格式的Excel文件</div>' +
                '<div class="step-actions" style="margin-top:20px;">' +
                '<button class="btn btn-default" onclick="SignPage._prevStep()">上一步</button>' +
                '<button class="btn btn-primary" id="btn-start-import" disabled onclick="SignPage._startImport()">开始导入</button>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '<div class="step-content" id="step3-content">' +
                '<div class="import-progress">' +
                '<div class="progress-header">导入进度</div>' +
                '<div class="progress-bar-container">' +
                '<div class="progress-bar" id="import-progress-bar"></div>' +
                '</div>' +
                '<div class="progress-info" id="import-progress-info">准备导入...</div>' +
                '</div>' +
                '<div class="import-result" id="import-result" style="display:none;">' +
                '<div class="result-header">导入结果</div>' +
                '<div class="result-item"><span class="result-label">成功：</span><span class="result-value" id="success-count">0</span> 条</div>' +
                '<div class="result-item"><span class="result-label">失败：</span><span class="result-value" id="fail-count">0</span> 条</div>' +
                '<div class="result-actions" style="margin-top:15px;">' +
                '<button class="btn btn-default" onclick="SignPage._closeImportModal()">完成</button>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>' +
                '</div>';

            SignPage._importModal = layui.layer.open({
                type: 1,
                title: '导入签到名单',
                content: importHtml,
                area: ['700px', '500px'],
                btn: [],
                closeBtn: 1
            });

            // 绑定事件
            setTimeout(function() {
                var btnStartImport = document.querySelector('#btn-start-import');
                if (btnStartImport) btnStartImport.disabled = true;
            }, 100);
        },

        _downloadTemplate: function() {
            var actId = SignPage._importActId || App.getCurrentActivityId();
            if (!actId) {
                layui.layer.msg('请先选择活动', { icon: 2 });
                return;
            }
            // 下载模板
            window.open('/huodong/admin/assets/templates/sign-import-template.csv');
        },

        _nextStep: function() {
            var step1 = document.querySelector('#step1-content');
            var step2 = document.querySelector('#step2-content');
            var stepItem1 = document.querySelector('.step-item:nth-child(1)');
            var stepItem2 = document.querySelector('.step-item:nth-child(2)');

            if (step1 && step2 && stepItem1 && stepItem2) {
                step1.classList.remove('active');
                step2.classList.add('active');
                stepItem1.classList.remove('active');
                stepItem2.classList.add('active');
            }
        },

        _prevStep: function() {
            var step1 = document.querySelector('#step1-content');
            var step2 = document.querySelector('#step2-content');
            var stepItem1 = document.querySelector('.step-item:nth-child(1)');
            var stepItem2 = document.querySelector('.step-item:nth-child(2)');

            if (step1 && step2 && stepItem1 && stepItem2) {
                step2.classList.remove('active');
                step1.classList.add('active');
                stepItem2.classList.remove('active');
                stepItem1.classList.add('active');
            }
        },

        _handleImportFile: function(input) {
            var file = input.files[0];
            if (file) {
                SignPage._importFile = file;
                var fileName = document.querySelector('#file-name');
                var filePreview = document.querySelector('#file-preview');
                var btnStartImport = document.querySelector('#btn-start-import');

                if (fileName) fileName.textContent = file.name;
                if (filePreview) filePreview.style.display = 'block';
                if (btnStartImport) btnStartImport.disabled = false;
            }
        },

        _startImport: function() {
            var actId = SignPage._importActId || App.getCurrentActivityId();
            if (!actId) {
                layui.layer.msg('请先选择活动', { icon: 2 });
                return;
            }
            if (!SignPage._importFile) {
                layui.layer.msg('请选择要导入的Excel文件', { icon: 2 });
                return;
            }

            // 显示第三步
            var step2 = document.querySelector('#step2-content');
            var step3 = document.querySelector('#step3-content');
            var stepItem2 = document.querySelector('.step-item:nth-child(2)');
            var stepItem3 = document.querySelector('.step-item:nth-child(3)');

            if (step2 && step3 && stepItem2 && stepItem3) {
                step2.classList.remove('active');
                step3.classList.add('active');
                stepItem2.classList.remove('active');
                stepItem3.classList.add('active');
            }

            // 准备表单数据
            var formData = new FormData();
            formData.append('file', SignPage._importFile);

            // 显示进度
            var progressBar = document.querySelector('#import-progress-bar');
            var progressInfo = document.querySelector('#import-progress-info');
            
            if (progressInfo) progressInfo.textContent = '正在上传文件...';
            if (progressBar) progressBar.style.width = '30%';

            // 调用后端API进行导入
            var xhr = new XMLHttpRequest();
            
            // 使用统一的API路径配置
            var url = API_CONFIG.sign.import + '?activity_id=' + actId;
            
            console.log('导入API路径:', url, '活动ID:', actId);

            xhr.open('POST', url, true);
            xhr.setRequestHeader('Hd-Token', Api.getToken() || '');
            xhr.upload.onprogress = function(e) {
                if (e.lengthComputable) {
                    var percent = Math.round((e.loaded / e.total) * 100);
                    if (progressBar) progressBar.style.width = percent + '%';
                    if (progressInfo) progressInfo.textContent = '上传中... ' + percent + '%';
                }
            };

            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    if (progressBar) progressBar.style.width = '100%';
                    if (progressInfo) progressInfo.textContent = '导入完成';
                    
                    if (xhr.status === 200) {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            var importResult = document.querySelector('#import-result');
                            var successCount = document.querySelector('#success-count');
                            var failCount = document.querySelector('#fail-count');
                            
                            if (importResult) importResult.style.display = 'block';
                            if (successCount) successCount.textContent = res.data.success || 0;
                            if (failCount) failCount.textContent = res.data.fail || 0;
                            
                            if (res.code === 0) {
                                layui.layer.msg('导入成功，已关联活动ID', { icon: 1 });
                            } else {
                                layui.layer.msg(res.msg || '导入失败', { icon: 2 });
                            }
                        } catch (e) {
                            layui.layer.msg('数据解析失败', { icon: 2 });
                        }
                    } else {
                        layui.layer.msg('请求失败', { icon: 2 });
                    }
                }
            };

            xhr.send(formData);
        },

        _closeImportModal: function() {
            if (SignPage._importModal) {
                layui.layer.close(SignPage._importModal);
                SignPage._importModal = null;
            }
            // 重新加载签到名单，确保导入的数据显示在列表中
            if (SignPage._signActId) {
                SignPage._loadSignTable(1); // 重新加载第一页
                layui.layer.msg('导入成功，已更新签到名单', { icon: 1 });
            } else {
                // 如果没有活动ID，尝试获取当前活动ID并重新加载
                var actId = App.getCurrentActivityId();
                if (actId) {
                    SignPage._signActId = actId;
                    SignPage._loadSignTable(1);
                    layui.layer.msg('导入成功，已更新签到名单', { icon: 1 });
                }
            }
        },

        _exportList: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) {
                layui.layer.msg('请先选择活动', { icon: 2 });
                return;
            }

            // 使用新的导出API路径
            window.open('/api/hd/sign/export?activity_id=' + actId);
        },

        _clearList: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) {
                layui.layer.msg('请先选择活动', { icon: 2 });
                return;
            }

            layui.layer.confirm('确定要清空所有签到记录吗？', {
                btn: ['确定', '取消']
            }, function() {
                layui.layer.load(2);
                Api.clearSignList(actId).then(function() {
                    layui.layer.closeAll('loading');
                    layui.layer.msg('清空成功', { icon: 1 });
                    SignPage._loadSignTable(1);
                }).catch(function() {
                    layui.layer.closeAll('loading');
                    layui.layer.msg('清空失败', { icon: 2 });
                });
            });
        },

        // ========== 白名单管理 ==========
        _switchTab: function(tabName) {
            // 切换TAB样式
            var tabItems = document.querySelectorAll('.tab-item');
            tabItems.forEach(function(item) {
                item.classList.remove('active');
            });
            event.target.classList.add('active');

            // 切换内容
            var contents = document.querySelectorAll('.tab-content');
            contents.forEach(function(content) {
                content.style.display = 'none';
            });
            document.getElementById(tabName + '-content').style.display = 'block';

            // 加载对应内容
            if (tabName === 'sign-list') {
                this._loadSignTable(1);
            } else if (tabName === 'whitelist') {
                this._loadWhitelistTable(1);
            }
        },

        _loadWhitelistTable: function(page) {
            var actId = App.getCurrentActivityId();
            if (!actId) return;

            var searchKeyword = document.getElementById('whitelist-search').value;
            var limit = 20;

            // 显示加载状态
            layui.layer.load(2);

            // 调用新的ThinkPHP API获取数据
            var xhr = new XMLHttpRequest();
            var url = API_CONFIG.hdSign.whitelist(actId) + '?page=' + page + '&limit=' + limit;
            if (searchKeyword) {
                url += '&search=' + encodeURIComponent(searchKeyword);
            }

            xhr.open('GET', url, true);
            xhr.setRequestHeader('Hd-Token', Api.getToken() || '');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    layui.layer.closeAll('loading');
                    if (xhr.status === 200) {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.code === 0) {
                                var data = res.data.list || [];
                                var total = res.data.total || 0;

                                // 渲染表格
                                layui.table.render({
                                    elem: '#whitelist-table',
                                    data: data,
                                    cols: [[
                                        {type: 'checkbox', fixed: 'left'},
                                        {field: 'id', title: 'ID', width: 80, fixed: 'left'},
                                        {field: 'name', title: '姓名', width: 100},
                                        {field: 'phone', title: '手机号', width: 150},
                                        {field: 'company', title: '公司', width: 150},
                                        {field: 'position', title: '职位', width: 100},
                                        {field: 'added_time', title: '添加时间', width: 180},
                                        {title: '操作', width: 150, fixed: 'right', templet: function(d) {
                                            return '<button class="btn btn-default btn-xs" onclick="SignPage._editWhitelistItem(' + d.id + ')"><i class="fas fa-edit"></i> 编辑</button> ' +
                                                   '<button class="btn btn-danger btn-xs" onclick="SignPage._deleteWhitelistItem(' + d.id + ')"><i class="fas fa-trash"></i> 删除</button>';
                                        }}
                                    ]],
                                    page: false,
                                    loading: false
                                });

                                // 渲染分页
                                layui.laypage.render({
                                    elem: 'whitelist-pager',
                                    count: total,
                                    limit: limit,
                                    curr: page,
                                    jump: function(obj, first) {
                                        if (!first) {
                                            SignPage._loadWhitelistTable(obj.curr);
                                        }
                                    }
                                });
                            } else {
                                layui.layer.msg(res.msg || '获取数据失败', {icon: 2});
                            }
                        } catch (e) {
                            layui.layer.msg('数据解析失败', {icon: 2});
                        }
                    } else {
                        layui.layer.msg('请求失败', {icon: 2});
                    }
                }
            };
            xhr.send();
        },

        _searchWhitelist: function() {
            this._loadWhitelistTable(1);
        },

        _addToWhitelist: function(id, name, phone) {
            var actId = App.getCurrentActivityId();
            if (!actId) return;

            layui.layer.confirm('确定要将 ' + name + ' 添加到白名单吗？', {
                btn: ['确定', '取消']
            }, function() {
                // 显示加载状态
                layui.layer.load(2);

                // 调用新的ThinkPHP API添加白名单
                var xhr = new XMLHttpRequest();
                var url = API_CONFIG.hdSign.whitelist(actId);

                xhr.open('POST', url, true);
                xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                xhr.setRequestHeader('Hd-Token', Api.getToken() || '');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        layui.layer.closeAll('loading');
                        if (xhr.status === 200) {
                            try {
                                var res = JSON.parse(xhr.responseText);
                                if (res.code === 0) {
                                    layui.layer.msg('添加到白名单成功', {icon: 1});
                                } else {
                                    layui.layer.msg(res.msg || '添加失败', {icon: 2});
                                }
                            } catch (e) {
                                layui.layer.msg('数据解析失败', {icon: 2});
                            }
                        } else {
                            layui.layer.msg('请求失败', {icon: 2});
                        }
                    }
                };

                var params = 'name=' + encodeURIComponent(name) +
                            '&phone=' + encodeURIComponent(phone);
                xhr.send(params);
            });
        },

        _addWhitelistItem: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return;

            layui.layer.open({
                type: 1,
                title: '添加白名单',
                content: '<div style="padding:20px;">' +
                    '<form class="layui-form" id="whitelist-form">' +
                    '<div class="layui-form-item">' +
                    '<label class="layui-form-label">姓名</label>' +
                    '<div class="layui-input-block">' +
                    '<input type="text" name="name" class="layui-input" placeholder="请输入姓名" required>' +
                    '</div>' +
                    '</div>' +
                    '<div class="layui-form-item">' +
                    '<label class="layui-form-label">手机号</label>' +
                    '<div class="layui-input-block">' +
                    '<input type="text" name="phone" class="layui-input" placeholder="请输入手机号" required>' +
                    '</div>' +
                    '</div>' +
                    '<div class="layui-form-item">' +
                    '<label class="layui-form-label">公司</label>' +
                    '<div class="layui-input-block">' +
                    '<input type="text" name="company" class="layui-input" placeholder="请输入公司">' +
                    '</div>' +
                    '</div>' +
                    '<div class="layui-form-item">' +
                    '<label class="layui-form-label">职位</label>' +
                    '<div class="layui-input-block">' +
                    '<input type="text" name="position" class="layui-input" placeholder="请输入职位">' +
                    '</div>' +
                    '</div>' +
                    '</form>' +
                    '</div>',
                area: ['400px', 'auto'],
                btn: ['保存', '取消'],
                yes: function(index) {
                    var formData = layui.form.val('whitelist-form');
                    if (!formData.name || !formData.phone) {
                        layui.layer.msg('姓名和手机号不能为空', {icon: 2});
                        return;
                    }
                    
                    // 显示加载状态
                    layui.layer.load(2);
                    
                    // 调用新的ThinkPHP API添加白名单
                    var xhr = new XMLHttpRequest();
                    var url = API_CONFIG.hdSign.whitelist(actId);
                    
                    xhr.open('POST', url, true);
                    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                    xhr.setRequestHeader('Hd-Token', Api.getToken() || '');
                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4) {
                            layui.layer.closeAll('loading');
                            if (xhr.status === 200) {
                                try {
                                    var res = JSON.parse(xhr.responseText);
                                    if (res.code === 0) {
                                        layui.layer.msg('添加成功', {icon: 1});
                                        layui.layer.close(index);
                                        SignPage._loadWhitelistTable(1);
                                    } else {
                                        layui.layer.msg(res.msg || '添加失败', {icon: 2});
                                    }
                                } catch (e) {
                                    layui.layer.msg('数据解析失败', {icon: 2});
                                }
                            } else {
                                layui.layer.msg('请求失败', {icon: 2});
                            }
                        }
                    };
                    
                    var params = 'name=' + encodeURIComponent(formData.name) +
                                '&phone=' + encodeURIComponent(formData.phone) +
                                '&company=' + encodeURIComponent(formData.company) +
                                '&position=' + encodeURIComponent(formData.position);
                    xhr.send(params);
                }
            });
            layui.form.render();
        },

        _editWhitelistItem: function(id) {
            var actId = App.getCurrentActivityId();
            if (!actId) return;

            // 显示加载状态
            layui.layer.load(2);

            // 调用新的ThinkPHP API获取白名单详情
            var xhr = new XMLHttpRequest();
            var url = API_CONFIG.hdSign.whitelist(actId) + '?limit=1&id=' + id;

            xhr.open('GET', url, true);
            xhr.setRequestHeader('Hd-Token', Api.getToken() || '');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    layui.layer.closeAll('loading');
                    if (xhr.status === 200) {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.code === 0 && res.data && res.data.list && res.data.list.length > 0) {
                                var item = res.data.list[0];

                                layui.layer.open({
                                    type: 1,
                                    title: '编辑白名单',
                                    content: '<div style="padding:20px;">' +
                                        '<form class="layui-form" id="whitelist-edit-form">' +
                                        '<input type="hidden" name="id" value="' + item.id + '">' +
                                        '<div class="layui-form-item">' +
                                        '<label class="layui-form-label">姓名</label>' +
                                        '<div class="layui-input-block">' +
                                        '<input type="text" name="name" class="layui-input" value="' + (item.name || '') + '" required>' +
                                        '</div>' +
                                        '</div>' +
                                        '<div class="layui-form-item">' +
                                        '<label class="layui-form-label">手机号</label>' +
                                        '<div class="layui-input-block">' +
                                        '<input type="text" name="phone" class="layui-input" value="' + (item.phone || '') + '" required>' +
                                        '</div>' +
                                        '</div>' +
                                        '<div class="layui-form-item">' +
                                        '<label class="layui-form-label">公司</label>' +
                                        '<div class="layui-input-block">' +
                                        '<input type="text" name="company" class="layui-input" value="' + (item.company || '') + '">' +
                                        '</div>' +
                                        '</div>' +
                                        '<div class="layui-form-item">' +
                                        '<label class="layui-form-label">职位</label>' +
                                        '<div class="layui-input-block">' +
                                        '<input type="text" name="position" class="layui-input" value="' + (item.position || '') + '">' +
                                        '</div>' +
                                        '</div>' +
                                        '</form>' +
                                        '</div>',
                                    area: ['400px', 'auto'],
                                    btn: ['保存', '取消'],
                                    yes: function(index) {
                                        var formData = layui.form.val('whitelist-edit-form');
                                        if (!formData.name || !formData.phone) {
                                            layui.layer.msg('姓名和手机号不能为空', {icon: 2});
                                            return;
                                        }

                                        // 显示加载状态
                                        layui.layer.load(2);

                                        // 调用新的ThinkPHP API更新白名单
                                        var updateXhr = new XMLHttpRequest();
                                        var updateUrl = API_CONFIG.hdSign.whitelist(actId);

                                        updateXhr.open('POST', updateUrl, true);
                                        updateXhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
                                        updateXhr.setRequestHeader('Hd-Token', Api.getToken() || '');
                                        updateXhr.onreadystatechange = function() {
                                            if (updateXhr.readyState === 4) {
                                                layui.layer.closeAll('loading');
                                                if (updateXhr.status === 200) {
                                                    try {
                                                        var updateRes = JSON.parse(updateXhr.responseText);
                                                        if (updateRes.code === 0) {
                                                            layui.layer.msg('编辑成功', {icon: 1});
                                                            layui.layer.close(index);
                                                            SignPage._loadWhitelistTable(1);
                                                        } else {
                                                            layui.layer.msg(updateRes.msg || '编辑失败', {icon: 2});
                                                        }
                                                    } catch (e) {
                                                        layui.layer.msg('数据解析失败', {icon: 2});
                                                    }
                                                } else {
                                                    layui.layer.msg('请求失败', {icon: 2});
                                                }
                                            }
                                        };

                                        var params = 'id=' + formData.id +
                                                    '&name=' + encodeURIComponent(formData.name) +
                                                    '&phone=' + encodeURIComponent(formData.phone) +
                                                    '&company=' + encodeURIComponent(formData.company) +
                                                    '&position=' + encodeURIComponent(formData.position);
                                        updateXhr.send(params);
                                    }
                                });
                                layui.form.render();
                            } else {
                                layui.layer.msg('获取数据失败', {icon: 2});
                            }
                        } catch (e) {
                            layui.layer.msg('数据解析失败', {icon: 2});
                        }
                    } else {
                        layui.layer.msg('请求失败', {icon: 2});
                    }
                }
            };
            xhr.send();
        },

        _deleteWhitelistItem: function(id) {
            var actId = App.getCurrentActivityId();
            if (!actId) return;

            layui.layer.confirm('确定要删除这条记录吗？', {
                btn: ['确定', '取消']
            }, function() {
                // 显示加载状态
                layui.layer.load(2);

                // 调用新的ThinkPHP API删除白名单
                var xhr = new XMLHttpRequest();
                var url = API_CONFIG.hdSign.whitelistDelete(actId, id);

                xhr.open('DELETE', url, true);
                xhr.setRequestHeader('Content-Type', 'application/json');
                xhr.setRequestHeader('Hd-Token', Api.getToken() || '');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        layui.layer.closeAll('loading');
                        if (xhr.status === 200) {
                            try {
                                var res = JSON.parse(xhr.responseText);
                                if (res.code === 0) {
                                    layui.layer.msg('删除成功', {icon: 1});
                                    SignPage._loadWhitelistTable(1);
                                } else {
                                    layui.layer.msg(res.msg || '删除失败', {icon: 2});
                                }
                            } catch (e) {
                                layui.layer.msg('数据解析失败', {icon: 2});
                            }
                        } else {
                            layui.layer.msg('请求失败', {icon: 2});
                        }
                    }
                };

                xhr.send();
            });
        },

        _clearWhitelist: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return;

            layui.layer.confirm('确定要清空所有白名单记录吗？', {
                btn: ['确定', '取消']
            }, function() {
                // 显示加载状态
                layui.layer.load(2);

                // 调用新的ThinkPHP API清空白名单
                var xhr = new XMLHttpRequest();
                var url = API_CONFIG.hdSign.whitelistClear(actId);

                xhr.open('DELETE', url, true);
                xhr.setRequestHeader('Content-Type', 'application/json');
                xhr.setRequestHeader('Hd-Token', Api.getToken() || '');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        layui.layer.closeAll('loading');
                        if (xhr.status === 200) {
                            try {
                                var res = JSON.parse(xhr.responseText);
                                if (res.code === 0) {
                                    layui.layer.msg('清空成功', {icon: 1});
                                    SignPage._loadWhitelistTable(1);
                                } else {
                                    layui.layer.msg(res.msg || '清空失败', {icon: 2});
                                }
                            } catch (e) {
                                layui.layer.msg('数据解析失败', {icon: 2});
                            }
                        } else {
                            layui.layer.msg('请求失败', {icon: 2});
                        }
                    }
                };

                xhr.send();
            });
        },

        // ========== 手机签到页 ==========
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
                '<div class="layui-form-item" style="margin-top:20px;"><button type="button" class="btn btn-primary" id="btn-save-mobile-config" onclick="SignPage._saveMobileConfig()"><i class="fas fa-save"></i> 保存配置</button></div>' +
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
            
            // 渲染表单
            layui.form.render(null, 'signMobile');
            
            // 监听手机端开关状态变化 - 实时响应
            layui.form.on('switch(mobileHideAvatar)', function(data){
                var mpAvatarArea = document.getElementById('mp-avatar-area');
                if (mpAvatarArea) {
                    if (data.elem.checked) {
                        mpAvatarArea.style.display = 'none';
                    } else {
                        mpAvatarArea.style.display = 'block';
                    }
                }
            });
            
            layui.form.on('switch(mobileQuickMessage)', function(data){
                // 快捷留言开关 - 预览中可以添加相关提示
                if (typeof SignPage._updateMobilePreview === 'function') {
                    SignPage._updateMobilePreview();
                }
            });
            
            layui.form.on('switch(mobileForceWxAuth)', function(data){
                // 强制授权登录开关 - 预览中可以添加相关提示
                if (typeof SignPage._updateMobilePreview === 'function') {
                    SignPage._updateMobilePreview();
                }
            });
            
            // 绑定保存按钮事件，确保按钮存在
            setTimeout(function() {
                var saveBtn = document.getElementById('btn-save-mobile-config');
                if (saveBtn && !saveBtn.onclick) {
                    saveBtn.onclick = function() { SignPage._saveMobileConfig(); };
                }
            }, 100);
            
            // 加载数据
            this._loadMobileConfig(actId);
        },
        
        // ========== 加载手机配置 ==========
        _loadMobileConfig: function(actId) {
            if (!actId) return;
            
            layui.layer.load(2);
            
            // 使用HTTP请求获取手机配置，因为API.js中没有对应的API方法
            var xhr = new XMLHttpRequest();
            var url = '/api/hd/sign/' + actId + '/mobile';
            
            xhr.open('GET', url, true);
            xhr.setRequestHeader('Hd-Token', Api.getToken() || '');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    layui.layer.closeAll('loading');
                    if (xhr.status === 200) {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            var data = res.data || {};
                            
                            // 填充表单数据
                            if (data.mobile_hide_avatar !== undefined) {
                                var hideAvatar = document.querySelector('[name="mobile_hide_avatar"]');
                                if (hideAvatar) {
                                    hideAvatar.checked = !!data.mobile_hide_avatar;
                                    layui.form.render('checkbox', 'signMobile');
                                }
                            }
                            
                            if (data.mobile_quick_message !== undefined) {
                                var quickMsg = document.querySelector('[name="mobile_quick_message"]');
                                if (quickMsg) {
                                    quickMsg.checked = !!data.mobile_quick_message;
                                    layui.form.render('checkbox', 'signMobile');
                                }
                            }
                            
                            if (data.mobile_force_wx_auth !== undefined) {
                                var forceAuth = document.querySelector('[name="mobile_force_wx_auth"]');
                                if (forceAuth) {
                                    forceAuth.checked = !!data.mobile_force_wx_auth;
                                    layui.form.render('checkbox', 'signMobile');
                                }
                            }
                            
                            if (data.mobile_welcome_text) {
                                var welcomeText = document.getElementById('mobile-welcome-text');
                                if (welcomeText) welcomeText.value = data.mobile_welcome_text;
                            }
                            
                            if (data.mobile_btn_text) {
                                var btnText = document.getElementById('mobile-btn-text');
                                if (btnText) btnText.value = data.mobile_btn_text;
                            }
                            
                            // 更新预览
                            if (typeof SignPage._updateMobilePreview === 'function') {
                                SignPage._updateMobilePreview();
                            }
                            
                            // 更新签到URL
                            if (typeof SignPage._copySignUrl === 'function') {
                                setTimeout(function() {
                                    var signUrlInput = document.getElementById('mobile-sign-url-input');
                                    if (signUrlInput) {
                                        var actId = App.getCurrentActivityId();
                                        if (actId) {
                                            var url = window.location.origin + '/huodong/sign/mobile.php?act_id=' + actId;
                                            signUrlInput.value = url;
                                        }
                                    }
                                }, 100);
                            }
                            
                        } catch (e) {
                            console.error('解析手机配置失败:', e);
                        }
                    } else {
                        console.error('加载手机配置失败，状态码:', xhr.status);
                    }
                }
            };
            xhr.send();
        },
        
        // ========== 保存手机配置 ==========
        _saveMobileConfig: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) {
                layui.layer.msg('请先选择活动', { icon: 2 });
                return;
            }
            
            // 收集表单数据
            var formData = {
                mobile_hide_avatar: document.querySelector('[name="mobile_hide_avatar"]').checked ? 1 : 0,
                mobile_quick_message: document.querySelector('[name="mobile_quick_message"]').checked ? 1 : 0,
                mobile_force_wx_auth: document.querySelector('[name="mobile_force_wx_auth"]').checked ? 1 : 0,
                mobile_welcome_text: document.getElementById('mobile-welcome-text').value || '',
                mobile_btn_text: document.getElementById('mobile-btn-text').value || ''
            };
            
            layui.layer.load(2);
            
            // 使用HTTP请求保存手机配置
            var xhr = new XMLHttpRequest();
            var url = '/api/hd/sign/' + actId + '/mobile';
            
            xhr.open('POST', url, true);
            xhr.setRequestHeader('Hd-Token', Api.getToken() || '');
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    layui.layer.closeAll('loading');
                    if (xhr.status === 200) {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.code === 0) {
                                layui.layer.msg('手机配置已保存', { icon: 1 });
                            } else {
                                layui.layer.msg(res.msg || '保存失败', { icon: 2 });
                            }
                        } catch (e) {
                            layui.layer.msg('数据解析失败', { icon: 2 });
                        }
                    } else {
                        layui.layer.msg('请求失败', { icon: 2 });
                    }
                }
            };
            xhr.send(JSON.stringify(formData));
        },
        
        // ========== 更新手机预览 ==========
        _updateMobilePreview: function() {
            // 更新欢迎语
            var welcomeText = document.getElementById('mobile-welcome-text');
            var mpWelcome = document.getElementById('mp-welcome');
            if (welcomeText && mpWelcome) {
                var text = welcomeText.value.trim() || '欢迎参与本次活动';
                mpWelcome.textContent = text;
            }
            
            // 更新按钮文本
            var btnText = document.getElementById('mobile-btn-text');
            var mpBtnDefault = document.getElementById('mp-btn-default');
            if (btnText && mpBtnDefault) {
                var text = btnText.value.trim() || '参 与 活 动';
                mpBtnDefault.textContent = text;
            }
            
            // 更新头像隐藏状态
            var hideAvatar = document.querySelector('[name="mobile_hide_avatar"]');
            var mpAvatarArea = document.getElementById('mp-avatar-area');
            if (hideAvatar && mpAvatarArea) {
                if (hideAvatar.checked) {
                    mpAvatarArea.style.display = 'none';
                } else {
                    mpAvatarArea.style.display = 'block';
                }
            }
            
            // 更新上传照片覆盖层（如果开启了上传照片）
            var requirePhoto = document.querySelector('[name="require_photo"]');
            var mpPhotoOverlay = document.getElementById('mp-photo-overlay');
            if (requirePhoto && mpPhotoOverlay) {
                if (requirePhoto.checked) {
                    mpPhotoOverlay.style.display = 'flex';
                } else {
                    mpPhotoOverlay.style.display = 'none';
                }
            }
        },
        
        // ========== 处理手机图片上传 ==========
        _handleMobileUpload: function(input, fieldName) {
            // 图片上传处理逻辑（简化版）
            var file = input.files[0];
            if (!file) return;
            
            // 验证文件类型
            if (!file.type.match('image/jpeg') && !file.type.match('image/png')) {
                layui.layer.msg('请上传JPEG或PNG格式的图片', { icon: 2 });
                return;
            }
            
            // 验证文件大小（1MB以内）
            if (file.size > 1024 * 1024) {
                layui.layer.msg('图片大小不能超过1MB', { icon: 2 });
                return;
            }
            
            var reader = new FileReader();
            reader.onload = function(e) {
                var previewImgId = fieldName + '-preview-img';
                var previewDivId = fieldName + '-preview';
                
                var previewImg = document.getElementById(previewImgId);
                var previewDiv = document.getElementById(previewDivId);
                
                if (previewImg) {
                    previewImg.src = e.target.result;
                }
                if (previewDiv) {
                    previewDiv.style.display = 'block';
                }
            };
            reader.readAsDataURL(file);
        },
        
        // ========== 删除手机图片 ==========
        _removeMobileImage: function(fieldName) {
            var previewImgId = fieldName + '-preview-img';
            var previewDivId = fieldName + '-preview';
            
            var previewImg = document.getElementById(previewImgId);
            var previewDiv = document.getElementById(previewDivId);
            
            if (previewImg) previewImg.src = '';
            if (previewDiv) previewDiv.style.display = 'none';
            
            // 清除文件输入
            var fileInputs = document.querySelectorAll('input[type="file"]');
            fileInputs.forEach(function(input) {
                input.value = '';
            });
            
            layui.layer.msg('已删除图片', { icon: 1 });
        },
        
        // ========== 复制签到URL ==========
        _copySignUrl: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) {
                layui.layer.msg('请先选择活动', { icon: 2 });
                return;
            }
            
            var signUrlInput = document.getElementById('mobile-sign-url-input');
            if (signUrlInput) {
                // 构建URL
                var url = window.location.origin + '/huodong/sign/mobile.php?act_id=' + actId;
                signUrlInput.value = url;
                
                // 复制到剪贴板
                signUrlInput.select();
                document.execCommand('copy');
                
                layui.layer.msg('签到URL已复制到剪贴板', { icon: 1 });
            }
        },

        // ========== 3D签到 ==========
        render3d: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title">3D签到</div>' +
                '<div class="layui-form" lay-filter="sign3d">' +
                '<div class="form-section"><div class="section-title">3D签到设置</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">头像数量</label><div class="layui-input-block"><input type="number" name="avatarnum" class="layui-input" placeholder="30" min="1" max="100"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">头像大小</label><div class="layui-input-block"><input type="number" name="avatarsize" class="layui-input" placeholder="7" min="1" max="20"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">头像间距</label><div class="layui-input-block"><input type="number" name="avatargap" class="layui-input" placeholder="15" min="1" max="50"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">播放模式</label><div class="layui-input-block"><select name="play_mode"><option value="sequential">顺序播放</option><option value="random">随机播放</option></select></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">空闲动画</label><div class="layui-input-block"><input type="checkbox" name="idle_enabled" lay-skin="switch" lay-text="开启|关闭"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">空闲延迟</label><div class="layui-input-block"><input type="number" name="idle_delay" class="layui-input" placeholder="5000" min="1000" max="30000"></div></div>' +
                '</div>' +
                '</form>' +
                '<div class="form-actions-section" style="margin-top:30px;padding-top:20px;border-top:1px solid #f0f0f0;background-color:#fafafa;padding:20px;border-radius:4px;">' +
                '<div style="display:flex;justify-content:space-between;align-items:center;">' +
                '<div style="color:#666;font-size:14px;">' +
                '<i class="fas fa-info-circle" style="margin-right:8px;color:#1890ff;"></i>' +
                '完成3D签名设置后，请点击保存按钮应用更改' +
                '</div>' +
                '<button type="button" class="btn btn-primary" id="btn-save-3d-config" onclick="SignPage._save3dConfig()" style="padding:8px 24px;font-size:16px;height:40px;">' +
                '<i class="fas fa-save" style="margin-right:8px;"></i>保存设置' +
                '</button>' +
                '</div>' +
                '</div>' +
                '</div>';

            Layout.setContent(html);
            
            // 渲染表单
            layui.form.render(null, 'sign3d');
            
            // 绑定保存按钮事件
            setTimeout(function() {
                var saveBtn = document.getElementById('btn-save-3d-config');
                if (saveBtn && !saveBtn.onclick) {
                    saveBtn.onclick = function() { SignPage._save3dConfig(); };
                }
            }, 100);
            
            // 加载数据
            this._load3dConfig(actId);
        },
        
        // ========== 加载3D配置 ==========
        _load3dConfig: function(actId) {
            if (!actId) return;
            
            layui.layer.load(2);
            
            // 使用API获取3D配置
            Api.get3dConfig(actId).then(function(res) {
                layui.layer.closeAll('loading');
                var data = res.data || {};
                
                // 填充表单数据
                if (data.avatarnum) {
                    var avatarNum = document.querySelector('[name="avatarnum"]');
                    if (avatarNum) avatarNum.value = data.avatarnum;
                }
                
                if (data.avatarsize) {
                    var avatarSize = document.querySelector('[name="avatarsize"]');
                    if (avatarSize) avatarSize.value = data.avatarsize;
                }
                
                if (data.avatargap) {
                    var avatarGap = document.querySelector('[name="avatargap"]');
                    if (avatarGap) avatarGap.value = data.avatargap;
                }
                
                if (data.play_mode) {
                    var playMode = document.querySelector('[name="play_mode"]');
                    if (playMode) playMode.value = data.play_mode;
                }
                
                if (data.idle_enabled !== undefined) {
                    var idleEnabled = document.querySelector('[name="idle_enabled"]');
                    if (idleEnabled) {
                        idleEnabled.checked = !!data.idle_enabled;
                        layui.form.render('checkbox', 'sign3d');
                    }
                }
                
                if (data.idle_delay) {
                    var idleDelay = document.querySelector('[name="idle_delay"]');
                    if (idleDelay) idleDelay.value = data.idle_delay;
                }
                
                // 重新渲染表单
                layui.form.render(null, 'sign3d');
                
            }).catch(function(err) {
                layui.layer.closeAll('loading');
                console.error('加载3D配置失败:', err);
            });
        },
        
        // ========== 保存3D配置 ==========
        _save3dConfig: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) {
                layui.layer.msg('请先选择活动', { icon: 2 });
                return;
            }
            
            // 收集表单数据
            var formData = {
                avatarnum: document.querySelector('[name="avatarnum"]').value || 30,
                avatarsize: document.querySelector('[name="avatarsize"]').value || 7,
                avatargap: document.querySelector('[name="avatargap"]').value || 15,
                play_mode: document.querySelector('[name="play_mode"]').value || 'sequential',
                idle_enabled: document.querySelector('[name="idle_enabled"]').checked ? 1 : 0,
                idle_delay: document.querySelector('[name="idle_delay"]').value || 5000
            };
            
            // 验证数值
            formData.avatarnum = parseInt(formData.avatarnum) || 30;
            formData.avatarsize = parseInt(formData.avatarsize) || 7;
            formData.avatargap = parseInt(formData.avatargap) || 15;
            formData.idle_delay = parseInt(formData.idle_delay) || 5000;
            
            layui.layer.load(2);
            
            // 使用API保存配置
            Api.save3dConfig(actId, formData).then(function(res) {
                layui.layer.closeAll('loading');
                if (res.code === 0) {
                    layui.layer.msg('3D设置已保存', { icon: 1 });
                } else {
                    layui.layer.msg(res.msg || '保存失败', { icon: 2 });
                }
            }).catch(function(err) {
                layui.layer.closeAll('loading');
                layui.layer.msg(err.msg || '保存失败', { icon: 2 });
            });
        },

        // ========== 头像墙 ==========
        renderAvatar: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title">头像墙</div>' +
                '<div class="layui-form" lay-filter="signAvatar">' +
                '<div class="form-section"><div class="section-title">头像墙设置</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">显示模式</label><div class="layui-input-block"><select name="display_mode"><option value="grid">网格布局</option><option value="flow">流式布局</option></select></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">头像大小</label><div class="layui-input-block"><input type="number" name="avatar_size" class="layui-input" placeholder="60" min="20" max="120"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">头像间距</label><div class="layui-input-block"><input type="number" name="avatar_gap" class="layui-input" placeholder="10" min="2" max="30"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">背景颜色</label><div class="layui-input-block"><input type="color" name="bg_color" class="layui-input" value="#f5f5f5"></div></div>' +
                '</div>' +
                '</form>' +
                '<div class="form-actions-section" style="margin-top:30px;padding-top:20px;border-top:1px solid #f0f0f0;background-color:#fafafa;padding:20px;border-radius:4px;">' +
                '<div style="display:flex;justify-content:space-between;align-items:center;">' +
                '<div style="color:#666;font-size:14px;">' +
                '<i class="fas fa-info-circle" style="margin-right:8px;color:#1890ff;"></i>' +
                '完成头像墙设置后，请点击保存按钮应用更改' +
                '</div>' +
                '<button type="button" class="btn btn-primary" id="btn-save-avatar-config" onclick="SignPage._saveAvatarConfig()" style="padding:8px 24px;font-size:16px;height:40px;">' +
                '<i class="fas fa-save" style="margin-right:8px;"></i>保存设置' +
                '</button>' +
                '</div>' +
                '</div>' +
                '</div>';

            Layout.setContent(html);
            
            // 渲染表单
            layui.form.render(null, 'signAvatar');
            
            // 绑定保存按钮事件
            setTimeout(function() {
                var saveBtn = document.getElementById('btn-save-avatar-config');
                if (saveBtn && !saveBtn.onclick) {
                    saveBtn.onclick = function() { SignPage._saveAvatarConfig(); };
                }
            }, 100);
            
            // 加载数据
            this._loadAvatarConfig(actId);
        },
        
        // ========== 加载头像墙配置 ==========
        _loadAvatarConfig: function(actId) {
            if (!actId) return;
            
            layui.layer.load(2);
            
            // 使用API获取头像墙配置
            // 注意：这里需要根据实际情况调用对应的API
            // 假设使用get3dConfig也获取头像墙配置
            Api.get3dConfig(actId).then(function(res) {
                layui.layer.closeAll('loading');
                var data = res.data || {};
                
                // 填充表单数据
                if (data.display_mode) {
                    var displayMode = document.querySelector('[name="display_mode"]');
                    if (displayMode) displayMode.value = data.display_mode;
                }
                
                if (data.avatar_size) {
                    var avatarSize = document.querySelector('[name="avatar_size"]');
                    if (avatarSize) avatarSize.value = data.avatar_size;
                }
                
                if (data.avatar_gap) {
                    var avatarGap = document.querySelector('[name="avatar_gap"]');
                    if (avatarGap) avatarGap.value = data.avatar_gap;
                }
                
                if (data.bg_color) {
                    var bgColor = document.querySelector('[name="bg_color"]');
                    if (bgColor) bgColor.value = data.bg_color || '#f5f5f5';
                }
                
                // 重新渲染表单
                layui.form.render(null, 'signAvatar');
                
            }).catch(function(err) {
                layui.layer.closeAll('loading');
                console.error('加载头像墙配置失败:', err);
            });
        },
        
        // ========== 保存头像墙配置 ==========
        _saveAvatarConfig: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) {
                layui.layer.msg('请先选择活动', { icon: 2 });
                return;
            }
            
            // 收集表单数据
            var formData = {
                display_mode: document.querySelector('[name="display_mode"]').value || 'grid',
                avatar_size: parseInt(document.querySelector('[name="avatar_size"]').value) || 60,
                avatar_gap: parseInt(document.querySelector('[name="avatar_gap"]').value) || 10,
                bg_color: document.querySelector('[name="bg_color"]').value || '#f5f5f5'
            };
            
            layui.layer.load(2);
            
            // 使用API保存配置
            // 注意：这里需要根据实际情况调用对应的API
            // 假设使用save3dConfig也保存头像墙配置
            Api.save3dConfig(actId, formData).then(function(res) {
                layui.layer.closeAll('loading');
                if (res.code === 0) {
                    layui.layer.msg('头像墙设置已保存', { icon: 1 });
                } else {
                    layui.layer.msg(res.msg || '保存失败', { icon: 2 });
                }
            }).catch(function(err) {
                layui.layer.closeAll('loading');
                layui.layer.msg(err.msg || '保存失败', { icon: 2 });
            });
        },

        // ========== 泡泡签到 ==========
        renderBubble: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title">泡泡签到</div>' +
                '<div class="layui-form" lay-filter="signBubble">' +
                '<div class="form-section"><div class="section-title">泡泡签到设置</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">泡泡数量</label><div class="layui-input-block"><input type="number" name="bubble_count" class="layui-input" placeholder="50" min="10" max="200"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">泡泡大小</label><div class="layui-input-block"><input type="number" name="bubble_size" class="layui-input" placeholder="30" min="10" max="80"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">移动速度</label><div class="layui-input-block"><input type="number" name="bubble_speed" class="layui-input" placeholder="5" min="1" max="20"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">背景颜色</label><div class="layui-input-block"><input type="color" name="bg_color" class="layui-input" value="#1a237e"></div></div>' +
                '</div>' +
                '</form>' +
                '<div class="form-actions-section" style="margin-top:30px;padding-top:20px;border-top:1px solid #f0f0f0;background-color:#fafafa;padding:20px;border-radius:4px;">' +
                '<div style="display:flex;justify-content:space-between;align-items:center;">' +
                '<div style="color:#666;font-size:14px;">' +
                '<i class="fas fa-info-circle" style="margin-right:8px;color:#1890ff;"></i>' +
                '完成泡泡签到设置后，请点击保存按钮应用更改' +
                '</div>' +
                '<button type="button" class="btn btn-primary" id="btn-save-bubble-config" onclick="SignPage._saveBubbleConfig()" style="padding:8px 24px;font-size:16px;height:40px;">' +
                '<i class="fas fa-save" style="margin-right:8px;"></i>保存设置' +
                '</button>' +
                '</div>' +
                '</div>' +
                '</div>';

            Layout.setContent(html);
            
            // 渲染表单
            layui.form.render(null, 'signBubble');
            
            // 绑定保存按钮事件
            setTimeout(function() {
                var saveBtn = document.getElementById('btn-save-bubble-config');
                if (saveBtn && !saveBtn.onclick) {
                    saveBtn.onclick = function() { SignPage._saveBubbleConfig(); };
                }
            }, 100);
            
            // 加载数据
            this._loadBubbleConfig(actId);
        },
        
        // ========== 加载泡泡签到配置 ==========
        _loadBubbleConfig: function(actId) {
            if (!actId) return;
            
            layui.layer.load(2);
            
            // 使用API获取泡泡签到配置
            // 注意：这里需要根据实际情况调用对应的API
            // 假设使用get3dConfig也获取泡泡签到配置
            Api.get3dConfig(actId).then(function(res) {
                layui.layer.closeAll('loading');
                var data = res.data || {};
                
                // 填充表单数据
                if (data.bubble_count) {
                    var bubbleCount = document.querySelector('[name="bubble_count"]');
                    if (bubbleCount) bubbleCount.value = data.bubble_count;
                }
                
                if (data.bubble_size) {
                    var bubbleSize = document.querySelector('[name="bubble_size"]');
                    if (bubbleSize) bubbleSize.value = data.bubble_size;
                }
                
                if (data.bubble_speed) {
                    var bubbleSpeed = document.querySelector('[name="bubble_speed"]');
                    if (bubbleSpeed) bubbleSpeed.value = data.bubble_speed;
                }
                
                if (data.bg_color) {
                    var bgColor = document.querySelector('[name="bg_color"]');
                    if (bgColor) bgColor.value = data.bg_color || '#1a237e';
                }
                
                // 重新渲染表单
                layui.form.render(null, 'signBubble');
                
            }).catch(function(err) {
                layui.layer.closeAll('loading');
                console.error('加载泡泡签到配置失败:', err);
            });
        },
        
        // ========== 保存泡泡签到配置 ==========
        _saveBubbleConfig: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) {
                layui.layer.msg('请先选择活动', { icon: 2 });
                return;
            }
            
            // 收集表单数据
            var formData = {
                bubble_count: parseInt(document.querySelector('[name="bubble_count"]').value) || 50,
                bubble_size: parseInt(document.querySelector('[name="bubble_size"]').value) || 30,
                bubble_speed: parseInt(document.querySelector('[name="bubble_speed"]').value) || 5,
                bg_color: document.querySelector('[name="bg_color"]').value || '#1a237e'
            };
            
            layui.layer.load(2);
            
            // 使用API保存配置
            // 注意：这里需要根据实际情况调用对应的API
            // 假设使用save3dConfig也保存泡泡签到配置
            Api.save3dConfig(actId, formData).then(function(res) {
                layui.layer.closeAll('loading');
                if (res.code === 0) {
                    layui.layer.msg('泡泡签到设置已保存', { icon: 1 });
                } else {
                    layui.layer.msg(res.msg || '保存失败', { icon: 2 });
                }
            }).catch(function(err) {
                layui.layer.closeAll('loading');
                layui.layer.msg(err.msg || '保存失败', { icon: 2 });
            });
        },
        
        // ========== 自定义字段管理 ==========
        
        // ========== 添加自定义字段 ==========
        _addCustomField: function() {
            var customFieldsBody = document.getElementById('custom-fields-body');
            if (!customFieldsBody) return;
            
            // 生成新的字段ID
            var fieldId = 'custom_field_' + Date.now();
            var fieldIndex = customFieldsBody.querySelectorAll('tr').length + 1;
            
            // 创建字段行HTML
            var fieldRow = '<tr id="custom-field-' + fieldId + '">' +
                '<td><input type="text" class="layui-input" placeholder="字段名称" data-field="field_name" style="width:140px;height:30px;"></td>' +
                '<td><select class="layui-select" data-field="field_type" style="width:90px;height:30px;" onchange="SignPage._updateFieldOptions(this)">' +
                '<option value="text">文本</option>' +
                '<option value="number">数字</option>' +
                '<option value="select">下拉框</option>' +
                '<option value="radio">单选</option>' +
                '<option value="checkbox">多选</option>' +
                '<option value="date">日期</option>' +
                '<option value="textarea">多行文本</option>' +
                '</select></td>' +
                '<td><input type="text" class="layui-input" placeholder="用逗号分隔选项" data-field="field_options" style="width:170px;height:30px;" title="仅下拉框、单选、多选类型需要填写选项值"></td>' +
                '<td><input type="checkbox" class="layui-input" data-field="required" lay-skin="switch" lay-text="是|否"></td>' +
                '<td><input type="number" class="layui-input" data-field="sort_order" value="' + fieldIndex + '" min="1" style="width:60px;height:30px;"></td>' +
                '<td><button type="button" class="btn btn-danger btn-xs" onclick="SignPage._removeCustomField(\'' + fieldId + '\')"><i class="fas fa-trash"></i> 删除</button></td>' +
                '</tr>';
            
            // 添加到表格
            customFieldsBody.insertAdjacentHTML('beforeend', fieldRow);
            
            // 渲染Layui组件
            if (layui && layui.form) {
                layui.form.render();
            }
            
            // 自动聚焦到新添加的字段名称输入框
            setTimeout(function() {
                var fieldRow = document.getElementById('custom-field-' + fieldId);
                if (fieldRow) {
                    var nameInput = fieldRow.querySelector('[data-field="field_name"]');
                    if (nameInput) {
                        nameInput.focus();
                    }
                }
            }, 100);
            
            // 如果这是第一个字段，添加示例提示
            if (fieldIndex === 1) {
                layui.layer.msg('第一个自定义字段已添加，保存后将生效', { icon: 1, time: 2000 });
            }
        },
        
        // ========== 更新字段选项 ==========
        _updateFieldOptions: function(selectElement) {
            var fieldType = selectElement.value;
            var row = selectElement.closest('tr');
            if (!row) return;
            
            var optionsInput = row.querySelector('[data-field="field_options"]');
            if (!optionsInput) return;
            
            // 根据字段类型更新输入框的状态
            if (fieldType === 'select' || fieldType === 'radio' || fieldType === 'checkbox') {
                optionsInput.placeholder = '用逗号分隔选项，如：选项1,选项2,选项3';
                optionsInput.disabled = false;
                optionsInput.title = '请输入选项值，用逗号分隔';
            } else {
                optionsInput.placeholder = '无需选项';
                optionsInput.value = '';
                optionsInput.disabled = true;
                optionsInput.title = '此字段类型不需要选项值';
            }
        },
        
        // ========== 移除自定义字段 ==========
        _removeCustomField: function(fieldId) {
            var fieldRow = document.getElementById('custom-field-' + fieldId);
            if (fieldRow) {
                fieldRow.remove();
                // 重新排序
                this._reorderCustomFields();
                layui.layer.msg('字段已删除', { icon: 1, time: 1500 });
            }
        },
        
        // ========== 重新排序自定义字段 ==========
        _reorderCustomFields: function() {
            var rows = document.querySelectorAll('#custom-fields-body tr');
            rows.forEach(function(row, index) {
                var sortInput = row.querySelector('[data-field="sort_order"]');
                if (sortInput) {
                    sortInput.value = index + 1;
                }
            });
        },
        
        // ========== 获取所有自定义字段数据 ==========
        _getCustomFieldsData: function() {
            var fields = [];
            var rows = document.querySelectorAll('#custom-fields-body tr');
            
            rows.forEach(function(row) {
                var fieldName = row.querySelector('[data-field="field_name"]');
                var fieldType = row.querySelector('[data-field="field_type"]');
                var fieldOptions = row.querySelector('[data-field="field_options"]');
                var required = row.querySelector('[data-field="required"]');
                var sortOrder = row.querySelector('[data-field="sort_order"]');
                
                if (fieldName && fieldType) {
                    var fieldData = {
                        field_name: fieldName.value.trim() || '',
                        field_type: fieldType.value || 'text',
                        field_options: fieldOptions ? fieldOptions.value.trim() : '',
                        required: required ? (required.checked ? 1 : 0) : 0,
                        sort_order: sortOrder ? parseInt(sortOrder.value) || 0 : 0
                    };
                    
                    // 验证字段名称
                    if (fieldData.field_name) {
                        fields.push(fieldData);
                    }
                }
            });
            
            return fields;
        },
        
        // ========== 加载自定义字段数据 ==========
        _loadCustomFields: function(fieldsData) {
            var customFieldsBody = document.getElementById('custom-fields-body');
            if (!customFieldsBody || !fieldsData || !Array.isArray(fieldsData)) return;
            
            // 清空现有字段
            customFieldsBody.innerHTML = '';
            
            // 添加每个字段
            fieldsData.forEach(function(field, index) {
                var fieldId = 'custom_field_' + Date.now() + '_' + index;
                
                var fieldRow = '<tr id="custom-field-' + fieldId + '">' +
                    '<td><input type="text" class="layui-input" placeholder="字段名称" data-field="field_name" value="' + (field.field_name || '') + '" style="width:140px;height:30px;"></td>' +
                    '<td><select class="layui-select" data-field="field_type" style="width:90px;height:30px;" onchange="SignPage._updateFieldOptions(this)">' +
                    '<option value="text"' + (field.field_type === 'text' ? ' selected' : '') + '>文本</option>' +
                    '<option value="number"' + (field.field_type === 'number' ? ' selected' : '') + '>数字</option>' +
                    '<option value="select"' + (field.field_type === 'select' ? ' selected' : '') + '>下拉框</option>' +
                    '<option value="radio"' + (field.field_type === 'radio' ? ' selected' : '') + '>单选</option>' +
                    '<option value="checkbox"' + (field.field_type === 'checkbox' ? ' selected' : '') + '>多选</option>' +
                    '<option value="date"' + (field.field_type === 'date' ? ' selected' : '') + '>日期</option>' +
                    '<option value="textarea"' + (field.field_type === 'textarea' ? ' selected' : '') + '>多行文本</option>' +
                    '</select></td>' +
                    '<td><input type="text" class="layui-input" placeholder="' + (field.field_type === 'select' || field.field_type === 'radio' || field.field_type === 'checkbox' ? '用逗号分隔选项' : '无需选项') + '" data-field="field_options" value="' + (field.field_options || '') + '" style="width:170px;height:30px;" title="' + (field.field_type === 'select' || field.field_type === 'radio' || field.field_type === 'checkbox' ? '请输入选项值，用逗号分隔' : '此字段类型不需要选项值') + '" ' + (field.field_type === 'select' || field.field_type === 'radio' || field.field_type === 'checkbox' ? '' : 'disabled') + '></td>' +
                    '<td><input type="checkbox" class="layui-input" data-field="required" lay-skin="switch" lay-text="是|否"' + (field.required ? ' checked' : '') + '></td>' +
                    '<td><input type="number" class="layui-input" data-field="sort_order" value="' + (field.sort_order || (index + 1)) + '" min="1" style="width:60px;height:30px;"></td>' +
                    '<td><button type="button" class="btn btn-danger btn-xs" onclick="SignPage._removeCustomField(\'' + fieldId + '\')"><i class="fas fa-trash"></i> 删除</button></td>' +
                    '</tr>';
                
                customFieldsBody.insertAdjacentHTML('beforeend', fieldRow);
            });
            
            // 渲染Layui组件
            if (layui && layui.form) {
                layui.form.render();
            }
        },
        
        // ========== 地图功能 ==========
        
        // 初始化地图
        _initMap: function() {
            var mapContainer = document.getElementById('location-map');
            var locationSearchIcon = document.querySelector('.location-search-icon');
            var signAddressInput = document.getElementById('sign-address');
            
            if (!mapContainer) return;
            
            console.log('开始初始化地图...');
            
            // 设置加载状态
            mapContainer.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;flex-direction:column;color:#666;"><i class="fas fa-spinner fa-spin" style="font-size:32px;margin-bottom:15px;color:#2196f3;"></i><div style="font-size:14px;margin-bottom:5px;">地图加载中...</div><div style="font-size:12px;color:#999;">首次加载可能需要几秒钟</div></div>';
            mapContainer.style.backgroundColor = '#f5f5f5';
            mapContainer.classList.add('loading');
            
            // 检查高德地图API是否已加载
            if (!window.AMap) {
                console.log('高德地图API未加载，开始加载API...');
                this._loadAMapAPI();
                return;
            }
            
            try {
                // 设置默认地址（优先使用本地配置）
                var longitude = document.getElementById('sign-longitude').value;
                var latitude = document.getElementById('sign-latitude').value;
                var center = [116.397428, 39.90923]; // 默认北京中心
                
                if (longitude && latitude && longitude !== '0' && latitude !== '0') {
                    center = [parseFloat(longitude), parseFloat(latitude)];
                }
                
                // 创建地图实例
                SignPage._mapInstance = new AMap.Map('location-map', {
                    zoom: longitude && latitude && longitude !== '0' && latitude !== '0' ? 15 : 11,
                    center: center,
                    viewMode: '2D',
                    resizeEnable: true
                });
                
                console.log('地图实例创建成功');
                
                // 添加地图加载完成事件
                SignPage._mapInstance.on('complete', function() {
                    console.log('地图加载完成，清除加载状态');
                    mapContainer.classList.remove('loading');
                    mapContainer.style.backgroundColor = '#fff';
                    
                    // 如果有坐标，添加标记点
                    if (longitude && latitude && longitude !== '0' && latitude !== '0') {
                        setTimeout(() => {
                            var address = document.getElementById('sign-address').value;
                            SignPage._addMarkerToMap(center, address || '已选位置');
                        }, 300);
                    }
                });
                
                // 给搜索图标添加点击事件
                if (locationSearchIcon) {
                    locationSearchIcon.addEventListener('click', function() {
                        SignPage._searchLocation();
                    });
                }
                
                // 给搜索图标添加点击事件
                if (locationSearchIcon) {
                    locationSearchIcon.addEventListener('click', function() {
                        SignPage._searchLocation();
                    });
                }
                
                // 给搜索输入框添加按键事件
                if (signAddressInput) {
                    signAddressInput.addEventListener('keypress', function(e) {
                        if (e.key === 'Enter') {
                            SignPage._searchLocation();
                        }
                    });
                    
                    // 输入事件 - 实时搜索建议
                    signAddressInput.addEventListener('input', function() {
                        var keyword = this.value.trim();
                        if (keyword.length >= 2 && window.AMap) {
                            SignPage._getLocationSuggestions(keyword);
                        } else {
                            var suggestionsContainer = document.getElementById('location-suggestions');
                            if (suggestionsContainer) {
                                suggestionsContainer.style.display = 'none';
                            }
                        }
                    });
                    
                    // 聚焦/失焦事件
                    signAddressInput.addEventListener('focus', function() {
                        var keyword = this.value.trim();
                        if (keyword.length >= 2 && window.AMap) {
                            SignPage._getLocationSuggestions(keyword);
                        }
                    });
                    
                    signAddressInput.addEventListener('blur', function() {
                        // 延迟隐藏建议框，避免点击建议项前就隐藏了
                        setTimeout(function() {
                            var suggestionsContainer = document.getElementById('location-suggestions');
                            if (suggestionsContainer) {
                                suggestionsContainer.style.display = 'none';
                            }
                        }, 200);
                    });
                }
                
                // 给"获取当前地址"按钮添加事件
                var btnGetLocation = document.getElementById('btn-get-location');
                if (btnGetLocation) {
                    btnGetLocation.addEventListener('click', function() {
                        SignPage._getCurrentLocation();
                    });
                }
                
            } catch (error) {
                console.error('地图初始化失败:', error);
                
                // 根据错误类型显示不同的错误信息
                var errorMessage = '地图加载失败';
                var errorDetail = '';
                
                if (error.message && error.message.includes('security')) {
                    errorMessage = '安全协议限制';
                    errorDetail = '请在HTTPS环境下使用或联系管理员配置安全域名';
                } else if (error.message && error.message.includes('key')) {
                    errorMessage = 'API密钥错误';
                    errorDetail = '高德地图API密钥配置不正确或已过期';
                } else if (error.message && error.message.includes('network') || error.message && error.message.includes('Network')) {
                    errorMessage = '网络连接问题';
                    errorDetail = '请检查网络连接，或地图API服务暂时不可用';
                } else {
                    errorDetail = error.message || '未知错误';
                }
                
                mapContainer.innerHTML = '<div style="padding:25px;text-align:center;color:#666;">' +
                    '<i class="fas fa-exclamation-circle" style="font-size:40px;color:#e74c3c;margin-bottom:15px;"></i>' +
                    '<h4 style="margin-bottom:10px;">' + errorMessage + '</h4>' +
                    '<p style="color:#888;margin-bottom:15px;">' + errorDetail + '</p>' +
                    '<div style="margin-top:20px;">' +
                    '<button type="button" class="btn btn-primary btn-sm" onclick="SignPage._tryReloadMap()" style="margin-right:8px;">' +
                    '<i class="fas fa-redo"></i> 重试</button>' +
                    '<button type="button" class="btn btn-default btn-sm" onclick="SignPage._showManualLocationInput()">' +
                    '<i class="fas fa-keyboard"></i> 手动输入</button>' +
                    '</div>' +
                    '</div>';
                
                mapContainer.classList.remove('loading');
            }
        },
        
        // 加载高德地图API
        _loadAMapAPI: function() {
            var mapContainer = document.getElementById('location-map');
            if (!mapContainer) return;
            
            console.log('开始加载高德地图API...');
            
            // 使用一个免费可用的测试密钥（仅供测试，正式环境需申请自己的API密钥）
            var apiKey = 'd2c5aed5e0c8dedd1be7fea7c7c46b37'; // 测试用高德地图密钥（可能会有限制）
            var script = document.createElement('script');
            script.src = 'https://webapi.amap.com/maps?v=2.0&key=' + apiKey + '&plugin=AMap.PlaceSearch,AMap.Geocoder,AMap.Geolocation,AMap.AutoComplete';
            script.async = true;
            script.defer = true;
            
            script.onload = function() {
                console.log('高德地图API加载成功，开始初始化地图...');
                
                // 添加少量延迟确保所有组件都加载完毕
                setTimeout(function() {
                    if (typeof AMap !== 'undefined' && AMap.plugin) {
                        // 加载所需的所有插件
                        AMap.plugin(['AMap.PlaceSearch', 'AMap.Geocoder', 'AMap.Geolocation', 'AMap.AutoComplete'], function() {
                            console.log('地图插件加载完成');
                            SignPage._initMap();
                        });
                    } else {
                        // 直接初始化地图
                        console.warn('AMap.plugin未找到，尝试直接初始化地图');
                        SignPage._initMap();
                    }
                }, 800); // 增加延迟确保API完全加载
            };
            
            script.onerror = function() {
                console.error('高德地图API加载失败，切换到备选方案');
                var mapContainer = document.getElementById('location-map');
                if (mapContainer) {
                    SignPage._showManualLocationInput();
                    console.log('已切换到手动输入模式');
                }
            };
            
            document.head.appendChild(script);
            
            // 设置超时检测，防止无限加载
            setTimeout(function() {
                if (typeof AMap === 'undefined') {
                    console.warn('地图API加载超时');
                    var mapContainer = document.getElementById('location-map');
                    if (mapContainer && mapContainer.innerHTML.indexOf('加载中') !== -1) {
                        mapContainer.innerHTML = '<div style="padding:20px;text-align:center;"><i class="fas fa-clock"></i> 地图加载超时，请刷新页面重试</div>';
                    }
                }
            }, 10000); // 10秒超时
        },
        
        // 搜索地点
        _searchLocation: function() {
            var addressInput = document.getElementById('sign-address');
            if (!addressInput) return;
            
            var keyword = addressInput.value.trim();
            if (!keyword) {
                layui.layer.msg('请输入搜索地址', { icon: 2 });
                return;
            }
            
            if (!window.AMap) {
                layui.layer.msg('地图API未加载成功', { icon: 2 });
                return;
            }
            
            layui.layer.msg('正在搜索...', { icon: 16, time: 1000 });
            
            // 使用高德地图PlaceSearch服务
            var placeSearch = new AMap.PlaceSearch({
                pageSize: 10,
                pageIndex: 1,
                city: '全国',
                autoFitView: true
            });
            
            placeSearch.search(keyword, function(status, result) {
                if (status === 'complete' && result.info === 'OK') {
                    if (result.poiList && result.poiList.pois && result.poiList.pois.length > 0) {
                        var poi = result.poiList.pois[0];
                        SignPage._displaySearchResult(poi);
                    } else {
                        layui.layer.msg('未找到相关地址', { icon: 2 });
                    }
                } else {
                    layui.layer.msg('搜索失败，请稍后重试', { icon: 2 });
                }
            });
        },
        
        // 显示搜索结果
        _displaySearchResult: function(poi) {
            if (!poi || !SignPage._mapInstance) return;
            
            // 更新地址输入框
            var addressInput = document.getElementById('sign-address');
            if (addressInput) {
                addressInput.value = poi.name + (poi.address ? ' - ' + poi.address : '');
            }
            
            // 更新经纬度输入框
            var longitudeInput = document.getElementById('sign-longitude');
            var latitudeInput = document.getElementById('sign-latitude');
            if (longitudeInput) longitudeInput.value = poi.location.lng;
            if (latitudeInput) latitudeInput.value = poi.location.lat;
            
            // 更新地图显示
            var center = [poi.location.lng, poi.location.lat];
            SignPage._mapInstance.setCenter(center);
            SignPage._mapInstance.setZoom(16);
            
            // 添加标记点
            this._addMarkerToMap(center, poi.name, poi.address || poi.pname + poi.cityname + poi.adname);
            
            // 触发地点预览更新
            if (typeof SignPage.updateLocationPreview === 'function') {
                setTimeout(function() {
                    SignPage.updateLocationPreview();
                }, 300);
            }
            
            layui.layer.msg('定位成功', { icon: 1 });
        },
        
        // 添加标记点到地图
        _addMarkerToMap: function(center, title, address) {
            if (!SignPage._mapInstance) return;
            
            // 清除之前的标记点
            if (SignPage._mapMarker) {
                SignPage._mapInstance.remove(SignPage._mapMarker);
                if (SignPage._infoWindow) {
                    SignPage._infoWindow.close();
                }
            }
            
            // 添加新标记点
            SignPage._mapMarker = new AMap.Marker({
                position: center,
                title: title,
                map: SignPage._mapInstance,
                animation: 'AMAP_ANIMATION_DROP'
            });
            
            // 添加信息窗口
            var content = '<div style="padding:8px;min-width:200px;max-width:300px;">' +
                         '<div style="font-weight:bold;margin-bottom:5px;color:#333;">' + title + '</div>';
            
            if (address) {
                content += '<div style="font-size:12px;color:#666;line-height:1.4;">' + address + '</div>';
            } else {
                content += '<div style="font-size:12px;color:#999;">坐标: ' + center[0].toFixed(6) + ', ' + center[1].toFixed(6) + '</div>';
            }
            content += '</div>';
            
            SignPage._infoWindow = new AMap.InfoWindow({
                content: content,
                anchor: 'bottom-center',
                offset: new AMap.Pixel(0, -5)
            });
            
            SignPage._infoWindow.open(SignPage._mapInstance, center);
            
            // 点击标记点显示信息窗口
            SignPage._mapMarker.on('click', function() {
                SignPage._infoWindow.open(SignPage._mapInstance, center);
            });
        },
        
        // 获取地点搜索建议
        _getLocationSuggestions: function(keyword) {
            if (!keyword || keyword.length < 2 || !window.AMap) return;
            
            // 防抖处理
            if (this._suggestionTimeout) {
                clearTimeout(this._suggestionTimeout);
            }
            
            this._suggestionTimeout = setTimeout(() => {
                try {
                    var placeSearch = new AMap.PlaceSearch({
                        pageSize: 5,
                        pageIndex: 1,
                        city: '全国',
                        citylimit: false
                    });
                    
                    placeSearch.search(keyword, function(status, result) {
                        if (status === 'complete' && result.info === 'OK') {
                            if (result.poiList && result.poiList.pois && result.poiList.pois.length > 0) {
                                var suggestions = result.poiList.pois.map(function(poi) {
                                    return {
                                        name: poi.name,
                                        address: poi.address || poi.pname + poi.cityname + poi.adname,
                                        location: poi.location
                                    };
                                });
                                SignPage._showLocationSuggestions(suggestions);
                            } else {
                                SignPage._showLocationSuggestions([]);
                            }
                        } else {
                            SignPage._showLocationSuggestions([]);
                        }
                    });
                } catch (error) {
                    console.error('获取搜索建议失败:', error);
                }
            }, 300);
        },
        
        // 获取当前位置
        _getCurrentLocation: function() {
            if (!navigator.geolocation) {
                layui.layer.msg('您的浏览器不支持地理位置功能', { icon: 2 });
                return;
            }
            
            layui.layer.msg('正在获取当前位置...', { icon: 16, time: 2000 });
            
            navigator.geolocation.getCurrentPosition(
                function(position) {
                    var longitude = position.coords.longitude;
                    var latitude = position.coords.latitude;
                    
                    // 使用高德地图逆地理编码
                    if (window.AMap && window.AMap.service) {
                        var geocoder = new AMap.Geocoder({
                            radius: 1000
                        });
                        
                        geocoder.getAddress([longitude, latitude], function(status, result) {
                            if (status === 'complete' && result.info === 'OK') {
                                var address = result.regeocode.formattedAddress;
                                
                                // 更新输入框
                                var addressInput = document.getElementById('sign-address');
                                var longitudeInput = document.getElementById('sign-longitude');
                                var latitudeInput = document.getElementById('sign-latitude');
                                
                                if (addressInput) addressInput.value = '当前位置：' + address;
                                if (longitudeInput) longitudeInput.value = longitude;
                                if (latitudeInput) latitudeInput.value = latitude;
                                
                                SignPage._displaySearchResult({
                                    name: '当前位置',
                                    address: address,
                                    location: { lng: longitude, lat: latitude }
                                });
                            }
                        });
                    }
                },
                function(error) {
                    var errorMsg = '获取位置失败：';
                    switch(error.code) {
                        case error.PERMISSION_DENIED:
                            errorMsg += '用户拒绝访问位置信息';
                            break;
                        case error.POSITION_UNAVAILABLE:
                            errorMsg += '位置信息不可用';
                            break;
                        case error.TIMEOUT:
                            errorMsg += '请求超时';
                            break;
                        case error.UNKNOWN_ERROR:
                            errorMsg += '未知错误';
                            break;
                    }
                    layui.layer.msg(errorMsg, { icon: 2 });
                },
                {
                    enableHighAccuracy: false,
                    timeout: 10000,
                    maximumAge: 60000
                }
            );
        },
        
        // 地点搜索建议
        _showLocationSuggestions: function(suggestions) {
            var suggestionsContainer = document.getElementById('location-suggestions');
            if (!suggestionsContainer) return;
            
            if (!suggestions || suggestions.length === 0) {
                suggestionsContainer.style.display = 'none';
                return;
            }
            
            var html = '';
            suggestions.forEach(function(suggestion, index) {
                html += '<div class="location-suggestion-item" data-index="' + index + '">' +
                        '<div class="suggestion-name">' + suggestion.name + '</div>' +
                        '<div class="suggestion-address">' + (suggestion.address || suggestion.district) + '</div>' +
                        '</div>';
            });
            
            suggestionsContainer.innerHTML = html;
            suggestionsContainer.style.display = 'block';
            
            // 添加点击事件
            var items = suggestionsContainer.querySelectorAll('.location-suggestion-item');
            items.forEach(function(item) {
                item.addEventListener('click', function() {
                    var index = parseInt(this.getAttribute('data-index'));
                    SignPage._selectSuggestion(suggestions[index]);
                });
            });
        },
        
// 选择搜索建议
        _selectSuggestion: function(suggestion) {
            // 隐藏建议框
            var suggestionsContainer = document.getElementById('location-suggestions');
            if (suggestionsContainer) {
                suggestionsContainer.style.display = 'none';
            }

            // 更新输入框
            var addressInput = document.getElementById('sign-address');
            if (addressInput) {
                addressInput.value = suggestion.name + (suggestion.address ? ' - ' + suggestion.address : '');
                SignPage._searchLocation();
            }
        },

        // 显示手动输入模式（地图API不可用时）
        _showManualLocationInput: function() {
            var mapContainer = document.getElementById('location-map');
            if (!mapContainer) return;
            
            var html = '<div style="padding:30px 20px;text-align:center;">' +
                '<div style="margin-bottom:25px;">' +
                '<i class="fas fa-map-marker-alt" style="font-size:48px;color:#f39c12;margin-bottom:15px;"></i>' +
                '<h3 style="margin-bottom:10px;color:#333;">手动输入位置信息</h3>' +
                '<p style="color:#666;margin-bottom:20px;">地图功能暂不可用，请手动填写位置信息</p>' +
                '</div>' +
                
                '<div style="max-width:500px;margin:0 auto;">' +
                '<div class="manual-location-form" style="background:#f9f9f9;border-radius:8px;padding:20px;text-align:left;">' +
                '<h4 style="margin-bottom:15px;color:#444;">位置信息填写指南：</h4>' +
                '<ol style="padding-left:20px;color:#555;line-height:1.6;">' +
                '<li>在地图应用（如百度地图、高德地图）中搜索您的活动地址</li>' +
                '<li>获取该位置的<strong>经度</strong>和<strong>纬度</strong>坐标</li>' +
                '<li>将坐标填入下方的经纬度输入框中</li>' +
                '<li>填写完整的活动地址信息</li>' +
                '</ol>' +
                
                '<div style="margin-top:20px;padding:15px;background:#fff;border:1px solid #eee;border-radius:6px;">' +
                '<p style="margin-bottom:10px;"><strong>示例坐标：</strong></p>' +
                '<p style="color:#666;font-size:13px;">北京天安门：经度 116.397428，纬度 39.90923<br>' +
                '上海东方明珠：经度 121.499998，纬度 31.239637</p>' +
                '</div>' +
                
                '<div style="margin-top:20px;background:#e8f4fd;border-left:4px solid #2196f3;padding:12px 15px;border-radius:4px;">' +
                '<p style="margin:0;color:#1976d2;"><i class="fas fa-lightbulb" style="margin-right:6px;"></i>' +
                '<strong>提示：</strong> 您可以在百度地图中按<strong>F12</strong>打开开发者工具，在网络请求中查找坐标信息</p>' +
                '</div>' +
                '</div>' +
                '</div>' +
                
                '<div style="margin-top:30px;">' +
                '<button type="button" class="btn btn-primary btn-sm" onclick="SignPage._tryReloadMap()" style="margin-right:10px;">' +
                '<i class="fas fa-redo"></i> 重新加载地图</button>' +
                '<button type="button" class="btn btn-default btn-sm" onclick="$(\'#location-map\').hide();$(\'#manual-location-guide\').show();">' +
                '<i class="fas fa-check-circle"></i> 继续使用手动输入</button>' +
                '</div>' +
                '</div>';
            
            mapContainer.innerHTML = html;
            mapContainer.style.backgroundColor = '#f8f9fa';
            mapContainer.classList.remove('loading');
            
            // 创建一个隐藏的提示区域
            var guideDiv = document.createElement('div');
            guideDiv.id = 'manual-location-guide';
            guideDiv.style.display = 'none';
            guideDiv.style.marginTop = '15px';
            guideDiv.style.padding = '15px';
            guideDiv.style.background = '#d4edda';
            guideDiv.style.border = '1px solid #c3e6cb';
            guideDiv.style.borderRadius = '5px';
            guideDiv.style.color = '#155724';
            guideDiv.innerHTML = '<i class="fas fa-check-circle" style="margin-right:6px;"></i>已切换到手动输入模式，请在下方地址栏填写详细地址和经纬度';
            
            mapContainer.parentNode.insertBefore(guideDiv, mapContainer.nextSibling);
        },

        // 尝试重新加载地图
        _tryReloadMap: function() {
            var mapContainer = document.getElementById('location-map');
            if (!mapContainer) return;
            
            mapContainer.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;flex-direction:column;color:#666;"><i class="fas fa-spinner fa-spin" style="font-size:32px;margin-bottom:15px;color:#2196f3;"></i><div style="font-size:14px;">重新加载地图中...</div></div>';
            mapContainer.style.backgroundColor = '#f5f5f5';
            mapContainer.classList.add('loading');
            
            // 移除可能已存在的script标签
            var oldScripts = document.querySelectorAll('script[src*="amap.com"]');
            oldScripts.forEach(function(script) {
                script.parentNode.removeChild(script);
            });
            
            // 移除手动输入提示
            var guideDiv = document.getElementById('manual-location-guide');
            if (guideDiv) {
                guideDiv.parentNode.removeChild(guideDiv);
            }
            
            // 重新加载API
            setTimeout(function() {
                SignPage._loadAMapAPI();
            }, 500);
        },

        // ========== 权限管理功能 ==========
        
        // 切换管理员权限
        _toggleAdmin: function(userId, isAdmin) {
            var actId = App.getCurrentActivityId();
            if (!actId) {
                layui.layer.msg('请先选择活动', { icon: 2 });
                return;
            }
            
            var action = isAdmin ? '设置为管理员' : '取消管理员权限';
            layui.layer.confirm('确定要' + action + '吗？', {
                icon: isAdmin ? 3 : 2,
                title: action,
                btn: ['确定', '取消']
            }, function(index) {
                layui.layer.close(index);
                SignPage._updateUserPermission(userId, actId, 'admin', isAdmin);
            });
        },

        // 切换核销员权限
        _toggleVerifier: function(userId, isVerifier) {
            var actId = App.getCurrentActivityId();
            if (!actId) {
                layui.layer.msg('请先选择活动', { icon: 2 });
                return;
            }
            
            var action = isVerifier ? '设置为核销员' : '取消核销员权限';
            layui.layer.confirm('确定要' + action + '吗？', {
                icon: isVerifier ? 3 : 2,
                title: action,
                btn: ['确定', '取消']
            }, function(index) {
                layui.layer.close(index);
                SignPage._updateUserPermission(userId, actId, 'verifier', isVerifier);
            });
        },

        // 通用权限更新函数
        _updateUserPermission: function(userId, actId, permissionType, isEnabled) {
            layui.layer.load(2);
            
            var xhr = new XMLHttpRequest();
            var url;
            
            if (permissionType === 'admin') {
                url = API_CONFIG.hdSign.toggleAdmin(actId, userId);
            } else {
                url = API_CONFIG.hdSign.toggleVerifier(actId, userId);
            }
            
            // 添加action参数到URL
            var action = isEnabled ? 'add' : 'remove';
            url += '?action=' + encodeURIComponent(action);
            
            xhr.open('POST', url, true);
            xhr.onreadystatechange = function() {
                if (xhr.readyState === 4) {
                    layui.layer.closeAll('loading');
                    if (xhr.status === 200) {
                        try {
                            var res = JSON.parse(xhr.responseText);
                            if (res.code === 0) {
                                layui.layer.msg(res.msg || '权限更新成功', { icon: 1 });
                                // 重新加载签到名单表格以显示更新后的权限状态
                                setTimeout(function() {
                                    SignPage._loadSignTable(SignPage._signPageCurrent || 1);
                                }, 500);
                            } else {
                                layui.layer.msg(res.msg || '权限更新失败', { icon: 2 });
                            }
                        } catch (e) {
                            layui.layer.msg('数据解析失败', { icon: 2 });
                        }
                    } else {
                        layui.layer.msg('请求失败，状态码：' + xhr.status, { icon: 2 });
                    }
                }
            };
            
            xhr.setRequestHeader('Content-Type', 'application/json');
            xhr.setRequestHeader('Hd-Token', Api.getToken() || '');
            xhr.send();
        },

        // 批量权限管理
        _batchManagePermissions: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) {
                layui.layer.msg('请先选择活动', { icon: 2 });
                return;
            }
            
            // 获取表格选中的数据
            var tableData = layui.table.checkStatus('sign-table').data;
            if (tableData.length === 0) {
                layui.layer.msg('请先选择用户', { icon: 3 });
                return;
            }
            
            var userIds = tableData.map(function(item) {
                return item.id;
            });
            
            layui.layer.open({
                type: 1,
                title: '批量权限管理',
                content: '<div style="padding:20px;">' +
                    '<div class="layui-form-item">' +
                    '<label class="layui-form-label">权限类型</label>' +
                    '<div class="layui-input-block">' +
                    '<select id="batch-permission-role" class="layui-input">' +
                    '<option value="admin">管理员</option>' +
                    '<option value="verifier">核销员</option>' +
                    '</select>' +
                    '</div>' +
                    '</div>' +
                    '<div class="layui-form-item">' +
                    '<label class="layui-form-label">操作</label>' +
                    '<div class="layui-input-block">' +
                    '<select id="batch-permission-action" class="layui-input">' +
                    '<option value="add">添加权限</option>' +
                    '<option value="remove">移除权限</option>' +
                    '</select>' +
                    '</div>' +
                    '</div>' +
                    '<div class="layui-form-item">' +
                    '<label class="layui-form-label">用户数量</label>' +
                    '<div class="layui-input-block">' +
                    '<div style="padding:8px 0;">' + userIds.length + ' 个用户</div>' +
                    '</div>' +
                    '</div>' +
                    '</div>',
                area: ['450px', '300px'],
                btn: ['确定', '取消'],
                yes: function(index) {
                    var role = document.querySelector('#batch-permission-role').value;
                    var action = document.querySelector('#batch-permission-action').value;
                    
                    layui.layer.close(index);
                    SignPage._executeBatchPermissions(actId, userIds, role, action);
                }
            });
        },

        // 执行批量权限设置 - 使用新的ThinkPHP API逐条处理
        _executeBatchPermissions: function(actId, userIds, role, action) {
            layui.layer.load(2);
            
            var successCount = 0;
            var failCount = 0;
            var total = userIds.length;
            var completed = 0;
            
            // 定义单个用户处理函数
            var processUser = function(userId, callback) {
                var xhr = new XMLHttpRequest();
                var url;
                
                if (role === 'admin') {
                    url = API_CONFIG.hdSign.toggleAdmin(actId, userId);
                } else {
                    url = API_CONFIG.hdSign.toggleVerifier(actId, userId);
                }
                
                // 通过URL参数传递action
                var apiUrl = url + '?action=' + encodeURIComponent(action);
                
                xhr.open('POST', apiUrl, true);
                xhr.setRequestHeader('Content-Type', 'application/json');
                xhr.setRequestHeader('Hd-Token', Api.getToken() || '');
                xhr.onreadystatechange = function() {
                    if (xhr.readyState === 4) {
                        if (xhr.status === 200) {
                            try {
                                var res = JSON.parse(xhr.responseText);
                                if (res.code === 0) {
                                    successCount++;
                                } else {
                                    // 如果是remove操作，且用户本就没有权限，也算成功
                                    if (action === 'remove' && res.msg && (res.msg.includes('取消') || res.msg.includes('已取消'))) {
                                        successCount++;
                                    } else {
                                        failCount++;
                                    }
                                }
                            } catch (e) {
                                failCount++;
                            }
                        } else {
                            failCount++;
                        }
                        completed++;
                        callback();
                    }
                };
                xhr.send();
            };
            
            // 处理所有用户
            var processNext = function(index) {
                if (index >= total) {
                    // 所有用户处理完成
                    layui.layer.closeAll('loading');
                    
                    var message = '批量权限设置完成<br>成功：' + successCount + ' 个<br>失败：' + failCount + ' 个';
                    
                    layui.layer.msg(message, {
                        icon: 1,
                        time: 4000
                    });
                    
                    // 重新加载签到名单表格
                    setTimeout(function() {
                        SignPage._loadSignTable(SignPage._signPageCurrent || 1);
                    }, 800);
                    return;
                }
                
                processUser(userIds[index], function() {
                    // 更新进度
                    var progress = Math.floor((completed / total) * 100);
                    // 可以在这里更新进度提示，简化处理直接继续
                    setTimeout(function() {
                        processNext(index + 1);
                    }, 100); // 添加小延迟避免请求过于密集
                });
            };
            
            // 开始处理
            processNext(0);
        },

        // 尝试重新加载地图
        _tryReloadMap: function() {
            var mapContainer = document.getElementById('location-map');
            if (!mapContainer) return;
            
            mapContainer.innerHTML = '<div style="display:flex;align-items:center;justify-content:center;height:100%;flex-direction:column;color:#666;"><i class="fas fa-spinner fa-spin" style="font-size:32px;margin-bottom:15px;color:#2196f3;"></i><div style="font-size:14px;">重新加载地图中...</div></div>';
            mapContainer.style.backgroundColor = '#f5f5f5';
            mapContainer.classList.add('loading');
            
            // 移除可能已存在的script标签
            var oldScripts = document.querySelectorAll('script[src*="amap.com"]');
            oldScripts.forEach(function(script) {
                script.parentNode.removeChild(script);
            });
            
            // 移除手动输入提示
            var guideDiv = document.getElementById('manual-location-guide');
            if (guideDiv) {
                guideDiv.parentNode.removeChild(guideDiv);
            }
            
            // 重新加载API
            setTimeout(function() {
                SignPage._loadAMapAPI();
            }, 500);
        }
    };

    global.SignPage = SignPage;
})(window);
