(function(){
  var textarea = document.getElementById('igp-travel-pro-token-json');
  if (!textarea) return;
  var bar = document.createElement('p');
  var button = document.createElement('button');
  button.type = 'button';
  button.className = 'button';
  button.textContent = 'Format JSON';
  button.addEventListener('click', function(){
    try { textarea.value = JSON.stringify(JSON.parse(textarea.value), null, 2); }
    catch(e) { alert('Invalid JSON: ' + e.message); }
  });
  bar.appendChild(button);
  textarea.parentNode.insertBefore(bar, textarea.nextSibling);
})();
