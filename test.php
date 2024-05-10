<?php
$url = "https://images.unsplash.com/photo-1712313171623-5df9435ec12b?crop=entropy&cs=tinysrgb&fit=max&fm=jpg&ixid=M3w1OTQzNzZ8MHwxfHJhbmRvbXx8fHx8fHx8fDE3MTQ2NTg3MTB8&ixlib=rb-4.0.3&q=80&w=1080";
$imageDirectory = "images/";        
$imageData = file_get_contents($url);
        $fileName = uniqid() . '.jpg'; // Generate unique filename
        $filePath = $imageDirectory . $fileName;

        if (file_put_contents($filePath, $imageData) !== false) {
            echo "Image downloaded and saved: $fileName\n";
        } else {
            echo "Error saving image: $fileName\n";
        }
?>
