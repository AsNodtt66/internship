{{--
    Fallback pengaman tampilan.

    Beberapa laptop punya extension browser atau antivirus (VPN extension,
    McAfee WebAdvisor, dsb) yang menyuntik Content-Security-Policy yang
    memblokir 'eval'. Alpine.js (dipakai Filament untuk dropdown, notifikasi,
    dll) butuh eval untuk boot, jadi kalau diblokir, elemen yang diberi
    atribut x-cloak akan tersembunyi permanen (karena Alpine yang biasanya
    melepas atribut itu tidak pernah jalan).

    Skrip di bawah ini TIDAK memakai eval/Function string sama sekali,
    jadi tetap jalan meski CSP unsafe-eval diblokir. Fungsinya: kalau
    setelah beberapa saat Alpine belum juga melepas atribut x-cloak
    (tandanya Alpine gagal boot), skrip ini akan melepasnya secara manual
    supaya kontennya tetap tampil, bukan malah hilang/putih.
--}}
<script>
    (function () {
        function revealCloakedElements() {
            document.querySelectorAll('[x-cloak]').forEach(function (el) {
                el.removeAttribute('x-cloak');
            });
        }

        // Beri Alpine kesempatan boot normal dulu. Kalau Alpine jalan
        // normal, atribut x-cloak sudah lebih dulu dilepas olehnya jadi
        // pengecekan ini tidak berpengaruh apa-apa (aman/no-op).
        window.setTimeout(revealCloakedElements, 1500);

        // Jaga-jaga untuk navigasi Livewire (SPA-like) yang merender
        // ulang sebagian halaman.
        document.addEventListener('livewire:navigated', function () {
            window.setTimeout(revealCloakedElements, 1500);
        });
    })();
</script>
