<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\District;
use App\Support\CustomerExcelImporter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerController extends Controller
{
    private const SORTABLE = ['last_name', 'first_name', 'status', 'created_at'];

    public function index(Request $request, District $district): View
    {
        $this->authorizeDistrict($district);

        $search = trim((string) $request->query('search', ''));
        $sort = in_array($request->query('sort'), self::SORTABLE, true) ? $request->query('sort') : 'last_name';
        $direction = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $customers = Customer::query()
            ->where('district_id', $district->id)
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.$search.'%';
                $q->where(function ($qq) use ($like) {
                    $qq->where('first_name', 'like', $like)
                        ->orWhere('last_name', 'like', $like)
                        ->orWhere('personal_id', 'like', $like)
                        ->orWhere('phone', 'like', $like)
                        ->orWhere('address', 'like', $like);
                });
            })
            ->orderBy($sort, $direction)
            ->get();

        return view('customers.index', compact('district', 'customers', 'search', 'sort', 'direction'));
    }

    public function create(District $district): View
    {
        $this->authorizeDistrict($district);

        return view('customers.create', compact('district'));
    }

    public function store(Request $request, District $district): RedirectResponse
    {
        $this->authorizeDistrict($district);

        $data = $this->validateCustomer($request);

        if ($request->hasFile('image')) {
            $data['image_path'] = $request->file('image')->store('customers', 'public');
        }

        Customer::create([
            ...$data,
            'admin_id' => Auth::id(),
            'district_id' => $district->id,
        ]);

        return redirect()->route('districts.customers.index', $district)->with('status', 'ქასთამერი დაემატა');
    }

    public function edit(District $district, Customer $customer): View
    {
        $this->authorizeDistrict($district);
        $this->authorizeCustomer($district, $customer);

        return view('customers.edit', compact('district', 'customer'));
    }

    public function update(Request $request, District $district, Customer $customer): RedirectResponse
    {
        $this->authorizeDistrict($district);
        $this->authorizeCustomer($district, $customer);

        $data = $this->validateCustomer($request);

        if ($request->boolean('remove_image')) {
            if ($customer->image_path) {
                Storage::disk('public')->delete($customer->image_path);
            }
            $data['image_path'] = null;
        }

        if ($request->hasFile('image')) {
            if ($customer->image_path) {
                Storage::disk('public')->delete($customer->image_path);
            }
            $data['image_path'] = $request->file('image')->store('customers', 'public');
        }

        $customer->update($data);

        return redirect()->route('districts.customers.index', $district)->with('status', 'ქასთამერი განახლდა');
    }

    public function destroy(District $district, Customer $customer): RedirectResponse
    {
        $this->authorizeDistrict($district);
        $this->authorizeCustomer($district, $customer);

        if ($customer->image_path) {
            Storage::disk('public')->delete($customer->image_path);
        }

        $customer->delete();

        return redirect()->route('districts.customers.index', $district)->with('status', 'ქასთამერი წაიშალა');
    }

    public function updateStatus(Request $request, District $district, Customer $customer): RedirectResponse
    {
        $this->authorizeDistrict($district);
        $this->authorizeCustomer($district, $customer);

        $data = $request->validate([
            'status' => ['required', 'in:not_called,called,came'],
        ]);
        $customer->update($data);

        return back();
    }

    public function export(District $district): StreamedResponse
    {
        $this->authorizeDistrict($district);

        $statusLabels = [
            Customer::STATUS_NOT_CALLED => 'არ დარეკილი',
            Customer::STATUS_CALLED => 'დარეკილი',
            Customer::STATUS_CAME => 'მოვიდა',
        ];

        $customers = Customer::where('district_id', $district->id)
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle(mb_substr($district->name, 0, 30));

        $headers = ['first_name', 'last_name', 'personal_id', 'address', 'phone', 'status'];
        foreach ($headers as $i => $h) {
            $sheet->setCellValue([$i + 1, 1], $h);
        }
        $sheet->getStyle('A1:F1')->getFont()->setBold(true);

        $row = 2;
        foreach ($customers as $c) {
            $sheet->setCellValue("A{$row}", $c->first_name);
            $sheet->setCellValue("B{$row}", $c->last_name);
            $sheet->setCellValueExplicit("C{$row}", $c->personal_id, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("D{$row}", $c->address);
            $sheet->setCellValueExplicit("E{$row}", $c->phone, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("F{$row}", $statusLabels[$c->status] ?? $c->status);
            $row++;
        }

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $filename = preg_replace('/[^\p{L}\p{N}_-]+/u', '_', $district->name).'_'.date('Y-m-d').'.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            (new Xlsx($spreadsheet))->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function import(Request $request, District $district): RedirectResponse
    {
        $this->authorizeDistrict($district);

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:10240'],
        ]);

        try {
            $result = CustomerExcelImporter::import(
                $request->file('file')->getRealPath(),
                $district->id,
                Auth::id(),
            );
        } catch (\RuntimeException $e) {
            return back()->withErrors(['file' => $e->getMessage()]);
        }

        $msg = "იმპორტი: დაემატა {$result['created']}, განახლდა {$result['updated']}, გამოტოვდა {$result['skipped']}";
        if (! empty($result['images'])) {
            $msg .= ", სურათი {$result['images']}";
        }

        return redirect()
            ->route('districts.customers.index', $district)
            ->with('status', $msg);
    }

    private function validateCustomer(Request $request): array
    {
        return $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'personal_id' => ['required', 'string', 'max:32'],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:32'],
            'image' => ['nullable', 'image', 'max:5120'],
            'status' => ['nullable', 'in:not_called,called,came'],
        ]);
    }

    private function authorizeDistrict(District $district): void
    {
        abort_unless($district->admin_id === Auth::id(), 403);
    }

    private function authorizeCustomer(District $district, Customer $customer): void
    {
        abort_unless($customer->district_id === $district->id, 404);
    }
}
