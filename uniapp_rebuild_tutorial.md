# uniapp项目重新构建完整教程

## 📋 项目信息
- **项目名称**: 点大商城
- **AppID**: `__UNI__9080613`
- **版本**: 2.6.9
- **修复目标**: 封面比例默认值从 `1:1` 改为 `3:4`

## 🔧 第一步：环境准备

### 1.1 下载HBuilderX
**必须使用HBuilderX进行构建，不能使用其他工具！**

1. 访问官网：https://www.dcloud.io/hbuilderx.html
2. 下载 **标准版**（约200MB）
3. 安装并启动HBuilderX

### 1.2 准备构建环境
```bash
# 检查Node.js版本
node --version  # 需要 >= 14.0.0

# 检查npm版本
npm --version   # 需要 >= 6.0.0
```

## 📁 第二步：修复源代码

在构建前，必须确保源代码已修复。需要修复的文件：

### 2.1 修复 `components/dp-photo-generation/dp-photo-generation.vue`
```javascript
// 第149-151行，计算属性中的默认值
coverRatio() {
    return this.params.cover_ratio || '3:4';  // 已修复为3:4
},

// 第134行，popupSelectedRatio默认值
popupSelectedRatio: '3:4',  // 需要修复

// 第247行，popupSelectedRatio重置值
this.popupSelectedRatio = '3:4';  // 需要修复
```

### 2.2 修复 `components/dp-video-generation/dp-video-generation.vue`
```javascript
// 第133行，计算属性中的默认值
coverRatio() {
    return this.params.cover_ratio || '3:4';  // 已为3:4，无需修复
},
```

## 🚀 第三步：构建流程

### 3.1 方式一：HBuilderX GUI构建（推荐）

#### 步骤1：导入项目
1. 打开HBuilderX
2. 点击菜单：文件 → 导入 → 从本地目录导入
3. 选择项目路径：`/home/www/ai.eivie.cn/uniapp`
4. 项目名称：点大商城

#### 步骤2：验证修复
1. 打开 `components/dp-photo-generation/dp-photo-generation.vue`
2. 按 `Ctrl+F` 搜索 `'1:1'`
3. 将所有 `'1:1'` 替换为 `'3:4'`（除了popupRatioOptions数组中的选项）

#### 步骤3：构建H5
1. 点击菜单：运行 → 运行到浏览器 → Chrome
2. 等待编译完成（约1-3分钟）
3. 在浏览器中测试封面比例是否为3:4

#### 步骤4：正式构建
1. 点击菜单：发行 → 网站-H5手机版
2. 配置选项：
   - 网站标题：点大商城
   - 网站域名：`https://ai.eivie.cn`
   - 其他选项保持默认
3. 点击 **发行** 按钮
4. 等待构建完成（约3-10分钟）

#### 步骤5：获取构建结果
构建完成后，H5文件位于：
```
uniapp/dist/build/h5/
```

### 3.2 方式二：命令行构建（如果已安装uni-cli）

```bash
# 进入项目目录
cd /home/www/ai.eivie.cn/uniapp

# 安装依赖
npm install @dcloudio/uni-app@latest @dcloudio/uni-h5@latest

# 构建H5
npm run build:h5

# 查看构建结果
ls -la dist/build/h5/
```

## 📦 第四步：部署构建结果

### 4.1 备份当前H5
```bash
# 进入项目根目录
cd /home/www/ai.eivie.cn

# 备份当前H5
cp -r h5 h5_backup_$(date +%Y%m%d_%H%M%S)
```

### 4.2 部署新构建
```bash
# 清空旧H5目录（保留静态资源）
cd /home/www/ai.eivie.cn/h5

# 删除所有文件（保留目录结构）
find . -type f -name "*.html" -o -name "*.js" -o -name "*.css" -o -name "*.png" -o -name "*.jpg" | xargs rm -f

# 复制新构建的文件
cp -r ../uniapp/dist/build/h5/* ./

# 更新版本号（强制浏览器刷新）
find . -name "*.html" -type f -exec sed -i 's/v=[0-9]*/v=4/g' {} \;
```

## 🧹 第五步：清理缓存

### 5.1 服务器缓存清理
```bash
# 清理ThinkPHP缓存
cd /home/www/ai.eivie.cn
rm -rf runtime/cache/* runtime/temp/* runtime/log/* runtime/session/*

# 清理文件缓存
find . -name "*.cache.*" -type f -delete
```

### 5.2 客户端缓存清理

#### H5浏览器：
1. 按 `Ctrl+Shift+Delete` 打开清除浏览数据
2. 选择：缓存的图片和文件
3. 时间范围：全部时间
4. 点击 **清除数据**

#### 微信小程序：
1. 删除小程序（长按图标 → 删除）
2. 重新搜索进入

#### 其他平台：
- 支付宝小程序：删除后重新进入
- 百度小程序：删除后重新进入
- 抖音小程序：删除后重新进入
- QQ小程序：删除后重新进入

## ✅ 第六步：验证修复

### 6.1 H5验证
1. 访问：`https://ai.eivie.cn/h5/`
2. 按 `Ctrl+F5` 强制刷新
3. 检查封面比例是否为3:4

### 6.2 设计器验证
1. 登录设计器后台
2. 编辑任意页面
3. 添加 **图片生成** 或 **视频生成** 组件
4. 检查默认封面比例

### 6.3 多端验证
在各平台小程序中预览页面，检查封面比例是否一致。

## 🚨 常见问题解决

### Q1：构建失败
**错误信息**：`Error: Cannot find module '@dcloudio/uni-app'`
**解决方案**：
```bash
# 重新安装依赖
cd uniapp
rm -rf node_modules package-lock.json
npm install @dcloudio/uni-app@3.0.0 @dcloudio/uni-h5@3.0.0
```

### Q2：封面比例仍为1:1
**原因**：
1. 源代码未完全修复
2. 浏览器缓存未清理
3. 构建配置有缓存

**解决方案**：
```bash
# 1. 检查所有组件中的'1:1'
cd uniapp
grep -r "'1:1'" components/

# 2. 清理HBuilderX构建缓存
# 在HBuilderX中：工具 → 清理缓存 → 清理所有缓存

# 3. 重新构建
```

### Q3：H5页面空白
**原因**：构建文件未正确部署

**解决方案**：
```bash
# 检查文件权限
cd /home/www/ai.eivie.cn/h5
chown -R www:www .
chmod -R 755 .
```

## 📝 构建脚本

我已创建以下脚本帮助您：

### 1. 修复脚本：`fix_uniapp_cover_ratio.sh`
```bash
#!/bin/bash
# 修复uniapp所有组件中的封面比例默认值
cd /home/www/ai.eivie.cn/uniapp

# 修复photo-generation组件
sed -i "s/popupSelectedRatio: '1:1'/popupSelectedRatio: '3:4'/g" components/dp-photo-generation/dp-photo-generation.vue
sed -i "s/this.popupSelectedRatio = '1:1'/this.popupSelectedRatio = '3:4'/g" components/dp-photo-generation/dp-photo-generation.vue

echo "修复完成！请重新构建项目。"
```

### 2. 部署脚本：`deploy_h5.sh`
```bash
#!/bin/bash
# 部署H5构建结果
BACKUP_DIR="h5_backup_$(date +%Y%m%d_%H%M%S)"
BUILD_SOURCE="../uniapp/dist/build/h5"

echo "开始部署H5..."

# 备份
echo "备份当前H5到: $BACKUP_DIR"
cp -r h5 $BACKUP_DIR

# 清空并部署
echo "清空H5目录..."
find h5 -type f \( -name "*.html" -o -name "*.js" -o -name "*.css" \) -delete

echo "复制新构建文件..."
cp -r $BUILD_SOURCE/* h5/

echo "更新版本号..."
find h5 -name "*.html" -type f -exec sed -i 's/v=[0-9]*/v=4/g' {} \;

echo "✅ 部署完成！"
```

## 📞 技术支持

如果构建过程中遇到问题：

1. **查看HBuilderX控制台**：窗口 → 显示开发者工具
2. **查看构建日志**：项目目录下 `unpackage/log/build.log`
3. **检查依赖版本**：`npm list @dcloudio/uni-app`

**重要提示**：
- 构建必须在 **本地开发环境** 进行，不能在生产服务器直接构建
- 构建完成后，将构建文件上传到服务器
- 确保所有平台都重新构建并部署

**构建完成后，H5和多端小程序的封面比例将全部统一为3:4！**