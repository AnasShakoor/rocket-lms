<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\BnplProvider;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BnplProviderController extends Controller
{
    public function index()
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }

        $providers = BnplProvider::latest()->paginate(20);
        return view('admin.bnpl-providers.index', compact('providers'));
    }

    public function create()
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }

        return view('admin.bnpl-providers.create');
    }

    public function store(Request $request)
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'logo_path' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'fee_percentage' => 'required|numeric|min:0|max:100',
            'installment_count' => 'required|integer|min:1|max:60',
            'is_active' => 'required|boolean',
            'surcharge_percentage' => 'nullable|numeric|min:0|max:100',
            'bnpl_fee' => 'nullable|numeric|min:0',
            'fee_description' => 'nullable|string|max:500',
            'public_api_key' => 'nullable|string|max:255',
            'secret_api_key' => 'nullable|string|max:255',
            'merchant_code' => 'nullable|string|max:255',
            'merchant_id' => 'nullable|string|max:255',
            'app_id' => 'nullable|string|max:255',
            'app_secret_key' => 'nullable|string|max:255',
            'widget_access_key' => 'nullable|string|max:255',
            'config' => 'nullable|string'
        ]);

        // Parse config JSON if provided
        if (!empty($validated['config'])) {
            try {
                $validated['config'] = json_decode($validated['config'], true);
            } catch (\Exception $e) {
                $validated['config'] = [];
            }
        }

        if ($request->hasFile('logo_path')) {
            $validated['logo_path'] = $request->file('logo_path')->store('bnpl-logos', 'public');
        }

        BnplProvider::create($validated);

        return redirect()->route('admin.bnpl-providers.index')
            ->with('success', 'BNPL Provider created successfully.');
    }

    public function edit(BnplProvider $provider)
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }

        return view('admin.bnpl-providers.edit', compact('provider'));
    }

    public function update(Request $request, BnplProvider $provider)
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'logo_path' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'fee_percentage' => 'required|numeric|min:0|max:100',
            'installment_count' => 'required|integer|min:1|max:60',
            'is_active' => 'required|boolean',
            'surcharge_percentage' => 'nullable|numeric|min:0|max:100',
            'bnpl_fee' => 'nullable|numeric|min:0',
            'fee_description' => 'nullable|string|max:500',
            'public_api_key' => 'nullable|string|max:255',
            'secret_api_key' => 'nullable|string|max:255',
            'merchant_code' => 'nullable|string|max:255',
            'merchant_id' => 'nullable|string|max:255',
            'app_id' => 'nullable|string|max:255',
            'app_secret_key' => 'nullable|string|max:255',
            'widget_access_key' => 'nullable|string|max:255',
            'config' => 'nullable|string'
        ]);

        // Parse config JSON if provided
        if (!empty($validated['config'])) {
            try {
                $validated['config'] = json_decode($validated['config'], true);
            } catch (\Exception $e) {
                $validated['config'] = [];
            }
        }

        if ($request->hasFile('logo_path')) {
            // Delete old logo
            if ($provider->logo_path && Storage::disk('public')->exists($provider->logo_path)) {
                Storage::disk('public')->delete($provider->logo_path);
            }
            $validated['logo_path'] = $request->file('logo_path')->store('bnpl-logos', 'public');
        }

        $provider->update($validated);

        return redirect()->route('admin.bnpl-providers.index')
            ->with('success', 'BNPL Provider updated successfully.');
    }

    public function destroy(BnplProvider $provider)
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }

        // Delete logo
        if ($provider->logo_path && Storage::disk('public')->exists($provider->logo_path)) {
            Storage::disk('public')->delete($provider->logo_path);
        }

        $provider->delete();

        return redirect()->route('admin.bnpl-providers.index')
            ->with('success', 'BNPL Provider deleted successfully.');
    }

    /**
     * Get Tabby configuration status
     */
    public function tabbyStatus()
    {
        if (!auth()->check() || auth()->user()->role_name !== 'admin') {
            abort(403, 'This action is unauthorized.');
        }

        $tabbyService = new \App\Services\TabbyService();
        $configStatus = $tabbyService->getConfigurationStatus();

        return response()->json($configStatus);
    }
}
