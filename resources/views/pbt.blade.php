<x-guest-layout>
    <form class="flex flex-col gap-2"
        method="GET" action="{{ route('pbt') }}">
        <div class="flex gap-4 items-center">
        <label for="flat">Flat Profit Sort</label>
        <input type="checkbox" name="flat" id="flat" @checked($flat) />
        <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
            Submit
        </button>
        </div>
        <div>
        {{-- Checkbox for froms and tos --}}
        @foreach($cities as $city)
            <input type="checkbox" name="froms[]" value="{{ $city }}" @checked(in_array($city, $froms)) />
            <label for="froms[]">{{ $city }}</label>
        @endforeach
        </div>
        <div>
        @foreach($cities as $city)
            <input type="checkbox" name="tos[]" value="{{ $city }}" @checked(in_array($city, $tos)) />
            <label for="tos[]">{{ $city }}</label>
        @endforeach
        </div>
    </form>
    <table class="table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Profit</th>
                <th>Profit %</th>
                <th>Last Updated</th>
                <th>From</th>
                <th>To</th>
                <th>From Silver</th>
                <th>To Silver</th>
            </tr>
        </thead>
        <tbody>
            @foreach($pbt as  $p)
                <tr>
                    <td class="border border-black">
                        <a class="underline text-blue-500" href="{{ route('item', $p['ItemTypeId']) }}">
                            {{ $p['item'] }}
                        </a>
                    </td>
                    <td class="border border-black">{{ $p['profit'] }}</td>
                    <td class="border border-black">{{ $p['profit_percent'] }}</td>
                    <td class="border border-black">{{ $p['utc'] }}</td>
                    <td class="border border-black">{{ $p['from'] }}</td>
                    <td class="border border-black">{{ $p['to'] }}</td>
                    <td class="border border-black">{{ $p['from_silver'] }}</td>
                    <td class="border border-black">{{ $p['to_silver'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</x-guest-layout>
