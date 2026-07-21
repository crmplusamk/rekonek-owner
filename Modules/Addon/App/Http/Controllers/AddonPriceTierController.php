<?php

namespace Modules\Addon\App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Modules\Addon\App\Http\Requests\StoreAddonPriceTierRequest;
use Modules\Addon\App\Http\Requests\UpdateAddonPriceTierRequest;
use Modules\Addon\App\Services\AddonTierCrudService;
use Modules\Addon\App\Services\AddonTierPricingService;

/**
 * CRUD Aturan Diskon (Price Tier) per addon. Halaman kelola satu addon
 * (Modules/Addon/resources/views/tier.blade.php) — addon module tidak punya
 * halaman edit/show terpisah, CRUD addon-nya sendiri dilakukan lewat modal di index.
 */
class AddonPriceTierController extends Controller
{
    public function __construct(
        private AddonTierCrudService $service,
        private AddonTierPricingService $pricing,
    ) {}

    public function index(string $addon)
    {
        $addonModel = $this->service->getAddonOrFail($addon);
        $tiers = $this->service->allFor($addon);
        $pricing = $this->pricing;

        return view('addon::tier', compact('addonModel', 'tiers', 'pricing'));
    }

    public function store(StoreAddonPriceTierRequest $request, string $addon)
    {
        try {
            $this->service->create($request->validated());
            notify()->success('Berhasil menambah aturan diskon');
        } catch (QueryException $e) {
            notify()->error($this->isUniqueViolation($e)
                ? 'Aturan diskon dengan minimal kuantitas tersebut sudah ada untuk addon ini.'
                : 'Terjadi kesalahan. ' . $e->getMessage());
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan. ' . $e->getMessage());
        }

        return to_route('addon.tier.index', $addon);
    }

    public function update(UpdateAddonPriceTierRequest $request, string $id)
    {
        $addonId = $this->service->find($id)->addon_id;

        try {
            $this->service->update($request->validated(), $id);
            notify()->success('Berhasil mengubah aturan diskon');
        } catch (QueryException $e) {
            notify()->error($this->isUniqueViolation($e)
                ? 'Aturan diskon dengan minimal kuantitas tersebut sudah ada untuk addon ini.'
                : 'Terjadi kesalahan. ' . $e->getMessage());
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan. ' . $e->getMessage());
        }

        return to_route('addon.tier.index', $addonId);
    }

    public function status(string $id)
    {
        try {
            $tier = $this->service->toggleStatus($id);
            notify()->success('Berhasil mengubah status aturan diskon');
            return to_route('addon.tier.index', $tier->addon_id);
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan. ' . $e->getMessage());
            return back();
        }
    }

    public function destroy(string $id)
    {
        $addonId = $this->service->find($id)->addon_id;

        try {
            $this->service->delete($id);
            notify()->success('Berhasil menghapus aturan diskon');
        } catch (\Exception $e) {
            notify()->error('Terjadi kesalahan. ' . $e->getMessage());
        }

        return to_route('addon.tier.index', $addonId);
    }

    private function isUniqueViolation(QueryException $e): bool
    {
        return ($e->errorInfo[0] ?? null) === '23505';
    }
}
