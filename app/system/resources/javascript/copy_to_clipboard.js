function copy_to_clipboard() {
  const element_id = document.getElementById("system_information");
  const text_to_copy = element_id.value;

  navigator.clipboard.writeText(text_to_copy)
    .then(function() {
      console.log('Text successfully copied to clipboard');
    })
    .catch(function(err) {
      console.error('Unable to copy text to clipboard', err);
    });
}