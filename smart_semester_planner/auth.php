<?php
session_start();

if (isset($_POST['role'])) {
    $_SESSION['role'] = $_POST['role'];
}

$role = $_SESSION['role'];
?>

<h2><?php echo ucfirst($role); ?> Login</h2>

<form action="signin.php" method="POST">

    <input type="hidden" name="role" value="<?php echo $role; ?>">

    <input type="text" name="id_or_username" placeholder="ID / Username" required><br>

    <input type="email" name="email" placeholder="Email" required><br>

    <input type="password" name="password" placeholder="Password (admin only)" ><br>

    <button type="submit">Submit</button>
</form>