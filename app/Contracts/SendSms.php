<?php

namespace App\Contracts;

interface SendSms
{
    /**
     * Send an SMS message.
     *
     * @param  string  $to          Recipient phone with country code e.g. +919876543210
     * @param  string  $from        Sender name / app name
     * @param  string  $text        Message body
     * @param  string|null  $template_id  DLT template ID (India only)
     */
    public function send(string $to, string $from, string $text, ?string $template_id = null): void;
}
