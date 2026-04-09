<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<!-- ✅ المسار الذكي للجافاسكربت لضمان تحميل الدالات (Logout/Delete) -->
<?php 
    $current_script = $_SERVER['SCRIPT_NAME'];
    $path_prefix = (strpos($current_script, '/admin/') !== false || strpos($current_script, '/employee/') !== false) ? '../' : ''; 
?>
<script src="<?php echo $path_prefix; ?>assets/script.js?v=<?php echo time(); ?>"></script>

</body>
</html>