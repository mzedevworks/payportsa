@extends('emails.layout')

@section('content')
<table width="100%" cellpadding="0" cellspacing="20">
            <tr>
                <td>
                    Your PayPort SA account has been created successfully.
                </td>
            </tr>
            <tr>
                <td>
                 Url: <a href="{{ route('login') }}">{{ route('login') }}</a><br>
                 Login Email: {{ $data['to']->email }}
                </td>
            </tr>
</table>
@endsection