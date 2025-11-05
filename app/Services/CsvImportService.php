<?php

namespace App\Services;

use App\Interfaces\CompanyRepositoryInterface;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class CsvImportService
{
    protected $repository;
    protected $batchSize = 500;

    public function __construct(CompanyRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function import($filePath): array
    {
        $batchId = Str::uuid()->toString();
        $results = [
            'total' => 0,
            'imported' => 0,
            'duplicates' => 0,
            'errors' => 0,
            'batch_id' => $batchId
        ];

        $file = fopen($filePath, 'r');
        $header = fgetcsv($file);
        
        if (!$this->validateHeader($header)) {
            throw new \Exception('Invalid CSV format. Required columns: company_name, email, phone_number');
        }

        $batch = [];
        $rowNumber = 1;

        while (($row = fgetcsv($file)) !== false) {
            $rowNumber++;
            $results['total']++;

            $data = array_combine($header, $row);
            $validation = $this->validateRow($data, $rowNumber);

            if (!$validation['valid']) {
                $this->repository->create([
                    'company_name' => $data['company_name'] ?? 'Invalid',
                    'email' => $data['email'] ?? null,
                    'phone_number' => $data['phone_number'] ?? null,
                    'import_errors' => $validation['errors'],
                    'import_batch' => $batchId
                ]);
                $results['errors']++;
                continue;
            }

            // Check for duplicates
            $duplicate = $this->repository->findDuplicate(
                $data['company_name'],
                $data['email'],
                $data['phone_number']
            );

            if ($duplicate) {
                $batch[] = [
                    'company_name' => $data['company_name'],
                    'email' => $data['email'],
                    'phone_number' => $data['phone_number'],
                    'is_duplicate' => true,
                    'duplicate_of' => $duplicate->id,
                    'import_batch' => $batchId,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                $results['duplicates']++;
            } else {
                $batch[] = [
                    'company_name' => $data['company_name'],
                    'email' => $data['email'] ?? null,
                    'phone_number' => $data['phone_number'] ?? null,
                    'is_duplicate' => false,
                    'import_batch' => $batchId,
                    'created_at' => now(),
                    'updated_at' => now()
                ];
                $results['imported']++;
            }

            // Batch insert for performance
            if (count($batch) >= $this->batchSize) {
                $this->repository->bulkInsert($batch);
                $batch = [];
            }
        }

        // Insert remaining records
        if (!empty($batch)) {
            $this->repository->bulkInsert($batch);
        }

        fclose($file);

        return $results;
    }

    protected function validateHeader(array $header): bool
    {
        $required = ['company_name', 'email', 'phone_number'];
        return empty(array_diff($required, $header));
    }

    protected function validateRow(array $data, int $rowNumber): array
    {
        $validator = Validator::make($data, [
            'company_name' => 'required|string|max:100',
            'email' => 'nullable|email|max:100',
            'phone_number' => 'nullable|string|max:15'
        ]);

        if ($validator->fails()) {
            return [
                'valid' => false,
                'errors' => [
                    'row' => $rowNumber,
                    'messages' => $validator->errors()->all()
                ]
            ];
        }

        return ['valid' => true];
    }
}