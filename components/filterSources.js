var pills = document.querySelectorAll('.source-pill');

var params = new URLSearchParams(location.search);
var sourceParam = params.get('source');

// If there is no source in the URL, use an empty string
if (sourceParam === null) {
  sourceParam = '';
}

// Split the comma sources into a list ("kamernet,funda" = ["kamernet", "funda"])
var sourceParts = sourceParam.split(',');
var activeSources = [];

for (var i = 0; i < sourceParts.length; i++) {
  var part = sourceParts[i].trim().toLowerCase();
  if (part !== '') {
    activeSources.push(part);
  }
}

// Highlight pills whose source is currently active in the URL
for (var i = 0; i < pills.length; i++) {
  var pill = pills[i];
  var pillSource = pill.dataset.source.toLowerCase();

  for (var j = 0; j < activeSources.length; j++) {
    if (activeSources[j] === pillSource) {
      pill.classList.add('active');
    }
  }
}

// When a pill is clicked toggle it on/off and reload the page with updated filters
for (var i = 0; i < pills.length; i++) {
  pills[i].addEventListener('click', function() {
    var key = this.dataset.source.toLowerCase();

    // If already active, remove it, otherwise add it
    var index = activeSources.indexOf(key);
    if (index !== -1) {
      activeSources.splice(index, 1);
    } else {
      activeSources.push(key);
    }

    // new URL with the updated source list
    var newParams = new URLSearchParams(location.search);

    if (activeSources.length > 0) {
      newParams.set('source', activeSources.join(','));
    } else {
      newParams.delete('source');
    }

    // Reload the page with the new filters applied
    location.href = '?' + newParams.toString();
  });
}
