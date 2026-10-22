PHOMORIA CLOUD - DOKU QRIS PHASE 2

Overlay ini menambahkan fondasi akun DOKU, adapter SNAP QRIS, endpoint create/status/cancel, webhook, dan halaman uji QRIS.

PENTING:
- Jangan memasukkan secret DOKU atau private key ke Git.
- Masukkan credential melalui halaman admin setelah overlay dipasang.
- DOKU QRIS SNAP membutuhkan merchantId, terminalId, client id/secret, dan merchant private key untuk tanda tangan token B2B.
- Merchant public key harus didaftarkan di DOKU dan merupakan pasangan dari private key merchant.

SETELAH EXTRACT:
1. php artisan migrate --force
2. php artisan optimize:clear
3. Login admin.
4. Buka /admin/doku
5. Buat akun DOKU Sandbox.
6. Isi Client ID, Client Secret, Merchant ID, Terminal ID, Postal Code, dan Merchant Private Key.
7. Pastikan Merchant Public Key dari pasangan private key sudah didaftarkan di DOKU Dashboard.
8. Buka halaman uji QRIS dan generate QR.

Belum ada perubahan pada desktop Java.
