<title>SIMT - Luminor Hotel Bogor Padjadjaran</title>

<link rel="icon" type="image/png" sizes="32x32" href="../assets/img/favicon.png">
<link rel="shortcut icon" href="../assets/img/favicon.png">

<?php 
session_start();
require_once('../config.php');

if(isset($_POST['login'])){
  $username = $_POST['username'];
  $password = $_POST['password'];

  $result = mysqli_query($connection, "SELECT users.*, trainee.nama, trainee.nip, trainee.nama_divisi, trainee.lokasi_presensi, trainee.foto 
  FROM users 
  LEFT JOIN trainee ON users.id_trainee = trainee.id 
  WHERE username = '$username'");

  if(mysqli_num_rows($result) === 1){
    $row = mysqli_fetch_assoc($result);
    if(password_verify($password, $row['password'])){
      if($row['status'] == 'Aktif'){
        $_SESSION['login'] = true;
        $_SESSION['id'] = $row['id'];
        $_SESSION['role'] = $row['role'];
        $_SESSION['nama'] = $row['nama'] ?? $row['username'];
        $_SESSION['nip'] = $row['nip'] ?? '-';
        $_SESSION['nama_divisi'] = $row['nama_divisi'] ?? 'Trainee';
        $_SESSION['lokasi_presensi'] = $row['lokasi_presensi'] ?? '';
        $_SESSION['foto'] = $row['foto'] ?? 'default.png';

        if($row['role'] === 'Admin'){
          header('Location: ../admin/home/home.php');
          exit();
        } elseif($row['role'] === 'Trainee'){
          header('Location: ../trainee/home/home.php');
          exit();
        }

      } else {
        $_SESSION['gagal'] = 'Akun anda belum aktif!';
      }
    } else {
      $_SESSION['gagal'] = 'Password salah, silakan coba lagi!';
    }
  } else {
    $_SESSION['gagal'] = 'Username salah, silakan coba lagi!';
  }
}
?>

<!doctype html>
<html lang="id">
  <head>
    <meta charset="utf-8"/>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <title>SIMT - Luminor Hotel Bogor Padjadjaran</title>

    <!-- CSS -->
    <link href="<?= base_url() ?>/assets/css/tabler.min.css" rel="stylesheet"/>
    <link href="<?= base_url() ?>/assets/css/tabler-vendors.min.css" rel="stylesheet"/>
    <link href="<?= base_url() ?>/assets/css/demo.min.css" rel="stylesheet"/>

    <style>
      @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap');
      body {
        background-color: #dadfff;
        font-family: 'Inter', sans-serif;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100vh;
        margin: 0;
      }

      .login-card {
        background: #ffffff;
        border-radius: 18px;
        box-shadow: 0 10px 40px rgba(0,0,0,0.1);
        color: #333;
        padding: 3rem 4rem;
        width: 100%;
        max-width: 600px;
        animation: fadeIn 0.8s ease;
      }

      /* Animasi untuk logo */
      @keyframes float {
        0% { transform: translateY(0px); }
        50% { transform: translateY(-10px); }
        100% { transform: translateY(0px); }
      }

      @keyframes fadeLogo {
        from { opacity: 0; transform: scale(0.9); }
        to { opacity: 1; transform: scale(1); }
      }

      .brand-logo {
        display: block;
        margin: 0 auto 0.75rem auto;
        width: auto;
        height: 75px;
        animation: fadeLogo 1s ease-out, float 4s ease-in-out infinite;
      }
      

      .brand-logo:hover {
        transform: scale(1.05);
        transition: transform 0.3s ease;
      }

      .brand-text {
        text-align: center;
        font-size: 1.45rem;
        font-weight: 900;
        letter-spacing: 1px;
        line-height: 1.3;
        margin-top: 0.5rem;
        margin-bottom: 1.5rem;

        background: linear-gradient(
          135deg,
          #0b5aa6 0%,
          #0b2a4a 50%,
          #000000 100%
        );
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
      }

      .brand-line1{
        display:block;
        font-size: 1.35rem;
        font-weight: 800;
        letter-spacing: .4px;
        background: linear-gradient(135deg, #0a3b6e, #0b5aa6);
        -webkit-background-clip: text;
        background-clip: text;
        color: transparent;
      }

      .brand-line2{
        display:block;
        margin-top: 2px;
        font-size: 1.35rem;
        font-weight: 800;
        color: #0a3b6e;
        letter-spacing: .3px;
      }

      .form-control {
        background: #f8f9fa;
        border: 1px solid #ddd;
        color: #333;
        padding: 0.75rem;
        font-size: 1rem;
        border-radius: 10px;
      }

      .form-control:focus {
        border-color: #0074b3;
        box-shadow: 0 0 0 3px rgba(0,179,122,0.2);
      }

      .btn-primary {
        background: #0074b3;
        border: none;
        transition: all 0.3s ease;
        font-weight: 600;
        padding: 0.75rem;
        border-radius: 10px;
      }

      .btn-primary:hover{
        background-position: 100% 50% !important;
        transform: translateY(-1px);
        box-shadow: 0 18px 34px rgba(11,90,166,.32) !important;
      }

      .btn-primary:active{
        transform: translateY(0) scale(.99);
        filter: brightness(.95);
      }

      .show-pass {
        cursor: pointer;
        color: #0074b3;
        position: absolute;
        right: 15px;
        top: 50%;
        transform: translateY(-50%);
        opacity: 0.7;
      }

      @keyframes fadeIn {
        from {opacity: 0; transform: translateY(30px);}
        to {opacity: 1; transform: translateY(0);}
      }

      .form-label {
        font-weight: 500;
        color: #444;
      }

      .sub-login-title{
        font-size: 1.15rem;   /* kecilin dikit */
        font-weight: 600;
        color: #6c757d;       /* abu muda */
        margin-bottom: 1.5rem !important;
      }

      body {
        font-family: 'Inter', sans-serif;
        min-height: 100vh;
        margin: 0;
        display: grid;
        place-items: center;
        padding: 24px;
        overflow-x: hidden;
        position: relative;
      }

      body::before{
        content:"";
        position: fixed;
        inset: 0;
        z-index: -2;
        background: url("<?= base_url() ?>/assets/img/bg-hotel.jpg") center/cover no-repeat;

        /* ini yang bikin foto lebih kebaca */
        filter: blur(1px) brightness(.8) contrast(1.05);
        transform: scale(1.02);
        opacity: .95;
      }

      /* overlay gradient biru biar elegan + teks kebaca */
      body::after{
        content:"";
        position: fixed;
        inset: 0;
        z-index: -1;

        /* overlay dibuat lebih transparan */
        background:
          radial-gradient(900px 500px at 25% 20%, rgba(11,90,166,.18), transparent 60%),
          radial-gradient(800px 500px at 80% 30%, rgba(10,59,110,.22), transparent 65%),
          linear-gradient(135deg, rgba(11,42,74,.95), rgba(10,59,110,.55));
      }

      .login-card {
        background: rgba(255,255,255,.92);
        border: 1px solid rgba(255,255,255,.6);
        border-radius: 20px;
        box-shadow: 0 22px 70px rgba(2, 8, 23, .30);
        backdrop-filter: blur(10px);
        color: #0f172a;
        padding: 42px 44px 34px;
        width: 100%;
        max-width: 620px;

        opacity: 0;
        transform: translateY(18px) scale(.985);
        animation: cardIn .75s cubic-bezier(.2,.9,.2,1) forwards;
      }

      @keyframes cardIn{
        to{ opacity:1; transform: translateY(0) scale(1); }
      }

      .login-card:hover{
        transform: translateY(-2px);
        transition: .25s ease;
        box-shadow: 0 28px 86px rgba(2, 8, 23, .34);
      }

      .btn-primary {
        background: linear-gradient(135deg, #0a3b6e, #0b5aa6);
        border: none;
        transition: transform .15s ease, box-shadow .15s ease, filter .15s ease;
        font-weight: 800;
        padding: 0.75rem;
        border-radius: 14px;
        height: 46px;
        box-shadow: 0 14px 26px rgba(11,90,166,.26);
      }

      .btn-primary:hover {
        transform: translateY(-1px);
        box-shadow: 0 18px 34px rgba(11,90,166,.32);
        filter: saturate(1.06);
      }

      .btn-primary{
        font-family: 'Inter', sans-serif;
        font-weight: 800;
        font-size: 1rem;
      }

      .btn{
        font-family: 'Inter', sans-serif;
      }

      .btn-primary{
      /* gradient panjang */
      background-image: linear-gradient(135deg,
        #0b5aa6 0%,
        #0b2a4a 50%,
        #0b5aa6 100%
      );
      background-size: 200% 200%;
      background-position: 0% 50%;

      transition: background-position .35s ease, transform .15s ease, box-shadow .15s ease;
    }
    .btn-primary:hover{
      background-position: 100% 50%;
    }
    
    .btn-primary:active{
      filter: brightness(.95);
      transform: translateY(0) scale(.99);
    }

    .login-card{
      opacity: 0;
      transform: translateY(32px) scale(.96);
      animation: cardIn .9s cubic-bezier(.2,.9,.2,1) forwards;
    }

    @keyframes cardIn{
      to{ opacity:1; transform: translateY(0) scale(1); }
    }

    .brand-text {
      text-align: center !important;
      font-size: 1.55rem !important;
      font-weight: 2500 !important;
      letter-spacing: 1.2px !important;
      line-height: 1.3 !important;
      margin-top: 0.5rem !important;
      margin-bottom: 1.5rem !important;

      background: linear-gradient(
        135deg,
        #0b5aa6 0%,
        #0b2a4a 50%,
        #000000 100%
      ) !important;

      -webkit-background-clip: text !important;
      background-clip: text !important;
      color: transparent !important;
    }


    </style>
  </head>
  <body>
    <div class="login-card">
      <!-- Logo dengan animasi -->
      <img src="<?= base_url() ?>/assets/img/logo.png" alt="Logo" class="brand-logo">
      <div class="brand-text">
        <span class="brand-line1">SISTEM INFORMASI MANAJEMEN -</span>
        <span class="brand-line2">TRAINEE (SIMT)</span>
      </div>
      <h2 class="text-center mb-4 sub-login-title">Silakan Masuk ke Akun Anda</h2>

      <form action="" method="post" autocomplete="off">
        <div class="mb-3">
          <label class="form-label">Username</label>
          <input type="text" class="form-control" name="username" placeholder="Masukkan username" required autofocus>
        </div>

        <div class="mb-3 position-relative">
          <label class="form-label">Password</label>
          <input type="password" class="form-control" id="password" name="password" placeholder="Masukkan password" required>
        </div>

        <button type="submit" class="btn btn-primary w-100" name="login">Masuk</button>
      </form>
    </div>

    <!-- Sweet Alert -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <script>
      function togglePassword(){
        const input = document.getElementById('password');
        input.type = input.type === 'password' ? 'text' : 'password';
      }

      <?php if(isset($_SESSION['gagal'])): ?>
        Swal.fire({
          icon: 'error',
          title: 'Oops...',
          text: '<?= $_SESSION['gagal'] ?>',
          confirmButtonColor: '#0074b3'
        });
        <?php unset($_SESSION['gagal']); ?>
      <?php endif; ?>
    </script>
  </body>
</html>
