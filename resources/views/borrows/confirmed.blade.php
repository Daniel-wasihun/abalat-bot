<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Borrow Request Confirmed</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 flex items-center justify-center min-h-screen">
    <div class="max-w-md w-full bg-white shadow-xl rounded-2xl overflow-hidden p-8 text-center">
        <div class="w-16 h-16 bg-emerald-100 text-emerald-600 rounded-full flex items-center justify-center mx-auto mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" class="w-8 h-8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" />
            </svg>
        </div>
        <h1 class="text-2xl font-bold text-gray-900 mb-2">Request Confirmed!</h1>
        <p class="text-gray-600 mb-6">
            You have successfully confirmed your borrow request for <strong>{{ is_array($borrow->book->title) ? ($borrow->book->title['en'] ?? reset($borrow->book->title)) : $borrow->book->title }}</strong>.
        </p>
        <p class="text-sm text-gray-500">
            Please visit the library to collect your book.
        </p>
    </div>
</body>
</html>
