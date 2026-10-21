<?php

namespace App\Livewire\Audit;

use App\Models\AuditLog;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

#[Title('Auditoría')]
class AuditLogList extends Component
{
    use WithPagination;

    #[Url]
    public string $search = '';

    #[Url]
    public string $event = '';

    #[Url]
    public string $severity = '';

    #[Url]
    public string $dateFrom = '';

    #[Url]
    public string $dateTo = '';

    public ?int $viewingId = null;
    public bool $showDetail = false;

    public function updated($property): void
    {
        if (in_array($property, ['search', 'event', 'severity', 'dateFrom', 'dateTo'])) {
            $this->resetPage();
        }
    }

    public function clearFilters(): void
    {
        $this->reset('search', 'event', 'severity', 'dateFrom', 'dateTo');
        $this->resetPage();
    }

    public function view(int $id): void
    {
        $this->viewingId = $id;
        $this->showDetail = true;
    }

    #[Computed]
    public function viewing(): ?AuditLog
    {
        return $this->viewingId ? AuditLog::find($this->viewingId) : null;
    }

    #[Computed]
    public function logs()
    {
        return AuditLog::query()
            ->when($this->search, function ($q) {
                $term = '%' . $this->search . '%';
                $q->where(fn($qq) => $qq
                    ->where('description', 'like', $term)
                    ->orWhere('reason', 'like', $term)
                    ->orWhere('user_name', 'like', $term));
            })
            ->when($this->event, fn($q) => $q->where('event', $this->event))
            ->when($this->severity, fn($q) => $q->where('severity', $this->severity))
            ->when($this->dateFrom, fn($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn($q) => $q->whereDate('created_at', '<=', $this->dateTo))
            ->latest('id')
            ->paginate(20);
    }

    public function render()
    {
        return view('livewire.audit.audit-log-list', [
            'events' => AuditLog::EVENTS,
        ]);
    }
}
