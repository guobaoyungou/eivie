/**
 * 上传任务数据模型
 */

/**
 * 任务状态
 */
export enum TaskStatus {
  PENDING = 'pending',
  UPLOADING = 'uploading',
  PAUSED = 'paused',
  SUCCESS = 'success',
  FAILED = 'failed',
  RETRYING = 'retrying'
}

/**
 * 上传任务
 */
export interface UploadTask {
  id: string
  filePath: string
  fileName: string
  fileSize: number
  fileMd5: string
  status: TaskStatus
  progress: number
  speed: number
  retryCount: number
  errorMessage?: string
  createdAt: number
  startedAt?: number
  completedAt?: number
  uploadUrl?: string
  objectKey?: string
}

/**
 * 上传进度数据
 */
export interface UploadProgress {
  taskId: string
  progress: number
  speed: number
  uploaded: number
  total: number
}

/**
 * 上传结果
 */
export interface UploadResult {
  taskId: string
  success: boolean
  fileUrl?: string
  portraitId?: number
  errorMessage?: string
}
