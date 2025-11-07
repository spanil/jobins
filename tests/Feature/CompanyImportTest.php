<?php

namespace Tests\Feature;

use App\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CompanyImportTest extends TestCase
{
    /**
     * A basic feature test example.
     */
    use RefreshDatabase;
    public function it_can_import_valid_csv_file()
    {
        $csvContent = "company_name,email,phone_number\n";
        $csvContent .= "Acme Corp,contact@acme.com,1234567890\n";
        $csvContent .= "Tech Solutions,info@techsol.com,9876543210\n";
        $csvContent .= "Global Industries,hello@global.com,5551234567\n";

        $file = UploadedFile::fake()->createWithContent('companies.csv', $csvContent);

        $response = $this->postJson('/api/company/import', [
            'file' => $file
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'total',
                    'imported',
                    'duplicates',
                    'errors',
                    'batch_id'
                ]
            ]);

        $this->assertEquals(3, Company::count());
        $this->assertDatabaseHas('companies', [
            'company_name' => 'Acme Corp',
            'email' => 'contact@acme.com',
            'phone_number' => '1234567890'
        ]);
    }

    /** #test*/
    public function it_detects_duplicate_records_during_import()
    {
        // Create original record
        Company::create([
            'company_name' => 'Acme Corp',
            'email' => 'contact@acme.com',
            'phone_number' => '1234567890',
            'is_duplicate' => false
        ]);

        $csvContent = "company_name,email,phone_number\n";
        $csvContent .= "Acme Corp,contact@acme.com,1234567890\n"; // Duplicate
        $csvContent .= "Tech Solutions,info@techsol.com,9876543210\n"; // New

        $file = UploadedFile::fake()->createWithContent('companies.csv', $csvContent);

        $response = $this->postJson('/api/v1/company/import', [
            'file' => $file
        ]);

        $response->assertStatus(200)
        ->assertJson([
            'success' => true,
            'message' => 'Import completed',
            'data' => [
                'total' => 2,
                'imported' => 2,
                'errors' => 0,
            ]
        ]);

        $this->assertEquals(3, Company::count());
        $this->assertEquals(1, Company::where('is_duplicate', true)->count());

        $duplicate = Company::where('is_duplicate', true)->first();
        $this->assertNotNull($duplicate->duplicate_of);
    }

    /** #test*/
    public function it_validates_email_format()
    {
        $csvContent = "company_name,email,phone_number\n";
        $csvContent .= "Acme Corp,invalid-email,1234567890\n";
        $csvContent .= "Tech Solutions,valid@email.com,9876543210\n";

        $file = UploadedFile::fake()->createWithContent('companies.csv', $csvContent);

        $response = $this->postJson('/api/v1/company/import', [
            'file' => $file
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total' => 2,
                    'imported' => 1,
                    'errors' => 1
                ]
            ]);

        $errorRecord = Company::whereNotNull('import_errors')->first();
        $this->assertNotNull($errorRecord);
        $this->assertNotNull($errorRecord->import_errors);
    }

    /** #test*/
    public function it_requires_company_name_field()
    {
        $csvContent = "company_name,email,phone_number\n";
        $csvContent .= ",contact@acme.com,1234567890\n"; // Missing company name
        $csvContent .= "Valid Corp,info@valid.com,9876543210\n";

        $file = UploadedFile::fake()->createWithContent('companies.csv', $csvContent);

        $response = $this->postJson('/api/v1/company/import', [
            'file' => $file
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total' => 2,
                    'imported' => 1,
                    'errors' => 1
                ]
            ]);
    }

    /** #test*/
    public function it_rejects_non_csv_files()
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->postJson('/api/v1/company/import', [
            'file' => $file
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }



    /** #test*/
    public function it_requires_file_for_import()
    {
        $response = $this->postJson('/api/v1/company/import', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['file']);
    }

    /** #test*/
    public function it_rejects_csv_with_invalid_headers()
    {
        $csvContent = "wrong_column,another_column\n";
        $csvContent .= "Value 1,Value 2\n";

        $file = UploadedFile::fake()->createWithContent('invalid.csv', $csvContent);

        $response = $this->postJson('/api/v1/company/import', [
            'file' => $file
        ]);

        $response->assertStatus(500)
            ->assertJson([
                'success' => false
            ]);
    }

    /** #test*/
    public function it_handles_multiple_duplicates_of_same_record()
    {
        $csvContent = "company_name,email,phone_number\n";
        $csvContent .= "Acme Corp,contact@acme.com,1234567890\n"; // Original
        $csvContent .= "Acme Corp,contact@acme.com,1234567890\n"; // Duplicate 1
        $csvContent .= "Acme Corp,contact@acme.com,1234567890\n"; // Duplicate 2

        $file = UploadedFile::fake()->createWithContent('companies.csv', $csvContent);

        $response = $this->postJson('/api/v1/company/import', [
            'file' => $file
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Import completed',
                'data' => [
                    'total' => 3,
                    'imported' => 3,
                    'errors' => 0,
                ]
            ]);

        $this->assertEquals(3, Company::count());
        $this->assertEquals(2, Company::where('is_duplicate', true)->count());

        // All duplicates should point to the same original
        $duplicates = Company::where('is_duplicate', true)->get();
        $originalId = $duplicates->first()->duplicate_of;
        $this->assertTrue($duplicates->every(fn($dup) => $dup->duplicate_of === $originalId));
    }



    /** #test*/
    public function it_assigns_same_batch_id_to_all_imported_records()
    {
        $csvContent = "company_name,email,phone_number\n";
        $csvContent .= "Company One,one@test.com,1234567890\n";
        $csvContent .= "Company Two,two@test.com,9876543210\n";

        $file = UploadedFile::fake()->createWithContent('companies.csv', $csvContent);

        $response = $this->postJson('/api/v1/company/import', [
            'file' => $file
        ]);

        $response->assertStatus(200);

        $batchId = $response->json('data.batch_id');
        $this->assertNotNull($batchId);

        $companies = Company::where('import_batch', $batchId)->get();
        $this->assertEquals(2, $companies->count());
        $this->assertTrue($companies->every(fn($c) => $c->import_batch === $batchId));
    }

    /** #test*/
    public function it_respects_max_length_constraints()
    {
        $csvContent = "company_name,email,phone_number\n";
        $csvContent .= str_repeat('A', 101) . ",email@test.com,1234567890\n"; // 101 chars (max 100)

        $file = UploadedFile::fake()->createWithContent('companies.csv', $csvContent);

        $response = $this->postJson('/api/v1/company/import', [
            'file' => $file
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'errors' => 1
                ]
            ]);
    }

    /** #test*/
    public function it_processes_large_csv_files_in_batches()
    {
        $csvContent = "company_name,email,phone_number\n";

        // Create 1000 records
        for ($i = 1; $i <= 1000; $i++) {
            $csvContent .= "Company $i,email$i@test.com,$i\n";
        }

        $file = UploadedFile::fake()->createWithContent('large.csv', $csvContent);

        $response = $this->postJson('/api/v1/company/import', [
            'file' => $file
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'total' => 1000,
                    'imported' => 1000
                ]
            ]);

        $this->assertEquals(1000, Company::count());
    }
}
