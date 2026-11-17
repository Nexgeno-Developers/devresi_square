<?php

namespace App\Http\Controllers\Messaging\Concerns;

use App\Models\Conversation;
use App\Services\Messaging\MessagingException;
use App\Support\Messaging\MessagingArea;
use App\Support\Messaging\Party;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

trait ActsAsMessagingParty
{
    protected function party(Request $request): Party
    {
        $party = $request->attributes->get('messaging.party');
        abort_unless($party instanceof Party, 403);

        return $party;
    }

    protected function area(Request $request): string
    {
        return (string) $request->attributes->get('messaging.area');
    }

    /** Non-participants get 404, so ids never reveal that a conversation exists. */
    protected function conversationFor(Request $request, Conversation $conversation): Conversation
    {
        $conversation->load('participants');
        abort_unless($conversation->hasParticipant($this->party($request)), 404);

        return $conversation;
    }

    protected function routeTo(Request $request, string $name, mixed $parameters = []): string
    {
        return MessagingArea::route($this->area($request), $name, $parameters);
    }

    protected function attempt(Request $request, callable $action, string $success, string|\Closure|null $redirect = null): RedirectResponse|JsonResponse
    {
        try {
            $result = $action();
        } catch (MessagingException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            return back()->withInput()->with('messaging_error', $exception->getMessage());
        }
        if ($request->expectsJson()) {
            return response()->json(['message' => $success, 'result' => is_scalar($result) ? $result : null]);
        }
        $target = $redirect instanceof \Closure ? $redirect($result) : $redirect;

        return ($target ? redirect($target) : back())->with('messaging_status', $success);
    }
}
