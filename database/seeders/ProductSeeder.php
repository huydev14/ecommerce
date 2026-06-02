<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class ProductSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run()
    {
        if (!Storage::disk('local')->exists('cloudinary_seeds.json')) {
            $this->command->error('Không tìm thấy file cloudinary_seeds.json');
            return;
        }

        $cloudinaryImages = json_decode(Storage::disk('local')->get('cloudinary_seeds.json'), true);

        if (empty($cloudinaryImages)) {
            $this->command->error('Danh sách link ảnh từ Cloudinary rỗng!');
            return;
        }

        shuffle($cloudinaryImages);

        $this->command->info('Đang tải danh sách sản phẩm để seed thumbnail...');

        $products = Product::query()
            ->select(['id', 'category_id'])
            ->orderBy('category_id')
            ->orderBy('id')
            ->get();

        if ($products->isEmpty()) {
            $this->command->error('Không tìm thấy product nào để seed thumbnail!');
            return;
        }

        $this->command->info('Bắt đầu cập nhật thumbnail cho toàn bộ product...');

        $totalImages = count($cloudinaryImages);
        $categoryOffsets = [];

        $productsByCategory = $products->groupBy(function ($product) {
            return $product->category_id ?? 'no_category';
        });

        foreach ($productsByCategory as $categoryKey => $categoryProducts) {
            $categoryOffset = $categoryOffsets[$categoryKey] ?? (abs(crc32((string) $categoryKey)) % $totalImages);

            foreach ($categoryProducts->values() as $index => $product) {
                $selectedImage = $cloudinaryImages[($categoryOffset + $index) % $totalImages];

                DB::table('products')
                    ->where('id', $product->id)
                    ->update([
                        'thumbnail' => $selectedImage,
                        'updated_at' => now(),
                    ]);
            }
        }

        $this->command->info('Toàn bộ product đã được gán thumbnail');
    }
}