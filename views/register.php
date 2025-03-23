<!-- filepath: c:\xamppSAD\htdocs\SADsystem\views\register.php -->
<?php
include '../collector/routes_data.php'; // Include the routes data file
?>

<!DOCTYPE html>
<html>
<head>
    <title>Register</title>
</head>
<body>
    <h2>Register</h2>
    <form action="submit_registration.php" method="POST">
        <label for="username">Username:</label>
        <input type="text" id="username" name="username" required>
        <label for="email">Email:</label>
        <input type="email" id="email" name="email" required>
        <label for="password">Password:</label>
        <input type="password" id="password" name="password" required>
        <label for="building">Building:</label>
        <select id="building" name="building" required>
            <?php foreach ($routes as $route): ?>
                <option value="<?php echo $route['location']; ?>"><?php echo $route['location']; ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit">Register</button>
    </form>
</body>
</html>