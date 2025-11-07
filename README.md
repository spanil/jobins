#api import csv
 http://localhost:8000/api/v1/company/import
 #view all records
 http://localhost:8000/api/v1/company
 #duplicates only
 http://localhost:8000/api/v1/company?filter=duplicates
  #unique only
 http://localhost:8000/api/v1/company?filter=unique
 #duplicate groups
 http://localhost:8000/api/v1/company/duplicates/groups
 #export to csv
 http://localhost:8000/api/v1/company/export

 #postman workspace link
 https://www.postman.com/jobins-0761/jobins


 #run the queue job for duplicate data detection
 php artisan queue:work 

# Feature tests only
php artisan test --testsuite=Feature

## Unit tests only
php artisan test --testsuite=Unit
##Run Specific Test File
bashphp artisan test tests/Feature/CompanyImportTest.php
##Run Specific Test Method
bashphp artisan test --filter it_can_import_valid_csv_file