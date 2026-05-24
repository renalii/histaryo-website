@extends('layouts.sidebar')

@php
    $lmCreateFormAction = $lmCreateFormAction ?? route('sitemanager.landmarks.store');
    $lmCreateCancelUrl = $lmCreateCancelUrl ?? route('sitemanager.landmarks');
@endphp

@section('content')
    <style>
        .lm-create-page {
            width: 100%;
            max-width: none;
            margin: 0;
            padding: 0 0 2rem;
            box-sizing: border-box;
        }
        .lm-create-head {
            margin-bottom: 1.25rem;
            display: grid;
            gap: 0.65rem 2rem;
            align-items: start;
            justify-items: start;
        }
        @media (min-width: 960px) {
            .lm-create-head {
                grid-template-columns: minmax(0, 1fr) minmax(0, 1.15fr);
                grid-template-rows: auto auto;
                margin-bottom: 1.35rem;
            }
            .lm-create-eyebrow {
                grid-column: 1;
                grid-row: 1;
                margin-bottom: 0;
            }
            .lm-create-title {
                grid-column: 1;
                grid-row: 2;
                margin-bottom: 0;
                width: 100%;
                min-width: 0;
            }
            .lm-create-lead {
                grid-column: 2;
                grid-row: 1 / -1;
                align-self: center;
                max-width: none;
                width: 100%;
                min-width: 0;
                justify-self: stretch;
            }
        }
        .lm-create-eyebrow {
            display: inline-block;
            width: fit-content;
            max-width: 100%;
            box-sizing: border-box;
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .12em;
            text-transform: uppercase;
            color: #92400e;
            background: linear-gradient(135deg, #fff7ed, #ffedd5);
            border: 1px solid #fed7aa;
            padding: .35rem .65rem;
            border-radius: 999px;
            margin-bottom: .65rem;
        }
        .lm-create-title {
            font-size: clamp(1.55rem, 3vw, 2rem);
            font-weight: 800;
            color: #7A2E1F;
            margin: 0 0 .5rem 0;
            line-height: 1.2;
            letter-spacing: -0.02em;
        }
        .lm-create-lead {
            color: #6b7280;
            font-size: 1rem;
            line-height: 1.55;
            margin: 0;
            max-width: 42em;
        }
        .lm-create-lead strong { color: #374151; font-weight: 600; }
        .lm-card {
            background: #fff;
            border: 1px solid #eceff3;
            border-radius: 16px;
            box-shadow: 0 8px 30px rgba(15, 23, 42, 0.06);
            overflow: hidden;
        }
        .lm-card-body { padding: 1.5rem 1.5rem 1.25rem; }
        @media (min-width: 640px) {
            .lm-card-body { padding: 1.75rem 2rem 1.5rem; }
        }
        .lm-section {
            margin-bottom: 1.5rem;
        }
        .lm-section:last-of-type { margin-bottom: 0; }
        .lm-section-title {
            font-size: .8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: .06em;
            color: #7A2E1F;
            margin: 0 0 1rem 0;
            padding-bottom: .5rem;
            border-bottom: 1px solid #f1f5f9;
        }
        .lm-field { margin-bottom: 1.1rem; }
        .lm-field:last-child { margin-bottom: 0; }
        .lm-field label {
            display: flex;
            align-items: center;
            gap: .4rem;
            font-weight: 600;
            font-size: .9rem;
            color: #1f2937;
            margin-bottom: .4rem;
        }
        .lm-optional {
            font-weight: 500;
            font-size: .75rem;
            color: #9ca3af;
            text-transform: none;
            letter-spacing: 0;
        }
        .lm-input,
        .lm-select,
        .lm-textarea {
            width: 100%;
            padding: .65rem .85rem;
            border: 1px solid #e5e7eb;
            border-radius: 10px;
            font-size: .95rem;
            color: #111827;
            background: #fafafa;
            box-sizing: border-box;
            transition: border-color .15s ease, box-shadow .15s ease, background .15s ease;
        }
        .lm-input:hover,
        .lm-select:hover,
        .lm-textarea:hover {
            border-color: #d1d5db;
            background: #fff;
        }
        .lm-input:focus,
        .lm-select:focus,
        .lm-textarea:focus {
            outline: none;
            border-color: #E8B34B;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(232, 179, 75, 0.22);
        }
        .lm-textarea {
            min-height: 112px;
            resize: vertical;
            line-height: 1.5;
        }
        .lm-select {
            cursor: pointer;
            appearance: none;
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' fill='none' viewBox='0 0 24 24' stroke='%236b7280'%3E%3Cpath stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M19 9l-7 7-7-7'%3E%3C/path%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right .75rem center;
            background-size: 1.1rem;
            padding-right: 2.35rem;
        }
        .lm-grid-2 {
            display: grid;
            gap: 1rem 1rem;
        }
        @media (min-width: 520px) {
            .lm-grid-2 { grid-template-columns: 1fr 1fr; }
        }
        .lm-file-wrap {
            position: relative;
            border: 1px dashed #d1d5db;
            border-radius: 12px;
            padding: 1rem 1rem;
            background: linear-gradient(180deg, #fafafa 0%, #fff 100%);
            transition: border-color .15s ease, background .15s ease;
        }
        .lm-file-wrap:focus-within {
            border-color: #E8B34B;
            background: #fffdf8;
            box-shadow: 0 0 0 3px rgba(232, 179, 75, 0.12);
        }
        .lm-file-wrap input[type="file"] {
            font-size: .88rem;
            width: 100%;
            cursor: pointer;
            color: #4b5563;
        }
        .lm-file-hint {
            font-size: .8rem;
            color: #9ca3af;
            margin: .35rem 0 0 0;
        }
        .lm-actions {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: .65rem;
            margin-top: 1.65rem;
            padding-top: 1.35rem;
            border-top: 1px solid #f1f5f9;
        }
        .lm-btn-primary {
            flex: 1 1 auto;
            min-width: 200px;
            padding: .78rem 1.35rem;
            border-radius: 12px;
            border: 1px solid #F3C96A;
            background: linear-gradient(180deg, #f3d073 0%, #E8B34B 100%);
            color: #461c14;
            font-weight: 700;
            font-size: .95rem;
            cursor: pointer;
            box-shadow: 0 4px 14px rgba(122, 46, 31, 0.12);
            transition: transform .12s ease, box-shadow .12s ease, filter .12s ease;
        }
        .lm-btn-primary:hover {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(122, 46, 31, 0.16);
            filter: brightness(1.02);
        }
        .lm-btn-primary:active {
            transform: translateY(0);
        }
        .lm-btn-secondary {
            padding: .78rem 1.35rem;
            border-radius: 12px;
            border: 1px solid #e5e7eb;
            background: #fff;
            color: #374151;
            font-weight: 600;
            font-size: .92rem;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            transition: background .15s ease, border-color .15s ease;
        }
        .lm-btn-secondary:hover {
            background: #f9fafb;
            border-color: #d1d5db;
        }
        .lm-flash-ok {
            padding: .85rem 1.1rem;
            border-radius: 12px;
            background: #ecfdf5;
            color: #166534;
            border: 1px solid #bbf7d0;
            margin-bottom: 1.1rem;
            font-weight: 600;
            font-size: .92rem;
        }
        .lm-flash-err {
            background: #fef2f2;
            border: 1px solid #fecaca;
            color: #991b1b;
            padding: .85rem 1.1rem;
            border-radius: 12px;
            margin-bottom: 1.1rem;
        }
        .lm-flash-err ul {
            margin: .25rem 0 0 0;
            padding-left: 1.2rem;
        }
        .lm-flash-err-title {
            font-weight: 700;
            margin: 0;
            font-size: .92rem;
        }
    </style>

    <div class="lm-create-page">
        <header class="lm-create-head">
            <span class="lm-create-eyebrow">Site Manager</span>
            <h1 class="lm-create-title">Create landmark</h1>
            <p class="lm-create-lead">
                Set a short <strong>landmark code</strong> (shown to curators, e.g. CGM01). Upload <strong>evidence</strong> that the landmark exists; an administrator reviews it before the site goes live. Create the matching <strong>QR code</strong> in QR Codes using that same value.
            </p>
        </header>

        @if (session('status'))
            <p class="lm-flash-ok" role="status">{{ session('status') }}</p>
        @endif

        @if ($errors->any())
            <div class="lm-flash-err" role="alert">
                <p class="lm-flash-err-title">Please fix the following:</p>
                <ul>
                    @foreach ($errors->all() as $err)
                        <li>{{ $err }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="lm-card">
            <form class="lm-card-body" method="POST" action="{{ $lmCreateFormAction }}" enctype="multipart/form-data">
                @csrf

                <div class="lm-section">
                    <h2 class="lm-section-title">Basics</h2>
                    <div class="lm-field">
                        <label for="name">Landmark name</label>
                        <input class="lm-input" id="name" name="name" type="text"
                               autocomplete="organization"
                               placeholder="Official or common name visitors will see"
                               value="{{ old('name') }}" required>
                    </div>
                    <div class="lm-field">
                        <label for="landmarkcode">Landmark code</label>
                        <input class="lm-input" id="landmarkcode" name="landmarkcode" type="text"
                               autocomplete="off"
                               maxlength="48"
                               pattern="[A-Za-z0-9_-]+"
                               title="Letters, numbers, hyphen, or underscore only"
                               placeholder="e.g. CGM01, BDSN01"
                               value="{{ old('landmarkcode') }}" required>
                        <p class="lm-file-hint" style="margin-top:.35rem;">Unique code curators see on their landmarks list (stored uppercase).</p>
                    </div>
                    <div class="lm-field">
                        <label for="category">Category</label>
                        <select class="lm-select" id="category" name="category" required>
                            @foreach (['Historical', 'Natural', 'Cultural', 'Religious', 'Modern'] as $cat)
                                <option value="{{ $cat }}" {{ old('category', 'Historical') === $cat ? 'selected' : '' }}>{{ $cat }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="lm-field">
                        <label for="description">Description</label>
                        <textarea class="lm-textarea" id="description" name="description" rows="5"
                                  placeholder="What makes this landmark significant? Keep it concise for visitors.">{{ old('description') }}</textarea>
                    </div>
                </div>

                <div class="lm-section">
                    <h2 class="lm-section-title">Location <span class="lm-optional">(optional but recommended)</span></h2>
                    <div class="lm-grid-2">
                        <div class="lm-field">
                            <label for="latitude">Latitude</label>
                            <input class="lm-input" id="latitude" name="latitude" type="text" inputmode="decimal"
                                   placeholder="e.g. 10.3157" value="{{ old('latitude') }}">
                        </div>
                        <div class="lm-field">
                            <label for="longitude">Longitude</label>
                            <input class="lm-input" id="longitude" name="longitude" type="text" inputmode="decimal"
                                   placeholder="e.g. 123.8854" value="{{ old('longitude') }}">
                        </div>
                    </div>
                </div>

                <div class="lm-section">
                    <h2 class="lm-section-title">Evidence <span class="lm-optional" style="color:#b45309;font-weight:700;">(required)</span></h2>
                    <div class="lm-field">
                        <label for="evidence_files">Supporting documents</label>
                        <div class="lm-file-wrap">
                            <input id="evidence_files" name="evidence_files[]" type="file"
                                   accept=".pdf,.jpg,.jpeg,.png,.webp,.doc,.docx,image/*,application/pdf"
                                   multiple required>
                        </div>
                    </div>
                </div>

                <div class="lm-section">
                    <h2 class="lm-section-title">Media</h2>
                    <div class="lm-field">
                        <label for="video_url">Video URL <span class="lm-optional">(optional)</span></label>
                        <input class="lm-input" id="video_url" name="video_url" type="url"
                               placeholder="https://youtu.be/… or YouTube watch link" value="{{ old('video_url') }}">
                    </div>
                    <div class="lm-field">
                        <label for="image">Hero image <span class="lm-optional">(optional)</span></label>
                        <div class="lm-file-wrap">
                            <input id="image" name="image" type="file" accept="image/*">
                        </div>
                    </div>
                </div>

                <div class="lm-actions">
                    <button type="submit" class="lm-btn-primary">Submit for approval</button>
                    <a href="{{ $lmCreateCancelUrl }}" class="lm-btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
@endsection
