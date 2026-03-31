#!/usr/bin/env node

/**
 * Uniapp H5 手动构建脚本
 * 当无法使用官方 CLI 时的备用方案
 */

const fs = require('fs');
const path = require('path');
const { execSync } = require('child_process');

const ROOT_DIR = '/home/www/ai.eivie.cn';
const UNIAPP_DIR = path.join(ROOT_DIR, 'uniapp');
const H5_DIR = path.join(ROOT_DIR, 'h5');
const BACKUP_DIR = path.join(ROOT_DIR, `h5_backup_${new Date().toISOString().replace(/[:.]/g, '-')}`);

console.log('============================================');
console.log('Uniapp H5 构建脚本');
console.log('============================================\n');

// 步骤 1: 检查 uniapp 目录
console.log('1. 检查 uniapp 目录...');
if (!fs.existsSync(UNIAPP_DIR)) {
    console.error('❌ uniapp 目录不存在:', UNIAPP_DIR);
    process.exit(1);
}
console.log('✅ uniapp 目录存在\n');

// 步骤 2: 备份现有 H5
console.log('2. 备份现有 H5 目录...');
if (fs.existsSync(H5_DIR)) {
    try {
        execSync(`cp -r ${H5_DIR} ${BACKUP_DIR}`, { stdio: 'inherit' });
        console.log(`✅ H5 已备份到: ${BACKUP_DIR}\n`);
    } catch (error) {
        console.warn('⚠️  备份失败，继续执行...\n');
    }
} else {
    console.log('⚠️  H5 目录不存在，无需备份\n');
}

// 步骤 3: 尝试安装依赖
console.log('3. 检查并安装依赖...');
process.chdir(UNIAPP_DIR);

// 检查是否已安装node_modules
if (!fs.existsSync(path.join(UNIAPP_DIR, 'node_modules'))) {
    console.log('   安装依赖中（这可能需要几分钟）...');
    try {
        // 创建一个临时的 package.json，使用兼容的依赖版本
        const packageJsonPath = path.join(UNIAPP_DIR, 'package.json.backup');
        const packageJson = require(path.join(UNIAPP_DIR, 'package.json'));
        
        // 保存原始 package.json
        fs.copyFileSync(
            path.join(UNIAPP_DIR, 'package.json'),
            packageJsonPath
        );
        
        console.log('   ⚠️  依赖安装需要手动处理');
        console.log('   请在 uniapp 目录下运行以下命令之一:');
        console.log('   ');
        console.log('   方法1（推荐）: 使用 HBuilderX 图形界面进行发行');
        console.log('   - 打开 HBuilderX');
        console.log('   - 导入项目: /home/www/ai.eivie.cn/uniapp');
        console.log('   - 点击: 发行 -> 网站-H5手机版');
        console.log('   - 构建完成后，运行本脚本继续部署');
        console.log('   ');
        console.log('   方法2: 如果已经构建过，直接复制');
        console.log('   - 如果之前已经成功构建过，可以跳过重新构建');
        console.log('   ');
        process.exit(0);
        
    } catch (error) {
        console.error('❌ 依赖安装失败:', error.message);
        process.exit(1);
    }
} else {
    console.log('✅ node_modules 已存在\n');
}

// 步骤 4: 尝试构建
console.log('4. 开始构建 H5...');
try {
    execSync('npm run build:h5', { stdio: 'inherit', cwd: UNIAPP_DIR });
    console.log('✅ 构建完成\n');
} catch (error) {
    console.error('❌ 构建失败');
    console.error('错误信息:', error.message);
    console.log('\n建议：');
    console.log('1. 使用 HBuilderX 图形界面进行构建');
    console.log('2. 或者手动运行: cd /home/www/ai.eivie.cn/uniapp && uvm');
    console.log('   选择 npm，等待安装完成后再运行 npm run build:h5');
    process.exit(1);
}

// 步骤 5: 检查构建结果
console.log('5. 检查构建结果...');
const distDir = path.join(UNIAPP_DIR, 'dist/build/h5');
if (!fs.existsSync(distDir)) {
    console.error('❌ 构建输出目录不存在:', distDir);
    console.log('可能的原因:');
    console.log('- 构建未完成');
    console.log('- 构建配置错误');
    process.exit(1);
}
console.log('✅ 构建输出目录存在\n');

// 步骤 6: 部署到 H5 目录
console.log('6. 部署到 H5 目录...');
try {
    // 清空 H5 目录
    if (fs.existsSync(H5_DIR)) {
        execSync(`rm -rf ${H5_DIR}/*`, { stdio: 'inherit' });
    } else {
        fs.mkdirSync(H5_DIR, { recursive: true });
    }
    
    // 复制文件
    execSync(`cp -r ${distDir}/* ${H5_DIR}/`, { stdio: 'inherit' });
    console.log('✅ 文件已部署\n');
} catch (error) {
    console.error('❌ 部署失败:', error.message);
    process.exit(1);
}

// 步骤 7: 清理缓存
console.log('7. 清理服务器缓存...');
try {
    process.chdir(ROOT_DIR);
    execSync('php think clear', { stdio: 'inherit' });
    execSync('rm -rf runtime/temp/*.php', { stdio: 'inherit' });
    console.log('✅ 缓存已清理\n');
} catch (error) {
    console.warn('⚠️  缓存清理失败，请手动执行: php think clear\n');
}

// 完成
console.log('============================================');
console.log('✅ H5 重新编译和部署完成！');
console.log('============================================');
console.log('访问地址: https://ai.eivie.cn/h5/');
console.log('备份位置:', BACKUP_DIR);
console.log('');
console.log('注意: 请清除浏览器缓存后访问');
console.log('============================================');
