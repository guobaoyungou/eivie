<template>
  <div class="content flex_y_center">
    <div class="inventory flex">
      <div class="inventory_head flex_y_center flex_bt">
        <span class="inventory_title">订单管理</span>
      </div>
      <div class="search flex_y_center">
        <div class="search_module">
          <el-icon class="search_icon" color="#808695" :size="20">
            <Search />
          </el-icon>
          <input type="text" v-model="form.keyword" @focusin="searchIn" @focusout="searchOut"
            class="search_input flex_x1"
            :style="form.searchFocus == 1 ? 'outline: 1px solid' + storeInfo.userInfo.color1 : ''"
            placeholder="输入商品条形码或商品名称" />
        </div>
      </div>
      <div class="body flex_y1">
        <div class="body_module">
          <div class="module" v-for="(item, index) in form.dataList" @click="orderClick(item)" :key="index">
            <div class="module_head flex_y_center flex_bt">
              <span>订单编号：{{ item.ordernum }}</span>
              <span class="module_state">{{ item.status_desc }}</span>
            </div>
            <div v-for="(itemS, indexS) in item.prolist" :key="indexS" class="module_item flex">
              <el-image class="module_image" :src="itemS.propic">
                <template #error>
                  <div class="image-slot">
                    <el-icon>
                      <icon-picture />
                    </el-icon>
                  </div>
                </template>
              </el-image>
              <div class="flex_x1">
                <div class="module_title">{{ itemS.proname }}</div>
                <div class="module_num">数量：{{ itemS.num }}</div>
              </div>
            </div>
            <div class="module_head flex_y_center flex_bt">
              <span>下单时间：{{ item.createtime }}</span>
              <span>共{{ item.prolist.length }}件，合计：<span :style="'color:' + storeInfo.userInfo.color1">¥{{
                  item.totalprice
              }}</span></span>
            </div>
          </div>
          <div v-if="!form.dataList.length" class="body_null">
            <img src="../../assets/images/order.png" class="body_icon" alt="" />
            <div class="body_title">未添加任何订单</div>
          </div>
        </div>
      </div>
    </div>
    <div class="operate flex_x1">
      <div class="update flex">
        <div class="update_title">订单详情</div>
        <div v-if="form.orderInfo != ''" class="update_body flex_y1">
          <div v-if="form.orderInfo.status == '0'" class="update_head flex_y_center flex_bt update_wait">
            <div>等待付款</div>
            <img src="../../assets/images/wait.png" class="body_icon" alt="" />
          </div>
          <div v-if="form.orderInfo.status == '1'" class="update_head flex_y_center flex_bt update_finish">
            <div>已完成</div>
            <img src="../../assets/images/cdone.png" class="body_icon" alt="" />
          </div>
          <div class="update_module">
            <div class="update_list">买家：{{ form.orderInfo.buyer }}</div>
            <div class="update_list">收银员：操作员（超级管理员）</div>
          </div>
          <div v-for="(item, index) in form.orderInfo.prolist" :key="index" class="update_item flex_y_center">
            <el-image class="update_image" :src="item.propic">
              <template #error>
                <div class="image-slot">
                  <el-icon>
                    <icon-picture />
                  </el-icon>
                </div>
              </template>
            </el-image>
            <div class="flex_x1">
              <div class="update_name flex flex_x1">
                <div class="flex_x1">
                  <div class="update_data">
                    {{ item.proname }}
                  </div>
                </div>
                <div :style="'color:' + storeInfo.userInfo.color1">¥{{ item.sell_price }}</div>
              </div>
              <div class="update_num flex">
                <div class="update_specs flex_x1">
                  规格：{{ item.ggname }}
                </div>
                数量：{{ item.num }}
              </div>
            </div>
          </div>
          <div class="update_module">
            <div class="update_list flex">
              <div class="update_lable">订单编号：{{ form.orderInfo.ordernum }}</div>下单时间：{{ form.orderInfo.createtime }}
            </div>
            <div class="update_list flex">
              <div class="update_lable">支付时间：{{ form.orderInfo.paytime }}</div>支付方式：{{ form.orderInfo.paytype }}
            </div>
            <div class="update_remarks">备注：{{ form.orderInfo.remark }}</div>
            <div class="update_list flex flex_x_bottom">
              <div class="update_unit">商品金额：</div><span
                :style="'color:' + storeInfo.userInfo.color1 + ';font-size:16px'">¥{{ form.orderInfo.pre_totalprice
                }}</span>
            </div>
            <div class="update_list flex flex_x_bottom">
              <div class="update_unit">实付金额：</div><span
                :style="'color:' + storeInfo.userInfo.color1 + ';font-size:16px'">¥{{ form.orderInfo.totalprice
                }}</span>
            </div>
          </div>
        </div>
        <div v-if="form.orderInfo != ''" class="update_opt flex flex_x_bottom">
          <div @click="goPrint"
            :style="'color:' + storeInfo.userInfo.color1 + ';border-color:' + storeInfo.userInfo.color1"
            class="update_btn flex_xy_center">打印小票</div>
          <div @click="form.remarkState = true" :style="'color:#fff' + ';background:' + storeInfo.userInfo.color1"
            class="update_btn flex_xy_center">备注</div>
        </div>
        <div v-if="form.orderInfo == ''" class="update_notice">
          <img src="../../assets/images/order.png" alt="">
          <div class="text">未选中任何订单</div>
        </div>
      </div>
    </div>
    <el-dialog v-model="form.remarkState" title="添加备注" width="500px">
      <el-form :model="form">
        <el-form-item label="备注" label-width="200">
          <el-input v-model="form.remark" :autosize="{ minRows: 6, maxRows: 4 }" maxlength="200" show-word-limit
            type="textarea" />
        </el-form-item>
      </el-form>
      <template #footer>
        <span class="dialog-footer">
          <el-button @click="form.remarkState = false">取消</el-button>
          <el-button @click="doRemark" type="primary">确定</el-button>
        </span>
      </template>
    </el-dialog>
  </div>
</template>

<script lang="ts" setup>
import { reactive, onMounted, onUnmounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessage } from 'element-plus'
import { openLoading, closeLoading } from '../../utils/loading'
import { Picture as IconPicture } from '@element-plus/icons-vue'
import { getCashierOrder, cashierChangeRemark, printBusiness } from '../../assets/apis/api'
import { userStore } from "../../store/userInfo"
const form: any = reactive({
  dataList: [],
  orderInfo: "",
  searchFocus: "",
  remark: "",
  keyword: "",
  remarkState: false
})
const storeInfo: any = userStore()
const router = useRouter()
onMounted(() => {
  if (router.currentRoute.value.query.id) {
    let cashier_id: any = router.currentRoute.value.query.id;
    sessionStorage.setItem("cashierId", cashier_id);
  }
  getCashier();
  keepDown();
})
const goPrint = () => {
  openLoading();
  let param: any = {
    apifrom: "vue",
    orderid: form.orderInfo.id
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
const doRemark = () => {
  if (form.remark) {
    openLoading();
    let param: any = {
      apifrom: "vue",
      orderid: form.orderInfo.id,
      remark: form.remark
    }
    cashierChangeRemark(param).then((res: any) => {
      closeLoading();
      if (res.status == '1') {
        form.orderInfo.remark = form.remark
        form.remark = ''
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
const orderClick = (e: any) => {
  form.orderInfo = e;
}
onUnmounted(() => {
  document.onkeydown = null
})
const searchIn = () => {
  form.searchFocus = '1'
}
const searchOut = () => {
  form.searchFocus = '0'
}
const getCashier = () => {
  openLoading();
  let param: any = {
    apifrom: "vue",
    cashier_id: sessionStorage.getItem("cashierId"),
    keyword: form.keyword,
    status: '1'
  }
  getCashierOrder(param).then((res: any) => {
    closeLoading();
    if (res.status == '1') {
      form.dataList = res.data;
    } else {
      ElMessage.error(res.msg)
    }
  })
}
const keepDown = () => {
  document.onkeydown = function (e) {
    if (e.key == 'Enter') {
      if (form.searchFocus == 1) {
        getCashier();
      }
    }
  }
}
</script>

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

  .inventory_head {
    color: #333;
    height: 50px;
    border-bottom: 1px solid #f2f2f2;
    padding: 0 16px;

    .inventory_title {
      font-weight: bold;
    }
  }

  .search {
    position: relative;
    box-sizing: border-box;
  }

  .search_module {
    position: relative;
    margin: 16px;
    width: 100%;
  }

  .search_icon {
    position: absolute;
    top: 0;
    bottom: 0;
    left: 10px;
    margin: auto 0;
  }

  .search_input {
    color: #333;
    padding: 4px 7px 4px 40px;
    width: 100%;
    border: none;
    background: #f2f3f5;
    border-radius: 4px;
    box-sizing: border-box;
    height: 40px;
  }

  .body {
    padding-bottom: 20px;
    overflow: hidden;
  }

  .body_module {
    height: 100%;
    overflow-y: scroll;
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
    padding: 10px 16px;
    color: #333;
    cursor: pointer;
    border-bottom: 1px solid #f2f2f2;

    .module_head {
      height: 38px;
    }

    .module_state {
      color: #4476ff;
    }

    .module_item {
      padding: 10px 0;
    }

    .module_image {
      width: 68px;
      height: 68px;
      flex-shrink: 0;
      border-radius: 4px;
      margin-right: 12px;
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
      height: 50px;
    }

    .module_num {
      font-size: 14px;
      color: #999;
      text-align: right;
    }
  }
}



.operate {
  position: relative;
  margin-left: 20px;
  height: 100%;
  background: #ffffff;
}

.update {
  position: relative;
  height: 100%;
  flex-direction: column;

  .update_title {
    color: #333;
    line-height: 50px;
    padding: 0 16px;
    font-weight: bold;
    border-bottom: 1px solid #f2f2f2;
  }

  .update_notice {
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

  .update_icon {
    height: 110px;
    width: 110px;
    display: block;
    margin: 0 auto;
  }

  .update_text {
    text-align: center;
    font-size: 14px;
    font-weight: 500;
    color: rgb(178, 178, 178);
    margin-top: 30px;
  }

  .update_body {
    position: relative;
    overflow: auto;
  }

  .update_head {
    position: relative;
    height: 80px;
    padding-left: 20px;
    font-size: 20px;
    font-weight: bold;
    overflow: hidden;
  }

  .update_wait {
    color: #f1495c;
    background-color: #fee8ea;
  }

  .update_finish {
    color: #4476ff;
    background-color: #eaeeff;
  }

  .update_head img {
    height: 160%;
    margin-right: -30px;
    display: block;
  }

  .update_module {
    padding: 20px;
  }

  .update_list {
    margin-top: 20px;
    width: 100%;
  }

  .update_list:first-child {
    margin-top: 0;
  }

  .update_item {
    padding: 20px;
    border-bottom: 1px solid #f2f2f2;
  }

  .update_image {
    width: 68px;
    height: 68px;
    border-radius: 4px;
    margin-right: 12px;
  }

  .update_image .image-slot {
    display: flex;
    justify-content: center;
    align-items: center;
    width: 100%;
    height: 100%;
    background: var(--el-fill-color-light);
    color: var(--el-text-color-secondary);
    font-size: 30px;
  }

  .update_image .image-slot .el-icon {
    font-size: 30px;
  }

  .update_name {
    color: #333;
  }

  .update_specs {
    font-size: 14px;
    color: #333;
  }

  .update_num {
    color: #999;
    font-size: 14px;
    margin-top: 15px;
  }

  .update_remarks {
    border-top: 1px solid #f2f2f2;
    border-bottom: 1px solid #f2f2f2;
    padding: 20px 0;
    margin-top: 20px;
  }

  .update_lable {
    width: 320px;
  }

  .update_unit {
    width: 120px;
  }


  .update_opt {
    padding: 8px;
    box-sizing: border-box;
    border-top: 1px solid #f2f2f2;

    .update_btn {
      height: 48px;
      width: 90px;
      font-size: 14px;
      border: 1px solid;
      border-radius: 4px;
      margin-left: 10px;
      cursor: pointer;
    }
  }
}
</style>