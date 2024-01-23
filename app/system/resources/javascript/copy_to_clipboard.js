function copyToClipboard() {
  const spanId = document.getElementById("system_information");
  const textToCopy = spanId.innerText;

  navigator.clipboard.writeText(textToCopy)
    .then(function() {
      console.log('Text successfully copied to clipboard');
    })
    .catch(function(err) {
      console.error('Unable to copy text to clipboard', err);
    });
}
