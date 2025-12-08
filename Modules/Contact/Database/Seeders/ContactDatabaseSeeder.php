<?php

namespace Modules\Contact\Database\Seeders;

use Illuminate\Support\Str;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\Contact\App\Models\Contact;
use Modules\Package\App\Models\Package;
use Modules\Subscription\App\Models\SubscriptionPackage;

class ContactDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $clients = DB::connection('crmplus')->table('companies')->get();
        $customPackage = Package::where('name', 'Free')->first();

        foreach($clients as $client) {
            $company = Contact::create([
                'id' => Str::uuid(),
                'company_id' => $client->id,
                'code' => Str::upper(Str::random(5)),
                'name' => $client->name,
                'phone' => $client->phone,
                'is_customer' => true,
                'is_active' => true,
            ]);

            SubscriptionPackage::create([
                'code' => Str::upper(Str::random(5)),
                'customer_id' => $company->id,
                'package_id' => $customPackage->id,
                'termin_duration' => 10,
                'termin' => 'year',
                'started_at' => '2020-01-01',
                'expired_at' => '2030-01-01',
                'is_active' => true,
                'company_id' => $company->company_id
            ]);
        }
    }
}
