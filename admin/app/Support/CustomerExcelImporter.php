<?php

namespace App\Support;

use App\Models\Customer;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\BaseDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing as WsDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\MemoryDrawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
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
 *
 * Embedded images anchored to any cell in the same row are extracted and attached
 * to the customer's image_path. Rows are upserted by (district_id, personal_id) so
 * the import is safe to re-run.
 */
class CustomerExcelImporter
{
    /**
     * @return array{created:int, updated:int, skipped:int, images:int}
     */
    public static function import(string $filePath, int $districtId, int $adminId): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, false);

        if (empty($rows)) {
            throw new RuntimeException('ფაილი ცარიელია');
        }

        [$headerRowIndex, $columnMap] = self::detectHeader($rows);

        if ($headerRowIndex === null) {
            throw new RuntimeException(
                'ვერ მოიძებნა სათაურის ხაზი. საჭიროა: first_name, last_name, personal_id (ან სახელი, გვარი, პირადი ნომერი)'
            );
        }

        $drawingsByRow = self::indexDrawingsByRow($sheet);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $images = 0;

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

            // Excel rows are 1-indexed, and our $rows array is 0-indexed.
            $excelRowNumber = $i + 1;
            $imagePath = null;
            if (isset($drawingsByRow[$excelRowNumber])) {
                $imagePath = self::extractDrawing($drawingsByRow[$excelRowNumber]);
                if ($imagePath !== null) {
                    $images++;
                }
            }

            $existing = Customer::where('district_id', $districtId)
                ->where('personal_id', $personalId)
                ->first();

            $attributes = [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'address' => $address !== '' ? $address : null,
                'phone' => $phone !== '' ? $phone : null,
            ];

            if ($existing) {
                if ($imagePath !== null) {
                    if ($existing->image_path) {
                        Storage::disk('public')->delete($existing->image_path);
                    }
                    $attributes['image_path'] = $imagePath;
                }
                $existing->update($attributes);
                $updated++;
            } else {
                Customer::create([
                    ...$attributes,
                    'admin_id' => $adminId,
                    'district_id' => $districtId,
                    'personal_id' => $personalId,
                    'image_path' => $imagePath,
                    'status' => Customer::STATUS_NOT_CALLED,
                ]);
                $created++;
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'skipped' => $skipped,
            'images' => $images,
        ];
    }

    /**
     * @return array<int, BaseDrawing>
     */
    private static function indexDrawingsByRow(Worksheet $sheet): array
    {
        $index = [];
        foreach ($sheet->getDrawingCollection() as $drawing) {
            $coords = $drawing->getCoordinates();
            if (! $coords) {
                continue;
            }
            [, $rowNumber] = Coordinate::coordinateFromString($coords);
            $index[(int) $rowNumber] = $drawing;
        }

        return $index;
    }

    private static function extractDrawing(BaseDrawing $drawing): ?string
    {
        $extension = strtolower($drawing->getExtension() ?: 'jpg');
        if ($extension === 'jpeg') {
            $extension = 'jpg';
        }
        $relative = 'customers/'.Str::random(40).'.'.$extension;

        try {
            if ($drawing instanceof WsDrawing) {
                $bytes = @file_get_contents($drawing->getPath());
                if ($bytes === false || $bytes === '') {
                    return null;
                }
                Storage::disk('public')->put($relative, $bytes);

                return $relative;
            }

            if ($drawing instanceof MemoryDrawing) {
                $resource = $drawing->getImageResource();
                if (! $resource) {
                    return null;
                }
                ob_start();
                imagejpeg($resource, null, 85);
                $bytes = ob_get_clean();
                $relative = preg_replace('/\.\w+$/', '.jpg', $relative);
                Storage::disk('public')->put($relative, $bytes);

                return $relative;
            }
        } catch (\Throwable) {
            return null;
        }

        return null;
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
