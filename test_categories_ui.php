<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Categories</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
</head>
<body class="bg-gray-100 p-8">
    <div class="max-w-4xl mx-auto bg-white rounded-lg shadow p-6">
        <h1 class="text-2xl font-bold mb-6">Test Categories</h1>
        
        <div class="mb-6">
            <h2 class="text-lg font-semibold mb-2">Raw API Response:</h2>
            <pre id="rawResponse" class="bg-gray-100 p-4 rounded overflow-auto max-h-64">Loading...</pre>
        </div>
        
        <div>
            <h2 class="text-lg font-semibold mb-2">Categories Dropdown:</h2>
            <div class="relative w-64">
                <select id="categoryFilter" class="block w-full px-4 py-2 border border-gray-300 rounded-md shadow-sm focus:ring-blue-500 focus:border-blue-500">
                    <option value="">Loading categories...</option>
                </select>
            </div>
        </div>
    </div>

    <script>
        // Fetch and display the raw response
        fetch('api/get_categories.php')
            .then(response => response.json())
            .then(data => {
                // Display raw response
                document.getElementById('rawResponse').textContent = JSON.stringify(data, null, 2);
                
                // Populate dropdown if we have data
                if (data.success && data.data && data.data.length > 0) {
                    const select = document.getElementById('categoryFilter');
                    select.innerHTML = '<option value="">All Categories</option>';
                    
                    data.data.forEach(category => {
                        const option = document.createElement('option');
                        option.value = category.id;
                        option.textContent = category.name;
                        select.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error:', error);
                document.getElementById('rawResponse').textContent = 'Error loading categories: ' + error.message;
            });
    </script>
</body>
</html>
