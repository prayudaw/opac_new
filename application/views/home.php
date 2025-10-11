<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pencarian</title>
    <link href="<?php echo base_url('assets/css/tailwind.min.css'); ?>" rel="stylesheet">
    <script src="<?php echo base_url('assets/js/jquery-3.6.0.min.js'); ?>"></script>
    <style>
    .slideshow-bg {
        position: absolute;
        inset: 0;
        z-index: -10;
        width: 100vw;
        height: 100vh;
        overflow: hidden;
    }

    .slideshow-bg img {
        position: absolute;
        width: 100vw;
        height: 100vh;
        object-fit: cover;
        opacity: 0;
        transition: opacity 1s ease;
    }

    .slideshow-bg img.active {
        opacity: 0.58;
    }
    </style>
</head>

<body class="relative min-h-screen flex flex-col justify-center items-center overflow-hidden">
    <!-- Slideshow Background -->
    <div class="slideshow-bg">
        <img src="<?php echo base_url('assets/img/img-1.jpeg'); ?>" class="active" alt="bg1">
        <img src="<?php echo base_url('assets/img/img-2.jpeg'); ?>" alt="bg2">
        <!-- <img src="https://images.unsplash.com/photo-1465101178521-c1a4c8a0f8f9?auto=format&fit=crop&w=1500&q=80"
            alt="bg3"> -->
    </div>
    <!-- Background  -->
    <div class="absolute inset-0 -z-10 pointer-events-none">
        <div class="w-full max-w-xl mx-auto mt-10 px-4 sm:px-6 lg:px-8 z-10">
            <div
                class="absolute top-0 left-1/2 transform -translate-x-1/2 blur-2xl opacity-10 w-[600px] h-[600px] rounded-full bg-gray-700">
            </div>
            <div class="absolute bottom-0 right-1/3 blur-2xl opacity-8 w-[400px] h-[400px] rounded-full bg-gray-800">
            </div>
            <div class="absolute top-1/3 left-0 blur-2xl opacity-5 w-[300px] h-[300px] rounded-full bg-gray-600"></div>
        </div>
    </div>
    <div class="w-full max-w-xl mx-auto mt-10 px-4 sm:px-6 lg:px-8 z-10">
        <div class="flex flex-col items-center mb-8 z-10">
            <span class="mb-6 text-5xl font-bold text-white drop-shadow-lg tracking-wide"
                style="color:#f3f7fa;">OPAC</span>
            <form id="form-search" action="javascript:void(0);" method="get" class="w-full">
                <div class="flex flex-col sm:flex-row gap-3 bg-white rounded-2xl shadow px-4 py-4 items-center">
                    <div class="relative w-full">
                        <input type="text" name="q" id="search-query" placeholder="Cari sesuatu..."
                            class="w-full outline-none bg-transparent text-base sm:text-lg px-3 py-3 pr-12 rounded-xl border border-gray-200 focus:border-blue-400 transition placeholder-gray-500 text-gray-900"
                            autofocus>
                        <!-- Tombol Voice Search -->
                        <button type="button" id="voice-search-btn" title="Cari dengan suara"
                            class="absolute inset-y-0 right-0 flex items-center px-3 text-gray-500 hover:text-blue-600 transition focus:outline-none">
                            <svg id="mic-icon" xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none"
                                viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                    d="M12 18v2m0 0a4 4 0 004-4h-8a4 4 0 004 4zm0-4V6m0 0a2 2 0 00-2 2v4a2 2 0 004 0V8a2 2 0 00-2-2z" />
                            </svg>
                        </button>
                    </div>
                    <button type="submit"
                        class="w-full sm:w-auto px-4 py-3 bg-blue-500 text-white rounded-xl font-semibold hover:bg-blue-600 transition flex items-center justify-center gap-2">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24"
                            stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                d="M21 21l-4.35-4.35m0 0A7.5 7.5 0 104.5 4.5a7.5 7.5 0 0012.15 12.15z" />
                        </svg>
                        Search
                    </button>
                </div>
            </form>
        </div>
        <div id="search-result" class="mt-6"></div>

</body>
<script>
$(function() {
    $('#form-search').on('submit', function(e) {
        e.preventDefault();
        var query = $('#search-query').val();
        if (!query) {
            $('#search-result').html('<div class="text-red-500">Masukkan kata kunci pencarian.</div>');
            return;
        }
        window.location.href = '<?php echo base_url() ?>result?q=' + encodeURIComponent(query);
    });

    // Slideshow background
    let slides = document.querySelectorAll('.slideshow-bg img');
    let idx = 0;
    setInterval(function() {
        slides[idx].classList.remove('active');
        idx = (idx + 1) % slides.length;
        slides[idx].classList.add('active');
    }, 5000);

    // Voice Search
    const voiceBtn = document.getElementById('voice-search-btn');
    const micIcon = document.getElementById('mic-icon');
    const searchInput = document.getElementById('search-query');
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    if (SpeechRecognition) {
        const recognition = new SpeechRecognition();
        recognition.lang = 'id-ID';
        recognition.interimResults = false;
        recognition.maxAlternatives = 1;

        voiceBtn.addEventListener('click', function() {
            recognition.start();
        });

        recognition.addEventListener('speechstart', function() {
            micIcon.classList.add('text-red-500', 'animate-pulse');
            searchInput.placeholder = 'Mendengarkan...';
        });

        recognition.addEventListener('result', function(e) {
            const transcript = e.results[0][0].transcript;
            searchInput.value = transcript;
            setTimeout(function() {
                $('#form-search').submit();
            }, 500);
        });

        recognition.addEventListener('speechend', function() {
            recognition.stop();
            micIcon.classList.remove('text-red-500', 'animate-pulse');
            searchInput.placeholder = 'Cari sesuatu...';
        });

        recognition.addEventListener('error', function() {
            micIcon.classList.remove('text-red-500', 'animate-pulse');
            searchInput.placeholder = 'Cari sesuatu...';
        });
    } else {
        voiceBtn.style.display = 'none';
    }
});
</script>
</div>
</body>

</html>