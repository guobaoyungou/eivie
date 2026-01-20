import { ElLoading } from 'element-plus'

let loading: { close: () => void; };
function openLoading() {
    loading = ElLoading.service({
        lock: true,
        text: 'Loading',
        background: 'rgba(0, 0, 0, 0.7)',
    })
}
function closeLoading() {
    loading.close();
}
export {
    openLoading,
    closeLoading
}