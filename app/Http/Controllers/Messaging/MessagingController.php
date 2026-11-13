<?php

namespace App\Http\Controllers\Messaging;

use App\Http\Controllers\Controller;
use App\Http\Controllers\Messaging\Concerns\ActsAsMessagingParty;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\MessageAttachment;
use App\Services\Messaging\ConversationProvisioner;
use App\Services\Messaging\ConversationService;
use App\Services\Messaging\InboxFeed;
use App\Services\Messaging\MessagingNotifier;
use App\Support\Messaging\MessagingArea;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Read and write in chats that already exist. There is deliberately no way
 * to start a chat here: tenancy and job chats are opened by the system.
 */
class MessagingController extends Controller
{
    use ActsAsMessagingParty;

    public function __construct(
        private readonly ConversationService $conversations,
        private readonly ConversationProvisioner $provisioner,
        private readonly InboxFeed $feed,
        private readonly MessagingNotifier $notifier,
    ) {}

    public function index(Request $request)
    {
        try {
            // Tenancies and jobs from before messaging, or changed without model events.
            $this->provisioner->syncForParty($this->party($request));
        } catch (\Throwable $exception) {
            Log::warning('Messaging sync on inbox failed', ['error' => $exception->getMessage()]);
        }

        return $this->listView($request, false);
    }

    public function archived(Request $request)
    {
        return $this->listView($request, true);
    }

    public function show(Request $request, Conversation $conversation)
    {
        $party = $this->party($request);
        $conversation = $this->conversationFor($request, $conversation);
        $conversation = $this->provisioner->syncContext($conversation);
        abort_unless($conversation->hasParticipant($party), 404);

        $lastRead = $this->conversations->lastReadBy($conversation, $party, $request->user());
        $messages = $conversation->messages()->with(['attachments', 'author'])->orderByDesc('id')->limit(200)->get()->reverse()->values();
        $this->conversations->markRead($conversation, $party, $request->user());

        return view('messaging.show', $this->shared($request, $conversation->isActive()) + [
            'conversation' => $conversation,
            'about' => $this->feed->describe($conversation, $party),
            'messages' => $messages,
            'firstUnreadId' => $messages->first(fn (Message $m) => $m->id > $lastRead && ! $m->sentBy($party))?->id,
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
            ])->render())->implode(''),
            'last_id' => (int) ($messages->last()?->id ?? $after),
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
            return $this->conversations->post($conversation, $party, $request->user(), $request->input('body'), $request->file('files', []))->id;
        }, 'Sent.', $this->routeTo($request, 'show', $conversation).'#latest');
    }

    public function download(Request $request, Conversation $conversation, MessageAttachment $attachment)
    {
        $conversation = $this->conversationFor($request, $conversation);
        $message = $attachment->message;
        abort_unless($message && (int) $message->conversation_id === (int) $conversation->id, 404);
        $path = $this->conversations->attachmentPath($attachment);
        abort_unless(Storage::disk('local')->exists($path), 404);

        $headers = ['X-Content-Type-Options' => 'nosniff', 'Cache-Control' => 'private, no-store'];
        if ($attachment->isImage() && $request->boolean('inline')) {
            return Storage::disk('local')->response($path, $attachment->original_name, $headers + ['Content-Type' => $attachment->mime ?: 'application/octet-stream']);
        }

        return Storage::disk('local')->download($path, $attachment->original_name, $headers);
    }

    /** Emails ResiSquare support. There is no in-app moderation. */
    public function report(Request $request, Conversation $conversation)
    {
        $party = $this->party($request);
        $conversation = $this->conversationFor($request, $conversation);
        $data = $request->validate(['note' => ['required', 'string', 'max:1000']]);
        $this->notifier->chatReported($conversation, $request->user(), $party, $data['note']);

        return redirect($this->routeTo($request, 'show', $conversation))
            ->with('messaging_status', 'Thanks. ResiSquare support has your report and will look into it.');
    }

    public function unread(Request $request)
    {
        return response()->json([
            'unread' => $this->conversations->unreadCount($this->party($request), $request->user()),
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
    private function shared(Request $request, bool $withActiveList = false): array
    {
        $party = $this->party($request);

        return MessagingArea::layoutFlags($this->area($request)) + [
            'party' => $party,
            'sidebarItems' => $withActiveList ? $this->feed->items($party, $request->user(), $this->area($request)) : collect(),
        ];
    }
}
