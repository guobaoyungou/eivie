# Uniapp H5 重新编译指南

## 当前状态

- **Uniapp 源码路径**: `/home/www/ai.eivie.cn/uniapp`
- **H5 部署路径**: `/home/www/ai.eivie.cn/h5`
- **最新源码修改时间**: 2026-03-24 14:07
- **当前 H5 构建时间**: 2026-03-24 11:56
- **结论**: **需要重新编译**，源码比 H5 新

## 问题说明

经过测试，命令行环境下的自动编译遇到以下问题：
1. `@dcloudio/uni-app@^2.0.0` 版本在 npm 仓库不存在
2. HBuilderX 的 CLI 工具依赖完整的 IDE 环境
3. 复制 node_modules 后，`uni` 命令仍无法正常工作

## 解决方案

### 🎯 方案一：使用 HBuilderX 图形界面（强烈推荐）

这是 DCloud 官方推荐的方式，最稳定可靠。

#### 步骤：

1. **下载 HBuilderX**
   - 官网：https://www.dcloud.io/hbuilderx.html
   - 下载标准版（Alpha版包含最新特性）
   - 支持 Windows、macOS、Linux

2. **导入项目**
   ```
   打开 HBuilderX
   → 文件 → 导入 → 从本地目录导入
   → 选择: /home/www/ai.eivie.cn/uniapp
   ```

3. **构建 H5**
   ```
   在项目上右键
   → 发行 → 网站-H5手机版
   → 网站标题: 点大商城
   → 网站域名: https://ai.eivie.cn
   → 点击"发行"
   ```

4. **等待构建完成**
   - 构建时间通常 1-3 分钟
   - 输出目录: `/home/www/ai.eivie.cn/uniapp/dist/build/h5`

5. **部署到服务器**
   ```bash
   # 备份当前 H5
   cd /home/www/ai.eivie.cn
   mv h5 h5_backup_$(date +%Y%m%d_%H%M%S)
   
   # 复制新构建的文件
   cp -r uniapp/dist/build/h5 ./h5
   
   # 修复权限
   chown -R www:www h5
   chmod -R 755 h5
   
   # 清理缓存
   php think clear
   rm -rf runtime/temp/*.php
   ```

---

### 🔧 方案二：使用已安装的 HBuilderX CLI（如果有的话）

服务器上已经有 HBuilderX 安装在 `/home/www/ai.eivie.cn/hbuilderx`，但这是一个图形化版本，不是 CLI 版本。

如果要在命令行使用，需要：

1. **安装 HBuilderX CLI**
   ```bash
   npm install -g @dcloudio/uvm
   cd /home/www/ai.eivie.cn/uniapp
   uvm  # 交互式选择 npm
   ```

2. **等待依赖安装完成**（这一步会很慢，可能需要 10-30 分钟）

3. **执行构建**
   ```bash
   npm run build:h5
   ```

---

### 🚀 方案三：使用现有的构建结果（临时方案）

如果暂时无法重新编译，可以：

1. **检查备份目录**
   ```bash
   ls -la /home/www/ai.eivie.cn/h5_backup_* | grep "3月 24"
   ```

2. **如果有更新的备份，恢复它**
   ```bash
   cp -r /home/www/ai.eivie.cn/h5_backup_XXXXXX/* /home/www/ai.eivie.cn/h5/
   ```

---

## 验证构建结果

构建完成后，执行以下检查：

```bash
# 1. 检查文件是否存在
ls -la /home/www/ai.eivie.cn/h5/

# 2. 检查关键文件
ls -la /home/www/ai.eivie.cn/h5/static/

# 3. 清理缓存
cd /home/www/ai.eivie.cn
php think clear
rm -rf runtime/temp/*.php

# 4. 访问测试
# 浏览器访问: https://ai.eivie.cn/h5/
# 记得清除浏览器缓存（Ctrl + F5）
```

---

## 常见问题

### Q1: 为什么不能直接用 npm run build:h5？
A: uni-app 使用特殊的构建系统，依赖 HBuilderX 的环境。package.json 中的 `uni` 命令需要特定的 CLI 工具。

### Q2: 能否在 CI/CD 中自动构建？
A: 可以，但需要：
1. 使用 Docker 容器，内含完整的 HBuilderX CLI 环境
2. 或者使用 DCloud 提供的云打包服务
3. 或者迁移到 uni-app Vue 3 / Vite 版本（支持更好的 CLI）

### Q3: 构建后浏览器看不到更新？
A: 执行以下操作：
```bash
# 1. 清理服务器缓存
php think clear
rm -rf runtime/temp/*.php

# 2. 更新静态资源版本号
# 编辑 h5 目录下的 HTML 文件，将 ?v=X 的版本号加 1

# 3. 清理浏览器缓存
# Ctrl + Shift + Delete 或 Ctrl + F5 强制刷新
```

---

## 推荐的工作流程

1. **本地开发**
   - 使用 HBuilderX 进行开发和测试
   - 实时预览：运行 → 运行到浏览器

2. **本地构建**
   - 发行 → 网站-H5手机版
   - 测试构建结果

3. **上传到服务器**
   - 使用 FTP/SFTP 上传 dist/build/h5 目录
   - 或使用 rsync 同步

4. **服务器部署**
   - 备份旧版本
   - 替换 h5 目录
   - 清理缓存
   - 测试访问

---

## 联系信息

- **项目路径**: `/home/www/ai.eivie.cn`
- **Git 用户**: eivie (370862955@qq.com)
- **文档创建时间**: 2026-03-24 15:30

---

## 快速命令参考

```bash
# 备份当前 H5
cd /home/www/ai.eivie.cn
mv h5 h5_backup_$(date +%Y%m%d_%H%M%S)

# 从构建结果部署
cp -r uniapp/dist/build/h5 ./h5
chown -R www:www h5
chmod -R 755 h5

# 清理缓存
php think clear
rm -rf runtime/temp/*.php

# 查看文件数量
find h5 -type f | wc -l

# 查看目录大小
du -sh h5
```
