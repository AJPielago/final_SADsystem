<?php
// Function to create a colored image
function createColoredImage($width, $height, $color, $text, $filename) {
    $image = imagecreatetruecolor($width, $height);
    
    // Convert hex color to RGB
    $color = ltrim($color, '#');
    $r = hexdec(substr($color, 0, 2));
    $g = hexdec(substr($color, 2, 2));
    $b = hexdec(substr($color, 4, 2));
    
    // Set background color
    $bgColor = imagecolorallocate($image, $r, $g, $b);
    imagefill($image, 0, 0, $bgColor);
    
    // Add text
    $textColor = imagecolorallocate($image, 255, 255, 255);
    $fontSize = 5;
    $textWidth = imagefontwidth($fontSize) * strlen($text);
    $textHeight = imagefontheight($fontSize);
    $x = ($width - $textWidth) / 2;
    $y = ($height - $textHeight) / 2;
    imagestring($image, $fontSize, $x, $y, $text, $textColor);
    
    // Save image
    imagejpeg($image, $filename, 90);
    imagedestroy($image);
}

// Create waste sorting game images
$wasteItems = [
    ['plastic-bottle.jpg', '#3498db', 'Plastic Bottle'],
    ['apple-core.jpg', '#2ecc71', 'Apple Core'],
    ['battery.jpg', '#e74c3c', 'Battery'],
    ['paper.jpg', '#3498db', 'Paper'],
    ['banana-peel.jpg', '#2ecc71', 'Banana Peel'],
    ['paint-can.jpg', '#e74c3c', 'Paint Can'],
    ['glass-bottle.jpg', '#3498db', 'Glass Bottle'],
    ['coffee-grounds.jpg', '#2ecc71', 'Coffee Grounds'],
    ['medicine.jpg', '#e74c3c', 'Medicine']
];

foreach ($wasteItems as $item) {
    createColoredImage(200, 200, $item[1], $item[2], 'assets/waste/' . $item[0]);
}

// Create community images
createColoredImage(100, 100, '#3498db', 'Avatar', 'assets/community/avatar1.jpg');
createColoredImage(800, 400, '#2ecc71', 'Upcycling Project', 'assets/community/post1.jpg');

// Create media images
createColoredImage(400, 300, '#3498db', 'Recycling Quiz', 'assets/media/quiz.jpg');
createColoredImage(400, 300, '#2ecc71', 'Virtual Tour', 'assets/media/virtual-tour.jpg');
createColoredImage(600, 400, '#3498db', 'Success Story', 'assets/media/success-story.jpg');

echo "Images generated successfully!";
?> 