var app = getApp();

Page({
  data: {
    loading: true,
    isload: false,
    detail: null,
    opt: {}
  },

  onLoad: function(opt) {
    this.setData({ opt: opt });
    this.getdata();
  },

  onPullDownRefresh: function() {
    this.getdata();
  },

  getdata: function() {
    var that = this;
    that.setData({ loading: true });

    app.get('ApiAivideo/scene_template_detail', {
      template_id: that.data.opt.id
    }, function(res) {
      that.setData({ loading: false, isload: true });
      wx.stopPullDownRefresh();

      if (res.status == 1) {
        that.setData({ detail: res.data });
        wx.setNavigationBarTitle({
          title: res.data.template_name || '模板详情'
        });
      } else {
        app.alert(res.msg || '获取详情失败');
      }
    });
  },

  goCreate: function() {
    var that = this;
    if (!that.data.detail) return;
    var detail = that.data.detail;

    wx.navigateTo({
      url: '/pagesZ/generation/create?id=' + detail.id + '&type=' + (that.data.opt.type || detail.generation_type || 1)
    });
  }
});
