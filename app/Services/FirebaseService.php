<?php

namespace App\Services;

use Kreait\Firebase\Auth;
use Kreait\Firebase\Factory;
use Illuminate\Support\Facades\Http;

class FirebaseService
{
    public const USER_COLLECTIONS = [
        'admin' => 'admin',
        'site_manager' => 'site_managers',
        'curator' => 'curators',
        'visitor' => 'visitor',
    ];

    public const USER_PROFILE_SUBCOLLECTION = 'users';

    protected $auth;

    protected $firestore;

    /**
     * Google Firestore defaults to gRPC; on Windows (e.g. XAMPP), streams often fail with
     * `"Stream removed"` / UNKNOWN. Setting FIRESTORE_TRANSPORT=rest uses HTTP REST instead.
     *
     * @see https://cloud.google.com/php/docs/reference/main#grpc-or-rest
     */
    public function __construct()
    {
        $transport = strtolower(trim((string) config('services.firebase.firestore_transport', 'grpc')));
        if (in_array($transport, ['rest', 'http'], true)) {
            // Read by google/cloud-php clients before opening gRPC connections.
            putenv('GOOGLE_CLOUD_DISABLE_GRPC=true');
            $_ENV['GOOGLE_CLOUD_DISABLE_GRPC'] = 'true';
        }

        $factory = (new Factory)->withServiceAccount(
            storage_path('app/firebase_credentials.json')
        );

        $this->auth = $factory->createAuth();
        $this->firestore = $factory->createFirestore()->database();
    }

    
    public function getAuth()
    {
        return $this->auth;
    }

    public function auth()
    {
        return $this->auth;
    }

    public function firestore()
    {
        return $this->firestore;
    }

    public function normalizeUserRole(?string $role): string
    {
        $role = strtolower(trim((string) $role));

        return match ($role) {
            'admin' => 'admin',
            'site_manager', 'landmark_manager' => 'site_manager',
            'curator' => 'curator',
            default => 'visitor',
        };
    }

    public function userCollectionName(?string $role): string
    {
        return self::USER_COLLECTIONS[$this->normalizeUserRole($role)];
    }

    public function userCollectionPath(?string $role): string
    {
        return 'users/'.$this->userCollectionName($role).'/'.self::USER_PROFILE_SUBCOLLECTION;
    }

    public function userCollection(?string $role)
    {
        return $this->firestore
            ->collection('users')
            ->document($this->userCollectionName($role))
            ->collection(self::USER_PROFILE_SUBCOLLECTION);
    }

    public function userDocument(string $uid, ?string $role)
    {
        return $this->userCollection($role)->document($uid);
    }

    public function userSnapshot(string $uid, ?string $role = null)
    {
        $uid = trim($uid);
        if ($uid === '') {
            return null;
        }

        if ($role !== null && trim($role) !== '') {
            $snapshot = $this->userDocument($uid, $role)->snapshot();
            if ($snapshot->exists()) {
                return $snapshot;
            }
        }

        foreach (array_keys(self::USER_COLLECTIONS) as $lookupRole) {
            $snapshot = $this->userDocument($uid, $lookupRole)->snapshot();
            if ($snapshot->exists()) {
                return $snapshot;
            }
        }

        return null;
    }

    public function userProfile(string $uid, ?string $role = null): ?array
    {
        $snapshot = $this->userSnapshot($uid, $role);
        if ($snapshot === null || ! $snapshot->exists()) {
            return null;
        }

        $data = $snapshot->data();
        $profileRole = $this->normalizeUserRole($data['role'] ?? $role);

        return [
            'uid' => $uid,
            'role' => $profileRole,
            'collection' => $this->userCollectionPath($profileRole),
            'ref' => $this->userDocument($uid, $profileRole),
            'snapshot' => $snapshot,
            'data' => array_merge($data, ['role' => $profileRole]),
        ];
    }

    public function allUserProfiles(): array
    {
        $profiles = [];

        foreach (array_keys(self::USER_COLLECTIONS) as $role) {
            foreach ($this->userCollection($role)->documents() as $doc) {
                if (! $doc->exists()) {
                    continue;
                }

                $data = $doc->data();
                $profileRole = $this->normalizeUserRole($data['role'] ?? $role);
                $profiles[$doc->id()] = array_merge($data, ['role' => $profileRole]);
            }
        }

        return $profiles;
    }

    
    public function createUser($email, $password, $displayName)
    {
        return $this->auth->createUser([
            'email'        => $email,
            'password'     => $password,
            'displayName'  => $displayName,
        ]);
    }

    public function signInWithEmailAndPassword($email, $password)
    {
        $apiKey = config('services.firebase.api_key');

        $response = Http::post(
            "https://identitytoolkit.googleapis.com/v1/accounts:signInWithPassword?key={$apiKey}",
            [
                'email'             => $email,
                'password'          => $password,
                'returnSecureToken' => true,
            ]
        );

        if ($response->successful()) {
            return $response->json();
        }

        throw new \Exception('Firebase login failed: ' . $response->body());
    }

    
    public function getAllLandmarks()
    {
        return $this->firestore->collection('landmarks')->documents();
    }

    public function getLandmarkById($landmarkId)
    {
        return $this->firestore
            ->collection('landmarks')
            ->document($landmarkId)
            ->snapshot();
    }

    
    public function getAllQuiz()
{
    return $this->firestore->collection('question_bank')->documents();
}

public function addQuiz(array $data)
{
    $docData = [
        'landmark_id' => $data['landmark_id'] ?? null,
        'question' => $data['question'] ?? '',
        'choices' => $data['choices'] ?? [],
        'correct_answer' => $data['correct_answer'] ?? '',
        'created_at' => now(),
    ];

    return $this->firestore->collection('question_bank')->add($docData);
}

public function updateQuiz($quizId, array $data)
{
    $docData = [];

    if (array_key_exists('landmark_id', $data)) {
        $docData['landmark_id'] = $data['landmark_id'];
    }
    if (array_key_exists('question', $data)) {
        $docData['question'] = $data['question'];
    }
    if (array_key_exists('choices', $data)) {
        $docData['choices'] = $data['choices'];
    }
    if (array_key_exists('correct_answer', $data)) {
        $docData['correct_answer'] = $data['correct_answer'];
    }

    $docData['updated_at'] = now();

    return $this->firestore
        ->collection('question_bank')
        ->document($quizId)
        ->set($docData, ['merge' => true]);
}

public function deleteQuiz($quizId)
{
    return $this->firestore
        ->collection('question_bank')
        ->document($quizId)
        ->delete();
}

public function getQuizByLandmarkId($landmarkId)
{
    return $this->firestore
        ->collection('question_bank')
        ->where('landmark_id', '=', $landmarkId)
        ->documents();
}

}
