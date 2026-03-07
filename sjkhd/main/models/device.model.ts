/**
 * 设备数据模型
 */

/**
 * 设备配置
 */
export interface DeviceConfig {
  deviceId: string
  deviceToken: string
  deviceName: string
  aid: number
  bid: number
  mdid?: number
  registeredAt: number
  lastSyncAt: number
}

/**
 * 设备信息
 */
export interface DeviceInfo {
  deviceId: string
  deviceName: string
  osType: string
  osVersion: string
  osArch: string
  pcName: string
  cpuModel: string
  cpuCores: number
  totalMemory: number
  macAddress: string
  clientVersion: string
  bid: number
  mdid?: number
  status: number
  createdAt?: number
  updatedAt?: number
}

/**
 * 设备注册请求
 */
export interface DeviceRegisterRequest {
  deviceCode: string
  deviceName: string
  bid: number
  mdid?: number
  deviceId: string
  osVersion: string
  clientVersion: string
  pcName: string
}

/**
 * 设备注册响应
 */
export interface DeviceRegisterResponse {
  deviceToken: string
  deviceId: string
  status: 'new' | 'exists'
}

/**
 * 设备状态
 */
export enum DeviceStatus {
  OFFLINE = 0,
  ONLINE = 1,
  DISABLED = 2
}
