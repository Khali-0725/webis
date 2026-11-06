<?php

namespace App\Mail;

use Illuminate\Support\Facades\Http;
use Symfony\Component\Mailer\Exception\TransportException;
use Symfony\Component\Mailer\SentMessage;
use Symfony\Component\Mailer\Transport\AbstractTransport;
use Symfony\Component\Mime\MessageConverter;
use Throwable;

class GoogleAppsScriptTransport extends AbstractTransport
{
    public function __construct(private string $endpoint, private string $secret)
    {
        parent::__construct();
    }

    protected function doSend(SentMessage $message): void
    {
        if (! preg_match('~\Ahttps://script\.google\.com/macros/s/[a-zA-Z0-9_-]+/exec\z~', $this->endpoint)
            || strlen($this->secret) < 32) {
            throw new TransportException('Configure GOOGLE_APPS_SCRIPT_URL and GOOGLE_APPS_SCRIPT_SECRET before sending mail.');
        }

        $email = MessageConverter::toEmail($message->getOriginalMessage());
        $recipients = $message->getEnvelope()->getRecipients();
        // WEBIS sends individual notifications; reject unsupported mail instead of dropping recipients or attachments.
        if (count($recipients) !== 1 || $email->getAttachments() || $email->getCc() || $email->getBcc()) {
            throw new TransportException('Google Apps Script mail supports one recipient without attachments, CC or BCC.');
        }

        $payload = json_encode([
            'timestamp' => now()->timestamp,
            'id' => bin2hex(random_bytes(16)),
            'to' => $recipients[0]->getAddress(),
            'subject' => $email->getSubject(),
            'text' => $email->getTextBody() ?? strip_tags($email->getHtmlBody() ?? ''),
            'html' => $email->getHtmlBody(),
            'name' => $email->getFrom()[0]->getName() ?: 'WEBIS',
        ], JSON_THROW_ON_ERROR);

        try {
            // Google redirects ContentService responses to script.googleusercontent.com using GET.
            $response = Http::acceptJson()->connectTimeout(10)->timeout(30)->post($this->endpoint, [
                'payload' => $payload,
                'signature' => hash_hmac('sha256', $payload, $this->secret),
            ]);
        } catch (Throwable) {
            // Do not include request bodies, OTPs or signed payloads in exception logs.
            throw new TransportException('Google Apps Script mail request failed. Check connectivity and deployment access.');
        }

        if (! $response->successful() || $response->json('ok') !== true) {
            $reason = $response->json('error');
            $knownReasons = ['unauthorized', 'invalid_request', 'quota_exceeded', 'busy', 'delivery_failed', 'delivery_uncertain'];
            $reason = is_string($reason) && in_array($reason, $knownReasons, true) ? $reason : 'invalid_response';
            throw new TransportException('Google Apps Script mail failed: '.$reason.'. Check the Google deployment and daily quota.');
        }
    }

    public function __toString(): string
    {
        return 'google_apps_script';
    }
}
