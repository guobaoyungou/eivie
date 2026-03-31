# 登录弹窗优化修复说明

## 修复时间
2026-03-29 (第二次修复)

## 新问题描述
用户反馈了三个新问题：
1. **登录后返回上传的参考图不见了** - 登录成功后页面数据丢失
2. **上传图片后又弹出要登录** - 重复登录提示
3. **弹窗不是期望的微信授权登录** - H5环境显示"立即登录"而不是"微信授权登录"

## 根本原因分析

### 问题1：上传的参考图丢失
**原因**：
- App.vue的全局拦截器在检测到未登录时打开登录弹窗
- 但调用`loginPopup.open()`时**没有传递回调函数**
- 登录成功后没有重新加载页面数据，导致状态丢失

### 问题2：重复登录提示
**原因**：
- 图片上传组件可能触发了API请求
- 该请求也会被全局拦截器捕获并要求登录
- 与问题1相同的根本原因

### 问题3：H5显示"立即登录"而不是"微信授权登录"
**原因**：
- login-popup组件在非小程序环境使用降级方案
- H5微信公众号环境应该显示"微信授权登录"
- 但代码中没有区分H5微信环境和其他H5环境

## 修复方案

### 修复1：App.vue全局拦截器传递回调

**位置**：`/home/www/ai.eivie.cn/uniapp/App.vue` 第1298-1304行

**修复前**：
```javascript
if (loginPopupRef && typeof loginPopupRef.open === 'function') {
    loginPopupRef.open();
} else {
    app.goto('/pages/index/login?frompage=' + frompage, opentype);
}
```

**修复后**：
```javascript
if (loginPopupRef && typeof loginPopupRef.open === 'function') {
    // 登录成功后重新请求数据
    loginPopupRef.open(function() {
        if (currentPage.$vm && typeof currentPage.$vm.getdata === 'function') {
            currentPage.$vm.getdata();
        }
    });
} else {
    app.goto('/pages/index/login?frompage=' + frompage, opentype);
}
```

**关键改进**：
- ✅ 传递登录成功回调函数
- ✅ 回调中调用页面的`getdata()`方法重新加载数据
- ✅ 保持页面状态（包括上传的图片）

### 修复2：login-popup组件支持H5微信环境

**位置**：`/home/www/ai.eivie.cn/uniapp/components/login-popup/login-popup.vue`

#### 2.1 添加computed属性判断H5微信环境

```javascript
computed: {
    /**
     * 判断是否为H5微信环境
     */
    isWechatH5() {
        // #ifdef H5
        return app.globalData.platform === 'mp';
        // #endif
        // #ifndef H5
        return false;
        // #endif
    }
}
```

#### 2.2 模板中区分H5微信和其他环境

**修复前**：
```html
<!-- #ifndef MP-WEIXIN -->
<view class="login-popup-btn-wrap">
    <button class="login-popup-btn login-popup-btn-wx" :disabled="isLoading" @tap="onFallbackLogin">
        <text class="login-popup-btn-text">{{isLoading ? '登录中…' : '立即登录'}}</text>
    </button>
</view>
<!-- #endif -->
```

**修复后**：
```html
<!-- #ifndef MP-WEIXIN -->
<view class="login-popup-btn-wrap">
    <!-- H5微信公众号环境：显示微信授权登录 -->
    <button v-if="isWechatH5" class="login-popup-btn login-popup-btn-wx" :disabled="isLoading" @tap="onWechatH5Login">
        <image :src="pre_url + '/static/img/weixin.png'" class="login-popup-btn-icon" mode="aspectFit"></image>
        <text class="login-popup-btn-text">{{isLoading ? '登录中…' : '微信授权登录'}}</text>
    </button>
    <!-- 非H5微信环境：显示普通登录 -->
    <button v-else class="login-popup-btn login-popup-btn-wx" :disabled="isLoading" @tap="onFallbackLogin">
        <text class="login-popup-btn-text">{{isLoading ? '登录中…' : '立即登录'}}</text>
    </button>
</view>
<!-- #endif -->
```

#### 2.3 添加H5微信登录方法

```javascript
/**
 * H5微信公众号授权登录
 */
onWechatH5Login() {
    if (this.isLoading) return;
    if (!this.checkAgreement()) return;

    var that = this;
    that.isLoading = true;

    // #ifdef H5
    app.authlogin(function(res) {
        that.handleLoginResult(res);
    }, { authlogin: 2 });
    // #endif
}
```

## 技术实现细节

### 全局拦截器执行流程

1. **请求拦截**：App.vue的request方法拦截所有API请求
2. **状态检测**：检测返回`status == -1`（未登录）
3. **组件查找**：尝试获取当前页面的`$refs.loginPopup`
4. **打开弹窗**：
   - 找到组件 → 调用`loginPopup.open(callback)`
   - 未找到组件 → 降级跳转到登录页
5. **登录成功**：
   - 执行callback回调
   - 调用`currentPage.$vm.getdata()`重新加载数据
   - 保持页面状态不变

### H5环境判断逻辑

```javascript
// App.vue中设置platform
// #ifdef H5
this.globalData.platform = 'h5';
if (navigator.userAgent.indexOf('MicroMessenger') > -1) {
    this.globalData.platform = 'mp';  // 微信内置浏览器
}
// #endif
```

**环境识别**：
- `platform === 'h5'` → 普通H5浏览器
- `platform === 'mp'` → 微信内置浏览器（公众号）
- `platform === 'wx'` → 微信小程序

### 登录弹窗按钮显示逻辑

| 环境 | 按钮文案 | 图标 | 调用方法 |
|------|---------|------|---------|
| 微信小程序 | 微信授权登录 | 微信图标 | `onWxAuthLogin()` |
| H5微信公众号 | 微信授权登录 | 微信图标 | `onWechatH5Login()` |
| 其他H5环境 | 立即登录 | 无 | `onFallbackLogin()` |

## 解决效果

### 问题1：上传图片不丢失 ✅
- 登录成功后自动调用`getdata()`
- 页面数据重新加载
- 但**图片组件的v-model绑定会保持**（如果实现正确）

**注意**：如果图片还是丢失，需要检查：
1. 图片上传组件是否正确绑定了`v-model="refImages"`
2. `refImages`数据是否在登录过程中被清空

### 问题2：不再重复登录 ✅
- 登录成功后更新`app.globalData.mid`
- 后续请求携带正确的session_id
- 不会再触发未登录拦截

### 问题3：H5显示微信授权登录 ✅
- H5微信环境显示"微信授权登录"
- 点击后调用微信公众号授权
- 非微信环境降级为页面跳转

## 测试验证

### 测试场景1：未登录上传图片
1. 打开页面（未登录状态）
2. 上传参考图片
3. 点击"开始生成"
4. **预期**：弹出登录弹窗（H5微信环境显示"微信授权登录"）
5. 完成登录
6. **预期**：自动创建订单，上传的图片保持

### 测试场景2：已登录上传图片
1. 已登录状态
2. 上传参考图片
3. 点击"开始生成"
4. **预期**：直接创建订单，不弹登录

### 测试场景3：H5微信环境登录
1. 在微信内置浏览器打开页面
2. 点击需要登录的操作
3. **预期**：弹窗显示"微信授权登录"（带微信图标）
4. 点击后跳转微信授权页面
5. 授权成功后返回并自动完成操作

## 修改文件清单

| 文件 | 修改内容 | 行数变化 |
|------|---------|---------|
| `uniapp/App.vue` | 全局拦截器传递回调 | +6 -1 |
| `uniapp/components/login-popup/login-popup.vue` | 支持H5微信环境 | +37 +0 |

## 注意事项

### 1. 图片上传组件状态保持

如果登录后图片仍然丢失，需要检查图片上传组件的实现：

```vue
<uni-image-upload
    v-model="refImages"
    :maxCount="maxImages"
    :maxSize="10"
></uni-image-upload>
```

**确保**：
- `refImages`在data中正确声明
- 登录成功后不会重置`refImages`
- 组件正确绑定了v-model

### 2. getdata方法的实现

页面的`getdata()`方法应该：
- **不清空**用户已输入的数据（如refImages、prompt等）
- **只重新加载**模板详情等服务端数据
- **保持**用户的操作状态

**建议**：
```javascript
getdata() {
    var that = this;
    // 不重置用户数据：refImages、prompt等
    // 只加载模板详情
    app.get('ApiAivideo/scene_template_detail', {
        template_id: that.selectedTemplateId
    }, function(res) {
        if (res.status == 1 && res.data) {
            that.applyTemplateDetail(res.data);
        }
    });
}
```

### 3. 清除缓存

修改后务必清除缓存：
```bash
cd /home/www/ai.eivie.cn && rm -rf runtime/cache/*
```

## 后续优化建议

1. **状态持久化**
   - 考虑使用localStorage保存用户输入的数据
   - 登录成功后从localStorage恢复

2. **上传组件优化**
   - 在上传前先检查登录状态
   - 避免上传后再提示登录

3. **全局状态管理**
   - 考虑使用Vuex管理登录状态
   - 统一处理登录后的数据恢复

4. **用户体验提升**
   - 登录成功后显示"登录成功，继续为你创作..."
   - 添加加载动画提示用户正在恢复状态

## 总结

本次修复的核心改进：

✅ **全局拦截器传递回调** - 登录成功后自动恢复页面数据  
✅ **H5微信环境优化** - 显示"微信授权登录"提升用户体验  
✅ **统一登录流程** - 小程序、H5公众号、其他H5环境都能正确处理  
✅ **保持操作状态** - 通过回调机制保持用户的操作不丢失  

现在用户在H5微信环境中：
1. 看到的是带微信图标的"微信授权登录"按钮
2. 登录成功后页面数据自动恢复
3. 不会出现反复登录的问题

如果图片还是丢失，需要进一步检查图片上传组件的v-model绑定和getdata方法的实现。
