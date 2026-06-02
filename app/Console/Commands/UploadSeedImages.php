<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use CloudinaryLabs\CloudinaryLaravel\Facades\Cloudinary;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;

class UploadSeedImages extends Command
{
    protected $signature = 'images:upload-seed';
    protected $description = 'Tự động quét thư mục tại local và upload ảnh lên Cloudinary';

    public function handle()
    {
        $dirPath = storage_path('app/private/seed_images');

        if (!File::exists($dirPath)) {
            $this->error("Không tìm thấy thư mục ảnh tại: $dirPath");
            return;
        }

        $files = File::files($dirPath);

        if (empty($files)) {
            $this->error("Thư mục seed_images đang trống rỗng!");
            return;
        }

        $totalFiles = count($files);
        $this->info("Tìm thấy {$totalFiles} ảnh gốc trên máy. Bắt đầu đẩy lên Cloudinary...");

        $uploadedUrls = [];

        $bar = $this->output->createProgressBar($totalFiles);
        $bar->start();

        foreach ($files as $file) {
            try {
                $result = Cloudinary::uploadApi()->upload($file->getRealPath(), [
                    'folder' => 'products',
                ]);

                $uploadedUrls[] = $result['secure_url'];
            } catch (\Exception $e) {
                continue;
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        Storage::disk('local')->put('cloudinary_seeds.json', json_encode($uploadedUrls));
        $this->info('Đã hoàn thành! Toàn bộ link ảnh Cloudinary đã được lưu vào file temporary.');
    }
}