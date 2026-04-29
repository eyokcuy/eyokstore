<?php
/**
 * GameTopUp Pro - Flash Messages
 * Displays session flash messages via SweetAlert2
 */

if (isset($_SESSION['flash']) && is_array($_SESSION['flash'])) {
    $flashType = $_SESSION['flash']['type'] ?? 'info';
    $flashMessage = $_SESSION['flash']['message'] ?? '';
    
    // Clear flash after reading
    unset($_SESSION['flash']);
    
    if (!empty($flashMessage)):
?>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        Swal.fire({
            icon: <?php echo json_encode($flashType); ?>,
            title: <?php echo json_encode(ucfirst($flashType)); ?>,
            text: <?php echo json_encode($flashMessage); ?>,
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer);
                toast.addEventListener('mouseleave', Swal.resumeTimer);
            }
        });
    });
</script>
<?php 
    endif;
}
?>
