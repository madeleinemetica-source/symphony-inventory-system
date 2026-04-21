<!DOCTYPE html>
<html>
    <head lang="en">
    <meta charset="UTF-8"></meta>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Playground</title>
    <script src="https://cdn.tailwindcss.com"></script>
    </head>

    <body class="bg-blue-900 text-white flex justify-center items-center min-h-screen">
        <h1 class="text-4xl font-bold">Welcome to the Playground!</h1>
        <button id="test-btn" class="mt-6 px-4 py-2 bg-white text-blue-900 rounded-lg hover:bg-gray-200 transition">
            Click Me</button>
    </body>

    <script>
        const testButton = document.getElementById('test-btn');
        testButton.addEventListener('click', (e) => {
            document.body.classList.toggle('bg-blue-900');
        });
        </script>
</html>