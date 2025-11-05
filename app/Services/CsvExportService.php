<?php

namespace App\Services;

use App\Interfaces\CompanyRepositoryInterface;
use Illuminate\Support\Facades\Storage;

class CsvExportService
{
    protected $repository;

    public function __construct(CompanyRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    public function export(?string $filter = null): string
    {
        $data = $this->repository->exportData($filter);
        
        $filename = 'companies_export_' . now()->format('Y-m-d_His') . '.csv';
        $filepath = storage_path('app/exports/' . $filename);

    
        if (!file_exists(storage_path('app/exports'))) {
            mkdir(storage_path('app/exports'), 0755, true);
        }

        $file = fopen($filepath, 'w');
        
    
        fputcsv($file, ['company_name', 'email', 'phone_number']);

     
        $data->chunk(1000)->each(function ($chunk) use ($file) {
            foreach ($chunk as $record) {
                fputcsv($file, [
                    $record->company_name,
                    $record->email,
                    $record->phone_number
                ]);
            }
        });

        fclose($file);

        return $filepath;
    }
}