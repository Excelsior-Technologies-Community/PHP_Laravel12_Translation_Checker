<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Translation Checker</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .card {
            background: white;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        select, button {
            padding: 8px 12px;
            margin: 5px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }
        button {
            background: #007bff;
            color: white;
            cursor: pointer;
            border: none;
        }
        button:hover {
            background: #0056b3;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #f8f9fa;
        }
        .missing {
            color: red;
            font-weight: bold;
        }
        .complete {
            color: green;
            font-weight: bold;
        }
        .progress-bar {
            width: 100%;
            background: #e0e0e0;
            border-radius: 5px;
            overflow: hidden;
            margin: 10px 0;
        }
        .progress {
            background: #28a745;
            height: 20px;
            text-align: center;
            line-height: 20px;
            color: white;
        }
    </style>
</head>
<body>
    <div class="card">
        <h1>🔍 Translation Missing Checker</h1>
        
        <div>
            <label>Select Language: </label>
            <select id="locale">
                <option value="gu">Gujarati (ગુજરાતી)</option>
                <option value="hi">Hindi (हिन्दी)</option>
                <option value="fr">French</option>
                <option value="es">Spanish</option>
                <option value="de">German</option>
                <option value="zh">Chinese</option>
                <option value="ja">Japanese</option>
                <option value="ko">Korean</option>
                <option value="ru">Russian</option>
            </select>
            
            <button onclick="checkTranslations()">🔍 Check Missing</button>
            <button onclick="exportCSV()">📥 Export CSV</button>
        </div>
    </div>
    
    <div id="results" class="card" style="display:none;">
        <h2>Results</h2>
        <div id="content"></div>
    </div>
    
    <script>
        function checkTranslations() {
            const locale = document.getElementById('locale').value;
            
            fetch(`/check-translations?locale=${locale}&format=json`)
                .then(response => response.json())
                .then(data => {
                    displayResults(data);
                })
                .catch(error => {
                    alert('Error: ' + error);
                });
        }
        
        function displayResults(data) {
            const resultsDiv = document.getElementById('results');
            const contentDiv = document.getElementById('content');
            
            // Progress bar
            const progressHTML = `
                <div class="progress-bar">
                    <div class="progress" style="width: ${data.completion}%">
                        ${data.completion}% Complete
                    </div>
                </div>
                <p><strong>Locale:</strong> ${data.locale.toUpperCase()}</p>
                <p><strong>Missing Count:</strong> ${data.missing_count}</p>
                <p><strong>Completion:</strong> ${data.completion}%</p>
            `;
            
            if (data.missing_count === 0) {
                contentDiv.innerHTML = progressHTML + '<p class="complete">✓ All translations are complete! No missing translations found.</p>';
                resultsDiv.style.display = 'block';
                return;
            }
            
            // Table HTML
            let tableHTML = `
                ${progressHTML}
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Key</th>
                            <th>Suggested Value</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
            `;
            
            data.missing_translations.forEach((item, index) => {
                tableHTML += `
                    <tr>
                        <td>${index + 1}</td>
                        <td><strong>${item.key}</strong></td>
                        <td>${item.suggested_value}</td>
                        <td class="missing">Missing ⚠️</td>
                    </tr>
                `;
            });
            
            tableHTML += `
                    </tbody>
                </table>
            `;
            
            contentDiv.innerHTML = tableHTML;
            resultsDiv.style.display = 'block';
        }
        
        function exportCSV() {
            const locale = document.getElementById('locale').value;
            window.location.href = `/export-translations?locale=${locale}`;
        }
    </script>
</body>
</html>