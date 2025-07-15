<?php 
function getVisitCount(){
    $counterFile = 'counter.txt';

    if(!file_exists($counterFile)) {
        file_put_contents($counterFile, '0');
    }

    $count = (int)file_get_contents($counterFile);
    $count++;
    file_put_contents($counterFile, $count);
    
    return $count;
}

$currentTime = date('H:i');
$visitCount = getVisitCount();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Счетчик посещений</title>
</head>
<body>
    <p>Страница была загружена <?php echo $visitCount; ?> раз. Текущее время <?php echo $currentTime; ?></p>
</body>
</html>