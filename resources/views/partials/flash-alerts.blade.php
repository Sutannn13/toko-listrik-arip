@php
    $successMessage = $successMessage ?? session('success');
    $errorMessage = $errorMessage ?? session('error');
    $showValidationErrors = $showValidationErrors ?? false;
    $validationErrors = $validationErrors ?? $errors;
@endphp

@if ($successMessage)
    <div class="ui-alert ui-alert-success font-medium">
        {{ $successMessage }}
    </div>
@endif

@if ($errorMessage)
    <div class="ui-alert ui-alert-error font-medium">
        {{ $errorMessage }}
    </div>
@endif

@if ($showValidationErrors && $validationErrors && $validationErrors->any())
    <div class="ui-alert ui-alert-error">
        <ul class="list-disc list-inside">
            @foreach ($validationErrors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
@endif
