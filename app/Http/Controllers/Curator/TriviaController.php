<?php

namespace App\Http\Controllers\Curator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Carbon\Carbon;

class TriviaController extends Controller
{
    protected $firebaseService;

    public function __construct(FirebaseService $firebaseService)
    {
        $this->firebaseService = $firebaseService;
    }

    /**
     * Show all trivia
     */
    public function all()
    {
        $triviaDocs = $this->firebaseService->getAllTrivia();
        $landmarkDocs = $this->firebaseService->getAllLandmarks();

        // Map landmarks for quick lookup
        $landmarkMap = [];
        $landmarkList = [];
        foreach ($landmarkDocs as $landmarkDoc) {
            if ($landmarkDoc->exists()) {
                $landmarkId   = $landmarkDoc->id();
                $landmarkName = $landmarkDoc['name'] ?? 'Unnamed';

                $landmarkList[] = [
                    'id'   => $landmarkId,
                    'name' => $landmarkName,
                ];

                $landmarkMap[$landmarkId] = $landmarkName;
            }
        }

        // Build trivia list
        $allTrivia = [];
        foreach ($triviaDocs as $doc) {
            if (!$doc->exists()) continue;

            $data = $doc->data();
            $landmarkId = $data['landmark_id'] ?? '';
            $landmarkName = $landmarkMap[$landmarkId] ?? 'Unknown Landmark';

            $allTrivia[] = [
                'trivia_id'      => $doc->id(),
                'landmark_id'    => $landmarkId,
                'landmark_name'  => $landmarkName,
                'question'       => $data['question'] ?? '',
                'choices'        => $data['choices'] ?? [],
                'correct_answer' => $data['correct_answer'] ?? '',
                'created_at'     => $data['created_at'] ?? '',
                'updated_at'     => $data['updated_at'] ?? '',
            ];
        }

        return view('curators.trivia.all', compact('allTrivia', 'landmarkList'));
    }

    /**
     * Store new trivia
     */
    public function store(Request $request)
    {
        $request->validate([
            'landmark_id'    => 'required|string',
            'question'       => 'required|string',
            'choices'        => 'required|array|min:2',
            'correct_answer' => 'required|string',
        ]);

        $this->firebaseService->addTrivia([
            'landmark_id'    => $request->landmark_id,
            'question'       => $request->question,
            'choices'        => array_values(array_filter($request->choices)),
            'correct_answer' => $request->correct_answer,
        ]);

        return redirect()->back()->with('success', 'Trivia added successfully!');
    }

    /**
     * Update trivia
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            'question'       => 'required|string',
            'choices'        => 'required|array|min:2',
            'correct_answer' => 'required|string',
        ]);

        $this->firebaseService->updateTrivia($id, [
            'question'       => $request->question,
            'choices'        => array_values(array_filter($request->choices)),
            'correct_answer' => $request->correct_answer,
        ]);

        return redirect()->back()->with('success', 'Trivia updated successfully!');
    }

    /**
     * Delete trivia
     */
    public function destroy($id)
    {
        $this->firebaseService->deleteTrivia($id);

        return redirect()->back()->with('success', 'Trivia deleted successfully!');
    }
}
