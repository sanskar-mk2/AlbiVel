<x-guest-layout>
    {{-- Search --}}
    <form method="GET" action="{{ route('albion') }}">
        <input type="search" name="search" id="search" value="{{ $search }}" />
        <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Submit
        </button>
    </form>
    <table class="table">
        <thead>
            <tr>
                <th>UnitPriceSilver</th>
                <th>TotalPriceSilver</th>
                <th>Amount</th>
                <th>Tier</th>
                <th>AuctionType</th>
                <th>ItemTypeId</th>
                <th>ItemGroupTypeId</th>
                <th>EnchantmentLevel</th>
                <th>QualityLevel</th>
                <th>Location</th>
                <th>Utc</th>
            </tr>
        </thead>
        <tbody>
            @foreach($albions as $albion)
                <tr>
                    <td class="border border-black">{{ $albion->UnitPriceSilver }}</td>
                    <td class="border border-black">{{ $albion->TotalPriceSilver }}</td>
                    <td class="border border-black">{{ $albion->Amount }}</td>
                    <td class="border border-black">{{ $albion->Tier }}</td>
                    <td class="border border-black">{{ $albion->AuctionType }}</td>
                    <td class="border border-black">
                        <a class="underline text-blue-500" href="{{ route('item', $albion->ItemTypeId) }}">
                            {{ $albion->human_name }}
                        </a>
                    </td>
                    <td class="border border-black">{{ $albion->ItemGroupTypeId }}</td>
                    <td class="border border-black">{{ $albion->EnchantmentLevel }}</td>
                    <td class="border border-black">{{ $albion->QualityLevel }}</td>
                    <td class="border border-black">{{ $albion->Location }}</td>
                    <td class="border border-black">{{ $albion->getHumanUtc() }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</x-guest-layout>
