<?php 
session_start();
include "db.php";
?>

<form method="POST">
帳號: <input name="account"><br>
密碼: <input type="password" name="password"><br>
<button type="submit">登入</button>
</form>

<?php
if ($_POST) {
    $account = $_POST['account'];
    $password = $_POST['password'];

    $sql = "SELECT * FROM dbusers WHERE account='$account'";
    $result = $conn->query($sql);

    if ($result->num_rows > 0) {
        $user = $result->fetch_assoc();

        if (password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];

            // ✅ 登入成功紀錄
            $conn->query("INSERT INTO dblog (user_account, is_success) VALUES ('$account',1)");

            header("Location: memo.php");
        } else {
            echo "密碼錯誤";
            $conn->query("INSERT INTO dblog (user_account, is_success) VALUES ('$account',0)");
        }
    } else {
        echo "帳號不存在";
        $conn->query("INSERT INTO dblog (user_account, is_success) VALUES ('$account',0)");
    }
}
?>