<?php

namespace App\Services;

use Kreait\Firebase\Factory;
use Kreait\Firebase\Auth;
use Illuminate\Support\Facades\Http;

class FirebaseService
{
    protected $auth;
    protected $firestore;

    public function __construct()
    {
        $factory = (new Factory)->withServiceAccount(
            storage_path('app/firebase_credentials.json')
        );

        $this->auth = $factory->createAuth();
        $this->firestore = $factory->createFirestore()->database();
    }

    /* -------------------------
     | Accessors
     |--------------------------*/
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

    /* -------------------------
     | Firebase Auth helpers
     |--------------------------*/
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

    /* -------------------------
     | Landmarks helpers
     |--------------------------*/
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

    /* -------------------------
     | Trivia helpers (flat collection)
     |--------------------------*/
    public function getAllTrivia()
    {
        return $this->firestore->collection('trivia')->documents();
    }

    public function getTrivia($triviaId)
    {
        return $this->firestore
            ->collection('trivia')
            ->document($triviaId)
            ->snapshot();
    }

    public function getTriviaByLandmarkId($landmarkId)
    {
        return $this->firestore
            ->collection('trivia')
            ->where('landmark_id', '=', $landmarkId)
            ->documents();
    }

    public function addTrivia(array $data)
    {
        $docData = [
            'landmark_id'    => $data['landmark_id'] ?? null,
            'question'       => $data['question'] ?? '',
            'choices'        => $data['choices'] ?? [],
            'correct_answer' => $data['correct_answer'] ?? '',
            
            'created_at'     => now(),
        ];

        return $this->firestore->collection('trivia')->add($docData);
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
        if (array_key_exists('clue', $data)) {
            $docData['clue'] = $data['clue'];
        }

        $docData['updated_at'] = now();

        return $this->firestore
            ->collection('trivia')
            ->document($triviaId)
            ->set($docData, ['merge' => true]);
    }

    public function deleteTrivia($triviaId)
    {
        return $this->firestore
            ->collection('trivia')
            ->document($triviaId)
            ->delete();
    }
}
