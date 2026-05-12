<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            'Điện tử' => ['Điện thoại', 'Laptop', 'Máy tính bảng', 'Máy ảnh', 'Phụ kiện'],
            'Thời trang' => ['Nam', 'Nữ', 'Trẻ em', 'Giày dép', 'Túi xách'],
            'Nhà cửa' => ['Phòng khách', 'Phòng ngủ', 'Nhà bếp', 'Trang trí', 'Sân vườn'],
            'Sức khỏe' => ['Chăm sóc da', 'Trang điểm', 'Thực phẩm chức năng', 'Thiết bị y tế', 'Cá nhân'],
            'Sách' => ['Văn học', 'Kinh tế', 'Kỹ năng sống', 'Ngoại ngữ', 'Thiếu nhi'],
        ];

        $order = 1;
        foreach ($categories as $parentName => $children) {
            $parent = Category::create([
                'name' => $parentName,
                'slug' => Str::slug($parentName),
                'description' => 'Mô tả cho danh mục ' . $parentName,
                'is_active' => true,
                'sort_order' => $order++,
            ]);

            $childOrder = 1;
            foreach ($children as $childName) {
                Category::create([
                    'name' => $childName,
                    'slug' => Str::slug($childName) . '-' . $parent->id,
                    'description' => 'Mô tả cho danh mục ' . $childName,
                    'parent_id' => $parent->id,
                    'is_active' => true,
                    'sort_order' => $childOrder++,
                ]);
            }
        }
    }
}
