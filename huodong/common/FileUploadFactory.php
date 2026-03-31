<?php
/**
 * 文件上传
 * PHP version 5.4+
 * 
 * @category Common
 * 
 * @package FileUploadFactory
 * 
 * @author fy <jhfangying@qq.com>
 * */
require_once '../data/config.php';
require_once '../common/File_helper.php';
/**
 * 文件上传
 * PHP version 5.4+
 * 
 * @category Common
 * 
 * @package FileUploadFactory
 * */
class FileUploadFactory
{
    // 参数有file,aliyunoss,sae
    var $fileuploader;
    var $_type=null;
    function __construct($type = 'file') 
    {
        switch ($type) {
            case 'aliyunoss' :
                $this->_type=2;
                require_once ('AliyunossFileUpoad.php');
                $this->fileuploader = new AliyunossFileUpoad ();
                // code...
                break;
            case 'sae' :
                $this->_type=3;
                $this->fileuploader = new SAEFileUpload ();
                break;
            default :
                $this->_type=1;
                require_once ('FileUpload.php');
                $this->fileuploader = new FileUpload ();
                break;
        }
    }
    function SaveRemotePic($picurl, $webroot) 
    {
        return $this->fileuploader->SaveRemotePic ( $picurl, $webroot );
    }

    function getUploadType(){
        return $this->_type;
    }
    //保存表单中的文件
    function SaveFormFile($formfiledata, $filename = '', $path='') 
    {
        $filepath=$this->fileuploader->SaveFormFile($formfiledata, $filename, $path);
        
        return $filepath;
    }
    //保存文件
    function SaveFile($filecontent, $extension, $webroot) 
    {
        $filepath=$this->fileuploader->SaveFile ( $filecontent, $extension, $webroot );
        return $this->saveAttachment($filepath,$extension);
    }

    private function saveAttachment($filepath,$extension)
    {
        $attachments_m=new M('attachments');
        $attachement_data=array(
                'filepath'=>$filepath,
                'extension'=>$extension,
                'type'=>$this->_type
        );
        $attachment_id=$attachments_m->add($attachement_data);
        $attachement_data['id']=$attachment_id;
        return $attachement_data;
    }

    //设置可以上传的文件类型
    function setAllowMimeTypes($mimetypes=array())
    {
        $this->fileuploader->setAllowMimeTypes($mimetypes);
    }
}