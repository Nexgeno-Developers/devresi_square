<?php

namespace App\Services\Repairs;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use InvalidArgumentException;

class RepairComplaintClassifier
{
    public function __construct(
        private readonly ?array $complaints = null,
        private readonly ?array $clocks = null,
        private readonly ?string $timezone = null,
    ) {
    }

    public function timezone(): string
    {
        return $this->timezone ?? (string) config('repair_sla.timezone', 'Europe/London');
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function complaints(): array
    {
        return $this->complaints ?? config('repair_sla.complaints', []);
    }

    /**
     * @return list<string>
     */
    public function codes(): array
    {
        return array_keys($this->complaints());
    }

    public function isWinter(CarbonInterface $at): bool
    {
        $local = CarbonImmutable::parse($at)->timezone($this->timezone());
        $monthDay = ((int) $local->month * 100) + (int) $local->day;

        return $monthDay >= 1001 || $monthDay <= 430;
    }

    public function winterStart(CarbonInterface $at): CarbonImmutable
    {
        $local = CarbonImmutable::parse($at)->timezone($this->timezone());
        $year = (int) $local->month >= 10 ? (int) $local->year : (int) $local->year - 1;

        return CarbonImmutable::create($year, 10, 1, 0, 0, 0, $this->timezone());
    }

    /**
     * @return array{
     *     complaint_code: string,
     *     priority: string,
     *     emergency_access: bool,
     *     reported_at: CarbonImmutable,
     *     sla_due_at: CarbonImmutable,
     *     make_safe_due_at: CarbonImmutable|null,
     *     snapshot: array<string, mixed>
     * }
     */
    public function classify(string $code, ?CarbonInterface $at = null): array
    {
        $at = CarbonImmutable::parse($at ?? now())->timezone($this->timezone());

        return $this->build($code, $at, $at);
    }

    /**
     * Upgrade an open summer heating complaint when winter starts.
     * The new clock runs from 1 October 00:00 Europe/London.
     * A winter classification is left unchanged.
     *
     * @param  array<string, mixed>  $snapshot
     * @return array<string, mixed>|null
     */
    public function heatingUpgrade(array $snapshot, CarbonInterface $at): ?array
    {
        if (($snapshot['complaint_code'] ?? null) !== 'heating_loss') {
            return null;
        }

        if (($snapshot['season'] ?? null) !== 'summer') {
            return null;
        }

        if (! $this->isWinter($at)) {
            return null;
        }

        $clockStart = $this->winterStart($at);

        return $this->build('heating_loss', $clockStart, CarbonImmutable::parse($at));
    }

    /**
     * @return list<array{code: string, title: string, clock: string, clock_label: string, safety_notice: string}>
     */
    public function tenantOptions(?CarbonInterface $at = null): array
    {
        $at = CarbonImmutable::parse($at ?? now());
        $options = [];

        foreach ($this->complaints() as $code => $complaint) {
            $classified = $this->classify($code, $at);
            $options[] = [
                'code' => $code,
                'title' => (string) ($complaint['title'] ?? $code),
                'clock' => (string) $classified['snapshot']['clock'],
                'clock_label' => (string) $classified['snapshot']['tenant_phrase'],
                'safety_notice' => (string) ($complaint['safety_notice'] ?? ''),
            ];
        }

        return $options;
    }

    /**
     * @param  array<string, mixed>  $snapshot
     */
    public function duePhrase(array $snapshot): string
    {
        $phrase = (string) ($snapshot['tenant_phrase'] ?? '');

        return $phrase !== '' ? $phrase : 'Your landlord has been told.';
    }

    /**
     * @return array<string, mixed>
     */
    private function build(string $code, CarbonImmutable $clockStart, CarbonImmutable $classifiedAt): array
    {
        $complaint = $this->complaints()[$code] ?? null;
        if (! is_array($complaint)) {
            throw new InvalidArgumentException("Unknown repair complaint [{$code}].");
        }

        $season = null;
        if (! empty($complaint['seasonal'])) {
            $season = $this->isWinter($clockStart) ? 'winter' : 'summer';
            $clockKey = $season === 'winter' ? 'make_safe_24' : 'hours_72';
        } else {
            $clockKey = (string) ($complaint['clock'] ?? '');
        }

        $clocks = $this->clocks ?? config('repair_sla.clocks', []);
        $clock = $clocks[$clockKey] ?? null;
        if (! is_array($clock)) {
            throw new InvalidArgumentException("Unknown repair clock [{$clockKey}].");
        }

        $start = $clockStart->timezone($this->timezone());
        $slaDue = $start->addHours((int) $clock['sla_hours']);
        $makeSafeDue = isset($clock['make_safe_hours'])
            ? $start->addHours((int) $clock['make_safe_hours'])
            : null;
        $stored = fn (CarbonImmutable $moment) => $moment->timezone(config('app.timezone'));

        $snapshot = [
            'complaint_code' => $code,
            'title' => (string) ($complaint['title'] ?? $code),
            'clock' => $clockKey,
            'season' => $season,
            'sla_hours' => (int) $clock['sla_hours'],
            'make_safe_hours' => $clock['make_safe_hours'],
            'warn_hours' => $clock['warn_hours'],
            'emergency_access' => (bool) $clock['emergency_access'],
            'tenant_phrase' => (string) $clock['tenant_phrase'],
            'timezone' => $this->timezone(),
            'clock_started_at' => $start->toIso8601String(),
            'classified_at' => $classifiedAt->timezone($this->timezone())->toIso8601String(),
        ];

        return [
            'complaint_code' => $code,
            'priority' => 'critical',
            'emergency_access' => (bool) $clock['emergency_access'],
            'reported_at' => $stored($start),
            'sla_due_at' => $stored($slaDue),
            'make_safe_due_at' => $makeSafeDue ? $stored($makeSafeDue) : null,
            'snapshot' => $snapshot,
        ];
    }
}
