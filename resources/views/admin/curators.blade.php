@extends('layouts.sidebar')

@section('content')
    <h2 style="font-size: 1.75rem; font-weight: bold; margin-bottom: 1.5rem;">🧑‍🏫 All Curators</h2>
    
    @if (empty($curators))
        <p style="color: #6b7280;">No curators found.</p>
    @else
        <div style="background: white; padding: 1.5rem; border-radius: 10px; box-shadow: 0 4px 10px rgba(0,0,0,0.05);">
            <table style="width: 100%; border-collapse: collapse;">
                <thead style="background-color: #f3f4f6;">
                    <tr>
                        <th style="text-align: left; padding: 12px;">Image</th>
                        <th style="text-align: left; padding: 12px;">Email</th>
                        <th style="text-align: left; padding: 12px;">UID</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($curators as $curator)
                        <tr style="border-top: 1px solid #e5e7eb;">
                            <td style="padding: 12px;">
                                @if (!empty($curator->profile_image_base64))
                                    <img
                                        src="data:{{ $curator->profile_image_mime }};base64,{{ $curator->profile_image_base64 }}"
                                        alt="Curator Image"
                                        style="width: 44px; height: 44px; object-fit: cover; border-radius: 9999px; border: 1px solid #e5e7eb;"
                                    >
                                @else
                                    <span style="color: #9ca3af; font-size: 0.85rem;">No image</span>
                                @endif
                            </td>
                            <td style="padding: 12px;">{{ $curator->email }}</td>
                            <td style="padding: 12px;">{{ $curator->uid }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif
@endsection
