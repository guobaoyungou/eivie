package api

import (
	"net/http"
	"strconv"
	"time"

	"github.com/ai-eivie/xiaozhi-cloud/internal/store"
	"github.com/gin-gonic/gin"
)

// ==================== 统一响应 ====================

type Response struct {
	Code    int         `json:"code"`
	Message string      `json:"message"`
	Data    interface{} `json:"data,omitempty"`
	Total   int64       `json:"total,omitempty"`
}

func OK(c *gin.Context, data interface{}, message ...string) {
	msg := "success"
	if len(message) > 0 {
		msg = message[0]
	}
	c.JSON(http.StatusOK, Response{Code: 0, Message: msg, Data: data})
}

func ErrorResp(c *gin.Context, code int, message string) {
	c.JSON(code, Response{Code: code / 100, Message: message})
}

func PageResponse(c *gin.Context, data interface{}, total int64) {
	c.JSON(http.StatusOK, Response{Code: 0, Message: "success", Data: data, Total: total})
}

func getAID(c *gin.Context) uint {
	if aid, exists := c.Get("aid"); exists {
		return aid.(uint)
	}
	return 0
}

func getPageParams(c *gin.Context) (page, pageSize int) {
	page, _ = strconv.Atoi(c.DefaultQuery("page", "1"))
	pageSize, _ = strconv.Atoi(c.DefaultQuery("page_size", "20"))
	if page < 1 {
		page = 1
	}
	if pageSize < 1 {
		pageSize = 20
	}
	if pageSize > 100 {
		pageSize = 100
	}
	return page, pageSize
}

// ==================== 门店管理 ====================

func (s *Server) ListStores(c *gin.Context) {
	page, pageSize := getPageParams(c)
	stores, total, err := s.gateway.GetDeviceManager().ListStores(getAID(c), page, pageSize)
	if err != nil {
		ErrorResp(c, 500, "查询门店失败")
		return
	}
	PageResponse(c, stores, total)
}

func (s *Server) CreateStore(c *gin.Context) {
	var req store.Store
	if err := c.ShouldBindJSON(&req); err != nil {
		ErrorResp(c, 400, "参数错误: "+err.Error())
		return
	}
	req.AID = getAID(c)
	req.CreatedAt = time.Now()

	if err := s.gateway.GetDeviceManager().CreateStore(&req); err != nil {
		ErrorResp(c, 500, "创建门店失败")
		return
	}
	OK(c, req, "门店创建成功")
}

func (s *Server) GetStore(c *gin.Context) {
	id, _ := strconv.ParseUint(c.Param("id"), 10, 32)
	store, err := s.gateway.GetDeviceManager().GetStoreByID(uint(id))
	if err != nil {
		ErrorResp(c, 404, "门店不存在")
		return
	}
	OK(c, store)
}

func (s *Server) UpdateStore(c *gin.Context) {
	id, _ := strconv.ParseUint(c.Param("id"), 10, 32)
	store, err := s.gateway.GetDeviceManager().GetStoreByID(uint(id))
	if err != nil {
		ErrorResp(c, 404, "门店不存在")
		return
	}

	if err := c.ShouldBindJSON(store); err != nil {
		ErrorResp(c, 400, "参数错误")
		return
	}

	if err := s.gateway.GetDeviceManager().UpdateStore(store); err != nil {
		ErrorResp(c, 500, "更新失败")
		return
	}
	OK(c, store, "更新成功")
}

func (s *Server) DeleteStore(c *gin.Context) {
	id, _ := strconv.ParseUint(c.Param("id"), 10, 32)
	if err := s.gateway.GetDeviceManager().DeleteStore(uint(id)); err != nil {
		ErrorResp(c, 500, "删除失败")
		return
	}
	OK(c, nil, "删除成功")
}

// ==================== 直播间管理 ====================

func (s *Server) ListRooms(c *gin.Context) {
	page, pageSize := getPageParams(c)
	storeID, _ := strconv.ParseUint(c.Query("store_id"), 10, 32)

	rooms, total, err := s.gateway.GetDeviceManager().ListRooms(getAID(c), uint(storeID), page, pageSize)
	if err != nil {
		ErrorResp(c, 500, "查询直播间失败")
		return
	}
	PageResponse(c, rooms, total)
}

func (s *Server) CreateRoom(c *gin.Context) {
	var req store.Room
	if err := c.ShouldBindJSON(&req); err != nil {
		ErrorResp(c, 400, "参数错误")
		return
	}
	req.AID = getAID(c)
	req.Status = 0
	req.CreatedAt = time.Now()

	if err := s.gateway.GetDeviceManager().CreateRoom(&req); err != nil {
		ErrorResp(c, 500, "创建直播间失败")
		return
	}
	OK(c, req, "创建成功")
}

func (s *Server) GetRoom(c *gin.Context) {
	id, _ := strconv.ParseUint(c.Param("id"), 10, 32)
	room, err := s.gateway.GetDeviceManager().GetRoomByID(uint(id))
	if err != nil {
		ErrorResp(c, 404, "直播间不存在")
		return
	}
	OK(c, room)
}

func (s *Server) UpdateRoom(c *gin.Context) {
	id, _ := strconv.ParseUint(c.Param("id"), 10, 32)
	room, err := s.gateway.GetDeviceManager().GetRoomByID(uint(id))
	if err != nil {
		ErrorResp(c, 404, "直播间不存在")
		return
	}

	if err := c.ShouldBindJSON(room); err != nil {
		ErrorResp(c, 400, "参数错误")
		return
	}

	if err := s.gateway.GetDeviceManager().UpdateRoom(room); err != nil {
		ErrorResp(c, 500, "更新失败")
		return
	}
	OK(c, room, "更新成功")
}

func (s *Server) DeleteRoom(c *gin.Context) {
	// TODO: 实现删除直播间
	OK(c, nil, "删除成功")
}

func (s *Server) StartLiveRoom(c *gin.Context) {
	id, _ := strconv.ParseUint(c.Param("id"), 10, 32)
	_ = id
	// TODO: 启动弹幕抓取
	OK(c, nil, "直播已启动")
}

func (s *Server) StopLiveRoom(c *gin.Context) {
	id, _ := strconv.ParseUint(c.Param("id"), 10, 32)
	_ = id
	// TODO: 停止弹幕抓取
	OK(c, nil, "直播已停止")
}

// ==================== 设备管理 ====================

func (s *Server) ListDevices(c *gin.Context) {
	page, pageSize := getPageParams(c)
	roomID, _ := strconv.ParseUint(c.Query("room_id"), 10, 32)
	storeID, _ := strconv.ParseUint(c.Query("store_id"), 10, 32)
	onlineOnly := c.Query("online") == "1"

	devices, total, err := s.gateway.GetDeviceManager().ListDevices(getAID(c), uint(roomID), uint(storeID), onlineOnly, page, pageSize)
	if err != nil {
		ErrorResp(c, 500, "查询设备失败")
		return
	}
	PageResponse(c, devices, total)
}

func (s *Server) RegisterDevice(c *gin.Context) {
	var req store.Device
	if err := c.ShouldBindJSON(&req); err != nil {
		ErrorResp(c, 400, "参数错误")
		return
	}
	req.AID = getAID(c)
	req.OnlineStatus = 0
	req.CreatedAt = time.Now()

	if err := s.gateway.GetDeviceManager().CreateDevice(&req); err != nil {
		ErrorResp(c, 500, "注册设备失败")
		return
	}
	OK(c, req, "注册成功")
}

func (s *Server) GetDevice(c *gin.Context) {
	// TODO: 获取设备详情
	OK(c, nil)
}

func (s *Server) UpdateDevice(c *gin.Context) {
	// TODO: 更新设备
	OK(c, nil, "更新成功")
}

func (s *Server) DeleteDevice(c *gin.Context) {
	// TODO: 删除设备
	OK(c, nil, "删除成功")
}

func (s *Server) BindDeviceToRoom(c *gin.Context) {
	var req struct {
		DeviceCode string `json:"device_code" binding:"required"`
		RoomID     uint   `json:"room_id" binding:"required"`
	}
	if err := c.ShouldBindJSON(&req); err != nil {
		ErrorResp(c, 400, "参数错误")
		return
	}

	if err := s.gateway.GetDeviceManager().BindDeviceToRoom(req.DeviceCode, req.RoomID); err != nil {
		ErrorResp(c, 500, "绑定失败")
		return
	}
	OK(c, nil, "绑定成功")
}

func (s *Server) UnbindDevice(c *gin.Context) {
	var req struct {
		DeviceCode string `json:"device_code" binding:"required"`
	}
	if err := c.ShouldBindJSON(&req); err != nil {
		ErrorResp(c, 400, "参数错误")
		return
	}

	if err := s.gateway.GetDeviceManager().UnbindDevice(req.DeviceCode); err != nil {
		ErrorResp(c, 500, "解绑失败")
		return
	}
	OK(c, nil, "解绑成功")
}

// ==================== 模型广场 ====================

func (s *Server) ListModels(c *gin.Context) {
	// TODO: 从数据库查询模型配置
	OK(c, []interface{}{})
}

func (s *Server) CreateModelConfig(c *gin.Context) {
	OK(c, nil, "创建成功")
}

func (s *Server) GetModelConfig(c *gin.Context) {
	OK(c, nil)
}

func (s *Server) UpdateModelConfig(c *gin.Context) {
	OK(c, nil, "更新成功")
}

func (s *Server) DeleteModelConfig(c *gin.Context) {
	OK(c, nil, "删除成功")
}

func (s *Server) ListProviders(c *gin.Context) {
	providers := []map[string]string{
		{"id": "deepseek", "name": "DeepSeek"},
		{"id": "tongyi", "name": "通义千问"},
		{"id": "zhipu", "name": "智谱 AI"},
		{"id": "gemini", "name": "Google Gemini"},
		{"id": "doubao", "name": "火山豆包"},
	}
	OK(c, providers)
}

// ==================== 知识库管理 ====================

func (s *Server) ListKnowledgeBases(c *gin.Context) {
	OK(c, []interface{}{})
}

func (s *Server) CreateKnowledgeBase(c *gin.Context) {
	OK(c, nil, "创建成功")
}

func (s *Server) GetKnowledgeBase(c *gin.Context) {
	OK(c, nil)
}

func (s *Server) UpdateKnowledgeBase(c *gin.Context) {
	OK(c, nil, "更新成功")
}

func (s *Server) DeleteKnowledgeBase(c *gin.Context) {
	OK(c, nil, "删除成功")
}

func (s *Server) UploadDocument(c *gin.Context) {
	OK(c, nil, "上传成功")
}

func (s *Server) ListDocuments(c *gin.Context) {
	OK(c, []interface{}{})
}

func (s *Server) SearchKnowledgeBase(c *gin.Context) {
	OK(c, []interface{}{})
}

// ==================== 弹幕设置 ====================

func (s *Server) GetDanmakuSettings(c *gin.Context) {
	OK(c, map[string]interface{}{
		"filter_enabled":   true,
		"keyword_enabled":  true,
		"sentiment_enabled": false,
	})
}

func (s *Server) UpdateDanmakuSettings(c *gin.Context) {
	OK(c, nil, "设置已保存")
}

func (s *Server) ReloadKeywords(c *gin.Context) {
	OK(c, nil, "关键词已重载")
}

// ==================== 统计监控 ====================

func (s *Server) DashboardStats(c *gin.Context) {
	stats := map[string]interface{}{
		"total_stores":     0,
		"total_rooms":      0,
		"total_devices":    0,
		"online_devices":   s.gateway.GetStats().OnlineDevices,
		"today_danmaku":    0,
		"today_dialogues":  0,
	}
	OK(c, stats)
}

func (s *Server) GatewayStats(c *gin.Context) {
	OK(c, s.gateway.GetStats())
}

func (s *Server) DanmakuStats(c *gin.Context) {
	OK(c, map[string]interface{}{
		"total":    0,
		"filtered": 0,
		"replied":  0,
	})
}

func (s *Server) DialogHistory(c *gin.Context) {
	OK(c, []interface{}{})
}
