<?php
session_start(); //inicjacja sesji PHP (przechowywanie danych użytkownika w superglobalnej zmiennej $_SESSION)
$message = ""; // przechowuje komunikaty błędów

if ($_SERVER['REQUEST_METHOD'] === 'POST') { //sprawdza czy metoda żądania to POST, jeśli tak to pobiera wartości z pól formularza (nazwa użytkownika i hasło) 
    $username = $_POST['username']; 
    $password = $_POST['password'];

    $db_host = getenv('DB_HOST');
    $db_user = getenv('DB_USER');
    $db_pass = getenv('DB_PASS');
    $db_name = getenv('DB_NAME'); 
    $conn = new mysqli($db_host, $db_user, $db_pass, $db_name); //połączenie z bazą danych

   
    $sql = "SELECT * FROM users WHERE username = ? AND password = ?";  /*pytajniki bronią przed atakami SQL INJECTION(dane podane przez użytkownika są traktowane jako tekst,
                                                                        a nie jako część kodu Dzięki temu dane użytkownika są oddzielone od kodu SQL, co zapobiega SQL Injection.)
                                                                        Dane są wprowadzane w miejsce pytajników(?) */
    $stmt = $conn->prepare($sql); //stmt mienna przechowująca przygotowane zapytanie.
    $stmt->bind_param("ss", $username, $password); //metoda bind_param przypisuje konkretne wartości do parametrów ? w zapytaniu. Argument "ss" ozanacza typ danych czyli string.
    $stmt->execute(); //Metoda execute() wysyła przygotowane zapytanie do bazy danych i wykonuje je z podanymi wartościami.
    $result = $stmt->get_result(); //Metoda get_result() pobiera wyniki zapytania z bazy danych

    if ($result->num_rows > 0) {   //jeżeli wynik zawiera co najmniej jeden rekord, ozancza to, że dane logowania są prawdziwe
        $_SESSION['username'] = $username; //ciasteczko $_SESSION zapisuje nazwę użytkownika w sesji
        header("Location: welcome.php");  //przekierowanie użytkownika na stronę welcome.php
        exit;
    } else {
        $message = "Nieprawidłowa nazwa użytkownika lub hasło!";
    }

    $stmt->close(); //Zamknięcie zapytania
    $conn->close(); //Zamknięcie połączenia z bazą danych 
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HERBAL-LTD | logowanie</title>
    <link rel="icon" href="./assets/icon.png" type="image/x-icon">
    <style>
        body {
            font-family: Arial, sans-serif;
            background-image: url(./assets/background.png);
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        .login-container {
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            text-align: center;
            width: 300px;
        }
        input {
            margin: 10px 0;
            padding: 10px;
            width: 70%;
            border: 1px solid #ccc;
            border-radius: 5px;
        }
        button {
            padding: 10px 20px;
            background-color:#49d392;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background-color:rgb(66, 175, 124);
        }
        .error {
            color: red;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <h1>HERBAL LTD.🍃</h1>
        <?php if (!empty($message)): ?> 
            <p class="error"><?php echo htmlspecialchars($message); //Jeśli zmienna $message nie jest pusta, wyświetla komunikat o błędzie "Nieprawidłowa nazwa użytkownika lub hasło!"?></p> 
        <?php endif; ?> 
        <form method="POST" action="">
            <input type="text" name="username" placeholder="Nazwa użytkownika" required>
            <input type="password" name="password" placeholder="Hasło" required>
            <button type="submit">ZALOGUJ SIĘ</button>
        </form>
    </div>
</body>
</html>
