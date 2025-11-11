// Prank Website Script
let clickCount = 0;
const messages = [
    "Anda harus menekan tombol OK untuk melanjutkan!",
    "Serius, tekan OK sekarang!",
    "Masih belum cukup, tekan OK lagi!",
    "Ayo dong, tekan OK!",
    "Capek ya? Tekan OK lagi!",
    "Belum selesai, tekan OK!",
    "Satu lagi, tekan OK!",
    "Hampir selesai, tekan OK!",
    "Terus tekan OK!",
    "Jangan berhenti, tekan OK!",
    "OK! OK! OK!",
    "Masih kurang, tekan OK!",
    "Ayo semangat, tekan OK!",
    "Belum boleh berhenti!",
    "Terus aja, tekan OK!",
    "Ini prank! Tapi tetap tekan OK! 😂"
];

document.addEventListener('DOMContentLoaded', function() {
    const prankSound = document.getElementById('prankSound');
    const okButton = document.getElementById('okButton');
    const counter = document.getElementById('counter');
    const messageElement = document.querySelector('.message');
    const prankBox = document.querySelector('.prank-box');

    // Auto-play sound dengan nada tinggi
    // Catatan: Browser modern memerlukan interaksi user untuk auto-play
    // Kita akan mencoba auto-play dan jika gagal, akan play saat klik pertama
    const playSound = () => {
        prankSound.play().catch(err => {
            console.log('Auto-play blocked, will play on first interaction');
        });
    };

    // Coba auto-play saat load
    playSound();

    // Fungsi untuk mengubah posisi tombol secara random
    const randomizeButtonPosition = () => {
        const randomX = Math.random() * 20 - 10; // -10 to 10
        const randomY = Math.random() * 20 - 10; // -10 to 10
        okButton.style.transform = `translate(${randomX}px, ${randomY}px)`;
    };

    // Event listener untuk tombol OK
    okButton.addEventListener('click', function() {
        // Pastikan sound playing
        if (prankSound.paused) {
            playSound();
        }

        clickCount++;
        counter.textContent = clickCount;

        // Ubah pesan secara random
        const randomMessage = messages[Math.floor(Math.random() * messages.length)];
        messageElement.textContent = randomMessage;

        // Animasi shake yang lebih kuat
        prankBox.style.animation = 'none';
        setTimeout(() => {
            prankBox.style.animation = 'shake 0.3s infinite';
        }, 10);

        // Randomize button position
        randomizeButtonPosition();

        // Ubah warna button secara random
        const colors = [
            'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
            'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
            'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
            'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
            'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
            'linear-gradient(135deg, #30cfd0 0%, #330867 100%)'
        ];
        okButton.style.background = colors[Math.floor(Math.random() * colors.length)];

        // Efek zoom
        okButton.style.transform = 'scale(1.2)';
        setTimeout(() => {
            okButton.style.transform = 'scale(1)';
            randomizeButtonPosition();
        }, 200);

        // Setelah 20 klik, tampilkan pesan khusus
        if (clickCount === 20) {
            messageElement.textContent = "🎉 Selamat! Anda sudah dikerjain! Ini cuma prank! 😂";
            messageElement.style.color = '#ff4757';
            messageElement.style.fontSize = '22px';
        }
    });

    // Tambahkan efek hover yang bergerak
    okButton.addEventListener('mouseenter', function() {
        if (clickCount > 5) {
            randomizeButtonPosition();
        }
    });

    console.log('🎵 CATATAN: Untuk mengganti suara nada tinggi, ganti src audio di index.html dengan file suara Anda sendiri!');
    console.log('📝 Contoh: <source src="./your-high-pitch-sound.mp3" type="audio/mpeg">');
});