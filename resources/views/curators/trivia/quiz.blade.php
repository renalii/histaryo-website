@extends('layouts.sidebar')

@section('content')
    <div style="max-width: 1100px; margin: 0 auto;">
        <h1 style="font-size: 2rem; font-weight: 700;">Quiz for Landmark: {{ $landmarkName }}</h1>

        <form method="POST" action="{{ route('curators.trivia.submit') }}">
            @csrf
            @foreach($randomTrivia as $index => $trivia)
                <div style="margin-bottom: 1.5rem;">
                    <p>{{ $trivia['question'] }}</p>
                    <ul>
                        @foreach($trivia['choices'] as $choice)
                            <li>
                                <input type="radio" name="question_{{ $index }}" value="{{ $choice }}">
                                {{ $choice }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endforeach

            <button type="submit" style="background-color: #E76A1F; color: white; padding: 0.75rem 1.5rem; font-weight: 600;">
                Submit Quiz
            </button>
        </form>
    </div>
@endsection
