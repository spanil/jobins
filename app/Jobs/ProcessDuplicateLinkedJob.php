<?php

namespace App\Jobs;

use App\Models\Company;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessDuplicateLinkedJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    protected $batchId;

    /**
     * Create a new job instance.
     */
    public function __construct($batchId)
    {
        $this->batchId = $batchId;
    }

    /**
     * Execute the job.
     */
    public function handle()
    {
        $batchRecords = Company::where('import_batch', $this->batchId)->get();
        $seen = []; // track first occurrence in the batch

        foreach ($batchRecords as $record) {
            $key = strtolower(trim($record->company_name)) . '|' .
                strtolower(trim($record->email ?? '')) . '|' .
                strtolower(trim($record->phone_number ?? ''));

            // Find the very first record ever for this company/email/phone
            $original = Company::where('company_name', $record->company_name)
                ->where('email', $record->email)
                ->where('phone_number', $record->phone_number)
                ->orderBy('id', 'asc') // first record ever inserted
                ->first();

            if ($original && $original->id != $record->id) {
                // This record is a duplicate of the original
                $record->update([
                    'duplicate_of' => $original->id,
                    'is_duplicate' => true,
                ]);
            } else {
                // First occurrence ever (original)
                $seen[$key] = $record->id;
                $record->update([
                    'duplicate_of' => null,
                    'is_duplicate' => false,
                ]);
            }
        }
    }
}
