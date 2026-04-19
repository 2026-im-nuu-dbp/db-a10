<?php include "db.php"; ?>

<form method="POST">
帳號: <input name="account"><br>
暱稱: <input name="nickname"><br>
密碼: <input type="password" name="password"><br>
性別: <input name="gender"><br>
興趣: <input name="interests"><br>
<button type="submit">註冊</button>
</form>

<?php
if ($_POST) {
    $account = $_POST['account'];
    $nickname = $_POST['nickname'];
    $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
    $gender = $_POST['gender'];
    $interests = $_POST['interests'];

    $sql = "INSERT INTO dbusers (account, nickname, password, gender, interests)
            VALUES ('$account','$nickname','$password','$gender','$interests')";

    if ($conn->query($sql)) {
        echo "註冊成功";
    } else {
        echo "錯誤：" . $conn->error;
    }
}
?>