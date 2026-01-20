import axios from 'axios'
import { ElMessage } from 'element-plus'
import { openLoading, closeLoading } from './loading'

const url = window.location.protocol + "//" + window.location.host;
// const url = 'https://v2d.diandashop.com';
const service = axios.create({
	baseURL: url,
	timeout: 60000,
	withCredentials: true,
	headers: {
		'Content-Type': 'application/json',
		'X-Requested-With': 'XMLHttpRequest'
	},
})

service.interceptors.request.use(
	function (config) {
		return config
	},
	function (error) {
		return Promise.reject(error)
	}
)

//响应拦截器
service.interceptors.response.use(
	function (response) {
		if (response.data.status == '-1') {
			var state = sessionStorage.getItem('loginState');
			if(!state||state==''){
				sessionStorage.setItem('loginState','1');
				ElMessage.error(response.data.msg)
				setTimeout(()=>{
					sessionStorage.setItem('loginState','');
				},1000)
				return;
			}else{
				return;
			}
		}
		const dataAxios = response.data
		return dataAxios
	},
	function (error) {
		if(error.response.status){
			closeLoading();
			ElMessage.error(error.response.data.message)
		}
		return Promise.reject(error)
	}
)

export default service