<a href="#cu-qr-preview-modal"
   class="cu-lm-add-btn"
   data-qr-has-image="{{ ! empty($qrBase64 ?? '') ? '1' : '0' }}"
   data-qr-filename="{{ $qrFilename ?? 'landmark-code-qr.png' }}"
   onclick="cuOpenQrPreview(event, this)">Download QR</a>
