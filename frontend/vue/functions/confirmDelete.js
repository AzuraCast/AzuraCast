import Swal
  from 'sweetalert2';

export default function (options) {
  const defaults = {
    title: 'Delete Record?',
    confirmButtonText: 'Delete',
    confirmButtonColor: '#e64942',
    showCancelButton: true,
    focusCancel: true
  };

  return Swal.fire({ ...defaults, ...options });
};
