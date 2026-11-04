<?php

namespace App\Helpers;

use Carbon\Carbon;

class FormatHelper
{
    /**
     * Format nominal angka menjadi Rupiah (IDR) tanpa desimal.
     */
    public static function formatIDR(int|float|null $amount): string
    {
        $num = (int) round((float) ($amount ?? 0));
        return 'Rp ' . number_format($num, 0, ',', '.');
    }

    /**
     * Format tanggal ke zona waktu Asia/Jakarta (WIB).
     */
    public static function formatWIB(mixed $dateInput): string
    {
        if (!$dateInput) {
            return '-';
        }
        try {
            return Carbon::parse($dateInput)
                ->timezone('Asia/Jakarta')
                ->translatedFormat('d M Y, H:i') . ' WIB';
        } catch (\Throwable $e) {
            return '-';
        }
    }

    /**
     * Sanitasi input string dari tag HTML berbahaya dan batasi panjang karakter.
     */
    public static function sanitizeInput(?string $str, int $maxLength = 500): string
    {
        if ($str === null) {
            return '';
        }
        $clean = preg_replace('/[<>]/', '', strip_tags($str));
        return mb_substr(trim((string) $clean), 0, $maxLength);
    }

    /**
     * Peta label dan warna badge Tailwind untuk status pesanan.
     */
    public static function orderStatusMap(): array
    {
        return [
            'WAITING_REVIEW' => [
                'label' => 'Menunggu Pembayaran Escrow / Tinjauan',
                'badge' => 'bg-blue-50 text-blue-800 border-blue-200',
            ],
            'WAITING_OFFER' => [
                'label' => 'Terbuka di Bursa (Menunggu Admin / Nego)',
                'badge' => 'bg-indigo-50 text-indigo-800 border-indigo-200',
            ],
            'WAITING_CUSTOMER_APPROVAL' => [
                'label' => 'Admin Mengajukan Nego Harga',
                'badge' => 'bg-amber-50 text-amber-900 border-amber-300',
            ],
            'APPROVED_FOR_PROCESS' => [
                'label' => 'Harga Disepakati (Wajib Bayar Escrow)',
                'badge' => 'bg-teal-50 text-teal-900 border-teal-200',
            ],
            'IN_PROGRESS' => [
                'label' => 'Sedang Diproses Admin (Dana di Escrow)',
                'badge' => 'bg-cyan-50 text-cyan-900 border-cyan-300',
            ],
            'WAITING_CUSTOMER_COMPLETION' => [
                'label' => 'Barang Diserahkan (Menunggu Konfirmasi Customer)',
                'badge' => 'bg-purple-100 text-purple-900 border-purple-300',
            ],
            'COMPLETED' => [
                'label' => 'Selesai (Dana Cair ke Admin)',
                'badge' => 'bg-emerald-100 text-emerald-900 border-emerald-300',
            ],
            'CANCELLED' => [
                'label' => 'Dibatalkan',
                'badge' => 'bg-slate-200 text-slate-700 border-slate-300',
            ],
        ];
    }

    /**
     * Peta label dan warna badge untuk status Rekening Bersama (Escrow).
     */
    public static function escrowStatusMap(): array
    {
        return [
            'UNPAID' => [
                'label' => 'Belum Dibayar ke Escrow',
                'badge' => 'bg-rose-50 text-rose-800 border-rose-200',
            ],
            'HELD_IN_ESCROW' => [
                'label' => 'Dana Ditahan di Rekening Bersama (Escrow)',
                'badge' => 'bg-amber-100 text-amber-900 border-amber-300',
            ],
            'RELEASED_TO_ADMIN' => [
                'label' => 'Dana Diteruskan ke Saldo Admin',
                'badge' => 'bg-emerald-100 text-emerald-900 border-emerald-300',
            ],
            'REFUNDED' => [
                'label' => 'Dana Dikembalikan ke Customer (Refund)',
                'badge' => 'bg-slate-200 text-slate-800 border-slate-300',
            ],
        ];
    }

    /**
     * Peta status verifikasi pendaftaran Admin (KTM).
     */
    public static function verificationStatusMap(): array
    {
        return [
            'NOT_SUBMITTED' => [
                'label' => 'Belum Mengajukan',
                'badge' => 'bg-slate-100 text-slate-700 border-slate-300',
            ],
            'PENDING' => [
                'label' => 'Menunggu Verifikasi',
                'badge' => 'bg-amber-100 text-amber-900 border-amber-300',
            ],
            'VERIFIED' => [
                'label' => 'Disetujui (Admin Aktif)',
                'badge' => 'bg-emerald-100 text-emerald-900 border-emerald-300',
            ],
            'REJECTED' => [
                'label' => 'Permohonan Ditolak',
                'badge' => 'bg-rose-100 text-rose-900 border-rose-300',
            ],
        ];
    }

    /**
     * Mesin Transisi Status Pesanan yang Valid (Mencegah lompatan status ilegal).
     */
    public static function canTransitionOrder(string $currentStatus, string $nextStatus): bool
    {
        $validTransitions = [
            'WAITING_REVIEW'              => ['IN_PROGRESS', 'CANCELLED'],
            'WAITING_OFFER'               => ['WAITING_CUSTOMER_APPROVAL', 'APPROVED_FOR_PROCESS', 'CANCELLED'],
            'WAITING_CUSTOMER_APPROVAL'   => ['APPROVED_FOR_PROCESS', 'WAITING_OFFER', 'CANCELLED'],
            'APPROVED_FOR_PROCESS'        => ['IN_PROGRESS', 'WAITING_OFFER', 'CANCELLED'],
            'IN_PROGRESS'                 => ['WAITING_CUSTOMER_COMPLETION', 'CANCELLED'],
            'WAITING_CUSTOMER_COMPLETION' => ['COMPLETED'],
            'COMPLETED'                   => [],
            'CANCELLED'                   => [],
        ];

        return in_array($nextStatus, $validTransitions[$currentStatus] ?? [], true);
    }
}