/**
 * 互动抽奖页面模块
 * 包含：抽奖轮次、奖品设置、中奖名单、内定名单、抽奖主题、手机端抽奖、导入抽奖
 */
;(function(global) {
    'use strict';

    // 修复 notice.avoidance 缺失的错误
    if (typeof notice !== 'undefined' && typeof notice.avoidance === 'undefined') {
        notice.avoidance = function() {};
    }

    // 修复 judegIsFullScreen 缺失的错误
    if (typeof judegIsFullScreen === 'undefined') {
        window.judegIsFullScreen = function() {
            return false;
        };
    }

    var LotteryPage = {
        // ========== 抽奖轮次 ==========
        renderRounds: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title"><span>抽奖轮次</span><button class="btn btn-primary btn-sm" onclick="LotteryPage._addRound()"><i class="fas fa-plus"></i> 添加轮次</button></div>' +
                '<table id="lottery-rounds-table" lay-filter="lotteryRoundsTable"></table></div>';
            Layout.setContent(html);
            this._initRoundsTable(actId);
        },

        _initRoundsTable: function(actId) {
            if (!document.getElementById('lotteryRoundsBar')) {
                var s = document.createElement('script');
                s.type = 'text/html'; s.id = 'lotteryRoundsBar';
                s.innerHTML = '<a class="layui-btn layui-btn-xs" lay-event="edit"><i class="fas fa-edit"></i> 编辑</a>' +
                    '<a class="layui-btn layui-btn-warm layui-btn-xs" lay-event="reset"><i class="fas fa-redo"></i> 重置</a>' +
                    '<a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="del"><i class="fas fa-trash"></i> 删除</a>';
                document.body.appendChild(s);
            }

            layui.table.render({
                elem: '#lottery-rounds-table',
                url: '/api/hd/lottery/' + actId + '/rounds',
                headers: { 'Authorization': 'Bearer ' + Api.getToken() },
                parseData: function(res) {
                    var list = res.data ? (res.data.list || res.data) : [];
                    return { code: 0, msg: '', count: Array.isArray(list) ? list.length : 0, data: list };
                },
                cols: [[
                    { field: 'id', title: 'ID', width: 70 },
                    { field: 'title', title: '轮次名称', width: 180 },
                    { field: 'show_type', title: '展示类型', width: 120 },
                    { field: 'win_again', title: '重复中奖', width: 100, templet: function(d) { return d.win_again ? '<span style="color:#43A047">允许</span>' : '<span style="color:#999">不允许</span>'; } },
                    { field: 'status', title: '状态', width: 100, templet: function(d) {
                        var map = { 0: '<span style="color:#999">未开始</span>', 1: '<span style="color:#1E88E5">进行中</span>', 2: '<span style="color:#43A047">已结束</span>' };
                        return map[d.status] || '未知';
                    }},
                    { title: '操作', width: 200, toolbar: '#lotteryRoundsBar' }
                ]],
                page: false,
                text: { none: '暂无抽奖轮次' }
            });

            layui.table.on('tool(lotteryRoundsTable)', function(obj) {
                if (obj.event === 'edit') LotteryPage._editRound(actId, obj.data);
                else if (obj.event === 'reset') {
                    layui.layer.confirm('确定重置该轮次？所有中奖记录将清空！', { icon: 3 }, function(idx) {
                        Api.resetLotteryRound(actId, obj.data.id).then(function() {
                            layui.layer.close(idx);
                            layui.table.reload('lottery-rounds-table');
                            layui.layer.msg('重置成功', { icon: 1 });
                        });
                    });
                } else if (obj.event === 'del') {
                    layui.layer.confirm('确定删除该轮次？', { icon: 3 }, function(idx) {
                        Api.deleteLotteryRound(actId, obj.data.id).then(function() {
                            layui.layer.close(idx);
                            obj.del();
                            layui.layer.msg('删除成功', { icon: 1 });
                        });
                    });
                }
            });
        },

        _addRound: function() {
            var actId = App.getCurrentActivityId();
            layui.layer.open({
                type: 1, title: '添加抽奖轮次', area: ['500px', '380px'],
                content: '<div style="padding:20px;"><form class="layui-form" lay-filter="addRound">' +
                    '<div class="layui-form-item"><label class="layui-form-label">轮次名称</label><div class="layui-input-block"><input type="text" name="title" class="layui-input" required></div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">展示类型</label><div class="layui-input-block"><select name="show_type"><option value="normal">普通抽奖</option><option value="3d">3D抽奖</option><option value="egg">砸金蛋</option><option value="box">抽奖箱</option></select></div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">重复中奖</label><div class="layui-input-block"><input type="checkbox" name="win_again" lay-skin="switch" lay-text="允许|不允许"></div></div>' +
                    '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" id="btn-save-round"><i class="fas fa-save"></i> 保存</button></div></div>' +
                    '</form></div>',
                success: function(layero) {
                    layui.form.render(null, 'addRound');
                    layero.find('#btn-save-round').on('click', function() {
                        var data = {
                            title: layero.find('[name="title"]').val(),
                            show_type: layero.find('[name="show_type"]').val(),
                            win_again: layero.find('[name="win_again"]').is(':checked') ? 1 : 0
                        };
                        if (!data.title) return layui.layer.msg('请输入轮次名称', { icon: 2 });
                        Api.createLotteryRound(actId, data).then(function() {
                            layui.layer.closeAll();
                            layui.table.reload('lottery-rounds-table');
                            layui.layer.msg('添加成功', { icon: 1 });
                        });
                    });
                }
            });
        },

        _editRound: function(actId, rowData) {
            layui.layer.open({
                type: 1, title: '编辑抽奖轮次', area: ['500px', '380px'],
                content: '<div style="padding:20px;"><form class="layui-form" lay-filter="editRound">' +
                    '<div class="layui-form-item"><label class="layui-form-label">轮次名称</label><div class="layui-input-block"><input type="text" name="title" class="layui-input" value="' + (rowData.title || '') + '"></div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">展示类型</label><div class="layui-input-block"><select name="show_type">' +
                    '<option value="normal"' + (rowData.show_type === 'normal' ? ' selected' : '') + '>普通抽奖</option>' +
                    '<option value="3d"' + (rowData.show_type === '3d' ? ' selected' : '') + '>3D抽奖</option>' +
                    '<option value="egg"' + (rowData.show_type === 'egg' ? ' selected' : '') + '>砸金蛋</option>' +
                    '<option value="box"' + (rowData.show_type === 'box' ? ' selected' : '') + '>抽奖箱</option>' +
                    '</select></div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">重复中奖</label><div class="layui-input-block"><input type="checkbox" name="win_again" lay-skin="switch" lay-text="允许|不允许"' + (rowData.win_again ? ' checked' : '') + '></div></div>' +
                    '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" id="btn-update-round"><i class="fas fa-save"></i> 保存</button></div></div>' +
                    '</form></div>',
                success: function(layero) {
                    layui.form.render(null, 'editRound');
                    layero.find('#btn-update-round').on('click', function() {
                        var data = {
                            title: layero.find('[name="title"]').val(),
                            show_type: layero.find('[name="show_type"]').val(),
                            win_again: layero.find('[name="win_again"]').is(':checked') ? 1 : 0
                        };
                        Api.updateLotteryRound(actId, rowData.id, data).then(function() {
                            layui.layer.closeAll();
                            layui.table.reload('lottery-rounds-table');
                            layui.layer.msg('更新成功', { icon: 1 });
                        });
                    });
                }
            });
        },

        // ========== 奖品设置 ==========
        renderPrizes: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title"><span>奖品设置</span><button class="btn btn-primary btn-sm" onclick="LotteryPage._addPrize()"><i class="fas fa-plus"></i> 添加奖品</button></div>' +
                '<table id="lottery-prizes-table" lay-filter="lotteryPrizesTable"></table></div>';
            Layout.setContent(html);
            this._initPrizesTable(actId);
        },

        _initPrizesTable: function(actId) {
            if (!document.getElementById('lotteryPrizesBar')) {
                var s = document.createElement('script');
                s.type = 'text/html'; s.id = 'lotteryPrizesBar';
                s.innerHTML = '<a class="layui-btn layui-btn-xs" lay-event="edit"><i class="fas fa-edit"></i> 编辑</a><a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="del"><i class="fas fa-trash"></i> 删除</a>';
                document.body.appendChild(s);
            }

            layui.table.render({
                elem: '#lottery-prizes-table',
                url: '/api/hd/lottery/' + actId + '/prizes',
                headers: { 'Authorization': 'Bearer ' + Api.getToken() },
                parseData: function(res) {
                    var list = res.data ? (res.data.list || res.data) : [];
                    return { code: 0, msg: '', count: Array.isArray(list) ? list.length : 0, data: list };
                },
                cols: [[
                    { field: 'imageid', title: '图片', width: 80, templet: function(d) {
                        var img = '';
                        // 检查可能的图片字段
                        var possibleFields = ['imageid', 'image', 'pic', 'picurl', 'picture', 'img', 'imgurl'];
                        for (var i = 0; i < possibleFields.length; i++) {
                            var field = possibleFields[i];
                            if (d[field]) {
                                if (field === 'imageid' && !isNaN(d[field]) && d[field] > 0) {
                                    img = '/huodong/imageproxy.php?id=' + d[field];
                                    break;
                                } else if (typeof d[field] === 'string' && d[field].trim() !== '') {
                                    img = d[field];
                                    break;
                                }
                            }
                        }
                        if (img) {
                            // 确保图片路径是完整URL
                            if (img.startsWith('/')) {
                                img = window.location.origin + img;
                            } else if (!img.startsWith('http') && !img.startsWith('//')) {
                                // 相对路径，添加基础URL
                                img = (window.location.origin + '/') + img;
                            }
                            // 如果图片URL包含 wxhd.eivie.cn 域名，替换为当前域名
                            if (img.includes('wxhd.eivie.cn')) {
                                img = img.replace('https://wxhd.eivie.cn', window.location.origin);
                            }
                            return '<img class="img-preview" src="' + img + '" onerror="this.style.display=\'none\'; this.parentNode.innerHTML=\'<i class=\\\'fas fa-image\\\' style=\\\'font-size:24px;color:#ddd;\\\'></i>\'">';
                        }
                        return '<i class="fas fa-image" style="font-size:24px;color:#ddd;"></i>';
                    }},
                    { field: 'prizename', title: '奖品名称', width: 160 },
{ field: 'type', title: '奖品级别', width: 100, templet: function(d) {
    var map = { 0: '普通奖品（无级别）', 1: '一等奖', 2: '二等奖', 3: '三等奖', 4: '四等奖', 5: '五等奖' };
    // 处理数字字符串和中文级别名称
                        var typeValue = d.type;
                        var result;
                        if (typeValue === null || typeValue === undefined || typeValue === '') {
                            result = '-';
                        } else if (!isNaN(typeValue) && typeValue !== '') {
                            // 数字或数字字符串
                            result = map[parseInt(typeValue, 10)] || typeValue;
                        } else if (typeof typeValue === 'string') {
                            // 检查是否已经是中文级别名称
                            var reverseMap = { '一等奖': 1, '二等奖': 2, '三等奖': 3, '四等奖': 4, '五等奖': 5 };
                            if (reverseMap[typeValue]) {
                                result = typeValue;
                            } else {
                                result = typeValue;
                            }
                        } else {
                            result = typeValue || '-';
                        }
                        return result;
                    }},
                    { field: 'num', title: '数量', width: 80 },
                    { field: 'leftnum', title: '剩余', width: 80 },
                    { field: 'draw_count', title: '每次抽取', width: 100 },
                    { title: '操作', width: 150, toolbar: '#lotteryPrizesBar' }
                ]],
                page: false,
                text: { none: '暂无奖品' }
            });

            layui.table.on('tool(lotteryPrizesTable)', function(obj) {
                console.log('普通奖品表格工具事件:', obj.event, '奖品数据:', obj.data);
                if (obj.event === 'edit') {
                    console.log('编辑奖品，ID:', obj.data.id, '活动ID:', actId);
                    LotteryPage._editPrize(actId, obj.data);
                }
                else if (obj.event === 'del') {
                    layui.layer.confirm('确定删除该奖品？', { icon: 3 }, function(idx) {
                        console.log('删除普通奖品: actId=' + actId + ', prizeId=' + obj.data.id);
                        Api.deleteLotteryPrize(actId, obj.data.id).then(function() {
                            layui.layer.close(idx);
                            obj.del();
                            layui.layer.msg('删除成功', { icon: 1 });
                        }).catch(function(err) {
                            layui.layer.close(idx);
                            if (err && err.msg) {
                                layui.layer.msg('删除失败: ' + err.msg, { icon: 2 });
                            }
                        });
                    });
                }
            });
        },

        // 奖品弹窗内联样式
        _prizeModalCSS: '<style>' +
            '.prize-form-item{display:flex;align-items:flex-start;margin-bottom:18px;}' +
            '.prize-form-label{width:90px;text-align:right;padding:9px 10px 9px 0;color:#333;font-size:14px;flex-shrink:0;line-height:1.3;}' +
            '.prize-form-input{flex:1;min-width:0;}' +
            '.prize-form-input .layui-input{height:38px;border-radius:4px;border-color:#d9d9d9;font-size:14px;}' +
            '.prize-form-input .layui-input:focus{border-color:#1890ff;box-shadow:0 0 0 2px rgba(24,144,255,0.1);}' +
            '.prize-draw-hint{margin-top:6px;display:flex;align-items:flex-start;}' +
            '.prize-hint-icon{color:#1890ff;margin-right:5px;font-style:normal;font-weight:bold;font-size:13px;flex-shrink:0;line-height:1.5;}' +
            '.prize-hint-text{color:#999;font-size:12px;line-height:1.5;}' +
            '.prize-upload-area{border:2px dashed #d9d9d9;border-radius:8px;padding:20px;text-align:center;background:#fafafa;cursor:pointer;transition:all 0.25s ease;width:100%;box-sizing:border-box;}' +
            '.prize-upload-area:hover{border-color:#1890ff;background:#f0f7ff;}' +
            '.prize-upload-icon{font-size:40px;color:#c0c4cc;margin-bottom:8px;}' +
            '.prize-upload-text{color:#999;font-size:14px;}' +
            '.prize-preview-wrap{position:relative;display:inline-block;}' +
            '.prize-preview-img{max-width:180px;max-height:120px;border-radius:6px;border:1px solid #e8e8e8;object-fit:cover;}' +
            '.prize-preview-remove{position:absolute;top:-8px;right:-8px;width:22px;height:22px;background:#ff4d4f;color:#fff;border-radius:50%;text-align:center;line-height:22px;font-size:12px;cursor:pointer;transition:transform 0.2s;}' +
            '.prize-preview-remove:hover{transform:scale(1.15);}' +
            '.prize-upload-tip{margin-top:8px;color:#999;font-size:12px;}' +
            '.prize-footer{display:flex;justify-content:flex-end;padding-top:16px;margin-top:20px;border-top:1px solid #f0f0f0;gap:12px;}' +
            '.btn-prize-cancel{padding:0 20px;height:36px;border:1px solid #d9d9d9;border-radius:4px;background:#fff;color:#666;font-size:14px;cursor:pointer;transition:all 0.25s;}' +
            '.btn-prize-cancel:hover{border-color:#1890ff;color:#1890ff;}' +
            '.btn-prize-save{padding:0 20px;height:36px;border:none;border-radius:4px;background:#1890ff;color:#fff;font-size:14px;cursor:pointer;transition:all 0.25s;}' +
            '.btn-prize-save:hover{background:#40a9ff;}' +
            '</style>',

        _addPrize: function() {
            var actId = App.getCurrentActivityId();
            var self = this;
            layui.layer.open({
                type: 1, title: '奖品信息', area: ['520px', '640px'],
                content: this._prizeModalCSS + '<div style="padding:25px 30px 15px;"><form class="layui-form" lay-filter="addPrize">' +
                    '<div class="prize-form-item"><label class="prize-form-label">抽奖顺序</label><div class="prize-form-input"><input type="number" name="sort" class="layui-input" placeholder="请输入数字" min="0"></div></div>' +
                    '<div class="prize-form-item"><label class="prize-form-label">奖品级别</label><div class="prize-form-input"><select name="type_text" class="layui-select"><option value="0">普通奖品（无级别）</option><option value="1">一等奖</option><option value="2">二等奖</option><option value="3">三等奖</option><option value="4">四等奖</option><option value="5">五等奖</option></select></div></div>' +
                    '<div class="prize-form-item"><label class="prize-form-label">奖品名称</label><div class="prize-form-input"><input type="text" name="prizename" class="layui-input" placeholder="请输入奖品名称"></div></div>' +
                    '<div class="prize-form-item"><label class="prize-form-label">奖品数量</label><div class="prize-form-input"><input type="number" name="num" class="layui-input" placeholder="请输入奖品数量" value="1" min="1"></div></div>' +
                    '<div class="prize-form-item"><label class="prize-form-label">每次抽取人数</label><div class="prize-form-input">' +
                        '<input type="number" name="draw_count" class="layui-input" placeholder="请输入每次抽奖的抽取数量" value="1" min="1">' +
                        '<div class="prize-draw-hint"><span class="prize-hint-icon">ⓘ</span><span class="prize-hint-text">例：假设该奖品有100份，分5次抽取，则每次抽取人数为20</span></div>' +
                    '</div></div>' +
                    '<div class="prize-form-item"><label class="prize-form-label">奖品图片</label><div class="prize-form-input">' +
                        '<input type="file" id="prize-add-file" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;" onchange="LotteryPage._handlePrizeImageUpload(this)">' +
                        '<div id="prize-add-upload-area" class="prize-upload-area" onclick="document.getElementById(\'prize-add-file\').click()">' +
                            '<i class="fas fa-cloud-upload-alt prize-upload-icon"></i>' +
                            '<div class="prize-upload-text">点击此处选择图片</div>' +
                        '</div>' +
                        '<div id="prize-add-preview" class="prize-preview-wrap" style="display:none;">' +
                            '<img id="prize-add-preview-img" class="prize-preview-img" src="">' +
                            '<span class="prize-preview-remove" onclick="LotteryPage._removePrizeImage(\'add\')">×</span>' +
                        '</div>' +
                        '<input type="hidden" name="imageid" id="prize-add-imageid" value="">' +
                        '<div class="prize-upload-tip">尺寸400x400像素，图片大小请勿超过2M</div>' +
                    '</div></div>' +
                    '<div class="prize-footer">' +
                        '<button type="button" class="btn-prize-cancel" onclick="layui.layer.closeAll()">取消</button>' +
                        '<button type="button" class="btn-prize-save" id="btn-save-prize"><i class="fas fa-check"></i> 保存</button>' +
                    '</div>' +
                '</form></div>',
                success: function(layero) {
                    layui.form.render(null, 'addPrize');
                    layero.find('#btn-save-prize').on('click', function() {
                        var data = {
                            sort: parseInt(layero.find('[name="sort"]').val()) || 0,
                            type: layero.find('[name="type_text"]').val(),
                            prizename: layero.find('[name="prizename"]').val(),
                            num: parseInt(layero.find('[name="num"]').val()) || 1,
                            draw_count: parseInt(layero.find('[name="draw_count"]').val()) || 1,
                            imageid: layero.find('[name="imageid"]').val()
                        };
                        if (!data.prizename) return layui.layer.msg('请输入奖品名称', { icon: 2 });
                        Api.createLotteryPrize(actId, data).then(function() {
                            layui.layer.closeAll();
                            layui.table.reload('lottery-prizes-table');
                            try { layui.table.reload('screen-prizes-table'); } catch(e) {}
                            layui.layer.msg('添加成功', { icon: 1 });
                        });
                    });
                }
            });
        },

        _editPrize: function(actId, rowData) {
            console.log('编辑奖品函数，活动ID:', actId, '奖品数据:', rowData);
            if (!rowData || !rowData.id) {
                console.error('编辑奖品失败：奖品ID不存在或无效', rowData);
                layui.layer.msg('奖品数据异常，无法编辑', { icon: 2 });
                return;
            }
            var self = this;
            var hasImage = rowData.imageid || rowData.image;
            layui.layer.open({
                type: 1, title: '奖品信息', area: ['520px', '640px'],
                content: this._prizeModalCSS + '<div style="padding:25px 30px 15px;"><form class="layui-form" lay-filter="editPrize">' +
                    '<div class="prize-form-item"><label class="prize-form-label">抽奖顺序</label><div class="prize-form-input"><input type="number" name="sort" class="layui-input" placeholder="请输入数字" min="0" value="' + (rowData.sort || 0) + '"></div></div>' +
                    '<div class="prize-form-item"><label class="prize-form-label">奖品级别</label><div class="prize-form-input"><select name="type_text" class="layui-select"><option value="0"' + (rowData.type == '0' ? ' selected' : '') + '>普通奖品（无级别）</option><option value="1"' + (rowData.type == '1' ? ' selected' : '') + '>一等奖</option><option value="2"' + (rowData.type == '2' ? ' selected' : '') + '>二等奖</option><option value="3"' + (rowData.type == '3' ? ' selected' : '') + '>三等奖</option><option value="4"' + (rowData.type == '4' ? ' selected' : '') + '>四等奖</option><option value="5"' + (rowData.type == '5' ? ' selected' : '') + '>五等奖</option></select></div></div>' +
                    '<div class="prize-form-item"><label class="prize-form-label">奖品名称</label><div class="prize-form-input"><input type="text" name="prizename" class="layui-input" placeholder="请输入奖品名称" value="' + (rowData.prizename || '') + '"></div></div>' +
                    '<div class="prize-form-item"><label class="prize-form-label">奖品数量</label><div class="prize-form-input"><input type="number" name="num" class="layui-input" placeholder="请输入奖品数量" value="' + (rowData.num || 1) + '" min="1"></div></div>' +
                    '<div class="prize-form-item"><label class="prize-form-label">每次抽取人数</label><div class="prize-form-input">' +
                        '<input type="number" name="draw_count" class="layui-input" placeholder="请输入每次抽奖的抽取数量" value="' + (rowData.draw_count || 1) + '" min="1">' +
                        '<div class="prize-draw-hint"><span class="prize-hint-icon">ⓘ</span><span class="prize-hint-text">例：假设该奖品有100份，分5次抽取，则每次抽取人数为20</span></div>' +
                    '</div></div>' +
                    '<div class="prize-form-item"><label class="prize-form-label">奖品图片</label><div class="prize-form-input">' +
                        '<input type="file" id="prize-edit-file" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;" onchange="LotteryPage._handlePrizeImageUpload(this, \'edit\')">' +
                        (hasImage
                            ? '<div id="prize-edit-preview" class="prize-preview-wrap" style="display:inline-block;">' +
                                '<img id="prize-edit-preview-img" class="prize-preview-img" src="' + (rowData.imageid || rowData.image) + '">' +
                                '<span class="prize-preview-remove" onclick="LotteryPage._removePrizeImage(\'edit\')">×</span>' +
                              '</div>'
                            : '<div id="prize-edit-upload-area" class="prize-upload-area" onclick="document.getElementById(\'prize-edit-file\').click()">' +
                                '<i class="fas fa-cloud-upload-alt prize-upload-icon"></i>' +
                                '<div class="prize-upload-text">点击此处选择图片</div>' +
                              '</div>'
                        ) +
                        '<input type="hidden" name="imageid" id="prize-edit-imageid" value="' + (rowData.imageid || rowData.image || '') + '">' +
                        '<div class="prize-upload-tip">尺寸400x400像素，图片大小请勿超过2M</div>' +
                    '</div></div>' +
                    '<div class="prize-footer">' +
                        '<button type="button" class="btn-prize-cancel" onclick="layui.layer.closeAll()">取消</button>' +
                        '<button type="button" class="btn-prize-save" id="btn-update-prize"><i class="fas fa-check"></i> 保存</button>' +
                    '</div>' +
                '</form></div>',
                success: function(layero) {
                    layui.form.render(null, 'editPrize');
                    layero.find('#btn-update-prize').on('click', function() {
                        var data = {
                            sort: parseInt(layero.find('[name="sort"]').val()) || 0,
                            type: layero.find('[name="type_text"]').val(),
                            prizename: layero.find('[name="prizename"]').val(),
                            num: parseInt(layero.find('[name="num"]').val()) || 1,
                            draw_count: parseInt(layero.find('[name="draw_count"]').val()) || 1,
                            imageid: layero.find('[name="imageid"]').val()
                        };
                        if (!data.prizename) return layui.layer.msg('请输入奖品名称', { icon: 2 });
                        Api.updateLotteryPrize(actId, rowData.id, data).then(function() {
                            layui.layer.closeAll();
                            layui.table.reload('lottery-prizes-table');
                            try { layui.table.reload('screen-prizes-table'); } catch(e) {}
                            layui.layer.msg('更新成功', { icon: 1 });
                        });
                    });
                }
            });
        },

        _addScreenPrize: function() {
            console.log('_addScreenPrize called');
            try {
                LotteryPage._addPrize();
            } catch (err) {
                console.error('Error in _addScreenPrize:', err);
                layui.layer.msg('添加奖品失败: ' + err.message, { icon: 2 });
            }
        },

        // ========== 奖品图片上传辅助方法 ==========
        _handlePrizeImageUpload: function(input, mode) {
            mode = mode || 'add';
            var file = input.files[0];
            if (!file) return;

            if (!file.type.match('image/jpeg') && !file.type.match('image/png') && !file.type.match('image/gif') && !file.type.match('image/webp')) {
                layui.layer.msg('请上传 JPEG、PNG、GIF 或 WebP 格式的图片', { icon: 2 });
                input.value = '';
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                layui.layer.msg('图片大小不能超过2M', { icon: 2 });
                input.value = '';
                return;
            }

            var reader = new FileReader();
            reader.onload = function(e) {
                var uploadArea = document.getElementById('prize-' + mode + '-upload-area');
                var previewWrap = document.getElementById('prize-' + mode + '-preview');
                var previewImg = document.getElementById('prize-' + mode + '-preview-img');
                var imageidInput = document.getElementById('prize-' + mode + '-imageid');

                if (uploadArea) uploadArea.style.display = 'none';
                previewImg.src = e.target.result;
                previewWrap.style.display = 'inline-block';

                var formData = new FormData();
                formData.append('file', file);
                Api.uploadImage(formData).then(function(res) {
                    if (res.data && res.data.url) {
                        imageidInput.value = res.data.url;
                    } else {
                        imageidInput.value = e.target.result;
                    }
                }).catch(function() {
                    imageidInput.value = e.target.result;
                    layui.layer.msg('图片已选择，将使用本地预览', { icon: 0, time: 1500 });
                });
            };
            reader.readAsDataURL(file);
        },

        _removePrizeImage: function(mode) {
            var previewWrap = document.getElementById('prize-' + mode + '-preview');
            var previewImg = document.getElementById('prize-' + mode + '-preview-img');
            var uploadArea = document.getElementById('prize-' + mode + '-upload-area');
            var imageidInput = document.getElementById('prize-' + mode + '-imageid');
            var fileInput = document.getElementById('prize-' + mode + '-file');

            if (previewImg) previewImg.src = '';
            if (previewWrap) previewWrap.style.display = 'none';
            if (uploadArea) uploadArea.style.display = '';
            if (imageidInput) imageidInput.value = '';
            if (fileInput) fileInput.value = '';
        },

        // ========== 中奖名单 ==========
        renderWinners: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card">' +
                '<div class="card-title"><span>中奖名单</span>' +
                '<div><button class="btn btn-primary btn-sm" onclick="Api.exportLottery(' + actId + ')"><i class="fas fa-download"></i> 导出</button>' +
                '<button class="btn btn-danger btn-sm" style="margin-left:8px;" onclick="LotteryPage._clearWinners()"><i class="fas fa-trash"></i> 清空</button></div></div>' +
                '<table id="lottery-winners-table" lay-filter="lotteryWinnersTable"></table></div>';
            Layout.setContent(html);
            this._initWinnersTable(actId);
        },

        _initWinnersTable: function(actId) {
            if (!document.getElementById('lotteryWinnersBar')) {
                var s = document.createElement('script');
                s.type = 'text/html'; s.id = 'lotteryWinnersBar';
                s.innerHTML = '<a class="layui-btn layui-btn-xs" lay-event="give"><i class="fas fa-gift"></i> 发奖</a>' +
                    '<a class="layui-btn layui-btn-warm layui-btn-xs" lay-event="cancel"><i class="fas fa-undo"></i> 取消</a>' +
                    '<a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="del"><i class="fas fa-trash"></i> 删除</a>';
                document.body.appendChild(s);
            }

            layui.table.render({
                elem: '#lottery-winners-table',
                url: '/api/hd/lottery/' + actId + '/winners',
                headers: { 'Hd-Token': Api.getToken() },
                parseData: function(res) {
                    var list = res.data ? (res.data.list || res.data) : [];
                    return { code: 0, msg: '', count: res.data ? (res.data.count || list.length) : 0, data: list };
                },
                cols: [[
                    { field: 'id', title: 'ID', width: 60 },
                    { field: 'avatar', title: '头像', width: 60, templet: function(d) {
                        return d.avatar ? '<img src="' + d.avatar + '" style="width:32px;height:32px;border-radius:50%;">' : '<i class="fas fa-user-circle" style="font-size:24px;color:#ddd;"></i>';
                    }},
                    { field: 'nickname', title: '昵称', width: 120 },
                    { field: 'phone', title: '手机号', width: 120 },
                    { field: 'prize_name', title: '奖品', width: 120 },
                    { field: 'round_name', title: '轮次', width: 120 },
                    { field: 'show_type', title: '类型', width: 90, templet: function(d) {
                        var map = { normal: '普通', '3d': '3D', egg: '砸金蛋', box: '抽奖箱' };
                        return map[d.show_type] || d.show_type || '-';
                    }},
                    { field: 'status', title: '状态', width: 80, templet: function(d) {
                        return d.status == 3 ? '<span style="color:#43A047">已发奖</span>' : '<span style="color:#FF9800">未发奖</span>';
                    }},
                    { field: 'win_time', title: '中奖时间', width: 160, templet: function(d) {
                        if (!d.win_time) return '-';
                        var t = new Date(d.win_time * 1000);
                        var pad = function(n) { return n < 10 ? '0' + n : n; };
                        return t.getFullYear() + '-' + pad(t.getMonth()+1) + '-' + pad(t.getDate()) + ' ' + pad(t.getHours()) + ':' + pad(t.getMinutes());
                    }},
                    { title: '操作', width: 180, toolbar: '#lotteryWinnersBar' }
                ]],
                page: true,
                limit: 50,
                text: { none: '暂无中奖记录' }
            });

            layui.table.on('tool(lotteryWinnersTable)', function(obj) {
                if (obj.event === 'give') {
                    Api.giveLotteryPrize(actId, obj.data.id).then(function() {
                        layui.table.reload('lottery-winners-table');
                        layui.layer.msg('发奖成功', { icon: 1 });
                    });
                } else if (obj.event === 'cancel') {
                    Api.cancelLotteryPrize(actId, obj.data.id).then(function() {
                        layui.table.reload('lottery-winners-table');
                        layui.layer.msg('已取消发奖', { icon: 1 });
                    });
                } else if (obj.event === 'del') {
                    layui.layer.confirm('确定删除该中奖记录？', { icon: 3 }, function(idx) {
                        Api.deleteLotteryWinner(actId, obj.data.id).then(function() {
                            layui.layer.close(idx);
                            obj.del();
                            layui.layer.msg('删除成功', { icon: 1 });
                        });
                    });
                }
            });
        },

        _clearWinners: function() {
            var actId = App.getCurrentActivityId();
            layui.layer.confirm('确定清空所有中奖记录？此操作不可恢复！', { icon: 3 }, function(idx) {
                Api.clearLotteryWinners(actId).then(function() {
                    layui.layer.close(idx);
                    layui.table.reload('lottery-winners-table');
                    layui.layer.msg('已清空', { icon: 1 });
                });
            });
        },

        // ========== 内定名单 ==========
        renderDesignated: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card">' +
                '<div class="card-title"><span>内定名单</span>' +
                '<button class="btn btn-primary btn-sm" onclick="LotteryPage._addDesignated()"><i class="fas fa-plus"></i> 添加内定</button></div>' +
                '<table id="lottery-designated-table" lay-filter="lotteryDesignatedTable"></table></div>';
            Layout.setContent(html);
            this._initDesignatedTable(actId);
        },

        _initDesignatedTable: function(actId) {
            if (!document.getElementById('lotteryDesignatedBar')) {
                var s = document.createElement('script');
                s.type = 'text/html'; s.id = 'lotteryDesignatedBar';
                s.innerHTML = '<a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="cancel"><i class="fas fa-times"></i> 取消内定</a>';
                document.body.appendChild(s);
            }

            layui.table.render({
                elem: '#lottery-designated-table',
                url: '/api/hd/lottery/' + actId + '/designated',
                headers: { 'Hd-Token': Api.getToken() },
                parseData: function(res) {
                    var list = res.data ? (res.data.list || res.data) : [];
                    return { code: 0, msg: '', count: res.data ? (res.data.count || list.length) : 0, data: list };
                },
                cols: [[
                    { field: 'id', title: 'ID', width: 60 },
                    { field: 'avatar', title: '头像', width: 60, templet: function(d) {
                        return d.avatar ? '<img src="' + d.avatar + '" style="width:32px;height:32px;border-radius:50%;">' : '<i class="fas fa-user-circle" style="font-size:24px;color:#ddd;"></i>';
                    }},
                    { field: 'nickname', title: '昵称', width: 140 },
                    { field: 'phone', title: '手机号', width: 130 },
                    { field: 'prize_name', title: '关联奖品', width: 140 },
                    { field: 'designated', title: '内定类型', width: 100, templet: function(d) {
                        return d.designated == 2 ? '<span style="color:#E53935">必中</span>' : '<span style="color:#999">不中</span>';
                    }},
                    { title: '操作', width: 120, toolbar: '#lotteryDesignatedBar' }
                ]],
                page: true,
                limit: 50,
                text: { none: '暂无内定记录' }
            });

            layui.table.on('tool(lotteryDesignatedTable)', function(obj) {
                if (obj.event === 'cancel') {
                    layui.layer.confirm('确定取消该内定？', { icon: 3 }, function(idx) {
                        Api.cancelLotteryDesignated(actId, obj.data.id).then(function() {
                            layui.layer.close(idx);
                            obj.del();
                            layui.layer.msg('已取消内定', { icon: 1 });
                        });
                    });
                }
            });
        },

        _addDesignated: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return;

            // 先加载奖品列表用于下拉
            Api.getLotteryPrizes(actId).then(function(res) {
                var prizes = (res.data && res.data.list) ? res.data.list : [];
                var prizeOpts = '<option value="0">不绑定奖品</option>';
                prizes.forEach(function(p) {
                    prizeOpts += '<option value="' + p.id + '">' + (p.prizename || p.name || '未命名') + '</option>';
                });

                layui.layer.open({
                    type: 1, title: '添加内定', area: ['520px', '460px'],
                    content: '<div style="padding:20px;"><form class="layui-form" lay-filter="addDesignated">' +
                        '<div class="layui-form-item"><label class="layui-form-label">搜索用户</label><div class="layui-input-block">' +
                        '<input type="text" id="designated-search" class="layui-input" placeholder="输入昵称/手机号搜索">' +
                        '<div id="designated-user-list" style="max-height:120px;overflow-y:auto;border:1px solid #e6e6e6;margin-top:5px;display:none;"></div>' +
                        '<input type="hidden" id="designated-user-id" value="0">' +
                        '</div></div>' +
                        '<div class="layui-form-item"><label class="layui-form-label">内定类型</label><div class="layui-input-block">' +
                        '<select name="designated"><option value="2">必中</option><option value="3">不中</option></select></div></div>' +
                        '<div class="layui-form-item"><label class="layui-form-label">关联奖品</label><div class="layui-input-block">' +
                        '<select name="prize_id">' + prizeOpts + '</select></div></div>' +
                        '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" id="btn-save-designated"><i class="fas fa-save"></i> 保存</button></div></div>' +
                        '</form></div>',
                    success: function(layero) {
                        layui.form.render(null, 'addDesignated');
                        var searchTimer = null;
                        layero.find('#designated-search').on('input', function() {
                            var kw = this.value.trim();
                            clearTimeout(searchTimer);
                            if (!kw) { layero.find('#designated-user-list').hide(); return; }
                            searchTimer = setTimeout(function() {
                                Api.searchLotteryUsers(actId, kw).then(function(res) {
                                    var users = (res.data && res.data.list) ? res.data.list : [];
                                    var listEl = layero.find('#designated-user-list');
                                    if (users.length === 0) { listEl.html('<div style="padding:8px;color:#999;">未找到用户</div>').show(); return; }
                                    var h = '';
                                    users.forEach(function(u) {
                                        h += '<div class="designated-user-item" data-id="' + u.id + '" style="padding:6px 10px;cursor:pointer;border-bottom:1px solid #f0f0f0;">' +
                                            '<strong>' + (u.nickname || u.signname || '-') + '</strong> <span style="color:#999;">' + (u.phone || '') + '</span></div>';
                                    });
                                    listEl.html(h).show();
                                    listEl.find('.designated-user-item').on('click', function() {
                                        var uid = $(this).data('id');
                                        var name = $(this).find('strong').text();
                                        layero.find('#designated-user-id').val(uid);
                                        layero.find('#designated-search').val(name);
                                        listEl.hide();
                                    });
                                });
                            }, 300);
                        });

                        layero.find('#btn-save-designated').on('click', function() {
                            var userId = parseInt(layero.find('#designated-user-id').val()) || 0;
                            if (!userId) return layui.layer.msg('请搜索并选择用户', { icon: 2 });
                            var data = {
                                user_id: userId,
                                designated: parseInt(layero.find('[name="designated"]').val()),
                                prize_id: parseInt(layero.find('[name="prize_id"]').val()) || 0
                            };
                            Api.addLotteryDesignated(actId, data).then(function() {
                                layui.layer.closeAll();
                                layui.table.reload('lottery-designated-table');
                                layui.layer.msg('内定设置成功', { icon: 1 });
                            });
                        });
                    }
                });
            });
        },

        // ========== 抽奖主题 ==========
        renderThemes: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');
            var html = '<div class="content-card"><div class="card-title">抽奖主题</div>' +
                '<div id="lottery-themes-list" class="loading-state"><i class="fas fa-spinner"></i> 加载中...</div></div>';
            Layout.setContent(html);

            Api.getLotteryThemes(actId).then(function(res) {
                var list = res.data || [];
                if (!Array.isArray(list)) list = [];
                var container = document.getElementById('lottery-themes-list');
                if (list.length === 0) {
                    container.innerHTML = '<div class="empty-state"><i class="fas fa-palette"></i><p>暂无主题</p></div>';
                    return;
                }
                var h = '<div class="switch-grid">';
                list.forEach(function(t) {
                    h += '<div class="switch-card"><div class="sw-icon"><i class="fas fa-palette"></i></div><div class="sw-info"><div class="sw-name">' + (t.theme_name || t.name || '未命名') + '</div><div class="sw-desc">' + (t.theme_path || '') + '</div></div></div>';
                });
                h += '</div>';
                container.innerHTML = h;
            }).catch(function() {
                var c = document.getElementById('lottery-themes-list');
                if (c) c.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>加载失败</p></div>';
            });
        },

        // ========== 手机端抽奖 ==========
        renderChoujiang: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');
            var html = '<div class="content-card"><div class="card-title">手机端抽奖配置</div>' +
                '<form class="layui-form" lay-filter="choujiang">' +
                '<div class="layui-form-item"><label class="layui-form-label">功能开关</label><div class="layui-input-block"><input type="checkbox" name="cj_enabled" lay-skin="switch" lay-text="开启|关闭"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">抽奖次数</label><div class="layui-input-inline"><input type="number" name="cj_times" class="layui-input" value="1" min="1"></div><div class="layui-form-mid">次/人</div></div>' +
                '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" onclick="LotteryPage._saveChoujiang()"><i class="fas fa-save"></i> 保存</button></div></div>' +
                '</form></div>';
            Layout.setContent(html);
            layui.form.render(null, 'choujiang');

            Api.getChoujiangConfig(actId).then(function(res) {
                var d = res.data || {};
                if (d.enabled !== undefined) {
                    var el = document.querySelector('[name="cj_enabled"]');
                    if (el) { el.checked = !!d.enabled; layui.form.render('checkbox', 'choujiang'); }
                }
                if (d.times) document.querySelector('[name="cj_times"]').value = d.times;
            }).catch(function() {});
        },

        _saveChoujiang: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return;
            var data = {
                enabled: document.querySelector('[name="cj_enabled"]').checked ? 1 : 0,
                times: parseInt(document.querySelector('[name="cj_times"]').value) || 1
            };
            Api.updateChoujiangConfig(actId, data).then(function() {
                layui.layer.msg('保存成功', { icon: 1 });
            });
        },

        // ========== 手机端抽奖 - 抽奖设置 ==========
        renderChoujiangSettings: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');
            var html = '<div class="content-card"><div class="card-title">手机端抽奖设置</div>' +
                '<form class="layui-form" lay-filter="choujiangSettings">' +
                '<div class="layui-form-item"><label class="layui-form-label">功能开关</label><div class="layui-input-block"><input type="checkbox" name="cj_enabled" lay-skin="switch" lay-text="开启|关闭"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">抽奖次数</label><div class="layui-input-inline"><input type="number" name="cj_times" class="layui-input" value="1" min="1"></div><div class="layui-form-mid">次/人</div></div>' +
                '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" onclick="LotteryPage._saveChoujiangSettings()"><i class="fas fa-save"></i> 保存</button></div></div>' +
                '</form></div>';
            Layout.setContent(html);
            layui.form.render(null, 'choujiangSettings');

            Api.getChoujiangConfig(actId).then(function(res) {
                var d = res.data || {};
                if (d.enabled !== undefined) {
                    var el = document.querySelector('[name="cj_enabled"]');
                    if (el) { el.checked = !!d.enabled; layui.form.render('checkbox', 'choujiangSettings'); }
                }
                if (d.times) document.querySelector('[name="cj_times"]').value = d.times;
            }).catch(function() {});
        },

        _saveChoujiangSettings: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return;
            var data = {
                enabled: document.querySelector('[name="cj_enabled"]').checked ? 1 : 0,
                times: parseInt(document.querySelector('[name="cj_times"]').value) || 1
            };
            Api.updateChoujiangConfig(actId, data).then(function() {
                layui.layer.msg('保存成功', { icon: 1 });
            });
        },

        // ========== 手机端抽奖 - 奖品设置 ==========
        renderChoujiangPrizes: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title"><span>手机端抽奖奖品设置</span><button class="btn btn-primary btn-sm" onclick="LotteryPage._addChoujiangPrize()"><i class="fas fa-plus"></i> 添加奖品</button></div>' +
                '<table id="choujiang-prizes-table" lay-filter="choujiangPrizesTable"></table></div>';
            Layout.setContent(html);
        },

        _addChoujiangPrize: function() {
            var actId = App.getCurrentActivityId();
            layui.layer.open({
                type: 1, title: '添加奖品', area: ['500px', '480px'],
                content: '<div style="padding:20px;"><form class="layui-form" lay-filter="addChoujiangPrize">' +
                    '<div class="layui-form-item"><label class="layui-form-label">奖品名称</label><div class="layui-input-block"><input type="text" name="prizename" class="layui-input" required></div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">奖品级别</label><div class="layui-input-block"><select name="type"><option value="0">普通奖品（无级别）</option><option value="1">一等奖</option><option value="2">二等奖</option><option value="3">三等奖</option><option value="4">四等奖</option><option value="5">五等奖</option></select></div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">数量</label><div class="layui-input-inline"><input type="number" name="num" class="layui-input" value="1" min="1"></div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">奖品图片</label><div class="layui-input-block"><input type="text" name="imageid" class="layui-input" placeholder="图片URL"></div></div>' +
                    '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" id="btn-save-choujiang-prize"><i class="fas fa-save"></i> 保存</button></div></div>' +
                    '</form></div>',
                success: function(layero) {
                    layui.form.render(null, 'addChoujiangPrize');
                    layero.find('#btn-save-choujiang-prize').on('click', function() {
                        var data = {
                            prizename: layero.find('[name="prizename"]').val(),
                            type: layero.find('[name="type"]').val(),
                            num: parseInt(layero.find('[name="num"]').val()) || 1,
                            imageid: layero.find('[name="imageid"]').val()
                        };
                        if (!data.prizename) return layui.layer.msg('请输入奖品名称', { icon: 2 });
                        Api.createLotteryPrize(actId, data).then(function() {
                            layui.layer.closeAll();
                            layui.table.reload('choujiang-prizes-table');
                            layui.layer.msg('添加成功', { icon: 1 });
                        });
                    });
                }
            });
        },

        // ========== 手机端抽奖 - 中奖名单 ==========
        renderChoujiangWinners: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card">' +
                '<div class="card-title"><span>手机端抽奖中奖名单</span>' +
                '<div><button class="btn btn-primary btn-sm" onclick="Api.exportLottery(' + actId + ')"><i class="fas fa-download"></i> 导出</button>' +
                '<button class="btn btn-danger btn-sm" style="margin-left:8px;" onclick="LotteryPage._clearChoujiangWinners()"><i class="fas fa-trash"></i> 清空</button></div></div>' +
                '<table id="choujiang-winners-table" lay-filter="choujiangWinnersTable"></table></div>';
            Layout.setContent(html);
        },

        _clearChoujiangWinners: function() {
            var actId = App.getCurrentActivityId();
            layui.layer.confirm('确定清空所有中奖记录？此操作不可恢复！', { icon: 3 }, function(idx) {
                Api.clearLotteryWinners(actId).then(function() {
                    layui.layer.close(idx);
                    layui.table.reload('choujiang-winners-table');
                    layui.layer.msg('已清空', { icon: 1 });
                });
            });
        },

        // ========== 手机端抽奖 - 内定名单 ==========
        renderChoujiangDesignated: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card">' +
                '<div class="card-title"><span>手机端抽奖内定名单</span>' +
                '<button class="btn btn-primary btn-sm" onclick="LotteryPage._addChoujiangDesignated()"><i class="fas fa-plus"></i> 添加内定</button></div>' +
                '<table id="choujiang-designated-table" lay-filter="choujiangDesignatedTable"></table></div>';
            Layout.setContent(html);
        },

        _addChoujiangDesignated: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return;

            // 先加载奖品列表用于下拉
            Api.getLotteryPrizes(actId).then(function(res) {
                var prizes = (res.data && res.data.list) ? res.data.list : [];
                var prizeOpts = '<option value="0">不绑定奖品</option>';
                prizes.forEach(function(p) {
                    prizeOpts += '<option value="' + p.id + '">' + (p.prizename || p.name || '未命名') + '</option>';
                });

                layui.layer.open({
                    type: 1, title: '添加内定', area: ['520px', '460px'],
                    content: '<div style="padding:20px;"><form class="layui-form" lay-filter="addChoujiangDesignated">' +
                        '<div class="layui-form-item"><label class="layui-form-label">搜索用户</label><div class="layui-input-block">' +
                        '<input type="text" id="choujiang-designated-search" class="layui-input" placeholder="输入昵称/手机号搜索">' +
                        '<div id="choujiang-designated-user-list" style="max-height:120px;overflow-y:auto;border:1px solid #e6e6e6;margin-top:5px;display:none;"></div>' +
                        '<input type="hidden" id="choujiang-designated-user-id" value="0">' +
                        '</div></div>' +
                        '<div class="layui-form-item"><label class="layui-form-label">内定类型</label><div class="layui-input-block">' +
                        '<select name="designated"><option value="2">必中</option><option value="3">不中</option></select></div></div>' +
                        '<div class="layui-form-item"><label class="layui-form-label">关联奖品</label><div class="layui-input-block">' +
                        '<select name="prize_id">' + prizeOpts + '</select></div></div>' +
                        '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" id="btn-save-choujiang-designated"><i class="fas fa-save"></i> 保存</button></div></div>' +
                        '</form></div>',
                    success: function(layero) {
                        layui.form.render(null, 'addChoujiangDesignated');
                        var searchTimer = null;
                        layero.find('#choujiang-designated-search').on('input', function() {
                            var kw = this.value.trim();
                            clearTimeout(searchTimer);
                            if (!kw) { layero.find('#choujiang-designated-user-list').hide(); return; }
                            searchTimer = setTimeout(function() {
                                Api.searchLotteryUsers(actId, kw).then(function(res) {
                                    var users = (res.data && res.data.list) ? res.data.list : [];
                                    var listEl = layero.find('#choujiang-designated-user-list');
                                    if (users.length === 0) { listEl.html('<div style="padding:8px;color:#999;">未找到用户</div>').show(); return; }
                                    var h = '';
                                    users.forEach(function(u) {
                                        h += '<div class="choujiang-designated-user-item" data-id="' + u.id + '" style="padding:6px 10px;cursor:pointer;border-bottom:1px solid #f0f0f0;">' +
                                            '<strong>' + (u.nickname || u.signname || '-') + '</strong> <span style="color:#999;">' + (u.phone || '') + '</span></div>';
                                    });
                                    listEl.html(h).show();
                                    listEl.find('.choujiang-designated-user-item').on('click', function() {
                                        var uid = $(this).data('id');
                                        var name = $(this).find('strong').text();
                                        layero.find('#choujiang-designated-user-id').val(uid);
                                        layero.find('#choujiang-designated-search').val(name);
                                        listEl.hide();
                                    });
                                });
                            }, 300);
                        });

                        layero.find('#btn-save-choujiang-designated').on('click', function() {
                            var userId = parseInt(layero.find('#choujiang-designated-user-id').val()) || 0;
                            if (!userId) return layui.layer.msg('请搜索并选择用户', { icon: 2 });
                            var data = {
                                user_id: userId,
                                designated: parseInt(layero.find('[name="designated"]').val()),
                                prize_id: parseInt(layero.find('[name="prize_id"]').val()) || 0
                            };
                            Api.addLotteryDesignated(actId, data).then(function() {
                                layui.layer.closeAll();
                                layui.table.reload('choujiang-designated-table');
                                layui.layer.msg('内定设置成功', { icon: 1 });
                            });
                        });
                    }
                });
            });
        },

        // ========== 导入抽奖 ==========
        renderImport: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');
            var html = '<div class="content-card"><div class="card-title">导入抽奖名单</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">批量导入</label><div class="layui-input-block"><textarea id="import-text" class="layui-textarea" placeholder="每行一个，格式：姓名,手机号" rows="8"></textarea></div></div>' +
                '<div class="layui-form-item"><div class="layui-input-block">' +
                '<button class="btn btn-primary" onclick="LotteryPage._doImport()"><i class="fas fa-upload"></i> 导入</button>' +
                '<button class="btn btn-danger" style="margin-left:10px;" onclick="LotteryPage._clearImport()"><i class="fas fa-trash"></i> 清空</button>' +
                '</div></div></div>';
            Layout.setContent(html);
        },

        _doImport: function() {
            var actId = App.getCurrentActivityId();
            var text = document.getElementById('import-text').value;
            if (!text.trim()) return layui.layer.msg('请输入导入数据', { icon: 2 });
            Api.batchImport(actId, { data: text }).then(function() {
                layui.layer.msg('导入成功', { icon: 1 });
            });
        },

        _clearImport: function() {
            var actId = App.getCurrentActivityId();
            layui.layer.confirm('确定清空导入名单？', { icon: 3 }, function(idx) {
                Api.clearImportList(actId).then(function() {
                    layui.layer.close(idx);
                    layui.layer.msg('已清空', { icon: 1 });
                });
            });
        },

        // ========== 幸运手机号 ==========
        renderLuckyPhone: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card">' +
                '<div class="card-title"><span>幸运手机号记录</span></div>' +
                '<table id="lucky-phone-table" lay-filter="luckyPhoneTable"></table></div>';
            Layout.setContent(html);

            layui.table.render({
                elem: '#lucky-phone-table',
                url: '/api/hd/lottery/' + actId + '/lucky-phone',
                headers: { 'Hd-Token': Api.getToken() },
                parseData: function(res) {
                    var list = res.data ? (res.data.list || res.data) : [];
                    return { code: 0, msg: '', count: res.data ? (res.data.count || list.length) : 0, data: list };
                },
                cols: [[
                    { field: 'id', title: 'ID', width: 70 },
                    { field: 'nickname', title: '昵称', width: 140 },
                    { field: 'phone', title: '手机号', width: 140 },
                    { field: 'prize_name', title: '奖品', width: 160, templet: function(d) { return d.prize_name || '-'; } },
                    { field: 'status', title: '状态', width: 100, templet: function(d) {
                        return d.status == 3 ? '<span style="color:#43A047">已发奖</span>' : '<span style="color:#FF9800">未发奖</span>';
                    }},
                    { field: 'win_time', title: '中奖时间', width: 160, templet: function(d) {
                        if (!d.win_time) return '-';
                        var t = new Date(d.win_time * 1000);
                        var pad = function(n) { return n < 10 ? '0' + n : n; };
                        return t.getFullYear() + '-' + pad(t.getMonth()+1) + '-' + pad(t.getDate()) + ' ' + pad(t.getHours()) + ':' + pad(t.getMinutes());
                    }}
                ]],
                page: true,
                limit: 50,
                text: { none: '暂无幸运手机号记录' }
            });
        },

        // ========== 幸运号码 ==========
        renderLuckyNumber: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card">' +
                '<div class="card-title">幸运号码设置</div>' +
                '<form class="layui-form" lay-filter="luckyNumber" style="padding:20px;">' +
                '<div class="layui-form-item"><label class="layui-form-label">功能开关</label><div class="layui-input-block"><input type="checkbox" name="ln_enabled" lay-skin="switch" lay-text="开启|关闭"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">号码位数</label><div class="layui-input-inline"><input type="number" name="ln_digit" class="layui-input" value="3" min="1" max="6"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">最小值</label><div class="layui-input-inline"><input type="number" name="ln_min" class="layui-input" value="0"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">最大值</label><div class="layui-input-inline"><input type="number" name="ln_max" class="layui-input" value="999"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">奖品名称</label><div class="layui-input-block"><input type="text" name="ln_prize_name" class="layui-input" placeholder="幸运奖品名称"></div></div>' +
                '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" onclick="LotteryPage._saveLuckyNumber()"><i class="fas fa-save"></i> 保存配置</button></div></div>' +
                '</form></div>' +
                '<div class="content-card" style="margin-top:16px;">' +
                '<div class="card-title">中奖记录</div>' +
                '<table id="lucky-number-records-table" lay-filter="luckyNumberRecordsTable"></table></div>';
            Layout.setContent(html);
            layui.form.render(null, 'luckyNumber');

            // 加载配置
            Api.getLuckyNumberConfig(actId).then(function(res) {
                var d = res.data || {};
                var el = document.querySelector('[name="ln_enabled"]');
                if (el) { el.checked = !!d.enabled; layui.form.render('checkbox', 'luckyNumber'); }
                if (d.digit !== undefined) document.querySelector('[name="ln_digit"]').value = d.digit;
                if (d.min !== undefined) document.querySelector('[name="ln_min"]').value = d.min;
                if (d.max !== undefined) document.querySelector('[name="ln_max"]').value = d.max;
                if (d.prize_name) document.querySelector('[name="ln_prize_name"]').value = d.prize_name;
            }).catch(function() {});

            // 加载记录表格
            layui.table.render({
                elem: '#lucky-number-records-table',
                url: '/api/hd/lottery/' + actId + '/lucky-number/records',
                headers: { 'Hd-Token': Api.getToken() },
                parseData: function(res) {
                    var list = res.data ? (res.data.list || res.data) : [];
                    return { code: 0, msg: '', count: res.data ? (res.data.count || list.length) : 0, data: list };
                },
                cols: [[
                    { field: 'id', title: 'ID', width: 70 },
                    { field: 'nickname', title: '昵称', width: 140 },
                    { field: 'verify_code', title: '幸运号码', width: 120 },
                    { field: 'status', title: '状态', width: 100, templet: function(d) {
                        return d.status == 3 ? '<span style="color:#43A047">已发奖</span>' : '<span style="color:#FF9800">未发奖</span>';
                    }},
                    { field: 'win_time', title: '中奖时间', width: 160, templet: function(d) {
                        if (!d.win_time) return '-';
                        var t = new Date(d.win_time * 1000);
                        var pad = function(n) { return n < 10 ? '0' + n : n; };
                        return t.getFullYear() + '-' + pad(t.getMonth()+1) + '-' + pad(t.getDate()) + ' ' + pad(t.getHours()) + ':' + pad(t.getMinutes());
                    }}
                ]],
                page: true,
                limit: 50,
                text: { none: '暂无中奖记录' }
            });
        },

        _saveLuckyNumber: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return;
            var data = {
                enabled: document.querySelector('[name="ln_enabled"]').checked ? 1 : 0,
                digit: parseInt(document.querySelector('[name="ln_digit"]').value) || 3,
                min: parseInt(document.querySelector('[name="ln_min"]').value) || 0,
                max: parseInt(document.querySelector('[name="ln_max"]').value) || 999,
                prize_name: document.querySelector('[name="ln_prize_name"]').value
            };
            Api.updateLuckyNumberConfig(actId, data).then(function() {
                layui.layer.msg('保存成功', { icon: 1 });
            });
        },

        // ========== 幸运号码 - 幸运号码设置 ==========
        renderLuckyNumberSettings: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card">' +
                '<div class="card-title">幸运号码设置</div>' +
                '<form class="layui-form" lay-filter="luckyNumberSettings" style="padding:20px;">' +
                '<div class="layui-form-item"><label class="layui-form-label">功能开关</label><div class="layui-input-block"><input type="checkbox" name="ln_enabled" lay-skin="switch" lay-text="开启|关闭"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">号码位数</label><div class="layui-input-inline"><input type="number" name="ln_digit" class="layui-input" value="3" min="1" max="6"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">最小值</label><div class="layui-input-inline"><input type="number" name="ln_min" class="layui-input" value="0"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">最大值</label><div class="layui-input-inline"><input type="number" name="ln_max" class="layui-input" value="999"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">奖品名称</label><div class="layui-input-block"><input type="text" name="ln_prize_name" class="layui-input" placeholder="幸运奖品名称"></div></div>' +
                '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" onclick="LotteryPage._saveLuckyNumberSettings()"><i class="fas fa-save"></i> 保存配置</button></div></div>' +
                '</form></div>';
            Layout.setContent(html);
            layui.form.render(null, 'luckyNumberSettings');

            // 加载配置
            Api.getLuckyNumberConfig(actId).then(function(res) {
                var d = res.data || {};
                var el = document.querySelector('[name="ln_enabled"]');
                if (el) { el.checked = !!d.enabled; layui.form.render('checkbox', 'luckyNumberSettings'); }
                if (d.digit !== undefined) document.querySelector('[name="ln_digit"]').value = d.digit;
                if (d.min !== undefined) document.querySelector('[name="ln_min"]').value = d.min;
                if (d.max !== undefined) document.querySelector('[name="ln_max"]').value = d.max;
                if (d.prize_name) document.querySelector('[name="ln_prize_name"]').value = d.prize_name;
            }).catch(function() {});
        },

        _saveLuckyNumberSettings: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return;
            var data = {
                enabled: document.querySelector('[name="ln_enabled"]').checked ? 1 : 0,
                digit: parseInt(document.querySelector('[name="ln_digit"]').value) || 3,
                min: parseInt(document.querySelector('[name="ln_min"]').value) || 0,
                max: parseInt(document.querySelector('[name="ln_max"]').value) || 999,
                prize_name: document.querySelector('[name="ln_prize_name"]').value
            };
            Api.updateLuckyNumberConfig(actId, data).then(function() {
                layui.layer.msg('保存成功', { icon: 1 });
            });
        },

        // ========== 幸运号码 - 中奖号码 ==========
        renderLuckyNumberWinners: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card">' +
                '<div class="card-title"><span>中奖号码</span>' +
                '<div><button class="btn btn-primary btn-sm" onclick="Api.exportLottery(' + actId + ')"><i class="fas fa-download"></i> 导出</button>' +
                '<button class="btn btn-danger btn-sm" style="margin-left:8px;" onclick="LotteryPage._clearLuckyNumberWinners()"><i class="fas fa-trash"></i> 清空</button></div></div>' +
                '<table id="lucky-number-winners-table" lay-filter="luckyNumberWinnersTable"></table></div>';
            Layout.setContent(html);

            // 加载中奖号码表格
            layui.table.render({
                elem: '#lucky-number-winners-table',
                url: '/api/hd/lottery/' + actId + '/lucky-number/records',
                headers: { 'Hd-Token': Api.getToken() },
                parseData: function(res) {
                    var list = res.data ? (res.data.list || res.data) : [];
                    return { code: 0, msg: '', count: res.data ? (res.data.count || list.length) : 0, data: list };
                },
                cols: [[
                    { field: 'id', title: 'ID', width: 70 },
                    { field: 'nickname', title: '昵称', width: 140 },
                    { field: 'verify_code', title: '幸运号码', width: 120 },
                    { field: 'status', title: '状态', width: 100, templet: function(d) {
                        return d.status == 3 ? '<span style="color:#43A047">已发奖</span>' : '<span style="color:#FF9800">未发奖</span>';
                    }},
                    { field: 'win_time', title: '中奖时间', width: 160, templet: function(d) {
                        if (!d.win_time) return '-';
                        var t = new Date(d.win_time * 1000);
                        var pad = function(n) { return n < 10 ? '0' + n : n; };
                        return t.getFullYear() + '-' + pad(t.getMonth()+1) + '-' + pad(t.getDate()) + ' ' + pad(t.getHours()) + ':' + pad(t.getMinutes());
                    }}
                ]],
                page: true,
                limit: 50,
                text: { none: '暂无中奖号码记录' }
            });
        },

        _clearLuckyNumberWinners: function() {
            var actId = App.getCurrentActivityId();
            layui.layer.confirm('确定清空所有中奖号码记录？此操作不可恢复！', { icon: 3 }, function(idx) {
                Api.clearLotteryWinners(actId).then(function() {
                    layui.layer.close(idx);
                    layui.table.reload('lucky-number-winners-table');
                    layui.layer.msg('已清空', { icon: 1 });
                });
            });
        },

        // ========== 幸运号码 - 内定号码 ==========
        renderLuckyNumberDesignated: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card">' +
                '<div class="card-title"><span>内定号码</span>' +
                '<button class="btn btn-primary btn-sm" onclick="LotteryPage._addLuckyNumberDesignated()"><i class="fas fa-plus"></i> 添加内定</button></div>' +
                '<table id="lucky-number-designated-table" lay-filter="luckyNumberDesignatedTable"></table></div>';
            Layout.setContent(html);
        },

        _addLuckyNumberDesignated: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return;

            layui.layer.open({
                type: 1, title: '添加内定号码', area: ['520px', '400px'],
                content: '<div style="padding:20px;"><form class="layui-form" lay-filter="addLuckyNumberDesignated">' +
                    '<div class="layui-form-item"><label class="layui-form-label">搜索用户</label><div class="layui-input-block">' +
                    '<input type="text" id="lucky-number-designated-search" class="layui-input" placeholder="输入昵称/手机号搜索">' +
                    '<div id="lucky-number-designated-user-list" style="max-height:120px;overflow-y:auto;border:1px solid #e6e6e6;margin-top:5px;display:none;"></div>' +
                    '<input type="hidden" id="lucky-number-designated-user-id" value="0">' +
                    '</div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">内定号码</label><div class="layui-input-block">' +
                    '<input type="text" name="designated_number" class="layui-input" placeholder="请输入内定号码" required></div></div>' +
                    '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" id="btn-save-lucky-number-designated"><i class="fas fa-save"></i> 保存</button></div></div>' +
                    '</form></div>',
                success: function(layero) {
                    layui.form.render(null, 'addLuckyNumberDesignated');
                    var searchTimer = null;
                    layero.find('#lucky-number-designated-search').on('input', function() {
                        var kw = this.value.trim();
                        clearTimeout(searchTimer);
                        if (!kw) { layero.find('#lucky-number-designated-user-list').hide(); return; }
                        searchTimer = setTimeout(function() {
                            Api.searchLotteryUsers(actId, kw).then(function(res) {
                                var users = (res.data && res.data.list) ? res.data.list : [];
                                var listEl = layero.find('#lucky-number-designated-user-list');
                                if (users.length === 0) { listEl.html('<div style="padding:8px;color:#999;">未找到用户</div>').show(); return; }
                                var h = '';
                                users.forEach(function(u) {
                                    h += '<div class="lucky-number-designated-user-item" data-id="' + u.id + '" style="padding:6px 10px;cursor:pointer;border-bottom:1px solid #f0f0f0;">' +
                                        '<strong>' + (u.nickname || u.signname || '-') + '</strong> <span style="color:#999;">' + (u.phone || '') + '</span></div>';
                                });
                                listEl.html(h).show();
                                listEl.find('.lucky-number-designated-user-item').on('click', function() {
                                    var uid = $(this).data('id');
                                    var name = $(this).find('strong').text();
                                    layero.find('#lucky-number-designated-user-id').val(uid);
                                    layero.find('#lucky-number-designated-search').val(name);
                                    listEl.hide();
                                });
                            });
                        }, 300);
                    });

                    layero.find('#btn-save-lucky-number-designated').on('click', function() {
                        var userId = parseInt(layero.find('#lucky-number-designated-user-id').val()) || 0;
                        var designatedNumber = layero.find('[name="designated_number"]').val().trim();
                        if (!userId) return layui.layer.msg('请搜索并选择用户', { icon: 2 });
                        if (!designatedNumber) return layui.layer.msg('请输入内定号码', { icon: 2 });
                        var data = {
                            user_id: userId,
                            designated_number: designatedNumber
                        };
                        Api.addLotteryDesignated(actId, data).then(function() {
                            layui.layer.closeAll();
                            layui.table.reload('lucky-number-designated-table');
                            layui.layer.msg('内定设置成功', { icon: 1 });
                        });
                    });
                }
            });
        },

        // ========== 幸运手机号 - 奖品设置 ==========
        renderLuckyPhonePrizes: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title"><span>幸运手机号奖品设置</span><button class="btn btn-primary btn-sm" onclick="LotteryPage._addLuckyPhonePrize()"><i class="fas fa-plus"></i> 添加奖品</button></div>' +
                '<table id="lucky-phone-prizes-table" lay-filter="luckyPhonePrizesTable"></table></div>';
            Layout.setContent(html);
        },

        _addLuckyPhonePrize: function() {
            var actId = App.getCurrentActivityId();
            layui.layer.open({
                type: 1, title: '添加奖品', area: ['500px', '480px'],
                content: '<div style="padding:20px;"><form class="layui-form" lay-filter="addLuckyPhonePrize">' +
                    '<div class="layui-form-item"><label class="layui-form-label">奖品名称</label><div class="layui-input-block"><input type="text" name="prizename" class="layui-input" required></div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">奖品级别</label><div class="layui-input-block"><select name="type"><option value="0">普通奖品（无级别）</option><option value="1">一等奖</option><option value="2">二等奖</option><option value="3">三等奖</option><option value="4">四等奖</option><option value="5">五等奖</option></select></div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">数量</label><div class="layui-input-inline"><input type="number" name="num" class="layui-input" value="1" min="1"></div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">奖品图片</label><div class="layui-input-block"><input type="text" name="imageid" class="layui-input" placeholder="图片URL"></div></div>' +
                    '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" id="btn-save-lucky-phone-prize"><i class="fas fa-save"></i> 保存</button></div></div>' +
                    '</form></div>',
                success: function(layero) {
                    layui.form.render(null, 'addLuckyPhonePrize');
                    layero.find('#btn-save-lucky-phone-prize').on('click', function() {
                        var data = {
                            prizename: layero.find('[name="prizename"]').val(),
                            type: layero.find('[name="type"]').val(),
                            num: parseInt(layero.find('[name="num"]').val()) || 1,
                            imageid: layero.find('[name="imageid"]').val()
                        };
                        if (!data.prizename) return layui.layer.msg('请输入奖品名称', { icon: 2 });
                        Api.createLotteryPrize(actId, data).then(function() {
                            layui.layer.closeAll();
                            layui.table.reload('lucky-phone-prizes-table');
                            layui.layer.msg('添加成功', { icon: 1 });
                        });
                    });
                }
            });
        },

        // ========== 幸运手机号 - 中奖名单 ==========
        renderLuckyPhoneWinners: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card">' +
                '<div class="card-title"><span>幸运手机号中奖名单</span>' +
                '<div><button class="btn btn-primary btn-sm" onclick="Api.exportLottery(' + actId + ')"><i class="fas fa-download"></i> 导出</button>' +
                '<button class="btn btn-danger btn-sm" style="margin-left:8px;" onclick="LotteryPage._clearLuckyPhoneWinners()"><i class="fas fa-trash"></i> 清空</button></div></div>' +
                '<table id="lucky-phone-winners-table" lay-filter="luckyPhoneWinnersTable"></table></div>';
            Layout.setContent(html);
        },

        _clearLuckyPhoneWinners: function() {
            var actId = App.getCurrentActivityId();
            layui.layer.confirm('确定清空所有中奖记录？此操作不可恢复！', { icon: 3 }, function(idx) {
                Api.clearLotteryWinners(actId).then(function() {
                    layui.layer.close(idx);
                    layui.table.reload('lucky-phone-winners-table');
                    layui.layer.msg('已清空', { icon: 1 });
                });
            });
        },

        // ========== 幸运手机号 - 内定名单 ==========
        renderLuckyPhoneDesignated: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card">' +
                '<div class="card-title"><span>幸运手机号内定名单</span>' +
                '<button class="btn btn-primary btn-sm" onclick="LotteryPage._addLuckyPhoneDesignated()"><i class="fas fa-plus"></i> 添加内定</button></div>' +
                '<table id="lucky-phone-designated-table" lay-filter="luckyPhoneDesignatedTable"></table></div>';
            Layout.setContent(html);
        },

        _addLuckyPhoneDesignated: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return;

            // 先加载奖品列表用于下拉
            Api.getLotteryPrizes(actId).then(function(res) {
                var prizes = (res.data && res.data.list) ? res.data.list : [];
                var prizeOpts = '<option value="0">不绑定奖品</option>';
                prizes.forEach(function(p) {
                    prizeOpts += '<option value="' + p.id + '">' + (p.prizename || p.name || '未命名') + '</option>';
                });

                layui.layer.open({
                    type: 1, title: '添加内定', area: ['520px', '460px'],
                    content: '<div style="padding:20px;"><form class="layui-form" lay-filter="addLuckyPhoneDesignated">' +
                        '<div class="layui-form-item"><label class="layui-form-label">搜索用户</label><div class="layui-input-block">' +
                        '<input type="text" id="lucky-phone-designated-search" class="layui-input" placeholder="输入昵称/手机号搜索">' +
                        '<div id="lucky-phone-designated-user-list" style="max-height:120px;overflow-y:auto;border:1px solid #e6e6e6;margin-top:5px;display:none;"></div>' +
                        '<input type="hidden" id="lucky-phone-designated-user-id" value="0">' +
                        '</div></div>' +
                        '<div class="layui-form-item"><label class="layui-form-label">内定类型</label><div class="layui-input-block">' +
                        '<select name="designated"><option value="2">必中</option><option value="3">不中</option></select></div></div>' +
                        '<div class="layui-form-item"><label class="layui-form-label">关联奖品</label><div class="layui-input-block">' +
                        '<select name="prize_id">' + prizeOpts + '</select></div></div>' +
                        '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" id="btn-save-lucky-phone-designated"><i class="fas fa-save"></i> 保存</button></div></div>' +
                        '</form></div>',
                    success: function(layero) {
                        layui.form.render(null, 'addLuckyPhoneDesignated');
                        var searchTimer = null;
                        layero.find('#lucky-phone-designated-search').on('input', function() {
                            var kw = this.value.trim();
                            clearTimeout(searchTimer);
                            if (!kw) { layero.find('#lucky-phone-designated-user-list').hide(); return; }
                            searchTimer = setTimeout(function() {
                                Api.searchLotteryUsers(actId, kw).then(function(res) {
                                    var users = (res.data && res.data.list) ? res.data.list : [];
                                    var listEl = layero.find('#lucky-phone-designated-user-list');
                                    if (users.length === 0) { listEl.html('<div style="padding:8px;color:#999;">未找到用户</div>').show(); return; }
                                    var h = '';
                                    users.forEach(function(u) {
                                        h += '<div class="lucky-phone-designated-user-item" data-id="' + u.id + '" style="padding:6px 10px;cursor:pointer;border-bottom:1px solid #f0f0f0;">' +
                                            '<strong>' + (u.nickname || u.signname || '-') + '</strong> <span style="color:#999;">' + (u.phone || '') + '</span></div>';
                                    });
                                    listEl.html(h).show();
                                    listEl.find('.lucky-phone-designated-user-item').on('click', function() {
                                        var uid = $(this).data('id');
                                        var name = $(this).find('strong').text();
                                        layero.find('#lucky-phone-designated-user-id').val(uid);
                                        layero.find('#lucky-phone-designated-search').val(name);
                                        listEl.hide();
                                    });
                                });
                            }, 300);
                        });

                        layero.find('#btn-save-lucky-phone-designated').on('click', function() {
                            var userId = parseInt(layero.find('#lucky-phone-designated-user-id').val()) || 0;
                            if (!userId) return layui.layer.msg('请搜索并选择用户', { icon: 2 });
                            var data = {
                                user_id: userId,
                                designated: parseInt(layero.find('[name="designated"]').val()),
                                prize_id: parseInt(layero.find('[name="prize_id"]').val()) || 0
                            };
                            Api.addLotteryDesignated(actId, data).then(function() {
                                layui.layer.closeAll();
                                layui.table.reload('lucky-phone-designated-table');
                                layui.layer.msg('内定设置成功', { icon: 1 });
                            });
                        });
                    }
                });
            });
        },

        // ========== 大屏抽奖 - 抽奖设置 ==========
        renderScreenSettings: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title">抽奖设置</div>' +
                '<div class="two-column-layout">' +
                // 左侧：设置表单
                '<div class="column-left"><form class="layui-form" lay-filter="screenSettings">' +
                '<div class="form-section"><div class="section-title">显示方式</div>' +
                '<div class="layui-form-item"><div class="layui-input-block">' +
                '<input type="radio" name="display_mode" value="nickname" title="昵称" lay-filter="display">' +
                '<input type="radio" name="display_mode" value="name" title="姓名" style="margin-left:15px;">' +
                '<input type="radio" name="display_mode" value="name_phone" title="姓名+手机号" style="margin-left:15px;">' +
                '</div></div>' +
                '</div>' +
                '<div class="form-section"><div class="section-title">选择模板</div>' +
                '<div class="layui-form-item"><div class="layui-input-block">' +
                '<input type="radio" name="template" value="gold" title="金色" lay-filter="template" style="color:#D4AF37;">' +
                '<input type="radio" name="template" value="red" title="红色" style="margin-left:15px;color:#E53935;">' +
                '<input type="radio" name="template" value="blue" title="蓝色" style="margin-left:15px;color:#1E88E5;">' +
                '<input type="radio" name="template" value="gray" title="灰色" style="margin-left:15px;color:#757575;">' +
                '</div></div>' +
                '</div>' +
                '<div class="form-section"><div class="section-title">背景设置</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">背景图片</label><div class="layui-input-block">' +
                '<button type="button" class="btn btn-default" id="btn-upload-bg" style="margin-right:8px;"><i class="fas fa-upload"></i> 上传背景</button>' +
                '<button type="button" class="btn btn-default" id="btn-select-bg"><i class="fas fa-image"></i> 选择背景</button>' +
                '</div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label"></label><div class="layui-input-block">' +
                '<button type="button" class="btn btn-default" id="btn-reset-bg"><i class="fas fa-undo"></i> 恢复默认</button>' +
                '</div></div>' +
                '</div>' +
                '<div class="form-section"><div class="section-title">基本设置</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">功能开关</label><div class="layui-input-block"><input type="checkbox" name="screen_enabled" lay-skin="switch" lay-text="开启|关闭"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">抽奖模式</label><div class="layui-input-block"><select name="screen_mode"><option value="normal">普通模式</option><option value="3d">3D模式</option><option value="egg">砸金蛋</option><option value="box">抽奖箱</option></select></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">动画时长</label><div class="layui-input-inline"><input type="number" name="screen_animation_duration" class="layui-input" placeholder="3000" min="1000" max="10000"></div><div class="layui-form-mid">毫秒</div></div>' +
                '</div>' +
                '<div class="layui-form-item" style="margin-top:20px;"><button type="button" class="btn btn-primary" id="btn-save-screen-settings" onclick="LotteryPage._saveScreenSettings()"><i class="fas fa-save"></i> 保存设置</button></div>' +
                '</form></div>' +
                // 右侧：预览区域
                '<div class="column-right"><div class="section-title">预览</div>' +
                '<div id="preview-vip-tip" style="background:#FFF3CD;color:#856404;padding:10px 16px;border-radius:6px;border:1px solid #FFEAA7;font-size:12px;margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;">' +
                '<span><i class="fas fa-crown" style="color:#FFC107;margin-right:6px;"></i>升级VIP后自定义背景才正式生效！</span>' +
                '<a href="/admin/vip/upgrade" target="_blank" style="color:#1E88E5;text-decoration:none;font-weight:bold;">&lt;去升级VIP&gt;</a>' +
                '</div>' +
                '<div id="lottery-preview" style="background:#000;border-radius:8px;padding:30px 20px;text-align:center;border:2px solid #333;min-height:400px;display:flex;flex-direction:column;align-items:center;justify-content:space-between;position:relative;overflow:hidden;">' +
                '<div id="preview-background" style="position:absolute;top:0;left:0;width:100%;height:100%;z-index:1;opacity:0.6;"></div>' +
                '<div id="preview-content" style="position:relative;z-index:2;width:100%;display:flex;flex-direction:column;align-items:center;justify-content:center;flex-grow:1;">' +
                '<div id="preview-title" style="font-size:32px;font-weight:bold;margin-bottom:30px;text-shadow:0 2px 4px rgba(0,0,0,0.5);">一等奖</div>' +
                '<div id="preview-center" style="width:180px;height:180px;border-radius:50%;border:4px solid #D4AF37;display:flex;align-items:center;justify-content:center;margin:20px 0;background:rgba(255,255,255,0.1);box-shadow:0 8px 32px rgba(0,0,0,0.3);">' +
                '<i class="fas fa-gift" style="font-size:64px;color:#E53935;"></i>' +
                '</div>' +
                '<button id="preview-start-btn" style="background:#D4AF37;color:#fff;border:none;border-radius:25px;padding:12px 40px;font-size:18px;font-weight:bold;cursor:pointer;margin-top:30px;box-shadow:0 4px 15px rgba(212,175,55,0.4);transition:all 0.3s;">开始</button>' +
                '</div>' +
                '</div>' +
                '<div style="margin-top:16px;font-size:12px;color:#999;text-align:center;">预览区域实时展示抽奖效果</div>' +
                '</div>' +
                '</div></div>';

            Layout.setContent(html);
            this._initScreenSettings(actId);
        },

        _initScreenSettings: function(actId) {
            var self = this;
            
            // 添加预览区域CSS动画样式
            if (!document.getElementById('preview-styles')) {
                var style = document.createElement('style');
                style.id = 'preview-styles';
                style.textContent = `
                    @keyframes pulse {
                        0% { opacity: 0.7; transform: scale(1); }
                        100% { opacity: 1; transform: scale(1.02); }
                    }
                    @keyframes glow {
                        0% { box-shadow: 0 0 10px var(--glow-color, #FFD700); }
                        100% { box-shadow: 0 0 30px var(--glow-color, #FFD700); }
                    }
                    #preview-start-btn {
                        transition: all 0.3s ease;
                    }
                    #preview-start-btn:hover {
                        transform: translateY(-2px);
                    }
                `;
                document.head.appendChild(style);
            }
            
            // 初始化表单
            layui.form.render(null, 'screenSettings');
            
            // 恢复默认背景按钮事件
            document.getElementById('btn-reset-bg')?.addEventListener('click', function() {
                self._resetBackground(actId);
            });
            
            // 上传背景按钮事件
            document.getElementById('btn-upload-bg')?.addEventListener('click', function() {
                self._uploadBackground(actId);
            });
            
            // 选择背景按钮事件
            document.getElementById('btn-select-bg')?.addEventListener('click', function() {
                self._selectBackground(actId);
            });
            
            // 显示方式变化时更新预览
            layui.form.on('radio(display)', function(data) {
                self._updatePreview();
            });
            
            // 模板变化时更新预览
            layui.form.on('radio(template)', function(data) {
                self._updatePreview();
            });
            
            // 加载现有配置
            this._loadScreenSettings(actId);
        },

        _loadScreenSettings: function(actId) {
            var self = this;
            Api.getScreenSettings(actId).then(function(res) {
                if (res.code === 0 && res.data) {
                    var data = res.data;
                    // 设置表单值
                    layui.form.val('screenSettings', {
                        display_mode: data.display_mode || 'nickname',
                        template: data.template || 'gold',
                        screen_enabled: data.screen_enabled === 1,
                        screen_mode: data.screen_mode || 'normal',
                        screen_animation_duration: data.screen_animation_duration || 3000
                    });
                    // 更新预览
                    self._updatePreview();
                }
            }).catch(function(err) {
                console.error('Failed to load screen settings:', err);
            });
        },

        _updatePreview: function() {
            // 获取当前选中的模板和显示方式
            var template = document.querySelector('[name="template"]:checked')?.value || 'gold';
            var displayMode = document.querySelector('[name="display_mode"]:checked')?.value || 'nickname';
            
            // 显示方式文本映射
            var displayTexts = {
                nickname: '用户昵称',
                name: '张三',
                name_phone: '张三 138****8888'
            };
            var displayText = displayTexts[displayMode] || displayTexts.nickname;
            
            // 模板配置
            var templateConfigs = {
                gold: {
                    background: 'radial-gradient(circle at 50% 50%, #FFD700 0%, #D4AF37 30%, #B8860B 70%, #8B6914 100%)',
                    titleColor: '#FFD700',
                    centerBorderColor: '#D4AF37',
                    centerBg: 'rgba(255, 215, 0, 0.1)',
                    buttonBg: '#D4AF37',
                    buttonShadow: 'rgba(212, 175, 55, 0.4)',
                    buttonHoverBg: '#FFD700',
                    effect: 'radial-gradient(circle at 50% 50%, rgba(255, 215, 0, 0.3) 0%, transparent 70%)',
                    lightColor: '#FFD700',
                    hasLightEffect: false,
                    style: '奢华金色科技抽奖风格'
                },
                red: {
                    background: 'radial-gradient(circle at 50% 50%, #8B0000 0%, #B22222 30%, #DC143C 70%, #FF0000 100%)',
                    titleColor: '#FF6B6B',
                    centerBorderColor: '#E53935',
                    centerBg: 'rgba(229, 57, 53, 0.1)',
                    buttonBg: '#E53935',
                    buttonShadow: 'rgba(229, 57, 53, 0.4)',
                    buttonHoverBg: '#FF5252',
                    effect: 'radial-gradient(circle at 50% 50%, rgba(255, 107, 107, 0.3) 0%, transparent 70%)',
                    lightColor: '#FF6B6B',
                    hasLightEffect: true,
                    style: '喜庆红色抽奖风格'
                },
                blue: {
                    background: 'radial-gradient(circle at 50% 50%, #0D47A1 0%, #1565C0 30%, #1976D2 70%, #1E88E5 100%)',
                    titleColor: '#64B5F6',
                    centerBorderColor: '#1E88E5',
                    centerBg: 'rgba(30, 136, 229, 0.1)',
                    buttonBg: '#1E88E5',
                    buttonShadow: 'rgba(30, 136, 229, 0.4)',
                    buttonHoverBg: '#42A5F5',
                    effect: 'radial-gradient(circle at 50% 50%, rgba(100, 181, 246, 0.3) 0%, transparent 70%)',
                    lightColor: '#64B5F6',
                    hasLightEffect: true,
                    style: '科技蓝色动感抽奖风格'
                },
                gray: {
                    background: 'radial-gradient(circle at 50% 50%, #000000 0%, #212121 30%, #424242 70%, #616161 100%)',
                    titleColor: '#E0E0E0',
                    centerBorderColor: '#BDBDBD',
                    centerBg: 'rgba(189, 189, 189, 0.1)',
                    buttonBg: '#757575',
                    buttonShadow: 'rgba(117, 117, 117, 0.4)',
                    buttonHoverBg: '#9E9E9E',
                    effect: 'radial-gradient(circle at 50% 50%, rgba(187, 134, 252, 0.3) 0%, transparent 70%)',
                    lightColor: '#BB86FC',
                    hasLightEffect: false,
                    style: '简约科技风抽奖风格'
                }
            };
            
            var config = templateConfigs[template] || templateConfigs.gold;
            
            // 更新背景
            var previewBg = document.getElementById('preview-background');
            if (previewBg) {
                previewBg.style.background = config.background;
                // 添加动态效果
                previewBg.style.backgroundImage = config.background + ', ' + config.effect;
                previewBg.style.animation = 'pulse 3s infinite alternate';
            }
            
            // 更新标题颜色
            var previewTitle = document.getElementById('preview-title');
            if (previewTitle) {
                previewTitle.style.color = config.titleColor;
            }
            
            // 更新中心区域
            var previewCenter = document.getElementById('preview-center');
            if (previewCenter) {
                previewCenter.style.borderColor = config.centerBorderColor;
                previewCenter.style.background = config.centerBg;
                previewCenter.style.boxShadow = '0 8px 32px rgba(0,0,0,0.3), 0 0 20px ' + config.titleColor.replace(')', ', 0.3)').replace('rgb', 'rgba');
                
                // 添加显示文本
                var displayElement = document.getElementById('preview-display-text');
                if (!displayElement) {
                    displayElement = document.createElement('div');
                    displayElement.id = 'preview-display-text';
                    displayElement.style.position = 'absolute';
                    displayElement.style.bottom = '-40px';
                    displayElement.style.left = '0';
                    displayElement.style.width = '100%';
                    displayElement.style.textAlign = 'center';
                    displayElement.style.fontSize = '14px';
                    displayElement.style.fontWeight = 'bold';
                    displayElement.style.color = config.titleColor;
                    previewCenter.appendChild(displayElement);
                }
                displayElement.style.color = config.titleColor;
                displayElement.textContent = displayText;
            }
            
            // 更新开始按钮
            var previewBtn = document.getElementById('preview-start-btn');
            if (previewBtn) {
                previewBtn.style.background = config.buttonBg;
                previewBtn.style.boxShadow = '0 4px 15px ' + config.buttonShadow;
                previewBtn.title = config.style;
                // 添加悬停效果
                previewBtn.onmouseenter = function() {
                    this.style.background = config.buttonHoverBg;
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 6px 20px ' + config.buttonShadow;
                };
                previewBtn.onmouseleave = function() {
                    this.style.background = config.buttonBg;
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '0 4px 15px ' + config.buttonShadow;
                };
            }
            
            // 处理特效
            this._removeParticleEffect();
            this._removeLightEffect();
            
            if (template === 'gray') {
                this._addParticleEffect();
            } else if (config.hasLightEffect) {
                this._addLightEffect(config.lightColor);
            }
        },
        
        _addParticleEffect: function() {
            var previewBg = document.getElementById('preview-background');
            if (!previewBg || previewBg.dataset.particlesAdded) return;
            
            // 创建粒子效果
            var particles = document.createElement('div');
            particles.id = 'particle-effect';
            particles.innerHTML = `
                <style>
                    @keyframes particleMove {
                        0% { transform: translate(0, 0) scale(1); opacity: 0.8; }
                        100% { transform: translate(var(--x, 100px), var(--y, 100px)) scale(0); opacity: 0; }
                    }
                </style>
                <div style="position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;">
                    ${Array.from({length: 20}, (_, i) => 
                        `<div style="position:absolute;top:${50 + Math.sin(i)*30}%;left:${50 + Math.cos(i)*30}%;width:2px;height:2px;background:#BB86FC;border-radius:50%;animation:particleMove ${2 + i*0.2}s infinite linear;--x:${Math.sin(i)*100}px;--y:${Math.cos(i)*100}px;"></div>`
                    ).join('')}
                </div>
            `;
            previewBg.appendChild(particles);
            previewBg.dataset.particlesAdded = 'true';
        },
        
        _removeParticleEffect: function() {
            var particles = document.getElementById('particle-effect');
            if (particles) {
                particles.remove();
            }
            var previewBg = document.getElementById('preview-background');
            if (previewBg) {
                delete previewBg.dataset.particlesAdded;
            }
        },
        
        _addLightEffect: function(lightColor) {
            var previewBg = document.getElementById('preview-background');
            if (!previewBg || previewBg.dataset.lightsAdded) return;
            
            // 创建放射状光线特效
            var lights = document.createElement('div');
            lights.id = 'light-effect';
            
            // 生成16条光线
            var lightHTML = '<div style="position:absolute;top:0;left:0;width:100%;height:100%;pointer-events:none;z-index:2;">';
            for (var i = 0; i < 16; i++) {
                var angleDeg = i * 22.5;
                var length = 100;
                lightHTML += '<div style="position:absolute;top:50%;left:50%;width:2px;height:' + length + 'px;background:linear-gradient(to bottom, transparent, ' + lightColor + ');transform-origin:0 0;transform:translate(-50%, -50%) rotate(' + angleDeg + 'deg);animation:lightPulse 2s infinite linear;animation-delay:' + (i*0.1) + 's;--angle:' + angleDeg + 'deg;"></div>';
            }
            lightHTML += '</div>';
            
            lights.innerHTML = '<style>@keyframes lightPulse { 0% { opacity: 0.2; transform: translate(-50%, -50%) rotate(var(--angle)) scaleY(0.3); } 50% { opacity: 0.6; transform: translate(-50%, -50%) rotate(var(--angle)) scaleY(1); } 100% { opacity: 0.2; transform: translate(-50%, -50%) rotate(var(--angle)) scaleY(0.3); } }</style>' + lightHTML;
            
            previewBg.appendChild(lights);
            previewBg.dataset.lightsAdded = 'true';
        },
        
        _removeLightEffect: function() {
            var lights = document.getElementById('light-effect');
            if (lights) {
                lights.remove();
            }
            var previewBg = document.getElementById('preview-background');
            if (previewBg) {
                delete previewBg.dataset.lightsAdded;
            }
        },
        
        _resetBackground: function(actId) {
            layui.layer.confirm('确定恢复默认背景？', { icon: 3 }, function(idx) {
                Api.resetScreenBackground(actId).then(function() {
                    layui.layer.close(idx);
                    layui.layer.msg('恢复默认背景成功', { icon: 1 });
                }).catch(function(err) {
                    layui.layer.msg('恢复失败：' + (err.message || '未知错误'), { icon: 2 });
                });
            });
        },

        _uploadBackground: function(actId) {
            layui.layer.msg('上传背景功能开发中', { icon: 0 });
            // 这里可以集成文件上传组件
        },

        _selectBackground: function(actId) {
            layui.layer.msg('选择背景功能开发中', { icon: 0 });
            // 这里可以打开背景库选择
        },

        _saveScreenSettings: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) {
                layui.layer.msg('请先选择活动', { icon: 2 });
                return;
            }
            
            var formData = {
                display_mode: document.querySelector('[name="display_mode"]:checked')?.value || 'nickname',
                template: document.querySelector('[name="template"]:checked')?.value || 'gold',
                screen_enabled: document.querySelector('[name="screen_enabled"]').checked ? 1 : 0,
                screen_mode: document.querySelector('[name="screen_mode"]').value,
                screen_animation_duration: parseInt(document.querySelector('[name="screen_animation_duration"]').value) || 3000
            };
            
            layui.layer.load(2);
            Api.saveScreenSettings(actId, formData).then(function(res) {
                layui.layer.closeAll('loading');
                if (res.code === 0) {
                    layui.layer.msg('保存成功', { icon: 1 });
                } else {
                    layui.layer.msg(res.msg || '保存失败', { icon: 2 });
                }
            }).catch(function(err) {
                layui.layer.closeAll('loading');
                layui.layer.msg('保存失败：' + (err.message || '网络错误'), { icon: 2 });
            });
        },

        // ========== 大屏抽奖 - 奖品设置 ==========
        renderScreenPrizes: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title"><span>大屏抽奖奖品设置</span><button class="btn btn-primary btn-sm" onclick="LotteryPage._addScreenPrize()"><i class="fas fa-plus"></i> 添加奖品</button></div>' +
                '<table id="screen-prizes-table" lay-filter="screenPrizesTable"></table></div>';
            Layout.setContent(html);
            this._initScreenPrizesTable(actId);
        },

        _initScreenPrizesTable: function(actId) {
            if (!document.getElementById('screenPrizesBar')) {
                var s = document.createElement('script');
                s.type = 'text/html'; s.id = 'screenPrizesBar';
                s.innerHTML = '<a class="layui-btn layui-btn-xs" lay-event="edit"><i class="fas fa-edit"></i> 编辑</a><a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="del"><i class="fas fa-trash"></i> 删除</a>';
                document.body.appendChild(s);
            }

            layui.table.render({
                elem: '#screen-prizes-table',
                url: '/api/hd/lottery/' + actId + '/prizes',
                headers: { 'Authorization': 'Bearer ' + Api.getToken() },
                parseData: function(res) {
                    console.log('大屏奖品API响应:', res);
                    var list = res.data ? (res.data.list || res.data) : [];
                    console.log('处理后的奖品列表:', list);
                    return { code: 0, msg: '', count: Array.isArray(list) ? list.length : 0, data: list };
                },
                cols: [[
                    { field: 'imageid', title: '图片', width: 80, templet: function(d) {
                        console.log('大屏奖品图片数据:', d);
                        var img = '';
                        // 检查可能的图片字段
                        var possibleFields = ['imageid', 'image', 'pic', 'picurl', 'picture', 'img', 'imgurl'];
                        for (var i = 0; i < possibleFields.length; i++) {
                            var field = possibleFields[i];
                            if (d[field]) {
                                if (field === 'imageid' && !isNaN(d[field]) && d[field] > 0) {
                                    img = '/huodong/imageproxy.php?id=' + d[field];
                                    break;
                                } else if (typeof d[field] === 'string' && d[field].trim() !== '') {
                                    img = d[field];
                                    break;
                                }
                            }
                        }
                        console.log('处理后的图片路径:', img, '原始字段值 - imageid:', d.imageid, 'image:', d.image);
                        // 如果图片URL包含 wxhd.eivie.cn 域名，替换为当前域名
                        if (img && typeof img === 'string' && img.includes('wxhd.eivie.cn')) {
                            img = img.replace('https://wxhd.eivie.cn', window.location.origin).replace('http://wxhd.eivie.cn', window.location.origin);
                        }
                        if (img) {
                            // 确保图片路径是完整URL
                            if (img.startsWith('/')) {
                                img = window.location.origin + img;
                            } else if (!img.startsWith('http') && !img.startsWith('//')) {
                                // 相对路径，添加基础URL
                                img = (window.location.origin + '/') + img;
                            }
                            return '<img class="img-preview" src="' + img + '" onerror="console.error(\'图片加载失败:\', this.src); this.style.display=\'none\'; this.parentNode.innerHTML=\'<i class=\\\'fas fa-image\\\' style=\\\'font-size:24px;color:#ddd;\\\'></i>\'">';
                        }
                        return '<i class="fas fa-image" style="font-size:24px;color:#ddd;"></i>';
                    }},
                    { field: 'prizename', title: '奖品名称', width: 160 },
{ field: 'type', title: '奖品级别', width: 100, templet: function(d) {
    console.log('奖品级别数据 type:', d.type, 'raw:', d);
    var map = { 0: '普通奖品（无级别）', 1: '一等奖', 2: '二等奖', 3: '三等奖', 4: '四等奖', 5: '五等奖' };
    // 处理数字字符串和中文级别名称
                        var typeValue = d.type;
                        var result;
                        if (typeValue === null || typeValue === undefined || typeValue === '') {
                            result = '-';
                        } else if (!isNaN(typeValue) && typeValue !== '') {
                            // 数字或数字字符串
                            result = map[parseInt(typeValue, 10)] || typeValue;
                        } else if (typeof typeValue === 'string') {
                            // 检查是否已经是中文级别名称
                            var reverseMap = { '一等奖': 1, '二等奖': 2, '三等奖': 3, '四等奖': 4, '五等奖': 5 };
                            if (reverseMap[typeValue]) {
                                result = typeValue;
                            } else {
                                result = typeValue;
                            }
                        } else {
                            result = typeValue || '-';
                        }
                        console.log('映射结果:', result);
                        return result;
                    }},
                    { field: 'num', title: '数量', width: 80 },
                    { field: 'leftnum', title: '剩余', width: 80 },
                    { field: 'draw_count', title: '每次抽取', width: 100 },
                    { title: '操作', width: 150, toolbar: '#screenPrizesBar' }
                ]],
                page: false,
                text: { none: '暂无奖品' }
            });

            layui.table.on('tool(screenPrizesTable)', function(obj) {
                console.log('大屏奖品表格工具事件:', obj.event, '奖品数据:', obj.data);
                if (obj.event === 'edit') {
                    console.log('编辑大屏奖品，ID:', obj.data.id, '活动ID:', actId);
                    LotteryPage._editPrize(actId, obj.data);
                }
                else if (obj.event === 'del') {
                    layui.layer.confirm('确定删除该奖品？', { icon: 3 }, function(idx) {
                        console.log('删除大屏奖品: actId=' + actId + ', prizeId=' + obj.data.id);
                        Api.deleteLotteryPrize(actId, obj.data.id).then(function() {
                            layui.layer.close(idx);
                            obj.del();
                            layui.layer.msg('删除成功', { icon: 1 });
                        }).catch(function(err) {
                            layui.layer.close(idx);
                        });
                    });
                }
            });
        },

        // ========== 大屏抽奖 - 中奖名单 ==========
        renderScreenWinners: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card">' +
                '<div class="card-title"><span>大屏抽奖中奖名单</span>' +
                '<div><button class="btn btn-primary btn-sm" onclick="Api.exportLottery(' + actId + ')"><i class="fas fa-download"></i> 导出</button>' +
                '<button class="btn btn-danger btn-sm" style="margin-left:8px;" onclick="LotteryPage._clearScreenWinners()"><i class="fas fa-trash"></i> 清空</button></div></div>' +
                '<table id="screen-winners-table" lay-filter="screenWinnersTable"></table></div>';
            Layout.setContent(html);
        },

        // ========== 大屏抽奖 - 内定名单 ==========
        renderScreenDesignated: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card">' +
                '<div class="card-title"><span>大屏抽奖内定名单</span>' +
                '<button class="btn btn-primary btn-sm" onclick="LotteryPage._addScreenDesignated()"><i class="fas fa-plus"></i> 添加内定</button></div>' +
                '<table id="screen-designated-table" lay-filter="screenDesignatedTable"></table></div>';
            Layout.setContent(html);
        },

        // ========== 导入抽奖 - 抽奖设置 ==========
        renderImportSettings: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title">导入抽奖设置</div>' +
                '<form class="layui-form" lay-filter="importSettings">' +
                '<div class="form-section"><div class="section-title">基本设置</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">功能开关</label><div class="layui-input-block"><input type="checkbox" name="import_enabled" lay-skin="switch" lay-text="开启|关闭"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">抽奖模式</label><div class="layui-input-block"><select name="import_mode"><option value="normal">普通模式</option><option value="3d">3D模式</option><option value="egg">砸金蛋</option><option value="box">抽奖箱</option></select></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">动画时长</label><div class="layui-input-inline"><input type="number" name="import_animation_duration" class="layui-input" placeholder="3000" min="1000" max="10000"></div><div class="layui-form-mid">毫秒</div></div>' +
                '</div>' +
                '<div class="layui-form-item" style="margin-top:20px;"><button type="button" class="btn btn-primary" id="btn-save-import-settings"><i class="fas fa-save"></i> 保存设置</button></div>' +
                '</form></div>';

            Layout.setContent(html);
        },

        // ========== 导入抽奖 - 导入名单 ==========
        renderImportList: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card">' +
                '<div class="card-title"><span>导入名单</span>' +
                '<div><button class="btn btn-primary btn-sm" onclick="LotteryPage._importList()"><i class="fas fa-upload"></i> 导入</button>' +
                '<button class="btn btn-danger btn-sm" style="margin-left:8px;" onclick="LotteryPage._clearImportList()"><i class="fas fa-trash"></i> 清空</button></div></div>' +
                '<table id="import-list-table" lay-filter="importListTable"></table></div>';
            Layout.setContent(html);
        },

        // ========== 导入抽奖 - 奖品设置 ==========
        renderImportPrizes: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title"><span>导入抽奖奖品设置</span><button class="btn btn-primary btn-sm" onclick="LotteryPage._addImportPrize()"><i class="fas fa-plus"></i> 添加奖品</button></div>' +
                '<table id="import-prizes-table" lay-filter="importPrizesTable"></table></div>';
            Layout.setContent(html);
        },

        // ========== 导入抽奖 - 中奖名单 ==========
        renderImportWinners: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card">' +
                '<div class="card-title"><span>导入抽奖中奖名单</span>' +
                '<div><button class="btn btn-primary btn-sm" onclick="Api.exportLottery(' + actId + ')"><i class="fas fa-download"></i> 导出</button>' +
                '<button class="btn btn-danger btn-sm" style="margin-left:8px;" onclick="LotteryPage._clearImportWinners()"><i class="fas fa-trash"></i> 清空</button></div></div>' +
                '<table id="import-winners-table" lay-filter="importWinnersTable"></table></div>';
            Layout.setContent(html);
        },

        // ========== 导入抽奖 - 内定名单 ==========
        renderImportDesignated: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card">' +
                '<div class="card-title"><span>导入抽奖内定名单</span>' +
                '<button class="btn btn-primary btn-sm" onclick="LotteryPage._addImportDesignated()"><i class="fas fa-plus"></i> 添加内定</button></div>' +
                '<table id="import-designated-table" lay-filter="importDesignatedTable"></table></div>';
            Layout.setContent(html);
        },

        // ========== 照片抽奖 - 抽奖设置 ==========
        renderPhotoSettings: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title">照片抽奖设置</div>' +
                '<form class="layui-form" lay-filter="photoSettings">' +
                '<div class="form-section"><div class="section-title">基本设置</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">功能开关</label><div class="layui-input-block"><input type="checkbox" name="photo_enabled" lay-skin="switch" lay-text="开启|关闭"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">展示模式</label><div class="layui-input-block"><select name="photo_mode"><option value="grid">网格模式</option><option value="carousel">轮播模式</option><option value="wall">照片墙</option></select></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">照片尺寸</label><div class="layui-input-inline"><input type="number" name="photo_size" class="layui-input" placeholder="150" min="50" max="300"></div><div class="layui-form-mid">像素</div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">动画效果</label><div class="layui-input-block"><select name="photo_animation"><option value="fade">淡入淡出</option><option value="slide">滑动</option><option value="zoom">缩放</option><option value="flip">翻转</option></select></div></div>' +
                '</div>' +
                '<div class="layui-form-item" style="margin-top:20px;"><button type="button" class="btn btn-primary" id="btn-save-photo-settings"><i class="fas fa-save"></i> 保存设置</button></div>' +
                '</form></div>';

            Layout.setContent(html);
        },

        // ========== 照片抽奖 - 导入照片 ==========
        renderPhotoImport: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card">' +
                '<div class="card-title"><span>导入照片</span>' +
                '<div><button class="btn btn-primary btn-sm" onclick="LotteryPage._importPhotos()"><i class="fas fa-upload"></i> 批量导入</button>' +
                '<button class="btn btn-danger btn-sm" style="margin-left:8px;" onclick="LotteryPage._clearPhotos()"><i class="fas fa-trash"></i> 清空</button></div></div>' +
                '<div class="layui-form-item" style="margin:20px 0;"><label class="layui-form-label">上传照片</label><div class="layui-input-block"><input type="file" name="photo_files" class="layui-input" accept="image/*" multiple></div></div>' +
                '<table id="photo-import-table" lay-filter="photoImportTable"></table></div>';
            Layout.setContent(html);
        },

        // ========== 照片抽奖 - 奖品设置 ==========
        renderPhotoPrizes: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title"><span>照片抽奖奖品设置</span><button class="btn btn-primary btn-sm" onclick="LotteryPage._addPhotoPrize()"><i class="fas fa-plus"></i> 添加奖品</button></div>' +
                '<table id="photo-prizes-table" lay-filter="photoPrizesTable"></table></div>';
            Layout.setContent(html);
        },

        // ========== 照片抽奖 - 中奖名单 ==========
        renderPhotoWinners: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card">' +
                '<div class="card-title"><span>照片抽奖中奖名单</span>' +
                '<div><button class="btn btn-primary btn-sm" onclick="Api.exportLottery(' + actId + ')"><i class="fas fa-download"></i> 导出</button>' +
                '<button class="btn btn-danger btn-sm" style="margin-left:8px;" onclick="LotteryPage._clearPhotoWinners()"><i class="fas fa-trash"></i> 清空</button></div></div>' +
                '<table id="photo-winners-table" lay-filter="photoWinnersTable"></table></div>';
            Layout.setContent(html);
        },

        // ========== 照片抽奖 - 内定名单 ==========
        renderPhotoDesignated: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card">' +
                '<div class="card-title"><span>照片抽奖内定名单</span>' +
                '<button class="btn btn-primary btn-sm" onclick="LotteryPage._addPhotoDesignated()"><i class="fas fa-plus"></i> 添加内定</button></div>' +
                '<table id="photo-designated-table" lay-filter="photoDesignatedTable"></table></div>';
            Layout.setContent(html);
        },

        // ========== 弹幕抽奖 - 抽奖设置 ==========
        renderBarrageSettings: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title">弹幕抽奖设置</div>' +
                '<form class="layui-form" lay-filter="barrageSettings">' +
                '<div class="form-section"><div class="section-title">基本设置</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">功能开关</label><div class="layui-input-block"><input type="checkbox" name="barrage_enabled" lay-skin="switch" lay-text="开启|关闭"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">弹幕速度</label><div class="layui-input-block"><select name="barrage_speed"><option value="slow">慢速</option><option value="normal">中速</option><option value="fast">快速</option></select></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">弹幕密度</label><div class="layui-input-block"><select name="barrage_density"><option value="sparse">稀疏</option><option value="normal">适中</option><option value="dense">密集</option></select></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">颜色模式</label><div class="layui-input-block"><select name="barrage_color"><option value="single">单色</option><option value="multiple">多彩</option><option value="random">随机</option></select></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">字体大小</label><div class="layui-input-inline"><input type="number" name="barrage_font_size" class="layui-input" placeholder="24" min="12" max="48"></div><div class="layui-form-mid">像素</div></div>' +
                '</div>' +
                '<div class="form-section"><div class="section-title">抽奖设置</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">参与方式</label><div class="layui-input-block"><select name="barrage_join_type"><option value="all">所有弹幕</option><option value="keyword">含关键词</option><option value="nickname">指定昵称</option></select></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">关键词</label><div class="layui-input-block"><input type="text" name="barrage_keyword" class="layui-input" placeholder="输入关键词"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">抽奖模式</label><div class="layui-input-block"><select name="barrage_lottery_mode"><option value="real-time">实时抽奖</option><option value="batch">批量抽奖</option></select></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">动画时长</label><div class="layui-input-inline"><input type="number" name="barrage_animation_duration" class="layui-input" placeholder="3000" min="1000" max="10000"></div><div class="layui-form-mid">毫秒</div></div>' +
                '</div>' +
                '<div class="layui-form-item" style="margin-top:20px;"><button type="button" class="btn btn-primary" id="btn-save-barrage-settings"><i class="fas fa-save"></i> 保存设置</button></div>' +
                '</form></div>';

            Layout.setContent(html);
        },

        // ========== 弹幕抽奖 - 奖品设置 ==========
        renderBarragePrizes: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title"><span>弹幕抽奖奖品设置</span><button class="btn btn-primary btn-sm" onclick="LotteryPage._addBarragePrize()"><i class="fas fa-plus"></i> 添加奖品</button></div>' +
                '<table id="barrage-prizes-table" lay-filter="barragePrizesTable"></table></div>';
            Layout.setContent(html);
        },

        // ========== 弹幕抽奖 - 中奖名单 ==========
        renderBarrageWinners: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card">' +
                '<div class="card-title"><span>弹幕抽奖中奖名单</span>' +
                '<div><button class="btn btn-primary btn-sm" onclick="Api.exportLottery(' + actId + ')"><i class="fas fa-download"></i> 导出</button>' +
                '<button class="btn btn-danger btn-sm" style="margin-left:8px;" onclick="LotteryPage._clearBarrageWinners()"><i class="fas fa-trash"></i> 清空</button></div></div>' +
                '<table id="barrage-winners-table" lay-filter="barrageWinnersTable"></table></div>';
            Layout.setContent(html);
        },

        // ========== 抽奖箱 - 抽奖设置 ==========
        renderBoxSettings: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title">抽奖箱设置</div>' +
                '<form class="layui-form" lay-filter="boxSettings">' +
                '<div class="form-section"><div class="section-title">基本设置</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">功能开关</label><div class="layui-input-block"><input type="checkbox" name="box_enabled" lay-skin="switch" lay-text="开启|关闭"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">抽奖箱样式</label><div class="layui-input-block"><select name="box_style"><option value="classic">经典抽奖箱</option><option value="modern">现代抽奖箱</option><option value="digital">数字抽奖箱</option></select></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">动画效果</label><div class="layui-input-block"><select name="box_animation"><option value="shake">摇晃</option><option value="rotate">旋转</option><option value="sparkle">闪烁</option><option value="confetti">彩纸</option></select></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">滚动速度</label><div class="layui-input-block"><select name="box_scroll_speed"><option value="slow">慢速</option><option value="normal">中速</option><option value="fast">快速</option></select></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">滚动时长</label><div class="layui-input-inline"><input type="number" name="box_scroll_duration" class="layui-input" placeholder="3000" min="1000" max="10000"></div><div class="layui-form-mid">毫秒</div></div>' +
                '</div>' +
                '<div class="form-section"><div class="section-title">奖品设置</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">显示方式</label><div class="layui-input-block"><select name="box_prize_display"><option value="name">仅显示名称</option><option value="full">显示详情</option><option value="image">显示图片</option></select></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">排序方式</label><div class="layui-input-block"><select name="box_prize_sort"><option value="default">默认排序</option><option value="random">随机排序</option><option value="value">价值排序</option></select></div></div>' +
                '</div>' +
                '<div class="layui-form-item" style="margin-top:20px;"><button type="button" class="btn btn-primary" id="btn-save-box-settings"><i class="fas fa-save"></i> 保存设置</button></div>' +
                '</form></div>';

            Layout.setContent(html);
        },

        // ========== 抽奖箱 - 奖品设置 ==========
        renderBoxPrizes: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title"><span>抽奖箱奖品设置</span><button class="btn btn-primary btn-sm" onclick="LotteryPage._addBoxPrize()"><i class="fas fa-plus"></i> 添加奖品</button></div>' +
                '<table id="box-prizes-table" lay-filter="boxPrizesTable"></table></div>';
            Layout.setContent(html);
        },

        // ========== 抽奖箱 - 中奖名单 ==========
        renderBoxWinners: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card">' +
                '<div class="card-title"><span>抽奖箱中奖名单</span>' +
                '<div><button class="btn btn-primary btn-sm" onclick="Api.exportLottery(' + actId + ')"><i class="fas fa-download"></i> 导出</button>' +
                '<button class="btn btn-danger btn-sm" style="margin-left:8px;" onclick="LotteryPage._clearBoxWinners()"><i class="fas fa-trash"></i> 清空</button></div></div>' +
                '<table id="box-winners-table" lay-filter="boxWinnersTable"></table></div>';
            Layout.setContent(html);
        },

        // ========== 抽奖箱 - 内定名单 ==========
        renderBoxDesignated: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card">' +
                '<div class="card-title"><span>抽奖箱内定名单</span>' +
                '<button class="btn btn-primary btn-sm" onclick="LotteryPage._addBoxDesignated()"><i class="fas fa-plus"></i> 添加内定</button></div>' +
                '<table id="box-designated-table" lay-filter="boxDesignatedTable"></table></div>';
            Layout.setContent(html);
        },

        // ========== 砸金蛋 - 抽奖设置 ==========
        renderEggSettings: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title">砸金蛋设置</div>' +
                '<form class="layui-form" lay-filter="eggSettings">' +
                '<div class="form-section"><div class="section-title">基本设置</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">功能开关</label><div class="layui-input-block"><input type="checkbox" name="egg_enabled" lay-skin="switch" lay-text="开启|关闭"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">金蛋数量</label><div class="layui-input-inline"><input type="number" name="egg_count" class="layui-input" placeholder="6" min="1" max="20"></div><div class="layui-form-mid">个</div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">金蛋样式</label><div class="layui-input-block"><select name="egg_style"><option value="classic">经典金蛋</option><option value="modern">现代金蛋</option><option value="colorful">彩色金蛋</option></select></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">砸蛋动画</label><div class="layui-input-block"><select name="egg_animation"><option value="hammer">锤子砸蛋</option><option value="click">点击砸蛋</option><option value="shake">摇晃砸蛋</option></select></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">动画时长</label><div class="layui-input-inline"><input type="number" name="egg_animation_duration" class="layui-input" placeholder="2000" min="500" max="5000"></div><div class="layui-form-mid">毫秒</div></div>' +
                '</div>' +
                '<div class="form-section"><div class="section-title">音效设置</div>' +
                '<div class="layui-form-item"><label class="layui-form-label">砸蛋音效</label><div class="layui-input-block"><input type="checkbox" name="egg_sound" lay-skin="switch" lay-text="开启|关闭"></div></div>' +
                '<div class="layui-form-item"><label class="layui-form-label">中奖音效</label><div class="layui-input-block"><input type="checkbox" name="egg_win_sound" lay-skin="switch" lay-text="开启|关闭"></div></div>' +
                '</div>' +
                '<div class="layui-form-item" style="margin-top:20px;"><button type="button" class="btn btn-primary" id="btn-save-egg-settings"><i class="fas fa-save"></i> 保存设置</button></div>' +
                '</form></div>';

            Layout.setContent(html);
        },

        // ========== 砸金蛋 - 奖品设置 ==========
        renderEggPrizes: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title"><span>砸金蛋奖品设置</span><button class="btn btn-primary btn-sm" onclick="LotteryPage._addEggPrize()"><i class="fas fa-plus"></i> 添加奖品</button></div>' +
                '<table id="egg-prizes-table" lay-filter="eggPrizesTable"></table></div>';
            Layout.setContent(html);
        },

        // ========== 砸金蛋 - 中奖名单 ==========
        renderEggWinners: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card">' +
                '<div class="card-title"><span>砸金蛋中奖名单</span>' +
                '<div><button class="btn btn-primary btn-sm" onclick="Api.exportLottery(' + actId + ')"><i class="fas fa-download"></i> 导出</button>' +
                '<button class="btn btn-danger btn-sm" style="margin-left:8px;" onclick="LotteryPage._clearEggWinners()"><i class="fas fa-trash"></i> 清空</button></div></div>' +
                '<table id="egg-winners-table" lay-filter="eggWinnersTable"></table></div>';
            Layout.setContent(html);
        },

        // ========== 砸金蛋 - 内定名单 ==========
        renderEggDesignated: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card">' +
                '<div class="card-title"><span>砸金蛋内定名单</span>' +
                '<button class="btn btn-primary btn-sm" onclick="LotteryPage._addEggDesignated()"><i class="fas fa-plus"></i> 添加内定</button></div>' +
                '<table id="egg-designated-table" lay-filter="eggDesignatedTable"></table></div>';
            Layout.setContent(html);
        }
    };

    global.LotteryPage = LotteryPage;
})(window);
