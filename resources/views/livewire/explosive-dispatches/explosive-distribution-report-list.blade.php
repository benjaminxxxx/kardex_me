<div class="space-y-6">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('home') }}" icon="home" />
        <flux:breadcrumbs.item href="#">Kardex</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>Reporte de distribución</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div class="flex items-center justify-between">
        <div>
            <flux:heading size="xl">Reporte de distribución de explosivos</flux:heading>
            <flux:text class="text-muted mt-2">Consumo por labor, perforista y fecha.</flux:text>
        </div>
        {{--  <flux:button icon="arrow-down-tray" wire:click="exportToExcel">Exportar a Excel</flux:button> --}}
    </div>

    {{-- ===== Filtros ===== --}}
    <div class="grid gap-4 md:grid-cols-4">
        <flux:select wire:model.live="year" label="Año">
            <flux:select.option value="">Todos los años</flux:select.option>
            @foreach ($this->availableYears as $y)
                <flux:select.option value="{{ $y }}">{{ $y }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:select wire:model.live="month" label="Mes">
            <flux:select.option value="">Todos los meses</flux:select.option>
            @foreach (range(1, 12) as $m)
                <flux:select.option value="{{ $m }}">{{ ucfirst(\Carbon\Carbon::create()->month($m)->translatedFormat('F')) }}</flux:select.option>
            @endforeach
        </flux:select>

        <flux:input type="date" wire:model.live="day" label="Día específico" />

        <flux:button variant="ghost" wire:click="clearFilters">Limpiar filtros</flux:button>
    </div>

    <div class="grid gap-4 md:grid-cols-2">
        <div>
            <flux:label>Perforista</flux:label>
            <livewire:shared.entity-search-select
                entityType="employee"
                fieldContext="report-driller"
                :selectedId="$drillerEmployeeId"
                :selectedLabel="$drillerEmployeeLabel"
            />
        </div>

        <div>
            <flux:label>Labor</flux:label>
            <livewire:shared.entity-search-select
                entityType="mining_labor"
                fieldContext="report-labor"
                :selectedId="$miningLaborId"
                :selectedLabel="$miningLaborLabel"
            />
        </div>
    </div>

    {{-- ===== Tabla, mismo layout que el Excel original ===== --}}
    <div class="overflow-x-auto">
        <flux:table :paginate="$distributions">
            <flux:table.columns>
                <flux:table.column>Año</flux:table.column>
                <flux:table.column>Mes</flux:table.column>
                <flux:table.column>Fecha</flux:table.column>
                <flux:table.column>Guardia</flux:table.column>
                <flux:table.column>Fulminante (u)</flux:table.column>
                <flux:table.column>Dinamita (u)</flux:table.column>
                <flux:table.column>Mecha Lenta (m)</flux:table.column>
                <flux:table.column>Guía (m)</flux:table.column>
                <flux:table.column>Guía Aux</flux:table.column>
                <flux:table.column>ANFO</flux:table.column>
                <flux:table.column>MI (labor)</flux:table.column>
                <flux:table.column>Typ-Labor</flux:table.column>
                <flux:table.column>Long Guía (pies)</flux:table.column>
                <flux:table.column>Long Barreno (pies)</flux:table.column>
                <flux:table.column>Perforista</flux:table.column>
                <flux:table.column>Kg Din</flux:table.column>
                <flux:table.column>Cajas Din</flux:table.column>
                <flux:table.column>Cajas Guía</flux:table.column>
                <flux:table.column>Cajitas Ful</flux:table.column>
                <flux:table.column>Pies Perforados</flux:table.column>
            </flux:table.columns>

            <flux:table.rows>
                @forelse($distributions as $dist)
                    <flux:table.row :key="$dist->id">
                        <flux:table.cell>{{ $dist->year }}</flux:table.cell>
                        <flux:table.cell>{{ $dist->month_name }}</flux:table.cell>
                        <flux:table.cell>{{ $dist->dispatch->dispatch_date->format('d/m/Y') }}</flux:table.cell>
                        <flux:table.cell>{{ $dist->shift_label }}</flux:table.cell>
                        <flux:table.cell>{{ $dist->fulminante_qty }}</flux:table.cell>
                        <flux:table.cell>{{ $dist->emulnor_qty }}</flux:table.cell>
                        <flux:table.cell>{{ $dist->mecha_lenta_qty }}</flux:table.cell>
                        <flux:table.cell>{{ $dist->guia_qty }}</flux:table.cell>
                        <flux:table.cell>{{ $dist->guia_aux_qty }}</flux:table.cell>
                        <flux:table.cell>{{ $dist->anfo_qty }}</flux:table.cell>
                        <flux:table.cell>{{ $dist->labor_code }}</flux:table.cell>
                        <flux:table.cell>{{ $dist->labor_type_label }}</flux:table.cell>
                        <flux:table.cell>{{ $dist->guide_length_feet }}</flux:table.cell>
                        <flux:table.cell>{{ $dist->drill_depth_feet }}</flux:table.cell>
                        <flux:table.cell>{{ $dist->driller_name }}</flux:table.cell>
                        <flux:table.cell>{{ $dist->kg_din ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $dist->cajas_din ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $dist->cajas_guia ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $dist->cajitas_ful ?? '—' }}</flux:table.cell>
                        <flux:table.cell>{{ $dist->pies_perforados }}</flux:table.cell>
                    </flux:table.row>
                @empty
                    <flux:table.row>
                        <flux:table.cell colspan="100%" class="text-center">
                            <flux:text class="text-muted">No hay registros con estos filtros.</flux:text>
                        </flux:table.cell>
                    </flux:table.row>
                @endforelse
            </flux:table.rows>
        </flux:table>
    </div>
</div>