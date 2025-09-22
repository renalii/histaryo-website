@extends('layouts.sidebar')

@section('content')
    <div style="max-width: 1100px; margin: 0 auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 2rem;">
            <h1 style="font-size: 2rem; font-weight: 700; margin: 0;">All Trivia Questions</h1>
            <button onclick="openModal()" style="background-color: #7e22ce; color: white; padding: 0.6rem 1.25rem; border: none; border-radius: 8px; font-weight: 600; cursor: pointer;">
                ➕ Add Trivia
            </button>
        </div>

        {{-- Success Message --}}
        @if (session('success'))
            <div style="background-color: #d1fae5; padding: 1rem 1.25rem; border-radius: 0.5rem; color: #065f46; font-weight: 500; margin-bottom: 1rem;">
                {{ session('success') }}
            </div>
        @endif

        {{-- Trivia Table --}}
        @if (count($allTrivia) > 0)
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; font-size: 0.95rem; box-shadow: 0 0 10px rgba(0,0,0,0.05);">
                    <thead style="background-color: #ede9fe; text-align: left;">
                        <tr>
                            <th style="padding: 0.75rem 1rem;">#</th>
                            <th style="padding: 0.75rem 1rem;">Landmark</th>
                            <th style="padding: 0.75rem 1rem;">Question</th>
                            <th style="padding: 0.75rem 1rem;">Choices</th>
                            <th style="padding: 0.75rem 1rem;">Correct Answer</th>
                            <th style="padding: 0.75rem 1rem;">Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($allTrivia as $index => $trivia)
                            <tr style="background-color: #fff; border-bottom: 1px solid #e5e7eb;">
                                <td style="padding: 0.75rem 1rem; color: #6b7280;">{{ $index + 1 }}</td>
                                <td style="padding: 0.75rem 1rem; font-weight: 500;">
                                    <a href="{{ route('curators.trivia.index', $trivia['landmark_id']) }}" style="color: #4c1d95; text-decoration: underline;">
                                        {{ $trivia['landmark_name'] }}
                                    </a>
                                </td>
                                <td style="padding: 0.75rem 1rem;">{{ $trivia['question'] }}</td>
                                <td style="padding: 0.75rem 1rem;">
                                    <ul style="padding-left: 1.25rem; margin: 0;">
                                        @foreach ($trivia['choices'] as $choice)
                                            <li>{{ $choice }}</li>
                                        @endforeach
                                    </ul>
                                </td>
                                <td style="padding: 0.75rem 1rem; color: #059669;">
                                    <strong>{{ $trivia['correct_answer'] }}</strong>
                                </td>
                                <td style="padding: 0.75rem 1rem; display: flex; gap: 0.5rem;">
                                    <button onclick='openEditModal(@json($trivia))' style="padding: 0.5rem 0.75rem; background-color: #fbbf24; color: #1f2937; border: none; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                                        ✏️ Edit
                                    </button>
                                    <form action="{{ route('curators.trivia.destroy', $trivia['trivia_id']) }}" method="POST" onsubmit="return confirm('Are you sure you want to delete this trivia?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="padding: 0.5rem 0.75rem; background-color: #ef4444; color: white; border: none; border-radius: 6px; font-size: 0.85rem; font-weight: 600; cursor: pointer;">
                                            🗑 Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @else
            <p style="color: #6b7280;">No trivia questions found.</p>
        @endif
    </div>

    {{-- Add Trivia Modal --}}
    <div id="modalOverlay" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); z-index: 9999;">
        <div style="background: white; width: 90%; max-width: 600px; margin: 5% auto; padding: 2rem; border-radius: 1rem; position: relative; box-shadow: 0 0 20px rgba(0,0,0,0.2);">
            <h2 style="margin-top: 0; font-size: 1.5rem; font-weight: 700; color: #4c1d95;">Add Trivia</h2>
            <button onclick="closeModal()" style="position: absolute; top: 1rem; right: 1rem; font-size: 1.25rem; border: none; background: none; cursor: pointer;">✖</button>

            <form id="addTriviaForm" method="POST" action="{{ route('curators.trivia.store') }}">
                @csrf

                <label for="landmark_id" style="font-weight: 600; display: block; margin-top: 1rem;">Select Landmark:</label>
                <select id="landmark_id" name="landmark_id" required style="width: 100%; padding: 0.6rem; margin-bottom: 1rem; border: 1px solid #d1d5db; border-radius: 0.375rem;">
                    <option value="" disabled selected>-- Choose a Landmark --</option>
                    @foreach ($landmarkList as $landmark)
                        <option value="{{ $landmark['id'] }}">{{ $landmark['name'] }}</option>
                    @endforeach
                </select>

                <label style="font-weight: 600;">Question:</label>
                <input type="text" name="question" required style="width: 100%; padding: 0.6rem; margin-bottom: 1rem;">

                <label style="font-weight: 600;">Choices (min 2):</label>
                <input type="text" name="choices[]" placeholder="Choice 1" required style="width: 100%; padding: 0.6rem; margin-bottom: 0.75rem;">
                <input type="text" name="choices[]" placeholder="Choice 2" required style="width: 100%; padding: 0.6rem; margin-bottom: 0.75rem;">
                <input type="text" name="choices[]" placeholder="Choice 3 (optional)" style="width: 100%; padding: 0.6rem; margin-bottom: 0.75rem;">
                <input type="text" name="choices[]" placeholder="Choice 4 (optional)" style="width: 100%; padding: 0.6rem; margin-bottom: 1rem;">

                <label style="font-weight: 600;">Correct Answer:</label>
                <input type="text" name="correct_answer" placeholder="Must match one of the choices" required style="width: 100%; padding: 0.6rem; margin-bottom: 1.5rem;">

                <button type="submit" style="background-color: #7e22ce; color: white; padding: 0.75rem 1.5rem; font-weight: 600; border: none; border-radius: 0.5rem; cursor: pointer;">
                    ➕ Add Trivia
                </button>
            </form>

            @if ($errors->any())
                <div style="margin-top: 1rem; color: red;">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
        </div>
    </div>

    {{-- Edit Trivia Modal --}}
    <div id="editModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.6); z-index: 9999;">
        <div style="background: white; width: 90%; max-width: 600px; margin: 5% auto; padding: 2rem; border-radius: 1rem; position: relative; box-shadow: 0 0 20px rgba(0,0,0,0.2);">
            <h2 style="margin-top: 0; font-size: 1.5rem; font-weight: 700; color: #4c1d95;">Edit Trivia</h2>
            <button onclick="closeEditModal()" style="position: absolute; top: 1rem; right: 1rem; font-size: 1.25rem; border: none; background: none; cursor: pointer;">✖</button>

            <form id="editTriviaForm" method="POST">
                @csrf
                @method('PUT')

                <label style="font-weight: 600;">Question:</label>
                <input type="text" name="question" id="edit_question" required style="width: 100%; padding: 0.6rem; margin-bottom: 1rem;">

                <label style="font-weight: 600;">Choices:</label>
                <input type="text" name="choices[]" id="edit_choice_1" required style="width: 100%; padding: 0.6rem; margin-bottom: 0.5rem;">
                <input type="text" name="choices[]" id="edit_choice_2" required style="width: 100%; padding: 0.6rem; margin-bottom: 0.5rem;">
                <input type="text" name="choices[]" id="edit_choice_3" style="width: 100%; padding: 0.6rem; margin-bottom: 0.5rem;">
                <input type="text" name="choices[]" id="edit_choice_4" style="width: 100%; padding: 0.6rem; margin-bottom: 1rem;">

                <label style="font-weight: 600;">Correct Answer:</label>
                <input type="text" name="correct_answer" id="edit_correct_answer" required style="width: 100%; padding: 0.6rem; margin-bottom: 1.5rem;">

                <button type="submit" style="background-color: #7e22ce; color: white; padding: 0.75rem 1.5rem; font-weight: 600; border: none; border-radius: 0.5rem; cursor: pointer;">
                    💾 Update
                </button>
            </form>
        </div>
    </div>

    {{-- Modal Scripts --}}
    <script>
        function openModal() {
            document.getElementById('modalOverlay').style.display = 'block';
        }

        function closeModal() {
            document.getElementById('modalOverlay').style.display = 'none';
        }

        function openEditModal(trivia) {
            document.getElementById('edit_question').value = trivia.question || '';
            document.getElementById('edit_choice_1').value = trivia.choices[0] || '';
            document.getElementById('edit_choice_2').value = trivia.choices[1] || '';
            document.getElementById('edit_choice_3').value = trivia.choices[2] || '';
            document.getElementById('edit_choice_4').value = trivia.choices[3] || '';
            document.getElementById('edit_correct_answer').value = trivia.correct_answer || '';

            document.getElementById('editTriviaForm').action =
                `/curators/trivia/${trivia.trivia_id}`;

            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }
    </script>
@endsection
