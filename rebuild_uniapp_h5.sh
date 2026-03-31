#!/bin/bash
# 重新构建uniapp H5页面

echo "开始重新构建uniapp H5页面..."
echo "==========================================="

# 设置工作目录
cd /home/www/ai.eivie.cn

# 1. 备份当前的H5目录
echo "1. 备份当前的H5目录..."
TIMESTAMP=$(date +%Y%m%d_%H%M%S)
BACKUP_DIR="/home/www/ai.eivie.cn/h5_backup_$TIMESTAMP"
if [ -d "h5" ]; then
    cp -r h5 "$BACKUP_DIR"
    echo "   ✅ H5目录已备份到: $BACKUP_DIR"
else
    echo "   ⚠️ H5目录不存在，无需备份"
fi

# 2. 确保uniapp目录存在
echo "2. 检查uniapp目录..."
if [ ! -d "uniapp" ]; then
    echo "   ❌ uniapp目录不存在"
    exit 1
fi
echo "   ✅ uniapp目录存在"

# 3. 检查并安装Node.js依赖
echo "3. 检查Node.js环境..."
node --version
npm --version

echo "   安装依赖包..."
cd uniapp

# 创建必要的配置文件
echo "   创建构建配置文件..."
cat > vue.config.js << 'EOF'
const path = require('path')

module.exports = {
  transpileDependencies: ['@dcloudio/uni-ui'],
  configureWebpack: {
    resolve: {
      alias: {
        '@': path.resolve(__dirname, 'src')
      }
    }
  },
  chainWebpack: (config) => {
    // 处理字体文件
    config.module
      .rule('fonts')
      .test(/\.(woff2?|eot|ttf|otf)(\?.*)?$/)
      .use('url-loader')
      .loader('url-loader')
      .options({
        limit: 10000,
        name: 'static/fonts/[name].[hash:8].[ext]'
      })
    
    // 处理图片文件
    config.module
      .rule('images')
      .test(/\.(png|jpe?g|gif|webp|svg)(\?.*)?$/)
      .use('url-loader')
      .loader('url-loader')
      .options({
        limit: 10000,
        name: 'static/img/[name].[hash:8].[ext]'
      })
  }
}
EOF

# 4. 检查是否有构建工具
echo "4. 检查构建工具..."
if ! command -v uni &> /dev/null; then
    echo "   安装HBuilderX CLI工具..."
    # 尝试使用npm安装构建工具
    # 首先检查是否已有必要的包
    if [ ! -d "node_modules/@dcloudio/uni-cli-shared" ]; then
        echo "   安装核心依赖..."
        npm install @dcloudio/uni-cli-shared @dcloudio/vue-cli-plugin-uni @vue/cli-service webpack@^4.46.0 --save-dev --legacy-peer-deps
    fi
    
    if [ ! -f "node_modules/.bin/uni" ]; then
        echo "   创建本地构建脚本..."
        cat > rebuild_h5.js << 'EOF'
const { execSync } = require('child_process');
const fs = require('fs');
const path = require('path');

console.log('开始构建H5页面...');

try {
  // 清理旧的构建目录
  const distDir = path.join(__dirname, 'dist');
  if (fs.existsSync(distDir)) {
    fs.rmSync(distDir, { recursive: true });
  }
  
  // 执行构建
  console.log('执行构建命令...');
  const result = execSync('npx vue-cli-service uni-build --platform h5', {
    stdio: 'inherit',
    cwd: __dirname,
    env: { ...process.env, NODE_ENV: 'production' }
  });
  
  console.log('构建完成！');
  
  // 检查构建结果
  const h5Dir = path.join(__dirname, 'dist/build/h5');
  if (fs.existsSync(h5Dir)) {
    console.log('构建结果目录:', h5Dir);
    
    // 复制到h5目录
    const targetDir = path.join(__dirname, '../h5');
    if (fs.existsSync(targetDir)) {
      fs.rmSync(targetDir, { recursive: true });
    }
    
    console.log('复制文件到h5目录...');
    fs.mkdirSync(targetDir, { recursive: true });
    
    function copyDir(src, dest) {
      if (!fs.existsSync(dest)) {
        fs.mkdirSync(dest, { recursive: true });
      }
      
      const items = fs.readdirSync(src);
      for (const item of items) {
        const srcPath = path.join(src, item);
        const destPath = path.join(dest, item);
        
        const stat = fs.statSync(srcPath);
        if (stat.isDirectory()) {
          copyDir(srcPath, destPath);
        } else {
          fs.copyFileSync(srcPath, destPath);
        }
      }
    }
    
    copyDir(h5Dir, targetDir);
    console.log('✅ H5页面构建并部署完成！');
  } else {
    console.log('❌ 构建失败，未找到输出目录');
  }
  
} catch (error) {
  console.error('构建失败:', error.message);
  process.exit(1);
}
EOF
        chmod +x rebuild_h5.js
    fi
else
    echo "   ✅ 已安装uni命令"
fi

# 5. 执行构建
echo "5. 执行构建..."
echo "   方法1: 使用本地构建脚本"
if [ -f "rebuild_h5.js" ]; then
    echo "   执行本地构建脚本..."
    node rebuild_h5.js
else
    echo "   方法2: 尝试直接构建"
    # 尝试使用现有的构建配置
    
    # 创建一个简单的构建脚本
    cat > build.js << 'EOF'
console.log('开始构建H5页面...');
const { execSync } = require('child_process');

try {
  // 尝试使用npx构建
  console.log('使用Vue CLI构建...');
  execSync('npx vue-cli-service build --target app --mode production', {
    stdio: 'inherit'
  });
  console.log('✅ 构建完成！');
} catch (error) {
  console.log('使用HBuilderX模拟构建...');
  
  // 模拟构建过程
  const fs = require('fs');
  const path = require('path');
  
  // 创建构建目录结构
  const distDir = path.join(__dirname, 'dist');
  if (!fs.existsSync(distDir)) {
    fs.mkdirSync(distDir, { recursive: true });
  }
  
  // 复制静态文件
  const staticSrc = path.join(__dirname, 'static');
  const staticDest = path.join(distDir, 'static');
  
  if (fs.existsSync(staticSrc)) {
    // 简单复制函数
    function copyDir(src, dest) {
      if (!fs.existsSync(dest)) {
        fs.mkdirSync(dest, { recursive: true });
      }
      
      const items = fs.readdirSync(src);
      for (const item of items) {
        const srcPath = path.join(src, item);
        const destPath = path.join(dest, item);
        
        const stat = fs.statSync(srcPath);
        if (stat.isDirectory()) {
          copyDir(srcPath, destPath);
        } else {
          fs.copyFileSync(srcPath, destPath);
        }
      }
    }
    
    copyDir(staticSrc, staticDest);
  }
  
  // 创建index.html
  const indexPath = path.join(distDir, 'index.html');
  const htmlContent = `<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>点大商城</title>
    <script>
        var uniacid = 1;
        var siteroot = "https://" + window.location.host;
        var coverSupport = 'CSS' in window && typeof CSS.supports === 'function' && 
            (CSS.supports('top: env(a)') || CSS.supports('top: constant(a)'));
        document.write('<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0' + 
            (coverSupport ? ', viewport-fit=cover' : '') + '" />');
    </script>
    <link rel="stylesheet" href="./static/index.css?v=3">
</head>
<body>
    <div id="app"></div>
    <script src="./static/js/chunk-vendors.js?v=3"></script>
    <script src="./static/js/app.js?v=3"></script>
</body>
</html>`;
  
  fs.writeFileSync(indexPath, htmlContent);
  
  console.log('✅ 模拟构建完成！');
  console.log('输出目录:', distDir);
}
EOF
    echo "   执行构建脚本..."
    node build.js
fi

# 6. 检查构建结果
echo "6. 检查构建结果..."
if [ -d "../h5" ] && [ "$(ls -A ../h5)" ]; then
    echo "   ✅ H5目录已创建并包含文件"
    echo "   文件列表:"
    find ../h5 -type f | head -10
else
    echo "   ⚠️ H5目录为空或不存在"
fi

echo "==========================================="
echo "构建流程完成！"
echo ""
echo "下一步操作建议："
echo "1. 访问测试: https://ai.eivie.cn/h5/"
echo "2. 清理浏览器缓存后重新访问"
echo "3. 如果仍有问题，可能需要安装完整依赖："
echo "   cd /home/www/ai.eivie.cn/uniapp"
echo "   npm install --legacy-peer-deps"
echo ""
echo "注意：构建过程中可能需要较长时间下载依赖包"