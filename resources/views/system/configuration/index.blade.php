@extends('system.layouts.app')

@section('content')

    <div class="row pb-2">
        <!--<div class="col-lg-6 col-md-12 pt-2 pt-md-0">
            <system-companies-form></system-companies-form>
        </div> -->
        <div class="col-lg-6 col-md-6">
            {{-- <system-certificate-index></system-certificate-index> --}}
            <system-support-configuration></system-support-configuration>
        </div>
        <div class="col-lg-6 col-md-6">
            <system-config-login></system-config-login>
        </div>
        </div>
    </div>

@endsection
