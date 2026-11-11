<?php

namespace App\Http\Controllers\Messaging;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Messaging\Concerns\ActsAsMessagingParty;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageReport;
use App\Models\MessageRequest;
use App\Services\Messaging\MessageRequestService;
use App\Services\Messaging\SafetyService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SafetyController extends Controller
{
    use ActsAsMessagingParty;

    public function __construct(
        private readonly SafetyService $safety,
        private readonly MessageRequestService $requests,
    ) {}

    public function blockRequest(Request $request, MessageRequest $messageRequest)
    {
        $party = $this->party($request);
        abort_unless($messageRequest->isTo($party), 404);

        return $this->attempt($request, function () use ($request, $messageRequest, $party) {
            if ($messageRequest->isPending()) {
                $this->requests->decline($messageRequest, $party, $request->user(), MessageRequest::BLOCKED);
            }
            $this->safety->block($party, $messageRequest->fromParty(), $request->user());
        }, 'Blocked. They can’t send you requests or messages.', $this->routeTo($request, 'requests'));
    }

    public function reportRequest(Request $request, MessageRequest $messageRequest)
    {
        $party = $this->party($request);
        abort_unless($messageRequest->isTo($party), 404);
        $data = $this->validateReport($request);

        return $this->attempt($request, fn () => $this->safety->report($party, $request->user(), $data['category'], $data['note'] ?? null, null, null, $messageRequest),
            'Thanks. ResiSquare will review this report.', $this->routeTo($request, 'requests'));
    }

    public function blockConversation(Request $request, Conversation $conversation)
    {
        $party = $this->party($request);
        $conversation = $this->conversationFor($request, $conversation);
        $other = $conversation->otherParty($party);
        abort_unless($other !== null, 404);

        return $this->attempt($request, fn () => $this->safety->block($party, $other, $request->user(), $request->input('reason')),
            'Blocked. They can’t message you any more.', $this->routeTo($request, 'show', $conversation));
    }

    public function unblockConversation(Request $request, Conversation $conversation)
    {
        $party = $this->party($request);
        $conversation = $this->conversationFor($request, $conversation);
        $other = $conversation->otherParty($party);
        abort_unless($other !== null, 404);

        return $this->attempt($request, fn () => $this->safety->unblock($party, $other, $request->user()),
            'Unblocked.', $this->routeTo($request, 'show', $conversation));
    }

    public function reportConversation(Request $request, Conversation $conversation)
    {
        $party = $this->party($request);
        $conversation = $this->conversationFor($request, $conversation);
        $data = $this->validateReport($request) + $request->validate(['message' => ['nullable', 'string', 'size:26']]);
        $message = null;
        if (! empty($data['message'])) {
            $message = Message::withTrashed()->where('conversation_id', $conversation->id)->where('public_id', $data['message'])->first();
            abort_unless($message !== null, 404);
        }

        return $this->attempt($request, fn () => $this->safety->report($party, $request->user(), $data['category'], $data['note'] ?? null, $conversation, $message),
            'Thanks. ResiSquare will review this report.', $this->routeTo($request, 'show', $conversation));
    }

    private function validateReport(Request $request): array
    {
        return $request->validate([
            'category' => ['required', Rule::in(MessageReport::CATEGORIES)],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
    }
}
