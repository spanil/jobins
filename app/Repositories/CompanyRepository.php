<?php
namespace App\Repositories;

use App\Interfaces\CompanyRepositoryInterface;
use App\Models\Company;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CompanyRepository implements CompanyRepositoryInterface
{
    protected $model;

    public function __construct(Company $model)
    {
        $this->model = $model;
    }

    public function create(array $data)
    {
        return $this->model->create($data);
    }

    public function findDuplicate(string $companyName, string $email, string $phoneNumber)
    {
        return $this->model
            ->where('company_name', $companyName)
            ->where('email', $email)
            ->where('phone_number', $phoneNumber)
            ->where('is_duplicate', false)
            ->first();
    }

    public function bulkInsert(array $records): bool
    {
        return $this->model->insert($records);
    }

    public function getAllWithFilters(?string $duplicateFilter = null)
    {
        $query = $this->model->newQuery();

        if ($duplicateFilter === 'duplicates') {
            $query->where('is_duplicate', true);
        } elseif ($duplicateFilter === 'unique') {
            $query->where('is_duplicate', false);
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    public function getDuplicateGroups(): Collection
    {
        return $this->model
            ->where('is_duplicate', true)
            ->with(['originalRecord'])
            ->get()
            ->groupBy('duplicate_of');
    }

    public function getByBatch(string $batchId)
    {
        return $this->model
            ->where('import_batch', $batchId)
            ->get();
    }

    public function markAsDuplicate(int $recordId, int $duplicateOfId): bool
    {
        return $this->model
            ->where('id', $recordId)
            ->update([
                'is_duplicate' => true,
                'duplicate_of' => $duplicateOfId
            ]);
    }

    public function exportData(?string $filter = null)
    {
        $query = $this->model->newQuery();

        if ($filter === 'duplicates') {
            $query->where('is_duplicate', true);
        } elseif ($filter === 'unique') {
            $query->where('is_duplicate', false);
        }

        return $query->select('company_name', 'email', 'phone_number', 'is_duplicate', 'duplicate_of');
    }
}