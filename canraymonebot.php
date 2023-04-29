<?php
// Adam Canray: sabtu, Maret 2020
/*
BOT PENGANTAR
Materi EBOOK: Membuat Sendiri Bot Telegram dengan PHP
Ebook live http://telegram.banghasan.com/
oleh: bang Hasan HS
id telegram: @hasanudinhs
email      : banghasan@gmail.com
twitter    : @hasanudinhs
disampaikan pertama kali di: Grup IDT
dibuat: Juni 2016, Ramadhan 1437 H
nama file : PertamaBot.php
change log:
revisi 1 [15 Juli 2016] :
+ menambahkan komentar beberapa line
+ menambahkan kode webhook dalam mode comment
Pesan: baca dengan teliti, penjelasan ada di baris komentar yang disisipkan.
Bot tidak akan berjalan, jika tidak diamati coding ini sampai akhir.
*/

//isikan token dan nama botmu yang di dapat dari bapak bot :
$TOKEN      = TOKEN_BOT;
$usernamebot= USERNAME_BOT; // sesuaikan besar kecilnya, bermanfaat nanti jika bot dimasukkan grup.


// aktifkan ini jika perlu debugging
$debug = true;
 

// fungsi untuk mengirim/meminta/memerintahkan sesuatu ke bot 
function request_url($method)
{
    global $TOKEN;
    return "https://api.telegram.org/bot" . $TOKEN . "/". $method;
}
 
// me: spesifik pesan(seperti menggunakan :id)
// fungsi untuk meminta pesan 
// bagian ebook di sesi Meminta Pesan, polling: getUpdates
function get_updates($offset) 
{
    $url = request_url("getUpdates")."?offset=".$offset;
        $resp = file_get_contents($url);
        $result = json_decode($resp, true);
        if ($result["ok"]==1)
            return $result["result"];
        return array();
}


// fungsi untuk mebalas pesan, 
// bagian ebook Mengirim Pesan menggunakan Metode sendMessage
function send_reply($chatid, $msgid, $text)
{
    global $debug;
    // me: untuk parameter pada url
    $data = array(
        'chat_id' => $chatid,
        'text'  => $text,
        'reply_to_message_id' => $msgid   // <---- biar ada reply nya balasannya, opsional, bisa dihapus baris ini
    );
    // use key 'http' even if you send the request to https://...
    $options = array(
        'http' => array(
            'header'  => "Content-type: application/x-www-form-urlencoded\r\n",
            'method'  => 'POST',
            // me: membuild array(aasoc atau index) menjadi parameter url. 
            // contoh output -> "chat_id=123&text=halo&reply_to_message_id=321"
            'content' => http_build_query($data),
        ),
    );
    // me: stream_context_create($['wrapper']['optionForHttp']) --> membuat stream context
    $context  = stream_context_create($options);
    // me: file_get_contents($pathUrl,$include_path,$context) --> membaca seluruh file menjadi string 
    $result = file_get_contents(request_url('sendMessage'), false, $context);

    // me: tampilkan debbugging
    if ($debug) 
        print_r($result);
}
 
// fungsi mengolahan pesan, menyiapkan pesan untuk dikirimkan

function create_response($text, $message)
{
    global $usernamebot;
    // inisiasi variable hasil yang mana merupakan hasil olahan pesan
    $hasil = '';  

    $fromid = $message["from"]["id"]; // variable penampung id user
    $chatid = $message["chat"]["id"]; // variable penampung id chat
    $pesanid= $message['message_id']; // variable penampung id message


    // variable penampung username nya user
    isset($message["from"]["username"])
        ? $chatuser = $message["from"]["username"]
        : $chatuser = '';
    

    // variable penampung nama user

    isset($message["from"]["last_name"]) 
        ? $namakedua = $message["from"]["last_name"] 
        : $namakedua = '';   
    $namauser = $message["from"]["first_name"]. ' ' .$namakedua;

    // ini saya pergunakan untuk menghapus kelebihan pesan spasi yang dikirim ke bot.
    $textur = preg_replace('/\s\s+/', ' ', $text); 

    // memecah pesan dalam 2 blok array, kita ambil yang array pertama saja
    $command = explode(' ',$textur,2); //

    // me: identifikasi pesan
    // identifikasi perintah (yakni kata pertama, atau array pertamanya)
    switch ($command[0]) {

        // jika ada pesan /id, bot akan membalas dengan menyebutkan idnya user
        case '/id':
        case '/id'.$usernamebot : //dipakai jika di grup yang haru ditambahkan @usernamebot
            $hasil = "$namauser, ID kamu adalah $fromid";
            break;
        
        // jika ada permintaan waktu
        case '/time':
        case '/time'.$usernamebot :
            $hasil  = "$namauser, waktu lokal bot sekarang adalah :\n";
            $hasil .= date("d M Y")."\nPukul ".date("H:i:s");
            break;

        // balasan default jika pesan tidak di definisikan
        default:
            $hasil = 'Terimakasih, pesan telah kami terima.';
            break;
    }

    return $hasil;
}
 
// jebakan token, klo ga diisi akan mati
// boleh dihapus jika sudah mengerti
if (strlen($TOKEN)<20) 
    die("Token mohon diisi dengan benar!\n");

// fungsi pesan yang sekaligus mengupdate offset 
// biar tidak berulang-ulang pesan yang di dapat 
function process_message($message)
{
    $updateid = $message["update_id"];
    $message_data = $message["message"];
    if (isset($message_data["text"])) {
        $chatid = $message_data["chat"]["id"];
        $message_id = $message_data["message_id"];
        $text = $message_data["text"];
        $response = create_response($text, $message_data);
        if (!empty($response))
          send_reply($chatid, $message_id, $response);
    }
    return $updateid;
}
 
// hapus baris dibawah ini, jika tidak dihapus berarti kamu kurang teliti!
// die("Mohon diteliti ulang codingnya..\nERROR: Hapus baris atau beri komen line ini yak!\n");
 
// hanya untuk metode poll
// fungsi untuk meminta pesan
// baca di ebooknya, yakni ada pada proses 1 
function process_one()
{
    global $debug;
    $update_id  = 0;
    echo "-";

    // me: ambil update_id terakhirnya
    if (file_exists("last_update_id")) 
        $update_id = (int)file_get_contents("last_update_id");
 
    $updates = get_updates($update_id);

    // jika debug=0 atau debug=false, pesan ini tidak akan dimunculkan
    if ((!empty($updates)) and ($debug) )  {
        echo "\r\n===== isi diterima \r\n";
        print_r($updates);
    }
 
    foreach ($updates as $message)
    {
        echo '+';
        $update_id = process_message($message);
    }
    
    // update file id, biar pesan yang diterima tidak berulang
    file_put_contents("last_update_id", $update_id + 1);
}

// metode poll
// proses berulang-ulang
// sampai di break secara paksa
// tekan CTRL+C jika ingin berhenti 
while (true) {
    process_one();
    sleep(1);
}

// metode webhook
// secara normal, hanya bisa digunakan secara bergantian dengan polling
// aktifkan ini jika menggunakan metode webhook
/*
$entityBody = file_get_contents('php://input');
$pesanditerima = json_decode($entityBody, true);
process_message($pesanditerima);
*/



/*
 * -----------------------
 * Grup @botphp
 * Jika ada pertanyaan jangan via PM
 * langsung ke grup saja.
 * ----------------------
 
* Just ask, not asks for ask..
Sekian.
*/
    
?>