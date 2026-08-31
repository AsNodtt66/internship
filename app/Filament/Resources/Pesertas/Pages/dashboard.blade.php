<x-filament-panels::page>
    <div class="space-y-6">
        {{-- Banner Selamat Datang --}}
        <div class="p-6 bg-white dark:bg-gray-900 rounded-xl shadow-sm border border-gray-200 dark:border-gray-800">
            <h2 class="text-xl font-bold text-gray-800 dark:text-gray-100">
                Selamat Datang di Portal Peserta PKL / Magang
            </h2>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
                Gunakan menu di sebelah kiri untuk mengajukan atau melihat status permohonan PKL, Magang, maupun Penelitian Anda.
            </p>
        </div>

        {{-- Widget Cards Statistik --}}
        <div>
            @livewire(\App\Filament\Peserta\Widgets\PesertaStatsOverview::class)
        </div>

        {{-- Tombol Aksi Cepat --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
            <div class="p-5 border border-gray-200 bg-white shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-emerald-100 text-emerald-600 rounded-lg dark:bg-emerald-900/50 dark:text-emerald-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800 dark:text-gray-200">Buat Pengajuan</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Isi formulir PKL, magang, atau penelitian</p>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="{{ \App\Filament\Peserta\Resources\PengajuanResource::getUrl('create') }}" 
                       class="inline-block w-full text-center px-4 py-2 text-sm font-medium text-white bg-emerald-600 rounded-lg hover:bg-emerald-500 transition">
                        Mulai Pengajuan
                    </a>
                </div>
            </div>

            <div class="p-5 border border-gray-200 bg-white shadow-sm rounded-xl dark:bg-gray-800 dark:border-gray-700">
                <div class="flex items-center space-x-3">
                    <div class="p-2 bg-blue-100 text-blue-600 rounded-lg dark:bg-blue-900/50 dark:text-blue-400">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                        </svg>
                    </div>
                    <div>
                        <h3 class="font-semibold text-gray-800 dark:text-gray-200">Riwayat Pengajuan</h3>
                        <p class="text-xs text-gray-500 dark:text-gray-400">Periksa status berkas dan tindak lanjut dari PIC</p>
                    </div>
                </div>
                <div class="mt-4">
                    <a href="{{ \App\Filament\Peserta\Resources\PengajuanResource::getUrl('index') }}" 
                       class="inline-block w-full text-center px-4 py-2 text-sm font-medium text-gray-700 bg-gray-100 rounded-lg dark:bg-gray-700 dark:text-gray-200 hover:bg-gray-200 transition">
                        Lihat Status Pengajuan
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>