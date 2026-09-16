@props(['type', 'id'])

<form action="{{ route('mutasi.'.$type.'.revoke-approval', $id) }}" method="POST"
    data-confirm="Batalkan persetujuan usulan ini? Status akan kembali menjadi Menunggu Verifikasi Dinas untuk diverifikasi ulang."
    class="my-4 rounded-xl border border-amber-200 bg-amber-50 p-4">
    @csrf
    <p class="mb-3 text-sm text-amber-900">Jika persetujuan diberikan secara keliru, kembalikan usulan ke Menunggu Verifikasi Dinas.</p>
    <button type="submit" class="rounded-lg bg-amber-600 px-4 py-2 text-sm font-semibold text-white hover:bg-amber-700">
        Batalkan Persetujuan
    </button>
</form>
