<div x-data="permisosTreeRol" class="space-y-4">
    <flux:breadcrumbs>
        <flux:breadcrumbs.item href="{{ route('home') }}" icon="home" />
        <flux:breadcrumbs.item href="{{ route('roles.index') }}">Roles</flux:breadcrumbs.item>
        <flux:breadcrumbs.item>{{ $roleName }}</flux:breadcrumbs.item>
    </flux:breadcrumbs>

    <div>
        <flux:heading size="xl">Permisos: {{ $roleName }}</flux:heading>
        <flux:text class="mt-1 text-muted">
            Activa o desactiva permisos para este rol. Al activar un nodo padre se activan
            automáticamente todos sus hijos. Al activar un hijo, su padre se activa también.
        </flux:text>
    </div>

    <div class="space-y-3">
        <template x-for="(nodo, idx) in arbol" :key="idx">
            <div x-html="renderNodo(nodo, [idx])"></div>
        </template>
    </div>

    <div class="flex justify-end gap-2">
        <flux:button variant="ghost" href="{{ route('roles.index') }}">Volver</flux:button>
        <flux:button variant="primary" wire:click="guardar" wire:loading.attr="disabled" icon="check">
            Guardar permisos
        </flux:button>
    </div>
</div>

@script
<script>
    Alpine.data('permisosTreeRol', () => ({
        arbol: @js($arbol),
        activados: @js($permisosActivados),
        expandidos: {},

        estaActivo(nombre) {
            return this.activados.includes(nombre);
        },
        estaExpandido(pathStr) {
            return this.expandidos[pathStr] === true;
        },
        toggleExpandido(pathStr, event) {
            event.stopPropagation();
            this.expandidos[pathStr] = !this.expandidos[pathStr];
        },
        algunHijoActivo(nodo) {
            if (!nodo.hijos || nodo.hijos.length === 0) return false;
            return nodo.hijos.some(h => this.estaActivo(h.nombre) || this.algunHijoActivo(h));
        },
        activarConHijos(nodo) {
            if (!this.activados.includes(nodo.nombre)) this.activados.push(nodo.nombre);
            if (nodo.hijos) nodo.hijos.forEach(h => this.activarConHijos(h));
        },
        desactivarConHijos(nodo) {
            this.activados = this.activados.filter(n => n !== nodo.nombre);
            if (nodo.hijos) nodo.hijos.forEach(h => this.desactivarConHijos(h));
        },
        activarAncestros(path, nodos = this.arbol) {
            if (path.length === 0) return;
            const nodo = nodos[path[0]];
            if (!nodo) return;
            if (!this.activados.includes(nodo.nombre)) this.activados.push(nodo.nombre);
            if (path.length > 1 && nodo.hijos) this.activarAncestros(path.slice(1), nodo.hijos);
        },
        toggle(nodo, path) {
            if (this.estaActivo(nodo.nombre)) {
                this.desactivarConHijos(nodo);
            } else {
                this.activarConHijos(nodo);
                const pathPadre = path.slice(0, -1);
                if (pathPadre.length > 0) this.activarAncestros(pathPadre);
            }
            $wire.set('permisosActivados', this.activados);
        },

        renderNodo(nodo, path, nivel = 0) {
            const tieneHijos = nodo.hijos && nodo.hijos.length > 0;
            const activo = this.estaActivo(nodo.nombre);
            const indeterminado = !activo && this.algunHijoActivo(nodo);
            const pathStr = JSON.stringify(path);
            const expandido = this.estaExpandido(pathStr);

            const indentBase = nivel === 0 ? '' : `margin-left: ${nivel * 20}px;`;

            let checkboxEstado = activo
                ? 'border-blue-500 bg-blue-500'
                : indeterminado
                    ? 'border-yellow-500 bg-yellow-500/20'
                    : 'border-zinc-300 dark:border-zinc-600 bg-transparent';

            let labelFondo = activo
                ? 'bg-blue-500/8 border-blue-500/30'
                : indeterminado
                    ? 'bg-yellow-500/5 border-yellow-500/20'
                    : 'border-transparent hover:bg-zinc-100 dark:hover:bg-zinc-800';

            const iconoCheck = activo
                ? `<svg class="w-2.5 h-2.5 text-white" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>`
                : indeterminado
                    ? `<div class="w-2 h-0.5 bg-yellow-500 rounded"></div>`
                    : '';

            const flechaIcono = expandido
                ? `<svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/></svg>`
                : `<svg class="w-3 h-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>`;

            const badgeHijos = tieneHijos
                ? `<span class="ml-auto text-[10px] px-1.5 py-0.5 rounded-full ${activo ? 'bg-blue-500/20 text-blue-500' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-500'}">${nodo.hijos.length}</span>`
                : '';

            return `
                <div style="${indentBase}" class="${nivel > 0 ? 'border-l-2 border-l-zinc-300 dark:border-l-zinc-700 pl-3 mt-1' : 'mt-2'}">
                    <div class="flex items-center gap-2 px-2 py-2 rounded-lg border transition-all duration-150 cursor-pointer select-none ${labelFondo}"
                         @click="toggle(${JSON.stringify(nodo).replace(/"/g, '&quot;')}, ${pathStr})">

                        <div class="flex-shrink-0 w-4 h-4 rounded border-2 flex items-center justify-center ${checkboxEstado}">
                            ${iconoCheck}
                        </div>

                        <span class="flex-1 text-sm ${activo ? 'text-zinc-900 dark:text-white font-medium' : 'text-zinc-500'}">
                            ${nodo.nombre}
                        </span>

                        ${badgeHijos}

                        ${tieneHijos ? `
                        <button type="button"
                            class="flex-shrink-0 p-1 rounded hover:bg-zinc-200 dark:hover:bg-zinc-700 text-zinc-500"
                            @click.stop="toggleExpandido('${pathStr.replace(/'/g, "\\'")}', $event)">
                            ${flechaIcono}
                        </button>` : ''}
                    </div>

                    ${tieneHijos && expandido ? `
                    <div class="mt-1">
                        ${nodo.hijos.map((hijo, i) => this.renderNodo(hijo, [...path, i], nivel + 1)).join('')}
                    </div>` : ''}
                </div>
            `;
        },
    }));
</script>
@endscript