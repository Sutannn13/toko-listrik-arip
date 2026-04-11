<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SettingController extends Controller
{
    private array $groups = ['store', 'general', 'bank', 'hours', 'notifications'];

    public function index(): View
    {
        $settings = Setting::orderBy('group')->orderBy('id')->get()->keyBy('key');
        return view('admin.settings.index', compact('settings'));
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->except(['_token', '_method']);

        foreach ($data as $key => $value) {
            $setting = Setting::where('key', $key)->first();
            if (! $setting) {
                continue;
            }

            if ($setting->type === 'boolean') {
                // Checkboxes not submitted = off
                $value = '1';
            }

            $setting->update(['value' => (string) $value]);
        }

        // Handle unchecked boolean fields (checkboxes that weren't submitted)
        $booleanKeys = Setting::where('type', 'boolean')->pluck('key');
        foreach ($booleanKeys as $boolKey) {
            if (! array_key_exists($boolKey, $data)) {
                Setting::where('key', $boolKey)->update(['value' => '0']);
            }
        }

        return redirect()->route('admin.settings.index')
            ->with('success', 'Pengaturan sistem berhasil disimpan.');
    }
}
