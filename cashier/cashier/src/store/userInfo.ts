import { defineStore } from 'pinia'

export const userStore = defineStore({
    id: 'userInfo',
    state: () => {
        return {
            userInfo: {
                color1:"",
                color1rgb:{
                    red:"",
                    green:"",
                    blue:""
                }
            }
        }
    },
    actions: {
        setuserInfo(data:any) {
            this.userInfo = data;
        }
    }
})