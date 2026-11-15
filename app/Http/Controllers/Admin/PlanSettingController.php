<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlanSetting;
use Illuminate\Http\Request;

class PlanSettingController extends Controller
{
    public function index()
    {
        $plans = PlanSetting::orderBy('level')->get();
        return view('admin.plan-settings', compact('plans'));
    }

    public function update(Request $request, PlanSetting $planSetting)
    {
        $data = $request->validate([
            'name'          => 'required|string|max:60',
            'icon'          => 'nullable|string|max:16',
            'badge'         => 'nullable|string|max:40',
            'description'   => 'nullable|string|max:255',
            'min_amount'    => 'required|numeric|min:1',
            'max_amount'    => 'nullable|numeric|gt:min_amount',
            'rate_min'      => 'required|numeric|min:0.01|max:100',
            'rate_max'      => 'required|numeric|gte:rate_min|max:100',
            'duration_days' => 'required|integer|min:1|max:3650',
            'max_cycles'    => 'required|integer|min:1|max:100',
            'color'         => ['required', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ]);
        $data['max_amount'] = $request->filled('max_amount') ? $data['max_amount'] : null; // blank = no limit
        $data['is_active']  = $request->boolean('is_active');

        $planSetting->update($data);

        return redirect()->route('admin.plan-settings')
            ->with('success', "{$planSetting->name} updated. Changes apply to new requests and assignments only.");
    }
}
