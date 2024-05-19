<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>404 Not Found</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #ffffcc; /* Yellow background color */
            color: #333; /* Text color */
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            flex-direction: column;
        }
        .container {
            text-align: center;
        }
        h3 {
            margin-bottom: 10px;
        }
        button {
            padding: 10px 20px;
            background-color: #ffcc00; /* Yellow button color */
            border: none;
            color: #333; /* Text color */
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        button:hover {
            background-color: #ffc400; /* Darker yellow on hover */
        }
    </style>
</head>
<body>
    <img src="{{ asset('notfound.svg') }}" alt="Not Found Image">
    <div class="container">
        <h3>404 Not Found</h3>
        <span>The resource you are looking for is missing. If you are the administrator, please contact your webmaster.</span>
        <button onclick="window.location.href='/home'">Back</button>
    </div>
</body>
</html>
