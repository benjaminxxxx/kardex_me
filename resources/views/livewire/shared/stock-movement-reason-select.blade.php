<div class="space-y-2">
    <flux:label>Motivo del movimiento</flux:label>

    @if(!$isCustom)
        <div class="flex gap-2">
            <flux:select :value="$reason" wire:change="selectReason($event.target.value)" class="w-full">
                <flux:select.option value="">-- Seleccionar motivo --</flux:select.option>
                @foreach($this->suggestedReasons as $item)
                    <flux:select.option value="{{ $item }}">{{ $item }}</flux:select.option>
                @endforeach
                <flux:select.option value="__custom__">+ Otro motivo personalizado...</flux:select.option>
            </flux:select>
        </div>
    @else
        <div class="flex items-center gap-2">
            <flux:input 
                wire:model.live.debounce.300ms="reason" 
                placeholder="Escribe el motivo..." 
                class="w-full"
            />
            <flux:button size="sm" variant="subtle" wire:click="selectReason('')" icon="x-mark" title="Volver a la lista" />
        </div>
    @endif
</div>