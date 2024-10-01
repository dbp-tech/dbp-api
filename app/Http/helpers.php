<?php


if(! function_exists('resultFunction')){
    function resultFunction($message, $status = false, $data = null){
        return [
            'status' => $status,
            'message' => $message,
            'data' => $data
        ];
    }
}

if(! function_exists('defaultFollowUpEcommerce')){
    function defaultFollowUpEcommerce(){
        $followUp = json_decode('[
            {
                "key": "welcome",
                "value": "Welcome",
                "defaultText": "*Terima kasih, Kami sudah terima pesanan anda dengan rincian sebagai berikut*\nProduk: {{product_name}}\nHarga: {{product_price}}\nOngkir: {{shipping_cost}}\n*Total: {{total_price}}*\n\n*Dikirim ke:*\nNama: {{name}}\nNo HP: {{phone}}\nAlamat: {{address}}\nKota: {{city}}\nKecamatan: {{district}}\nProvinsi: {{province}}\n\nSilahkan Transfer Nomor Rekening Berikut : \n{{bank_accounts}}\n\n*Jika Sudah Transfer Mohon Konfirmasi Agar Bisa Segera Diproses*",
                "currentText": "*Terima kasih, Kami sudah terima pesanan anda dengan rincian sebagai berikut*\nProduk: {{product_name}}\nHarga: {{product_price}}\nOngkir: {{shipping_cost}}\n*Total: {{total_price}}*\n\n*Dikirim ke:*\nNama: {{name}}\nNo HP: {{phone}}\nAlamat: {{address}}\nKota: {{city}}\nKecamatan: {{district}}\nProvinsi: {{province}}\n\nSilahkan Transfer Nomor Rekening Berikut : \n{{bank_accounts}}\n\n*Jika Sudah Transfer Mohon Konfirmasi Agar Bisa Segera Diproses*"
            },
            {
                "key": "order_detail",
                "value": "Order Detail",
                "defaultText": "Pesanan anda:\nProduk: {{product_name}}\nHarga: {{product_price}}\nOngkir: {{shipping_cost}}\nTotal: {{total_price}}\n\nDikirim ke:\nNama: {{name}}\nNo HP: {{phone}}\nAlamat: {{address}}\nKota: {{city}}\nKecamatan: {{district}}\n\nSilahkan transfer senilai {{total_price}}, ke salah satu rekening dibawah ini:\n{{bank_accounts}}",
                "currentText": "Pesanan anda:\nProduk: {{product_name}}\nHarga: {{product_price}}\nOngkir: {{shipping_cost}}\nTotal: {{total_price}}\n\nDikirim ke:\nNama: {{name}}\nNo HP: {{phone}}\nAlamat: {{address}}\nKota: {{city}}\nKecamatan: {{district}}\n\nSilahkan transfer senilai {{total_price}}, ke salah satu rekening dibawah ini:\n{{bank_accounts}}"
            },
            {
                "key": "followup_1",
                "value": "Follow up 1",
                "defaultText": "Halo kak, Untuk barang yang kakak pesan kami cek belum dilakukan pembayaran,\nagar orderannya kami proses kakak bisa melakukan proses pembayaran ya kak 😊\n\nkakak bisa melakukan pembayaran ke nomor rekening:\n{{bank_accounts}}",
                "currentText": "Halo kak, Untuk barang yang kakak pesan kami cek belum dilakukan pembayaran,\nagar orderannya kami proses kakak bisa melakukan proses pembayaran ya kak 😊\n\nkakak bisa melakukan pembayaran ke nomor rekening:\n{{bank_accounts}}"
            },
            {
                "key": "followup_2",
                "value": "Follow up 2",
                "defaultText": "Halo kak, Selamat siang,\nagar orderannya kami proses kakak bisa melakukan proses pembayaran ya kak 😊\n\nkakak bisa melakukan pembayaran ke nomor rekening:\n{{bank_accounts}}",
                "currentText": "Halo kak, Selamat siang,\nagar orderannya kami proses kakak bisa melakukan proses pembayaran ya kak 😊\n\nkakak bisa melakukan pembayaran ke nomor rekening:\n{{bank_accounts}}"
            },
            {
                "key": "followup_3",
                "value": "Follow up 3",
                "defaultText": "Halo kak, Selamat siang,\nproduk ini ternyata laris manis loh kak, stock nya tinggal dikit apakah kakak yakin tidak akan melanjutkan ordernya 😊\n\nkakak bisa melakukan pembayaran ke nomor rekening:\n{{bank_accounts}}",
                "currentText": "Halo kak, Selamat siang,\nproduk ini ternyata laris manis loh kak, stock nya tinggal dikit apakah kakak yakin tidak akan melanjutkan ordernya 😊\n\nkakak bisa melakukan pembayaran ke nomor rekening:\n{{bank_accounts}}"
            },
            {
                "key": "followup_4",
                "value": "Follow up 4",
                "defaultText": "Halo kak, Selamat siang,\nproduk yang kakak checkout sisa stocknya tipis banget, sepertinya besok habis, buruan agar tidak kehabisan kakak bisa melakukan pembayaran sekarang juga 😊\n\nkakak bisa melakukan pembayaran ke nomor rekening:\n{{bank_accounts}}",
                "currentText": "Halo kak, Selamat siang,\nproduk yang kakak checkout sisa stocknya tipis banget, sepertinya besok habis, buruan agar tidak kehabisan kakak bisa melakukan pembayaran sekarang juga 😊\n\nkakak bisa melakukan pembayaran ke nomor rekening:\n{{bank_accounts}}"
            },
            {
                "key": "processing",
                "value": "Processing",
                "defaultText": "Pembayaran dari {{name}} untuk pembelian {{product_name}} senilai {{total_price}} telah kami terima, pesanan anda kini kami proses. 😊\n\nkakak bisa melakukan pembayaran ke nomor rekening:\n{{bank_accounts}}",
                "currentText": "Pembayaran dari {{name}} untuk pembelian {{product_name}} senilai {{total_price}} telah kami terima, pesanan anda kini kami proses. 😊\n\nkakak bisa melakukan pembayaran ke nomor rekening:\n{{bank_accounts}}"
            },
            {
                "key": "completed",
                "value": "Completed",
                "defaultText": "Terima kasih kak {{name}} sudah belanja di toko kami 🙏🏻 semoga barangnya awet dan kembali berlangganan di toko kami",
                "currentText": "Terima kasih kak {{name}} sudah belanja di toko kami 🙏🏻 semoga barangnya awet dan kembali berlangganan di toko kami"
            },
            {
                "key": "upselling",
                "value": "Up Selling",
                "defaultText": "Halo, kami lagi ada promo khusus hanya untuk anda {{name}}\n- Produk XYZ, dari Rp180.000 jadi cuma Rp140rb aja ☺\nStok terbatas ya, pesan sekarang sebelum kehabisan...️",
                "currentText": "Halo, kami lagi ada promo khusus hanya untuk anda {{name}}\n- Produk XYZ, dari Rp180.000 jadi cuma Rp140rb aja ☺\nStok terbatas ya, pesan sekarang sebelum kehabisan...️"
            },
            {
                "key": "redirect",
                "value": "Redirect",
                "defaultText": "Halo, saya sudah melakukan pemesanan {{product_name}}, atas nama {{name}}. Mohon segera diproses ya 🙏🏻",
                "currentText": "Halo, saya sudah melakukan pemesanan {{product_name}}, atas nama {{name}}. Mohon segera diproses ya 🙏🏻"
            }
        ]
        ', true);
        return $followUp;
    }
}