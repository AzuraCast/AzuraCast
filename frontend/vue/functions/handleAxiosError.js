export default function (error, notifyMessage = 'An error occurred and your request could not be completed.') {
  if (error.response) {
    // Request made and server responded
    notifyMessage = error.response.data.message;
    console.error(notifyMessage);
  } else if (error.request) {
    // The request was made but no response was received
    console.error(error.request);
  } else {
    // Something happened in setting up the request that triggered an Error
    console.error('Error', error.message);
  }

  notify('<b>' + notifyMessage + '</b>', 'danger');

  return notifyMessage;
}
