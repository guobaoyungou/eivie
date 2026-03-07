#!/usr/bin/env python3
"""
Patch H5 webpack chunk files to add dp-photo-generation and dp-video-generation components.

This script modifies compiled webpack chunks to inject:
1. Component entries in the dp component's components map
2. Render function ternaries for photo_generation and video_generation
3. New webpack module definitions for the two components
"""

import re
import os
import sys

# Photo generation render function (compiled from dp-photo-generation.vue template)
# Uses unicode escape \u5df2\u7528 for "已用"
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

# Video generation render function (compiled from dp-video-generation.vue template)
# Same as photo but with covertype:"video", cover_ratio default "3:4", detailurl type=2
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

# Computed properties (same for both, except coverRatio default)
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


def patch_toDetail_in_content(content):
    """Patch compiled toDetail functions to support detailurl attribute.

    The compiled dp-product-item/itemlist/itemline/waterfall components hardcode
    navigation to /pages/shop/product?id=X in their toDetail methods.
    This function injects a check at the beginning of each toDetail method:
    if the component receives a 'detailurl' attribute (via $attrs), it navigates
    to that URL instead, appending the item id as a query parameter.

    Uses uni.navigateTo() directly (globally available in uni-app H5) to avoid
    needing to know the component's local goto variable name.
    """
    # Skip if already patched
    if 'this.$attrs&&this.$attrs.detailurl' in content:
        return content, False

    # Only patch files that have our generation components
    if 'dp-photo-generation' not in content and 'dp-video-generation' not in content:
        return content, False

    # Pattern matches the beginning of toDetail functions that navigate to /pages/shop/product
    # Variant 1 (dp-product-item, itemlist, itemline): var e=this.data[t],a=e[this.idfield],...
    # Variant 2 (dp-product-waterfall): var e=this.list[t],a=e[this.idfield],...
    # Groups: 1=param, 2=item_var, 3=source(data|list), 4=id_var, 5=url_var
    pattern = re.compile(
        r'toDetail:function\((\w)\)\{'
        r'var (\w)=this\.(data|list)\[\1\],'
        r'(\w)=\2\[this\.idfield\],'
        r'(\w)="/pages/shop/product\?id="\+\4;'
    )

    def replacement(m):
        param = m.group(1)      # e.g. t
        item_var = m.group(2)   # e.g. e
        source = m.group(3)     # data or list
        id_var = m.group(4)     # e.g. a
        url_var = m.group(5)    # e.g. s or i

        # Build the detailurl check using uni.navigateTo (globally available)
        check = (
            f'if(this.$attrs&&this.$attrs.detailurl){{'
            f'var _did={item_var}[this.idfield];'
            f'uni.navigateTo({{url:this.$attrs.detailurl+'
            f'(this.$attrs.detailurl.indexOf("?")>-1?"&":"?")'
            f'+"id="+_did}});'
            f'return}}'
        )

        # Reconstruct: break the var statement after item assignment,
        # insert the check, then continue with a new var for the rest
        return (
            f'toDetail:function({param}){{'
            f'var {item_var}=this.{source}[{param}];'
            f'{check}'
            f'var {id_var}={item_var}[this.idfield],'
            f'{url_var}="/pages/shop/product?id="+{id_var};'
        )

    new_content = pattern.sub(replacement, content)
    changed = new_content != content

    # Also patch "data-url" render pattern used by older webpack builds.
    # These builds embed the navigation URL directly in the render function
    # as a data-url attribute, e.g.:
    #   "data-url":"/pages/shop/product?id="+a[t.idfield]
    # We replace with a ternary that checks for detailurl:
    #   "data-url":(t.$attrs&&t.$attrs.detailurl?...detailurl...:"...original...")
    dataurl_pattern = re.compile(
        r'"data-url":"/pages/shop/product\?id="\+(\w)\[(\w)\.idfield\]'
    )

    def dataurl_replacement(m):
        item_var = m.group(1)   # e.g. a
        ctx_var = m.group(2)    # e.g. t
        return (
            f'"data-url":({ctx_var}.$attrs&&{ctx_var}.$attrs.detailurl'
            f'?{ctx_var}.$attrs.detailurl+'
            f'({ctx_var}.$attrs.detailurl.indexOf("?")>-1?"&":"?")'
            f'+"id="+{item_var}[{ctx_var}.idfield]'
            f':"/pages/shop/product?id="+{item_var}[{ctx_var}.idfield])'
        )

    new_content2 = dataurl_pattern.sub(dataurl_replacement, new_content)
    if new_content2 != new_content:
        changed = True
        new_content = new_content2

    return new_content, changed


def build_module(mod_id, sub_ids, render_fn, computed_str):
    """Build a webpack module definition for a generation component."""
    item_id, itemlist_id, itemline_id, waterfall_id = sub_ids
    return (
        f'"{mod_id}":function(t,e,a){{"use strict";'
        f'Object.defineProperty(e,"__esModule",{{value:!0}});'
        f'e.default={{'
        f'components:{{'
        f'"dp-product-item":a("{item_id}").default,'
        f'"dp-product-itemlist":a("{itemlist_id}").default,'
        f'"dp-product-itemline":a("{itemline_id}").default,'
        f'"dp-product-waterfall":a("{waterfall_id}").default'
        f'}},'
        f'props:{{menuindex:{{default:-1}},params:{{}},data:{{}}}},'
        f'computed:{{{computed_str}}},'
        f'render:{render_fn}'
        f'}}}}'
    )


# --- Map anchor variants (order matters: try most specific first) ---
MAP_ANCHORS = [
    # New variant: dpHotelRoom is last in components map
    (r'(dpHotelRoom:(\w)\("([^"]+)"\)\.default)\}', 'dpHotelRoom'),
    # Old variant: dpCarhailing is last in components map
    (r'(dpCarhailing:(\w)\("([^"]+)"\)\.default)\}', 'dpCarhailing'),
]

# --- Render anchor variants ---
RENDER_ANCHORS = [
    # New variant: hotelroom is the last ternary before ]}))],2)}
    {
        'search': '"hotelroom"==e.temp',
        'end_marker': ']:t._e()]}))],2)}',
    },
    # Old variant: carhailing is the last ternary before ]}))],2)}
    {
        'search': '"carhailing"==e.temp',
        'end_marker': ']:t._e()]}))],2)}',
    },
]


def patch_file(filepath):
    """Patch a single webpack chunk file."""
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # Check if already patched
    if 'dpPhotoGeneration' in content or 'dp-photo-generation' in content:
        return 'SKIP_ALREADY_PATCHED'

    # Step 1: Find dp-product sub-component module IDs
    # Pattern: dpProductItem:VAR("ID").default,dpProductItemlist:VAR("ID").default,...
    sub_match = re.search(
        r'dpProductItem:(\w)\("([^"]+)"\)\.default,'
        r'dpProductItemlist:\1\("([^"]+)"\)\.default,'
        r'dpProductItemline:\1\("([^"]+)"\)\.default,'
        r'dpProductWaterfall:\1\("([^"]+)"\)\.default',
        content
    )
    if not sub_match:
        return 'SKIP_NO_SUB_COMPONENTS'

    req_var = sub_match.group(1)  # webpack_require variable name (a, i, etc.)
    item_id = sub_match.group(2)
    itemlist_id = sub_match.group(3)
    itemline_id = sub_match.group(4)
    waterfall_id = sub_match.group(5)

    # Step 2: Find the dp components map ending (try multiple anchors)
    map_match = None
    for pattern, anchor_name in MAP_ANCHORS:
        map_match = re.search(pattern, content)
        if map_match:
            break
    if not map_match:
        return 'SKIP_NO_MAP_ANCHOR'

    # Step 3: Find the render function's last ternary ending
    # Try multiple anchor variants; iterate ALL occurrences since
    # the same e.temp check can appear in both dp-tab and dp renders.
    # The dp render ends with ]}))],2)} while dp-tab ends with ]}))],2),t.loading
    render_var = None
    render_block_end = None  # position of ']:t._e()' end
    render_close_end = None  # position of ']}))],2)}' end
    found_render = False
    for anchor in RENDER_ANCHORS:
        search_start = 0
        while True:
            pos = content.find(anchor['search'], search_start)
            if pos < 0:
                break
            # Find the createElement variable: VAR("dp-..."
            var_match = re.search(r'\?\[(\w)\("dp-', content[pos:pos+100])
            if not var_match:
                search_start = pos + 1
                continue
            # Find the end marker after this position
            search_region = content[pos:pos+600]
            end_idx = search_region.find(anchor['end_marker'])
            if end_idx < 0:
                search_start = pos + 1
                continue
            # Found a match - verify it's the dp render (ends with ],2)}) not ],2),t.loading)
            render_var = var_match.group(1)
            render_block_end = pos + end_idx + len(']:t._e()')
            render_close_end = pos + end_idx + len(anchor['end_marker'])
            found_render = True
            break
        if found_render:
            break

    if render_var is None or render_block_end is None:
        return 'SKIP_NO_RENDER'

    # Step 4: Apply modifications

    # 4a: Extend components map
    old_map_end = map_match.group(1) + '}'
    new_map_end = (
        map_match.group(1) + ','
        f'dpPhotoGeneration:{req_var}("_hpg1").default,'
        f'dpVideoGeneration:{req_var}("_hvg1").default'
        '}'
    )
    content = content.replace(old_map_end, new_map_end, 1)

    # 4b: Extend render function - insert after the last ]:t._e() and before ]}))],2)}
    # After the map replacement, positions may have shifted, so re-find
    insert_snippet = (
        f',"photo_generation"==e.temp?[{render_var}("dp-photo-generation",{{attrs:{{params:e.params,data:e.data,menuindex:t.menuindex}}}})]'
        ':t._e()'
        f',"video_generation"==e.temp?[{render_var}("dp-video-generation",{{attrs:{{params:e.params,data:e.data,menuindex:t.menuindex}}}})]'
        ':t._e()'
    )
    # Re-find the render anchor position (shifted by map insertion)
    # Must iterate ALL occurrences to find the dp render (not dp-tab)
    render_inserted = False
    for anchor in RENDER_ANCHORS:
        search_start = 0
        while True:
            pos = content.find(anchor['search'], search_start)
            if pos < 0:
                break
            search_region = content[pos:pos+600]
            end_idx = search_region.find(anchor['end_marker'])
            if end_idx < 0:
                search_start = pos + 1
                continue
            insert_pos = pos + end_idx + len(']:t._e()')
            content = content[:insert_pos] + insert_snippet + content[insert_pos:]
            render_inserted = True
            break
        if render_inserted:
            break
    if not render_inserted:
        return 'SKIP_NO_RENDER_INSERT'

    # 4c: Add new module definitions
    sub_ids = (item_id, itemlist_id, itemline_id, waterfall_id)
    pg_module = build_module('_hpg1', sub_ids, PG_RENDER, PG_COMPUTED)
    vg_module = build_module('_hvg1', sub_ids, VG_RENDER, VG_COMPUTED)

    # Find the end of the modules object - pattern: }]);  or }])
    # The chunk format is: (window["webpackJsonp"]...).push([[...],{...modules...}]);
    # or: (window["webpackJsonp"]...).push([[...],{...modules...}])
    # We need to insert before the last }]
    # Find the last occurrence of the modules closing
    end_match = re.search(r'\}\]\);?\s*$', content)
    if end_match:
        insert_pos = end_match.start()
        # Insert before the closing }]
        # The modules object ends with }  before }]
        # We need to add ,moduledef before the } that closes the modules object
        # Actually, let's find the pattern more precisely
        # The last few chars should be: ...lastmodule_code }}]); 
        # We insert our modules before the closing }]);
        # The } before }] closes the modules object
        # So we insert: ,newmodule_code before that }
        # Find the } that closes the modules object (the one before ]);)
        # It's at insert_pos
        content = content[:insert_pos] + ',' + pg_module + ',' + vg_module + content[insert_pos:]
    else:
        return 'SKIP_NO_CHUNK_END'

    # Step 5: Patch toDetail functions to support detailurl
    content, td_changed = patch_toDetail_in_content(content)

    # Write the patched file
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

    return 'OK'


def patch_tab_file(filepath):
    """Patch dp-tab component in a webpack chunk file."""
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    # dp-tab's specific signature: getIndexdata + loading in render
    marker = 'getIndexdata.apply(void 0,arguments)'
    if marker not in content:
        return 'SKIP_NO_TAB'

    # Check if dp-tab already patched
    idx = 0
    already_patched = False
    while True:
        pos = content.find(marker, idx)
        if pos == -1:
            break
        after_text = content[pos:pos+800]
        if 'dp-photo-generation' in after_text and 't.loading' in after_text:
            already_patched = True
            break
        idx = pos + 1
    if already_patched:
        return 'SKIP_ALREADY_PATCHED'

    # Step 1: Find dp-product sub-component module IDs
    sub_match = re.search(
        r'dpProductItem:(\w)\("([^"]+)"\)\.default,'
        r'dpProductItemlist:\1\("([^"]+)"\)\.default,'
        r'dpProductItemline:\1\("([^"]+)"\)\.default,'
        r'dpProductWaterfall:\1\("([^"]+)"\)\.default',
        content
    )
    if not sub_match:
        return 'SKIP_NO_SUB_COMPONENTS'

    req_var = sub_match.group(1)
    item_id = sub_match.group(2)
    itemlist_id = sub_match.group(3)
    itemline_id = sub_match.group(4)
    waterfall_id = sub_match.group(5)

    # Step 2: Find dp-tab's components map - insert after dpFormdata entry
    # Match dpFormdata:VAR("ID").default followed by either , or }
    formdata_map_match = re.search(
        r'(dpFormdata:(\w)\("([^"]+)"\)\.default)([,}])',
        content
    )
    if not formdata_map_match:
        return 'SKIP_NO_TAB_MAP'

    tab_req_var = formdata_map_match.group(2)

    # Step 3: Find dp-tab's render ending using getIndexdata + t.loading
    # Iterate ALL occurrences of getIndexdata to find the one near tab close
    tab_render_close = ']}))],2),t.loading'
    render_patched = False
    search_start = 0
    while True:
        gidx = content.find(marker, search_start)
        if gidx == -1:
            break
        after_gidx = content[gidx + len(marker):gidx + len(marker) + 200]
        close_idx = after_gidx.find(tab_render_close)
        if close_idx != -1:
            full_end_start = gidx + len(marker)
            full_end_pos = full_end_start + close_idx
            between = content[full_end_start:full_end_pos]
            # Extract createElement var from ANY nearby dp- component
            backward_search = content[max(0, gidx-300):gidx]
            var_match = re.search(r'(\w)\("dp-[a-z-]+"', backward_search)
            if var_match:
                render_var = var_match.group(1)
                old_text = between + tab_render_close
                new_text = (
                    between.rstrip()
                    + f',"photo_generation"==e.temp?[{render_var}("dp-photo-generation",{{attrs:{{params:e.params,data:e.data,menuindex:t.menuindex}}}})]'
                    ':t._e()'
                    f',"video_generation"==e.temp?[{render_var}("dp-video-generation",{{attrs:{{params:e.params,data:e.data,menuindex:t.menuindex}}}})]'
                    ':t._e()'
                    + tab_render_close
                )
                content = content[:full_end_start] + new_text + content[full_end_pos + len(tab_render_close):]
                render_patched = True
                break
        search_start = gidx + 1

    if not render_patched:
        return 'SKIP_NO_TAB_RENDER'

    # Step 4a: Extend dp-tab components map - insert after dpFormdata entry
    # Re-find after render patch (positions may have shifted)
    formdata_map_match = re.search(
        r'(dpFormdata:(\w)\("([^"]+)"\)\.default)([,}])',
        content
    )
    if formdata_map_match:
        insert_after = formdata_map_match.group(1)
        next_char = formdata_map_match.group(4)
        generation_entries = (
            f',dpPhotoGeneration:{tab_req_var}("_hpg1").default'
            f',dpVideoGeneration:{tab_req_var}("_hvg1").default'
        )
        # Insert generation entries after dpFormdata entry
        old_text = insert_after + next_char
        new_text = insert_after + generation_entries + next_char
        content = content.replace(old_text, new_text, 1)

    # Step 4b: Add module definitions (only if not already present from dp patch)
    if '"_hpg1":function(' not in content:
        sub_ids = (item_id, itemlist_id, itemline_id, waterfall_id)
        pg_module = build_module('_hpg1', sub_ids, PG_RENDER, PG_COMPUTED)
        vg_module = build_module('_hvg1', sub_ids, VG_RENDER, VG_COMPUTED)

        end_match = re.search(r'\}\]\);?\s*$', content)
        if end_match:
            insert_pos = end_match.start()
            content = content[:insert_pos] + ',' + pg_module + ',' + vg_module + content[insert_pos:]
        else:
            return 'SKIP_NO_CHUNK_END'

    # Step 5: Patch toDetail functions to support detailurl
    content, td_changed = patch_toDetail_in_content(content)

    # Write the patched file
    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

    return 'OK'


def patch_toDetail_only(filepath):
    """Patch only the toDetail functions in an already-patched file."""
    with open(filepath, 'r', encoding='utf-8') as f:
        content = f.read()

    content, changed = patch_toDetail_in_content(content)
    if not changed:
        return 'SKIP_NO_CHANGE'

    with open(filepath, 'w', encoding='utf-8') as f:
        f.write(content)

    return 'OK'


def run_patch(files, patch_fn, label):
    """Run a patch function on a list of files and report stats."""
    print(f"=== Patching {label} in {len(files)} files ===")
    stats = {}
    for filepath in files:
        try:
            result = patch_fn(filepath)
            stats[result] = stats.get(result, 0) + 1
            if result == 'OK':
                print(f"  PATCHED: {os.path.basename(filepath)}")
            elif result not in ('SKIP_ALREADY_PATCHED',):
                print(f"  {result}: {os.path.basename(filepath)}")
        except Exception as e:
            stats['ERROR'] = stats.get('ERROR', 0) + 1
            print(f"  ERROR: {os.path.basename(filepath)}: {e}")
    print(f"\n=== {label} Results ===")
    for k, v in sorted(stats.items()):
        if v > 0:
            print(f"  {k}: {v}")
    print(f"  Total files: {len(files)}")
    return stats


def main():
    import glob
    mode = sys.argv[1] if len(sys.argv) > 1 else 'dp'

    if mode == 'dp':
        with open('/tmp/h5_dp_files.txt', 'r') as f:
            files = [line.strip() for line in f.readlines() if line.strip()]
        run_patch(files, patch_file, 'dp component')
    elif mode == 'tab':
        with open('/tmp/h5_dp_tab_files.txt', 'r') as f:
            files = [line.strip() for line in f.readlines() if line.strip()]
        run_patch(files, patch_tab_file, 'dp-tab component')
    elif mode == 'scan':
        # Auto-scan mode: find and patch ALL unpatched files in h5/static/js/
        js_dir = sys.argv[2] if len(sys.argv) > 2 else 'h5/static/js'
        all_js = sorted(glob.glob(os.path.join(js_dir, '*.js')))
        print(f"Scanning {len(all_js)} JS files in {js_dir}...")
        # Phase 1: patch dp component
        dp_candidates = []
        tab_candidates = []
        for f in all_js:
            with open(f, 'r') as fh:
                c = fh.read()
            # dp component candidate: has render ternaries with e.temp and dpWxad
            if 'dpWxad' in c and ('"carhailing"==e.temp' in c or '"hotelroom"==e.temp' in c):
                if 'dpPhotoGeneration' not in c and 'dp-photo-generation' not in c:
                    dp_candidates.append(f)
            # dp-tab candidate: has dpFormdata and getIndexdata
            if 'dpFormdata' in c and 'getIndexdata.apply(void 0,arguments)' in c:
                # Check if dp-tab already patched
                idx = 0
                already = False
                marker = 'getIndexdata.apply(void 0,arguments)'
                while True:
                    pos = c.find(marker, idx)
                    if pos == -1:
                        break
                    after = c[pos:pos+800]
                    if 'dp-photo-generation' in after and 't.loading' in after:
                        already = True
                        break
                    idx = pos + 1
                if not already:
                    tab_candidates.append(f)
        print(f"\nFound {len(dp_candidates)} unpatched dp files, {len(tab_candidates)} unpatched dp-tab files")
        if dp_candidates:
            print()
            run_patch(dp_candidates, patch_file, 'dp component')
        if tab_candidates:
            print()
            run_patch(tab_candidates, patch_tab_file, 'dp-tab component')
        if not dp_candidates and not tab_candidates:
            print("All files are already patched!")

        # Phase 3: fix toDetail in all patched files (including previously patched)
        print("\n=== Phase 3: Fixing toDetail functions ===")
        todetail_candidates = []
        for f in all_js:
            with open(f, 'r') as fh:
                c = fh.read()
            if ('dp-photo-generation' in c or 'dp-video-generation' in c):
                if 'this.$attrs&&this.$attrs.detailurl' not in c:
                    todetail_candidates.append(f)
        if todetail_candidates:
            run_patch(todetail_candidates, patch_toDetail_only, 'toDetail fix')
        else:
            print("All toDetail functions are already fixed!")
    elif mode == 'fix_todetail':
        # Fix toDetail functions in all patched files
        js_dir = sys.argv[2] if len(sys.argv) > 2 else 'h5/static/js'
        all_js = sorted(glob.glob(os.path.join(js_dir, '*.js')))
        print(f"Scanning {len(all_js)} JS files in {js_dir} for toDetail fix...")
        candidates = []
        for f in all_js:
            with open(f, 'r') as fh:
                c = fh.read()
            if ('dp-photo-generation' in c or 'dp-video-generation' in c):
                if 'this.$attrs&&this.$attrs.detailurl' not in c:
                    candidates.append(f)
        if candidates:
            run_patch(candidates, patch_toDetail_only, 'toDetail fix')
        else:
            print("All toDetail functions are already fixed!")
    else:
        print(f"Unknown mode: {mode}")
        print("Usage: python3 h5_patch_generation.py [dp|tab|scan|fix_todetail] [js_dir]")
        sys.exit(1)


if __name__ == '__main__':
    main()
