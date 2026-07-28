<flux:modal wire:model.self="show" class="md:w-[560px]">
    <div
        x-data="{
            laborType: @entangle('laborType'),
            levelNumber: @entangle('levelNumber'),
            veinName: @entangle('veinName'),
            prefixes: {
                tajo: 'TJ', subnivel: 'S/N', galeria: 'GAL', estocada: 'EST',
                crucero: 'CX', chimenea: 'CH', pique: 'PQ', buzon: 'B/C',
            },
            get previewCode() {
                const prefix = this.prefixes[this.laborType] || '??';
                const parts = [prefix, this.levelNumber, this.veinName]
                    .filter(v => v !== null && v !== '' && v !== undefined);
                return parts.join(' ').toUpperCase();
            }
        }"
        class="space-y-6"
    >
        <div>
            <flux:heading size="lg">
                {{ $mode === 'edit' ? 'Editar labor minera' : 'Registrar nueva labor minera' }}
            </flux:heading>
            <flux:text class="mt-1 text-zinc-500">
                El código se genera automáticamente según tipo, nivel y veta.
            </flux:text>
        </div>

        {{-- ===== Preview del código, instantáneo, sin esperar al servidor ===== --}}
        <div>
            <flux:input
                label="Código generado"
                readonly
                x-bind:value="previewCode || '—'"
                class="font-mono"
            />
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <flux:select wire:model="laborType" x-model="laborType" label="Tipo de labor">
                <flux:select.option value="tajo">Tajo</flux:select.option>
                <flux:select.option value="subnivel">Subnivel</flux:select.option>
                <flux:select.option value="galeria">Galería</flux:select.option>
                <flux:select.option value="estocada">Estocada</flux:select.option>
                <flux:select.option value="crucero">Crucero</flux:select.option>
                <flux:select.option value="chimenea">Chimenea</flux:select.option>
                <flux:select.option value="pique">Piqué</flux:select.option>
                <flux:select.option value="buzon">Buzón</flux:select.option>
            </flux:select>

            <flux:input
                type="number"
                wire:model="levelNumber"
                x-model="levelNumber"
                label="Nivel"
                placeholder="Ej: 138 (sube de 10 en 10)"
            />
        </div>

        {{-- ===== Veta: sugerida vía datalist, pero se puede escribir una nueva ===== --}}
        <div>
            <flux:input
                wire:model="veinName"
                x-model="veinName"
                label="Veta / Apodo"
                placeholder="RUBY, NELLY, LIDIA..."
                list="vein-suggestions"
            />
            <datalist id="vein-suggestions">
                @foreach ($this->veinSuggestions as $vein)
                    <option value="{{ $vein }}"></option>
                @endforeach
            </datalist>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <flux:select wire:model="direction" label="Dirección" placeholder="Sin especificar">
                <flux:select.option value="este">Este</flux:select.option>
                <flux:select.option value="oeste">Oeste</flux:select.option>
                <flux:select.option value="norte">Norte</flux:select.option>
                <flux:select.option value="sur">Sur</flux:select.option>
            </flux:select>

            <flux:select wire:model="status" label="Estado">
                <flux:select.option value="active">Activa</flux:select.option>
                <flux:select.option value="exhausted">Agotada</flux:select.option>
                <flux:select.option value="paused">Pausada</flux:select.option>
            </flux:select>
        </div>

        <flux:textarea wire:model="notes" label="Observaciones" />

        <div class="flex justify-between">
            <flux:button variant="ghost" wire:click="close">Cancelar</flux:button>
            <flux:button variant="primary" wire:click="save" icon="check">
                {{ $mode === 'edit' ? 'Guardar cambios' : 'Registrar labor' }}
            </flux:button>
        </div>
    </div>
</flux:modal>