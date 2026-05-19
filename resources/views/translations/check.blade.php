<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Translation Checker</title>

    <style>
        body{
            font-family:Arial,sans-serif;
            max-width:1200px;
            margin:0 auto;
            padding:20px;
            background:#f5f5f5;
        }

        .card{
            background:#fff;
            border-radius:10px;
            padding:20px;
            margin-bottom:20px;
            box-shadow:0 2px 8px rgba(0,0,0,.1);
        }

        .button-group{
            display:flex;
            gap:10px;
            flex-wrap:wrap;
            margin-top:15px;
        }

        select,button{
            padding:10px 14px;
            border:none;
            border-radius:6px;
        }

        button{
            cursor:pointer;
            color:white;
        }

        .blue{background:#2563eb;}
        .green{background:#16a34a;}
        .gray{background:#6b7280;}

        .purple-link{
            background:#9333ea;
            color:white;
            text-decoration:none;
            padding:10px 14px;
            border-radius:6px;
        }

        table{
            width:100%;
            border-collapse:collapse;
            margin-top:20px;
        }

        th,td{
            padding:12px;
            border-bottom:1px solid #ddd;
            text-align:left;
        }

        th{
            background:#f8f9fa;
        }

        .missing{
            color:red;
            font-weight:bold;
        }

        .complete{
            color:green;
            font-weight:bold;
        }

        .progress-bar{
            width:100%;
            background:#ddd;
            border-radius:20px;
            overflow:hidden;
            margin:15px 0;
        }

        .progress{
            background:#16a34a;
            height:25px;
            color:#fff;
            text-align:center;
            line-height:25px;
        }
    </style>
</head>

<body>

<div class="card">
    <h1>🌍 Translation Missing Checker</h1>

    <label>Select Language:</label>

    <select id="locale">
        <option value="gu">Gujarati</option>
        <option value="hi">Hindi</option>
        <option value="fr">French</option>
        <option value="es">Spanish</option>
        <option value="de">German</option>
        <option value="zh">Chinese</option>
        <option value="ja">Japanese</option>
        <option value="ko">Korean</option>
        <option value="ru">Russian</option>
    </select>

    <div class="button-group">
        <button class="blue" onclick="checkTranslations()">🔍 Check Missing</button>

        <button class="green" onclick="exportCSV()">📥 Export CSV</button>

        <button class="gray" onclick="clearCache()">🗑 Clear Cache</button>

        <a href="{{ route('translations.history') }}" class="purple-link">
            📊 View History
        </a>
    </div>
</div>

<div id="results" class="card" style="display:none;">
    <h2>Results</h2>
    <div id="content"></div>
</div>

<script>

function checkTranslations(){

    const locale=document.getElementById('locale').value;

    fetch(`/check-translations?locale=${locale}&format=json`)
    .then(response=>response.json())
    .then(data=>displayResults(data))
    .catch(error=>alert('Error: '+error));

}

function displayResults(data){

    const resultsDiv=document.getElementById('results');
    const contentDiv=document.getElementById('content');

    let progressHTML=`
        <div class="progress-bar">
            <div class="progress" style="width:${data.completion}%">
                ${data.completion}% Complete
            </div>
        </div>

        <p><strong>Locale:</strong> ${data.locale.toUpperCase()}</p>
        <p><strong>Missing Count:</strong> ${data.missing_count}</p>
        <p><strong>Completion:</strong> ${data.completion}%</p>
    `;

    if(data.missing_count===0){

        contentDiv.innerHTML=
        progressHTML+
        `<p class="complete">✅ No missing translations found</p>`;

        resultsDiv.style.display='block';

        return;
    }

    let html=`
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

    data.missing_translations.forEach((item,index)=>{

        html+=`
        <tr>
            <td>${index+1}</td>
            <td><strong>${item.key}</strong></td>
            <td>${item.suggested_value}</td>
            <td class="missing">Missing ⚠️</td>
        </tr>
        `;

    });

    html+=`</tbody></table>`;

    contentDiv.innerHTML=html;

    resultsDiv.style.display='block';

}

function exportCSV(){

    const locale=document.getElementById('locale').value;

    window.location.href=`/export-translations?locale=${locale}`;

}

function clearCache(){

    fetch("{{ route('translations.clear-cache') }}",{

        method:'POST',

        headers:{
            "X-CSRF-TOKEN":document.querySelector('meta[name="csrf-token"]').content,
            "Content-Type":"application/json"
        }

    })

    .then(response=>response.json())

    .then(data=>{

        alert(data.message || 'Cache cleared successfully');

        location.reload();

    })

    .catch(()=>{

        alert('Error clearing cache');

    });

}

</script>

</body>
</html>