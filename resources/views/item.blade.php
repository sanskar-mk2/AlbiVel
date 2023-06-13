<x-guest-layout>
    <table class="table">
        <caption>{{ $albions[0]->getHumanName() . ' ' . $albions[0]->Tier . '.' . $albions[0]->EnchantmentLevel; }}</caption>
        <thead>
            <tr>
                <th>City</th>
                <th>Sell min Quality</th>
                <th>Sell min Price</th>
                <th>Sell min Total</th>
                <th>Sell min Count</th>
                <th>SMP time</th>
                <th>Buy max Quality</th>
                <th>Buy max Price</th>
                <th>Buy max Total</th>
                <th>Buy max Count</th>
                <th>BMP time</th>
            </tr>
        </thead>
        <tbody>
            @foreach($cities as $cityname => $city)
                <tr>
                    <td class="border border-black">{{ $cityname }}</td>
                    <td class="border border-black">{{ $city['min_offer_quality'] ?? '-' }}</td>
                    <td class="border border-black" style="background-color: {{ $city['lowest_offer'] ?? 'white' }}">
                        {{ isset($city['min_offer']) ? number_format($city['min_offer']) : '-' }}
                    </td>
                    <td class="border border-black">{{ isset($city['min_offer_total']) ? number_format($city['min_offer_total']) : '-' }}</td>
                    <td class="border border-black">{{ $city['min_offer_count'] ?? '-' }}</td>
                    <td class="border border-black">{{ $city['min_offer_time'] ?? '-' }}</td>
                    <td class="border border-black">{{ $city['max_request_quality'] ?? '-' }}</td>
                    <td class="border border-black" style="background-color: {{ $city['highest_request'] ?? 'white' }}">
                        {{ isset($city['max_request']) ? number_format($city['max_request']) : '-' }}
                    </td>
                    <td class="border border-black">{{ isset($city['max_request_total']) ? number_format($city['max_request_total']) : '-' }}</td>
                    <td class="border border-black">{{ $city['max_request_count'] ?? '-' }}</td>
                    <td class="border border-black">{{ $city['max_request_time'] ?? '-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</x-guest-layout>
