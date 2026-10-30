# Panduan Operasional Payment Protection Phomoria

Dokumen ini adalah panduan penggunaan melalui Postman untuk pemilik/pengelola Phomoria yang berwenang. Simpan token sebagai rahasia. Jangan memasukkannya ke Git, screenshot, URL, atau membagikannya kepada pihak yang tidak berwenang.

## 1. Persiapan Postman

Buat Environment Postman, misalnya `Phomoria Production`, dengan variabel:

| Variable | Initial value |
|---|---|
| `base_url` | `https://phomoria.com/sub` |
| `payment_protection_token` | Isi token rahasia yang tersimpan di `.env` hosting |

Jangan mengekspor atau membagikan Environment yang berisi token tanpa menghapus nilainya terlebih dahulu.

## 2. Mengaktifkan penangguhan

Buat request dengan pengaturan berikut:

- Method: **POST**
- URL: `{{base_url}}/api/payment-protection/suspend`
- Authorization: pilih **Bearer Token**, lalu isi `{{payment_protection_token}}`
- Header: `Accept: application/json`
- Body: kosong

Klik **Send**.

Respons berhasil yang diharapkan:

```json
{
  "message": "Phomoria has been suspended.",
  "status": "suspended"
}
```

HTTP status yang diharapkan: **200 OK**.

Setelah berhasil, marker penangguhan dibuat di document root aplikasi. Request website dan API biasa akan menerima status **503 Service Unavailable**. Database dan data pelanggan tidak dihapus atau diubah oleh mekanisme ini.

**Penting:** jangan menganggap layanan sudah ditangguhkan jika Postman tidak menerima respons sukses. Periksa respons dan status HTTP terlebih dahulu.

## 3. Memulihkan layanan

Buat request kedua:

- Method: **POST**
- URL: `{{base_url}}/api/payment-protection/resume`
- Authorization: pilih **Bearer Token**, lalu isi `{{payment_protection_token}}`
- Header: `Accept: application/json`
- Body: kosong

Klik **Send**.

Respons berhasil yang diharapkan:

```json
{
  "message": "Phomoria has been restored.",
  "status": "active"
}
```

HTTP status yang diharapkan: **200 OK**.

Setelah itu, pastikan halaman website, login/CMS, galeri, dan API kembali berfungsi normal.

## 4. Memahami respons gagal

| HTTP status | Arti umum | Tindakan |
|---|---|---|
| 401 Unauthorized | Token tidak ada atau tidak cocok | Periksa Environment dan token di hosting. Jangan menempelkan token ke chat. |
| 500 Internal Server Error | Server gagal membuat atau menghapus marker, atau terjadi kesalahan internal | Periksa log Laravel dan izin akses file. Jangan menganggap status berubah. |
| 503 Service Unavailable | Request biasa ditolak karena layanan sedang ditangguhkan | Gunakan endpoint resume yang benar untuk memulihkan layanan. |

Jika token yang benar tetap menghasilkan 401, jangan mencoba mengubah file atau konfigurasi secara acak di hosting. Verifikasi konfigurasi secara aman melalui SSH tanpa mencetak nilai token.

## 5. Checklist sebelum dan sesudah tindakan

Sebelum suspend:
- Pastikan tindakan sesuai kewenangan dan perjanjian yang berlaku.
- Pastikan token tersedia dan Environment Postman yang dipilih benar.
- Pastikan Anda siap memulihkan layanan melalui endpoint resume.

Sesudah suspend:
- Pastikan respons menunjukkan `suspended` dan HTTP 200.
- Verifikasi bahwa halaman website menerima HTTP 503.
- Simpan waktu tindakan dan hasilnya untuk catatan internal.

Sesudah resume:
- Pastikan respons menunjukkan `active` dan HTTP 200.
- Buka kembali website dan halaman penting.
- Uji satu API operasional yang aman.
- Pastikan marker sudah tidak ada di server.

## 6. Batasan penting

- Jangan mengirim token melalui query string atau URL.
- Jangan menyimpan token di repository.
- Jangan menguji suspend pada saat pelanggan sedang menggunakan layanan tanpa jadwal pemeliharaan yang disetujui.
- Mekanisme ini tidak menghapus database, foto, transaksi, atau file pelanggan.
- Cache eksternal, file yang sudah diunduh, dan proses worker yang sedang berjalan tidak otomatis dihentikan.
- Jika endpoint kontrol tidak dapat diakses, pemulihan manual dengan menghapus hanya file marker dapat dilakukan oleh administrator hosting yang berwenang sesuai panduan teknis.
