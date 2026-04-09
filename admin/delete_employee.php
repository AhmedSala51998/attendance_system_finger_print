<?php
include "../config.php";
if(!isset($_SESSION['employee_id']) || $_SESSION['role'] != 'admin'){
    header("Location: ../login"); exit();
}

if(isset($_GET['id'])){
    $emp_id = intval($_GET['id']);
    
    // ✅ جلب بيانات الموظف للتأكد إنه مش أدمن
    $check = $conn->query("SELECT role FROM employees WHERE id = $emp_id");
    $user = $check->fetch_assoc();
    
    if($user && $user['role'] == 'admin'){
        // منع حذف الأدمن
        header("Location: employees?error=admin");
        exit();
    }
    
    if($conn->query("DELETE FROM employees WHERE id = $emp_id")){
        header("Location: employees?success=deleted");
        exit();
    }
}
header("Location: employees");
?>