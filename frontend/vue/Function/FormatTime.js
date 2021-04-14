export default function (seconds) {
  let date = new Date(seconds * 1000);
  let hh = date.getUTCHours();
  let mm = date.getUTCMinutes();
  let ss = date.getSeconds();

  if (mm < 10) {
    mm = '0' + mm;
  }
  if (ss < 10) {
    ss = '0' + ss;
  }

  if (hh > 0) {
    if (hh < 10) {
      hh = '0' + hh;
    }

    return hh + ':' + mm + ':' + ss;
  }

  return mm + ':' + ss;
}
