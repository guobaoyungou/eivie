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
  
  // 执行构建（使用 uni-app 的 uni-build 命令）
  console.log('执行构建命令...');
  const result = execSync('node node_modules/@vue/cli-service/bin/vue-cli-service.js uni-build', {
    stdio: 'inherit',
    cwd: __dirname,
    env: {
      ...process.env,
      NODE_ENV: 'production',
      UNI_PLATFORM: 'h5',
      UNI_INPUT_DIR: __dirname,
      UNI_CLI_CONTEXT: __dirname,
      NODE_OPTIONS: '--max-old-space-size=8192'
    }
  });
  
  console.log('构建完成！');
  
  // 检查构建结果
  const h5Dir = path.join(__dirname, 'dist/build/h5');
  if (fs.existsSync(h5Dir)) {
    console.log('构建结果目录:', h5Dir);
    
    // 复制到h5目录
    const targetDir = path.join(__dirname, '../h5');
    
    // 先备份 cashier.html（部署脚本必须保护现金页）
    const targetCashierPath = path.join(targetDir, 'cashier.html');
    let cashierBackup = null;
    if (fs.existsSync(targetCashierPath)) {
      cashierBackup = fs.readFileSync(targetCashierPath);
      console.log('  - 已备份 cashier.html');
    }
    
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
    
    // === 修复 index.html：注入CSS链接、coverSupport视口脚本、转换为相对路径 ===
    console.log('修复 index.html（注入CSS链接和视口脚本）...');
    const indexHtmlPath = path.join(targetDir, 'index.html');
    if (fs.existsSync(indexHtmlPath)) {
      let indexHtml = fs.readFileSync(indexHtmlPath, 'utf8');
      const timestamp = Math.floor(Date.now() / 1000);
      
      // 1. 将绝对路径 /h5/static/ 转换为相对路径 ./static/
      indexHtml = indexHtml.replace(/\/h5\/static\//g, './static/');
      console.log('  - 已转换为相对路径');
      
      // 2. 为JS文件添加缓存破坏参数
      indexHtml = indexHtml.replace(/(\.js)(")/g, `$1?v=${timestamp}$2`);
      console.log('  - 已添加缓存破坏参数');
      
      // 3. 替换静态viewport为动态coverSupport viewport（兼容刘海屏）
      const staticViewport = '<meta name="viewport" content="width=device-width,initial-scale=1">';
      const dynamicViewport = `<script>var coverSupport = 'CSS' in window && typeof CSS.supports === 'function' && (CSS.supports('top: env(a)') || CSS.supports('top: constant(a)'))
            document.write('<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0' + (coverSupport ? ', viewport-fit=cover' : '') + '" />')</script>`;
      if (indexHtml.includes(staticViewport)) {
        indexHtml = indexHtml.replace(staticViewport, dynamicViewport);
        console.log('  - 已注入 coverSupport 视口脚本');
      }
      
      // 4. 查找构建产物中的CSS文件，注入 <link> 标签
      const staticDir = path.join(targetDir, 'static');
      if (fs.existsSync(staticDir)) {
        const cssFiles = fs.readdirSync(staticDir).filter(f => f.endsWith('.css'));
        if (cssFiles.length > 0) {
          const cssLinks = cssFiles.map(f => `<link rel=stylesheet href=./static/${f}?v=${timestamp}>`).join('');
          indexHtml = indexHtml.replace('</head>', cssLinks + '</head>');
          console.log(`  - 已注入CSS链接: ${cssFiles.join(', ')}`);
        } else {
          console.log('  - ⚠️ 未找到CSS文件');
        }
      }
      
      // 5. 写回修复后的 index.html
      fs.writeFileSync(indexHtmlPath, indexHtml);
      console.log('  - index.html 修复完成');
      
      // 重新读取修复后的 index.html 作为模板
      const indexHtmlContent = fs.readFileSync(indexHtmlPath, 'utf8');
      
      // 生成数字命名的HTML文件（1.html-20.html），每个文件注入对应的 uniacid
      console.log('生成带 uniacid 的数字命名HTML文件...');
      for (let i = 1; i <= 20; i++) {
        let htmlContent = indexHtmlContent;
        
        // 查找 <script> 初始化块（含 coverSupport 的内联脚本）并在开头注入 uniacid
        const scriptTag = '<script>';
        const coverSupportMatch = htmlContent.indexOf('var coverSupport');
        if (coverSupportMatch !== -1) {
          // 找到 coverSupport 所在的 <script> 标签，在其内部开头注入
          const scriptStart = htmlContent.lastIndexOf(scriptTag, coverSupportMatch);
          if (scriptStart !== -1) {
            const insertPos = scriptStart + scriptTag.length;
            const injection = `var uniacid=${i};var siteroot="https://"+window.location.host;`;
            htmlContent = htmlContent.slice(0, insertPos) + injection + htmlContent.slice(insertPos);
          }
        } else {
          // 如果没有 coverSupport 脚本，在 </title> 后插入独立的 script 标签
          const titleEnd = htmlContent.indexOf('</title>');
          if (titleEnd !== -1) {
            const injection = `<script>var uniacid=${i};var siteroot="https://"+window.location.host;</script>`;
            htmlContent = htmlContent.slice(0, titleEnd + 8) + injection + htmlContent.slice(titleEnd + 8);
          } else {
            const headEnd = htmlContent.indexOf('</head>');
            if (headEnd !== -1) {
              const injection = `<script>var uniacid=${i};var siteroot="https://"+window.location.host;</script>`;
              htmlContent = htmlContent.slice(0, headEnd) + injection + htmlContent.slice(headEnd);
            }
          }
        }
        
        const numHtmlPath = path.join(targetDir, `${i}.html`);
        fs.writeFileSync(numHtmlPath, htmlContent);
        console.log(`  - 生成 ${i}.html (uniacid=${i})`);
      }
    }
    
    // 恢复 cashier.html（优先使用之前备份的内容）
    if (cashierBackup) {
      fs.writeFileSync(targetCashierPath, cashierBackup);
      console.log('  - 已恢复 cashier.html（从内存备份）');
    } else {
      const cashierSourcePath = path.join(__dirname, 'public/cashier.html');
      if (fs.existsSync(cashierSourcePath)) {
        fs.copyFileSync(cashierSourcePath, targetCashierPath);
        console.log('  - 复制 cashier.html（从项目public目录）');
      } else {
        console.log('  - ⚠️  未找到 cashier.html，跳过');
      }
    }
    
    console.log('✅ H5页面构建并部署完成！');
    
    // 执行后处理补丁：注入 photo/video generation 组件
    console.log('\n执行后处理补丁...');
    const patchScript = path.join(__dirname, '../h5_patch_generation.py');
    const repatchScript = path.join(__dirname, '../h5_repatch_normalizer.py');
    const jsDir = path.join(targetDir, 'static/js');
    
    if (fs.existsSync(patchScript) && fs.existsSync(jsDir)) {
      try {
        console.log('  - 执行 h5_patch_generation.py scan ...');
        execSync(`python3 "${patchScript}" scan "${jsDir}"`, {
          stdio: 'inherit',
          cwd: path.join(__dirname, '..')
        });
        console.log('  - h5_patch_generation.py 补丁完成');
      } catch (e) {
        console.log('  - ⚠️ h5_patch_generation.py 执行失败:', e.message);
      }
    } else {
      console.log('  - ⚠️ 跳过 h5_patch_generation.py（脚本或JS目录不存在）');
    }
    
    if (fs.existsSync(repatchScript)) {
      try {
        console.log('  - 执行 h5_repatch_normalizer.py ...');
        execSync(`python3 "${repatchScript}"`, {
          stdio: 'inherit',
          cwd: path.join(__dirname, '..')
        });
        console.log('  - h5_repatch_normalizer.py 补丁完成');
      } catch (e) {
        console.log('  - ⚠️ h5_repatch_normalizer.py 执行失败:', e.message);
      }
    } else {
      console.log('  - ⚠️ 跳过 h5_repatch_normalizer.py（脚本不存在）');
    }
  } else {
    console.log('❌ 构建失败，未找到输出目录');
  }
  
} catch (error) {
  console.error('构建失败:', error.message);
  process.exit(1);
}
