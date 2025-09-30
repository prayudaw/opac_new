<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pencarian</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
</head>

<body class="relative min-h-screen flex flex-col justify-center items-center overflow-hidden">
    <!-- Background Estetik Soft & Natural -->
    <div class="absolute inset-0 -z-10 pointer-events-none">
        <div class="w-full h-full bg-gradient-to-tr from-gray-300 via-gray-500 to-gray-700">
            <!-- Lingkaran blur dinamis -->
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
            <span class="mb-6 text-5xl font-bold text-white drop-shadow-lg tracking-wide">OPAC</span>
            <form id="form-search" action="javascript:void(0);" method="get" class="w-full">
                <div class="flex flex-row gap-3 bg-white rounded-2xl shadow px-4 py-4 items-center">
                    <input type="text" name="q" id="search-query" placeholder="Cari sesuatu..."
                        class="flex-1 outline-none bg-transparent text-base sm:text-lg px-3 py-3 rounded-xl border border-gray-200 focus:border-blue-400 transition placeholder-gray-500 text-gray-900"
                        autofocus>
                    <button type="submit"
                        class="px-4 py-3 bg-blue-500 text-white rounded-xl font-semibold hover:bg-blue-600 transition flex items-center gap-2">
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
        <!-- Hasil pencarian akan ditampilkan di sini -->
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
            // Redirect ke halaman hasil pencarian
            window.location.href = '<?php echo base_url() ?>result?q=' + encodeURIComponent(query);
        });
    });
</script>
</div>
</body>

</html>