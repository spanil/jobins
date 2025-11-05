<?php

namespace App\Http\Controllers\Api\Version1;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Interfaces\CompanyRepositoryInterface;
use App\Http\Requests\Company\CsvFileRequest;
use App\Http\Requests\Company\MarkDuplicateRequest;
use App\Http\Resources\V1\Api\CompanyResource;
use App\Services\CsvExportService;
use App\Services\CsvImportService;

class CompanyController extends Controller
{
    protected $repository;
    protected $importService;
    protected $exportService;

    public function __construct(
        CompanyRepositoryInterface $repository,
        CsvImportService $importService,
        CsvExportService $exportService
    ) {
        $this->repository = $repository;
        $this->importService = $importService;
        $this->exportService = $exportService;
    }

    /**
     * Import CSV file
     * POST /api/companies/import
     */
    public function import(CsvFileRequest $request)
    {   
        try {
            $file = $request->file('file');
            $filePath = $file->getRealPath();
            
            $results = $this->importService->import($filePath);

            return response()->json([
                'success' => true,
                'message' => 'Import completed',
                'data' => $results
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get all companies with optional filter
     * GET /api/companies?filter=duplicates|unique
     */
    public function index(Request $request)
    {
        try {
            $filter = $request->query('filter');
            $companies = $this->repository->getAllWithFilters($filter);

            return response()->json([
                'success' => true,
                'data' => CompanyResource::collection($companies),
                'count' => $companies->count()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get duplicate groups
     * GET /api/companies/duplicates/groups
     */
    public function getDuplicateGroups()
    {
        try {
            $groups = $this->repository->getDuplicateGroups();
            
            $formatted = $groups->map(function ($duplicates, $originalId) {
                return [
                    'original_record_id' => $originalId,
                    'original_record' => $duplicates->first()->originalRecord,
                    'duplicates' => $duplicates,
                    'duplicate_count' => $duplicates->count()
                ];
            })->values();

            return response()->json([
                'success' => true,
                'data' => $formatted,
                'total_groups' => $formatted->count()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get records by batch
     * GET /api/companies/batch/{batchId}
     */
    public function getByBatch($batchId)
    {
        try {
            $records = $this->repository->getByBatch($batchId);

            return response()->json([
                'success' => true,
                'data' => $records,
                'count' => $records->count()
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Export companies to CSV
     * GET /api/companies/export?filter=duplicates|unique
     */
    public function export(Request $request)
    {
        try {
            $filter = $request->query('filter');
            $filepath = $this->exportService->export($filter);

            return response()->download($filepath)->deleteFileAfterSend(true);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Mark record as duplicate
     * PUT /api/companies/{id}/mark-duplicate
     */
    public function markAsDuplicate(MarkDuplicateRequest $request, $id)
    {
       

        try {
            $validated = $request->validated();
            $result = $this->repository->markAsDuplicate($id, $validated['duplicate_of']);
            if ($result) {
                return response()->json([
                    'success' => true,
                    'message' => 'Record marked as duplicate'
                ], 200);
            }

            return response()->json([
                'success' => false,
                'message' => 'Failed to mark as duplicate'
            ], 400);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}