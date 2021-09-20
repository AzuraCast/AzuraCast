import _
  from 'lodash';

import Swal
  from 'sweetalert2';

export default function (options) {
  let defaults = {
    title: 'Delete Record?',
    confirmButtonText: 'Delete',
    confirmButtonColor: '#e64942',
    showCancelButton: true,
    focusCancel: true
  };

  let config = _.defaultsDeep(_.clone(options), defaults);

  return Swal.fire(config);
};
