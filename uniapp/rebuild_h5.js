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
  const result = execSync('npm run build:h5', {
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
    
    // 生成数字命名的HTML文件（1.html-20.html）
    console.log('生成数字命名HTML文件...');
    const indexHtmlPath = path.join(targetDir, 'index.html');
    if (fs.existsSync(indexHtmlPath)) {
      const indexHtmlContent = fs.readFileSync(indexHtmlPath, 'utf8');
      
      // 生成 1.html 到 20.html
      for (let i = 1; i <= 20; i++) {
        const numHtmlPath = path.join(targetDir, `${i}.html`);
        fs.writeFileSync(numHtmlPath, indexHtmlContent);
        console.log(`  - 生成 ${i}.html`);
      }
    }
    
    // 检查并复制 cashier.html（如果存在源文件）
    const cashierSourcePath = path.join(__dirname, 'public/cashier.html');
    const backupCashierPath = path.join(__dirname, '../h5_backup_20260324_151701_GzCpA/h5_backup_20260324_151701/cashier.html');
    const targetCashierPath = path.join(targetDir, 'cashier.html');
    
    if (fs.existsSync(cashierSourcePath)) {
      fs.copyFileSync(cashierSourcePath, targetCashierPath);
      console.log('  - 复制 cashier.html（从项目public目录）');
    } else if (fs.existsSync(backupCashierPath)) {
      // 从备份目录复制
      fs.copyFileSync(backupCashierPath, targetCashierPath);
      console.log('  - 复制 cashier.html（从备份目录）');
    } else {
      console.log('  - ⚠️  未找到 cashier.html 源文件，跳过');
    }
    
    console.log('✅ H5页面构建并部署完成！');
  } else {
    console.log('❌ 构建失败，未找到输出目录');
  }
  
} catch (error) {
  console.error('构建失败:', error.message);
  process.exit(1);
}
