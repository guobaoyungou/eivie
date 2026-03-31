#!/bin/bash

# 设置uniapp构建环境脚本
# 请在本地开发机器上运行此脚本，不要在生产服务器上运行

set -e

echo "========================"
echo "uniapp构建环境设置脚本"
echo "========================"

# 检查是否在合适的机器上运行
if [[ "$(uname -s)" == "Darwin" ]]; then
    echo "✅ 检测到 macOS 系统"
elif [[ "$(uname -s)" == "Linux" ]]; then
    echo "⚠️  检测到 Linux 系统，建议在macOS或Windows上使用HBuilderX"
else
    echo "❓ 未知系统：$(uname -s)"
fi

echo ""
echo "步骤1: 检查 Node.js"
if command -v node &> /dev/null; then
    NODE_VERSION=$(node --version)
    echo "✅ Node.js 已安装: $NODE_VERSION"
    NODE_MAJOR=$(echo $NODE_VERSION | cut -d'.' -f1 | tr -d 'v')
    if [ $NODE_MAJOR -ge 14 ]; then
        echo "✅ Node.js 版本符合要求 (>=14)"
    else
        echo "❌ Node.js 版本过低，需要 >=14"
        exit 1
    fi
else
    echo "❌ Node.js 未安装"
    echo "请访问 https://nodejs.org/ 下载安装"
    exit 1
fi

echo ""
echo "步骤2: 检查 npm"
if command -v npm &> /dev/null; then
    NPM_VERSION=$(npm --version)
    echo "✅ npm 已安装: $NPM_VERSION"
else
    echo "❌ npm 未安装"
    exit 1
fi

echo ""
echo "步骤3: 检查项目依赖"
if [ -f "package.json" ]; then
    echo "✅ 找到 package.json"
    
    # 创建正确的依赖配置
    echo "正在更新依赖配置..."
    cat > package.json << 'EOF'
{
  "name": "diandashop-uniapp",
  "version": "2.6.9",
  "private": true,
  "scripts": {
    "dev:h5": "cross-env NODE_ENV=development UNI_PLATFORM=h5 vue-cli-service uni-build --watch",
    "build:h5": "cross-env NODE_ENV=production UNI_PLATFORM=h5 vue-cli-service uni-build",
    "build:mp-weixin": "cross-env NODE_ENV=production UNI_PLATFORM=mp-weixin vue-cli-service uni-build"
  },
  "dependencies": {
    "@dcloudio/uni-app": "^3.0.0",
    "@dcloudio/uni-h5": "^3.0.0",
    "@dcloudio/uni-mp-weixin": "^3.0.0",
    "vue": "^2.6.14",
    "vuex": "^3.6.2"
  },
  "devDependencies": {
    "@dcloudio/uni-cli-shared": "^3.0.0",
    "@dcloudio/vue-cli-plugin-uni": "^3.0.0",
    "@vue/cli-service": "~4.5.19",
    "cross-env": "^7.0.3",
    "sass": "^1.53.0",
    "sass-loader": "^10.2.1"
  },
  "browserslist": [
    "last 3 version",
    "> 1%",
    "not dead"
  ]
}
EOF
    echo "✅ 依赖配置已更新"
else
    echo "❌ 未找到 package.json"
    exit 1
fi

echo ""
echo "步骤4: 安装依赖"
echo "正在安装依赖，这可能需要几分钟..."
npm install

echo ""
echo "步骤5: 构建H5"
echo "构建H5版本..."
npm run build:h5

if [ -d "dist/build/h5" ]; then
    echo "✅ H5构建成功"
    echo "构建文件位于: dist/build/h5"
    
    # 复制到指定目录（如果需要）
    echo ""
    read -p "是否将构建结果复制到指定目录？(y/n): " COPY_CHOICE
    if [[ "$COPY_CHOICE" =~ ^[Yy]$ ]]; then
        read -p "请输入目标目录路径: " TARGET_DIR
        if [ -d "$TARGET_DIR" ]; then
            cp -rf dist/build/h5/* "$TARGET_DIR/"
            echo "✅ 已复制到: $TARGET_DIR"
        else
            echo "❌ 目标目录不存在: $TARGET_DIR"
        fi
    fi
else
    echo "❌ H5构建失败"
    exit 1
fi

echo ""
echo "========================"
echo "环境设置完成！"
echo "========================"
echo ""
echo "后续操作："
echo "1. 将构建的H5文件部署到服务器"
echo "2. 清理服务器缓存"
echo "3. 测试封面比例是否为3:4"
echo ""
echo "如果需要构建其他平台："
echo "- 微信小程序: npm run build:mp-weixin"
echo ""
echo "注意：此脚本只在本地开发环境使用"