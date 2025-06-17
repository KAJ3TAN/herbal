<?php
session_start(); //inicjalizacja sesji, dostęp do superglobalnej zmiennej $_SESSION
if (!isset($_SESSION['username'])) {  /* Sprawdza, czy w sesji znajduje się nazwa użytkownika. Jeśli nie, użytkownik nie ma uprawnień do przeglądania tej strony 
                                        i zostaje przekierowany na stronę logowania (login.php).*/
    header("Location: login.php");
    exit;
}
$username = $_SESSION['username']; // Pobranie nazwy użytkownika z sesji
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HERBAL LTD.</title>
    <link rel="icon" href="./img/icon.png" type="image/x-icon">
    <style>
        body {
            margin: 0;
            font-family: Arial, sans-serif;
            background-color: #f4f4f4;
        }

        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background-color: #49d392;
            color: white;
            padding: 10px 20px;
        }

        .navbar .logo {
            background-color: #fff;
            font-size: 24px;
            font-weight: bold;
            color: #49d392;
            padding: 5px 10px;
            border-radius: 10px;
        }

        .navbar .right {
            display: flex;
            align-items: center;
        }

        .navbar .username {
            margin-right: 20px;
            font-size: 18px;
        }

        .navbar button {
            padding: 8px 15px;
            background-color: white;
            color: #49d392;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }

        .navbar button:hover {
            background-color: rgb(230, 230, 230);
        }

    
        .content {
            text-align: center;
            padding: 50px;
        }

        .content h1 {
            color: #333;
            margin-bottom: 50px;
        }

    
        .animated-card {
            display: inline-block;
            width: 300px;
            height: 200px;
            background-color: #fff;
            border-radius: 15px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            position: relative;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .animated-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 8px 16px rgba(0, 0, 0, 0.2);
            cursor: pointer;
        }

        
        .animated-card::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #49d392, rgb(97, 240, 197), #49d392);
            z-index: 2;
        }

    
        .animated-card .inner {
            padding: 20px;
            text-align: center;
            position: relative;
            z-index: 1;
        }

        .animated-card .title {
            font-size: 22px;
            font-weight: bold;
            color: #49d392;
        }

        .animated-card .people {
            margin-top: 10%;
            width: 35%;
        }


        .access-denied {
            color: red;
            font-size: 20px;
            font-weight: bold;
            text-align: center;
            margin-top: 20px;
        }
    </style>
    <script>
        function checkAccess() { //Funkcja sprawdza czy zalogowany użytkownik to admin jeśli nie, podczas próby wejścia na stronę "zarządzenie użytkownikami" wyświetla alert "Dostęp zabroniony".
            const isAdmin = <?php echo json_encode($username === 'admin'); ?>;
            if (!isAdmin) {
                alert('Dostęp zabroniony');
                return false;
            }
            return true;
        }
    </script>
</head>
<body>
    <div class="navbar">
        <div class="logo">HERBAL LTD. 🍃</div>
        <div class="right">
            <div class="username"><?php echo htmlspecialchars($username); ?></div>
            <form action="logout.php" method="POST" style="margin: 0;">
                <button type="submit">wyloguj</button>
            </form>
        </div>
    </div>

    <div class="content">
        <h1>Witamy w panelu sterowania Herbal LTD!</h1>
        <div class="animated-card" onclick="if (checkAccess()) window.location.href='user_management.php';">  <!-- jeżeli checkAccess zwróci true wykonywane jest przekierowanie do user_management.php--> 
            <div class="inner">
                <div class="title">Zarządzanie Użytkownikami</div>
                <img class="people" src="./img/people.png">
            </div>
        </div>
    </div>
</body>
</html>
