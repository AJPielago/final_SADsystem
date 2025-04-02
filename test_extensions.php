<?php
echo "Checking required extensions:<br>";
echo "GD Extension: " . (extension_loaded('gd') ? 'Enabled' : 'Disabled') . "<br>";
echo "MBString Extension: " . (extension_loaded('mbstring') ? 'Enabled' : 'Disabled') . "<br>";
echo "ZIP Extension: " . (extension_loaded('zip') ? 'Enabled' : 'Disabled') . "<br>";
?> 