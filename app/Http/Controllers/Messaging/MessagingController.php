<?php

namespace App\Http\Controllers\Messaging;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Messaging\Concerns\ActsAsMessagingParty;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Models\MessageRequest;
use App\Services\Messaging\ConversationService;
use App\Services\Messaging\InboxFeed;
use App\Services\Messaging\MessageRequestService;
use App\Services\Messaging\MessagingDirectory;
use App\Services\Messaging\MessagingException;
use App\Services\Messaging\MessagingGuard;
use App\Support\Messaging\MessagingArea;
use App\Support\Messaging\Party;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class MessagingController extends Controller
{
    use ActsAsMessagingParty;

    public function __construct(
        private readonly ConversationService $conversations,
        private readonly MessageRequestService $requests,
        private readonly MessagingDirectory $directory,
        private readonly InboxFeed $feed,
        private readonly MessagingGuard $guard,
    ) {}

    public function index(Request $request)
    {
        return $this->listView($request, false);
    }

    public function archived(Request $request)
    {
        return $this->listView($request, true);
    }

    public function requests(Request $request)
    {
        $party = $this->party($request);
        $incoming = MessageRequest::query()
            ->where('to_type', $party->type)->where('to_id', $party->id)
            ->where('status', MessageRequest::PENDING)
            ->latest()->get();
        $sent = MessageRequest::query()
            ->where('from_type', $party->type)->where('from_id', $party->id)
            ->whereIn('status', [MessageRequest::PENDING, MessageRequest::DECLINED, MessageRequest::EXPIRED, MessageRequest::BLOCKED, MessageRequest::WITHDRAWN])
            ->latest('updated_at')->limit(50)->get();

        return view('messaging.requests', $this->shared($request) + [
            'incoming' => $incoming,
            'sent' => $sent,
        ]);
    }

    public function create(Request $request)
    {
        $party = $this->party($request);
        $target = null;
        if ($request->filled('contractor') && $party->isAccount()) {
            $target = $this->directory->accountContractor($party, (int) $request->query('contractor'));
            $existing = $this->conversations->forPair($party, $target);
            if ($existing) {
                return redirect($this->routeTo($request, 'show', $existing));
            }
        }

        return view('messaging.create', $this->shared($request) + [
            'target' => $target,
            'contractorId' => $target ? (int) $request->query('contractor') : null,
        ]);
    }

    public function storeRequest(Request $request)
    {
        $party = $this->party($request);
        $data = $request->validate([
            'email' => ['nullable', 'required_without:contractor', 'email:rfc', 'max:191'],
            'contractor' => ['nullable', 'integer'],
            'intro' => ['nullable', 'string', 'max:'.config('messaging.intro_max')],
        ]);

        if (! empty($data['contractor'])) {
            $target = $this->directory->accountContractor($party, (int) $data['contractor']);

            return $this->attempt($request, function () use ($party, $target, $request, $data) {
                return $this->requests->send($party, $target, $request->user(), $data['intro'] ?? null);
            }, 'Request sent. You can chat once they accept.', fn ($result) => $result instanceof Conversation
                ? $this->routeTo($request, 'show', $result)
                : $this->routeTo($request, 'requests'));
        }

        return $this->attempt($request, function () use ($party, $request, $data) {
            $this->requests->sendByEmail($party, $request->user(), $data['email'], $data['intro'] ?? null);
        }, 'If this person uses ResiSquare, they’ll get your request.', $this->routeTo($request, 'requests'));
    }

    public function accept(Request $request, MessageRequest $messageRequest)
    {
        $party = $this->party($request);
        abort_unless($messageRequest->isTo($party), 404);

        return $this->attempt($request, fn () => $this->requests->accept($messageRequest, $party, $request->user()),
            'Request accepted. You can chat now.', fn ($conversation) => $this->routeTo($request, 'show', $conversation));
    }

    public function decline(Request $request, MessageRequest $messageRequest)
    {
        $party = $this->party($request);
        abort_unless($messageRequest->isTo($party), 404);

        return $this->attempt($request, fn () => $this->requests->decline($messageRequest, $party, $request->user()),
            'Request declined.', $this->routeTo($request, 'requests'));
    }

    public function withdraw(Request $request, MessageRequest $messageRequest)
    {
        $party = $this->party($request);
        abort_unless($messageRequest->isFrom($party), 404);

        return $this->attempt($request, fn () => $this->requests->withdraw($messageRequest, $party, $request->user()),
            'Request withdrawn.', $this->routeTo($request, 'requests'));
    }

    /** "Message …" buttons and email links for people who already work together. */
    public function start(Request $request, string $context, int $id, ?int $user = null)
    {
        $party = $this->party($request);
        $target = $this->directory->relationshipTarget($party, $context, $id, $user);

        try {
            $conversation = $this->requests->openFromRelationship($party, $target['party'], $request->user(), $target['origin'], $target['origin_id']);
        } catch (MessagingException $exception) {
            return redirect($this->routeTo($request, 'index'))->with('messaging_error', $exception->getMessage());
        }

        return redirect($this->routeTo($request, 'show', $conversation));
    }

    public function show(Request $request, Conversation $conversation)
    {
        $party = $this->party($request);
        $conversation = $this->conversationFor($request, $conversation);
        $this->conversations->refreshStatus($conversation);
        $other = $conversation->otherParty($party);
        $lastRead = $this->conversations->lastReadBy($conversation, $party, $request->user());
        $messages = $conversation->messages()->withTrashed()->with(['attachments', 'author'])->orderByDesc('id')->limit(200)->get()->reverse()->values();
        $this->conversations->markRead($conversation, $party, $request->user());

        return view('messaging.show', $this->shared($request, true) + [
            'conversation' => $conversation,
            'other' => $other,
            'messages' => $messages,
            'firstUnreadId' => $messages->first(fn (Message $m) => $m->id > $lastRead && ! $m->sentBy($party))?->id,
            'seenUpTo' => $this->conversations->seenUpTo($conversation, $party),
            'iBlocked' => $other ? $this->guard->hasBlocked($party, $other) : false,
            'blocked' => $other ? $this->guard->isBlocked($party, $other) : false,
            'participant' => $conversation->participantFor($party),
            'canAttach' => $conversation->unmasked_at !== null,
        ]);
    }

    public function poll(Request $request, Conversation $conversation)
    {
        $party = $this->party($request);
        $conversation = $this->conversationFor($request, $conversation);
        $after = max(0, (int) $request->query('after', 0));
        $messages = $this->conversations->messagesAfter($conversation, $after);
        if ($messages->isNotEmpty()) {
            $this->conversations->markRead($conversation, $party, $request->user());
        }

        return response()->json([
            'html' => $messages->map(fn (Message $message) => view('messaging.partials.message', [
                'message' => $message,
                'party' => $party,
                'conversation' => $conversation,
                'area' => $this->area($request),
                'seenUpTo' => 0,
            ])->render())->implode(''),
            'last_id' => (int) ($messages->last()?->id ?? $after),
            'seen_up_to' => $this->conversations->seenUpTo($conversation, $party),
            'active' => $conversation->fresh()->isActive(),
        ]);
    }

    public function send(Request $request, Conversation $conversation)
    {
        $party = $this->party($request);
        $conversation = $this->conversationFor($request, $conversation);
        $request->validate([
            'body' => ['nullable', 'string', 'max:'.config('messaging.body_max')],
            'files' => ['nullable', 'array', 'max:'.config('messaging.attachments.max_files')],
            'files.*' => ['file', 'max:'.config('messaging.attachments.max_kb')],
        ]);

        return $this->attempt($request, function () use ($conversation, $party, $request) {
            $message = $this->conversations->post($conversation, $party, $request->user(), $request->input('body'), $request->file('files', []));

            return $message->id;
        }, 'Sent.', $this->routeTo($request, 'show', $conversation).'#latest');
    }

    public function unsend(Request $request, Conversation $conversation, Message $message)
    {
        $party = $this->party($request);
        $this->conversationFor($request, $conversation);

        return $this->attempt($request, fn () => $this->conversations->unsend($message, $party, $request->user()),
            'Message taken back.', $this->routeTo($request, 'show', $conversation));
    }

    public function download(Request $request, Conversation $conversation, MessageAttachment $attachment)
    {
        $conversation = $this->conversationFor($request, $conversation);
        $message = $attachment->message()->withTrashed()->first();
        abort_unless($message && (int) $message->conversation_id === (int) $conversation->id && ! $message->trashed() && ! $message->isRemoved(), 404);
        $path = $this->conversations->attachmentPath($attachment);
        abort_unless(Storage::disk('local')->exists($path), 404);

        $headers = ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'];
        if ($attachment->isImage() && $request->boolean('inline')) {
            return Storage::disk('local')->response($path, $attachment->original_name, $headers + ['Content-Type' => $attachment->mime ?: 'application/octet-stream']);
        }

        return Storage::disk('local')->download($path, $attachment->original_name, $headers);
    }

    public function archive(Request $request, Conversation $conversation)
    {
        $party = $this->party($request);
        $conversation = $this->conversationFor($request, $conversation);
        $archive = ! $request->boolean('restore');
        $this->conversations->setArchived($conversation, $party, $archive);

        return redirect($this->routeTo($request, $archive ? 'index' : 'show', $archive ? [] : $conversation))
            ->with('messaging_status', $archive ? 'Conversation archived. New messages bring it back.' : 'Conversation moved back to your inbox.');
    }

    public function mute(Request $request, Conversation $conversation)
    {
        $party = $this->party($request);
        $conversation = $this->conversationFor($request, $conversation);
        $mute = ! $request->boolean('unmute');
        $this->conversations->setMuted($conversation, $party, $mute);

        return back()->with('messaging_status', $mute ? 'Notifications for this conversation are off.' : 'Notifications for this conversation are on.');
    }

    public function unread(Request $request)
    {
        $party = $this->party($request);

        return response()->json([
            'unread' => $this->feed->unreadTotal($party, $request->user(), $this->area($request), $this->conversations),
            'requests' => MessageRequest::query()->where('to_type', $party->type)->where('to_id', $party->id)->where('status', MessageRequest::PENDING)->count(),
        ]);
    }

    private function listView(Request $request, bool $archived)
    {
        $party = $this->party($request);

        return view('messaging.index', $this->shared($request) + [
            'items' => $this->feed->items($party, $request->user(), $this->area($request), $archived),
            'archived' => $archived,
        ]);
    }

    /** @return array<string, mixed> */
    private function shared(Request $request, bool $withList = false): array
    {
        $party = $this->party($request);

        return MessagingArea::layoutFlags($this->area($request)) + [
            'party' => $party,
            'pendingRequests' => MessageRequest::query()->where('to_type', $party->type)->where('to_id', $party->id)->where('status', MessageRequest::PENDING)->count(),
            'sidebarItems' => $withList ? $this->feed->items($party, $request->user(), $this->area($request)) : collect(),
        ];
    }
}
