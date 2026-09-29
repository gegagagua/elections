<?php

namespace App\Console\Commands;

use App\Models\District;
use App\Models\User;
use App\Support\CustomerExcelImporter;
use Illuminate\Console\Command;

class ImportDistrictCustomers extends Command
{
    protected $signature = 'customers:import
        {file : Absolute path to the .xlsx / .xls / .csv file}
        {--district= : Existing district id or name (created for the admin if missing)}
        {--admin= : Admin user id or email (defaults to the first admin)}
        {--description= : Optional description used when creating the district}';

    protected $description = 'Import customers from a "საბოლოო კარდაკარი" formatted Excel file into a district';

    public function handle(): int
    {
        $file = $this->argument('file');
        if (! is_file($file)) {
            $this->error("File not found: {$file}");

            return self::FAILURE;
        }

        $admin = $this->resolveAdmin();
        if (! $admin) {
            $this->error('Admin not found. Pass --admin=<id|email> or seed one.');

            return self::FAILURE;
        }

        $districtInput = (string) ($this->option('district') ?: pathinfo($file, PATHINFO_FILENAME));
        $district = $this->resolveDistrict($admin, $districtInput);
        $this->line("Admin:    {$admin->email} (#{$admin->id})");
        $this->line("District: {$district->name} (#{$district->id})");
        $this->line("File:     {$file}");

        try {
            $result = CustomerExcelImporter::import($file, $district->id, $admin->id);
        } catch (\Throwable $e) {
            $this->error('Import failed: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info("Created: {$result['created']}, updated: {$result['updated']}, skipped: {$result['skipped']}, images attached: {$result['images']}.");

        return self::SUCCESS;
    }

    private function resolveAdmin(): ?User
    {
        $arg = $this->option('admin');
        if (! $arg) {
            return User::where('role', User::ROLE_ADMIN)->orderBy('id')->first();
        }

        if (ctype_digit((string) $arg)) {
            return User::where('id', (int) $arg)->first();
        }

        return User::where('email', $arg)->first();
    }

    private function resolveDistrict(User $admin, string $input): District
    {
        if (ctype_digit($input)) {
            $existing = District::where('id', (int) $input)->where('admin_id', $admin->id)->first();
            if ($existing) {
                return $existing;
            }
        }

        return District::firstOrCreate(
            ['admin_id' => $admin->id, 'name' => $input],
            ['description' => $this->option('description') ?: null],
        );
    }
}
