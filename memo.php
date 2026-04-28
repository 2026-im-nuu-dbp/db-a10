<?php
session_start();
include "db.php";

if (!isset($_SESSION['user_id'])) {
    die("請先登入");
}

$user_id = $_SESSION['user_id'];
$errors = [];

/* =====================
   縮圖函式（只支援 JPG）
===================== */
function createThumbnail($src, $dest, $new_width) {
    if (!file_exists($src)) {
        return false;
    }

    $source_image = imagecreatefromjpeg($src);
    if (!$source_image) {
        return false;
    }

    $width = imagesx($source_image);
    $height = imagesy($source_image);

    $new_height = floor($height * ($new_width / $width));
    $thumbnail = imagecreatetruecolor($new_width, $new_height);

    imagecopyresampled($thumbnail, $source_image, 0, 0, 0, 0,
                       $new_width, $new_height, $width, $height);

    return imagejpeg($thumbnail, $dest);
}

/* =====================
   取得修改資料
===================== */
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = intval($_GET['edit']);
    $stmt = $conn->prepare("SELECT * FROM dememo WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $edit_data = $result->fetch_assoc();
}

/* =====================
   新增 / 修改
===================== */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content'] ?? '');

    if ($content === '') {
        $errors[] = '請輸入備忘錄內容。';
    }

    if (!empty($_POST['edit_id'])) {
        $id = intval($_POST['edit_id']);

        if (empty($errors)) {
            $stmt = $conn->prepare("UPDATE dememo SET content = ? WHERE id = ? AND user_id = ?");
            $stmt->bind_param('sii', $content, $id, $user_id);
            $stmt->execute();
        }
    } else {
        if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            $errors[] = '請上傳 JPG 格式圖片。';
        } else {
            $tmp = $_FILES['image']['tmp_name'];
            $imageType = @exif_imagetype($tmp);

            if ($imageType !== IMAGETYPE_JPEG) {
                $errors[] = '僅支援 JPG 圖片。';
            }
        }

        if (empty($errors)) {
            $time = time();
            $basename = basename($_FILES['image']['name']);
            $uploadDir = 'uploads';

            if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true)) {
                $errors[] = '無法建立上傳資料夾。';
            }

            $image_path = $uploadDir . '/' . $time . '_' . $basename;
            $thumbnail_path = $uploadDir . '/thumb_' . $time . '_' . $basename;

            if (!move_uploaded_file($tmp, $image_path)) {
                $errors[] = '圖片上傳失敗。';
            }

            if (empty($errors) && !createThumbnail($image_path, $thumbnail_path, 150)) {
                $errors[] = '縮圖產生失敗，請確認圖片是否為有效 JPG。';
            }

            if (empty($errors)) {
                $stmt = $conn->prepare("INSERT INTO dememo (user_id, content, image_path, thumbnail_path) VALUES (?, ?, ?, ?)");
                $stmt->bind_param('isss', $user_id, $content, $image_path, $thumbnail_path);
                $stmt->execute();
            }
        }
    }

    if (empty($errors)) {
        header('Location: memo.php');
        exit;
    }
}

/* =====================
   刪除
===================== */
if (isset($_GET['del'])) {
    $id = intval($_GET['del']);
    $stmt = $conn->prepare("DELETE FROM dememo WHERE id = ? AND user_id = ?");
    $stmt->bind_param('ii', $id, $user_id);
    $stmt->execute();
    header('Location: memo.php');
    exit;
}

/* =====================
   查詢
===================== */
$stmt = $conn->prepare("SELECT * FROM dememo WHERE user_id = ? ORDER BY id DESC");
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>備忘錄系統</title>
</head>
<body>

<?php if (!empty($errors)): ?>
    <div style="color: red; margin-bottom: 16px;">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error, ENT_QUOTES, 'UTF-8'); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<h2><?php echo $edit_data ? "修改備忘錄" : "新增備忘錄"; ?></h2>

<form method="POST" enctype="multipart/form-data">
    <textarea name="content" rows="4" cols="50"><?php echo $edit_data['content'] ?? ''; ?></textarea><br><br>

    <?php if (!$edit_data): ?>
        圖片：<input type="file" name="image"><br><br>
    <?php endif; ?>

    <input type="hidden" name="edit_id" value="<?php echo $edit_data['id'] ?? ''; ?>">

    <button type="submit">
        <?php echo $edit_data ? "更新" : "新增"; ?>
    </button>
</form>

<br>
<a href="logout.php">登出</a>

<hr>

<h2>我的備忘錄</h2>

<?php while ($row = $result->fetch_assoc()): ?>

    <div style="margin-bottom:20px;">
        <p><?php echo nl2br($row['content']); ?></p>

        <?php if ($row['thumbnail_path']): ?>
            <img src="<?php echo $row['thumbnail_path']; ?>"><br>
        <?php endif; ?>

        <a href="?edit=<?php echo $row['id']; ?>">修改</a> |
        <a href="?del=<?php echo $row['id']; ?>" onclick="return confirm('確定刪除？')">刪除</a>
    </div>

    <hr>

<?php endwhile; ?>

</body>
</html>