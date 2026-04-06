<?php
declare(strict_types=1);

namespace app\controller\hd;

use think\facade\Db;
use think\facade\Filesystem;
use app\model\hd\HdAttachment;
use app\model\hd\HdBackground;
use app\model\hd\HdMusic;
use app\service\hd\HdThemeService;
use app\common\OssHelper;

/**
 * 大屏互动 - 文件上传控制器
 * 支持背景图、音乐、附件上传
 */
class HdUploadController extends HdBaseController
{
    /**
     * 通用图片上传
     * POST /api/hd/upload/image
     */
    public function image()
    {
        $file = request()->file('file');
        if (!$file) {
            return $this->error('请上传文件');
        }

        try {
            validate(['file' => [
                'fileSize'    => 5 * 1024 * 1024, // 5MB
                'fileExt'     => 'jpg,jpeg,png,gif,bmp,webp',
            ]])->check(['file' => $file]);

            $savename = Filesystem::putFile('hd/' . $this->getBid(), $file);
            $filepath = 'upload/' . str_replace("\\", '/', $savename);
            $url = request()->domain() . '/' . $filepath;

            // 记录附件
            $attachment = new HdAttachment();
            $attachment->aid = $this->getAid();
            $attachment->bid = $this->getBid();
            $attachment->file_type = 'image';
            $attachment->file_name = $file->getOriginalName();
            $attachment->file_path = $filepath;
            $attachment->file_url = $url;
            $attachment->file_size = $file->getSize();
            $attachment->createtime = time();
            $attachment->save();

            return $this->success([
                'id'       => $attachment->id,
                'url'      => $url,
                'path'     => $filepath,
                'filename' => $file->getOriginalName(),
            ], '上传成功');
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage());
        } catch (\Exception $e) {
            return $this->error('上传失败: ' . $e->getMessage());
        }
    }

    /**
     * 上传活动背景图/视频
     * POST /api/hd/upload/background
     * 支持 plugname 参数，写入 weixin_background / weixin_attachments 表
     * 使用平台附件配置的存储类型（腾讯云COS）
     */
    public function background()
    {
        \think\facade\Log::info('=== background upload start ===');
        $file = request()->file('file');
        if (!$file) {
            \think\facade\Log::info('background upload: no file');
            return $this->error('请上传背景文件');
        }
        \think\facade\Log::info('background upload: file=' . $file->getOriginalName() . ' size=' . $file->getSize());

        $plugname = input('post.plugname', '');
        $activityId = (int)input('post.activity_id', 0);

        if (empty($plugname)) {
            return $this->error('请指定功能模块(plugname)');
        }

        try {
            // 验证文件格式：jpg, jpeg, png, webp, mp4，最大 10MB
            // 注意：fileMime 验证依赖 fileinfo 扩展（finfo_open），服务器未安装，仅用 fileExt 验证
            validate(['file' => [
                'fileSize'    => 10 * 1024 * 1024, // 10MB
                'fileExt'     => 'jpg,jpeg,png,webp,mp4',
            ]])->check(['file' => $file]);

            // 判断文件类型
            $ext = strtolower($file->extension());
            $bgtype = ($ext === 'mp4') ? 2 : 1;

            // 生成云端存储路径
            $ossPath = 'hd/' . $this->getBid() . '/bg/' . date('Ymd') . '/' . md5(uniqid((string)mt_rand(), true)) . '.' . $ext;

            // 使用平台附件配置的存储类型上传到腾讯云COS
            $ossHelper = new OssHelper();
            $fileUrl = $ossHelper->uploadFile($file->getPathname(), $ossPath);

            // 记录到 weixin_attachments 表（filepath 存储 COS 完整 URL）
            $filemd5 = md5_file($file->getPathname());
            $themeService = new HdThemeService();
            $attachmentId = $themeService->saveAttachment($fileUrl, $ext, 1, $filemd5);

            // 更新活动 screen_config 中对应 plugname 的背景配置
            $themeService->updateBackgroundByPlugname($activityId, $plugname, $attachmentId, $bgtype);

            return $this->success([
                'url'      => $fileUrl,
                'path'     => $fileUrl,
                'bgtype'   => $bgtype,
                'plugname' => $plugname,
            ], '上传成功');
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage());
        } catch (\Throwable $e) {
            \think\facade\Log::error('background upload exception: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            return $this->error('上传失败: ' . $e->getMessage());
        }
    }

    /**
     * 上传活动背景音乐
     * POST /api/hd/upload/music
     */
    public function music()
    {
        $file = request()->file('file');
        if (!$file) {
            return $this->error('请上传音乐文件');
        }

        $activityId = (int)input('post.activity_id', 0);
        $title = input('post.title', $file->getOriginalName());

        try {
            validate(['file' => [
                'fileSize'    => 20 * 1024 * 1024, // 20MB
                'fileExt'     => 'mp3,wav,ogg,m4a,aac',
            ]])->check(['file' => $file]);

            $savename = Filesystem::putFile('hd/' . $this->getBid() . '/music', $file);
            $filepath = 'upload/' . str_replace("\\", '/', $savename);
            $url = request()->domain() . '/' . $filepath;

            $music = new HdMusic();
            $music->aid = $this->getAid();
            $music->bid = $this->getBid();
            $music->activity_id = $activityId;
            $music->title = $title;
            $music->url = $url;
            $music->path = $filepath;
            $music->createtime = time();
            $music->save();

            return $this->success([
                'id'    => $music->id,
                'url'   => $url,
                'title' => $title,
            ], '音乐上传成功');
        } catch (\think\exception\ValidateException $e) {
            return $this->error($e->getMessage());
        } catch (\Exception $e) {
            return $this->error('上传失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取活动的背景图列表
     * GET /api/hd/upload/backgrounds
     */
    public function backgrounds()
    {
        $activityId = (int)input('get.activity_id', 0);
        $scene = input('get.scene', '');

        $where = [
            ['aid', '=', $this->getAid()],
            ['bid', '=', $this->getBid()],
        ];
        if ($activityId) {
            $where[] = ['activity_id', '=', $activityId];
        }
        if ($scene) {
            $where[] = ['scene', '=', $scene];
        }

        $list = HdBackground::where($where)->order('id desc')->limit(50)->select()->toArray();

        return $this->success($list);
    }

    /**
     * 获取活动的音乐列表
     * GET /api/hd/upload/musics
     */
    public function musics()
    {
        $activityId = (int)input('get.activity_id', 0);

        $where = [
            ['aid', '=', $this->getAid()],
            ['bid', '=', $this->getBid()],
        ];
        if ($activityId) {
            $where[] = ['activity_id', '=', $activityId];
        }

        $list = HdMusic::where($where)->order('id desc')->limit(50)->select()->toArray();

        return $this->success($list);
    }

    /**
     * 删除背景图
     * DELETE /api/hd/upload/background/:id
     */
    public function deleteBackground(int $id)
    {
        $bg = HdBackground::where('id', $id)
            ->where('aid', $this->getAid())
            ->where('bid', $this->getBid())
            ->find();

        if (!$bg) {
            return $this->error('背景图不存在');
        }

        // 删除文件
        if ($bg->path && file_exists(ROOT_PATH . $bg->path)) {
            @unlink(ROOT_PATH . $bg->path);
        }

        $bg->delete();
        return $this->success([], '删除成功');
    }

    /**
     * 删除音乐
     * DELETE /api/hd/upload/music/:id
     */
    public function deleteMusic(int $id)
    {
        $music = HdMusic::where('id', $id)
            ->where('aid', $this->getAid())
            ->where('bid', $this->getBid())
            ->find();

        if (!$music) {
            return $this->error('音乐不存在');
        }

        if ($music->path && file_exists(ROOT_PATH . $music->path)) {
            @unlink(ROOT_PATH . $music->path);
        }

        $music->delete();
        return $this->success([], '删除成功');
    }
}
