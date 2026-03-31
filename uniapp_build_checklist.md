# uniapp重新构建检查清单

## ✅ 已完成修复

### 1. 源代码修复
- [x] `components/dp-photo-generation/dp-photo-generation.vue`
  - [x] `coverRatio()` 计算属性：`'1:1'` → `'3:4'`
  - [x] `popupSelectedRatio` 默认值：`'1:1'` → `'3:4'`
  - [x] `popupSelectedRatio` 重置值：`'1:1'` → `'3:4'`
  - [x] `popupRatioOptions` 选项列表：保留 `'1:1'`（作为选项）

- [x] `components/dp-video-generation/dp-video-generation.vue`
  - [x] `coverRatio()` 计算属性：已为 `'3:4'`

- [x] `components/dp-product-item/dp-product-item.vue`
  - [x] `cover_ratio` 默认值：`'1:1'` → `'3:4'`

### 2. 设计器页面修复
- [x] `app/view/designer_page/designer_editornew.html`
  - [x] `photo_generation` 组件默认值：`'1:1'` → `'3:4'`
  - [x] `video_generation` 组件默认值：`'1:1'` → `'3:4'`

### 3. H5临时修复
- [x] 批量修复H5 JS文件中的 `'1:1'` → `'3:4'`
- [x] 更新H5版本号：`v=2` → `v=3`

## 🚀 构建步骤

### 步骤1：准备HBuilderX
1. [ ] 下载HBuilderX标准版
2. [ ] 安装并启动HBuilderX
3. [ ] 检查Node.js版本（>=14.0.0）

### 步骤2：导入项目
1. [ ] 文件 → 导入 → 从本地目录导入
2. [ ] 选择路径：`/home/www/ai.eivie.cn/uniapp`
3. [ ] 项目名称：点大商城

### 步骤3：验证修复
1. [ ] 打开 `components/dp-photo-generation/dp-photo-generation.vue`
2. [ ] 检查以下内容：
   - 第149-151行：`coverRatio() { return this.params.cover_ratio || '3:4'; }`
   - 第134行：`popupSelectedRatio: '3:4'`
   - 第247行：`this.popupSelectedRatio = '3:4'`

### 步骤4：构建H5
1. [ ] 运行 → 运行到浏览器 → Chrome
2. [ ] 等待编译完成
3. [ ] 在浏览器中测试封面比例
4. [ ] 发行 → 网站-H5手机版
5. [ ] 配置域名：`https://ai.eivie.cn`
6. [ ] 点击 **发行**

### 步骤5：获取构建文件
构建完成后，文件位于：
```
uniapp/dist/build/h5/
```
或
```
uniapp/unpackage/dist/build/h5/  (HBuilderX默认)
```

### 步骤6：部署到服务器
1. [ ] 备份当前H5：`cp -r h5 h5_backup_日期`
2. [ ] 清空H5目录：删除所有`.html`、`.js`、`.css`文件
3. [ ] 复制新文件：`cp -r uniapp/dist/build/h5/* h5/`
4. [ ] 更新版本号：`find h5 -name "*.html" -exec sed -i 's/v=[0-9]*/v=4/g' {} \;`

## 🧹 缓存清理

### 服务器缓存
```bash
cd /home/www/ai.eivie.cn
rm -rf runtime/cache/* runtime/temp/* runtime/log/*
```

### 客户端缓存
- [ ] H5：按 `Ctrl+Shift+Delete` 清除浏览器缓存，或按 `Ctrl+F5`
- [ ] 微信小程序：删除小程序重新进入
- [ ] 其他平台：删除应用重新进入

## ✅ 验证清单

### H5验证
- [ ] 访问：`https://ai.eivie.cn/h5/`
- [ ] 按 `Ctrl+F5` 强制刷新
- [ ] 检查封面比例是否为3:4

### 设计器验证
- [ ] 登录设计器后台
- [ ] 编辑任意页面
- [ ] 添加图片生成组件
- [ ] 检查默认封面比例

### 多端验证
- [ ] 微信小程序预览
- [ ] 支付宝小程序预览
- [ ] 其他平台预览

## 🚨 故障排除

### 问题1：构建失败
**症状**：HBuilderX报错，无法构建
**解决**：
1. 检查Node.js版本：`node --version`
2. 清理HBuilderX缓存：工具 → 清理缓存
3. 重启HBuilderX

### 问题2：封面比例仍为1:1
**症状**：构建后封面比例未改变
**解决**：
1. 确认源代码已修复
2. 清理所有缓存（服务器、浏览器、小程序）
3. 重新构建并部署

### 问题3：H5页面空白
**症状**：访问H5显示空白页
**解决**：
1. 检查构建文件是否完整
2. 检查文件权限：`chown -R www:www h5`
3. 检查控制台错误信息

## 📞 技术支持

### 查看日志
- HBuilderX控制台：窗口 → 显示开发者工具
- 构建日志：`uniapp/unpackage/log/build.log`

### 检查依赖
```bash
cd uniapp
npm list @dcloudio/uni-app
```

### 紧急回滚
```bash
# 回滚到备份
cp -r h5_backup_日期/* h5/
```

## 📁 已创建的文件

1. `uniapp_rebuild_tutorial.md` - 详细构建教程
2. `fix_uniapp_cover_ratio.sh` - 修复脚本
3. `deploy_h5.sh` - 部署脚本
4. `uniapp_build_checklist.md` - 本检查清单

## ⏰ 预计时间

- 环境准备：10-30分钟
- 构建过程：5-15分钟
- 部署：5分钟
- 验证：10分钟
- **总计：30-60分钟**

## 🎯 成功标准

- [ ] H5页面封面比例显示为3:4
- [ ] 设计器新组件默认封面比例为3:4
- [ ] 多端同步显示3:4封面比例
- [ ] 用户设置优先于默认值

**重要提示**：构建必须在本地环境进行，完成后将文件上传到服务器。