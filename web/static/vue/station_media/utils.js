export function formatFileSize (bytes) {
  let s = ['bytes', 'KB', 'MB', 'GB', 'TB', 'PB', 'EB']
  let pos, d
  for (pos = 0; bytes >= 1000; pos++, bytes /= 1000) {
    d = Math.round(bytes * 10)
  }

  return pos ? [parseInt(d / 10), '.', d % 10, ' ', s[pos]].join('') : bytes + ' bytes'
}