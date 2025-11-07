<?php

namespace App\Interfaces;

use Illuminate\Support\Collection;

interface CompanyRepositoryInterface
{
    public function create(array $data);
    
    public function findDuplicate(string $companyName, string $email, string $phoneNumber);
    
    public function bulkInsert(array $records): bool;
    
    public function getAllWithFilters(?string $duplicateFilter = null);
    
    public function getDuplicateGroups(): Collection;
    
    public function getByBatch(string $batchId);
    
    public function markAsDuplicate(int $recordId, int $duplicateOfId): bool;
    
    public function exportData(?string $filter = null);
}