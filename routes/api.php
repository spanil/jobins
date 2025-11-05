<?php
use App\Http\Controllers\Api\Version1\CompanyController;
use Illuminate\Support\Facades\Route;
$apiGroup = [
    "prefix" => "v1",
    "namespace" => "Api",
    "as" => "api::"
];


Route::group($apiGroup, function () {
    Route::post('company/import', [CompanyController::class, 'import']);
    Route::get('company/', [CompanyController::class, 'index']);
    Route::get('company/duplicates/groups', [CompanyController::class, 'getDuplicateGroups']);
    Route::get('company/batch/{batchId}', [CompanyController::class, 'getByBatch']);
    Route::get('company/export', [CompanyController::class, 'export']);
    Route::put('company/{id}/mark-duplicate', [CompanyController::class, 'markAsDuplicate']);
});