<?php

namespace App\Http\Controllers\Curator;

use App\Http\Controllers\Controller;
use App\Support\CuratorAssignedLandmark;
use Illuminate\Http\Request;
use App\Services\FirebaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class QuizController extends Controller
{
    private const PER_PAGE = 9;

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
        return $this->questionBankView($request, null, null);
    }

    public function show(Request $request, string $id)
    {
        return $this->questionBankView($request, $id, 'edit');
    }

    public function confirmDelete(Request $request, string $id)
    {
        return $this->questionBankView($request, $id, 'delete');
    }

    /**
     * @return \Illuminate\Contracts\View\View
     */
    private function questionBankView(Request $request, ?string $openQuizId, ?string $autoOpenQuizMode)
    {
        $landmarkMap = [];
        $landmarkList = [];
        $assignedLandmarkId = trim((string) (CuratorAssignedLandmark::id() ?? ''));
        $landmarkIds = $assignedLandmarkId !== '' ? [$assignedLandmarkId] : CuratorAssignedLandmark::browseableIds();
        $landmarkIds = array_values(array_filter(array_unique(array_map(
            fn (mixed $id): string => trim((string) $id),
            $landmarkIds
        ))));

        if ($landmarkIds !== []) {
            $landmarkId = $landmarkIds[0];
            $landmarkMap[$landmarkId] = $this->cachedLandmarkName($landmarkId);
            $landmarkList[] = ['id' => $landmarkId, 'name' => $landmarkMap[$landmarkId]];
        }

        $perPage = self::PER_PAGE;
        $currentPage = max(1, (int) $request->query('page', 1));
        $totalQuiz = 0;
        $currentItems = [];

        if ($landmarkList !== []) {
            $landmarkId = $landmarkList[0]['id'];
            $totalQuiz = Cache::remember('curator:quiz-count:'.$landmarkId, now()->addMinutes(5), function () use ($landmarkId): int {
                $start = microtime(true);
                $query = $this->firebase->firestore()
                    ->collection('question_bank')
                    ->where('landmark_id', '=', $landmarkId);
                $count = (int) $query->count();
                Log::info('Timing Firestore query', [
                    'query' => 'question_bank.count_by_landmark',
                    'landmark_id' => $landmarkId,
                    'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                ]);

                return $count;
            });
            $lastPage = max(1, (int) ceil($totalQuiz / $perPage));
            $currentPage = min($currentPage, $lastPage);
            $offset = ($currentPage - 1) * $perPage;

            $currentItems = Cache::remember(
                'curator:quiz-page:'.$landmarkId.':'.$currentPage,
                now()->addMinutes(5),
                function () use ($landmarkId, $offset, $perPage, $landmarkMap): array {
                    $start = microtime(true);
                    $items = [];
                    $query = $this->firebase->firestore()
                        ->collection('question_bank')
                        ->where('landmark_id', '=', $landmarkId);
                    foreach ($query->offset($offset)->limit($perPage)->documents() as $doc) {
                        if ($doc->exists()) {
                            $items[] = $this->quizItemFromSnapshot($doc, $landmarkMap);
                        }
                    }
                    Log::info('Timing Firestore query', [
                        'query' => 'question_bank.page_by_landmark',
                        'landmark_id' => $landmarkId,
                        'records' => count($items),
                        'duration_ms' => (int) round((microtime(true) - $start) * 1000),
                    ]);

                    return $items;
                }
            );
        }

        $exhibits = $this->exhibitsForLandmark($landmarkList[0]['id'] ?? '');

        $quizPaginator = new LengthAwarePaginator(
            $currentItems,
            $totalQuiz,
            $perPage,
            $currentPage,
            [
                'path' => route('curators.quiz.all'),
                'query' => $request->except('page'),
            ]
        );

        $writableLandmarkIdSet = [];
        foreach ($landmarkIds as $id) {
            $writableLandmarkIdSet[trim((string) $id)] = true;
        }

        $autoOpenQuiz = null;
        if ($openQuizId !== null && $openQuizId !== '') {
            $snap = $this->firebase->firestore()->collection('question_bank')->document($openQuizId)->snapshot();
            if (! $snap->exists()) {
                abort(404);
            }
            $d = $snap->data();
            $landmarkId = trim((string) ($d['landmark_id'] ?? ''));
            if (! in_array($landmarkId, $landmarkIds, true)) {
                abort(404);
            }
            CuratorAssignedLandmark::assertMatches($landmarkId);

            $autoOpenQuiz = $this->quizItem($openQuizId, $d, $landmarkMap);
        }

        return view('curators.quiz.all', compact(
            'quizPaginator',
            'landmarkList',
            'writableLandmarkIdSet',
            'assignedLandmarkId',
            'autoOpenQuiz',
            'autoOpenQuizMode',
            'exhibits'
        ));
    }

    private function cachedLandmarkName(string $landmarkId): string
    {
        return Cache::remember(
            'curator:quiz-bank:landmark-name:'.$landmarkId,
            now()->addMinutes(10),
            function () use ($landmarkId): string {
                $snap = $this->firebase->getLandmarkById($landmarkId);
                if (! $snap->exists()) {
                    return 'Unnamed';
                }

                return trim((string) ($snap['name'] ?? '')) ?: 'Unnamed';
            }
        );
    }

    private function quizItemFromSnapshot(mixed $doc, array $landmarkMap): array
    {
        return $this->quizItem($doc->id(), $doc->data(), $landmarkMap);
    }

    private function quizItem(string $quizId, array $data, array $landmarkMap): array
    {
        $landmarkId = trim((string) ($data['landmark_id'] ?? ''));

        return [
            'quiz_id'      => $quizId,
            'landmark_id'    => $landmarkId,
            'landmark_name'  => $landmarkMap[$landmarkId] ?? 'Unknown site',
            'exhibit_id'     => trim((string) ($data['exhibit_id'] ?? '')),
            'exhibit_name'   => $this->quizExhibitName($data, $landmarkId),
            'question'       => $data['question'] ?? '',
            'choices'        => array_values($data['choices'] ?? []),
            'correct_answer' => $data['correct_answer'] ?? '',
        ];
    }

    public function store(Request $request)
    {
        $assigned = CuratorAssignedLandmark::id();
        if ($assigned === null) {
            abort(403);
        }

        $request->validate([
            'exhibit_id'     => 'required|string',
            'question'       => 'required|string',
            'choices'        => 'required|array|min:2',
            'choices.*'      => 'required|string',
            'correct_answer' => 'required|string',
        ]);

        $exhibitId = trim((string) $request->input('exhibit_id'));
        if (! $this->exhibitBelongsToLandmark($exhibitId, (string) $assigned)) {
            return back()
                ->withErrors(['exhibit_id' => 'Choose an exhibit from your assigned landmark.'])
                ->withInput();
        }

        $question = trim((string) $request->input('question'));
        $choices = array_values(array_map(fn (mixed $choice): string => trim((string) $choice), $request->input('choices', [])));
        $correctAnswer = trim((string) $request->input('correct_answer'));
        if ($question === '' || in_array('', $choices, true)) {
            return back()
                ->withErrors(['question' => 'Question and all answer choices are required.'])
                ->withInput();
        }
        if (! in_array($correctAnswer, $choices, true)) {
            return back()
                ->withErrors(['correct_answer' => 'Correct answer must be one of the choices.'])
                ->withInput();
        }

        CuratorAssignedLandmark::assertMatches($assigned);

        $this->firebase->addQuiz([
            'landmark_id'    => $assigned,
            'exhibit_id'     => $exhibitId,
            'question'       => $question,
            'choices'        => $choices,
            'correct_answer' => $correctAnswer,
        ]);
        $this->forgetQuizCache($assigned);
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
            'exhibit_id'     => 'required|string',
            'question'       => 'required|string',
            'choices'        => 'required|array|min:2',
            'choices.*'      => 'required|string',
            'correct_answer' => 'required|string',
        ]);

        $landmarkId = trim((string) ($snap['landmark_id'] ?? ''));
        $exhibitId = trim((string) $request->input('exhibit_id'));
        if (! $this->exhibitBelongsToLandmark($exhibitId, $landmarkId)) {
            return redirect()
                ->route('curators.quiz.show', $id)
                ->withErrors(['exhibit_id' => 'Choose an exhibit from your assigned landmark.'])
                ->withInput();
        }

        $question = trim((string) $request->input('question'));
        $choices = array_values(array_map(fn (mixed $choice): string => trim((string) $choice), $request->input('choices', [])));
        $correctAnswer = trim((string) $request->input('correct_answer'));
        if ($question === '' || in_array('', $choices, true)) {
            return redirect()
                ->route('curators.quiz.show', $id)
                ->withErrors(['question' => 'Question and all answer choices are required.'])
                ->withInput();
        }
        if (! in_array($correctAnswer, $choices, true)) {
            return redirect()
                ->route('curators.quiz.show', $id)
                ->withErrors(['correct_answer' => 'Correct answer must be one of the choices.'])
                ->withInput();
        }

        $this->firebase->updateQuiz($id, [
            'exhibit_id'     => $exhibitId,
            'question'       => $question,
            'choices'        => $choices,
            'correct_answer' => $correctAnswer,
        ]);
        $this->forgetQuizCache((string) ($snap['landmark_id'] ?? ''));

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
        $this->forgetQuizCache((string) ($snap['landmark_id'] ?? ''));

        return redirect()
            ->route('curators.quiz.all')
            ->with('success', 'Quiz deleted successfully');
    }

    private function forgetQuizCache(string $landmarkId): void
    {
        $landmarkId = trim($landmarkId);
        if ($landmarkId === '') {
            return;
        }

        Cache::forget('curator:quiz-count:'.$landmarkId);
        for ($page = 1; $page <= 20; $page++) {
            Cache::forget('curator:quiz-page:'.$landmarkId.':'.$page);
        }
    }

    private function exhibitBelongsToLandmark(string $exhibitId, string $landmarkId): bool
    {
        if ($exhibitId === '' || $landmarkId === '') {
            return false;
        }

        $snapshot = $this->firebase->firestore()->collection('exhibits')->document($exhibitId)->snapshot();

        return $snapshot->exists()
            && trim((string) ($snapshot['landmark_id'] ?? '')) === $landmarkId
            && $this->isQuizEligibleExhibit($snapshot->data());
    }

    /** @return list<array{id:string,name:string}> */
    private function exhibitsForLandmark(string $landmarkId): array
    {
        if ($landmarkId === '') {
            return [];
        }

        $exhibits = [];
        foreach ($this->firebase->firestore()->collection('exhibits')->where('landmark_id', '==', $landmarkId)->documents() as $document) {
            if (! $document->exists()) {
                continue;
            }

            $data = $document->data();
            if (! $this->isQuizEligibleExhibit($data)) {
                continue;
            }

            $exhibits[] = [
                'id' => $document->id(),
                'name' => trim((string) ($data['name'] ?? 'Untitled exhibit')) ?: 'Untitled exhibit',
            ];
        }

        usort($exhibits, fn (array $left, array $right): int => strcasecmp($left['name'], $right['name']));

        return $exhibits;
    }

    /** Existing exhibits are quiz-eligible unless explicitly marked inactive. */
    private function isQuizEligibleExhibit(array $data): bool
    {
        return strtolower(trim((string) ($data['status'] ?? 'active'))) !== 'inactive';
    }

    private function quizExhibitName(array $data, string $landmarkId): string
    {
        $exhibitId = trim((string) ($data['exhibit_id'] ?? ''));
        if ($exhibitId === '' || $landmarkId === '') {
            return '';
        }

        $exhibit = $this->firebase->firestore()->collection('exhibits')->document($exhibitId)->snapshot();
        if (! $exhibit->exists() || trim((string) ($exhibit['landmark_id'] ?? '')) !== $landmarkId) {
            return '';
        }

        return trim((string) ($exhibit['name'] ?? ''));
    }
}
