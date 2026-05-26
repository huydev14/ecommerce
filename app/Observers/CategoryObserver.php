<?php

namespace App\Observers;

use App\Models\Category;
use App\Services\AuditLogService;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

class CategoryObserver
{
    /**
     * Handle the Category "created" event.
     */
    public function created(Category $category): void
    {
        AuditLogService::log(
            "Tạo mới danh mục: $category->name (ID: $category->id)",
            $category,
            'category'
        );
    }

    /**
     * Handle the Category "updated" event.
     */
    public function updated(Category $category): void
    {
        $newData = $category->getChanges();
        $oldData = Arr::only($category->getOriginal(), array_keys($newData));

        $properties = [
            'old' => $oldData,
            'attributes' => $newData,
        ];

        AuditLogService::log(
            "Cập nhật danh mục: $category->name (ID: $category->id)",
            $category,
            'category',
            Auth::user(),
            $properties
        );
    }

    /**
     * Handle the Category "deleted" event.
     */
    public function deleted(Category $category): void
    {
        AuditLogService::log(
            "Xóa danh mục: $category->name (ID: $category->id)",
            $category,
            'category'
        );
    }

}
