// types/course.ts
export interface Course {
    id: number;
    title: string;
    description: string;
    thumbnail: string; // Tên mới từ Resource
    instructor: {
        id: number;
        name: string;
    };
    price: {
        raw: number;
        formatted: string;
    };
    rating: {
        average: number;
        count: number;
    };
    status: string;
    created_at: string;
}