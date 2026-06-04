<?php
/**
 * 算八字海报设置
 * 参照 ShortvideoPoster 模式，使用 admin_set_poster 表（type='bazi'）
 */
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class BaziPosterController extends Common
{
    public function initialize(){
        parent::initialize();
        if(bid > 0) showmsg('无访问权限');
    }

    public function index(){
        $type = input('param.type') ? input('param.type') : $this->platform[0];
        $posterset = Db::name('admin_set_poster')
            ->where('aid',aid)->where('type','bazi')
            ->where('platform',$type)->order('id')->find();

        if(!$posterset){
            // 默认海报：八字主题背景 + 姓名/出生信息/结果摘要 + 二维码
            $data_mp = jsonEncode([
                'poster_bg' => PRE_URL.'/static/imgsrc/posterbg.jpg',
                'poster_data' => [
                    ["left"=>"13px","top"=>"12px","type"=>"pro_img","width"=>"318px","height"=>"300px"],
                    ["left"=>"29px","top"=>"320px","type"=>"text","width"=>"200px","height"=>"20px","size"=>"18px","color"=>"#c6a55a","content"=>"[姓名]"],
                    ["left"=>"29px","top"=>"345px","type"=>"text","width"=>"200px","height"=>"16px","size"=>"13px","color"=>"#8a8070","content"=>"[出生日期] [出生时间]"],
                    ["left"=>"29px","top"=>"365px","type"=>"text","width"=>"200px","height"=>"16px","size"=>"13px","color"=>"#8a8070","content"=>"[出生地点] · [性别]"],
                    ["left"=>"29px","top"=>"395px","type"=>"textarea","width"=>"250px","height"=>"60px","size"=>"12px","color"=>"#999","content"=>"[八字摘要]"],
                    ["left"=>"29px","top"=>"470px","type"=>"head","width"=>"38px","height"=>"38px","radius"=>"100"],
                    ["left"=>"80px","top"=>"480px","type"=>"text","width"=>"120px","height"=>"16px","size"=>"14px","color"=>"#666","content"=>"[商城名称]"],
                    ["left"=>"234px","top"=>"470px","type"=>"qrmp","width"=>"77px","height"=>"77px","size"=>""],
                ]
            ]);
            $data_wx = jsonEncode([
                'poster_bg' => PRE_URL.'/static/imgsrc/posterbg.jpg',
                'poster_data' => [
                    ["left"=>"13px","top"=>"12px","type"=>"pro_img","width"=>"318px","height"=>"300px"],
                    ["left"=>"29px","top"=>"320px","type"=>"text","width"=>"200px","height"=>"20px","size"=>"18px","color"=>"#c6a55a","content"=>"[姓名]"],
                    ["left"=>"29px","top"=>"345px","type"=>"text","width"=>"200px","height"=>"16px","size"=>"13px","color"=>"#8a8070","content"=>"[出生日期] [出生时间]"],
                    ["left"=>"29px","top"=>"365px","type"=>"text","width"=>"200px","height"=>"16px","size"=>"13px","color"=>"#8a8070","content"=>"[出生地点] · [性别]"],
                    ["left"=>"29px","top"=>"395px","type"=>"textarea","width"=>"250px","height"=>"60px","size"=>"12px","color"=>"#999","content"=>"[八字摘要]"],
                    ["left"=>"29px","top"=>"470px","type"=>"head","width"=>"38px","height"=>"38px","radius"=>"100"],
                    ["left"=>"80px","top"=>"480px","type"=>"text","width"=>"120px","height"=>"16px","size"=>"14px","color"=>"#666","content"=>"[商城名称]"],
                    ["left"=>"234px","top"=>"470px","type"=>"qrwx","width"=>"77px","height"=>"77px","size"=>""],
                ]
            ]);
            Db::name('admin_set_poster')->insert(['aid'=>aid,'type'=>'bazi','platform'=>'mp','content'=>$data_mp]);
            Db::name('admin_set_poster')->insert(['aid'=>aid,'type'=>'bazi','platform'=>'wx','content'=>$data_wx]);
            Db::name('admin_set_poster')->insert(['aid'=>aid,'type'=>'bazi','platform'=>'alipay','content'=>$data_mp]);
            Db::name('admin_set_poster')->insert(['aid'=>aid,'type'=>'bazi','platform'=>'baidu','content'=>$data_mp]);
            Db::name('admin_set_poster')->insert(['aid'=>aid,'type'=>'bazi','platform'=>'toutiao','content'=>$data_mp]);
            Db::name('admin_set_poster')->insert(['aid'=>aid,'type'=>'bazi','platform'=>'qq','content'=>$data_mp]);
            Db::name('admin_set_poster')->insert(['aid'=>aid,'type'=>'bazi','platform'=>'h5','content'=>$data_mp]);
            Db::name('admin_set_poster')->insert(['aid'=>aid,'type'=>'bazi','platform'=>'app','content'=>$data_mp]);
            $posterset = Db::name('admin_set_poster')->where('aid',aid)->where('type','bazi')->where('platform',$type)->order('id')->find();
        }

        $posterdata = json_decode($posterset['content'],true);
        View::assign('type',$type);
        View::assign('poster_bg',$posterdata['poster_bg']);
        View::assign('poster_data',$posterdata['poster_data']);
        return View::fetch();
    }

    public function save(){
        $type = input('param.type') ? input('param.type') : $this->platform[0];
        $poster_bg = input('post.poster_bg');
        $poster_data = input('post.poster_data');
        $data_index = ['poster_bg'=>$poster_bg,'poster_data'=>json_decode($poster_data)];
        $posterset = Db::name('admin_set_poster')->where('aid',aid)->where('type','bazi')->where('platform',$type)->order('id')->find();
        Db::name('admin_set_poster')->where('id',$posterset['id'])->update(['content'=>json_encode($data_index)]);
        if(input('post.clearhistory') == 1){
            Db::name('member_poster')->where('aid',aid)->where('type','bazi')->where('posterid',$posterset['id'])->delete();
        }
        \app\common\System::plog('算八字海报设置');
        return json(['status'=>1,'msg'=>'保存成功','url'=>true]);
    }
}
