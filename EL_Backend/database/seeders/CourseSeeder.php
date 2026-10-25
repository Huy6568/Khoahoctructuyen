<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CourseSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Lấy danh sách Category IDs
        $allCategoryIds = DB::table('categories')->pluck('id')->toArray();

        if (empty($allCategoryIds)) {
            return;
        }

        // 2. Danh sách 15 khóa học mẫu với dữ liệu mảng (Array) khác biệt
        $sampleCourses = [
            [
                'title' => 'Mastering React.js từ cơ bản đến nâng cao',
                'description' => 'Khóa học toàn diện giúp bạn làm chủ React.js từ con số 0. Bạn sẽ học cách xây dựng các ứng dụng Single Page (SPA) có hiệu năng cao, tái sử dụng component linh hoạt và quản lý state phức tạp.',
                'objectives' => [
                    'Hiểu sâu về Virtual DOM và vòng đời của React Component.',
                    'Làm chủ React Hooks (useState, useEffect, useContext).',
                    'Quản lý State toàn cục với Redux Toolkit.',
                    'Xây dựng và deploy một ứng dụng E-commerce thực tế.'
                ],
                'requirements' => [
                    'Nắm vững HTML, CSS cơ bản.',
                    'Hiểu về JavaScript ES6 (Arrow function, Destructuring).'
                ],
                'price' => 599000,
                'thumbnail' => 'https://picsum.photos/300/170?random=10',
            ],
            [
                'title' => 'Thiết kế giao diện người dùng với Figma',
                'description' => 'Figma là công cụ thiết kế UI/UX hàng đầu hiện nay. Khóa học này sẽ hướng dẫn bạn từ cách sử dụng công cụ cơ bản đến việc tạo ra các Auto Layout và Prototype mượt mà.',
                'objectives' => [
                    'Thành thạo công cụ Figma trong 1 tuần.',
                    'Sử dụng Auto Layout để thiết kế giao diện Responsive.',
                    'Xây dựng Design System và Component Sets.',
                    'Tạo Prototype tương tác trực quan để thuyết trình.'
                ],
                'requirements' => [
                    'Không yêu cầu kinh nghiệm thiết kế.',
                    'Cần có máy tính kết nối Internet để dùng Figma web.'
                ],
                'price' => 350000,
                'thumbnail' => 'https://picsum.photos/300/170?random=11',
            ],
            [
                'title' => 'Python cho Khoa học dữ liệu (Data Science)',
                'description' => 'Bước vào thế giới dữ liệu với Python. Khóa học tập trung vào các thư viện cốt lõi nhất giúp bạn làm sạch, phân tích và trực quan hóa các tập dữ liệu khổng lồ.',
                'objectives' => [
                    'Sử dụng Pandas để xử lý và làm sạch dữ liệu.',
                    'Tính toán ma trận hiệu năng cao với Numpy.',
                    'Vẽ biểu đồ trực quan hóa dữ liệu bằng Matplotlib và Seaborn.',
                    'Phân tích tập dữ liệu thực tế từ Kaggle.'
                ],
                'requirements' => [
                    'Biết cú pháp lập trình cơ bản (bất kỳ ngôn ngữ nào).',
                    'Đam mê với những con số và thống kê.'
                ],
                'price' => 890000,
                'thumbnail' => 'https://picsum.photos/300/170?random=12',
            ],
            [
                'title' => 'Node.js & MongoDB: Xây dựng RESTful API',
                'description' => 'Sử dụng JavaScript ở phía máy chủ. Khóa học hướng dẫn bạn dùng Express.js kết hợp cơ sở dữ liệu NoSQL (MongoDB) để làm backend cho mọi ứng dụng web và mobile.',
                'objectives' => [
                    'Xây dựng API chuẩn RESTful với Express.js.',
                    'Thiết kế mô hình dữ liệu NoSQL với Mongoose.',
                    'Bảo mật API với JWT (JSON Web Token) và bcrypt.',
                    'Xử lý lỗi và tối ưu hóa hiệu suất ứng dụng Node.js.'
                ],
                'requirements' => [
                    'Hiểu biết cơ bản về JavaScript.',
                    'Đã từng thao tác với API (Postman).'
                ],
                'price' => 450000,
                'thumbnail' => 'https://picsum.photos/300/170?random=13',
            ],
            [
                'title' => 'Kỹ năng quản lý dự án Agile/Scrum',
                'description' => 'Học cách làm việc nhóm hiệu quả theo mô hình hiện đại nhất. Khóa học không dạy code, mà dạy cách để một đội ngũ code làm ra sản phẩm đúng hạn và đúng yêu cầu.',
                'objectives' => [
                    'Hiểu rõ tư duy Agile và khung làm việc Scrum.',
                    'Viết User Story và tổ chức Backlog chuẩn xác.',
                    'Chạy các buổi họp Sprint Planning, Daily Scrum, Retrospective.',
                    'Sử dụng Jira để quản lý luồng công việc.'
                ],
                'requirements' => [
                    'Sẵn sàng làm việc nhóm.',
                    'Có kinh nghiệm làm việc trong môi trường công sở là một lợi thế.'
                ],
                'price' => 0,
                'thumbnail' => 'https://picsum.photos/300/170?random=14',
            ],
            [
                'title' => 'Fullstack Web Development với Next.js',
                'description' => 'Next.js là tương lai của React. Học cách render HTML từ server (SSR), tối ưu hóa SEO và xây dựng ứng dụng Fullstack chỉ với một framework duy nhất.',
                'objectives' => [
                    'Hiểu rõ sự khác biệt giữa SSR, SSG và CSR.',
                    'Sử dụng App Router mới nhất của Next.js.',
                    'Fetch dữ liệu an toàn trên Server Components.',
                    'Tích hợp cơ sở dữ liệu trực tiếp trong Next.js (Server Actions).'
                ],
                'requirements' => [
                    'Nắm vững React.js cơ bản.',
                    'Hiểu về khái niệm API.'
                ],
                'price' => 1200000,
                'thumbnail' => 'https://picsum.photos/300/170?random=15',
            ],
            [
                'title' => 'Laravel API thực chiến với Sanctum',
                'description' => 'Chuyên đề nâng cao dành cho lập trình viên PHP. Tách biệt hoàn toàn Frontend và Backend, dùng Laravel chuyên làm API để cung cấp dữ liệu cho Vue, React hoặc Mobile App.',
                'objectives' => [
                    'Tạo API Resources để chuẩn hóa định dạng JSON trả về.',
                    'Sử dụng Laravel Sanctum để cấp Token xác thực.',
                    'Viết Unit Test cho các Endpoint API.',
                    'Xử lý File Upload và Background Jobs qua API.'
                ],
                'requirements' => [
                    'Đã biết sử dụng Laravel cơ bản (Routing, Controller, Eloquent).',
                    'Biết sử dụng phần mềm Postman.'
                ],
                'price' => 790000,
                'thumbnail' => 'https://picsum.photos/300/170?random=16',
            ],
            [
                'title' => 'SQL nâng cao cho lập trình viên',
                'description' => 'Vượt ra khỏi các câu lệnh SELECT đơn giản. Khóa học giúp bạn tư duy tối ưu hóa cơ sở dữ liệu, hiểu về Index, Transaction và viết các câu lệnh Query phức tạp nhưng vẫn chạy siêu nhanh.',
                'objectives' => [
                    'Phân tích và tối ưu hóa câu lệnh bằng EXPLAIN.',
                    'Hiểu và sử dụng đúng các loại JOIN (Left, Right, Inner, Cross).',
                    'Sử dụng Window Functions (OVER, PARTITION BY).',
                    'Đánh Index đúng cách để không làm chậm thao tác Ghi/Xóa.'
                ],
                'requirements' => [
                    'Đã từng làm việc với MySQL, PostgreSQL hoặc SQL Server.',
                    'Biết viết lệnh SELECT, INSERT, UPDATE, DELETE cơ bản.'
                ],
                'price' => 420000,
                'thumbnail' => 'https://picsum.photos/300/170?random=17',
            ],
            [
                'title' => 'UI Design System từ A-Z',
                'description' => 'Không chỉ là vẽ giao diện, khóa học dạy cách thiết kế hệ thống có tính kế thừa. Bạn sẽ biết cách chuẩn hóa màu sắc, typography và component cho các dự án quy mô lớn.',
                'objectives' => [
                    'Xây dựng bộ thư viện nền tảng (Foundations) chuẩn.',
                    'Thiết lập quy tắc Typography và Spacing (lưới khoảng cách).',
                    'Thiết kế bộ UI Kit có thể tái sử dụng cho toàn công ty.',
                    'Bàn giao tài liệu thiết kế (Handoff) cho Developer trơn tru.'
                ],
                'requirements' => [
                    'Biết sử dụng cơ bản công cụ Figma hoặc Sketch.',
                    'Có mắt thẩm mỹ tốt.'
                ],
                'price' => 650000,
                'thumbnail' => 'https://picsum.photos/300/170?random=18',
            ],
            [
                'title' => 'Digital Marketing cơ bản cho người mới',
                'description' => 'Nhập môn tiếp thị số. Giúp bạn hiểu toàn cảnh về hệ sinh thái Marketing Online, từ SEO website, quảng cáo trả phí đến xây dựng nội dung trên mạng xã hội.',
                'objectives' => [
                    'Hiểu các thuật ngữ cốt lõi (CPC, CPA, CTR, ROI).',
                    'Thiết lập chiến dịch Google Ads cơ bản.',
                    'Tối ưu hóa SEO On-page cho bài viết Website.',
                    'Đọc và hiểu các chỉ số từ Google Analytics.'
                ],
                'requirements' => [
                    'Sử dụng máy tính thành thạo.',
                    'Có một trang web hoặc fanpage để thực hành là điểm cộng.'
                ],
                'price' => 390000,
                'thumbnail' => 'https://picsum.photos/300/170?random=19',
            ],
            [
                'title' => 'Tiếng Anh giao tiếp cho IT',
                'description' => 'Nâng cấp vốn từ vựng chuyên ngành công nghệ thông tin. Thực hành kỹ năng phỏng vấn, trình bày dự án và viết email chuyên nghiệp với khách hàng nước ngoài.',
                'objectives' => [
                    'Bổ sung 500 từ vựng chuyên ngành IT thường gặp.',
                    'Tự tin tham gia các buổi họp Daily Standup bằng tiếng Anh.',
                    'Viết Email báo cáo tiến độ dự án chuyên nghiệp.',
                    'Rèn luyện kỹ năng phát âm và nghe phản xạ.'
                ],
                'requirements' => [
                    'Có nền tảng ngữ pháp tiếng Anh cơ bản (tương đương TOEIC 400).',
                    'Đang học hoặc làm việc trong lĩnh vực IT.'
                ],
                'price' => 280000,
                'thumbnail' => 'https://picsum.photos/300/170?random=20',
            ],
            [
                'title' => 'JavaScript ES6+ chuyên sâu',
                'description' => 'Bạn có thực sự hiểu JavaScript hoạt động thế nào dưới nền tảng? Khóa học đi sâu vào Event Loop, Closure, Hoisting và các tính năng hiện đại nhất của ECMAScript.',
                'objectives' => [
                    'Hiểu cơ chế bất đồng bộ (Asynchronous) thực sự hoạt động ra sao.',
                    'Thành thạo Promises và Async/Await.',
                    'Làm chủ các khái niệm khó như Closure, Scope, và từ khóa "this".',
                    'Sử dụng thành thạo Array Methods (map, filter, reduce).'
                ],
                'requirements' => [
                    'Đã biết viết các hàm JavaScript đơn giản.',
                    'Hiểu về biến và vòng lặp.'
                ],
                'price' => 520000,
                'thumbnail' => 'https://picsum.photos/300/170?random=21',
            ],
            [
                'title' => 'Product Management căn bản',
                'description' => 'Trở thành vị "thuyền trưởng" của sản phẩm công nghệ. Học cách nghiên cứu thị trường, làm việc với team Dev và Design để ra mắt một ứng dụng thành công.',
                'objectives' => [
                    'Nắm bắt quy trình phát triển sản phẩm từ ý tưởng đến ra mắt.',
                    'Viết tài liệu PRD (Product Requirement Document) chuẩn xác.',
                    'Phân tích người dùng và đưa ra quyết định dựa trên dữ liệu (Data-driven).',
                    'Sử dụng mô hình A/B Testing để tối ưu hóa sản phẩm.'
                ],
                'requirements' => [
                    'Khả năng tư duy logic và phân tích thị trường.',
                    'Thích quan sát hành vi người dùng.'
                ],
                'price' => 560000,
                'thumbnail' => 'https://picsum.photos/300/170?random=22',
            ],
            [
                'title' => 'Python Automation cho công việc văn phòng',
                'description' => 'Đừng tốn hàng giờ copy-paste dữ liệu nữa. Khóa học hướng dẫn bạn viết các đoạn mã Python ngắn để tự động đọc file Excel, gửi email tự động và cào dữ liệu từ Website.',
                'objectives' => [
                    'Viết Script Python tự động thao tác trên hàng nghìn file Excel.',
                    'Lập trình gửi báo cáo tự động qua Email định kỳ.',
                    'Sử dụng Selenium để tự động hóa thao tác trên trình duyệt web.',
                    'Trích xuất (Crawl) dữ liệu từ các trang web để làm báo cáo.'
                ],
                'requirements' => [
                    'Dành cho dân văn phòng, kế toán, nhân sự.',
                    'Chưa từng biết code vẫn có thể học.'
                ],
                'price' => 310000,
                'thumbnail' => 'https://picsum.photos/300/170?random=23',
            ],
            [
                'title' => 'Canva và tư duy thiết kế nội dung số',
                'description' => 'Tự tay làm ra các ấn phẩm truyền thông đẹp mắt mà không cần đến Photoshop phức tạp. Bí quyết thiết kế thần tốc nhưng vẫn đảm bảo tính thẩm mỹ cao.',
                'objectives' => [
                    'Sử dụng thành thạo toàn bộ công cụ của Canva.',
                    'Áp dụng lý thuyết màu sắc và bố cục cơ bản vào thiết kế.',
                    'Thiết kế bộ nhận diện thương hiệu cho Fanpage (Avatar, Cover, Post).',
                    'Tạo Presentation slide chuyên nghiệp, thu hút người xem.'
                ],
                'requirements' => [
                    'Cần có tài khoản Canva (miễn phí hoặc Pro đều được).',
                    'Yêu thích sự sáng tạo và cái đẹp.'
                ],
                'price' => 250000,
                'thumbnail' => 'https://picsum.photos/300/170?random=24',
            ],
        ];

        // 3. Tiến hành chèn dữ liệu
        foreach ($sampleCourses as $courseData) {
            DB::table('courses')->insert([
                'instructor_id' => 2, // ID của giảng viên (đảm bảo UserSeeder đã chạy và có user id=2)
                'category_id' => $allCategoryIds[array_rand($allCategoryIds)],
                'title' => $courseData['title'],
                'description' => $courseData['description'],
                // Mã hóa mảng thành JSON chuẩn, giữ nguyên font tiếng Việt
                'objectives' => json_encode($courseData['objectives'], JSON_UNESCAPED_UNICODE),
                'requirements' => json_encode($courseData['requirements'], JSON_UNESCAPED_UNICODE),
                'price' => $courseData['price'],
                'thumbnail_url' => $courseData['thumbnail'],
                'status' => 'published',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }
}
