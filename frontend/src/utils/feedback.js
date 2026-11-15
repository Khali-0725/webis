import Swal from 'sweetalert2';

export function showFeedback(icon, title, text) {
  return Swal.fire({
    icon,
    title,
    text,
    timer: icon === 'success' ? 1800 : undefined,
    showConfirmButton: icon !== 'success',
  });
}
