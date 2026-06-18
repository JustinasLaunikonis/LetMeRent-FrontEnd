// Looks up Dutch cities from PDOK (the Dutch national geo service)
//  Both the front page search bar (searchCity.js) and the map sidebar
// (mapCity.js) call window.suggestDutchCities(), so they search the same data in the same way.

// We use PDOK's "suggest" endpoint, which returns the best matching places for whatever the user has typed so far

var citySuggestUrl = 'https://api.pdok.nl/bzk/locatieserver/search/v3_1/suggest';

function citySuggestGetDocs(responseData) {
  if (responseData && responseData.response && Array.isArray(responseData.response.docs)) {
    return responseData.response.docs;
  }
  return [];
}

// PDOK shows each place as "Town, Municipality, Province", for example
// "Leeuwarden, Leeuwarden, Fryslan".
// We only need the town name, which is thepart before the first comma.
function citySuggestPlaceName(displayName) {
  if (!displayName) {
    return '';
  }

  var commaIndex = displayName.indexOf(',');
  if (commaIndex === -1) {
    return displayName.trim();
  }

  return displayName.slice(0, commaIndex).trim();
}

window.suggestDutchCities = function (query, onReady) {
  var searchText = '';
  if (query) {
    searchText = query.trim();
  }

  // Wait until there is something to search for.
  if (searchText === '') {
    if (onReady) {
      onReady([]);
    }
    return;
  }

  // Ask PDOK for places that match the text
  // rows=10 keeps the dropdown short
  var url = citySuggestUrl + '?q=' + encodeURIComponent(searchText) + '&fq=type:woonplaats&rows=10';

  fetch(url)
    .then(function (response) {
      return response.json();
    })
    .then(function (responseData) {
      var docs = citySuggestGetDocs(responseData);

      var names = [];
      var seen = {};   // used as a set so the same name is not added twice
      for (var i = 0; i < docs.length; i++) {
        var name = citySuggestPlaceName(docs[i].weergavenaam);
        if (name !== '' && seen[name] !== true) {
          seen[name] = true;
          names.push(name);
        }
      }

      if (onReady) {
        onReady(names);
      }
    })
    .catch(function () {
      if (onReady) {
        onReady(null);
      }
    });
};
