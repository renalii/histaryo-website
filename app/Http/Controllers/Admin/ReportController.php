<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\FirebaseService;
use Illuminate\Http\Request;
use PDF;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\ArrayExport;
use Carbon\Carbon;

class ReportController extends Controller
{
    protected $firestore;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firestore = $firebaseService->firestore();
    }

    public function index()
    {
        return view('admin.reports');
    }

    public function export(Request $request)
    {
        $type   = $request->input('type', 'users');
        $format = $request->input('format', 'pdf');
        $from   = $request->input('from');
        $to     = $request->input('to');

        $data = $this->getReportData($type, $from, $to);

        $fileName = ucfirst($type) . '_Report_' . now()->format('Y-m-d_His');

        if ($format === 'excel') {
            return Excel::download(new ArrayExport($data), $fileName . '.xlsx');
        } else {
            $pdf = PDF::loadView('admin.reports_pdf', [
                'data' => $data,
                'type' => $type,
                'from' => $from,
                'to'   => $to,
            ]);
            return $pdf->download($fileName . '.pdf');
        }
    }

    private function getReportData($type, $from, $to)
    {
        $collection = $this->firestore->collection($type === 'visits' ? 'logs' : $type)->documents();
        $results = [];

        foreach ($collection as $doc) {
            $data = $doc->data();

            
            if ($from || $to) {
                $timestamp = $data['timestamp'] ?? $data['created_at'] ?? null;
                if ($timestamp) {
                    $date = Carbon::parse($timestamp);

                    if ($from && $date->lt(Carbon::parse($from))) {
                        continue;
                    }
                    if ($to && $date->gt(Carbon::parse($to))) {
                        continue;
                    }
                }
            }

            
            switch ($type) {
                case 'landmarks':
                    $results[] = [
                        'Name'        => $data['name'] ?? '',
                        'Description' => $data['description'] ?? '',
                        'Latitude'    => $data['latitude'] ?? $data['lat'] ?? '',
                        'Longitude'   => $data['longitude'] ?? $data['long'] ?? $data['longti'] ?? '',
                    ];
                    break;

                case 'users':
                    $results[] = [
                        'User ID' => $doc->id(),
                        'Email'   => $data['email'] ?? '',
                        'Role'    => $data['role'] ?? 'visitor',
                    ];
                    break;

                case 'visits':
                    $results[] = [
                        'User ID'   => $data['user_id'] ?? '',
                        'Landmark'  => $data['landmark'] ?? '',
                        'Visited At'=> $data['timestamp'] ?? '',
                    ];
                    break;

                case 'trivia':
                    $results[] = [
                        'Question'   => $data['question'] ?? '',
                        'Answer'     => $data['answer'] ?? '',
                        'Created At' => $data['created_at'] ?? '',
                    ];
                    break;

                default:
                    $results[] = $data; 
            }
        }

        return $results;
    }
}
