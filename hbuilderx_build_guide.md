# HBuilderX 构建指南

## 项目信息
- **项目名称**: 点大商城
- **AppID**: `__UNI__9080613`
- **版本**: 2.6.9
- **封面比例修复**: photo-generation组件默认值从 `1:1` 改为 `3:4`

## 构建前准备

### 1. 下载HBuilderX
- 访问 https://www.dcloud.io/hbuilderx.html
- 下载最新版HBuilderX（推荐标准版）
- 安装并启动HBuilderX

### 2. 导入项目
1. 打开HBuilderX
2. 点击菜单：文件 → 导入 → 从本地目录导入
3. 选择 `/home/www/ai.eivie.cn/uniapp` 目录
4. 项目名称为：点大商城

### 3. 验证修复
在构建前，请确认修复已生效：
1. 打开文件：`components/dp-photo-generation/dp-photo-generation.vue`
2. 检查第149-151行，应为：
   ```javascript
   // 封面比例，默认3:4
   coverRatio() {
       return this.params.cover_ratio || '3:4';
   },
   ```

## 构建步骤

### 方式一：HBuilderX GUI构建

#### 构建H5
1. 点击左侧菜单：运行 → 运行到浏览器 → Chrome
2. 等待编译完成，测试封面比例是否为3:4
3. 确认无误后，点击：发行 → 网站-H5手机版
4. 配置：
   - 网站标题：点大商城
   - 网站域名：https://ai.eivie.cn
   - 点击发行
5. 构建完成后，将 `dist/build/h5` 目录内容复制到 `/home/www/ai.eivie.cn/h5/`

#### 构建微信小程序
1. 点击：运行 → 运行到小程序模拟器 → 微信开发者工具
2. 测试封面比例是否为3:4
3. 确认无误后，点击：发行 → 小程序-微信
4. 输入AppID：`wx7fa337f123a0c063`
5. 点击发行
6. 构建完成后，将 `dist/build/mp-weixin` 目录内容复制到 `/home/www/ai.eivie.cn/mp-weixin/`

### 方式二：命令行构建（如果已安装HBuilderX CLI）

如果已安装HBuilderX CLI，可执行以下命令：

```bash
# 进入项目目录
cd /home/www/ai.eivie.cn/uniapp

# 构建H5
hbx build --project uniapp --platform h5 --output ../h5_build

# 构建微信小程序
hbx build --project uniapp --platform mp-weixin --output ../mp-weixin_build

# 复制构建结果
cp -rf ../h5_build/* ../h5/
cp -rf ../mp-weixin_build/* ../mp-weixin/
```

## 构建后操作

### 1. 清理缓存
```bash
# 清理服务器缓存
cd /home/www/ai.eivie.cn
rm -rf runtime/cache/* runtime/temp/* runtime/log/*

# 清理H5缓存（更新版本号）
cd h5
find . -name "*.html" -type f -exec sed -i 's/v=[0-9]*/v=3/g' {} \;
```

### 2. 验证构建
1. **H5**: 访问 https://ai.eivie.cn/h5/，按 Ctrl+F5 强制刷新
2. **微信小程序**: 在微信开发者工具中预览
3. **设计器**: 添加新组件，检查封面比例默认值

## 常见问题

### 1. 构建失败
- 检查HBuilderX版本是否最新
- 检查Node.js是否安装（建议Node.js 14+）
- 检查网络连接是否正常

### 2. 封面比例仍为1:1
- 确认组件代码已修复
- 清理所有缓存（浏览器、小程序、服务器）
- 检查是否有多处相同的默认值设置

### 3. 多端不同步
- 确保所有平台都重新构建
- 检查各平台小程序的构建配置
- 验证数据库中的页面数据是否已更新

## 紧急解决方案

如果无法立即构建，可使用临时修复：

1. **修改数据库**：更新所有页面中的cover_ratio值
2. **中间件修复**：在PHP输出时动态替换默认值
3. **静态文件修复**：直接修改H5的JS文件（不推荐）

## 联系方式
- 项目路径：/home/www/ai.eivie.cn/
- 管理员：admin
- 构建时间：2026年3月24日