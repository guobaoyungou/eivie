<template>
  <div class="header">
    <div class="menu flex_y_center">
      <img :src="form.userInfo.blogo" alt="">
      <div class="title flex_x1">{{ form.userInfo.name }}</div>
      <div class="user">
        <el-dropdown>
          <span class="el-dropdown-link">
            操作员（{{ form.userInfo.option_name }}）<el-icon class="el-icon--right">
              <arrow-down />
            </el-icon>
          </span>
          <template #dropdown>
            <el-dropdown-menu>
              <el-dropdown-item @click="loginOut">退出登录</el-dropdown-item>
            </el-dropdown-menu>
          </template>
        </el-dropdown>
      </div>
    </div>
  </div>
  <div class="body">
    <div class="module flex">
      <div class="nav">
        <div @click="toPath('/index/index')" class="item"
          :style="form.currentPath == '/index/index' ? 'color:' + storeInfo.userInfo.color1 + ';background:rgba(' + storeInfo.userInfo.color1rgb.red + ',' + storeInfo.userInfo.color1rgb.green + ',' + storeInfo.userInfo.color1rgb.blue + ',0.2)' : ''">
          首页
        </div>
        <div @click="toPath('/order/index')" class="item"
          :style="form.currentPath == '/order/index' ? 'color:' + storeInfo.userInfo.color1 + ';background:rgba(' + storeInfo.userInfo.color1rgb.red + ',' + storeInfo.userInfo.color1rgb.green + ',' + storeInfo.userInfo.color1rgb.blue + ',0.2)' : ''">
          订单
        </div>
      </div>
      <div class="main flex_x1">
        <router-view />
      </div>
    </div>
  </div>
</template>
<script lang="ts" setup>
import { watch, reactive, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import { ElMessageBox, ElMessage } from 'element-plus'
import { getCashierInfo } from '../assets/apis/api'
import { openLoading, closeLoading } from '../utils/loading'
import { userStore } from "../store/userInfo"
const form: any = reactive({
  currentPath: '',
  userInfo: {}
})
const router = useRouter()
const storeInfo = userStore()
onMounted(() => {
  if (router.currentRoute.value.query.id) {
    let cashier_id: any = router.currentRoute.value.query.id;
    sessionStorage.setItem("cashierId", cashier_id);
  }
  togetCashierInfo();
})
const togetCashierInfo = () => {
  openLoading();
  let param: any = {
    apifrom: "vue",
    cashier_id: sessionStorage.getItem("cashierId")
  }
  getCashierInfo(param).then((res: any) => {
    closeLoading();
    if (res.status == '1') {
      form.userInfo = res.data;
      storeInfo.setuserInfo(res.data);
      var link: any = {};
      link = document.querySelector("link[rel*='icon']") || document.createElement('link');
      link.type = 'image/x-icon';
      link.rel = 'shortcut icon';
      link.href = res.data.ico;
      document.getElementsByTagName('head')[0].appendChild(link);
    } else {
      ElMessage.error(res.msg)
    }
  })
}
const loginOut = () => {
  ElMessageBox.confirm(
    '退出登录?',
    '提示',
    {
      confirmButtonText: '确定',
      cancelButtonText: '取消',
      distinguishCancelAndClose: true,
      type: 'warning',
      callback: function (action: any) {
        if (action == 'confirm') {
          window.location.href = window.location.origin + '/?s=/login/logout'
        }
      }
    }
  )
}
watch(() => router.currentRoute.value.path, (toPath: any) => {
  form.currentPath = toPath
}, { immediate: true, deep: true })

const toPath = (path: any) => {
  router.replace(path)
}
</script>

<style scoped lang="scss">
.header {
  width: 100%;
  background: #fff;

  .menu {
    background: #fff;
    height: 65px;
    max-width: 1627px;
    width: 100%;
    margin: 0 auto;

    img {
      height: 42px;
      border-radius: 5px;
      margin-right: 15px;
    }
  }

  .title {
    font-size: 16px;
    font-weight: bold;
    color: #333;
  }

  .user {
    cursor: pointer;
    font-size: 14px;
    color: #6b6f7a;
  }
}

.body {
  width: 100%;
  background-color: #f7f7f7;
  padding: 20px 0;
  box-sizing: border-box;
  height: calc(100vh - 65px);

  .module {
    max-width: 1627px;
    width: 100%;
    height: 100%;
    margin: 0 auto;
  }

  .nav {
    width: 72px;
    height: 100%;
    background: #fff;
    border-right: 1px solid #f2f2f2;
  }

  .item {
    height: 64px;
    background: #fff;
    border-bottom: 1px solid #f2f2f2;
    line-height: 64px;
    text-align: center;
    cursor: pointer;
    font-size: 16px;
    color: #333;
  }

  .main {
    height: 100%;
  }
}
</style>