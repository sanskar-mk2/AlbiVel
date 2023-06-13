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
    # sorted by Utc column, get the first 100 rows
    $albions = Albion::orderBy('Utc', 'desc')->take(100)->get();
    # get all unique ItemTypeId
    $human_names = AlbionItem::whereIn('machine_name', $albions->pluck('ItemTypeId')->unique())->get();
    #map human names to ItemTypeId
    $albions->map(function ($albion) use ($human_names) {
        $albion->human_name = $human_names->where('machine_name', $albion->ItemTypeId)->first()->human_name ?? $albion->ItemTypeId;
        return $albion;
    });
    $data = compact('albions');
    return view('albion')->with($data);
});

Route::get('/pbt', function () {

    # get all unique ItemTypeId
    $items = Albion::pluck('ItemTypeId')->unique();

    # find the lowest UnitPriceSilver for AuctionType = offer for each ItemTypeId And Store along with Location and Utc
    $lowest_offer = Albion::where('AuctionType', 'offer')
        // ->where('Location', '!=', 'Caerleon')
        ->orderBy('UnitPriceSilver', 'asc')->get()->unique('ItemTypeId');

    $highest_request = Albion::where('AuctionType', 'request')
        // ->where('Location', '!=', 'Caerleon')
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

    # remove if from and to location are same
    foreach ($pbt as $key => $value) {
        if ($value['from'] == $value['to']) {
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

    # get rid of negative profits
    foreach ($pbt as $key => $value) {
        if ($value['profit'] < 0) {
            unset($pbt[$key]);
        }
    }

    # unique froms
    $froms = array_unique(array_column($pbt, 'from'));
    # unique tos
    $tos = array_unique(array_column($pbt, 'to'));

    # if from and/or to querystring is set, filter pbt
    if (request()->query('from')) {
        $pbt = array_filter($pbt, function ($pbt) {
            return $pbt['from'] == request()->query('from');
        });
    }

    if (request()->query('to')) {
        $pbt = array_filter($pbt, function ($pbt) {
            return $pbt['to'] == request()->query('to');
        });
    }

    $from_s = request()->query('from') ?? '';
    $to_s = request()->query('to') ?? '';
    $flat = request()->query('flat') ?? '';

    return view('pbt')->with(compact('pbt', 'froms', 'tos', 'from_s', 'to_s', 'flat'));
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
