<!DOCTYPE html>
<html lang="he" dir="rtl">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <!-- Meta, title, CSS, favicons, etc. -->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <title>Worker Hours Report</title>
    <style>
        body {
            font-family: 'DejaVu Sans', sans-serif;
            color: #212529;
            background: #fcfcfc;
            margin: 0;
            padding: 0;
            direction: rtl;     
        }
        
        .container {
            padding: 10px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 10px;
        }
        table, th, td {
            border: 1px solid black;
        }
        th, td {
            padding: 5px;
            text-align: right;
        }
        th {
            background-color: #f2f2f2;
            font-weight: bold;
        }
        .summary-row {
            font-weight: bold;
            background-color: #e0e0e0;
        }
        .page-break {
            page-break-before: always;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 10px;
        }
        th:nth-child(1), td:nth-child(1) {
        width: 15%; /* Width for hours */
        }
        
        th:nth-child(2), td:nth-child(2) {
            width: 30%; /* Width for exit time */
        }
        
        th:nth-child(3), td:nth-child(3) {
            width: 30%; /* Width for entry time */
        }
        
        th:nth-child(4), td:nth-child(4) {
            width: 20%; /* Width for day */
        }
    </style>
    
</head>
<body>
    <div class="container">
        @foreach ($allPdfData as $workerName => $data)    
            <table >
                <thead>
                    <tr>
                        <th>חברה :{{ $data['companyName'] }} </th>
                        <th>מַחלָקָה :{{ $data['department'] }}  </th>
                        <th>שם העובד :{{ $workerName }} </th>
                        <th></th>                  
                    </tr>
                    <tr>                        
                       <th>יְוֹם</th>
                        <th>כניסה</th>
                        <th>יציאה</th>
                        <th>שעות</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $totalHours = 0;
                    @endphp
                    @foreach ($data['dates'] as $date)
                        @php
                            $entry = $data['pdfData'][$date] ?? null;
                            $hours =  $entry && $entry['total_hours'] > 0 ? round($entry['total_hours'], 2) : '';
                            $totalHours += $entry && $entry['total_hours'] > 0 ? $hours : 0;
                        @endphp
                        <tr> 
                            <td>{{ $date }}</td>      
                            <td>{{ $entry['entry_time'] ?? ' ' }}</td>
                            <td>{{ $entry['exit_time'] ?? ' ' }}</td>
                            <td>{{ $hours }}</td>
                          
                           
                           
                        </tr>
                    @endforeach
                    <tr class="summary-row">        
                        
                     <td></td>
                        <td></td>
                        <td><strong>סהכ</strong></td>
                        <td><strong>{{ round($totalHours, 2) }}</strong></td>
                        
                    </tr>
                </tbody>
            </table>
            <div class="page-break"></div>
        @endforeach
    </div>
</body>
</html>
