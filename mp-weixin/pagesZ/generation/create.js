var app = getApp();

// 文件校验配置
var FILE_VALIDATE_CONFIG = {
  image: {
    maxSize: 10 * 1024 * 1024, // 10MB
    extensions: ['jpg', 'jpeg', 'png', 'gif', 'bmp', 'webp'],
    sizeLabel: '10MB',
    formatLabel: 'JPG/PNG'
  },
  video: {
    maxSize: 50 * 1024 * 1024, // 50MB
    extensions: ['mp4', 'mov'],
    sizeLabel: '50MB',
    formatLabel: 'MP4/MOV'
  }
};

Page({
  data: {
    loading: false,
    isload: false,
    detail: null,
    prompt: '',
    promptExpanded: false,
    refImages: [],
    uploadingFiles: [], // 正在上传的文件列表 {tempPath, progress, status}
    quantity: 1,
    selectedRatio: '3:4',
    submitting: false,
    generationType: 1,
    totalPrice: '0.00',
    opt: {},
    showPrivacyDialog: false,
    ratioOptions: [
      { value: '2:3', label: '2:3', w: 2, h: 3 },
      { value: '3:2', label: '3:2', w: 3, h: 2 },
      { value: '3:4', label: '3:4', w: 3, h: 4 },
      { value: '4:3', label: '4:3', w: 4, h: 3 },
      { value: '9:16', label: '9:16', w: 9, h: 16 },
      { value: '16:9', label: '16:9', w: 16, h: 9 },
      { value: '4:5', label: '4:5', w: 4, h: 5 },
      { value: '5:4', label: '5:4', w: 5, h: 4 },
      { value: '21:9', label: '21:9', w: 21, h: 9 }
    ],
    countOptions: [1, 2, 3, 4, 5, 6, 7, 8, 9],
    maxImages: 1, // 默认1张，由模板 max_ref_images 控制
    promptVisible: true,
    showIdPhotoGuide: false,
    idPhotoGuideShownMap: {},
    idPhotoTypeName: '',
    idPhotoCorrectTips: [],
    idPhotoWrongTips: [],
    // 余额/积分不足弹窗
    showInsufficientPopup: false,
    insufficientType: '', // balance_insufficient / score_insufficient
    insufficientTitle: '',
    insufficientMsg: '',
    insufficientBtnText: '',
    insufficientExtra: {},
    // 积分模式
    scorePayEnabled: false,
    priceInScore: 0,
    scoreUnitName: '词元'
  },

  onLoad: function(opt) {
    this.setData({
      opt: opt,
      generationType: parseInt(opt.type) || 1
    });
    var title = this.data.generationType == 1 ? '创建图片任务' : '创建视频任务';
    wx.setNavigationBarTitle({ title: title });
    this.getdata();
  },

  onReady: function() {
    var that = this;
    // 注册隐私授权回调（原生方式，不依赖 wxxieyi 组件）
    if (wx.onNeedPrivacyAuthorization) {
      wx.onNeedPrivacyAuthorization(function(resolve, eventInfo) {
        console.log('[create] onNeedPrivacyAuthorization triggered by:', eventInfo && eventInfo.referrer);
        that.privacyResolve = resolve;
        that.setData({ showPrivacyDialog: true });
      });
    }
  },

  // 用户点击同意隐私协议
  handleAgreePrivacy: function() {
    this.setData({ showPrivacyDialog: false });
    // open-type="agreePrivacyAuthorization" 按钮会自动通知框架用户同意
    // 手动调用 resolve 作为兜底
    if (this.privacyResolve) {
      this.privacyResolve({ buttonId: 'agree-btn', event: 'agree' });
      this.privacyResolve = null;
    }
  },

  // 用户拒绝隐私协议
  handleRejectPrivacy: function() {
    this.setData({ showPrivacyDialog: false });
    if (this.privacyResolve) {
      this.privacyResolve({ event: 'disagree' });
      this.privacyResolve = null;
    }
  },

  // 打开隐私协议全文
  openPrivacyContract: function() {
    wx.openPrivacyContract();
  },

  getdata: function() {
    var that = this;
    that.setData({ loading: true });

    app.get('ApiAivideo/scene_template_detail', {
      template_id: that.data.opt.id
    }, function(res) {
      that.setData({ loading: false, isload: true });

      if (res.status == 1) {
        var detail = res.data;
        var price = parseFloat(detail.price) || 0;
        var prompt = detail.prompt || '';
        
        // 默认选择该模板的每单输出数量
        var defaultQuantity = parseInt(detail.output_quantity) || 1;
        if (defaultQuantity > 9) defaultQuantity = 9;
        if (defaultQuantity < 1) defaultQuantity = 1;
        
        // 提示词可见性
        var promptVisible = (detail.prompt_visible !== 0 && detail.prompt_visible !== '0');
        
        // 最大上传数量，由模板配置控制，默认1
        var maxImages = parseInt(detail.max_ref_images) || 1;
        if (maxImages > 9) maxImages = 9;
        if (maxImages < 1) maxImages = 1;
        
        // 证件照指引信息
        var idPhotoTips = that.getIdPhotoTips(detail.id_photo_type || 0);
        
        that.setData({
          detail: detail,
          prompt: prompt,
          quantity: defaultQuantity,
          promptVisible: promptVisible,
          maxImages: maxImages,
          idPhotoTypeName: detail.id_photo_type_name || '',
          idPhotoCorrectTips: idPhotoTips.correct,
          idPhotoWrongTips: idPhotoTips.wrong,
          totalPrice: that.data.generationType == 1 ? (price * defaultQuantity).toFixed(2) : price.toFixed(2),
          scorePayEnabled: detail.score_pay_enabled || false,
          priceInScore: detail.price_in_score || 0,
          scoreUnitName: detail.score_unit_name || '词元'
        });
      } else {
        app.alert(res.msg);
      }
    });
  },

  togglePrompt: function() {
    this.setData({ promptExpanded: !this.data.promptExpanded });
  },

  onPromptInput: function(e) {
    this.setData({ prompt: e.detail.value });
  },

  onOptimizePrompt: function() {
    wx.showToast({ title: '功能即将上线', icon: 'none' });
  },

  selectRatio: function(e) {
    this.setData({ selectedRatio: e.currentTarget.dataset.value });
  },

  selectCount: function(e) {
    var count = parseInt(e.currentTarget.dataset.value);
    this.setData({ quantity: count });
    this.calcTotalPrice();
  },

  calcTotalPrice: function() {
    var detail = this.data.detail;
    if (!detail) return;
    var price = parseFloat(detail.price) || 0;
    var total = this.data.generationType == 1 ? (price * this.data.quantity).toFixed(2) : price.toFixed(2);
    this.setData({ totalPrice: total });
  },

  // 文件校验：校验单个文件的大小和格式
  validateFile: function(file) {
    var config = FILE_VALIDATE_CONFIG.image;
    var fileName = (file.name || file.tempFilePath || file.path || '').toLowerCase();
    var dotIndex = fileName.lastIndexOf('.');
    var ext = dotIndex > -1 ? fileName.substring(dotIndex + 1) : '';

    // 校验文件大小
    if (file.size && file.size > config.maxSize) {
      return { valid: false, msg: '文件大小超过限制，图片不超过' + config.sizeLabel };
    }

    // 校验文件格式（仅当可获取扩展名时校验）
    if (ext && config.extensions.indexOf(ext) === -1) {
      return { valid: false, msg: '不支持该文件格式，请选择 ' + config.formatLabel + ' 格式图片' };
    }

    return { valid: true };
  },

  // 统一的 fail 回调处理
  handleChooseFileFail: function(err, source) {
    console.log('[create] ' + source + ' fail:', JSON.stringify(err));
    var errMsg = (err && err.errMsg) ? err.errMsg : '';
    if (errMsg.indexOf('cancel') !== -1) {
      return;
    }
    if (errMsg.indexOf('privacy') !== -1) {
      // 隐私授权失败 → onNeedPrivacyAuthorization 会自动弹出隐私弹窗
      // 框架会自动重试被阻断的 API 调用，无需手动重试
      console.log('[create] privacy error, waiting for onNeedPrivacyAuthorization');
      return;
    }
    if (source === 'chooseMedia_camera' && (errMsg.indexOf('auth') !== -1 || errMsg.indexOf('deny') !== -1 || errMsg.indexOf('refuse') !== -1)) {
      // 相机权限被拒绝，引导去设置页
      wx.showModal({
        title: '权限提示',
        content: '需要相机权限才能拍照，请前往设置页开启',
        showCancel: true,
        confirmText: '去设置',
        success: function(modalRes) {
          if (modalRes.confirm) {
            wx.openSetting();
          }
        }
      });
    } else {
      app.alert('选择文件失败，请重试');
    }
  },

  chooseImage: function() {
    var that = this;
    var remaining = that.data.maxImages - that.data.refImages.length - that.data.uploadingFiles.length;
    if (remaining <= 0) {
      app.alert('最多上传 ' + that.data.maxImages + ' 张图片');
      return;
    }
    wx.showActionSheet({
      itemList: ['拍照', '从手机相册选择', '从微信会话选择'],
      success: function(res) {
        if (res.tapIndex == 0) {
          // 拍照：直接调用 chooseMedia，由系统处理权限弹窗
          wx.chooseMedia({
            count: 1,
            mediaType: ['image'],
            sourceType: ['camera'],
            camera: 'back',
            success: function(r) {
              console.log('[create] chooseMedia camera success');
              that.uploadImage(r.tempFiles[0].tempFilePath);
            },
            fail: function(err) {
              that.handleChooseFileFail(err, 'chooseMedia_camera');
            }
          });
        } else if (res.tapIndex == 1) {
          // 从手机相册选择
          wx.chooseMedia({
            count: remaining,
            mediaType: ['image'],
            sourceType: ['album'],
            success: function(r) {
              console.log('[create] chooseMedia album success:', r.tempFiles.length);
              for (var i = 0; i < r.tempFiles.length; i++) {
                that.uploadImage(r.tempFiles[i].tempFilePath);
              }
            },
            fail: function(err) {
              that.handleChooseFileFail(err, 'chooseMedia_album');
            }
          });
        } else if (res.tapIndex == 2) {
          // 从微信会话选择
          wx.chooseMessageFile({
            count: remaining,
            type: 'image',
            success: function(r) {
              console.log('[create] chooseMessageFile success:', r.tempFiles.length);
              if (r.tempFiles && r.tempFiles.length > 0) {
                var validFiles = [];
                for (var i = 0; i < r.tempFiles.length; i++) {
                  var fileInfo = r.tempFiles[i];
                  var result = that.validateFile(fileInfo);
                  if (result.valid) {
                    validFiles.push(fileInfo);
                  } else {
                    app.alert(result.msg);
                  }
                }
                for (var j = 0; j < validFiles.length; j++) {
                  that.uploadImage(validFiles[j].path);
                }
              }
            },
            fail: function(err) {
              that.handleChooseFileFail(err, 'chooseMessageFile');
            }
          });
        }
      }
    });
  },

  // 点击上传按钮：检查证件照指引后直接选图（隐私授权由 onNeedPrivacyAuthorization 自动处理）
  onUploadTap: function() {
    var that = this;
    var detail = that.data.detail;
    if (detail && detail.is_id_photo == 1) {
      var tplId = detail.id;
      var shownMap = that.data.idPhotoGuideShownMap;
      if (!shownMap[tplId]) {
        that.setData({ showIdPhotoGuide: true });
        return;
      }
    }
    that.chooseImage();
  },

  closeIdPhotoGuide: function() {
    this.setData({ showIdPhotoGuide: false });
  },

  confirmIdPhotoGuide: function() {
    var tplId = this.data.detail ? this.data.detail.id : 0;
    var shownMap = this.data.idPhotoGuideShownMap;
    shownMap[tplId] = true;
    this.setData({ showIdPhotoGuide: false, idPhotoGuideShownMap: shownMap });
    this.chooseImage();
  },

  getIdPhotoTips: function(type) {
    var correctMap = {
      1: ['正面免冠、白色背景', '五官清晰完整'],
      2: ['白色背景、不露齿', '露双耳'],
      3: ['白色背景、免冠', '面部居中'],
      4: ['纯色背景（红/蓝/白）', '肩部以上'],
      5: ['纯色背景（红/蓝/白）', '肩部以上']
    };
    var wrongMap = {
      1: ['戴帽/墨镜、背景杂乱', '照片模糊'],
      2: ['背景非白色、表情夸张', '头发遮脸'],
      3: ['侧脸、逆光', '分辨率过低'],
      4: ['半身照、背景渐变', '化妆过度'],
      5: ['半身照、光线不均', '表情不自然']
    };
    return {
      correct: correctMap[type] || ['正面免冠、五官清晰', '背景纯色、光线均匀'],
      wrong: wrongMap[type] || ['模糊/遮挡面部', '背景杂乱/曝光不均']
    };
  },

  uploadImage: function(filePath) {
    var that = this;
    // 添加到上传中列表
    var uploadingFiles = that.data.uploadingFiles.concat([{
      tempPath: filePath,
      progress: 0,
      status: 'uploading' // uploading | success | failed
    }]);
    var fileIndex = uploadingFiles.length - 1;
    that.setData({ uploadingFiles: uploadingFiles });

    var uploadTask = wx.uploadFile({
      url: app.globalData.baseurl + 'ApiImageupload/uploadImg/aid/' + (that.data.opt.aid || '') + '/platform/mp/session_id/' + (app.globalData.session_id || ''),
      filePath: filePath,
      name: 'file',
      formData: { aid: that.data.opt.aid || '' },
      success: function(uploadRes) {
        try {
          var data = JSON.parse(uploadRes.data);
          if (data.status == 1 || data.code == 0) {
            var url = data.url || data.data.src || data.data.url;
            // 添加到已上传列表
            var refImages = that.data.refImages.concat([url]);
            // 从上传中列表移除
            var updatedUploading = that.data.uploadingFiles.filter(function(f) {
              return f.tempPath !== filePath;
            });
            that.setData({
              refImages: refImages,
              uploadingFiles: updatedUploading
            });
          } else {
            // 标记上传失败
            that._updateUploadingFileStatus(filePath, 'failed');
            app.alert(data.msg || '上传失败');
          }
        } catch(e) {
          that._updateUploadingFileStatus(filePath, 'failed');
          app.alert('上传失败');
        }
      },
      fail: function() {
        that._updateUploadingFileStatus(filePath, 'failed');
        app.alert('上传失败，请重试');
      }
    });

    // 监听上传进度
    if (uploadTask && uploadTask.onProgressUpdate) {
      uploadTask.onProgressUpdate(function(progressRes) {
        var list = that.data.uploadingFiles;
        for (var i = 0; i < list.length; i++) {
          if (list[i].tempPath === filePath) {
            var key = 'uploadingFiles[' + i + '].progress';
            that.setData({ [key]: progressRes.progress });
            break;
          }
        }
      });
    }
  },

  // 更新上传中文件的状态
  _updateUploadingFileStatus: function(filePath, status) {
    var list = this.data.uploadingFiles;
    for (var i = 0; i < list.length; i++) {
      if (list[i].tempPath === filePath) {
        var key = 'uploadingFiles[' + i + '].status';
        this.setData({ [key]: status });
        break;
      }
    }
  },

  // 重试上传失败的文件
  retryUpload: function(e) {
    var tempPath = e.currentTarget.dataset.path;
    // 从上传中列表移除
    var updatedUploading = this.data.uploadingFiles.filter(function(f) {
      return f.tempPath !== tempPath;
    });
    this.setData({ uploadingFiles: updatedUploading });
    // 重新上传
    this.uploadImage(tempPath);
  },

  // 移除上传失败的文件
  removeUploadingFile: function(e) {
    var tempPath = e.currentTarget.dataset.path;
    var updatedUploading = this.data.uploadingFiles.filter(function(f) {
      return f.tempPath !== tempPath;
    });
    this.setData({ uploadingFiles: updatedUploading });
  },

  removeImage: function(e) {
    var idx = e.currentTarget.dataset.idx;
    var refImages = this.data.refImages;
    refImages.splice(idx, 1);
    this.setData({ refImages: refImages });
  },

  previewUploadImage: function(e) {
    wx.previewImage({
      current: e.currentTarget.dataset.url,
      urls: this.data.refImages
    });
  },

  previewCompare: function(e) {
    var type = e.currentTarget.dataset.type;
    var detail = this.data.detail;
    var origImg = detail.original_image || detail.ref_image || detail.cover_image;
    var effImg = (detail.effect_images && detail.effect_images.length > 0) ? detail.effect_images[0] : ((detail.sample_images && detail.sample_images.length > 0) ? detail.sample_images[0] : detail.cover_image);
    var urls = [];
    if (origImg) urls.push(origImg);
    if (effImg && urls.indexOf(effImg) === -1) urls.push(effImg);
    var current = type == 'ref' ? origImg : effImg;
    wx.previewImage({ current: current, urls: urls });
  },

  submitGeneration: function() {
    var that = this;
    if (that.data.submitting) return;

    if (that.data.refImages.length == 0) {
      return app.alert('请上传一张照片');
    }

    that.setData({ submitting: true });
    wx.showLoading({ title: '提交中' });

    // 当提示词隐藏时，使用模板默认提示词
    var submitPrompt = that.data.promptVisible ? that.data.prompt : (that.data.detail.prompt || '');

    var postData = {
      template_id: that.data.opt.id,
      generation_type: that.data.generationType,
      prompt: submitPrompt,
      ref_images: that.data.refImages,
      quantity: that.data.quantity,
      ratio: that.data.selectedRatio,
      bid: that.data.opt.bid || 0
    };

    app.post('ApiAivideo/create_generation_order', postData, function(res) {
      wx.hideLoading();
      that.setData({ submitting: false });

      if (res.status == 1) {
        var data = res.data;
        if (data.need_pay) {
          wx.navigateTo({
            url: '/pages/pay/pay?ordernum=' + data.ordernum + '&tablename=generation'
          });
        } else {
          wx.redirectTo({
            url: '/pagesZ/generation/result?order_id=' + data.order_id
          });
        }
      } else {
        // 处理余额/积分不足
        var errorType = res.error_type || 'normal';
        if (errorType == 'score_insufficient') {
          var extra = res.extra || {};
          that.setData({
            showInsufficientPopup: true,
            insufficientType: 'score_insufficient',
            insufficientTitle: that.data.scoreUnitName + '不足',
            insufficientMsg: '当前可用' + that.data.scoreUnitName + ' ' + (extra.current_score || 0) + '，本次需要 ' + (extra.required_score || 0) + ' ' + that.data.scoreUnitName,
            insufficientBtnText: '购买创作会员',
            insufficientExtra: extra
          });
        } else if (errorType == 'balance_insufficient') {
          var extra = res.extra || {};
          that.setData({
            showInsufficientPopup: true,
            insufficientType: 'balance_insufficient',
            insufficientTitle: '余额不足',
            insufficientMsg: '当前余额 ￥' + (extra.current_balance || 0) + '，还需 ￥' + (extra.need_amount || 0),
            insufficientBtnText: '去充值',
            insufficientExtra: extra
          });
        } else {
          app.alert(res.msg);
        }
      }
    });
  },

  // 关闭余额/积分不足弹窗
  closeInsufficientPopup: function() {
    this.setData({ showInsufficientPopup: false });
  },

  // 余额/积分不足弹窗主按钮点击
  onInsufficientAction: function() {
    this.setData({ showInsufficientPopup: false });
    if (this.data.insufficientType == 'balance_insufficient') {
      wx.navigateTo({ url: '/pagesExt/money/recharge' });
    } else if (this.data.insufficientType == 'score_insufficient') {
      // 跳转到创作会员购买页（如没有则充值页）
      wx.navigateTo({ url: '/pagesExt/money/recharge' });
    }
  }
});
