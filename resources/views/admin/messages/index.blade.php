@extends('layouts.app')

@section('content')
<div class="container-fluid px-3 px-md-4 py-4">

    @include('admin.partials.onglets')

    <div class="text-center py-5">
        <h2 class="h5 mb-3">{{ __('Message Support') }}</h2>
        <p class="text-muted">📬 Cette fonctionnalité arrive bientôt — elle sera programmée plus tard.</p>
    </div>

</div>
@endsection
