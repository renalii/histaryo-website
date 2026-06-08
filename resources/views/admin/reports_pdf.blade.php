<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $type === 'quiz' ? 'Quiz' : ucfirst($type) }} Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f3f4f6; }
        h2 { margin-bottom: 5px; }
    </style>
</head>
<body>
    <h2>{{ $type === 'quiz' ? 'Quiz' : ucfirst($type) }} Report</h2>

    @if($from || $to)
        <p><strong>Period:</strong> {{ $from ?? '—' }} → {{ $to ?? '—' }}</p>
    @endif

    <table>
        <thead>
            @if(count($data) > 0)
                <tr>
                    @foreach(array_keys($data[0]) as $col)
                        <th>{{ $col }}</th>
                    @endforeach
                </tr>
            @endif
        </thead>
        <tbody>
            @forelse($data as $row)
                <tr>
                    @foreach($row as $value)
                        <td>{{ $value }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="10">No data available for this report.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
