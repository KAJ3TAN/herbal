<?php
session_start(); //inicjalizacja sesji, dostęp do superglobalnej zmiennej $_SESSION
if (!isset($_SESSION['username'])) { /* Sprawdza, czy w sesji znajduje się nazwa użytkownika. Jeśli nie, użytkownik nie ma uprawnień do przeglądania tej strony 
                                        i zostaje przekierowany na stronę logowania (login.php).*/
    header("Location: login.php");
    exit;
}

$loggedInUsername = $_SESSION['username']; //przypisanie nazwy użytkownika z ciasteczka sesji do zmiennej loggedInUsername

if ($loggedInUsername !== 'admin') { //sprawdzamy czy zalogowany użytkownik to admin, jeżeli nie wyświetla się komunikat "Dostęp zabroniony"
    echo "<h1>Dostęp zabroniony</h1>";
    exit;
}

$conn = new mysqli('localhost', 'root', '', 'users_db'); //nawiązanie połączenia z bazą danych users_db

$message = "";

//obsługa dodawania użytkownika
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) { /* Sprawdza, czy w tablicy $_POST istnieje klucz 'add_user' użytkownik przesłał 
                                                                            formularz za pomocą odpowiedniego przycisku(name="add_user") */
    //pobieranie danych przesłanych poprzez formularz (trim usuwa whitespaces)
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $password = trim($_POST['password']);

  
   //Sprawdza czy imię i nazwisko zawierają tylko litery oraz czy nie są identyczne
    if (!preg_match('/^[a-zA-ZĄąĆćĘęŁłŃńÓóŚśŹźŻż]+$/u', $firstname)) {
        $message = "Imię zawiera niedozwolone znaki!";
    } elseif (!preg_match('/^[a-zA-ZĄąĆćĘęŁłŃńÓóŚśŹźŻż]+$/u', $lastname)) {
        $message = "Nazwisko zawiera niedozwolone znaki!";
    }
    elseif (strcasecmp($firstname, $lastname) === 0) {
        $message = "Imię i nazwisko nie mogą być takie same.";
    } else {
        $base_username = strtolower(substr($firstname, 0, 1) . $lastname); //tworzy login dla użytkownika (username)
        $username = $base_username;
        $counter = 1; /*Licznik jest używany do tworzenia unikalnej nazwy użytkownika, jeśli podstawowa nazwa ($base_username) już istnieje w bazie. Obsługa wyjątków np: mamy dwóch użytkowników
         o nazwisku kowalski z czego jednen nazywa sie Adam, a drugi Artur, aby każdy login był unikalny dodajemy cyfre na końcu tj.: akowalski, akowalski1 */
        while (true) {
            $check_query = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE username = ?");
            $check_query->bind_param("s", $username);
            $check_query->execute();
            $result = $check_query->get_result();
            $row = $result->fetch_assoc();
            $check_query->close();

            if ($row['count'] == 0) {
                break; //username jest unikalny
            }

            //dodanie cyfry do username, jeśli taki już istnieje
            $username = $base_username . $counter;
            $counter++;
        }

        // Znalezienie najmniejszego dostępnego ID
        $result = $conn->query("SELECT MIN(t1.id + 1) AS next_id 
                                FROM users t1 
                                LEFT JOIN users t2 
                                ON t1.id + 1 = t2.id 
                                WHERE t2.id IS NULL");
        $row = $result->fetch_assoc();
        $next_id = $row['next_id'] ?? 1; 


        // dodanie nowego użytkownika
        $stmt = $conn->prepare("INSERT INTO users (id, username, firstname, lastname, password) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("issss", $next_id, $username, $firstname, $lastname, $password);

        if ($stmt->execute()) {
            $message = "Użytkownik został pomyślnie dodany!";
            header("Location: user_management.php");
            exit;
        } else {
            $message = "Błąd: " . $stmt->error;
        }

        $stmt->close();
    }
}

// usuwanie użytkownika
if ($_SERVER['REQUEST_METHOD'] === 'GET' && isset($_GET['delete_user'])) {
    $id_to_delete = intval($_GET['delete_user']);
    $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
    $stmt->bind_param("i", $id_to_delete);

    if ($stmt->execute()) {
        $message = "Użytkownik został pomyślnie usunięty!";
        header("Location: user_management.php");
        exit;
    } else {
        $message = "Błąd: " . $stmt->error;
    }

    $stmt->close();
}

//edytowanie danych użytkownika
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['edit_user'])) {
    $user_id = intval($_POST['user_id']);
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $password = trim($_POST['password']);

    if (!preg_match('/^[a-zA-Z]+$/', $firstname)) {
        $message = "Error: First Name can only contain letters.";
    } elseif (!preg_match('/^[a-zA-Z]+$/', $lastname)) {
        $message = "Error: Last Name can only contain letters.";
    } else {
        $base_username = strtolower(substr($firstname, 0, 1) . $lastname);
        $username = $base_username;

        $counter = 1;
        while (true) {
            $check_query = $conn->prepare("SELECT COUNT(*) as count FROM users WHERE username = ? AND id != ?");
            $check_query->bind_param("si", $username, $user_id);
            $check_query->execute();
            $result = $check_query->get_result();
            $row = $result->fetch_assoc();
            $check_query->close();

            if ($row['count'] == 0) {
                break; 
            }

            $username = $base_username . $counter;
            $counter++;
        }

        $stmt = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, password = ?, username = ? WHERE id = ?");
        $stmt->bind_param("ssssi", $firstname, $lastname, $password, $username, $user_id);

        if ($stmt->execute()) {
            $message = "User updated successfully!";
            header("Location: user_management.php");
            exit;
        } else {
            $message = "Error: " . $stmt->error;
        }

        $stmt->close();
    }
}
$result = $conn->query("SELECT id, username, firstname, lastname FROM users");
$editUser = null;
if (isset($_GET['edit_user'])) {
    $editUserId = intval($_GET['edit_user']);
    $editUserQuery = $conn->prepare("SELECT * FROM users WHERE id = ?");
    $editUserQuery->bind_param("i", $editUserId);
    $editUserQuery->execute();
    $editUser = $editUserQuery->get_result()->fetch_assoc();
    $editUserQuery->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HERBAL-LTD | zarządzanie </title>
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

       

        h1 {
            margin-left: 20px;
            color: #333;
        }

        .table-content {
            border-radius: 20px;
            width: 80%;
            margin: 0 auto;
        }

        table {
            border-radius: 20px;
            width: 100%;
            border-collapse: collapse;
            margin: 0 auto;
        }

        table, th, td {
            border: 1px solid #ddd;
        }

        th, td {
            padding: 10px;
            text-align: left;
        }

        th {
            background-color: #49d392;
            color: white;
        }

        .delete-button {
            text-decoration: none;
            padding: 5px 10px;
            background-color: tomato;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .delete-button:hover {
            background-color: rgb(187, 15, 15);
        }

        .button-container {
            text-align: center;
            margin-top: 20px;
        }

        .button-container label {
            display: inline-block;
            padding: 10px 20px;
            background-color: #49d392;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 18px;
        }

        .button-container label:hover {
            background-color: #3ea77f;
        }

        .message {
            color: red;
            font-size: 16px;
            margin-top: 20px;
            text-align: center;
        }

        
        .modal {
            display: none;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6); 
            justify-content: center;
            align-items: center;
        }

        .modal-content {
            background-color: white;
            padding: 20px;
            border-radius: 15px;
            width: 400px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3); 
            animation: fadeIn 0.5s ease; 
        }

        .modal-content h2 {
            margin-top: 0;
            color: #49d392;
            font-size: 24px;
        }

        .modal-content input {
            width: 80%;
            padding: 10px;
            margin: 10px 0;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
            box-sizing: border-box;
        }

        .modal-content button {
            padding: 10px 20px;
            background-color: #49d392;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            margin-top: 10px;
        }

        .modal-content .modal-close{
            background-color: gray;
        }

        .modal-content .modal-close:hover{
            background-color: #666;
        }

        .modal-content button:hover {
            background-color: #3ea77f;
        }

        .modal-close {
            margin-top: 15px;
            padding: 8px 15px;
            background-color: gray;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .modal-close:hover {
            background-color: darkgray;
        }

        
        #addUserCheckbox:checked ~ .modal {
            display: flex; 
        }

       .edit-user-main {
        display: flex;
            position: fixed;
            z-index: 1;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.6); 
            justify-content: center;
            align-items: center;
       }


        .edit-container {
            width: 30%;
            height: 40%;
            text-align: center;
            margin-top: 20px;
            background: #fff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            max-width: 400px;
            margin: 20px auto;
            animation: fadeIn 0.5s ease;
        }

        .edit-container .btns {
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        
        }

        .btns button {
            font-size: 15px;
        }

        .edit-container h2 {
            color: #49d392;
        }

        .edit-container input {
            margin: 0 auto;
            display: block;
            width: 70%;
            padding: 10px;
            margin-bottom: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 16px;
        }

        .edit-container button {
            padding: 10px 20px;
            background-color: #49d392;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            margin-top: 5px;
            margin-bottom: 10px;
        }

        .edit-container button:hover {
            background-color: #3ea77f;
        }

        .cancel-button {
            display: inline-block;
            padding: 10px 20px;
            background-color: gray;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            margin-left: 10px;
        }

        .cancel-button:hover {
            background-color: #666;
        }

        .edit-button {
            background-color: rgb(24, 128, 240);
            color: white;
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
        }

        .edit-button:hover {
            background-color:rgb(25, 104, 189);
        }


                

                @keyframes fadeIn {
                    from {
                        opacity: 0;
                        transform: scale(0.9);
                    }
                    to {
                        opacity: 1;
                        transform: scale(1);
                    }
                }
</style>
</head>
<body>
    
    <div class="navbar">
        <div class="logo">HERBAL LTD. 🍃</div>
        <div class="right">
            <div class="username"><?php echo htmlspecialchars($loggedInUsername); ?></div>
            <form action="logout.php" method="POST" style="margin: 0;">
                <button type="submit">wyloguj</button>
            </form>
        </div>
    </div>
    
    <h1>Zarządzanie Użytkownikami</h1> 
    <div class="table-content">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nazwa użytkownika</th>
                    <th>Imię</th>
                    <th>Nazwisko</th>
                    <th>Akcja</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($row = $result->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($row['id']); ?></td>
                        <td><?php echo htmlspecialchars($row['username']); ?></td>
                        <td><?php echo htmlspecialchars($row['firstname']); ?></td>
                        <td><?php echo htmlspecialchars($row['lastname']); ?></td>
                        <td>
                            <?php if ($row['username'] !== 'admin'): ?>
                            <a href="?delete_user=<?php echo $row['id']; ?>" class="delete-button">Usuń</a>
                            <a href="?edit_user=<?php echo $row['id']; ?>" class="edit-button">Edytuj</a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>

        <?php if ($editUser): ?>
   <div class="edit-user-main">
        <div class="edit-container">
            <h2>Edytuj dane</h2>
            <form method="POST" action="">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($editUser['id']); ?>">
                <input type="text" name="firstname" placeholder="Imię" value="<?php echo htmlspecialchars($editUser['firstname']); ?>" required>
                <input type="text" name="lastname" placeholder="Nazwisko" value="<?php echo htmlspecialchars($editUser['lastname']); ?>" required>
                <input type="password" name="password" placeholder="Nowe hasło" required>
                <div class="btns">
                    <button type="submit" name="edit_user">Zapisz zmiany</button>
                    <a href="user_management.php" class="cancel-button">Anuluj</a>
                </div>
               
            </form>
        </div>
   </div>             

<?php endif; ?>


        
        <div class="button-container">
            <label for="addUserCheckbox">Dodaj użytkownika</label>
        </div>

      
        <?php if (!empty($message)): ?>
            <div class="message"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
    </div>

  
    <input type="checkbox" id="addUserCheckbox" style="display: none;" />
    <div class="modal">
        <div class="modal-content">
            <h2>Dodaj nowego użytkownika</h2>
            <form method="POST" action="">
                <input type="text" name="firstname" placeholder="Imię" required>
                <input type="text" name="lastname" placeholder="Nazwisko" required>
                <input type="password" name="password" placeholder="Hasło" required>
                <button type="submit" name="add_user">Dodaj</button>
            </form>
            <button class="modal-close" onclick="document.getElementById('addUserCheckbox').checked = false;">Anuluj</button>
        </div>
    </div>
</body>
</html>
