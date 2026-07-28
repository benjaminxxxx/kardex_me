<div class="space-y-6">

    <flux:heading size="xl">
        Despachos de explosivos
    </flux:heading>

    <flux:table :paginate="$dispatches">

        <flux:table.columns>
            <flux:table.column>Fecha</flux:table.column>
            <flux:table.column>Guardia</flux:table.column>

            <flux:table.column>Ful.</flux:table.column>
            <flux:table.column>Emulnor</flux:table.column>
            <flux:table.column>Mecha</flux:table.column>
            <flux:table.column>Guía</flux:table.column>
            <flux:table.column>Guía Aux.</flux:table.column>
            <flux:table.column>ANFO</flux:table.column>

            <flux:table.column>Almacenero</flux:table.column>
            <flux:table.column>Supervisor</flux:table.column>
            <flux:table.column>Estado</flux:table.column>
            <flux:table.column></flux:table.column>
        </flux:table.columns>

        <flux:table.rows>

            @forelse ($dispatches as $dispatch)

                <flux:table.row :key="$dispatch->id">

                    <flux:table.cell>
                        {{ $dispatch->dispatch_date->format('d/m/Y') }}
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $dispatch->shift === \App\Models\ExplosiveFieldDispatch::SHIFT_DAY ? 'Día' : 'Noche' }}
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ number_format($dispatch->fulminante_qty, 0) }}
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ number_format($dispatch->emulnor_qty, 0) }}
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ number_format($dispatch->mecha_lenta_qty, 2) }} m
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ number_format($dispatch->guia_qty, 2) }} m
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ number_format($dispatch->guia_aux_qty, 0) }}
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ number_format($dispatch->anfo_qty, 2) }} kg
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $dispatch->dispatchedBy->person->display_name }}
                    </flux:table.cell>

                    <flux:table.cell>
                        {{ $dispatch->requestedBy->person->display_name }}
                    </flux:table.cell>

                    <flux:table.cell>
                        <flux:badge
                            size="sm"
                            :color="$dispatch->isDistributed() ? 'green' : 'yellow'">

                            {{ $dispatch->isDistributed()
                                ? 'Distribuido'
                                : 'Pendiente' }}

                        </flux:badge>
                    </flux:table.cell>

                    <flux:table.cell>

                        @if (
                            $dispatch->requested_by_employee_id === $this->myEmployeeId &&
                            $dispatch->isPendingDistribution()
                        )

                            <flux:button
                                size="sm"
                                variant="primary"
                                href="{{ route('explosive-dispatches.distribute', $dispatch) }}">

                                Distribuir

                            </flux:button>

                        @endif

                    </flux:table.cell>

                </flux:table.row>

            @empty

                <flux:table.row>

                    <flux:table.cell colspan="12" class="text-center">

                        <flux:text class="text-zinc-500">
                            No hay despachos registrados.
                        </flux:text>

                    </flux:table.cell>

                </flux:table.row>

            @endforelse

        </flux:table.rows>

    </flux:table>

</div>