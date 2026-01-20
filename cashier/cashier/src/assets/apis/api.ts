// 导入axios实例
import httpRequest from '../../utils/axios'

// 1.商品列表
interface getProductParam {
	page: any,
	limit: any,
	name: any,
	code: any,
	cid: any,
	apifrom: any
}
export function getProductList(param: getProductParam) {
    return httpRequest({
		url: '/?s=/Cashier/getProductList',
		method: 'post',
		data: param,
	})
}

// 2.商品分类
interface getCategoryParam {
	page: any,
	limit: any,
	apifrom: any
}
export function getCategoryList(param: getCategoryParam) {
    return httpRequest({
		url: '/?s=/Cashier/getCategoryList',
		method: 'post',
		data: param,
	})
}

// 3.订单列表
interface getCashierParam {
	cashier_id: any,
	status: any
}
export function getCashierOrder(param: getCashierParam) {
    return httpRequest({
		url: '/?s=/Cashier/getCashierOrder',
		method: 'post',
		data: param,
	})
}

// 4.商品加入收银台
interface addToCashierParam {
	cashier_id: any,
	proid: any,
	num: any,
	price: any
}
export function addToCashier(param: addToCashierParam) {
    return httpRequest({
		url: '/?s=/Cashier/addToCashier',
		method: 'post',
		data: param,
	})
}

// 5.收银台商品数量增减
interface changeNumParam {
	cashier_id: any,
	proid: any,
	num: any
}
export function cashierChangeNum(param: changeNumParam) {
    return httpRequest({
		url: '/?s=/Cashier/cashierChangeNum',
		method: 'post',
		data: param,
	})
}

// 6.改价
interface cashierChangePriceParam {
	cashier_id: any,
	proid: any,
	price: any
}
export function cashierChangePrice(param: cashierChangePriceParam) {
    return httpRequest({
		url: '/?s=/Cashier/cashierChangePrice',
		method: 'post',
		data: param,
	})
}

// 7.挂单
interface hangupParam {
	cashier_id: any
}
export function hangup(param: hangupParam) {
    return httpRequest({
		url: '/?s=/Cashier/hangup',
		method: 'post',
		data: param,
	})
}

// 8.取单
interface cancelParam {
	orderid: any
}
export function cancelHangup(param: cancelParam) {
    return httpRequest({
		url: '/?s=/Cashier/cancelHangup',
		method: 'post',
		data: param,
	})
}

// 9.订单删除
interface delCashierParam {
	orderid: any
}
export function delCashierOrder(param: delCashierParam) {
    return httpRequest({
		url: '/?s=/Cashier/delCashierOrder',
		method: 'post',
		data: param,
	})
}

// 10.订单备注
interface cashierChangeRemarkParam {
	orderid: any,
	remark: any
}
export function cashierChangeRemark(param: cashierChangeRemarkParam) {
    return httpRequest({
		url: '/?s=/Cashier/cashierChangeRemark',
		method: 'post',
		data: param,
	})
}

// 11.通过 [手机号/mid] 查询取用户信息
interface searchMemberParam {
	keyword: any
}
export function searchMember(param: searchMemberParam) {
    return httpRequest({
		url: '/?s=/Cashier/searchMember',
		method: 'post',
		data: param,
	})
}

// 12.待支付订单
interface getWaitPayOrderParam {
	cashier_id: any,
	remove_zero: any
}
export function getWaitPayOrder(param: getWaitPayOrderParam) {
    return httpRequest({
		url: '/?s=/Cashier/getWaitPayOrder',
		method: 'post',
		data: param,
	})
}

// 13.会员优惠券列表
interface memberCouponListParam {
	page: any,
	limit : any,
	mid : any
}
export function memberCouponList(param: memberCouponListParam) {
    return httpRequest({
		url: '/?s=/Cashier/memberCouponList',
		method: 'post',
		data: param,
	})
}

// 14.支付预览
interface payPreviewParam {
	cashier_id: any,
	mid : any
}
export function payPreview(param: payPreviewParam) {
    return httpRequest({
		url: '/?s=/Cashier/payPreview',
		method: 'post',
		data: param,
	})
}

// 15.支付
interface payParam {
	cashier_id: any,
	couponid: any,
	mid: any,
	userscore: any,
	paytype: any
}
export function payCashier(param: payParam) {
    return httpRequest({
		url: '/?s=/Cashier/pay',
		method: 'post',
		data: param,
	})
}

// 16.打印小票
interface printParam {
	orderid: any
}
export function printBusiness(param: printParam) {
    return httpRequest({
		url: '/?s=/Cashier/print',
		method: 'post',
		data: param,
	})
}

// 17.收银台配置
interface getCashierInfoParam {
	cashier_id: any
}
export function getCashierInfo(param: getCashierInfoParam) {
    return httpRequest({
		url: '/?s=/Cashier/getCashierInfo',
		method: 'post',
		data: param,
	})
}