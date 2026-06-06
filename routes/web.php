<?php

use App\Http\Controllers\Api\Auth\OAuthController;
use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\BrandController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\BannerController;
use App\Http\Controllers\CustomerAddressController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductImportController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\TaxController;
use App\Http\Controllers\UnitController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\WarehouseController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;

require __DIR__ . '/auth.php';

// ---- OAuth Routes ----
Route::prefix('auth')->group(function () {
    Route::get('microsoft/redirect', [OAuthController::class, 'microsoftRedirect'])->name('oauth.microsoft.redirect');
    Route::get('microsoft/callback', [OAuthController::class, 'microsoftCallback'])->name('oauth.microsoft.callback');
});

// ----  Lang switch -----------------------------------
Route::get('lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'vi'])) {
        Session::put('locale', $locale);
    }

    return redirect()->back();
})->name('lang.switch');

Route::prefix('admin')->middleware('auth')->group(function () {

    Route::get('/test-mail', [SettingController::class, 'testMail']);
    // ---- Dashboard -----------------------------------
    Route::get('/', function () {
        return view('dashboard.index');
    })->name('dashboard');

    // ---- Users ---------------------------------------
    Route::prefix('users')->name('users.')->group(function () {
        Route::get('/data', [UserController::class, 'data'])->name('data');
        Route::get('/filter-data', [UserController::class, 'getFilterData'])->name('filter_data');
        Route::get('/getTeamsDropdown', [UserController::class, 'getTeamsDropdown'])->name('teams_data');

        Route::post('/{id}/restore', [UserController::class, 'restore'])->name('restore');
    });
    Route::resource('users', UserController::class);

    // ---- Profile ---------------------------------------
    Route::prefix('profile')->name('profile.')->group(function () {
        Route::get('/', [ProfileController::class, 'edit'])->name('edit');
        Route::patch('/', [ProfileController::class, 'update'])->name('update');
        Route::delete('/', [ProfileController::class, 'destroy'])->name('destroy');
    });

    // ---- Role ---------------------------------------
    Route::resource('/roles', RoleController::class)
        ->except(['show']);

    // ---- Audit logs ---------------------------------------
    Route::prefix('audit-logs')->name('audit-logs.')->group(function () {
        Route::get('/', [AuditLogController::class, 'index'])->name('index');
        Route::get('/{id}', [AuditLogController::class, 'show'])->whereNumber('id')->name('show');

        Route::get('/data', [AuditLogController::class, 'data'])->name('data');
        Route::get('/filter-data', [AuditLogController::class, 'getFilterData'])->name('filter_data');
    });

    // --- Banners -------------------------------------
    Route::get('/banners/data', [BannerController::class, 'data'])->name('banners.data');
    Route::resource('banners', BannerController::class)
        ->except(['show']);

    // --- Brands -----------------------------------
    Route::prefix('brands')->name('brands.')->group(function () {
        Route::get('/data', [BrandController::class, 'data'])->name('data');
        Route::get('/filter-data', [BrandController::class, 'getFilterData'])->name('filter_data');
    });
    Route::resource('brands', BrandController::class);

    // --- Categories -------------------------------
    Route::prefix('categories')->name('categories.')->group(function () {
        Route::get('/data', [CategoryController::class, 'data'])->name('data');
        Route::get('/filter-data', [CategoryController::class, 'getFilterData'])->name('filter_data');
    });
    Route::resource('categories', CategoryController::class);

    // --- Products ---------------------------------
    Route::prefix('products')->name('products.')->group(function () {
        Route::get('/data', [ProductController::class, 'data'])->name('data');
        Route::get('/filter-data', [ProductController::class, 'getFilterData'])->name('filter_data');
        Route::post('/{id}/restore', [ProductController::class, 'restore'])->name('restore');
    });
    Route::resource('products', ProductController::class);

    // --- Product variants --------------------------
    Route::prefix('product-variants')->name('product-variants.')->group(function () {
        Route::get('/data', [ProductVariantController::class, 'data'])->name('data');
        Route::get('/filter-data', [ProductVariantController::class, 'getFilterData'])->name('filter_data');
        Route::post('/{id}/restore', [ProductVariantController::class, 'restore'])->name('restore');
    });
    Route::resource('product-variants', ProductVariantController::class);

    // --- Customer addresses -----------------------
    Route::prefix('customer-addresses')->name('customer-addresses.')->group(function () {
        Route::get('/data', [CustomerAddressController::class, 'data'])->name('data');
        Route::get('/filter-data', [CustomerAddressController::class, 'getFilterData'])->name('filter_data');
        Route::get('/districts', [CustomerAddressController::class, 'districts'])->name('districts');
        Route::get('/wards', [CustomerAddressController::class, 'wards'])->name('wards');
    });
    Route::resource('customer-addresses', CustomerAddressController::class)
        ->except(['show']);

    // --- Units ------------------------------------
    Route::prefix('units')->name('units.')->group(function () {
        Route::get('/data', [UnitController::class, 'data'])->name('data');
        Route::get('/filter-data', [UnitController::class, 'getFilterData'])->name('filter_data');
        Route::post('/{id}/restore', [UnitController::class, 'restore'])->name('restore');
    });
    Route::resource('units', UnitController::class);

    // --- Taxes ------------------------------------
    Route::prefix('taxes')->name('taxes.')->group(function () {
        Route::get('/data', [TaxController::class, 'data'])->name('data');
        Route::get('/filter-data', [TaxController::class, 'getFilterData'])->name('filter_data');
        Route::post('/{id}/restore', [TaxController::class, 'restore'])->name('restore');
    });
    Route::resource('taxes', TaxController::class);

    // --- Warehouses -------------------------------
    Route::prefix('warehouses')->name('warehouses.')->group(function () {
        Route::get('/data', [WarehouseController::class, 'data'])->name('data');
        Route::get('/filter-data', [WarehouseController::class, 'getFilterData'])->name('filter_data');
        Route::post('/{id}/restore', [WarehouseController::class, 'restore'])->name('restore');
    });
    Route::resource('warehouses', WarehouseController::class)
        ->except(['show']);

    // --- Stocks -----------------------------------
    Route::prefix('stocks')->name('stocks.')->group(function () {
        Route::get('/data', [StockController::class, 'data'])->name('data');
        Route::get('/filter-data', [StockController::class, 'getFilterData'])->name('filter_data');
        Route::post('/{id}/restore', [StockController::class, 'restore'])->name('restore');
    });
    Route::resource('stocks', StockController::class)
        ->except(['show']);

    // --- Stock movements --------------------------
    Route::prefix('stock-movements')->name('stock-movements.')->group(function () {
        Route::get('/data', [StockMovementController::class, 'data'])->name('data');
        Route::get('/filter-data', [StockMovementController::class, 'getFilterData'])->name('filter_data');
        Route::post('/{id}/restore', [StockMovementController::class, 'restore'])->name('restore');
    });
    Route::resource('stock-movements', StockMovementController::class)
        ->only(['index', 'create', 'store', 'destroy']);

    // --- Orders -----------------------------------
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/data', [OrderController::class, 'data'])->name('data');
        Route::get('/filter-data', [OrderController::class, 'getFilterData'])->name('filter_data');
    });
    Route::resource('orders', OrderController::class)
        ->only(['index', 'show', 'update']);

    // --- Product imports --------------------------
    Route::prefix('product-imports')->name('product-imports.')->group(function () {
        Route::get('/', [ProductImportController::class, 'index'])->name('index');
        Route::post('/preview', [ProductImportController::class, 'uploadAndPreview'])->name('upload');
        Route::get('/{batchId}/preview', [ProductImportController::class, 'showPreview'])->name('preview');
        Route::get('/{batchId}/progress', [ProductImportController::class, 'progress'])->name('progress');
        Route::post('/{batchId}/resolve-master-data', [ProductImportController::class, 'resolveMissingMasterData'])->name('resolve-master-data');
        Route::post('/{batchId}/confirm', [ProductImportController::class, 'confirmImport'])->name('confirm');
        Route::delete('/{batchId}/cancel', [ProductImportController::class, 'cancelImport'])->name('cancel');
        Route::get('/download-template', [ProductImportController::class, 'downloadTemplate'])->name('download-template');
    });

    // --- Admin Settings -------------------------------
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', [SettingController::class, 'index'])->name('index');
        Route::patch('oauth/{provider}', [SettingController::class, 'updateOAuth'])->name('updateOAuth');
        Route::patch('mail', [SettingController::class, 'updateMail'])->name('updateMail');
    });
});

// VueJS mount
Route::get('/{any?}', function () {
    return view('client.index');
})->where('any', '.*');
