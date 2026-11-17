<?php

namespace App\Services\Messaging;

use RuntimeException;

/** A rule stopped the action. The message is safe to show to the person. */
class MessagingException extends RuntimeException
{
}
