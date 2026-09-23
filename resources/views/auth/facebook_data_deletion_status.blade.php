@extends('layout')
@section('title')
    <title>Welcome to {{ Config::get("app.app_name") }} - Facebook Data Deletion Status</title>
@append
@section('scripts')
    <script type="application/javascript">
    </script>
@append

@section('content')
    <div class="container">
        <div class="well">
            <h3>Facebook Data Deletion Request Status</h3>
            <p>Confirmation code: <strong>{{ $result['confirmation_code'] }}</strong></p>
            @if($result['status'] === 'completed')
                <p>Your Facebook-linked data has been deleted.</p>
            @else
                <p>No data was found for this identifier.</p>
            @endif
        </div>
    </div>
@endsection
