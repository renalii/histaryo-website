<?php

namespace App\Services;

use Kreait\Firebase\Auth;
use Kreait\Firebase\Factory;
use Illuminate\Support\Facades\Http;

class FirebaseService
{
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

    
    public function getAllTrivia()
{
    return $this->firestore->collection('question_bank')->documents();
}

public function addTrivia(array $data)
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

public function updateTrivia($triviaId, array $data)
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
        ->document($triviaId)
        ->set($docData, ['merge' => true]);
}

public function deleteTrivia($triviaId)
{
    return $this->firestore
        ->collection('question_bank')
        ->document($triviaId)
        ->delete();
}

public function getTriviaByLandmarkId($landmarkId)
{
    return $this->firestore
        ->collection('question_bank')
        ->where('landmark_id', '=', $landmarkId)
        ->documents();
}

}
