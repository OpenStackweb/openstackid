@extends('reactapp_layout')
@section('title')
    <title>Welcome to {{ Config::get('app.app_name') }} - Sign Up </title>
@append
@section('meta')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@append


<!-- @section('content')
<div class="container">
    <div class="col-xs-12 col-md-5 col-md-offset-3 signup-form">
        <form id="form-registration"
              target="_self"
              method="POST" autocomplete="off" action="{{ URL::action('Auth\RegisterController@register') }}">
            @csrf
            <h2>Register</h2>
            <p class="hint-text">Create your account. It's free and only takes a minute.</p>
            <div class="form-group">
                <div class="row">
                    <div class="col-xs-6">
                        <input autocomplete="off" type="text" class="form-control" name="first_name" placeholder="First Name" required="required" data-lpignore="true"
                               @if($first_name)
                               value="{{$first_name}}"
                               @else
                               value="{{old('first_name')}}"
                               @endif
                        />
                    </div>
                    <div class="col-xs-6">
                        <input autocomplete="off" type="text" class="form-control" name="last_name" placeholder="Last Name" required="required" data-lpignore="true"
                               @if($last_name)
                               value="{{$last_name}}"
                               @else
                               value="{{old('last_name')}}"
                               @endif
                        />
                    </div>
                </div>
            </div>
            <div class="form-group">
                <input type="email" class="form-control" id="email" name="email" placeholder="Email" required="required" autocomplete="username" data-lpignore="true"
                       @if($email)
                       value="{{$email}}"
                       @else
                       value="{{old('email')}}"
                       @endif
                />
            </div>
            <div class="form-group">
                    <select id="country_iso_code" class="form-control{{ $errors->has('country_iso_code') ? ' is-invalid' : '' }}" name="country_iso_code" value="{{ old('country_iso_code') }}" required autofocus autocomplete="off" data-lpignore="true">
                        <option value="">--SELECT A COUNTRY --</option>
                        @foreach($countries as $country)
                            <option value="{!! $country->getAlpha2() !!}">{!! $country->getName() !!}</option>
                        @endforeach
                    </select>
                    @if ($errors->has('country_iso_code'))
                        <span class="invalid-feedback" role="alert">
                                        <strong>{{ $errors->first('country_iso_code') }}</strong>
                                    </span>
                    @endif

            </div>
            <div class="form-group password-container">
                <input type="password" class="form-control" id="password" name="password" placeholder="Password" required="required" autocomplete="new-password" data-lpignore="true">
            </div>
            <div class="form-group password-container">
                <input type="password" class="form-control" id="password-confirm" name="password_confirmation" placeholder="Confirm Password" required="required" autocomplete="new-password" data-lpignore="true">
            </div>
            <i class="fa fa-info-circle"> The password must be 8–30 characters, and must include a special character.</i>
            <div class="form-group">
                <div class="recaptcha-container" >
                    {!! Recaptcha::render(['id'=>'captcha', 'class'=>'input-block-level', 'callback'=>'verifyCaptchaCallback']) !!}
                    <input type="hidden"name="g_recaptcha_hidden" id="g_recaptcha_hidden">
                </div>
            </div>

            @if(Config::get("app.code_of_conduct_link"))
                <div class="checkbox agree_code_of_conduct">
                    <label>
                        <input name="agree_code_of_conduct" id="agree_code_of_conduct" type="checkbox"> I agree to the <a href="{!! Config::get("app.code_of_conduct_link") !!}" target="_blank">{!! Config::get("app.tenant_name") !!} Community Code of Conduct</a>?
                    </label>
                </div>
            @endif

            <div class="form-group">
                <button type="submit" class="btn btn-primary btn-lg btn-block">Register Now</button>
            </div>
            @if($redirect_uri)
                <input type="hidden" id="redirect_uri" name="redirect_uri" value="{{$redirect_uri}}"/>
            @endif
            @if($client_id)
                <input type="hidden" id="client_id" name="client_id" value="{{$client_id}}"/>
            @endif
        </form>
        <div class="text-center">Already have an account? <a target="_self" href="{!! URL::action('UserController@getLogin') !!}">Sign in</a></div>
    </div>
</div>
@endsection  -->

@section('css')
    {!! HTML::style('assets/css/signup.css') !!}
@append
@section('content')
    
@append
@section('scripts')
    <script>
        let signUpError = '';
        const initialValues = {
            first_name: '',
            last_name: '',
            email: '',
            country_iso_code: '',
            password: '',
            password_confirmation: '',
            agree_code_of_conduct: false,
        }
        @if ($errors->any())
            @foreach($errors->all() as $error)
                signUpError = '{!! $error !!}';
            @endforeach

            initialValues.first_name = "{{old('first_name')}}";
            initialValues.last_name = "{{old('last_name')}}";
            initialValues.email = "{{old('email')}}";
            initialValues.country_iso_code = "{{old('country_iso_code')}}";
        @endif

        let countries = [];
        @foreach($countries as $country)
            countries.push({ value: "{!! $country->getAlpha2() !!}", text: "{!! $country->getName() !!}" });
        @endforeach

        let config = {
            csrfToken :  document.head.querySelector('meta[name="csrf-token"]').content,
            realm: '{{isset($identity_select) ? $realm : ""}}',
            appName: '{{ Config::get("app.app_name") }}',
            appLogo: '{{  Config::get("app.logo_url") }}',
            clientId: '{{ $client_id }}',
            codeOfConductUrl: '{!! Config::get("app.code_of_conduct_link") !!}',
            countries: countries,
            redirectUri: '{{ $redirect_uri }}',
            signInAction:'{{ URL::action("UserController@getLogin") }}',
            signUpAction: '{{ URL::action("Auth\RegisterController@register") }}',
            signUpError: signUpError,
            captchaPublicKey: '{{ Config::get("recaptcha.public_key") }}',
            showInfoBanner: parseInt('{{ Config::get("app.show_info_banner", 0) }}') === 1 ? true: false,
            infoBannerContent: '{!! html_entity_decode(Config::get("app.info_banner_content")) !!}',
            tenantName: '{{ Config::get("app.tenant_name") }}',
            initialValues: initialValues
        }

        window.SIGN_UP_ENDPOINT = config.signUpAction;
    </script>
    {!! HTML::script('assets/signup.js') !!}
@append
