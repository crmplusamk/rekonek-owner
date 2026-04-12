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
                    <a href="{{ env('CRM_CLIENT_HOST') }}" class="logo">
                        <img src="{{ URL::to('assets/images/logo.png') }}" style="width: 150px; height:70px" alt="logo" />
                    </a>
                </td>
            </tr>
            <tr>
                <td align="left" valign="center" style="padding-bottom: 40px;">
                    <div style="text-align:left; margin: 0 20px; padding: 40px; background-color:#ffffff; border-radius: 6px">
                        <div style="padding-bottom: 24px; font-size: 17px;">
                            <strong>Halo {{ $contactName }}</strong>
                        </div>

                        <div style="padding-bottom: 24px;">
                            Langganan Anda akan segera berakhir pada <strong>{{ $expiredDate }}</strong>. Kami telah menerbitkan invoice perpanjangan untuk memastikan layanan Anda tetap berjalan tanpa gangguan.
                        </div>

                        <table style="width:100%; border-collapse:collapse; background-color:#f9f9f9; border-radius:6px; margin-bottom:24px;">
                            <tr>
                                <td style="padding:16px 20px; border-bottom:1px solid #eeeeee; font-size:13px; color:#5E6278; width:50%;">
                                    Invoice
                                </td>
                                <td style="padding:16px 20px; border-bottom:1px solid #eeeeee; font-size:13px; font-weight:600;">
                                    {{ $invoiceCode }}
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:16px 20px; border-bottom:1px solid #eeeeee; font-size:13px; color:#5E6278;">
                                    Batas Pembayaran
                                </td>
                                <td style="padding:16px 20px; border-bottom:1px solid #eeeeee; font-size:13px; font-weight:600;">
                                    {{ $dueDate }}
                                </td>
                            </tr>
                            <tr>
                                <td style="padding:16px 20px; font-size:13px; color:#5E6278; font-weight:600;">
                                    Total Pembayaran
                                </td>
                                <td style="padding:16px 20px; font-size:16px; font-weight:700; color:#2465FF;">
                                    {{ $invoiceTotal }}
                                </td>
                            </tr>
                        </table>

                        <div style="padding-bottom: 30px;">
                            Silakan lakukan pembayaran sebelum batas waktu yang ditentukan agar layanan Anda tidak terganggu.
                        </div>

                        <div style="padding-bottom: 40px; text-align:center;">
                            <a href="{{ env('CRM_CLIENT_HOST') }}/billing/invoice"
                                rel="noopener"
                                style="text-decoration:none;display:inline-block;text-align:center;padding:0.75575rem 1.3rem;font-size:0.925rem;line-height:1.5;border-radius:0.35rem;color:#ffffff;background-color:#2465FF;border:0px;margin-right:0.75rem!important;font-weight:600!important;outline:none!important;vertical-align:middle"
                                target="_blank">
                                Lihat Invoice & Bayar
                            </a>
                        </div>

                        <div style="padding-bottom: 10px">
                            Salam hormat,
                            <br>
                            Rekonek
                        </div>
                    </div>
                </td>
            </tr>
        </tbody>
    </table>
</div>
