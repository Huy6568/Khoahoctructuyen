import axios from 'axios';
import type { AxiosInstance, InternalAxiosRequestConfig, AxiosResponse } from 'axios';

/**
 * Thiết lập cấu hình cơ bản cho Axios
 * baseURL: Lấy từ file .env (ví dụ: http://localhost:8000/api)
 */
const api: AxiosInstance = axios.create({
    baseURL: import.meta.env.VITE_API_URL || 'http://localhost:8000/api',
    timeout: 10000,
    headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
    },
});

/**
 * REQUEST INTERCEPTOR: Tự động đính kèm Token
 * Mỗi khi gửi request, hệ thống sẽ kiểm tra xem có token trong localStorage không
 * Nếu có, nó sẽ tự động thêm Header 'Authorization: Bearer <token>'
 */
api.interceptors.request.use(
    (config: InternalAxiosRequestConfig) => {
        const token = localStorage.getItem('access_token');
        
        if (token && config.headers) {
            config.headers.Authorization = `Bearer ${token}`;
        }
        
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

/**
 * RESPONSE INTERCEPTOR: Xử lý phản hồi và lỗi hệ thống
 * Giúp bạn quản lý tập trung các mã lỗi như 401 (Hết hạn đăng nhập) hoặc 403 (Không có quyền)
 */
api.interceptors.response.use(
    (response: AxiosResponse) => {
        // Bạn có thể xử lý dữ liệu ở đây trước khi trả về cho Component
        return response;
    },
    (error) => {
        const status = error.response ? error.response.status : null;

        if (status === 401) {
            // Trường hợp: Token hết hạn hoặc không hợp lệ
            console.error('Phiên đăng nhập đã hết hạn.');
            localStorage.removeItem('access_token');
            localStorage.removeItem('user_info');
            
            // Tùy chọn: Chuyển hướng về trang login nếu không ở trang login
            if (!window.location.pathname.includes('/login')) {
                window.location.href = '/login';
            }
        }

        if (status === 403) {
            console.error('Bạn không có quyền thực hiện hành động này.');
        }

        if (status === 500) {
            console.error('Lỗi hệ thống từ phía Backend (Laravel).');
        }

        return Promise.reject(error);
    }
);

export default api;