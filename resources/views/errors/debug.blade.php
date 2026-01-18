<!DOCTYPE html>
<html>

<head>
    <title>Debug Error</title>
    <style>
        body {
            font-family: monospace;
            padding: 20px;
            background: #fff0f0;
            color: #333;
        }

        .error {
            background: #fff;
            padding: 20px;
            border: 1px solid #ffcccc;
            border-radius: 5px;
        }

        h1 {
            color: #cc0000;
        }

        pre {
            background: #f8f8f8;
            padding: 10px;
            overflow-x: auto;
            border: 1px solid #ddd;
        }
    </style>
</head>

<body>
    <h1>Emergency Debug Error Capture</h1>
    <div class="error">
        <h3>{{ $message }}</h3>
        <p><strong>File:</strong> {{ $file }}:{{ $line }}</p>
        <hr>
        <pre>{{ $trace }}</pre>
    </div>
</body>

</html>