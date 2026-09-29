<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\District;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    public function run(): void
    {
        $gega = User::where('email', 'gegagagua@gmail.com')->first();

        if (! $gega) {
            return;
        }

        $district1 = District::updateOrCreate(
            ['admin_id' => $gega->id, 'name' => 'უბანი #1 — ვაკე'],
            ['description' => 'ვაკის უბანი']
        );
        $district2 = District::updateOrCreate(
            ['admin_id' => $gega->id, 'name' => 'უბანი #2 — საბურთალო'],
            ['description' => 'საბურთალოს უბანი']
        );

        $customers = [
            [$district1, ['first_name' => 'გიორგი',   'last_name' => 'ბერიძე',      'personal_id' => '01001012345', 'address' => 'თბილისი, რუსთაველის 12',   'phone' => '+995599111111', 'status' => Customer::STATUS_NOT_CALLED]],
            [$district1, ['first_name' => 'ნინო',     'last_name' => 'კაპანაძე',    'personal_id' => '01001067890', 'address' => 'ბათუმი, ჭავჭავაძის 5',      'phone' => '+995599222222', 'status' => Customer::STATUS_CALLED]],
            [$district1, ['first_name' => 'ლევანი',   'last_name' => 'გელაშვილი',   'personal_id' => '01001054321', 'address' => 'ქუთაისი, აღმაშენებლის 33', 'phone' => '+995599333333', 'status' => Customer::STATUS_NOT_CALLED]],
            [$district2, ['first_name' => 'თამარი',   'last_name' => 'ხარაძე',      'personal_id' => '01001099999', 'address' => 'თბილისი, ვაჟა-ფშაველას 7',  'phone' => '+995599444444', 'status' => Customer::STATUS_CAME]],
            [$district2, ['first_name' => 'დავითი',   'last_name' => 'ჯავახიშვილი', 'personal_id' => '01001088888', 'address' => 'რუსთავი, კოსტავას 21',      'phone' => '+995599555555', 'status' => Customer::STATUS_NOT_CALLED]],
        ];

        foreach ($customers as [$district, $customer]) {
            Customer::updateOrCreate(
                ['personal_id' => $customer['personal_id']],
                array_merge($customer, ['admin_id' => $gega->id, 'district_id' => $district->id])
            );
        }

        User::updateOrCreate(
            ['email' => 'manager1@example.com'],
            [
                'name' => 'მენეჯერი 1',
                'password' => Hash::make('password'),
                'role' => User::ROLE_MANAGER,
                'district_id' => $district1->id,
                'created_by_id' => $gega->id,
            ]
        );
    }
}
