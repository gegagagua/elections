<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CustomerApiController extends Controller
{
    private const SORTABLE = ['last_name', 'first_name', 'status', 'created_at'];

    public function index(Request $request): JsonResponse
    {
        $user = Auth::user();
        $query = Customer::query();

        if ($user->role === User::ROLE_MANAGER) {
            if (! $user->district_id) {
                return response()->json(['data' => []]);
            }
            $query->where('district_id', $user->district_id);
        } else {
            $query->where('admin_id', $user->id);
        }

        if ($status = $request->query('status')) {
            $query->where('status', $status);
        }

        if ($term = trim((string) $request->query('search', ''))) {
            $like = '%'.$term.'%';
            $query->where(function ($q) use ($like) {
                $q->where('first_name', 'like', $like)
                    ->orWhere('last_name', 'like', $like)
                    ->orWhere('personal_id', 'like', $like)
                    ->orWhere('phone', 'like', $like)
                    ->orWhere('address', 'like', $like);
            });
        }

        $sort = $request->query('sort', 'last_name');
        if (! in_array($sort, self::SORTABLE, true)) {
            $sort = 'last_name';
        }
        $direction = strtolower((string) $request->query('direction', 'asc')) === 'desc' ? 'desc' : 'asc';

        $customers = $query->orderBy($sort, $direction)->get([
            'id',
            'first_name',
            'last_name',
            'personal_id',
            'address',
            'phone',
            'image_path',
            'status',
        ]);

        return response()->json(['data' => $customers->map(fn ($c) => self::present($c))]);
    }

    public function updateStatus(Request $request, Customer $customer): JsonResponse
    {
        $this->authorizeAccess($customer);

        $data = $request->validate([
            'status' => ['required', 'in:not_called,called,came'],
        ]);

        $customer->update($data);

        return response()->json(['data' => self::present($customer)]);
    }

    public function uploadImage(Request $request, Customer $customer): JsonResponse
    {
        $this->authorizeAccess($customer);

        $request->validate([
            'image' => ['required', 'image', 'max:8192'],
        ]);

        if ($customer->image_path) {
            Storage::disk('public')->delete($customer->image_path);
        }

        $path = $request->file('image')->store('customers', 'public');
        $customer->update(['image_path' => $path]);

        return response()->json(['data' => self::present($customer)]);
    }

    public function deleteImage(Customer $customer): JsonResponse
    {
        $this->authorizeAccess($customer);

        if ($customer->image_path) {
            Storage::disk('public')->delete($customer->image_path);
            $customer->update(['image_path' => null]);
        }

        return response()->json(['data' => self::present($customer)]);
    }

    private function authorizeAccess(Customer $customer): void
    {
        $user = Auth::user();
        if ($user->role === User::ROLE_MANAGER) {
            abort_unless($customer->district_id === $user->district_id, 403);
        } else {
            abort_unless($customer->admin_id === $user->id, 403);
        }
    }

    private static function present(Customer $customer): array
    {
        return [
            'id' => $customer->id,
            'first_name' => $customer->first_name,
            'last_name' => $customer->last_name,
            'personal_id' => $customer->personal_id,
            'address' => $customer->address,
            'phone' => $customer->phone,
            'status' => $customer->status,
            'image_url' => $customer->image_url,
        ];
    }
}
