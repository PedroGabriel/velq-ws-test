<?php

namespace App\Mail;

use App\Velq\Mail\Inbound;
use Illuminate\Support\Facades\Log;

/**
 * InboundHandler - the example app-owned handler Velq invokes when mail arrives for this DO.
 *
 * Wire it up in velq.json:
 *
 *     "email": { "enabled": true, "handler": "App\\Mail\\InboundHandler", "catch_all": false }
 *
 * Velq receives the message on its own SMTP listener, parses it host-side, stores the raw RFC822 in
 * this DO's Files binding, then wakes this DO from snapshot and calls handle() with a pre-parsed
 * Inbound event - no MIME parsing in PHP. The class name above is the only contract; the method name
 * is `handle($inbound)`.
 *
 * TRUST: from/to/cc/subject/bodies are sender-controlled display data. The only trust signal is
 * $inbound->verdicts (host-computed); branch on $inbound->passedAuth() / verdicts, never on the From
 * address. The plus-addressing tag ($inbound->mailboxHash, e.g. "ticket-42" in
 * support+ticket-42@app.velq.dev) is opaque - use it to fan out work, never as a path or storage key.
 *
 * IMPORTANT: this handler MUST NOT send mail to the internet. Velq's inbound trigger does NOT grant
 * outbound send - the guest's MAIL_* binding is the caught dev mailer, so a Mail::to(...)->send() here
 * is captured, never delivered. Auto-replies to a forged sender would be backscatter; that is by design.
 */
class InboundHandler
{
    public function handle(Inbound $inbound): void
    {
        // Reject mail that did not pass sender authentication. Velq's host already gates a wake on
        // SPF-or-DKIM, but the verdict block lets the app apply its own (e.g. require full DMARC).
        if (! $inbound->passedAuth()) {
            Log::warning('inbound mail failed auth', [
                'from'     => $inbound->fromEmail(),
                'verdicts' => $inbound->verdicts,
            ]);

            return;
        }

        // Branch on the plus-addressing tag for free intra-DO sub-routing (opaque display data).
        // e.g. support+ticket-42@app.velq.dev -> mailboxHash "ticket-42".
        $tag = $inbound->mailboxHash;

        Log::info('inbound mail received', [
            'from'        => $inbound->fromEmail(),
            'to'          => $inbound->recipient(),
            'tag'         => $tag,
            'subject'     => $inbound->subject,
            'attachments' => count($inbound->attachments),
            'raw_url'     => $inbound->rawUrl,
        ]);

        // A real app would persist the message, open a ticket, run an agent, etc. The raw RFC822 is
        // already durably stored in this DO's Files binding at $inbound->rawUrl; large attachments are
        // referenced by url in $inbound->attachments (fetch on demand), not inlined into this event.
    }
}
