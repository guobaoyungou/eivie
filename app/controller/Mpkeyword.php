<?php
/**
 * 点大商城（www.diandashop.com） - 微信公众号小程序商城系统!
 * Copyright © 2020 山东点大网络科技有限公司 保留所有权利
 * =========================================================
 * 版本：V2
 * 授权主体：shop.guobaoyungou.cn
 * 授权域名：guobaoyungou.cn
 * 授权码：TZJcxBSGGdtDBIxFerKVJo
 * ----------------------------------------------
 * 您只能在商业授权范围内使用，不可二次转售、分发、分享、传播
 * 任何企业和个人不得对代码以任何目的任何形式的再发布
 * =========================================================
 */

// +----------------------------------------------------------------------
// | 自动回复
// +----------------------------------------------------------------------
namespace app\controller;
use think\facade\View;
use think\facade\Db;

class Mpkeyword extends Common
{
    public function initialize(){
		parent::initialize();
		if(bid > 0) showmsg('无访问权限');
	}
	//关注回复
	public function subscribe(){
		//$set = Db::name('admin_set')->where('aid',aid)->find();
		//if($set['mpisauth']==0 && ($set['mpappid']=='' || $set['mpappsecret']=='')){
		//	showmsg('请先授权公众号',(string)url('Shouquan/index'));
		//}

        $sum = 3; //三条关注回复
        //ktype 0完全匹配 1包含匹配 2关注回复
		$info = Db::name('mp_keyword')->where('aid',aid)->where('ktype',2)->order('createtime asc')->select()->toArray();
        if(!$info){
            $info = array();
            for($i=0;$i<$sum;$i++) {
                $info[$i]['id'] = '';
            }
        }

        foreach($info as $k => $v) {
            if($v['msgtype'] == 'text'){
                $text[$k] = $v['content'];
            }elseif($v['msgtype'] == 'image'){
                $image[$k] = json_decode($v['content'],true);
            }elseif($v['msgtype'] == 'voice'){
                $voice[$k] = json_decode($v['content'],true);
            }elseif($v['msgtype'] == 'video'){
                $video[$k] = json_decode($v['content'],true);
            }elseif($v['msgtype'] == 'music'){
                $music[$k] = json_decode($v['content'],true);
            }elseif($v['msgtype'] == 'news'){
                $news[$k] = json_decode($v['content'],true);
            }elseif($v['msgtype'] == 'miniprogrampage'){
                $miniprogrampage[$k] = json_decode($v['content'],true);
            }
        }
		View::assign('info',$info);
		View::assign('text',$text??[]);
		View::assign('image',$image??[]);
		View::assign('voice',$voice??[]);
		View::assign('video',$video??[]);
		View::assign('music',$music??[]);
		View::assign('news',$news??[]);
		View::assign('miniprogrampage',$miniprogrampage??[]);
		return View::fetch();
	}

    public function subscribeSave()
    {
        $infoArray = input('post.info/a');
        foreach ($infoArray as $key => $info) {
            $msgtype = $info['msgtype'];
            if ($msgtype == 'text') {
                $textData = input('post.text');
                $info['content'] = $textData[$key];
            } elseif ($msgtype == 'image') {
                $image = input('post.image/a');
                if (isset($image[$key]) && $image[$key]) {
                    $imageData = $image[$key];
                    $imageData['MediaId'] = \app\common\Wechat::getmediaid(aid, $imageData['url']);
                    $info['content'] = json_encode($imageData);
                }
            } elseif ($msgtype == 'voice') {
                $voice = input('post.voice/a');
                if (isset($voice[$key]) && $voice[$key]) {
                    $voiceData = $voice[$key];
                    $voiceData['MediaId'] = \app\common\Wechat::getmediaid(aid, $voiceData['url'], 'voice');
                    $info['content'] = json_encode($voiceData);
                }
            } elseif ($msgtype == 'video') {
                $video = input('post.video/a');
                if (isset($video[$key]) && $video[$key]) {
                    $videoData = $video[$key];
                    $videoData['MediaId'] = \app\common\Wechat::getmediaid(aid, $videoData['url'], 'video', json_encode(['title' => $videoData['title'], 'introduction' => $videoData['description']]));
                    $info['content'] = json_encode($videoData);
                }
            } elseif ($msgtype == 'music') {
                $music = input('post.music/a');
                if (isset($music[$key]) && $music[$key]) {
                    $musicData = $music[$key];
                    $info['content'] = json_encode($musicData);
                }
            } elseif ($msgtype == 'news') {
                $news = input('post.news/a');
                if (isset($news[$key]) && $news[$key]) {
                    $newsData = $news[$key];
                    $info['content'] = json_encode($newsData);
                }
            } elseif ($msgtype == 'miniprogrampage') {
                $miniprogrampage = input('post.miniprogrampage/a');
                if (isset($miniprogrampage[$key]) && $miniprogrampage[$key]) {
                    $miniprogrampageData = $miniprogrampage[$key];
                    $miniprogrampageData['MediaId'] = \app\common\Wechat::getmediaid(aid, $miniprogrampageData['pic']);
                    $info['content'] = json_encode($miniprogrampageData);
                }
            }

            if (!empty($info['id'])) {
                Db::name('mp_keyword')->where('aid', aid)->where('id', $info['id'])->update($info);
                \app\common\System::plog('编辑公众号关键字回复' . $info['id']);
            } else {
                $info['aid'] = aid;
                $info['createtime'] = time();
                $id = Db::name('mp_keyword')->insertGetId($info);
                \app\common\System::plog('添加公众号关键字回复' . $id);
            }
        }

        return json(['status' => 1, 'msg' => '操作成功', 'url' => (string)url('index')]);
    }



    //列表
    public function index(){
		//$set = Db::name('admin_set')->where('aid',aid)->find();
		//if($set['mpisauth']==0 && ($set['mpappid']=='' || $set['mpappsecret']=='')){
		//	showmsg('请先授权公众号',(string)url('Shouquan/index'));
		//}
		if(request()->isAjax()){
			$page = input('param.page');
			$limit = input('param.limit');
			if(input('param.field') && input('param.order')){
				$order = input('param.field').' '.input('param.order');
			}else{
				$order = 'sort desc,id';
			}
			$where = array();
			$where[] = ['aid','=',aid];
			$where[] = ['ktype','in','0,1'];
			if(input('param.keyword')) $where[] = ['keyword','like','%'.input('param.keyword').'%'];
			$count = 0 + Db::name('mp_keyword')->where($where)->count();
			$data = Db::name('mp_keyword')->where($where)->page($page,$limit)->order($order)->select()->toArray();
			return json(['code'=>0,'msg'=>'查询成功','count'=>$count,'data'=>$data]);
		}
		return View::fetch();
    }
	//编辑
	public function edit(){
		if(input('param.id')){
			$info = Db::name('mp_keyword')->where('aid',aid)->where('id',input('param.id/d'))->find();
		}else{
			$info = array('id'=>'');
		}
		if($info['msgtype'] == 'text'){
			$text = $info['content'];
		}elseif($info['msgtype'] == 'image'){
			$image = json_decode($info['content'],true);
		}elseif($info['msgtype'] == 'voice'){
			$voice = json_decode($info['content'],true);
		}elseif($info['msgtype'] == 'video'){
			$video = json_decode($info['content'],true);
		}elseif($info['msgtype'] == 'music'){
			$music = json_decode($info['content'],true);
		}elseif($info['msgtype'] == 'news'){
			$news = json_decode($info['content'],true);
		}elseif($info['msgtype'] == 'miniprogrampage'){
			$miniprogrampage = json_decode($info['content'],true);
		}
		View::assign('info',$info);
		View::assign('text',$text);
		View::assign('image',$image);
		View::assign('voice',$voice);
		View::assign('video',$video);
		View::assign('music',$music);
		View::assign('news',$news);
		View::assign('miniprogrampage',$miniprogrampage);
		return View::fetch();
	}
	public function save(){
		$info = input('post.info/a');
		if($info['msgtype'] == 'text'){
			$info['content'] = $_POST['text'];
		}elseif($info['msgtype'] == 'image'){
			$image = $_POST['image'];
			$image['MediaId'] = \app\common\Wechat::getmediaid(aid,$image['url']);
			$info['content'] = jsonEncode($image);
		}elseif($info['msgtype'] == 'voice'){
			$voice = $_POST['voice'];
			$voice['MediaId'] = \app\common\Wechat::getmediaid(aid,$voice['url'],'voice');
			$info['content'] = jsonEncode($voice);
		}elseif($info['msgtype'] == 'video'){
			$video = $_POST['video'];
			$video['MediaId'] = \app\common\Wechat::getmediaid(aid,$video['url'],'video',jsonEncode(['title'=>$video['title'],'introduction'=>$video['description']]));
			$info['content'] = jsonEncode($video);
		}elseif($info['msgtype'] == 'music'){
			$music = $_POST['music'];
			$info['content'] = jsonEncode($music);
		}elseif($info['msgtype'] == 'news'){
			$news = $_POST['news'];
			$info['content'] = jsonEncode($news);
		}elseif($info['msgtype'] == 'miniprogrampage'){
			$miniprogrampage = $_POST['miniprogrampage'];
            $miniprogrampage['MediaId'] = \app\common\Wechat::getmediaid(aid,$miniprogrampage['pic']);
            $info['content'] = jsonEncode($miniprogrampage);
		}
		if($info['id']){
			Db::name('mp_keyword')->where('aid',aid)->where('id',$info['id'])->update($info);
			\app\common\System::plog('编辑公众号关键字回复'.$info['id']);
		}else{
			$info['aid'] = aid;
			$info['createtime'] = time();
			$id = Db::name('mp_keyword')->insertGetId($info);
			\app\common\System::plog('添加公众号关键字回复'.$id);
		}
		return json(['status'=>1,'msg'=>'操作成功','url'=>(string)url('index')]);
	}
	//删除
	public function del(){
		$ids = input('post.ids/a');
		Db::name('mp_keyword')->where('aid',aid)->where('id','in',$ids)->delete();
		\app\common\System::plog('删除公众号关键字回复'.implode(',',$ids));
		return json(['status'=>1,'msg'=>'删除成功']);
	}
}
