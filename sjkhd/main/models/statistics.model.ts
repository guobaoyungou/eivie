/**
 * 统计数据模型
 */

/**
 * 统计信息
 */
export interface Statistics {
  todayUploadCount: number
  todayUploadSize: number
  avgSpeed: number
  successRate: number
  totalDetected: number
  totalSuccess: number
  totalFailed: number
  queueLength: number
  uploadingCount: number
}

/**
 * 日统计
 */
export interface DailyStatistics {
  date: string
  uploadCount: number
  uploadSize: number
  successCount: number
  failedCount: number
  avgSpeed: number
}

/**
 * 实时统计
 */
export interface RealtimeStatistics {
  currentSpeed: number
  queueLength: number
  uploadingTasks: number
  pendingTasks: number
}
