<?php 
session_start();
require_once('../../config.php');

$id = $_GET['id'];
$result = mysqli_query($connection, "DELETE FROM trainee WHERE id = '$id'");

$_SESSION['berhasil'] = 'Data berhasil dihapus!';
header('Location: trainee.php');
exit;

include('../layout/footer.php');

?>