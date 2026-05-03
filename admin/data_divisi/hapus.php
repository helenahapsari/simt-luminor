<?php 
session_start();
require_once('../../config.php');

$id = $_GET['id'];
$result = mysqli_query($connection, "DELETE FROM divisi WHERE id = '$id'");

$_SESSION['berhasil'] = 'Data berhasil dihapus!';
header('Location: divisi.php');
exit;

include('../layout/footer.php');

?>