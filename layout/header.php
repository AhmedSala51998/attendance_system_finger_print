<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>نظام الحضور والانصراف الذكي</title>
    <!-- Google Fonts - Cairo -->
    <link href="https://fonts.googleapis.com/css2?family=Cairo:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome الأحدث -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- ✅ المسار الذكي لضمان عمل التصميم في كل مكان -->
    <?php 
        $current_script = $_SERVER['SCRIPT_NAME'];
        $path_prefix = (strpos($current_script, '/admin/') !== false || strpos($current_script, '/employee/') !== false) ? '../' : ''; 
    ?>
    <link rel="stylesheet" href="<?php echo $path_prefix; ?>assets/style.css?v=<?php echo time(); ?>">
</head>
<body>