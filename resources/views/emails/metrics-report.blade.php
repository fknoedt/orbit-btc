<!DOCTYPE html>
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .success { color: green; font-weight: bold; }
        .error { color: red; font-weight: bold; }
        .warning { color: orange; font-weight: bold; }
        .note { color: blue; }
        .caution { color: darkred; font-weight: bold; }
        .info { color: teal; }
        .title { font-size: 2em; font-weight: bold; margin-bottom: 0.5em; }
        .section { font-size: 1.5em; font-weight: bold; margin-top: 1em; margin-bottom: 0.5em; }
        .text { color: #333; } /* For writeln/text/default methods */
        /* Add more styles as needed for other methods */
    </style>
</head>
<body>
<h1>Metrics Monitoring Report [{{ $env }}]</h1>
{!! $buffer !!}
</body>
</html>
