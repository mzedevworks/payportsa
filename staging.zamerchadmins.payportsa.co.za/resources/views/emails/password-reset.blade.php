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
                 Login Email: {{ $data['to_email'] }}
                </td>
            </tr>
            <tr>
                <td>
                    We assigned you a temporary password which you can use until you update it with your own. To do so, enter  your login email address.  Click on "change password" and fill-in the required fields.
                </td>
            </tr>
            <tr>
                <td>
                   To avoid typing errors, we suggest copying and pasting your temporary password.
                </td>
            </tr>
            <tr>
               <td align="center">
                   <h3>{{ $data['temporary_password'] }}<h3>
               </td> 
            </tr>
            <!-- <tr>
                <td align="center">
                   <table class="action" align="center" width="100%" cellpadding="0" cellspacing="0">
                        <tr>
                            <td align="center">
                                <table width="100%" border="0" cellpadding="0" cellspacing="0">
                                    <tr>
                                        <td align="center">
                                            <table border="0" cellpadding="0" cellspacing="0">
                                                <tr>
                                                    <td>
                                                       <a href="{{ url('merchant/password/reset') }}" class="button button-{{ $color ?? 'primary' }}" target="_blank">Reset password</a>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>
               </td>
            </tr> -->
        </table>
@endsection