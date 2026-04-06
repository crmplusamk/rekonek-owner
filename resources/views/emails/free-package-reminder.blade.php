<style>
    html,
    body {
        padding: 0;
        margin: 0;
    }
</style>

<div style="font-family:Arial,Helvetica,sans-serif; line-height: 1.5; font-weight: normal; font-size: 15px; color: #2F3044; min-height: 100%; margin:0; padding:0; width:100%; background-color:#edf2f7">
    <table align="center" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse:collapse;margin:0 auto; padding:0; max-width:600px">
        <tbody>
            <tr>
                <td align="center" valign="center" style="text-align:center; padding: 40px">
                    <a href="{{ config('app.url') }}" class="logo">
                        <img src="{{ URL::to('assets/images/logo.png') }}" style="width: 150px; height:70px" alt="logo" />
                    </a>
                </td>
            </tr>
            <tr>
                <td align="left" valign="center" style="padding-bottom: 40px;">
                    <div style="text-align:left; margin: 0 20px; padding: 40px; background-color:#ffffff; border-radius: 6px">
                        <div style="padding-bottom: 30px; font-size: 17px;">
                            <strong>Halo {{ $contactName }}</strong>
                            <div style="margin-top: 10px;">
                                Paket Free Rekonek Anda akan berakhir dalam <strong>{{ $daysLeft }} hari</strong>.
                            </div>
                        </div>
                        <div style="padding-bottom: 30px">
                            Silakan lakukan upgrade sebelum masa aktif habis agar layanan tetap berjalan tanpa gangguan.
                        </div>
                        <div style="padding-bottom: 40px; text-align:center;">
                            <a href="{{ config('app.url') }}"
                                rel="noopener"
                                style="text-decoration:none;display:inline-block;text-align:center;padding:0.75575rem 1.3rem;font-size:0.925rem;line-height:1.5;border-radius:0.35rem;color:#ffffff;background-color:#2465FF;border:0px;margin-right:0.75rem!important;font-weight:600!important;outline:none!important;vertical-align:middle"
                                target="_blank">
                                Buka Dashboard
                            </a>
                        </div>
                        <div style="border-bottom: 1px solid #eeeeee; margin: 15px 0"></div>
                        <div style="padding-bottom: 25px; word-wrap: break-all;">
                            <p style="margin-bottom: 10px;">
                                Tombol tidak berfungsi? Coba copy paste link berikut pada browser Anda:
                            </p>
                            <a href="{{ config('app.url') }}"
                                rel="noopener" target="_blank"
                                style="text-decoration:none;color: #009EF7">
                                {{ config('app.url') }}
                            </a>
                        </div>
                        <div style="padding-bottom: 10px">
                            Salam Hormat,
                            <br>
                            Rekonek
                            </br>
                        </div>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>
