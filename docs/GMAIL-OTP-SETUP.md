# Libreng Gmail OTP sa Render

Gumagamit ang WEBIS ng Google Apps Script para magpadala ng email mula sa iyong
Gmail account. HTTPS ang koneksiyon mula Render, kaya hindi kailangan ang SMTP,
Google App Password, Resend, sariling domain, o bagong hosting.

Sa personal Google account, hanggang **100 email recipients bawat araw** ang
Apps Script quota. Bawat pagpapadala ng OTP sa isang recipient ay bawas sa quota.
Kasama rin sa quota ang ibang Apps Script emails ng parehong Google account.
Maaaring baguhin ng Google ang limits. Hindi garantiya ang inbox placement;
tingnan din ang Spam folder.

## 1. Gumawa ng Google project

1. Buksan ang <https://script.google.com/> gamit ang Gmail na magiging sender.
   Kung maraming Google accounts ang naka-sign in, gumamit ng InPrivate/incognito
   window at mag-sign in lang sa sender account. May kilalang Apps Script
   authorization issues ang multi-login, kabilang ang failed approval redirects.
2. Piliin ang **New project** at pangalanang **WEBIS Gmail OTP**.
3. Palitan ang laman ng `Code.gs` gamit ang buong code sa
   [google-apps-script/Code.gs](google-apps-script/Code.gs). I-save.

Kung may inihanda nang WEBIS project sa browser, gamitin iyon; huwag gumawa ng
duplicate project.

## 2. Itakda ang shared secret

Gumawa ng random secret sa Laragon terminal:

```sh
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Sa Google Apps Script, buksan ang **Project Settings → Script properties → Add
script property**:

| Property | Value |
| --- | --- |
| `WEBIS_MAIL_SECRET` | Ang generated secret |

I-save. Kailangan ang parehong secret sa Render. Huwag ilagay sa frontend,
GitHub, screenshots, o chat. Hindi ito ang Google App Password.

## 3. Pahintulutan at i-deploy sa Google

1. Sa **Editor**, piliin ang `authorizeMail` sa function dropdown at pindutin
   ang **Run**.
2. Suriin ang Google permission screen. Kailangan lang ng script ang permission
   na mag-send ng email; hindi nito kailangang basahin ang Gmail inbox.
   Ikaw ang magbibigay ng pahintulot sa Google account mo.
3. Piliin ang **Deploy → New deployment → Select type → Web app**.
4. **Execute as: Me**. **Who has access: Anyone**. Kailangang matawag ito ng
   Render nang walang interactive Google sign-in. Kahit public ang URL,
   kailangan pa rin ng valid signature mula sa backend bago mag-send.
5. I-deploy at kopyahin ang **Web app URL**, na nagtatapos sa `/exec`.
   Huwag gamitin ang `/dev` URL o Script ID.

Kung walang **Anyone** option dahil sa account restrictions, hindi magagamit ang
deployment na iyon mula sa Render. Gamitin ang personal Google account na
pinapahintulutan ang public web apps.

## 4. Render settings

Kailangang ma-deploy muna ang backend code na may `google_apps_script` mailer.
Sa **Render → WEBIS backend → Environment**, itakda:

```dotenv
MAIL_MAILER=google_apps_script
GOOGLE_APPS_SCRIPT_URL=https://script.google.com/macros/s/YOUR_DEPLOYMENT_ID/exec
GOOGLE_APPS_SCRIPT_SECRET=THE_SAME_SECRET_FROM_SCRIPT_PROPERTIES
MAIL_FROM_ADDRESS=YOUR_GOOGLE_SENDER@gmail.com
MAIL_FROM_NAME=WEBIS
```

Ang placeholders sa itaas ay papalitan ng tunay mong values. Ang Google account
na nag-deploy ng script ang aktuwal na sender; hindi maaaring gamitin ang
`MAIL_FROM_ADDRESS` para magpanggap na ibang Gmail account.

Piliin ang **Save, rebuild, and deploy**. Nagre-refresh ng Laravel config cache
ang kasalukuyang Docker startup. Hindi ginagamit ng mailer na ito ang lumang
`MAIL_HOST`, `MAIL_PORT`, `MAIL_USERNAME`, `MAIL_PASSWORD`, o `RESEND_API_KEY`.

Para gamitin din locally, ilagay ang parehong settings sa `backend/.env`, saka:

```sh
php artisan config:clear
```

## 5. Subukan ang tunay na OTP

1. Sa WEBIS **Forgot Password**, gamitin ang email ng existing, non-deleted account.
2. Tingnan ang recipient inbox at Spam. Dapat may six-digit reset code.
3. Ilagay ang code sa loob ng 10 minutes at mag-set ng bagong password.
4. Subukang mag-login gamit ang bagong password.

Huwag baguhin ang password ng ibang tao para mag-test. Gumamit ng sarili mong
test account. Hindi kailangang Google account ang recipient, at hindi kailangang
recipient mismo ang mag-authorize sa Google.

Kapag hindi dumating:

| Error sa backend log | Susuriin |
| --- | --- |
| `unauthorized` | Pareho ba ang secret sa Google at Render? Minimum 32 characters. |
| `invalid_response` | Tama ba ang `/exec` URL, **Anyone**, at deployment version? Hindi sapat ang HTML sign-in page. |
| `quota_exceeded` | Ubos na ang daily quota; maghintay hanggang ma-reset. |
| `delivery_failed` | Google authorization at Apps Script execution history. Walang OTP o secret sa relay logs. |
| `busy` | May kasabay na request; subukan ulit. |
| `delivery_uncertain` | Naulit ang request habang hindi tiyak ang unang send. Tingnan muna ang inbox. |

Walang automatic resend kapag pumalya ang HTTP request, para maiwasang magpadala
ng duplicate OTP. Posibleng natanggap ng Google ang mail bago mag-timeout ang
backend. Kung nireject ng WEBIS ang code, mag-request ng bago; 60 seconds ang
kasalukuyang resend cooldown para sa successful requests.

Kapag inedit ang Google script, gamitin ang **Deploy → Manage deployments → Edit →
Version: New version → Deploy** para gamitin ng `/exec` URL ang bagong code.

## Verification

```sh
cd backend
php artisan test --compact
node --test tests/google-apps-script.test.cjs
```

Backend tests fake the Google HTTP response while exercising OTP generation,
email rendering, signing, verification and password reset. Script tests run the
actual `Code.gs` with simulated Google services to check signing, expiry, replay
protection, quota errors and delivery failures. These checks do not prove live
email delivery; the real-account test above is still required after deployment.

Sources: [Google web apps](https://developers.google.com/apps-script/guides/web),
[MailApp](https://developers.google.com/apps-script/reference/mail/mail-app),
[Google quotas](https://developers.google.com/apps-script/guides/services/quotas).
[Multi-account troubleshooting](https://developers.google.com/apps-script/guides/support/troubleshooting#issues_with_multiple_google_accounts).
