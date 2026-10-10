<x-filament-panels::page>
    <x-filament::section
        heading="Daftar Peran"
        description="{{ $roles->count() }} peran · {{ $permissionTotal }} izin terdaftar"
    >
        <table class="fi-ta-table" style="width: 100%; border-collapse: collapse">
            <thead>
                <tr>
                    <th scope="col" class="fi-ta-header-cell">Peran</th>
                    <th scope="col" class="fi-ta-header-cell" style="text-align: right">Jumlah Izin</th>
                    <th scope="col" class="fi-ta-header-cell" style="text-align: right">Pengguna</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($roles as $role)
                    <tr class="fi-ta-row fi-striped">
                        <td class="fi-ta-cell" style="padding-block: 0.6rem">
                            <div class="fi-ta-cell-content" style="font-weight: 600">
                                {{ \Illuminate\Support\Str::headline($role->name) }}
                            </div>
                        </td>
                        <td class="fi-ta-cell" style="text-align: right">
                            <div class="fi-ta-cell-content" style="font-variant-numeric: tabular-nums">
                                {{ $role->permissions_count }}
                            </div>
                        </td>
                        <td class="fi-ta-cell" style="text-align: right">
                            <div class="fi-ta-cell-content" style="font-variant-numeric: tabular-nums">
                                {{ $role->users_count }}
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr class="fi-ta-row">
                        <td class="fi-ta-cell" colspan="3" style="text-align: center; padding-block: 2rem">
                            <div class="fi-ta-cell-content" style="color: var(--gray-500)">
                                Belum ada peran terdaftar.
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </x-filament::section>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(20rem, 1fr)); gap: 1rem; margin-top: 1.5rem">
        @foreach ($roles as $role)
            <x-filament::section
                :heading="\Illuminate\Support\Str::headline($role->name)"
                collapsible
                collapsed
            >
                <x-slot name="afterHeader">
                    <x-filament::badge color="gray">
                        {{ $role->permissions_count }} izin
                    </x-filament::badge>
                </x-slot>

                <div style="display: flex; flex-wrap: wrap; gap: 0.375rem">
                    @foreach ($role->permissions->sortBy('name') as $permission)
                        <x-filament::badge color="gray">
                            {{ $permission->name }}
                        </x-filament::badge>
                    @endforeach
                </div>
            </x-filament::section>
        @endforeach
    </div>
</x-filament-panels::page>
