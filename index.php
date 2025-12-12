<?php
session_start();
require_once __DIR__ . '/includes/config.php';

$pageTitle = "DewScent | 당신의 향기를 찾아서";
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>

    <!-- 폰트 -->
    <link href="https://fonts.googleapis.com/css2?family=Cormorant+Garamond:wght@300;400;500;600&family=Noto+Sans+KR:wght@200;300;400;500;600&display=swap" rel="stylesheet">

    <!-- 메인 스타일 -->
    <link rel="stylesheet" href="public/css/style.css?v=7">

</head>
<body>

    <?php include __DIR__ . '/includes/header.php'; ?>

    <main id="main">
        <?php include __DIR__ . '/includes/home.php'; ?>
        <!-- ❌ 여기서 footer는 include 하지 않음 -->
    </main>

    <!-- ✅ footer는 main 밖, body 안에서 한 번만 include -->
    <?php include __DIR__ . '/includes/footer.php'; ?>

    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <?php include __DIR__ . '/includes/modals.php'; ?>

    <!-- API 클라이언트 + 메인 스크립트 (캐시 방지용 버전 파라미터) -->
    <script src="public/js/api.js?v=4"></script>
    <script src="public/js/main.js?v=6"></script>
</body>
</html>
