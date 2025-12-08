<?php

namespace Modules\Contact\App\Repositories;

use Illuminate\Support\Str;
use Modules\Contact\App\Models\Contact;

class ContactRepository
{

    public function create($request)
    {
        $data = Contact::create([
            'company_id' => $request['company_id'],
            'name' => $request['name'],
            'code' => Str::upper(Str::random(5)),
            'phone' => $request['phone'],
            'email' => $request['email'],
            'login_email' => $request['email'],
            'login_password' => $request['login_password'],
            'is_active' => $request['is_active'] ?? false,
            'is_customer' => $request['is_customer'] ?? false,
            'verification_code' => $request['verification_code'] ?? null,
        ]);

        return $data;
    }

    public function update($request)
    {
        $data = Contact::find($request['id'])->update([
            'verification_code' => $request['verification_code'] ?? null,
        ]);

        return $data;
    }

    public function verify($request)
    {
        $contact = Contact::where([
            "verification_code" => $request['verification_code'],
            "is_customer" => false,
            "is_active" => false
        ])
        ->first();

        $contact->update([
            "verification_code" => null,
            "is_active" => true,
            "is_customer" => true
        ]);

        return $contact;
    }

    public function activate($request)
    {
        $email = $request['email'];
        $data = Contact::where([
            'email' => $email,
            'company_id' => $request['company_id'],
            'is_active' => false,
            'is_customer' => false
        ])
        ->first();

        $data->update([
            'is_active' => true,
            'is_customer' => true
        ]);

        return $data;
    }
}
