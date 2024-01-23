function copy_to_clipboard() {
  const span_id = document.getElementById("system_information");
  const text_to_copy = span_id.innerText;

  navigator.clipboard.writeText(text_to_copy)
    .then(function() {
      console.log('Text successfully copied to clipboard');
    })
    .catch(function(err) {
      console.error('Unable to copy text to clipboard', err);
    });
}
