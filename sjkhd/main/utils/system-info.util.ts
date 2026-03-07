import { platform, arch, hostname, cpus, totalmem } from 'os'
import { networkInterfaces } from 'os'
import { app } from 'electron'

/**
 * 系统信息工具类
 */

/**
 * 系统信息接口
 */
export interface SystemInfo {
  osType: string
  osVersion: string
  osArch: string
  pcName: string
  cpuModel: string
  cpuCores: number
  totalMemory: number
  macAddress: string
  clientVersion: string
}

/**
 * 获取操作系统类型
 */
export function getOSType(): string {
  const platformMap: Record<string, string> = {
    'win32': 'Windows',
    'darwin': 'macOS',
    'linux': 'Linux'
  }
  
  return platformMap[platform()] || platform()
}

/**
 * 获取操作系统版本
 */
export function getOSVersion(): string {
  // 简化版本信息
  return `${platform()} ${arch()}`
}

/**
 * 获取计算机名称
 */
export function getPCName(): string {
  return hostname()
}

/**
 * 获取 CPU 信息
 */
export function getCPUInfo(): { model: string; cores: number } {
  const cpuInfo = cpus()
  
  return {
    model: cpuInfo[0]?.model || 'Unknown',
    cores: cpuInfo.length
  }
}

/**
 * 获取总内存（MB）
 */
export function getTotalMemory(): number {
  return Math.round(totalmem() / 1024 / 1024)
}

/**
 * 获取 MAC 地址
 */
export function getMACAddress(): string {
  const interfaces = networkInterfaces()
  
  for (const name of Object.keys(interfaces)) {
    const interfaceList = interfaces[name]
    if (!interfaceList) continue
    
    for (const iface of interfaceList) {
      // 跳过内部和非 IPv4 地址
      if (iface.internal || iface.family !== 'IPv4') {
        continue
      }
      
      // 返回第一个有效的 MAC 地址
      if (iface.mac && iface.mac !== '00:00:00:00:00:00') {
        return iface.mac
      }
    }
  }
  
  // 如果没有找到，返回默认值
  return '00:00:00:00:00:00'
}

/**
 * 获取客户端版本
 */
export function getClientVersion(): string {
  return app.getVersion()
}

/**
 * 获取完整的系统信息
 */
export function getSystemInfo(): SystemInfo {
  const cpuInfo = getCPUInfo()
  
  return {
    osType: getOSType(),
    osVersion: getOSVersion(),
    osArch: arch(),
    pcName: getPCName(),
    cpuModel: cpuInfo.model,
    cpuCores: cpuInfo.cores,
    totalMemory: getTotalMemory(),
    macAddress: getMACAddress(),
    clientVersion: getClientVersion()
  }
}

/**
 * 获取设备唯一标识（基于 MAC 地址）
 */
export function getDeviceId(): string {
  return getMACAddress().replace(/:/g, '').toUpperCase()
}
