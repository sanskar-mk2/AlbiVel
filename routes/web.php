<?php

use App\Http\Controllers\ProfileController;
use App\Models\Albion;
use App\Models\AlbionItem;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    # get search
    $search = request()->query('search');
    $exploded = explode(' ', $search);
    if (count($exploded) > 1) {
        $item = $exploded[0];
        $quality = $exploded[1];
        $tier_enchantment = explode('.', $quality);
        $tier = $tier_enchantment[0];
        $enchantment = $tier_enchantment[1] ?? null;
    } else if (count($exploded) == 1) {
        $item = $exploded[0];
        $tier = null;
        $enchantment = null;
    } else {
        $item = null;
        $tier = null;
        $enchantment = null;
    }

    # sorted by Utc column, get the first 100 rows
    $albions = Albion::orderBy('Utc', 'desc')
        ->join('albion_items', 'albion_table.ItemTypeId', '=', 'albion_items.machine_name')
        ->when($item, function ($query, $item) {
            return $query->where('albion_items.human_name', 'like', '%' . $item . '%');
        })->when($tier, function ($query, $tier) {
            return $query->where('albion_table.Tier', $tier);
        })->when($enchantment, function ($query, $enchantment) {
            return $query->where('albion_table.EnchantmentLevel', $enchantment);
        })
        ->take(100)
        ->get();

    $data = compact('albions', 'search');
    return view('albion')->with($data);
})->name('albion');

Route::get('/pbt', function () {
    # get froms and tos city arrays from request
    $froms = request()->query('froms') ?? [];
    $tos = request()->query('tos') ?? [];

    # get all unique ItemTypeId
    $items = Albion::pluck('ItemTypeId')->unique();

    $cities = Albion::pluck('Location')->unique();

    # find the lowest UnitPriceSilver for AuctionType = offer for each ItemTypeId And Store along with Location and Utc
    $lowest_offer = Albion::where('AuctionType', 'offer')
        ->whereIn('Location', $froms)
        ->orderBy('UnitPriceSilver', 'asc')->get()->unique('ItemTypeId');

    $highest_request = Albion::where('AuctionType', 'request')
        ->whereIn('Location', $tos)
        ->orderBy('UnitPriceSilver', 'desc')->get()->unique('ItemTypeId');

    # calculate profit before tax for each item, store along with from and to location, utc
    $pbt = [];
    $human_names = AlbionItem::whereIn('machine_name', $items)->get();
    foreach ($lowest_offer as $offer) {
        foreach ($highest_request as $request) {
            if ($offer->ItemTypeId == $request->ItemTypeId) {
                $pbt[$offer->ItemTypeId]['ItemTypeId'] = $offer->ItemTypeId;
                $pbt[$offer->ItemTypeId]['item'] = ($human_names->where('machine_name', $offer->ItemTypeId)->first()->human_name ?? $offer->ItemTypeId) . ' ' . $offer->Tier . '.' . $offer->EnchantmentLevel;
                $pbt[$offer->ItemTypeId]['profit'] = $request->UnitPriceSilver - $offer->UnitPriceSilver;
                $pbt[$offer->ItemTypeId]['profit_percent'] = floatval(number_format(($pbt[$offer->ItemTypeId]['profit'] / $offer->UnitPriceSilver) * 100, 2));
                $pbt[$offer->ItemTypeId]['from'] = $offer->Location;
                $pbt[$offer->ItemTypeId]['from_silver'] = $offer->UnitPriceSilver;
                $pbt[$offer->ItemTypeId]['to'] = $request->Location;
                $pbt[$offer->ItemTypeId]['to_silver'] = $request->UnitPriceSilver;
                # oldest utc
                $pbt[$offer->ItemTypeId]['utc'] = $offer->Utc < $request->Utc ? $offer->getHumanUtc() : $request->getHumanUtc();
            }
        }
    }

    # get rid of negative profits
    foreach ($pbt as $key => $value) {
        if ($value['profit'] < 0) {
            unset($pbt[$key]);
        }
    }

    # if querystring flat=1, sort by profit, most profitable first
    if (request()->query('flat') == "on") {
        usort($pbt, function ($a, $b) {
            return $b['profit'] <=> $a['profit'];
        });
    } else {
        # sort by profit_percent, most profitable first
        usort($pbt, function ($a, $b) {
            return $b['profit_percent'] <=> $a['profit_percent'];
        });
    }

    $flat = request()->query('flat') ?? '';

    return view('pbt')->with(compact('pbt', 'flat', 'cities', 'froms', 'tos'));
})->name('pbt');


Route::get('item/{id}', function ($id) {
    $albions = Albion::where('ItemTypeId', $id)->get();
    # get unique cities
    $cities = $albions->pluck('Location')->unique();
    # make an PHP array with cities as keys
    $cities = array_combine($cities->toArray(), array_fill(0, count($cities), []));

    $lowest_offer = 99999999999999;
    $highest_request = 0;
    $lowest_offer_city = '';
    $highest_request_city = '';

    foreach ($albions as $albion) {
        if ($albion->AuctionType == 'offer') {
            $cities[$albion->Location]['min_offer'] = $albion->UnitPriceSilver;
            $cities[$albion->Location]['min_offer_total'] = $albion->TotalPriceSilver;
            $cities[$albion->Location]['min_offer_count'] = $albion->Amount;
            $cities[$albion->Location]['min_offer_time'] = $albion->getHumanUtc();
            $cities[$albion->Location]['min_offer_quality'] = $albion->getQualityName();
            if ($albion->UnitPriceSilver && $albion->UnitPriceSilver < $lowest_offer) {
                $lowest_offer = $albion->UnitPriceSilver;
                $lowest_offer_city = $albion->Location;
            }
        } else {
            $cities[$albion->Location]['max_request'] = $albion->UnitPriceSilver;
            $cities[$albion->Location]['max_request_total'] = $albion->TotalPriceSilver;
            $cities[$albion->Location]['max_request_count'] = $albion->Amount;
            $cities[$albion->Location]['max_request_time'] = $albion->getHumanUtc();
            $cities[$albion->Location]['max_request_quality'] = $albion->getQualityName();
            if ($albion->UnitPriceSilver > $highest_request) {
                $highest_request = $albion->UnitPriceSilver;
                $highest_request_city = $albion->Location;
            }
        }
    }

    if ($lowest_offer_city) {
        $cities[$lowest_offer_city]['lowest_offer'] = 'lime';
    }
    if ($highest_request_city) {
        $cities[$highest_request_city]['highest_request'] = 'lime';
    }

    $data = compact('albions', 'cities');
    return view('item')->with($data);
})->name('item');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__ . '/auth.php';
