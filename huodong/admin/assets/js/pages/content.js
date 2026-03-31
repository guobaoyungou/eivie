/**
 * 投票相册页面模块
 * 包含：投票管理、相册管理
 */
;(function(global) {
    'use strict';

    var ContentPage = {
        // ========== 投票管理 ==========
        renderVote: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title"><span>投票管理</span><div>' +
                '<button class="btn btn-primary btn-sm" onclick="ContentPage._addVoteItem()"><i class="fas fa-plus"></i> 添加选项</button> ' +
                '<button class="btn btn-danger btn-sm" onclick="ContentPage._resetVotes()"><i class="fas fa-redo"></i> 重置投票</button>' +
                '</div></div>' +
                '<table id="vote-items-table" lay-filter="voteItemsTable"></table></div>';

            Layout.setContent(html);

            if (!document.getElementById('voteItemBar')) {
                var s = document.createElement('script');
                s.type = 'text/html'; s.id = 'voteItemBar';
                s.innerHTML = '<a class="layui-btn layui-btn-xs" lay-event="edit"><i class="fas fa-edit"></i> 编辑</a><a class="layui-btn layui-btn-danger layui-btn-xs" lay-event="del"><i class="fas fa-trash"></i> 删除</a>';
                document.body.appendChild(s);
            }

            layui.table.render({
                elem: '#vote-items-table',
                url: '/api/hd/vote/' + actId + '/items',
                headers: { 'Authorization': 'Bearer ' + Api.getToken() },
                parseData: function(res) {
                    var list = res.data ? (res.data.list || res.data) : [];
                    return { code: 0, msg: '', count: Array.isArray(list) ? list.length : 0, data: list };
                },
                cols: [[
                    { field: 'image', title: '图片', width: 80, templet: function(d) {
                        var img = d.image || d.thumb;
                        return img ? '<img class="img-preview" src="' + img + '">' : '<i class="fas fa-image" style="font-size:24px;color:#ddd;"></i>';
                    }},
                    { field: 'title', title: '选项名称', width: 180 },
                    { field: 'description', title: '描述', minWidth: 200 },
                    { field: 'vote_count', title: '票数', width: 100, sort: true },
                    { title: '操作', width: 150, toolbar: '#voteItemBar' }
                ]],
                page: false,
                text: { none: '暂无投票选项' }
            });

            layui.table.on('tool(voteItemsTable)', function(obj) {
                if (obj.event === 'edit') ContentPage._editVoteItem(actId, obj.data);
                else if (obj.event === 'del') {
                    layui.layer.confirm('确定删除该投票选项？', { icon: 3 }, function(idx) {
                        Api.deleteVoteItem(actId, obj.data.id).then(function() {
                            layui.layer.close(idx);
                            obj.del();
                            layui.layer.msg('删除成功', { icon: 1 });
                        });
                    });
                }
            });
        },

        _addVoteItem: function() {
            var actId = App.getCurrentActivityId();
            layui.layer.open({
                type: 1, title: '添加投票选项', area: ['500px', '400px'],
                content: '<div style="padding:20px;"><form class="layui-form" lay-filter="addVoteItem">' +
                    '<div class="layui-form-item"><label class="layui-form-label">选项名称</label><div class="layui-input-block"><input type="text" name="title" class="layui-input" required></div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">描述</label><div class="layui-input-block"><textarea name="description" class="layui-textarea"></textarea></div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">图片URL</label><div class="layui-input-block"><input type="text" name="image" class="layui-input" placeholder="选项图片地址"></div></div>' +
                    '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" id="btn-save-vote"><i class="fas fa-save"></i> 保存</button></div></div>' +
                    '</form></div>',
                success: function(layero) {
                    layui.form.render(null, 'addVoteItem');
                    layero.find('#btn-save-vote').on('click', function() {
                        var data = {
                            title: layero.find('[name="title"]').val(),
                            description: layero.find('[name="description"]').val(),
                            image: layero.find('[name="image"]').val()
                        };
                        if (!data.title) return layui.layer.msg('请输入选项名称', { icon: 2 });
                        Api.createVoteItem(actId, data).then(function() {
                            layui.layer.closeAll();
                            layui.table.reload('vote-items-table');
                            layui.layer.msg('添加成功', { icon: 1 });
                        });
                    });
                }
            });
        },

        _editVoteItem: function(actId, rowData) {
            layui.layer.open({
                type: 1, title: '编辑投票选项', area: ['500px', '400px'],
                content: '<div style="padding:20px;"><form class="layui-form" lay-filter="editVoteItem">' +
                    '<div class="layui-form-item"><label class="layui-form-label">选项名称</label><div class="layui-input-block"><input type="text" name="title" class="layui-input" value="' + (rowData.title || '') + '"></div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">描述</label><div class="layui-input-block"><textarea name="description" class="layui-textarea">' + (rowData.description || '') + '</textarea></div></div>' +
                    '<div class="layui-form-item"><label class="layui-form-label">图片URL</label><div class="layui-input-block"><input type="text" name="image" class="layui-input" value="' + (rowData.image || rowData.thumb || '') + '"></div></div>' +
                    '<div class="layui-form-item"><div class="layui-input-block"><button type="button" class="btn btn-primary" id="btn-update-vote"><i class="fas fa-save"></i> 保存</button></div></div>' +
                    '</form></div>',
                success: function(layero) {
                    layui.form.render(null, 'editVoteItem');
                    layero.find('#btn-update-vote').on('click', function() {
                        var data = {
                            title: layero.find('[name="title"]').val(),
                            description: layero.find('[name="description"]').val(),
                            image: layero.find('[name="image"]').val()
                        };
                        Api.updateVoteItem(actId, rowData.id, data).then(function() {
                            layui.layer.closeAll();
                            layui.table.reload('vote-items-table');
                            layui.layer.msg('更新成功', { icon: 1 });
                        });
                    });
                }
            });
        },

        _resetVotes: function() {
            var actId = App.getCurrentActivityId();
            layui.layer.confirm('确定重置所有投票数据？此操作不可恢复！', { icon: 3, title: '警告' }, function(idx) {
                Api.resetVotes(actId).then(function() {
                    layui.layer.close(idx);
                    layui.table.reload('vote-items-table');
                    layui.layer.msg('投票已重置', { icon: 1 });
                });
            });
        },

        // ========== 相册管理 ==========
        renderAlbum: function() {
            var actId = App.getCurrentActivityId();
            if (!actId) return Layout.setContent('<div class="empty-state"><i class="fas fa-exclamation-circle"></i><p>请先选择活动</p></div>');

            var html = '<div class="content-card"><div class="card-title"><span>相册管理</span><div>' +
                '<button class="btn btn-primary btn-sm" onclick="ContentPage._addPhoto()"><i class="fas fa-plus"></i> 添加照片</button> ' +
                '<button class="btn btn-danger btn-sm" onclick="ContentPage._clearAlbum()"><i class="fas fa-trash"></i> 清空相册</button>' +
                '</div></div>' +
                '<div id="album-photos" class="loading-state"><i class="fas fa-spinner"></i> 加载中...</div></div>';

            Layout.setContent(html);
            this._loadPhotos(actId);
        },

        _loadPhotos: function(actId) {
            Api.getAlbumPhotos(actId).then(function(res) {
                var list = res.data ? (res.data.list || res.data) : [];
                if (!Array.isArray(list)) list = [];
                var container = document.getElementById('album-photos');
                if (list.length === 0) {
                    container.innerHTML = '<div class="empty-state"><i class="fas fa-images"></i><p>暂无照片</p></div>';
                    return;
                }
                var h = '<div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px;">';
                list.forEach(function(p) {
                    var src = p.url || p.image || p.photo_url || '';
                    h += '<div style="position:relative;border-radius:8px;overflow:hidden;aspect-ratio:1;background:#f5f5f5;">' +
                        '<img src="' + src + '" style="width:100%;height:100%;object-fit:cover;">' +
                        '<button onclick="ContentPage._deletePhoto(' + actId + ',' + p.id + ')" style="position:absolute;top:4px;right:4px;background:rgba(0,0,0,0.5);color:#fff;border:none;border-radius:50%;width:24px;height:24px;cursor:pointer;font-size:12px;"><i class="fas fa-times"></i></button>' +
                        '</div>';
                });
                h += '</div>';
                container.innerHTML = h;
            }).catch(function() {
                var c = document.getElementById('album-photos');
                if (c) c.innerHTML = '<div class="empty-state"><i class="fas fa-exclamation-triangle"></i><p>加载失败</p></div>';
            });
        },

        _addPhoto: function() {
            var actId = App.getCurrentActivityId();
            layui.layer.open({
                type: 1, title: '添加照片', area: ['450px', '250px'],
                content: '<div style="padding:20px;">' +
                    '<div class="layui-form-item"><label class="layui-form-label">图片URL</label><div class="layui-input-block"><input type="text" id="photo-url" class="layui-input" placeholder="输入图片地址"></div></div>' +
                    '<div class="layui-form-item"><div class="layui-input-block"><button class="btn btn-primary" id="btn-add-photo"><i class="fas fa-save"></i> 添加</button></div></div>' +
                    '</div>',
                success: function(layero) {
                    layero.find('#btn-add-photo').on('click', function() {
                        var url = layero.find('#photo-url').val();
                        if (!url) return layui.layer.msg('请输入图片地址', { icon: 2 });
                        Api.addAlbumPhoto(actId, { url: url }).then(function() {
                            layui.layer.closeAll();
                            layui.layer.msg('添加成功', { icon: 1 });
                            ContentPage._loadPhotos(actId);
                        });
                    });
                }
            });
        },

        _deletePhoto: function(actId, photoId) {
            layui.layer.confirm('确定删除该照片？', { icon: 3 }, function(idx) {
                Api.deleteAlbumPhoto(actId, photoId).then(function() {
                    layui.layer.close(idx);
                    layui.layer.msg('删除成功', { icon: 1 });
                    ContentPage._loadPhotos(actId);
                });
            });
        },

        _clearAlbum: function() {
            var actId = App.getCurrentActivityId();
            layui.layer.confirm('确定清空整个相册？此操作不可恢复！', { icon: 3, title: '警告' }, function(idx) {
                Api.clearAlbum(actId).then(function() {
                    layui.layer.close(idx);
                    layui.layer.msg('已清空', { icon: 1 });
                    ContentPage._loadPhotos(actId);
                });
            });
        }
    };

    global.ContentPage = ContentPage;
})(window);
