export default function (url) {
  if (url === null) {
    return null;
  }

  return url.split(/[?#]/)[0];
}
