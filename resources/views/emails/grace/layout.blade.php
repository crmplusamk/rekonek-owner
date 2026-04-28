<style>
    html,
    body {
        padding: 0;
        margin: 0;
    }
</style>

@php
    $actionUrl = $actionUrl ?? env('CRM_CLIENT_HOST') . '/login?redirect=%2Fcrm%2Fbilling%2Fsubscription';
    $actionText = $actionText ?? 'Aktifkan Kembali';
@endphp

<div style="font-family:Arial,Helvetica,sans-serif; line-height:1.5; font-weight:normal; font-size:15px; color:#2F3044; min-height:100%; margin:0; padding:0; width:100%; background-color:#edf2f7">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;margin:0 auto; padding:0; max-width:600px">
        <tbody>
            <tr>
                <td align="center" valign="center" style="text-align:center; padding:40px">
                    <a href="{{ env('CRM_CLIENT_HOST') }}/login" class="logo">
                        <img src="{{ URL::to('assets/images/logo.png') }}" style="width:150px; height:70px" alt="Rekonek" />
                    </a>
                </td>
            </tr>
            <tr>
                <td align="left" valign="center">
                    <div style="text-align:left; margin:0 20px; padding:40px; background-color:#ffffff; border-radius:6px">
                        <div style="padding-bottom:30px; font-size:17px;">
                            <strong>Halo {{ $name ?? 'Customer' }}</strong>
                        </div>

                        @yield('content')

                        @hasSection('action')
                            <div style="padding-bottom:40px; text-align:center;">
                                @yield('action')
                            </div>
                        @elseif(! empty($showButton))
                            <div style="padding-bottom:40px; text-align:center;">
                                <a href="{{ $actionUrl }}"
                                    rel="noopener"
                                    style="text-decoration:none;display:inline-block;text-align:center;padding:0.75575rem 1.3rem;font-size:0.925rem;line-height:1.5;border-radius:0.35rem;color:#ffffff;background-color:#2465FF;border:0px;margin-right:0.75rem!important;font-weight:600!important;outline:none!important;vertical-align:middle"
                                    target="_blank">
                                    {{ $actionText }}
                                </a>
                            </div>
                        @endif

                        @if(! empty($showButton))
                            <div style="border-bottom:1px solid #eeeeee; margin:15px 0"></div>
                            <div style="padding-bottom:50px; word-wrap:break-all;">
                                <p style="margin-bottom:10px;">
                                    Tombol tidak berfungsi ? Coba copy paste link berikut pada browser anda :
                                </p>
                                <a href="{{ $actionUrl }}"
                                    rel="noopener" target="_blank"
                                    style="text-decoration:none;color:#009EF7">
                                    {{ $actionUrl }}
                                </a>
                            </div>
                        @endif

                        <div style="padding-bottom:10px">
                            Salam Hormat,
                            <br>
                            Rekonek
                        </div>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>
