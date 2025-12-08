<?php use App\Libraries\AppProperties; ?>
@extends('template.auth.main')

@section('content')
<div class="authentication-theme auth-style_1">
    <div class="row">
        <div class="col-12 logo-section">
            <a href="#" class="logo">
                <img src="{{ URL::to('assets/images/logo.png') }}" alt="logo" />
            </a>
        </div>
    </div>
    <div class="row">
        <div class="col-lg-5 col-md-7 col-sm-9 col-11 mx-auto">
            <div class="grid">
                <div class="grid-body">
                    <div class="row">
                        <div class="col-lg-7 col-md-8 col-sm-9 col-12 mx-auto form-wrapper">
                            @if($errors->any())
                                <div class="alert alert-danger">
                                    @foreach($errors->all() as $error)
                                        <div class="text-capitalize">{{ $error }}</div>
                                    @endforeach
                                </div>
                            @endif
                            <form action="{{ route('auth.login') }}" method="POST">
                                @csrf
                                @method('post')
                                <div class="form-group">
                                    <input type="text" name="email" class="form-control" data-validator-label="Email" data-validator="required|email" placeholder="Email" id="email" required="required" />
                                    <div class="form-control-feedback order-1"></div>
                                </div>
                                <div class="input-group show_hide_password">
                                    <input type="password" name="password" class="form-control no-radius-right" data-validator-label="Email" data-validator="required" placeholder="Password" id="password" required="required">
                                    <div class="form-control-feedback order-1"></div>
                                    <div class="input-group-append">
                                        <span class="input-group-text" id="basic-addon1">
                                            <a href="#"><i class="mdi mdi-eye-off" aria-hidden="true"></i></a>
                                        </span>
                                    </div>
                                </div>
                                <button type="submit" class="btn btn-primary btn-block mt-4">Login</button>
                            </form>
                            <hr>
                            <div class="w-100 text-center mt-3">
                                <a class="text-primary text-center w-100" href="">Lupa Password?</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <p class="text-muted text-center">{!! AppProperties::getProperties('copyright') !!}</p>
</div>
@endsection

@section('js')
<script>
    $(document).on('blur', '[data-validator]', function () {
        new Validator($(this), {
            /* your options here*/
        });
    });
</script>
@endsection
