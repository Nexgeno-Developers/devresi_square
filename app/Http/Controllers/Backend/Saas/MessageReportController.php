<?php

namespace App\Http\Controllers\Backend\Saas;

use App\Http\Controllers\Controller;
use App\Models\MessageReport;
use App\Models\MessagingPartyFlag;
use App\Services\Messaging\SafetyService;
use App\Support\AuditTrail;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** Super Admin moderation: reported content plus 10 messages either side, nothing else. */
class MessageReportController extends Controller
{
    public function __construct(private readonly SafetyService $safety) {}

    public function index(Request $request)
    {
        $status = $request->query('status', MessageReport::OPEN);
        $reports = MessageReport::query()
            ->when(in_array($status, [MessageReport::OPEN, MessageReport::ACTIONED, MessageReport::DISMISSED], true), fn ($q) => $q->where('status', $status))
            ->with('reporter')
            ->latest()
            ->paginate(30)
            ->withQueryString();
        $flags = MessagingPartyFlag::query()
            ->where(fn ($q) => $q->where('suspended_until', '>', now())->orWhere('paused_until', '>', now()))
            ->latest('updated_at')->limit(50)->get();

        return view('backend.saas.message-reports.index', compact('reports', 'status', 'flags'));
    }

    public function show(Request $request, MessageReport $report)
    {
        $context = $this->safety->moderationContext($report);
        AuditTrail::record(null, $request->user()->id, 'messaging.report.viewed', $report, [], ['messages_shown' => $context->count()]);

        return view('backend.saas.message-reports.show', [
            'report' => $report->load(['reporter', 'request']),
            'context' => $context,
        ]);
    }

    public function resolve(Request $request, MessageReport $report)
    {
        $data = $request->validate([
            'action' => ['required', Rule::in(['dismiss', 'warn', 'remove', 'suspend', 'lift'])],
            'note' => ['nullable', 'string', 'max:1000'],
            'days' => ['nullable', 'integer', 'min:1', 'max:3650'],
        ]);
        $this->safety->resolve($report, $request->user(), $data['action'], $data['note'] ?? null, $data['days'] ?? null);

        return redirect()->route('backend.saas.message-reports.show', $report)->with('success', 'Report updated.');
    }
}
