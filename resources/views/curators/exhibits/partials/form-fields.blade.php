@php
    $isEdit = is_array($exhibit ?? null);
    $prefix = $isEdit ? 'edit-'.$exhibit['id'].'-' : 'create-';
    $categoryOptions = $categoryOptions ?? [];
    $landmarkOptions = $landmarkOptions ?? [];
    $canSelectLandmark = (bool) ($canSelectLandmark ?? false);
    $selectedCategory = old('category', $exhibit['category'] ?? '');
    $landmarkName = is_array($landmark ?? null) ? ($landmark['name'] ?? 'Assigned landmark') : 'Assigned landmark';
    $selectedLandmarkId = old('landmark_id', $exhibit['landmark_id'] ?? ($landmark['id'] ?? ''));
@endphp

@if ($canSelectLandmark)
    <label for="{{ $prefix }}landmark">
        Belongs to Landmark
        <span class="category-dropdown landmark-dropdown">
            <select class="category-dropdown__native" id="{{ $prefix }}landmark" name="landmark_id" required>
                <option value="">Select landmark</option>
                @foreach ($landmarkOptions as $landmarkOption)
                    <option value="{{ $landmarkOption['id'] }}" @selected($selectedLandmarkId === $landmarkOption['id'])>{{ $landmarkOption['name'] }}</option>
                @endforeach
            </select>
            <button class="category-dropdown__toggle landmark-dropdown__toggle" type="button" aria-haspopup="listbox" aria-expanded="false"></button>
            <button type="button" class="custom-select-arrow" aria-label="Toggle landmark options" aria-expanded="false"></button>
            <ul class="category-dropdown-menu landmark-dropdown-menu" role="listbox" hidden></ul>
        </span>
    </label>
@else
    <div class="exhibits-form-note">
        <strong>Belongs to Landmark</strong>
        {{ $landmarkName }}
    </div>
@endif

<label for="{{ $prefix }}name">
    Exhibit Name
    <input id="{{ $prefix }}name" type="text" name="name" value="{{ old('name', $exhibit['name'] ?? '') }}" placeholder="Spanish Cannons" required>
</label>

<div class="exhibits-grid-2">
    <label for="{{ $prefix }}category">
        Category
        <span class="category-dropdown{{ ($routePrefix ?? '') === 'curators' ? ' curator-custom-select' : '' }}">
            <select class="category-dropdown__native" id="{{ $prefix }}category" name="category" required {{ count($categoryOptions) === 0 ? 'disabled' : '' }}>
                @if (count($categoryOptions) === 0)
                    <option value="">No categories available</option>
                @else
                    <option value="">Select category</option>
                @endif
                @foreach ($categoryOptions as $categoryOption)
                    <option value="{{ $categoryOption }}" @selected($selectedCategory === $categoryOption)>{{ $categoryOption }}</option>
                @endforeach
            </select>
            <button class="category-dropdown__toggle" type="button" aria-haspopup="listbox" aria-expanded="false" {{ count($categoryOptions) === 0 ? 'disabled' : '' }}></button>
            <button type="button" class="custom-select-arrow" aria-label="Toggle category options" aria-expanded="false" {{ count($categoryOptions) === 0 ? 'disabled' : '' }}></button>
            <ul class="category-dropdown-menu" role="listbox" hidden></ul>
        </span>
    </label>
    <label for="{{ $prefix }}year-period">
        Year/Period
        <input id="{{ $prefix }}year-period" type="text" name="year_period" value="{{ old('year_period', $exhibit['year_period'] ?? '') }}" placeholder="Spanish era, 1738, 19th century">
    </label>
</div>

@if (count($categoryOptions) === 0)
    <div class="exhibits-form-alert">No exhibit categories available. Please add a category first.</div>
@endif

<label for="{{ $prefix }}description">
    Short Description
    <textarea id="{{ $prefix }}description" name="description" placeholder="Briefly describe the item or display.">{{ old('description', $exhibit['description'] ?? '') }}</textarea>
</label>

<label for="{{ $prefix }}historical-info">
    Historical Information
    <textarea id="{{ $prefix }}historical-info" name="historical_info" placeholder="Add provenance, cultural context, usage, makers, owners, or related historical events.">{{ old('historical_info', $exhibit['historical_info'] ?? '') }}</textarea>
</label>

@if ($isEdit)
    <div class="exhibits-grid-2">
        <label for="{{ $prefix }}images">
            Images (add more images)
            <input id="{{ $prefix }}images" class="landmark-form-control equal-control" type="file" name="images[]" accept="image/*" multiple>
        </label>
        <label for="{{ $prefix }}status">
            Status
            <span class="category-dropdown edit-exhibit-status-dropdown{{ ($routePrefix ?? '') === 'curators' ? ' curator-custom-select' : '' }}">
                <select class="category-dropdown__native" id="{{ $prefix }}status" name="status" required>
                    <option value="active" @selected(old('status', $exhibit['status'] ?? 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', $exhibit['status'] ?? 'active') === 'inactive')>Inactive</option>
                </select>
                <button class="category-dropdown__toggle equal-control" type="button" aria-haspopup="listbox" aria-expanded="false"></button>
                <button type="button" class="custom-select-arrow" aria-label="Toggle status options" aria-expanded="false"></button>
                <ul class="category-dropdown-menu" role="listbox" hidden></ul>
            </span>
        </label>
    </div>

    @if (count($exhibit['images'] ?? []) > 0)
        <div>
            <div class="exhibits-existing-title">Existing Images</div>
            <div class="exhibits-existing-media">
                @foreach ($exhibit['images'] as $image)
                    @php $imagePath = is_array($image) ? (string) ($image['path'] ?? '') : ''; @endphp
                    @if ($imagePath !== '')
                        <label>
                            @if (! empty($image['url']))
                                <img src="{{ $image['url'] }}" alt="{{ $image['filename'] ?? 'Exhibit image' }}">
                            @endif
                            <span class="exhibits-existing-remove">
                                <input type="checkbox" name="remove_images[]" value="{{ $imagePath }}">
                                Remove
                            </span>
                        </label>
                    @endif
                @endforeach
            </div>
        </div>
    @endif
@else
    <div class="exhibits-grid-2">
        <label for="{{ $prefix }}images">
            Images
            <input id="{{ $prefix }}images" class="landmark-form-control images-upload-control" type="file" name="images[]" accept="image/*" multiple required>
        </label>
        <label for="{{ $prefix }}status">
            Status
            <span class="category-dropdown add-exhibit-status-dropdown{{ ($routePrefix ?? '') === 'curators' ? ' curator-custom-select' : '' }}">
                <select class="category-dropdown__native" id="{{ $prefix }}status" name="status" required>
                    <option value="active" @selected(old('status', 'active') === 'active')>Active</option>
                    <option value="inactive" @selected(old('status', 'active') === 'inactive')>Inactive</option>
                </select>
                <button class="category-dropdown__toggle status-select-control" type="button" aria-haspopup="listbox" aria-expanded="false"></button>
                <button type="button" class="custom-select-arrow" aria-label="Toggle status options" aria-expanded="false"></button>
                <ul class="category-dropdown-menu" role="listbox" hidden></ul>
            </span>
        </label>
    </div>
@endif
