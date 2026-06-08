<?php

namespace App\Http\Controllers\Curator;

use App\Http\Controllers\Controller;
use App\Support\CuratorAssignedLandmark;
use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Session;

class QuizController extends Controller
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
        return $this->questionBankView($request, null);
    }

    public function show(Request $request, string $id)
    {
        return $this->questionBankView($request, $id);
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    private function questionBankView(Request $request, ?string $openQuizId)
    {
        $landmarkMap = [];
        $landmarkList = [];
        $quizDocs = [];

        foreach (CuratorAssignedLandmark::browseableIds() as $lid) {
            $lid = trim((string) $lid);
            $snap = $this->firebase->getLandmarkById($lid);
            if ($snap->exists()) {
                $landmarkMap[$lid] = $snap['name'] ?? 'Unnamed';
            }
            $quizDocs = array_merge($quizDocs, iterator_to_array($this->firebase->getQuizByLandmarkId($lid)));
        }

        foreach ($landmarkMap as $lid => $name) {
            $landmarkList[] = ['id' => $lid, 'name' => $name];
        }

        usort($landmarkList, fn (array $a, array $b) => strcasecmp($a['name'], $b['name']));

        $allQuiz = [];
        foreach ($quizDocs as $doc) {
            if (! $doc->exists()) {
                continue;
            }
            $d = $doc->data();
            $landmarkId = trim((string) ($d['landmark_id'] ?? ''));
            $landmarkName = $landmarkMap[$landmarkId] ?? 'Unknown site';

            $allQuiz[] = [
                'quiz_id'      => $doc->id(),
                'landmark_id'    => $landmarkId,
                'landmark_name'  => $landmarkName,
                'question'       => $d['question'] ?? '',
                'choices'        => array_values($d['choices'] ?? []),
                'correct_answer' => $d['correct_answer'] ?? '',
                'created_at'     => self::firestoreTimeToString($d['created_at'] ?? null),
                'updated_at'     => self::firestoreTimeToString($d['updated_at'] ?? null),
            ];
        }

        $perPage = 9;
        $currentPage = max(1, (int) $request->query('page', 1));

        if ($openQuizId !== null && $openQuizId !== '') {
            foreach ($allQuiz as $index => $item) {
                if (($item['quiz_id'] ?? '') === $openQuizId) {
                    $currentPage = (int) floor($index / $perPage) + 1;
                    break;
                }
            }
        }

        $offset = ($currentPage - 1) * $perPage;
        $currentItems = array_slice($allQuiz, $offset, $perPage);

        $quizPaginator = new LengthAwarePaginator(
            $currentItems,
            count($allQuiz),
            $perPage,
            $currentPage,
            [
                'path' => route('curators.quiz.all'),
                'query' => $request->except('page'),
            ]
        );

        $writableLandmarkIdSet = [];
        foreach (CuratorAssignedLandmark::writableIds() as $id) {
            $writableLandmarkIdSet[trim((string) $id)] = true;
        }

        $autoOpenQuiz = null;
        $quizDocumentTitle = null;
        if ($openQuizId !== null && $openQuizId !== '') {
            $snap = $this->firebase->firestore()->collection('question_bank')->document($openQuizId)->snapshot();
            if (! $snap->exists()) {
                abort(404);
            }
            $d = $snap->data();
            $landmarkId = trim((string) ($d['landmark_id'] ?? ''));
            if (! in_array($landmarkId, CuratorAssignedLandmark::browseableIds(), true)) {
                abort(404);
            }
            CuratorAssignedLandmark::assertMatches($landmarkId);

            $landmarkName = $landmarkMap[$landmarkId] ?? 'Unknown site';
            $autoOpenQuiz = [
                'quiz_id'      => $openQuizId,
                'landmark_id'    => $landmarkId,
                'landmark_name'  => $landmarkName,
                'question'       => $d['question'] ?? '',
                'choices'        => array_values($d['choices'] ?? []),
                'correct_answer' => $d['correct_answer'] ?? '',
                'created_at'     => self::firestoreTimeToString($d['created_at'] ?? null),
                'updated_at'     => self::firestoreTimeToString($d['updated_at'] ?? null),
            ];
            $quizDocumentTitle = $landmarkName;
        }

        $assignedLandmarkId = trim((string) (Session::get('assigned_landmark_id') ?? ''));

        return view('curators.quiz.all', compact(
            'quizPaginator',
            'landmarkList',
            'writableLandmarkIdSet',
            'assignedLandmarkId',
            'autoOpenQuiz',
            'quizDocumentTitle'
        ));
    }

    public function store(Request $request)
    {
        $assigned = CuratorAssignedLandmark::id();
        if ($assigned === null) {
            abort(403);
        }

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

        CuratorAssignedLandmark::assertMatches($assigned);

        $this->firebase->addQuiz([
            'landmark_id'    => $assigned,
            'question'       => (string) $request->question,
            'choices'        => array_values(array_filter($request->choices, fn($c) => $c !== null && $c !== '')),
            'correct_answer' => (string) $request->correct_answer,
        ]);

        return back()->with('success', 'Quiz added successfully');
    }

    public function update(Request $request, string $id)
    {
        $snap = $this->firebase->firestore()->collection('question_bank')->document($id)->snapshot();
        if (! $snap->exists()) {
            abort(404);
        }
        CuratorAssignedLandmark::assertMatches((string) ($snap['landmark_id'] ?? ''));

        $request->validate([
            'question'       => 'required|string',
            'choices'        => 'required|array|min:2',
            'correct_answer' => 'required|string',
        ]);

        if (!in_array($request->correct_answer, $request->choices, true)) {
            return redirect()
                ->route('curators.quiz.show', $id)
                ->withErrors(['correct_answer' => 'Correct answer must be one of the choices.'])
                ->withInput();
        }

        $this->firebase->updateQuiz($id, [
            'question'       => (string) $request->question,
            'choices'        => array_values(array_filter($request->choices, fn($c) => $c !== null && $c !== '')),
            'correct_answer' => (string) $request->correct_answer,
        ]);

        return redirect()
            ->route('curators.quiz.all')
            ->with('success', 'Quiz updated successfully');
    }

    public function destroy(string $id)
    {
        $snap = $this->firebase->firestore()->collection('question_bank')->document($id)->snapshot();
        if (! $snap->exists()) {
            abort(404);
        }
        CuratorAssignedLandmark::assertMatches((string) ($snap['landmark_id'] ?? ''));

        $this->firebase->deleteQuiz($id);

        return redirect()
            ->route('curators.quiz.all')
            ->with('success', 'Quiz deleted successfully');
    }

    private static function firestoreTimeToString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if ($value instanceof \DateTimeInterface) {
            return $value->format(\DateTimeInterface::ATOM);
        }

        if (is_object($value) && method_exists($value, 'get')) {
            $dt = $value->get();
            if ($dt instanceof \DateTimeInterface) {
                return $dt->format(\DateTimeInterface::ATOM);
            }
        }

        return is_scalar($value) ? (string) $value : null;
    }
}
