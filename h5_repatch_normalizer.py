#!/usr/bin/env python3
"""
Repatch H5 webpack chunk files to use normalizer (828b) pattern for _hpg1/_hvg1 modules.

The old modules used a plain options object export:
  "_hpg1":function(t,e,a){"use strict";Object.defineProperty(e,"__esModule",{value:!0});e.default={...}}

The new modules use the normalizer pattern (matching dp-product's 3baa module):
  "_hpg1":function(t,e,a){"use strict";a.r(e);var i={...components...},s=function(){...render...},n=[],o=a("828b"),r=Object(o["a"])({...script...},s,n,!1,null,null,null,!1,i,void 0);e["default"]=r.exports}
"""

import re
import os
import sys
import glob

# Photo generation render function
PG_RENDER = (
    'function(){var t=this,e=t.$createElement,a=t._self._c||e;'
    'return a("v-uni-view",{staticClass:"dp-photo-generation",'
    'style:{backgroundColor:t.params.bgcolor,'
    'margin:2.2*t.params.margin_y+"rpx "+2.2*t.params.margin_x+"rpx 0",'
    'padding:2.2*t.params.padding_y+"rpx "+2.2*t.params.padding_x+"rpx",'
    'width:"calc(100% - "+2.2*2*t.params.margin_x+"rpx)"}},['
    '"1"==t.params.style||"2"==t.params.style||"3"==t.params.style'
    '?a("dp-product-item",{attrs:{'
    'showstyle:t.params.style,data:t.data,saleimg:t.params.saleimg,'
    'showname:t.params.showname,showprice:t.params.showprice,'
    'showsales:t.params.showsales,showcart:t.params.showcart,'
    'cartimg:t.params.cartimg,carttext:t.params.carttext,'
    'idfield:"proid",menuindex:t.menuindex,'
    'probgcolor:t.params.probgcolor,params:t.params,'
    'saleslabel:"\\u5df2\\u7528",cover_ratio:t.coverRatio,'
    'cover_radius:t.coverRadius,card_radius:t.cardRadius,'
    'btn_position:t.btnPosition,card_gap:t.cardGap,'
    'info_padding:t.infoPadding,'
    'detailurl:"/pagesZ/generation/create?type=1"'
    '}}):t._e(),'
    '"list"==t.params.style'
    '?a("dp-product-itemlist",{attrs:{'
    'data:t.data,saleimg:t.params.saleimg,'
    'showname:t.params.showname,showprice:t.params.showprice,'
    'showsales:t.params.showsales,showcart:t.params.showcart,'
    'cartimg:t.params.cartimg,carttext:t.params.carttext,'
    'idfield:"proid",menuindex:t.menuindex,'
    'probgcolor:t.params.probgcolor,'
    'saleslabel:"\\u5df2\\u7528",cover_ratio:t.coverRatio,'
    'cover_radius:t.coverRadius,card_radius:t.cardRadius,'
    'btn_position:t.btnPosition,info_padding:t.infoPadding,'
    'detailurl:"/pagesZ/generation/create?type=1"'
    '}}):t._e(),'
    '"line"==t.params.style'
    '?a("dp-product-itemline",{attrs:{'
    'data:t.data,saleimg:t.params.saleimg,'
    'showname:t.params.showname,showprice:t.params.showprice,'
    'showsales:t.params.showsales,showcart:t.params.showcart,'
    'cartimg:t.params.cartimg,carttext:t.params.carttext,'
    'idfield:"proid",menuindex:t.menuindex,'
    'probgcolor:t.params.probgcolor,'
    'saleslabel:"\\u5df2\\u7528",cover_ratio:t.coverRatio,'
    'cover_radius:t.coverRadius,card_radius:t.cardRadius,'
    'btn_position:t.btnPosition,info_padding:t.infoPadding,'
    'detailurl:"/pagesZ/generation/create?type=1"'
    '}}):t._e(),'
    '"waterfall"==t.params.style'
    '?a("dp-product-waterfall",{attrs:{'
    'list:t.data,saleimg:t.params.saleimg,'
    'showname:t.params.showname,showprice:t.params.showprice,'
    'showsales:t.params.showsales,showcart:t.params.showcart,'
    'cartimg:t.params.cartimg,carttext:t.params.carttext,'
    'idfield:"proid",menuindex:t.menuindex,'
    'probgcolor:t.params.probgcolor,'
    'saleslabel:"\\u5df2\\u7528",cover_ratio:t.coverRatio,'
    'cover_radius:t.coverRadius,card_radius:t.cardRadius,'
    'btn_position:t.btnPosition,card_gap:t.cardGap,'
    'info_padding:t.infoPadding,'
    'detailurl:"/pagesZ/generation/create?type=1"'
    '}}):t._e()],1)}'
)

# Video generation render function
VG_RENDER = (
    'function(){var t=this,e=t.$createElement,a=t._self._c||e;'
    'return a("v-uni-view",{staticClass:"dp-video-generation",'
    'style:{backgroundColor:t.params.bgcolor,'
    'margin:2.2*t.params.margin_y+"rpx "+2.2*t.params.margin_x+"rpx 0",'
    'padding:2.2*t.params.padding_y+"rpx "+2.2*t.params.padding_x+"rpx",'
    'width:"calc(100% - "+2.2*2*t.params.margin_x+"rpx)"}},['
    '"1"==t.params.style||"2"==t.params.style||"3"==t.params.style'
    '?a("dp-product-item",{attrs:{'
    'showstyle:t.params.style,data:t.data,saleimg:t.params.saleimg,'
    'showname:t.params.showname,showprice:t.params.showprice,'
    'showsales:t.params.showsales,showcart:t.params.showcart,'
    'cartimg:t.params.cartimg,carttext:t.params.carttext,'
    'idfield:"proid",menuindex:t.menuindex,'
    'probgcolor:t.params.probgcolor,params:t.params,'
    'saleslabel:"\\u5df2\\u7528",covertype:"video",cover_ratio:t.coverRatio,'
    'cover_radius:t.coverRadius,card_radius:t.cardRadius,'
    'btn_position:t.btnPosition,card_gap:t.cardGap,'
    'info_padding:t.infoPadding,'
    'detailurl:"/pagesZ/generation/create?type=2"'
    '}}):t._e(),'
    '"list"==t.params.style'
    '?a("dp-product-itemlist",{attrs:{'
    'data:t.data,saleimg:t.params.saleimg,'
    'showname:t.params.showname,showprice:t.params.showprice,'
    'showsales:t.params.showsales,showcart:t.params.showcart,'
    'cartimg:t.params.cartimg,carttext:t.params.carttext,'
    'idfield:"proid",menuindex:t.menuindex,'
    'probgcolor:t.params.probgcolor,'
    'saleslabel:"\\u5df2\\u7528",covertype:"video",cover_ratio:t.coverRatio,'
    'cover_radius:t.coverRadius,card_radius:t.cardRadius,'
    'btn_position:t.btnPosition,info_padding:t.infoPadding,'
    'detailurl:"/pagesZ/generation/create?type=2"'
    '}}):t._e(),'
    '"line"==t.params.style'
    '?a("dp-product-itemline",{attrs:{'
    'data:t.data,saleimg:t.params.saleimg,'
    'showname:t.params.showname,showprice:t.params.showprice,'
    'showsales:t.params.showsales,showcart:t.params.showcart,'
    'cartimg:t.params.cartimg,carttext:t.params.carttext,'
    'idfield:"proid",menuindex:t.menuindex,'
    'probgcolor:t.params.probgcolor,'
    'saleslabel:"\\u5df2\\u7528",covertype:"video",cover_ratio:t.coverRatio,'
    'cover_radius:t.coverRadius,card_radius:t.cardRadius,'
    'btn_position:t.btnPosition,info_padding:t.infoPadding,'
    'detailurl:"/pagesZ/generation/create?type=2"'
    '}}):t._e(),'
    '"waterfall"==t.params.style'
    '?a("dp-product-waterfall",{attrs:{'
    'list:t.data,saleimg:t.params.saleimg,'
    'showname:t.params.showname,showprice:t.params.showprice,'
    'showsales:t.params.showsales,showcart:t.params.showcart,'
    'cartimg:t.params.cartimg,carttext:t.params.carttext,'
    'idfield:"proid",menuindex:t.menuindex,'
    'probgcolor:t.params.probgcolor,'
    'saleslabel:"\\u5df2\\u7528",covertype:"video",cover_ratio:t.coverRatio,'
    'cover_radius:t.coverRadius,card_radius:t.cardRadius,'
    'btn_position:t.btnPosition,card_gap:t.cardGap,'
    'info_padding:t.infoPadding,'
    'detailurl:"/pagesZ/generation/create?type=2"'
    '}}):t._e()],1)}'
)

PG_COMPUTED = (
    'coverRatio:function(){return this.params.cover_ratio||"1:1"},'
    'coverRadius:function(){return void 0!==this.params.cover_radius?this.params.cover_radius:8},'
    'cardRadius:function(){return void 0!==this.params.card_radius?this.params.card_radius:8},'
    'btnPosition:function(){return this.params.btn_position||"bottom-right"},'
    'cardGap:function(){return void 0!==this.params.card_gap?this.params.card_gap:12},'
    'infoPadding:function(){return void 0!==this.params.info_padding?this.params.info_padding:12}'
)

VG_COMPUTED = (
    'coverRatio:function(){return this.params.cover_ratio||"3:4"},'
    'coverRadius:function(){return void 0!==this.params.cover_radius?this.params.cover_radius:8},'
    'cardRadius:function(){return void 0!==this.params.card_radius?this.params.card_radius:8},'
    'btnPosition:function(){return this.params.btn_position||"bottom-right"},'
    'cardGap:function(){return void 0!==this.params.card_gap?this.params.card_gap:12},'
    'infoPadding:function(){return void 0!==this.params.info_padding?this.params.info_padding:12}'
)


def build_normalizer_module(mod_id, sub_ids, render_fn, computed_str):
    """Build a webpack module using the normalizer (828b) pattern, matching dp-product's structure."""
    item_id, itemlist_id, itemline_id, waterfall_id = sub_ids
    # This matches the dp-product assembler module pattern exactly:
    # 1. a.r(e) marks as ES module
    # 2. var i = components map
    # 3. var s = render function
    # 4. var n = [] staticRenderFns
    # 5. var o = a("828b") normalizer
    # 6. var r = Object(o["a"])(scriptOptions, render, staticRenderFns, functional, injectStyles, scopeId, moduleId, isShadow, components, moduleContext)
    # 7. e["default"] = r.exports
    return (
        f'"{mod_id}":function(t,e,a){{"use strict";'
        f'a.r(e);'
        f'var i={{dpProductItem:a("{item_id}").default,'
        f'dpProductItemlist:a("{itemlist_id}").default,'
        f'dpProductItemline:a("{itemline_id}").default,'
        f'dpProductWaterfall:a("{waterfall_id}").default}},'
        f's={render_fn},'
        f'n=[],'
        f'o=a("828b"),'
        f'r=Object(o["a"])'
        f'({{props:{{menuindex:{{default:-1}},params:{{}},data:{{}}}},computed:{{{computed_str}}}}}'
        f',s,n,!1,null,null,null,!1,i,void 0);'
        f'e["default"]=r.exports}}'
    )


def extract_module_text(content, start_marker):
    """Extract complete module text from start_marker to its closing brace."""
    idx = content.find(start_marker)
    if idx == -1:
        return None, -1, -1
    # Track braces to find module end
    depth = 0
    i = idx
    started = False
    while i < len(content):
        ch = content[i]
        if ch == '{':
            depth += 1
            started = True
        elif ch == '}':
            depth -= 1
            if started and depth == 0:
                return content[idx:i+1], idx, i+1
        i += 1
    return None, -1, -1


def repatch_file(filepath):
    """Replace old _hpg1/_hvg1 modules with normalizer-based ones."""
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Check if file has old-style modules
    if '"_hpg1":function' not in content:
        return 'SKIP_NO_PATCH'

    # Check if already using normalizer pattern
    # The normalizer pattern has a("828b") inside the _hpg1 module
    hpg1_start = content.find('"_hpg1":function')
    hpg1_snippet = content[hpg1_start:hpg1_start+200]
    if '828b' in hpg1_snippet:
        return 'SKIP_ALREADY_NORMALIZER'

    # Extract sub-component module IDs from the old _hpg1 module
    # Pattern: "dp-product-item":a("ID").default
    old_hpg1_text, old_hpg1_start, old_hpg1_end = extract_module_text(content, '"_hpg1":function')
    if old_hpg1_text is None:
        return 'ERROR_EXTRACT_HPG1'

    old_hvg1_text, old_hvg1_start, old_hvg1_end = extract_module_text(content, '"_hvg1":function')
    if old_hvg1_text is None:
        return 'ERROR_EXTRACT_HVG1'

    # Extract sub-component IDs from the old module
    sub_match = re.search(
        r'"dp-product-item":\w\("([^"]+)"\)\.default,'
        r'"dp-product-itemlist":\w\("([^"]+)"\)\.default,'
        r'"dp-product-itemline":\w\("([^"]+)"\)\.default,'
        r'"dp-product-waterfall":\w\("([^"]+)"\)\.default',
        old_hpg1_text
    )
    if not sub_match:
        return 'ERROR_NO_SUB_IDS'

    item_id = sub_match.group(1)
    itemlist_id = sub_match.group(2)
    itemline_id = sub_match.group(3)
    waterfall_id = sub_match.group(4)
    sub_ids = (item_id, itemlist_id, itemline_id, waterfall_id)

    # Build new modules
    new_hpg1 = build_normalizer_module('_hpg1', sub_ids, PG_RENDER, PG_COMPUTED)
    new_hvg1 = build_normalizer_module('_hvg1', sub_ids, VG_RENDER, VG_COMPUTED)

    # Replace old modules with new ones
    # Important: replace _hvg1 first if it comes after _hpg1 (to maintain positions)
    if old_hvg1_start > old_hpg1_start:
        content = content[:old_hvg1_start] + new_hvg1 + content[old_hvg1_end:]
        content = content[:old_hpg1_start] + new_hpg1 + content[old_hpg1_end:]
    else:
        content = content[:old_hpg1_start] + new_hpg1 + content[old_hpg1_end:]
        # Recalculate hvg1 position after replacement
        old_hvg1_text2, old_hvg1_start2, old_hvg1_end2 = extract_module_text(content, '"_hvg1":function')
        if old_hvg1_text2 and '828b' not in old_hvg1_text2[:200]:
            content = content[:old_hvg1_start2] + new_hvg1 + content[old_hvg1_end2:]

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

    return 'OK'


def main():
    js_dir = '/home/www/ai.eivie.cn/h5/static/js'
    files = glob.glob(os.path.join(js_dir, '*.js'))

    print(f"=== Repatching {len(files)} JS files with normalizer pattern ===")

    stats = {}
    for filepath in sorted(files):
        try:
            result = repatch_file(filepath)
            stats[result] = stats.get(result, 0) + 1
        except Exception as e:
            stats['ERROR'] = stats.get('ERROR', 0) + 1
            print(f"  ERROR: {os.path.basename(filepath)}: {e}")

    print(f"\n=== Results ===")
    for k, v in sorted(stats.items()):
        if v > 0:
            print(f"  {k}: {v}")
    total_patched = stats.get('OK', 0)
    print(f"\n  Total files processed: {len(files)}")
    print(f"  Successfully repatched: {total_patched}")


if __name__ == '__main__':
    main()
