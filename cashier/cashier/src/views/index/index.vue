<template>
  <div class="content flex_y_center">
    <div class="inventory flex">
      <div v-if="form.updateState == '2' || form.updateState == '3'" class="inventory_alert"></div>
      <div class="inventory_head flex_y_center flex_bt">
        <span class="inventory_title">结算清单（ <span v-if="form.dataInfo.prolist">{{ form.dataInfo.prolist.length }}</span>
          <span v-else>0</span> 件）</span>
        <span>会员：<span :style="'color:' + storeInfo.userInfo.color1">{{ form.memberUserInfo.tel || '无' }}</span></span>
      </div>
      <div class="flex_y1 body">
        <div v-if="form.dataInfo.prolist">
          <div class="module flex_y_center" v-for="(item, index) in form.dataInfo.prolist" :key="index">
            <el-image class="module_image" :src="item.propic">
              <template #error>
                <div class="image-slot">
                  <el-icon>
                    <icon-picture />
                  </el-icon>
                </div>
              </template>
            </el-image>
            <div class="flex_x1">
              <div class="module_title flex">
                <div class="flex_x1 h1">
                  <div class="module_text h1">{{ item.proname }}</div>
                </div>
                <div @click="updateClick('1', item)" v-if="item.protype == '1'" class="module_up flex_r">改价</div>
                <div @click="delShop(item.id)" class="module_up flex_r">删除</div>
              </div>
              <div class="module_unit">规格：{{ item.ggname }}</div>
              <div class="flex_y_center">
                <div :style="'color:' + storeInfo.userInfo.color1" class="module_price flex_x1">
                  ￥{{ item.sell_price }}
                </div>
                <div class="module_opt flex">
                  <div class="module_icon flex_xy_center" @click="cutShop(item.id, item.num)">-</div>
                  <input type="text" v-model="item.num" @focusin="numberIn(index)" @focusout="numberOut()"
                    @blur="getProNum(item)" @change="getProState"
                    :style="form.numberFocus == 1 && form.numberIndex == index ? 'outline: 1px solid' + storeInfo.userInfo.color1 : ''"
                    class="module_input flex_x1">
                  <div class="module_icon flex_xy_center" @click="addShop(item.id, item.num)">+</div>
                </div>
                <div :style="'color:' + storeInfo.userInfo.color1" class="module_total flex_x1">
                  ￥{{ item.totalprice }}
                </div>
              </div>
            </div>
          </div>
        </div>
        <div v-if="!form.dataInfo.prolist.length" class="body_null">
          <img src="../../assets/images/receipt.png" class="body_icon" alt="">
          <div class="body_title">未添加任何商品</div>
        </div>
      </div>
      <div v-if="form.dataInfo.prolist.length" class="price flex_x_bottom ">应收金额：<span
          :style="'color:' + storeInfo.userInfo.color1 + ';font-size:24px'">¥{{ form.dataInfo.totalprice }}</span></div>
      <div v-if="form.dataInfo.prolist.length" class="group flex">
        <div @click="toCancel"
          :style="'color:' + storeInfo.userInfo.color1 + ';border:1px solid' + storeInfo.userInfo.color1"
          class="group_btn flex_x1 flex_xy_center">取消</div>
        <div @click="toRemark"
          :style="'color:' + storeInfo.userInfo.color1 + ';border:1px solid' + storeInfo.userInfo.color1"
          class="group_btn flex_x1 flex_xy_center">备注</div>
        <div @click="toHangup"
          :style="'color:' + storeInfo.userInfo.color1 + ';border:1px solid' + storeInfo.userInfo.color1"
          class="group_btn flex_x1 flex_xy_center">挂单</div>
        <div @click="toZero"
          :style="'color:' + storeInfo.userInfo.color1 + ';border:1px solid' + storeInfo.userInfo.color1"
          class="group_btn flex_x1 flex_xy_center">
          <span v-if="form.remove_zero == '0'">抹零</span>
          <span v-if="form.remove_zero == '1'">取消抹零</span>
        </div>
        <div @click="toMember"
          :style="'color:#fff' + ';border:1px solid' + storeInfo.userInfo.color1 + ';background:' + storeInfo.userInfo.color1"
          class="group_btn flex_x1 flex_xy_center">收款</div>
      </div>
    </div>
    <div v-if="form.updateState == '0'" class="operate flex flex_x1">
      <div class="operate_table flex">
        <div v-for="(item, index) in form.operateTable" :key="index" class="operate_item"
          :style="form.operateIndex == index ? 'color:' + storeInfo.userInfo.color1 + ';border-bottom:2px solid' + storeInfo.userInfo.color1 : ''"
          @click="operateClick(item.value)">
          <div v-if="form.dataInfoHang.length">
            <div v-if="index == 2" :style="'background:' + storeInfo.userInfo.color1" class="operate_num">{{
                form.dataInfoHang.length
            }}</div>
          </div>
          {{ item.lable }}
        </div>
      </div>
      <div v-if="form.operateIndex == 0" class="operate_body flex_y1 flex">
        <div class="shop flex_y1 flex">
          <div class="shop_body flex flex_x1">
            <div class="shop_search flex_y_center">
              <el-icon class="shop_icon" color="#808695" :size="20">
                <Search />
              </el-icon>
              <input @focusin="shopIn" @focusout="shopOut" type="text" v-model="form.shopKeyWord.name"
                class="shop_input flex_x1"
                :style="form.shopFocus == 1 ? 'outline: 1px solid' + storeInfo.userInfo.color1 : ''"
                placeholder="输入商品条形码或商品名称" />
            </div>
            <div class="shop_list flex_y1" v-show="form.shopList.length" v-infinite-scroll="shopLoad">
              <div class="flex_wp">
                <div v-for="(item, index) in form.shopList" :key="index" @click="doAddToCash(item)"
                  class="shop_module flex flex_y_center">
                  <el-image class="shop_img" :src="item.pic">
                    <template #error>
                      <div class="image-slot">
                        <el-icon>
                          <icon-picture />
                        </el-icon>
                      </div>
                    </template>
                  </el-image>
                  <div class="flex_x1">
                    <div class="shop_title h2">{{ item.name }}</div>
                    <div class="shop_data flex flex_bt">
                      <span :style="'color:' + storeInfo.userInfo.color1">￥{{ item.sell_price }}</span>
                      <span class="shop_stock">库存:{{ item.stock }}</span>
                    </div>
                  </div>
                </div>
              </div>
              <div v-if="form.shopMoreType" class="shop_more">暂无更多</div>
            </div>
            <div v-if="!form.shopList.length" class="shop_notice">
              <img src="../../assets/images/scan.png" alt="">
              <div class="text">可使用扫码枪或输入商品名称/条码查找商品</div>
            </div>
          </div>
          <div class="shop_table">
            <div v-for="(item, index) in form.shopTable" :key="index" :title="item.name" class="shop_item h1"
              :style="form.shopIndex == index ? 'color:' + storeInfo.userInfo.color1 + ';background:rgba(' + storeInfo.userInfo.color1rgb.red + ',' + storeInfo.userInfo.color1rgb.green + ',' + storeInfo.userInfo.color1rgb.blue + ',0.2)' : ''"
              @click="shopClick(index, item.id)">{{ item.name }}</div>
          </div>
        </div>
      </div>
      <div v-if="form.operateIndex == 1" class="operate_body flex_y1 flex_xy_center">
        <div class="key" v-if="form.memberState == '1'">
          <div class="key_title">请输入会员手机号或会员码查询会员
            <!-- ，或输入要办理会员的手机号进行办理 -->
          </div>
          <!-- <div class="key_text">(若会员未绑定手机号，可通过扫码枪扫描/输入会员码登录会员)</div> -->
          <input @focusin="memberIn" @focusout="memberOut" type="text" class="key_input"
            :style="form.memberFocus == 1 ? 'outline: 1px solid' + storeInfo.userInfo.color1 : ''"
            v-model="form.memberData" placeholder="输入会员手机号/会员码" />
          <div class="key_module flex">
            <div class="key_content flex flex_wp">
              <div @mouseup="keyUp" @mousedown="keyDown(7)" class="key_item"
                :class="form.keyIndex == '7' ? 'item_active' : ''">7</div>
              <div @mouseup="keyUp" @mousedown="keyDown(8)" class="key_item"
                :class="form.keyIndex == '8' ? 'item_active' : ''">8</div>
              <div @mouseup="keyUp" @mousedown="keyDown(9)" class="key_item"
                :class="form.keyIndex == '9' ? 'item_active' : ''">9</div>
              <div @mouseup="keyUp" @mousedown="keyDown(4)" class="key_item"
                :class="form.keyIndex == '4' ? 'item_active' : ''">4</div>
              <div @mouseup="keyUp" @mousedown="keyDown(5)" class="key_item"
                :class="form.keyIndex == '5' ? 'item_active' : ''">5</div>
              <div @mouseup="keyUp" @mousedown="keyDown(6)" class="key_item"
                :class="form.keyIndex == '6' ? 'item_active' : ''">6</div>
              <div @mouseup="keyUp" @mousedown="keyDown(1)" class="key_item"
                :class="form.keyIndex == '1' ? 'item_active' : ''">1</div>
              <div @mouseup="keyUp" @mousedown="keyDown(2)" class="key_item"
                :class="form.keyIndex == '2' ? 'item_active' : ''">2</div>
              <div @mouseup="keyUp" @mousedown="keyDown(3)" class="key_item"
                :class="form.keyIndex == '3' ? 'item_active' : ''">3</div>
              <div class="key_item"></div>
              <div @mouseup="keyUp" @mousedown="keyDown(0)" class="key_item"
                :class="form.keyIndex == '0' ? 'item_active' : ''">0</div>
              <div class="key_item"></div>
            </div>
            <div class="key_opt">
              <div @mouseup="keyUp" @mousedown="keyDown('clear')" class="key_item key_row"
                :class="form.keyIndex == 'clear' ? 'item_active' : ''">清除</div>
              <div @mouseup="keyUp" @mousedown="keyDown('delete')" class="key_item key_row"
                :class="form.keyIndex == 'delete' ? 'item_active' : ''">
                <el-icon>
                  <CircleClose />
                </el-icon>
              </div>
              <div @mouseup="keyUp" @mousedown="keyDown('confirm')" class="key_confirm"
                :style="form.keyIndex == 'confirm' ? 'border-color:' + storeInfo.userInfo.color1 + ';background:' + storeInfo.userInfo.color1 : 'border-color:' + storeInfo.userInfo.color1 + ';background:' + storeInfo.userInfo.color1 + ';opacity:0.8'">
                确定</div>
            </div>
          </div>
        </div>
        <div class="memberDetail" v-if="form.memberState == '2'">
          <div class="memberDetail_user">
            <img src="../../assets/images/noface.png" class="memberDetail_head" alt="" />
            <div class="memberDetail_data">
              <div class="memberDetail_name">{{ form.memberUserInfo.nickname }}<div
                  :style="'color:' + storeInfo.userInfo.color1" class="memberDetail_tag">{{
                      form.memberUserInfo.level_name
                  }}</div>
              </div>
              <div class="memberDetail_phone">{{ form.memberUserInfo.tel }}</div>
            </div>
            <div @click="changeMember"
              :style="'color:' + storeInfo.userInfo.color1 + ';border-color:' + storeInfo.userInfo.color1"
              class="memberDetail_btn">切换会员</div>
          </div>
          <div class="memberDetail_module">
            <div class="memberDetail_item">
              <div class="text">余额</div>
              <div class="num">{{ form.memberUserInfo.money }}</div>
            </div>
            <div class="memberDetail_item">
              <div class="text">积分</div>
              <div class="num">{{ form.memberUserInfo.score }}</div>
            </div>
            <div class="memberDetail_item">
              <div class="text">优惠券</div>
              <div class="num">{{ form.memberUserInfo.couponcount }} <span v-if="form.memberUserInfo.couponcount != 0"
                  @click="checkCoupon" :style="'color:' + storeInfo.userInfo.color1" class="opt">查看</span>
              </div>
            </div>
          </div>
          <div class="memberDetail_title">会员信息</div>
          <div class="memberDetail_list">
            <div class="item">
              <div class="title">会员注册时间：</div>
              <div class="text">{{ form.memberUserInfo.createtime || '-' }}</div>
            </div>
            <div class="item">
              <div class="title">生日：</div>
              <div class="text">{{ form.memberUserInfo.birthday || '-' }}</div>
            </div>
            <div class="item">
              <div class="title">默认收货地址：</div>
              <div class="text">{{ form.memberUserInfo.birthday || '-' }}</div>
            </div>
            <div class="item">
              <div class="title">备注：</div>
              <div class="text">{{ form.memberUserInfo.remark || '-' }}</div>
            </div>
          </div>
        </div>
      </div>
      <div v-if="form.operateIndex == 2" class="operate_body flex_y1">
        <div class="take">
          <div v-if="form.dataInfoHang.length" class="take_module">
            <div v-for="(item, index) in form.dataInfoHang" :key="index" class="take_list">
              <div class="take_date">挂单时间：{{ item.hangup_time }}</div>
              <div class="flex flex_wp">
                <div v-for="(items, indexs) in item.prolist" :key="indexs" class="take_item">
                  <div class="take_title h1">{{ items.proname }}</div>
                  <div class="take_data flex_bt">
                    <span :style="'color:' + storeInfo.userInfo.color1">¥{{ items.sell_price }}</span>
                    <span class="take_num">x{{ items.num }}</span>
                  </div>
                </div>
              </div>
              <div class="take_opt flex_bt flex_y_center">
                <div class="take_order">
                  订单金额：<span :style="'color:' + storeInfo.userInfo.color1">¥{{ item.pre_totalprice }}</span>
                </div>
                <div class="flex">
                  <div class="take_btn"
                    :style="'color:' + storeInfo.userInfo.color1 + ';border-color:' + storeInfo.userInfo.color1"
                    @click="doDelCashierOrder(item.id)">删除</div>
                  <div class="take_btn" :style="'color:#fff' + ';background:' + storeInfo.userInfo.color1"
                    @click="doCancelHangup(item)">取单</div>
                </div>
              </div>
            </div>
          </div>
          <div v-if="!form.dataInfoHang.length" class="take_null">
            <img src="../../assets/images/order.png" class="take_icon" alt="">
            <div class="take_text">暂无数据</div>
          </div>
        </div>
      </div>
      <div v-if="form.operateIndex == 3" class="operate_body flex_y1 flex_xy_center">
        <div class="key">
          <div class="key_title">输入收款金额，金额加入结算清单中进行结算。</div>
          <div class="key_data">
            <input @focusin="directIn" @focusout="directOut" type="text" class="key_input"
              :style="form.directFocus == 1 ? 'outline: 1px solid' + storeInfo.userInfo.color1 : ''"
              v-model="form.directData" placeholder="请输入需要的金额" />
            <div class="key_unit">元</div>
          </div>
          <div class="key_module flex">
            <div class="key_content flex flex_wp">
              <div @mouseup="keyUp" @mousedown="keyDown(7)" class="key_item"
                :class="form.keyIndex == '7' ? 'item_active' : ''">7</div>
              <div @mouseup="keyUp" @mousedown="keyDown(8)" class="key_item"
                :class="form.keyIndex == '8' ? 'item_active' : ''">8</div>
              <div @mouseup="keyUp" @mousedown="keyDown(9)" class="key_item"
                :class="form.keyIndex == '9' ? 'item_active' : ''">9</div>
              <div @mouseup="keyUp" @mousedown="keyDown(4)" class="key_item"
                :class="form.keyIndex == '4' ? 'item_active' : ''">4</div>
              <div @mouseup="keyUp" @mousedown="keyDown(5)" class="key_item"
                :class="form.keyIndex == '5' ? 'item_active' : ''">5</div>
              <div @mouseup="keyUp" @mousedown="keyDown(6)" class="key_item"
                :class="form.keyIndex == '6' ? 'item_active' : ''">6</div>
              <div @mouseup="keyUp" @mousedown="keyDown(1)" class="key_item"
                :class="form.keyIndex == '1' ? 'item_active' : ''">1</div>
              <div @mouseup="keyUp" @mousedown="keyDown(2)" class="key_item"
                :class="form.keyIndex == '2' ? 'item_active' : ''">2</div>
              <div @mouseup="keyUp" @mousedown="keyDown(3)" class="key_item"
                :class="form.keyIndex == '3' ? 'item_active' : ''">3</div>
              <div class="key_item"></div>
              <div @mouseup="keyUp" @mousedown="keyDown(0)" class="key_item"
                :class="form.keyIndex == '0' ? 'item_active' : ''">0</div>
              <div @mouseup="keyUp" @mousedown="keyDown('.')" class="key_item"
                :class="form.keyIndex == '.' ? 'item_active' : ''">.</div>
            </div>
            <div class="key_opt">
              <div @mouseup="keyUp" @mousedown="keyDown('clear')" class="key_item key_row"
                :class="form.keyIndex == 'clear' ? 'item_active' : ''">清除</div>
              <div @mouseup="keyUp" @mousedown="keyDown('delete')" class="key_item key_row"
                :class="form.keyIndex == 'delete' ? 'item_active' : ''">
                <el-icon>
                  <CircleClose />
                </el-icon>
              </div>
              <div @mouseup="keyUp" @mousedown="keyDown('confirm')" class="key_confirm"
                :style="form.keyIndex == 'confirm' ? 'border-color:' + storeInfo.userInfo.color1 + ';background:' + storeInfo.userInfo.color1 : 'border-color:' + storeInfo.userInfo.color1 + ';background:' + storeInfo.userInfo.color1 + ';opacity:0.8'">
                确定</div>
            </div>
          </div>
        </div>
      </div>
    </div>
    <div v-if="form.updateState == '1'" class="operate flex_x1">
      <div class="update">
        <div class="update_title flex_y_center flex_bt">
          <span>改价</span>
          <el-icon @click="updateClick('0', false)" class="update_icon">
            <Close />
          </el-icon>
        </div>
        <div class="update_body">
          <div class="update_module flex_y_center">
            <el-image class="update_img" :src="form.shopUpdate.propic">
              <template #error>
                <div class="image-slot">
                  <el-icon>
                    <icon-picture />
                  </el-icon>
                </div>
              </template>
            </el-image>
            <div class="flex_x1">
              <div class="h2">{{ form.shopUpdate.proname }}</div>
              <div class="update_data flex flex_bt">
                <span :style="'color:' + storeInfo.userInfo.color1">￥{{ form.shopUpdate.sell_price }}</span>
                <span class="update_stock">库存:{{ form.shopUpdate.stock }}</span>
              </div>
            </div>
          </div>
          <div class="update_item flex_y_center">
            <div class="update_lable">现价</div>￥{{ form.shopUpdate.sell_price }}
          </div>
          <div class="update_item flex_y_center">
            <div class="update_lable">定价</div>
            <div class="update_set">
              <input type="text" class="update_input" @focusin="updateIn" @focusout="updateOut"
                :style="form.updateFocus == 1 ? 'outline: 1px solid' + storeInfo.userInfo.color1 : ''"
                v-model="form.shopUpdate.upPrice" />
              <div class="update_unit">元</div>
            </div>
          </div>
        </div>
        <div class="update_opt flex flex_x_bottom">
          <div :style="'color:' + storeInfo.userInfo.color1 + ';border-color:' + storeInfo.userInfo.color1"
            @click="updateClick('0', false)" class="update_btn flex_xy_center">取消</div>
          <div :style="'color:#fff' + ';background:' + storeInfo.userInfo.color1" @click="doUpPrice"
            class="update_btn flex_xy_center">确定</div>
        </div>
      </div>
    </div>
    <div v-if="form.updateState == '2'" class="operate flex_x1">
      <div class="pay">
        <div class="pay_body">
          <div class="pay_header">
            <div class="pay_data">
              <img src="../../assets/images/noface.png" class="pay_img" alt="" />
              <div>
                <div class="pay_phone">{{ form.memberUserInfo.nickname }}</div>
                <div class="pay_phone">{{ form.memberUserInfo.tel }}</div>
              </div>
              <div @click="changeMember"
                :style="'color:' + storeInfo.userInfo.color1 + ';border-color:' + storeInfo.userInfo.color1"
                class="pay_change">切换会员</div>
            </div>
            <div class="pay_item">
              <div class="title">会员积分</div>
              <div :style="'color:' + storeInfo.userInfo.color1" class="num">{{ form.memberUserInfo.score }}</div>
            </div>
            <div class="pay_item">
              <div class="title">会员余额</div>
              <div :style="'color:' + storeInfo.userInfo.color1" class="num">￥{{ form.memberUserInfo.money }}</div>
            </div>
          </div>
          <div class="pay_title">收款金额：¥{{ form.payInfo.totalprice }}</div>
          <div class="pay_text flex_xy_center pay_style">
            支付方式：
            <el-radio-group class="flex_y_center" v-model="form.payData.paytype">
              <el-radio class="pay_type" v-for="(item, index) in form.payTypeListMember" :key="index"
                :label="item.value">{{ item.lable }}</el-radio>
            </el-radio-group>
          </div>
          <div v-if="form.payInfo != ''" class="pay_text flex_xy_center">
            会员折扣({{ form.payInfo.memberinfo.discount }}折)：<span :style="'color:' + storeInfo.userInfo.color1">-￥{{
                form.payInfo.leveldk_money
            }}</span>
          </div>
          <div class="pay_text flex_xy_center" v-if="form.payInfo.couponlist.length">
            <div class="pay_name">优惠券：</div>
            <div class="pay_content">
              <el-space wrap>
                <el-card class="pay_card"
                  :style="form.payData.couponid == item.id ? 'color:' + storeInfo.userInfo.color1 + ';border-color:' + storeInfo.userInfo.color1 : ''"
                  @click="couponClick(item)" v-for="(item, index) in form.payInfo.couponlist" :key="index"
                  shadow="always">
                  <div class="pay_lable">{{ item.couponname }}</div>
                  <div class="pay_lable">有效期：{{ item.endtime }}</div>
                </el-card>
              </el-space>
            </div>
          </div>
          <div v-if="form.payInfo.memberinfo.score != 0" class="pay_text flex_xy_center">
            {{ form.payInfo.memberinfo.score }}积分：
            <el-checkbox style="margin-right:3px" @change="getDeduct($event)" label="使用积分抵扣" />
            <span :style="'color:' + storeInfo.userInfo.color1" style="margin-left:5px"
              v-if="form.payData.userscore == '1'">-￥{{
                  form.payInfo.scoredk_money
              }}</span>
          </div>
          <div v-if="form.payInfo != ''" class="pay_text flex_xy_center">
            抹零金额：-￥{{ form.payInfo.moling_money }}
          </div>
          <div class="pay_total flex_xy_center">实付金额：¥{{ form.payInfo.final_totalprice }}</div>
          <div v-if="form.payData.paytype == '1'" class="pay_text flex_xy_center"
            :style="'color:' + storeInfo.userInfo.color1">
            请扫描微信付款码支付，确认支付成功后，点击确认付款即可完成付款操作。</div>
          <div v-if="form.payData.paytype == '2'" class="pay_text flex_xy_center"
            :style="'color:' + storeInfo.userInfo.color1">
            请扫描支付宝付款码支付，确认支付成功后，点击确认付款即可完成付款操作。</div>
        </div>
        <div class="pay_opt flex flex_x_bottom">
          <div @click="toPayCancel"
            :style="'color:' + storeInfo.userInfo.color1 + ';border-color:' + storeInfo.userInfo.color1"
            class="pay_btn flex_xy_center">取消</div>
          <div @click="toPayCashier"
            :style="'color:#fff' + ';background:' + storeInfo.userInfo.color1 + ';border-color:' + storeInfo.userInfo.color1"
            class="pay_btn flex_xy_center">确定付款</div>
        </div>
      </div>
    </div>
    <div v-if="form.updateState == '3'" class="operate flex_x1">
      <div class="pay">
        <div class="pay_body">
          <div class="pay_title">收款金额：¥{{ form.payInfo.totalprice }}</div>
          <div class="pay_text flex_xy_center pay_style">
            支付方式：
            <el-radio-group class="flex_y_center" v-model="form.payData.paytype">
              <el-radio class="pay_type" v-for="(item, index) in form.payTypeListNormal" :key="index"
                :label="item.value">{{ item.lable }}</el-radio>
            </el-radio-group>
          </div>
          <div v-if="form.payInfo != ''" class="pay_text flex_xy_center"> 抹零金额：-￥{{ form.payInfo.moling_money }}</div>
          <div class="pay_total flex_xy_center">实付金额：¥{{ form.payInfo.final_totalprice }}</div>
          <div v-if="form.payData.paytype == '1'" class="pay_text flex_xy_center"
            :style="'color:' + storeInfo.userInfo.color1">
            请扫描微信付款码支付，确认支付成功后，点击确认付款即可完成付款操作。</div>
          <div v-if="form.payData.paytype == '2'" class="pay_text flex_xy_center"
            :style="'color:' + storeInfo.userInfo.color1">
            请扫描支付宝付款码支付，确认支付成功后，点击确认付款即可完成付款操作。</div>
        </div>
        <div class="pay_opt flex flex_x_bottom">
          <div @click="form.updateState = '0'; form.memberState = '1'"
            :style="'color:' + storeInfo.userInfo.color1 + ';border-color:' + storeInfo.userInfo.color1"
            class="pay_btn flex_xy_center">取消</div>
          <div @click="toPayCashier"
            :style="'color:#fff' + ';background:' + storeInfo.userInfo.color1 + ';border-color:' + storeInfo.userInfo.color1"
            class="pay_btn flex_xy_center">确定付款</div>
        </div>
      </div>
    </div>
    <div v-if="form.updateState == '4'" class="operate flex_x1">
      <div class="success">
        <div class="success_body">
          <el-icon :style="'color:' + storeInfo.userInfo.color1" class="icon">
            <CircleCheckFilled />
          </el-icon>
          <div :style="'color:' + storeInfo.userInfo.color1" class="text">收款成功</div>
        </div>
        <div class="success_opt flex flex_x_bottom">
          <div @click="goPrint"
            :style="'color:' + storeInfo.userInfo.color1 + ';border-color:' + storeInfo.userInfo.color1"
            class="success_btn flex_xy_center">打印小票</div>
          <div @click="goOnPay"
            :style="'color:#fff' + ';background:' + storeInfo.userInfo.color1 + ';border-color:' + storeInfo.userInfo.color1"
            class="success_btn flex_xy_center">继续收款</div>
        </div>
      </div>
    </div>
    <el-dialog v-model="form.dialogState" title="选择规格" width="500px">
      <el-form :model="form">
        <el-form-item label="规格" label-width="200">
          <el-radio-group v-model="form.specsInfo.ggid">
            <el-radio v-for="(item, index) in form.specsInfo.guigelist" :key="index" :label="item.id" border>
              {{ item.name }}</el-radio>
          </el-radio-group>
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="form.dialogState = false">取消</el-button>
          <el-button @click="doSpecs" type="primary">确定</el-button>
        </span>
      </template>
    </el-dialog>
    <el-dialog v-model="form.remarkState" title="添加备注" width="500px">
      <el-form :model="form">
        <el-form-item label="备注" label-width="200">
          <el-input v-model="form.dataInfo.remark" :autosize="{ minRows: 6, maxRows: 4 }" maxlength="200"
            show-word-limit type="textarea" />
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="form.remarkState = false">取消</el-button>
          <el-button @click="doRemark" type="primary">确定</el-button>
        </span>
      </template>
    </el-dialog>
    <el-dialog v-model="form.couponInfoState" title="优惠券列表" width="550px">
      <el-form :model="form">
        <el-form-item label="优惠券" label-width="200">
          <el-space wrap>
            <el-card v-for="(item, index) in form.couponInfo" :key="index" shadow="always">
              <div>{{ item.couponname }}</div>
              <div>有效期：{{ item.endtime }}</div>
            </el-card>
          </el-space>
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="form.couponInfoState = false">取消</el-button>
          <el-button @click="form.couponInfoState = false" type="primary">确定</el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>
<script lang="ts" setup>
import { reactive, onMounted, onUnmounted } from 'vue'
import { ElMessage, ElMessageBox } from 'element-plus'
import { openLoading, closeLoading } from '../../utils/loading'
import { getProductList, getCategoryList, getCashierOrder, addToCashier, cashierChangeNum, cashierChangePrice, hangup, cancelHangup, delCashierOrder, cashierChangeRemark, searchMember, getWaitPayOrder, memberCouponList, payPreview, payCashier, printBusiness } from '../../assets/apis/api'
import { Picture as IconPicture } from '@element-plus/icons-vue'
import { userStore } from "../../store/userInfo"
import { useRouter } from 'vue-router'
const form: any = reactive({
  operateTable: [
    { lable: "商品", value: 0 },
    { lable: "会员", value: 1 },
    { lable: "取单", value: 2 },
    { lable: "直接收款", value: 3 }
  ],
  operateIndex: 0,

  shopTable: [
    {
      name: "全部",
      id: ''
    }
  ],
  shopIndex: 0,
  shopList: [],
  shopKeyWord: {
    page: 1,
    limit: 30,
    name: "",
    cid: "",
    code: "",
    apifrom: "vue"
  },
  shopMoreType: false,
  shopUpdate: "",
  dialogState: false,
  remarkState: false,

  memberState: '1',
  memberUserInfo: "",

  keyIndex: '',

  dataInfo: {
    prolist: []
  },

  dataInfoHang: [],

  shopFocus: '0',

  memberData: '',
  memberFocus: '0',

  directData: '',
  directFocus: '0',

  updateState: '0',

  remove_zero: 0,

  couponInfo: [],
  couponInfoState: false,

  payInfo: {
    couponlist: [],
    memberinfo: {}
  },
  payData: {
    couponid: "",
    paytype: "1",
    userscore: ""
  },
  payTypeListMember: [
    {
      lable: "微信",
      value: "1"
    },
    // {
    //   lable: "支付宝",
    //   value: "2"
    // },
    {
      lable: "现金",
      value: "3"
    },
    {
      lable: "余额",
      value: "4"
    }
  ],
  payTypeListNormal: [
    {
      lable: "微信",
      value: "1"
    },
    // {
    //   lable: "支付宝",
    //   value: "2"
    // },
    {
      lable: "现金",
      value: "3"
    }
  ],
  scanTime: '',
  scanCodeGoods: '',
  scanCode: '',
  scanState: true,

  printId: "",

  proState: false,
  directState: false,
  numberFocus: '0',
  noticeState: false,
  updateFocus: '0',
  numberIndex: ""
})
const storeInfo: any = userStore()
const router = useRouter()
onMounted(() => {
  if (router.currentRoute.value.query.id) {
    let cashier_id: any = router.currentRoute.value.query.id;
    sessionStorage.setItem("cashierId", cashier_id);
  }
  categoryList();
  getCashier();
  getCashierHang();
  keepDown();
})
onUnmounted(() => {
  document.onkeydown = null
})
const getProState = () => {
  form.proState = true;
}
const getProNum = (e: any) => {
  if (form.proState) {
    openLoading();
    let param: any = {
      apifrom: "vue",
      cashier_id: sessionStorage.getItem("cashierId"),
      id: e.id,
      num: e.num
    }
    cashierChangeNum(param).then((res: any) => {
      closeLoading();
      if (res.status == '1') {
        form.proState = false;
        getCashier();
      } else {
        ElMessage.error(res.msg)
      }
    })
  }
}
const goPrint = () => {
  openLoading();
  let param: any = {
    apifrom: "vue",
    orderid: form.printId
  }
  printBusiness(param).then((res: any) => {
    closeLoading();
    if (res.status == '1') {
      ElMessage.success("已打印")
    } else {
      ElMessage.error(res.msg)
    }
  })
}
const goOnPay = () => {
  form.updateState = '0'
  form.operateIndex = '0'
  form.memberState = '1'
  form.memberUserInfo = ''
  form.payData.paytype = '1'
}
const toPayCashier = () => {
  if (form.scanCode == '') {
    if (form.payData.paytype == '1') {
      ElMessage.error("请扫描微信付款码")
      return;
    }
    if (form.payData.paytype == '2') {
      ElMessage.error("请扫描支付宝付款码")
      return;
    }
  }
  let mid = ''
  if (form.memberUserInfo != '') {
    mid = form.memberUserInfo.id
  }
  openLoading();
  let param: any = {
    apifrom: "vue",
    cashier_id: sessionStorage.getItem("cashierId"),
    mid: mid,
    paytype: form.payData.paytype,
    couponid: form.payData.couponid,
    userscore: form.payData.userscore,
    auth_code: form.scanCode
  }
  payCashier(param).then((res: any) => {
    closeLoading();
    form.scanCode = '';
    if (res.status == '1') {
      form.printId = JSON.stringify(form.dataInfo.id);
      form.updateState = '4';
      getCashier();
    } else {
      ElMessage.error(res.msg)
    }
  })
}
const getDeduct = (e: any) => {
  if (e) {
    form.payData.userscore = '1'
  } else {
    form.payData.userscore = '0'
  }
  toPayPreview();
}
const couponClick = (item: any) => {
  form.payData.couponid = item.id;
  toPayPreview();
}
const toPayPreview = () => {
  let mid = ''
  if (form.memberUserInfo != '') {
    mid = form.memberUserInfo.id
  }
  openLoading();
  let param: any = {
    apifrom: "vue",
    cashier_id: sessionStorage.getItem("cashierId"),
    mid: mid,
    couponid: form.payData.couponid,
    userscore: form.payData.userscore
  }
  payPreview(param).then((res: any) => {
    closeLoading();
    if (res.status == '1') {
      form.payInfo = res.data;
    } else {
      ElMessage.error(res.msg)
    }
  })
}
const checkCoupon = () => {
  openLoading();
  let param: any = {
    apifrom: "vue",
    page: 1,
    limit: 100,
    mid: form.memberUserInfo.id
  }
  memberCouponList(param).then((res: any) => {
    closeLoading();
    if (res.status == '1') {
      form.couponInfo = res.data;
      form.couponInfoState = true;
    } else {
      ElMessage.error(res.msg)
    }
  })
}
const toPayCancel = () => {
  form.updateState = '0'
  form.memberState = '2'
  form.payData.paytype = '1'
}
const toCancel = () => {
  openLoading();
  let param: any = {
    apifrom: "vue",
    orderid: form.dataInfo.id
  }
  delCashierOrder(param).then((res: any) => {
    closeLoading();
    if (res.status == '1') {
      getCashier();
    } else {
      ElMessage.error(res.msg)
    }
  })
}
const toZero = () => {
  openLoading();
  if (form.remove_zero == '0') {
    form.remove_zero = '1'
  } else if (form.remove_zero == '1') {
    form.remove_zero = '0'
  }
  getCashier();
}
const changeMember = () => {
  form.updateState = '0'
  form.memberState = '1';
  form.memberUserInfo = '';
}
const doSearchMember = () => {
  openLoading();
  let param: any = {
    apifrom: "vue",
    cashier_id: sessionStorage.getItem("cashierId"),
    keyword: form.memberData
  }
  searchMember(param).then((res: any) => {
    closeLoading();
    if (res.status == '1') {
      form.memberUserInfo = res.data;
      form.memberData = '';
      form.memberState = '2';
    } else {
      ElMessage.error(res.msg)
    }
  })
}
const toMember = () => {
  if (form.memberUserInfo == '') {
    form.noticeState = true;
    ElMessageBox.confirm(
      '订单未绑定会员，是否需要绑定会员进行结算?',
      '提示',
      {
        confirmButtonText: '会员登录(Enter)',
        cancelButtonText: '跳过(Esc)',
        distinguishCancelAndClose: true,
        type: 'warning',
        callback: function (action: any) {
          form.noticeState = false;
          if (action == 'confirm') {
            form.operateIndex = 1;
            form.directState = false;
            form.scanCode = '';
            form.updateState = '0';
          } else if (action == 'cancel') {
            form.scanCode = '';
            form.operateIndex = 0;
            toPayPreview();
            form.updateState = '3';
            form.directState = false;
          } else if (action == 'close') {
            form.scanCode = '';
            form.operateIndex = 0;
            toPayPreview();
            form.updateState = '3';
            form.directState = false;
          }
        }
      }
    )
  } else {
    toPayPreview();
    form.updateState = '2'
  }
}
const doRemark = () => {
  if (form.dataInfo.remark) {
    openLoading();
    let param: any = {
      apifrom: "vue",
      orderid: form.dataInfo.id,
      remark: form.dataInfo.remark
    }
    cashierChangeRemark(param).then((res: any) => {
      closeLoading();
      if (res.status == '1') {
        form.remarkState = false;
        ElMessage({
          message: '操作成功',
          type: 'success',
        })
      } else {
        ElMessage.error(res.msg)
      }
    })
  } else {
    ElMessage.error("请输入备注")
  }
}
const toRemark = () => {
  form.remarkState = true
}
const doDelCashierOrder = (e: any) => {
  ElMessageBox.confirm(
    '删除该单?',
    '提示',
    {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    }
  )
    .then(() => {
      openLoading();
      let param: any = {
        apifrom: "vue",
        orderid: e
      }
      delCashierOrder(param).then((res: any) => {
        closeLoading();
        if (res.status == '1') {
          getCashierHang();
        } else {
          ElMessage.error(res.msg)
        }
      })
    })
    .catch(() => {
      ElMessage({
        type: 'info',
        message: '已取消',
      })
    })
}
const getCashierHang = () => {
  let param: any = {
    apifrom: "vue",
    cashier_id: sessionStorage.getItem("cashierId"),
    status: 2
  }
  getCashierOrder(param).then((res: any) => {
    if (res.status == '1') {
      form.dataInfoHang = res.data;
    } else {
      ElMessage.error(res.msg)
    }
  })
}
const toHangup = () => {
  openLoading();
  let mid = ''
  if (form.memberUserInfo != '') {
    mid = form.memberUserInfo.id;
  }
  let param: any = {
    apifrom: "vue",
    cashier_id: sessionStorage.getItem("cashierId"),
    mid: mid
  }
  hangup(param).then((res: any) => {
    closeLoading();
    if (res.status == '1') {
      form.updateState = '0';
      form.memberState = '1';
      form.memberUserInfo = '';
      form.operateIndex = '0';
      getCashier();
      getCashierHang();
    } else {
      ElMessage.error(res.msg)
    }
  })
}
const doCancelHangup = (e: any) => {
  ElMessageBox.confirm(
    '您确认取出这个订单吗?',
    '提示',
    {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    }
  )
    .then(() => {
      if (e.mid) {
        openLoading();
        let param: any = {
          apifrom: "vue",
          cashier_id: sessionStorage.getItem("cashierId"),
          keyword: e.mid
        }
        searchMember(param).then((res: any) => {
          if (res.status == '1') {
            form.memberUserInfo = res.data;
            form.memberState = '2';
            form.operateIndex = '1';
            let param: any = {
              apifrom: "vue",
              orderid: e.id
            }
            cancelHangup(param).then((res: any) => {
              closeLoading();
              if (res.status == '1') {
                getCashier();
                getCashierHang();
              } else {
                ElMessage.error(res.msg)
              }
            })
          } else {
            ElMessage.error(res.msg)
          }
        })
      } else {
        openLoading();
        let param: any = {
          apifrom: "vue",
          orderid: e.id
        }
        cancelHangup(param).then((res: any) => {
          closeLoading();
          if (res.status == '1') {
            getCashier();
            getCashierHang();
          } else {
            ElMessage.error(res.msg)
          }
        })
      }
    })
    .catch(() => {
      ElMessage({
        type: 'info',
        message: '已取消',
      })
    })
}
const doUpPrice = () => {
  if (form.shopUpdate.upPrice) {
    openLoading();
    let param: any = {
      apifrom: "vue",
      cashier_id: sessionStorage.getItem("cashierId"),
      id: form.shopUpdate.id,
      price: form.shopUpdate.upPrice
    }
    cashierChangePrice(param).then((res: any) => {
      closeLoading();
      if (res.status == '1') {
        ElMessage({
          message: '操作成功',
          type: 'success',
        })
        getCashier();
      } else {
        ElMessage.error(res.msg)
      }
    })
  } else {
    ElMessage.error("请输入定价")
  }
}
const upShop = (e: any) => {
  form.shopUpdate = e;
}
const delShop = (e: any) => {
  ElMessageBox.confirm(
    '删除该商品?',
    '提示',
    {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      type: 'warning',
    }
  )
    .then(() => {
      openLoading();
      let param: any = {
        apifrom: "vue",
        cashier_id: sessionStorage.getItem("cashierId"),
        id: e,
        num: 0
      }
      cashierChangeNum(param).then((res: any) => {
        closeLoading();
        if (res.status == '1') {
          getCashier();
        } else {
          ElMessage.error(res.msg)
        }
      })
    })
    .catch(() => {
      ElMessage({
        type: 'info',
        message: '已取消',
      })
    })
}
const addShop = (e: any, num: any) => {
  openLoading();
  let param: any = {
    apifrom: "vue",
    cashier_id: sessionStorage.getItem("cashierId"),
    id: e,
    num: ++num
  }
  cashierChangeNum(param).then((res: any) => {
    closeLoading();
    if (res.status == '1') {
      getCashier();
    } else {
      ElMessage.error(res.msg)
    }
  })
}
const cutShop = (e: any, num: any) => {
  if (num == 1) {
    return;
  }
  openLoading();
  let param: any = {
    apifrom: "vue",
    cashier_id: sessionStorage.getItem("cashierId"),
    id: e,
    num: --num
  }
  cashierChangeNum(param).then((res: any) => {
    closeLoading();
    if (res.status == '1') {
      getCashier();
    } else {
      ElMessage.error(res.msg)
    }
  })
}
const doAddToCash = (e: any) => {
  form.specsInfo = e;
  if (e.guige_num == '1') {
    openLoading();
    let param: any = {
      apifrom: "vue",
      cashier_id: sessionStorage.getItem("cashierId"),
      proid: e.id,
      ggid: e.guigelist[0].id,
      num: 1,
      price: ''
    }
    addToCashier(param).then((res: any) => {
      closeLoading();
      if (res.status == '1') {
        getCashier();
      } else {
        ElMessage.error(res.msg)
      }
    })
  } else {
    form.dialogState = true
  }
}
const directAddToCash = () => {
  openLoading();
  let param: any = {
    apifrom: "vue",
    cashier_id: sessionStorage.getItem("cashierId"),
    proid: "-99",
    ggid: '',
    num: 1,
    price: form.directData
  }
  addToCashier(param).then((res: any) => {
    closeLoading();
    if (res.status == '1') {
      form.directState = true;
      getCashier();
    } else {
      ElMessage.error(res.msg)
    }
  })
}
const doSpecs = () => {
  if (form.specsInfo.ggid) {
    openLoading();
    let param: any = {
      apifrom: "vue",
      cashier_id: sessionStorage.getItem("cashierId"),
      proid: form.specsInfo.id,
      ggid: form.specsInfo.ggid,
      num: 1,
      price: ''
    }
    addToCashier(param).then((res: any) => {
      closeLoading();
      if (res.status == '1') {
        form.dialogState = false;
        form.specsInfo.ggid = null;
        getCashier();
      } else {
        ElMessage.error(res.msg)
      }
    })
  } else {
    ElMessage.error("请选择商品规格")
  }

}
const getCashier = () => {
  let param: any = {
    apifrom: "vue",
    cashier_id: sessionStorage.getItem("cashierId"),
    remove_zero: form.remove_zero
  }
  getWaitPayOrder(param).then((res: any) => {
    closeLoading();
    if (res.status == '1') {
      if (res.data) {
        form.dataInfo = res.data;
      } else {
        form.dataInfo = {
          prolist: []
        }
      }
    } else {
      ElMessage.error(res.msg)
    }
  })
}
const categoryList = () => {
  openLoading();
  let param: any = {
    apifrom: "vue"
  }
  getCategoryList(param).then((res: any) => {
    closeLoading();
    if (res.status == '1') {
      form.shopTable.push(...res.data)
    } else {
      ElMessage.error(res.msg)
    }
  })
}
const shopLoad = () => {
  if (form.shopMoreType) {
    return;
  }
  ++form.shopKeyWord.page;
  productList();
}
const productList = () => {
  openLoading();
  getProductList(form.shopKeyWord).then((res: any) => {
    closeLoading();
    if (res.status == '1') {
      form.shopList.push(...res.data)
      if (res.data.length < form.shopKeyWord.limit && form.shopList.length) {
        form.shopMoreType = true;
      }
    } else {
      ElMessage.error(res.msg)
    }
  })
}
const updateClick = (e: any, item: any) => {
  form.updateState = e
  if (item) {
    upShop(item);
  }
}
const shopIn = () => {
  form.shopFocus = '1'
}
const shopOut = () => {
  form.shopFocus = '0'
}
const memberIn = () => {
  form.memberFocus = '1'
}
const memberOut = () => {
  form.memberFocus = '0'
}
const numberIn = (index: any) => {
  form.numberFocus = '1';
  form.numberIndex = index;
}
const numberOut = () => {
  form.numberFocus = '0';
  form.numberIndex = '';
}
const directIn = () => {
  form.directFocus = '1'
}
const directOut = () => {
  form.directFocus = '0'
}
const updateIn = () => {
  form.updateFocus = '1'
}
const updateOut = () => {
  form.updateFocus = '0'
}
const operateClick = (e: any) => {
  form.operateIndex = e;
}
const shopClick = (e: any, id: any) => {
  form.shopIndex = e;
  form.shopKeyWord.cid = id;
  form.shopKeyWord.page = 1;
  form.shopMoreType = false;
  form.shopList = [];
  productList();
}
const keyDown = (e: any) => {
  if (form.operateIndex == 1) {
    form.keyIndex = e;
    if (form.memberFocus == '0') {
      if (e == 'clear') {
        form.memberData = ''
      } else if (e == 'delete') {
        form.memberData = form.memberData.substr(0, form.memberData.length - 1);
      } else if (e == 'confirm') {
        if (form.memberState == 1) {
          if (form.memberData == '') {
            ElMessage.error("请输入会员手机号或会员码");
          } else {
            doSearchMember();
          }
        }
      } else {
        form.memberData = form.memberData + e;
      }
    }
    if (form.memberFocus == '1') {
      if (e == 'confirm') {
        if (form.memberData == '') {
          ElMessage.error("请输入会员手机号或会员码");
        } else {
          doSearchMember();
        }
      }
    }
  } else if (form.operateIndex == 3) {
    form.keyIndex = e;
    if (form.directState) {
      if (e == 'confirm') {
        toMember();
        return;
      }
    }
    if (form.directFocus == '0') {
      if (e == 'clear') {
        form.directData = ''
      } else if (e == 'delete') {
        form.directData = form.directData.substr(0, form.directData.length - 1);
      } else if (e == 'confirm') {
        if (form.directData == '') {
          ElMessage.error("请输入收款金额")
        } else {
          directAddToCash()
        }
      } else {
        form.directData = form.directData + e;
      }
    }
    if (form.directFocus == '1') {
      if (e == 'confirm') {
        if (form.directData == '') {
          ElMessage.error("请输入收款金额")
        } else {
          directAddToCash()
        }
      }
    }
  }
}
const keyUp = () => {
  form.keyIndex = '';
}
var setTime: any
const setDown = (e: any) => {
  keyDown(e);
  clearTimeout(setTime);
  setTime = setTimeout(() => {
    keyUp();
  }, 100)
}
const keepDown = () => {
  document.onkeydown = function (e) {
    if (form.noticeState) {
      return;
    }
    if (form.numberFocus == '1') {
      return;
    }
    if (e.key == 'Escape') {
      return;
    }
    if (form.operateIndex == 0) {
      if (e.key == 'Enter' && form.scanState) {
        if (form.shopFocus == '1') {
          if (e.key == 'Enter') {
            form.shopKeyWord.page = 1;
            form.shopMoreType = false;
            form.shopList = [];
            productList();
          }
        } else {
          if (form.dataInfo.prolist.length) {
            if (form.updateState == '1') {
              doUpPrice();
            } else if (form.updateState == '3') {
              toPayCashier();
            } else {
              if (form.memberUserInfo == '') {
                toMember()
              } else {
                form.operateIndex = 1;
              }
            }
          }
          if (form.updateState == '4') {
            form.updateState = '0'
            form.operateIndex = '0'
            form.memberState = '1'
            form.memberUserInfo = ''
            form.payData.paytype = '1'
            return;
          }
        }
      } else {
        if (form.updateState == '0') {
          if (e.key == 'Enter') {
            openLoading();
            let param: any = {
              apifrom: "vue",
              cashier_id: sessionStorage.getItem("cashierId"),
              barcode: form.scanCodeGoods
            }
            addToCashier(param).then((res: any) => {
              closeLoading();
              form.scanCodeGoods = '';
              if (res.status == '1') {
                getCashier();
              } else {
                ElMessage.error(res.msg)
              }
            })
          } else {
            form.scanCodeGoods = form.scanCodeGoods + e.key;
          }
          form.scanState = false;
          clearTimeout(form.scanTime);
          form.scanTime = setTimeout(() => {
            form.scanState = true;
          }, 100)
        } else if (form.updateState == '3') {
          if (e.key == 'Enter') {
            openLoading();
            toPayCashier();
          } else {
            form.scanCode = form.scanCode + e.key;
          }
          form.scanState = false;
          clearTimeout(form.scanTime);
          form.scanTime = setTimeout(() => {
            form.scanState = true;
          }, 100)
        }
      }
    } else if (form.operateIndex == 1) {
      if (form.memberUserInfo == '') {
        if (e.key == '0') {
          setDown(0);
        } else if (e.key == '1') {
          setDown(1);
        } else if (e.key == '2') {
          setDown(2);
        } else if (e.key == '3') {
          setDown(3);
        } else if (e.key == '4') {
          setDown(4);
        } else if (e.key == '5') {
          setDown(5);
        } else if (e.key == '6') {
          setDown(6);
        } else if (e.key == '7') {
          setDown(7);
        } else if (e.key == '8') {
          setDown(8);
        } else if (e.key == '9') {
          setDown(9);
        } else if (e.key == 'Backspace') {
          setDown('delete');
        } else if (e.key == 'Enter') {
          setDown('confirm');
        }
      } else {
        if (e.key == 'Enter' && form.scanState) {
          if (form.dataInfo.prolist.length) {
            if (form.updateState == '0') {
              form.updateState = '2';
              toPayPreview();
            } else if (form.updateState == '1') {
              doUpPrice();
            } else if (form.updateState == '2') {
              toPayCashier();
            }
          }
          if (form.updateState == '4') {
            form.updateState = '0'
            form.operateIndex = '0'
            form.memberState = '1'
            form.memberUserInfo = ''
            form.payData.paytype = '1'
            return;
          }
        } else {
          if (form.updateState == '2') {
            if (e.key == 'Enter') {
              toPayCashier();
            } else {
              form.scanCode = form.scanCode + e.key;
            }
            form.scanState = false;
            clearTimeout(form.scanTime);
            form.scanTime = setTimeout(() => {
              form.scanState = true;
            }, 100)
          }
        }
      }
    } else if (form.operateIndex == 3) {
      if (e.key == '0') {
        setDown(0);
      } else if (e.key == '1') {
        setDown(1);
      } else if (e.key == '2') {
        setDown(2);
      } else if (e.key == '3') {
        setDown(3);
      } else if (e.key == '4') {
        setDown(4);
      } else if (e.key == '5') {
        setDown(5);
      } else if (e.key == '6') {
        setDown(6);
      } else if (e.key == '7') {
        setDown(7);
      } else if (e.key == '8') {
        setDown(8);
      } else if (e.key == '9') {
        setDown(9);
      } else if (e.key == '.') {
        setDown('.');
      } else if (e.key == 'Backspace') {
        setDown('delete');
      } else if (e.key == 'Enter') {
        setDown('confirm');
      }
    }
  };
}
</script>
<style>
.el-radio__label {
  font-size: 20px;
}

.el-checkbox__label {
  font-size: 16px;
}

.el-radio {
  height: auto;
  white-space: pre-wrap;
}

.el-card.is-always-shadow{
  box-shadow: none;
}
</style>
<style scoped lang="scss">
.content {
  position: relative;
  height: 100%;
  font-size: 14px;
}

.inventory {
  position: relative;
  width: 520px;
  height: 100%;
  flex-direction: column;
  background: #ffffff;

  .inventory_alert {
    position: absolute;
    top: 0;
    bottom: 0;
    width: 100%;
    background: rgba(255, 255, 255, .7);
    z-index: 2;
    cursor: not-allowed;
  }

  .inventory_head {
    color: #333;
    height: 50px;
    border-bottom: 1px solid #f2f2f2;
    padding: 0 16px;
    font-size: 16px;

    .inventory_title {
      font-weight: bold;
    }
  }

  .body {
    overflow: auto;
  }

  .body_null {
    margin-top: 250px;
  }

  .body_icon {
    height: 110px;
    width: 110px;
    display: block;
    margin: 0 auto;
  }

  .body_title {
    text-align: center;
    font-size: 14px;
    font-weight: 500;
    color: rgb(178, 178, 178);
    margin-top: 30px;
  }

  .module {
    padding: 13px;
    border-bottom: 1px solid #f2f2f2;
    box-sizing: border-box;

    .module_image {
      width: 68px;
      height: 68px;
      border-radius: 4px;
      margin-right: 10px;
    }

    .module_image .image-slot {
      display: flex;
      justify-content: center;
      align-items: center;
      width: 100%;
      height: 100%;
      background: var(--el-fill-color-light);
      color: var(--el-text-color-secondary);
      font-size: 30px;
    }

    .module_image .image-slot .el-icon {
      font-size: 30px;
    }

    .module_title {
      color: #333;
    }

    .module_unit {
      font-size: 14px;
      color: #333;
      margin: 5px 0;
    }

    .module_text {
      width: 330px;
    }

    .module_up {
      margin-left: 14px;
      color: #4476ff;
      cursor: pointer;
    }

    .module_price {
      font-size: 16px;
    }

    .module_opt {
      width: 140px;
      border: 1px solid #dcdee2;
      border-radius: 4px;
    }

    .module_icon {
      color: #808695;
      cursor: pointer;
      padding: 0;
      width: 30px;
      background-color: #f5f6fa;
    }

    .module_input {
      width: 100%;
      height: 30px;
      padding: 0 7px;
      font-size: 16px;
      border-right: 1px solid #dcdee2;
      border-left: 1px solid #dcdee2;
      border-top: none;
      border-bottom: none;
      color: #333;
      text-align: center;
      background-color: #fff;
      background-image: none;
      position: relative;
    }

    .module_total {
      font-size: 16px;
      text-align: right;
    }
  }

  .price {
    color: #666;
    padding: 0 16px;
    line-height: 40px;
  }

  .group {
    padding: 8px;
    border-top: 1px solid #f2f2f2;

    .group_btn {
      height: 48px;
      font-size: 16px;
      border-radius: 4px;
      margin-right: 10px;
      cursor: pointer;
    }
  }
}

.operate {
  position: relative;
  margin-left: 20px;
  height: 100%;
  background: #ffffff;
  flex-direction: column;

  .operate_table {
    padding: 0 16px;
    font-size: 16px;
    border-bottom: 1px solid #f2f2f2;
  }

  .operate_item {
    position: relative;
    padding: 14px 16px;
    color: #333;
    margin-right: 16px;
    cursor: pointer;
    text-align: center;
    border-bottom: 2px solid #fff;
  }

  .operate_num {
    position: absolute;
    right: 0;
    top: 3px;
    padding: 1px 5.5px;
    font-size: 14px;
    color: #fff;
    border-radius: 100px;
  }

  .operate_body {
    position: relative;
  }
}

.shop {
  .shop_body {
    height: 100%;
    padding: 16px;
    align-items: flex-start;
    box-sizing: border-box;
    flex-direction: column;
  }

  .shop_list {
    position: relative;
    width: 100%;
    overflow: auto;
  }

  .shop_more {
    font-size: 14px;
    color: #999;
    text-align: center;
    padding-bottom: 10px;
  }

  .shop_table {
    width: 100px;
    height: 100%;
    overflow-y: auto;
    border-left: 1px solid #f2f2f2;
  }

  .shop_item {
    text-align: center;
    height: 40px;
    line-height: 40px;
    cursor: pointer;
    font-size: 16px;
    padding: 0 6px;
  }

  .shop_search {
    position: relative;
    width: 100%;
    box-sizing: border-box;
    margin-bottom: 25px;
  }

  .shop_icon {
    position: absolute;
    top: 0;
    bottom: 0;
    left: 10px;
    margin: auto 0;
  }

  .shop_input {
    color: #333;
    padding: 4px 7px 4px 40px;
    border: 1px solid #dcdee2;
    background: #f2f3f5;
    border-radius: 4px;
    box-sizing: border-box;
    height: 40px;
  }

  .shop_module {
    border-radius: 4px;
    border: 1px solid #ededed;
    margin: 0 15px 15px 0;
    box-sizing: border-box;
    padding: 12px;
    position: relative;
    cursor: pointer;
    width: 275px;
    background: #fff;
  }

  .shop_img {
    width: 68px;
    height: 68px;
    margin-right: 14px;
    border-radius: 4px;
  }

  .shop_img .image-slot {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
    height: 100%;
    background: var(--el-fill-color-light);
    color: var(--el-text-color-secondary);
    font-size: 30px;
  }

  .shop_img .image-slot .el-icon {
    font-size: 30px;
  }

  .shop_title {
    color: #333;
  }

  .shop_data {
    margin-top: 12px;
    font-size: 14px;
  }

  .shop_stock {
    color: #999;
  }

  .shop_notice {
    padding: 150px 0 0 0;
    width: 100%;

    img {
      height: 110px;
      width: 110px;
      display: block;
      margin: 0 auto;
    }

    .text {
      text-align: center;
      font-size: 14px;
      font-weight: 500;
      color: #b2b2b2;
      margin-top: 30px;
    }
  }
}

.key {
  max-width: 580px;
  height: 386px;
  position: absolute;
  left: 0;
  right: 0;
  top: 0;
  bottom: 0;
  margin: auto auto;

  .key_title {
    font-size: 16px;
    color: #666;
    line-height: 20px;
    padding-bottom: 15px;
  }

  .key_text {
    color: #999;
    font-size: 14px;
    margin: 5px 0 0 0;
    padding-bottom: 15px;
  }

  .key_data {
    position: relative;
  }

  .key_unit {
    position: absolute;
    top: 0;
    bottom: 0;
    right: 0;
    margin: auto 0;
    font-size: 18px;
    width: 60px;
    line-height: 60px;
    height: 60px;
  }

  .key_input {
    height: 60px;
    font-size: 18px;
    border: 1px solid #dcdee2;
    border-radius: 4px;
    color: #333;
    box-sizing: border-box;
    width: 100%;
    padding: 6px 18px;
  }

  .key_module {
    background: #f5f6fa;
    border-top: 1px solid #dcdee2;
    border-left: 1px solid #dcdee2;
    margin-top: 25px;
    font-size: 20px;
    cursor: pointer;
  }

  .key_content {
    width: 75%;
  }

  .key_opt {
    width: 25%;
  }

  .key_item {
    height: 66px;
    color: #333;
    border-right: 1px solid #dcdee2;
    border-bottom: 1px solid #dcdee2;
    width: 33.33%;
    box-sizing: border-box;
    text-align: center;
    line-height: 66px;
    user-select: none;
  }

  .item_active {
    background: #dadde6;
  }

  .key_row {
    width: 100%;
  }

  .key_confirm {
    line-height: 132px;
    text-align: center;
    box-sizing: border-box;
    color: #fff;
    border-right: 1px solid;
    border-bottom: 1px solid;
    user-select: none;
  }
}

.take {
  position: relative;
  padding: 16px;
  width: 100%;
  height: 100%;
  box-sizing: border-box;

  .take_module {
    height: 100%;
    width: 100%;
    overflow-y: scroll;
  }

  .take_list {
    padding-top: 16px;
  }

  .take_list:first-child {
    padding-top: 0;
  }

  .take_date {
    color: #333;
  }

  .take_item {
    width: 225px;
    background: #f5f6fa;
    border-radius: 4px;
    margin-top: 20px;
    margin-right: 20px;
    padding: 16px;
    position: relative;
    box-sizing: border-box;
  }

  .take_title {
    color: #333;
  }

  .take_data {
    margin-top: 30px;
    font-size: 14px;
  }

  .take_num {
    color: #333;
  }

  .take_opt {
    margin-top: 25px;
    padding-bottom: 20px;
    border-bottom: 1px solid #f0f0f0;
  }

  .take_null {
    margin-top: 250px;
  }

  .take_icon {
    height: 110px;
    width: 110px;
    display: block;
    margin: 0 auto;
  }

  .take_text {
    text-align: center;
    font-size: 14px;
    font-weight: 500;
    color: rgb(178, 178, 178);
    margin-top: 30px;
  }

  .take_order {
    color: #333;
  }

  .take_btn {
    font-size: 14px;
    margin-left: 15px;
    height: 32px;
    padding: 0 15px;
    text-align: center;
    line-height: 32px;
    border-radius: 5px;
    cursor: pointer;
    border: 1px solid;
  }
}


.update {
  position: relative;
  height: 100%;

  .update_body {
    padding: 16px;
  }

  .update_module {
    width: 276px;
    border-radius: 4px;
    border: 1px solid #ededed;
    padding: 12px;
    position: relative;
    color: #333;
    cursor: pointer;
    background: #fff;
  }

  .update_img {
    width: 68px;
    height: 68px;
    margin-right: 14px;
    border-radius: 4px;
  }


  .update_img .image-slot {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
    height: 100%;
    background: var(--el-fill-color-light);
    color: var(--el-text-color-secondary);
    font-size: 30px;
  }

  .update_img .image-slot .el-icon {
    font-size: 30px;
  }


  .update_data {
    margin-top: 12px;
    font-size: 14px;
  }

  .update_stock {
    color: #999;
  }

  .update_title {
    color: #333;
    line-height: 50px;
    padding: 0 16px;
    font-weight: bold;
    border-bottom: 1px solid #f2f2f2;
  }

  .update_icon {
    cursor: pointer;
  }

  .update_item {
    padding: 30px 10px 0 30px;
    color: #333;
  }

  .update_lable {
    width: 70px;
  }

  .update_set {
    position: relative;
  }

  .update_input {
    width: 180px;
    height: 48px;
    border-radius: 5px;
    padding: 0 5px;
    color: #333;
    font-size: 14px;
    overflow: hidden;
    border: 1px solid #dcdee2;
  }

  .update_unit {
    position: absolute;
    top: 0;
    bottom: 0;
    right: 1px;
    margin: auto 0;
    height: 48px;
    width: 48px;
    line-height: 48px;
    color: #333;
    text-align: center;
    background-color: #f8f8f9;
    border-left: 1px solid #dcdee2;
  }

  .update_opt {
    position: absolute;
    padding: 8px;
    width: 100%;
    box-sizing: border-box;
    bottom: 0;
    border-top: 1px solid #f2f2f2;

    .update_btn {
      height: 48px;
      width: 90px;
      font-size: 14px;
      border: 1px solid;
      border-radius: 4px;
      margin-right: 10px;
      cursor: pointer;
    }

    .update_btn:last-child {
      color: #fff;
      margin-right: 0;
    }
  }
}

.el-radio {
  margin: 0 20px 20px 0;
}

.memberDetail {
  position: relative;
  padding: 0 20px;
  height: 100%;
  width: 100%;
  box-sizing: border-box;

  .memberDetail_user {
    display: flex;
    align-items: center;
    padding: 32px 0;
  }

  .memberDetail_head {
    width: 48px;
    height: 48px;
    border-radius: 24px;
    display: block;
    margin-right: 14px;
  }

  .memberDetail_data {
    flex: 1;
  }

  .memberDetail_name {
    color: #333;
    font-size: 16px;
    display: flex;
    align-items: center;
  }

  .memberDetail_phone {
    font-size: 14px;
    color: #333;
    margin-top: 5px;
  }

  .memberDetail_tag {
    margin-left: 12px;
    background: #fef2ed;
    font-size: 10px;
    cursor: pointer;
    padding: 0 15px;
    display: flex;
    align-items: center;
    line-height: 25px;
    text-align: center;
  }

  .memberDetail_icon {
    font-size: 15px;
    color: #ccc;
    margin-left: 5px;
  }

  .memberDetail_btn {
    border: 1px solid;
    padding: 0 15px;
    height: 36px;
    line-height: 36px;
    font-size: 14px;
    border-radius: 4px;
    cursor: pointer;
  }

  .memberDetail_module {
    background: #f5f6fa;
    border-radius: 4px;
    color: #333;
    display: flex;
    flex-wrap: wrap;
    padding: 10px 0;
  }

  .memberDetail_item {
    padding: 15px 20px;
    box-sizing: border-box;
    width: 25%;

    .text {
      font-size: 14px;
    }

    .num {
      font-size: 26px;
      font-weight: 800;
      margin-top: 10px;
    }

    .opt {
      font-size: 14px;
      margin-left: 5px;
      cursor: pointer;
      font-weight: normal;
    }
  }

  .memberDetail_title {
    color: #333;
    line-height: 62px;
    font-size: 14px;
    font-weight: bold;
  }

  .memberDetail_list {
    border-radius: 5px;
    background: #f5f6fa;
    padding: 22px;
    font-size: 14px;
    color: #333;

    .item {
      position: relative;
      line-height: 32px;
      font-size: 14px;
      display: flex;
    }

    .title {
      width: 100px;
      display: flex;
      justify-content: flex-end;
    }

    .text {
      position: relative;
    }
  }
}

.pay {
  position: relative;
  height: 100%;

  .pay_body {
    padding: 16px;
  }

  .pay_header {
    display: flex;
    align-items: center;
  }

  .pay_data {
    width: 255px;
    display: flex;
    flex-shrink: 0;
    align-items: center;
  }

  .pay_img {
    width: 48px;
    height: 48px;
    border-radius: 24px;
    margin-right: 12px;
  }

  .pay_phone {
    font-size: 14px;
  }

  .pay_change {
    border: 1px solid;
    padding: 0px 15px;
    height: 36px;
    line-height: 36px;
    font-size: 14px;
    border-radius: 4px;
    cursor: pointer;
    margin-left: 15px;
    flex-shrink: 0;
  }

  .pay_item {
    height: 76px;
    flex: 1;
    background: #f5f6fa;
    border-radius: 4px;
    margin-left: 16px;
    padding: 16px;
    box-sizing: border-box;

    .title {
      font-size: 14px;
    }

    .num {
      font-size: 18px;
      font-weight: 800;
      margin-top: 5px;
    }
  }

  .pay_title {
    font-size: 20px;
    font-weight: 800;
    color: #333;
    text-align: center;
    margin: 100px 0 0 0;
  }

  .pay_total {
    margin-top: 20px;
    font-size: 20px;
    font-weight: 800;
    color: #333;
    text-align: center;
  }

  .pay_name {
    flex-shrink: 0;
  }

  .pay_content {
    max-height: 220px;
    max-width: 820px;
    overflow-y: scroll;
  }

  .pay_content::-webkit-scrollbar {
    width: 0 !important
  }

  .pay_text {
    color: #666;
    font-size: 16px;
    margin-top: 20px;
  }

  .pay_style {
    font-size: 20px;
  }

  .pay_card {
    cursor: pointer;
  }

  .pay_lable {
    line-height: 32px;
  }

  .pay_active {
    border: 1px solid;
  }

  .pay_type {
    margin: 0 10px 0 0;
  }

  .pay_opt {
    position: absolute;
    padding: 8px;
    width: 100%;
    box-sizing: border-box;
    bottom: 0;
    border-top: 1px solid #f2f2f2;

    .pay_btn {
      height: 48px;
      width: 90px;
      font-size: 14px;
      border: 1px solid;
      border-radius: 4px;
      margin-right: 10px;
      cursor: pointer;
    }
  }
}

.success {
  position: relative;
  height: 100%;

  .success_body {
    padding: 16px;

    .icon {
      font-size: 80px;
      margin: 240px auto 0 auto;
      display: block;
    }

    .text {
      font-size: 20px;
      font-weight: bold;
      text-align: center;
      margin-top: 16px;

    }
  }

  .success_opt {
    position: absolute;
    padding: 8px;
    width: 100%;
    box-sizing: border-box;
    bottom: 0;
    border-top: 1px solid #f2f2f2;

    .success_btn {
      height: 48px;
      width: 90px;
      font-size: 14px;
      border: 1px solid;
      border-radius: 4px;
      margin-right: 10px;
      cursor: pointer;
    }
  }
}
</style>