<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $page->title }}</title>
    <link rel="stylesheet" href="https://cdn.tailwindcss.com">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <header class="mb-8">
            <h1 class="text-4xl font-bold text-gray-800">{{ $page->title }}</h1>
        </header>

        <main class="bg-white rounded-lg shadow-md p-6">
            <div class="prose max-w-none">
                {!! $page->content !!}
            </div>
        </main>

        <footer class="mt-8 text-center text-gray-600">
            <p>Powered by Buni CMS</p>
        </footer>
    </div>
</body>
</html>