<x-filament-panels::page>
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
        <div class="fi-section-header px-4 py-3 border-b border-gray-950/5 dark:border-white/10">
            <h3 class="text-base font-semibold text-gray-950 dark:text-white">
                Daftar Peran
            </h3>
            <p class="text-sm text-gray-500 dark:text-gray-400">
                {{ $roles->count() }} peran · {{ $permissionTotal }} izin terdaftar
            </p>
        </div>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 dark:text-gray-400">
                    <th class="px-4 py-2.5 font-medium">Peran</th>
                    <th class="px-4 py-2.5 font-medium">Jumlah Izin</th>
                    <th class="px-4 py-2.5 font-medium">Pengguna</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-950/5 dark:divide-white/10">
                @foreach ($roles as $role)
                    <tr>
                        <td class="px-4 py-2.5 font-semibold text-gray-950 dark:text-white">
                            {{ \Illuminate\Support\Str::headline($role->name) }}
                        </td>
                        <td class="px-4 py-2.5 text-gray-600 dark:text-gray-300">
                            {{ $role->permissions_count }}
                        </td>
                        <td class="px-4 py-2.5 text-gray-600 dark:text-gray-300">
                            {{ $role->users_count }}
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="mt-4 grid gap-4 md:grid-cols-2">
        @foreach ($roles as $role)
            <details class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <summary class="cursor-pointer px-4 py-3 text-sm font-semibold text-gray-950 select-none dark:text-white">
                    {{ \Illuminate\Support\Str::headline($role->name) }}
                    <span class="ml-1 font-normal text-gray-500 dark:text-gray-400">
                        ({{ $role->permissions_count }} izin)
                    </span>
                </summary>
                <div class="flex flex-wrap gap-1.5 px-4 pb-4">
                    @foreach ($role->permissions->sortBy('name') as $permission)
                        <span class="rounded-md bg-gray-100 px-2 py-0.5 font-mono text-xs text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                            {{ $permission->name }}
                        </span>
                    @endforeach
                </div>
            </details>
        @endforeach
    </div>
</x-filament-panels::page>
