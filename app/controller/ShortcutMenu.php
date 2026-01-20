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


namespace app\controller;

use think\facade\Db;
use think\facade\View;

class ShortcutMenu extends Common
{
    public function index()
    {
        $shortMenu = Db::name("shortcut_menu")
            ->where('aid', aid)
            ->where('bid', bid)
            ->where('uid', uid)
            ->find();
        $shortMenu['menus'] = json_decode($shortMenu['menus'], true);
        // 根据权限筛选
        if ($this->auth_data != 'all') {
            $shortMenu['menus'] = array_filter($shortMenu['menus'], function($menu) {
                return in_array($menu['value'], $this->auth_data);
            });
        }
        View::assign('menus', $shortMenu['menus']);
        View::assign('menuValues', array_map(function($menu) {
            return $menu['value'];
        }, $shortMenu['menus']));
		
        $menudata = \app\common\Menu::getdata(aid,uid);
        // 剔除快捷菜单
        if (!empty($menudata['system'])) {
            $menudata['system']['child'] = array_filter($menudata['system']['child'], function($child) {
                return $child['path'] != 'ShortcutMenu/index';
            });
            if (empty($menudata['system']['child'])) {
                unset($menudata['system']);
            }
        }
        View::assign('menudata', $menudata);

        return View::fetch();
    }

    public function save()
    {
        $menus = input('post.menus');
        $shortMenu = Db::name("shortcut_menu")
            ->where('aid', aid)
            ->where('bid', bid)
            ->where('uid', uid)
            ->find();
        if (empty($shortMenu)) {
            Db::name("shortcut_menu")
                ->insert([
                    'aid' => aid,
                    'bid' => bid,
                    'uid' => uid,
                    'menus' => json_encode($menus, JSON_UNESCAPED_UNICODE)
                ]);
        } else {
            Db::name("shortcut_menu")
                ->where('aid', aid)
                ->where('bid', bid)
                ->where('uid', uid)
                ->update([
                    'menus' => json_encode($menus, JSON_UNESCAPED_UNICODE)
                ]);
        }
        
        return json(['status'=>1,'msg'=>'操作成功','url'=>(string)url('index')]);
    }
}