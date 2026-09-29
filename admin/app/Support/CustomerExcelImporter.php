<?php

namespace App\Support;

use App\Models\Customer;
use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

/**
 * Imports customers from an Excel/CSV file.
 *
 * Two header shapes are accepted:
 *   1. English keys:  first_name, last_name, personal_id, address, phone
 *   2. Georgian keys: სახელი, გვარი, პირადი ნომერი, მისამართი, ტელეფონი
 *
 * Header row is auto-detected inside the first 10 rows (case-insensitive).
 * Blank leading columns (like the numbering column in the source file) are ignored.
 */
class CustomerExcelImporter
{
    /**
     * @return array{created:int, skipped:int}
     */
    public static function import(string $filePath, int $districtId, int $adminId): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        if (empty($rows)) {
            throw new RuntimeException('ფაილი ცარიელია');
        }

        [$headerRowIndex, $columnMap] = self::detectHeader($rows);

        if ($headerRowIndex === null) {
            throw new RuntimeException(
                'ვერ მოიძებნა სათაურის ხაზი. საჭიროა: first_name, last_name, personal_id (ან სახელი, გვარი, პირადი ნომერი)'
            );
        }

        $created = 0;
        $skipped = 0;

        $total = count($rows);
        for ($i = $headerRowIndex + 1; $i < $total; $i++) {
            $row = $rows[$i];
            $get = static fn (string $key) => isset($columnMap[$key])
                ? trim((string) ($row[$columnMap[$key]] ?? ''))
                : '';

            $firstName = $get('first_name');
            $lastName = $get('last_name');
            $personalId = $get('personal_id');
            $address = $get('address');
            $phone = $get('phone');

            if ($firstName === '' || $lastName === '' || $personalId === '') {
                if ($firstName !== '' || $lastName !== '' || $personalId !== '' || $address !== '' || $phone !== '') {
                    $skipped++;
                }
                continue;
            }

            Customer::create([
                'admin_id' => $adminId,
                'district_id' => $districtId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'personal_id' => $personalId,
                'address' => $address !== '' ? $address : null,
                'phone' => $phone !== '' ? $phone : null,
                'status' => Customer::STATUS_NOT_CALLED,
            ]);
            $created++;
        }

        return ['created' => $created, 'skipped' => $skipped];
    }

    /**
     * Scans the first rows and returns [headerRowIndex, columnMap], where columnMap
     * maps normalized keys (first_name, last_name, personal_id, address, phone) to
     * the numeric column index in the row.
     *
     * @param  array<int, array<int, mixed>>  $rows
     * @return array{0:?int, 1:array<string,int>}
     */
    private static function detectHeader(array $rows): array
    {
        $aliases = self::headerAliases();

        $limit = min(count($rows), 10);
        for ($r = 0; $r < $limit; $r++) {
            $map = [];
            foreach ($rows[$r] ?? [] as $colIdx => $value) {
                $normalized = self::normalize((string) $value);
                if ($normalized === '') {
                    continue;
                }
                foreach ($aliases as $key => $variants) {
                    if (isset($map[$key])) {
                        continue;
                    }
                    if (in_array($normalized, $variants, true)) {
                        $map[$key] = $colIdx;
                        break;
                    }
                }
            }

            if (isset($map['first_name'], $map['last_name'], $map['personal_id'])) {
                return [$r, $map];
            }
        }

        return [null, []];
    }

    private static function normalize(string $s): string
    {
        $s = trim($s);
        $s = mb_strtolower($s, 'UTF-8');

        return preg_replace('/\s+/u', ' ', $s) ?? $s;
    }

    /**
     * @return array<string, array<int, string>>
     */
    private static function headerAliases(): array
    {
        return [
            'first_name' => ['first_name', 'firstname', 'name', 'სახელი'],
            'last_name' => ['last_name', 'lastname', 'surname', 'გვარი'],
            'personal_id' => [
                'personal_id',
                'personalid',
                'personal number',
                'personal_number',
                'პირადი ნომერი',
                'პ/ნ',
                'პნ',
                'პირადობა',
            ],
            'address' => ['address', 'მისამართი'],
            'phone' => ['phone', 'mobile', 'ტელეფონი', 'ტელ'],
        ];
    }
}
