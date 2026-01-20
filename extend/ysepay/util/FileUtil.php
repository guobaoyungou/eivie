<?php
//custom_file(pay_ysepay)

class FileUtil
{
    public static function mkdir($path)
    {
        if (!file_exists($path)) {
            // 创建文件夹
            //创建一个有读写权限的目录，最好使用以下代码，不要直接使用mkdir函数指定权限，以避免系统umask的影响，要注意一点，权限值最好使用八进制表示，即“0”开头，而且一定不要加引号。
            if (!mkdir($path, 0754)) {
                throw new RuntimeException('创建目录失败：' . $path);
            }
            if (!chmod($path, 0754)) {
                throw new RuntimeException('修改目录权限失败：' . $path);
            }
        }
    }

    public static function saveFile($filePath, $content)
    {
        self::mkdir(dirname($filePath));
        $fd = fopen($filePath, "w");
        fwrite($fd, $content);
        fclose($fd);
    }

}