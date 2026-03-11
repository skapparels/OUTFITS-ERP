<?php

namespace App\Controllers;

use App\Models\SystemSetting;
use Illuminate\Http\Request;

class SystemSettingController
{
    public function index(Request $request)
    {
        $query = SystemSetting::query();
        if ($request->filled('group')) {
            $query->where('group_name', $request->string('group'));
        }

        return $query->paginate($request->integer('per_page', 50));
    }

    public function upsert(Request $request)
    {
        $data = $request->validate([
            'key' => ['required', 'string', 'max:190'],
            'value' => ['nullable', 'string'],
            'group_name' => ['nullable', 'string', 'max:120'],
        ]);

        return SystemSetting::query()->updateOrCreate(
            ['key' => $data['key']],
            ['value' => $data['value'] ?? null, 'group_name' => $data['group_name'] ?? 'general']
        );
    }
}
