<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Translation Checker Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Translation Checker Dashboard</h1>
        
        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 lg:grid-cols-5 gap-4 mb-8" id="stats-cards">
            @foreach($stats as $locale => $data)
                <div class="bg-white rounded-lg shadow p-4">
                    <h3 class="text-lg font-semibold mb-2">{{ strtoupper($locale) }}</h3>
                    <div class="text-2xl font-bold {{ $data['completion'] >= 80 ? 'text-green-600' : ($data['completion'] >= 50 ? 'text-yellow-600' : 'text-red-600') }}">
                        {{ $data['completion'] }}%
                    </div>
                    <div class="text-sm text-gray-600 mt-2">
                        Missing: {{ $data['missing_count'] }} / {{ $data['total_keys'] }}
                    </div>
                    <div class="mt-3">
                        <button onclick="checkLocale('{{ $locale }}')" class="text-blue-500 hover:text-blue-700 text-sm">
                            Check Details →
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Filters -->
        <div class="bg-white rounded-lg shadow p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Filter Translations</h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Select Locale</label>
                    <select id="locale" class="w-full border rounded-lg px-3 py-2">
                        <option value="">All Locales</option>
                        @foreach($locales as $locale)
                            <option value="{{ $locale }}">{{ strtoupper($locale) }}</option>
                        @endforeach
                    </select>
                </div>
                
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Search</label>
                    <input type="text" id="search" placeholder="Search translations..." class="w-full border rounded-lg px-3 py-2">
                </div>
                
                <div class="flex items-end">
                    <button onclick="checkTranslations()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg">
                        Check Now
                    </button>
                    
                    <button onclick="exportTranslations()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg ml-2">
                        Export CSV
                    </button>
                    
                    <button onclick="clearCache()" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-lg ml-2">
                        Clear Cache
                    </button>
                </div>
            </div>
        </div>
        
        <!-- Results Table -->
        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="px-6 py-4 border-b">
                <h2 class="text-xl font-semibold">Missing Translations</h2>
            </div>
            <div id="results" class="p-6">
                <p class="text-gray-500 text-center">Select a locale and click "Check Now" to view missing translations</p>
            </div>
        </div>
    </div>
    
    <script>
        function checkTranslations() {
            const locale = document.getElementById('locale').value;
            if (!locale) {
                alert('Please select a locale');
                return;
            }
            
            $.ajax({
                url: "{{ route('translations.check') }}",
                method: 'GET',
                data: {
                    locale: locale,
                    format: 'json'
                },
                success: function(response) {
                    displayResults(response);
                },
                error: function(xhr) {
                    alert('Error: ' + xhr.responseJSON?.message || 'Something went wrong');
                }
            });
        }
        
        function checkLocale(locale) {
            document.getElementById('locale').value = locale;
            checkTranslations();
        }
        
        function displayResults(data) {
            const resultsDiv = document.getElementById('results');
            
            if (data.missing_count === 0) {
                resultsDiv.innerHTML = '<div class="text-green-600 text-center p-4">✓ All translations are complete for ' + data.locale.toUpperCase() + '!</div>';
                return;
            }
            
            let html = `
                <div class="mb-4 p-3 bg-yellow-50 border border-yellow-200 rounded">
                    <strong>Locale:</strong> ${data.locale.toUpperCase()} | 
                    <strong>Completion:</strong> ${data.completion}% | 
                    <strong>Missing:</strong> ${data.missing_count}
                </div>
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Key</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Suggested Value</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
            `;
            
            data.missing_translations.forEach(item => {
                html += `
                    <tr>
                        <td class="px-6 py-4 text-sm text-gray-900">${item.key}</td>
                        <td class="px-6 py-4 text-sm text-gray-600">${item.suggested_value}</td>
                        <td class="px-6 py-4">
                            <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800">
                                Missing
                            </span>
                        </td>
                    </tr>
                `;
            });
            
            html += `
                    </tbody>
                </table>
            `;
            
            resultsDiv.innerHTML = html;
        }
        
        function exportTranslations() {
            const locale = document.getElementById('locale').value;
            if (!locale) {
                alert('Please select a locale first');
                return;
            }
            
            window.location.href = "{{ route('translations.export') }}?locale=" + locale;
        }
        
        function clearCache() {
            $.ajax({
                url: "{{ route('translations.clear-cache') }}",
                method: 'POST',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    alert('Cache cleared successfully!');
                    location.reload();
                },
                error: function() {
                    alert('Error clearing cache');
                }
            });
        }
        
        // Search functionality
        $('#search').on('keyup', function() {
            const value = $(this).val().toLowerCase();
            $('#results table tbody tr').filter(function() {
                $(this).toggle($(this).text().toLowerCase().indexOf(value) > -1);
            });
        });
    </script>
</body>
</html>