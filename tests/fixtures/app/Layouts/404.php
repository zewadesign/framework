<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Page Not Found</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="">
    <meta name="author" content="">
    <meta http-equiv="X-UA-Compatible" content="IE=100">
</head>

<body>
<div class="container">

    <div class="jumbotron">
        <h1>404 - Page Not Found</h1>

        <p>
            <?php if (isset($errorMessage)) {
                echo $errorMessage;
            } ?>
        </p>
    </div>

</div>

</body>
</html>