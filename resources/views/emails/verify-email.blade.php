{{--
    GC-Stats — Verify-email notification (custom HTML, table-based for
    client compatibility — deliberately not Laravel's default markdown
    mail component).

    Copyright (c) 2026 Alice Alleman — GC-Stats-Website
    License: https://github.com/GC-Stats/Website/blob/main/LICENSE (GC-Stats License v1.0)
    Repository: https://github.com/GC-Stats/Website
--}}
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('auth.verify_email.mail.subject') }}</title>
</head>
<body style="margin:0; padding:0; background-color:#0A0A0A; font-family:Helvetica,Arial,sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#0A0A0A;">
        <tr>
            <td align="center" style="padding:40px 16px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="max-width:480px;">

                    <tr>
                        <td align="center" style="padding-bottom:32px;">
                            <span style="font-size:20px; font-weight:900; letter-spacing:-0.03em; color:#ffffff; text-transform:uppercase;">
                                GC <span style="color:#e4ae22;">Stats</span>
                            </span>
                        </td>
                    </tr>

                    <tr>
                        <td style="background-color:#111111; border:1px solid rgba(255,255,255,0.08); border-radius:8px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="height:3px; background-color:#e4ae22; border-radius:8px 8px 0 0;"></td>
                                </tr>
                                <tr>
                                    <td style="padding:36px 32px;">
                                        <p style="margin:0 0 8px; font-size:11px; font-weight:700; letter-spacing:0.15em; text-transform:uppercase; color:#e4ae22;">
                                            {{ __('auth.verify_email.mail.eyebrow') }}
                                        </p>
                                        <h1 style="margin:0 0 20px; font-size:22px; font-weight:900; letter-spacing:-0.02em; color:#ffffff;">
                                            {{ __('auth.verify_email.mail.heading', ['name' => $user->name]) }}
                                        </h1>
                                        <p style="margin:0 0 28px; font-size:14px; line-height:1.6; color:#9ca3af;">
                                            {{ __('auth.verify_email.mail.body') }}
                                        </p>

                                        <table role="presentation" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="border-radius:4px; background-color:#e4ae22;">
                                                    <a href="{{ $url }}" target="_blank"
                                                       style="display:inline-block; padding:14px 28px; font-size:12px; font-weight:800; letter-spacing:0.1em; text-transform:uppercase; color:#000000; text-decoration:none;">
                                                        {{ __('auth.verify_email.mail.action') }}
                                                    </a>
                                                </td>
                                            </tr>
                                        </table>

                                        <p style="margin:28px 0 0; font-size:12px; line-height:1.6; color:#6b7280;">
                                            {{ __('auth.verify_email.mail.expires', ['minutes' => config('auth.verification.expire', 60)]) }}
                                        </p>

                                        <p style="margin:20px 0 0; padding-top:20px; border-top:1px solid rgba(255,255,255,0.08); font-size:11px; line-height:1.6; color:#4b5563; word-break:break-all;">
                                            {{ __('auth.verify_email.mail.fallback_link') }}<br>
                                            <a href="{{ $url }}" style="color:#6b7280;">{{ $url }}</a>
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding-top:24px;" align="center">
                            <p style="margin:0; font-size:11px; line-height:1.6; color:#4b5563;">
                                {{ __('auth.verify_email.mail.ignore') }}
                            </p>
                        </td>
                    </tr>

                </table>
            </td>
        </tr>
    </table>
</body>
</html>
