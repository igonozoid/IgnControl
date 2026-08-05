<?php

namespace App\Models;

use App\Traits\Auditable;
use App\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class Task extends Model
{
    use HasFactory, BelongsToCompany, Auditable;

    // Mesmas opções do sistema legado. "custom" é uma regra livre em
    // texto (recurrence_note) que a tela mostra mas não sabe calcular
    // sozinha — por isso não gera próxima ocorrência automaticamente.
    public const RECURRENCE_TYPES = [
        'none' => 'Sem recorrência',
        'daily' => 'Diária',
        'weekly' => 'Semanal',
        'fortnightly' => 'Quinzenal',
        'monthly' => 'Mensal',
        'bimonthly' => 'Bimestral',
        'quarterly' => 'Trimestral',
        'semiannual' => 'Semestral',
        'yearly' => 'Anual',
        'biennial' => 'Bianual',
        'custom' => 'Personalizada',
    ];

    private const RECURRENCE_MONTH_STEPS = [
        'monthly' => 1,
        'bimonthly' => 2,
        'quarterly' => 3,
        'semiannual' => 6,
        'yearly' => 12,
        'biennial' => 24,
    ];

    protected $fillable = [
        'company_id',
        'title',
        'description',
        'due_date',
        'recurrence_type',
        'recurrence_weekday',
        'recurrence_day_of_month',
        'recurrence_note',
        'status',
        'contact_id',
        'financial_entry_id',
        'created_by',
        'completed_at',
    ];

    protected $casts = [
        'due_date' => 'date:Y-m-d',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $task) {
            if (empty($task->created_by)) {
                $task->created_by = auth()->id();
            }
        });
    }

    public function contact(): BelongsTo
    {
        return $this->belongsTo(Contact::class);
    }

    public function financialEntry(): BelongsTo
    {
        return $this->belongsTo(FinancialEntry::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isOverdue(): bool
    {
        return $this->status === 'pending' && $this->due_date && $this->due_date->isPast();
    }

    /**
     * Rótulo curto pra mostrar ao lado da recorrência na tela — "toda
     * terça", "todo dia 10" — igual ao que o legado mostrava na lista.
     */
    public function recurrenceLabel(): ?string
    {
        if ($this->recurrence_type === 'none' || empty($this->recurrence_type)) {
            return null;
        }

        $label = self::RECURRENCE_TYPES[$this->recurrence_type] ?? null;
        if (! $label) {
            return null;
        }

        if (in_array($this->recurrence_type, ['weekly', 'fortnightly'], true) && $this->recurrence_weekday !== null) {
            $weekdays = ['Domingo', 'Segunda', 'Terça', 'Quarta', 'Quinta', 'Sexta', 'Sábado'];

            return $label.' • '.($weekdays[$this->recurrence_weekday] ?? '');
        }

        if (array_key_exists($this->recurrence_type, self::RECURRENCE_MONTH_STEPS) && $this->recurrence_day_of_month !== null) {
            return $label.' • dia '.$this->recurrence_day_of_month;
        }

        return $label;
    }

    /**
     * Calcula a data da próxima ocorrência a partir da data de vencimento
     * que acabou de ser concluída — mesma lógica do sistema legado
     * (_next_recurrence_date). "custom" não é calculável (é uma regra em
     * texto livre), então não gera próxima ocorrência sozinha.
     */
    public function nextRecurrenceDate(): ?Carbon
    {
        if (empty($this->due_date) || in_array($this->recurrence_type, ['none', 'custom', null], true)) {
            return null;
        }

        $due = Carbon::parse($this->due_date);

        return match ($this->recurrence_type) {
            'daily' => $due->copy()->addDay(),
            'weekly' => $due->copy()->addDays(7),
            'fortnightly' => $due->copy()->addDays(14),
            default => $this->nextMonthBasedRecurrenceDate($due),
        };
    }

    private function nextMonthBasedRecurrenceDate(Carbon $due): ?Carbon
    {
        $months = self::RECURRENCE_MONTH_STEPS[$this->recurrence_type] ?? null;
        if ($months === null) {
            return null;
        }

        $next = $due->copy()->addMonthsNoOverflow($months);

        if ($this->recurrence_day_of_month !== null) {
            $day = min($this->recurrence_day_of_month, $next->daysInMonth);
            $next->day($day);
        }

        return $next;
    }
}
