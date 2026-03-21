<?php

namespace App\Http\Controllers\Curator;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;

class TriviaController extends Controller
{
    /**
     * Hold the Firebase service instance.
     */
    private FirebaseService $firebase;

    public function __construct(FirebaseService $firebase)
    {
        
        $this->firebase = $firebase;
    }

    
    public function all(Request $request)
    {
        
        $landmarkDocs = $this->firebase->getAllLandmarks();
        $landmarkMap  = [];
        $landmarkList = [];

        foreach ($landmarkDocs as $ld) {
            if (!$ld->exists()) continue;
            $id   = $ld->id();
            $name = $ld['name'] ?? 'Unnamed';
            $landmarkMap[$id] = $name;
            $landmarkList[]   = ['id' => $id, 'name' => $name];
        }

        
        $triviaDocs = $this->firebase->getAllTrivia();

        $allTrivia = [];
        foreach ($triviaDocs as $doc) {
            if (!$doc->exists()) continue;
            $d = $doc->data();
            $landmarkId   = $d['landmark_id'] ?? '';
            $landmarkName = $landmarkMap[$landmarkId] ?? 'Unknown Landmark';

            $allTrivia[] = [
                'trivia_id'      => $doc->id(),
                'landmark_id'    => $landmarkId,
                'landmark_name'  => $landmarkName,
                'question'       => $d['question'] ?? '',
                'choices'        => array_values($d['choices'] ?? []),
                'correct_answer' => $d['correct_answer'] ?? '',
                'created_at'     => $d['created_at'] ?? null,
                'updated_at'     => $d['updated_at'] ?? null,
            ];
        }

        
        $perPage = 9;
        $currentPage = max(1, (int) $request->query('page', 1));
        $offset = ($currentPage - 1) * $perPage;
        $currentItems = array_slice($allTrivia, $offset, $perPage);

        $triviaPaginator = new LengthAwarePaginator(
            $currentItems,
            count($allTrivia),
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('curators.trivia.all', compact('triviaPaginator', 'landmarkList'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'landmark_id'    => 'required|string',
            'question'       => 'required|string',
            'choices'        => 'required|array|min:2',
            'correct_answer' => 'required|string',
        ]);

        
        if (!in_array($request->correct_answer, $request->choices, true)) {
            return back()
                ->withErrors(['correct_answer' => 'Correct answer must be one of the choices.'])
                ->withInput();
        }

        $this->firebase->addTrivia([
            'landmark_id'    => (string) $request->landmark_id,
            'question'       => (string) $request->question,
            'choices'        => array_values(array_filter($request->choices, fn($c) => $c !== null && $c !== '')),
            'correct_answer' => (string) $request->correct_answer,
        ]);

        return back()->with('success', 'Trivia added to Question Bank!');
    }

    public function update(Request $request, string $triviaId)
    {
        $request->validate([
            'question'       => 'required|string',
            'choices'        => 'required|array|min:2',
            'correct_answer' => 'required|string',
        ]);

        if (!in_array($request->correct_answer, $request->choices, true)) {
            return back()
                ->withErrors(['correct_answer' => 'Correct answer must be one of the choices.'])
                ->withInput();
        }

        $this->firebase->updateTrivia($triviaId, [
            'question'       => (string) $request->question,
            'choices'        => array_values(array_filter($request->choices, fn($c) => $c !== null && $c !== '')),
            'correct_answer' => (string) $request->correct_answer,
        ]);

        return back()->with('success', 'Trivia updated.');
    }

    public function destroy(string $triviaId)
    {
        $this->firebase->deleteTrivia($triviaId);
        return back()->with('success', 'Trivia deleted.');
    }

    
    public function play(string $landmarkId)
    {
        $lm = $this->firebase->getLandmarkById($landmarkId);
        if (!$lm->exists()) abort(404);

        return view('quiz.play', [
            'landmark' => [
                'id'   => $landmarkId,
                'name' => $lm['name'] ?? 'Untitled',
            ],
        ]);
    }

    
    public function getQuiz(Request $request)
    {
        $landmarkId = (string) $request->query('landmark_id', '');
        $limit      = (int) $request->query('limit', 5);

        if ($landmarkId === '') {
            return response()->json(['error' => 'landmark_id is required'], 422);
        }

        $docs = $this->firebase->getTriviaByLandmarkId($landmarkId);

        $pool = [];
        foreach ($docs as $doc) {
            if (!$doc->exists()) continue;
            $d = $doc->data();
            $choices = array_values($d['choices'] ?? []);
            shuffle($choices);
            $pool[] = [
                'id'       => $doc->id(),
                'question' => (string) ($d['question'] ?? ''),
                'choices'  => $choices,
            ];
        }

        shuffle($pool);
        $items = array_slice($pool, 0, max(1, $limit));

        return response()->json([
            'landmark_id' => $landmarkId,
            'count'       => count($items),
            'items'       => $items,
        ]);
    }

    
    public function getQuizKey(Request $request)
    {
        $landmarkId = (string) $request->query('landmark_id', '');
        if ($landmarkId === '') {
            return response()->json(['error' => 'landmark_id is required'], 422);
        }

        $docs = $this->firebase->getTriviaByLandmarkId($landmarkId);

        $key = [];
        foreach ($docs as $doc) {
            if (!$doc->exists()) continue;
            $d = $doc->data();
            $key[] = [
                'id'              => $doc->id(),
                'correct_answer'  => (string)($d['correct_answer'] ?? ''),
            ];
        }

        return response()->json([
            'landmark_id' => $landmarkId,
            'items'       => $key,
        ]);
    }
}
