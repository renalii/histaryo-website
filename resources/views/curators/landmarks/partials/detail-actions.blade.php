<a href="{{ route('curators.qr') }}"
   class="cu-lm-add-btn"
   data-qr-download-url="{{ route('curators.qr.byLandmark', $landmarkId) }}"
   onclick="cuDownloadQrAndGo(event, this)">Download QR</a>
<a href="{{ route('landmarks.edit', $landmarkId) }}" class="cu-lm-add-btn" style="background:#e0e7ff;border:1px solid #a5b4fc;">Edit</a>
<form action="{{ route('landmarks.destroy', $landmarkId) }}" method="POST" style="display:inline;">
    @csrf
    @method('DELETE')
    <button type="submit" class="cu-lm-add-btn" style="background:#fee2e2;border:1px solid #fecaca;color:#991b1b;">Delete</button>
</form>
