<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Translation Scan History</title>

    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100">

    <div class="container mx-auto px-4 py-8">

        <div class="flex justify-between items-center mb-6">

            <h1 class="text-3xl font-bold">
                📊 Translation Scan History
            </h1>

            <a href="/"
                class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                ← Back Dashboard
            </a>

        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">

            <div class="px-6 py-4 border-b">

                <h2 class="text-xl font-semibold">
                    Previous Scan Results
                </h2>

            </div>

            @if($history->count())

            <table class="min-w-full">

                <thead class="bg-gray-50">

                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-semibold">
                            Locale
                        </th>

                        <th class="px-6 py-3 text-left text-sm font-semibold">
                            Missing
                        </th>

                        <th class="px-6 py-3 text-left text-sm font-semibold">
                            Completion
                        </th>

                        <th class="px-6 py-3 text-left text-sm font-semibold">
                            Scanned At
                        </th>

                        <th class="px-6 py-3 text-left text-sm font-semibold">
                            Status
                        </th>
                    </tr>

                </thead>

                <tbody>

                    @foreach($history as $item)

                    <tr class="border-b hover:bg-gray-50">

                        <td class="px-6 py-4 font-medium">
                            {{ strtoupper($item->locale) }}
                        </td>

                        <td class="px-6 py-4">
                            {{ $item->missing_count }}
                        </td>

                        <td class="px-6 py-4">
                            {{ $item->completion }}%
                        </td>

                        <td class="px-6 py-4 text-gray-600">
                            {{ $item->scanned_at }}
                        </td>

                        <td class="px-6 py-4">

                            @if($item->completion >= 80)

                            <span class="bg-green-100 text-green-700 px-3 py-1 rounded-full text-xs">
                                Excellent
                            </span>

                            @elseif($item->completion >= 50)

                            <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-xs">
                                Average
                            </span>

                            @else

                            <span class="bg-red-100 text-red-700 px-3 py-1 rounded-full text-xs">
                                Needs Work
                            </span>

                            @endif

                        </td>

                    </tr>

                    @endforeach

                </tbody>

            </table>

            <div class="p-6">
                {{ $history->links() }}
            </div>

            @else

            <div class="text-center p-10 text-gray-500">
                No scan history found
            </div>

            @endif

        </div>

    </div>

</body>

</html>